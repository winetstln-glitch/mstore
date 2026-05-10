<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ModemDataController extends Controller
{
    public function index()
    {
        $modemTypes = \App\Models\InventoryItem::query()
            ->where('type_group', 'material')
            ->where(function ($q) {
                $q->where('type', 'like', '%onu%')
                    ->orWhere('name', 'like', '%onu%')
                    ->orWhere('model', 'like', '%onu%')
                    ->orWhere('description', 'like', '%onu%');
            })
            ->orderBy('type')
            ->orderBy('brand')
            ->orderBy('model')
            ->get()
            ->map(function ($item) {
                $byName = trim((string) $item->name);
                if ($byName !== '') {
                    return $byName;
                }

                $composed = trim(implode(' ', array_filter([
                    $item->brand,
                    $item->model,
                ])));
                if ($composed !== '') {
                    return $composed;
                }

                $byModel = trim((string) $item->model);
                if ($byModel !== '') {
                    return $byModel;
                }

                $byType = trim((string) $item->type);
                if ($byType !== '' && strcasecmp($byType, 'onu') !== 0) {
                    return $byType;
                }

                return null;
            })
            ->filter()
            ->unique(fn ($value) => mb_strtolower((string) $value))
            ->values();

        return view('modem_data.index', compact('modemTypes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'modem_type' => 'nullable|string|max:255',
            'mac_address' => 'required|string|max:20',
            'serial_number' => 'required|string|max:100',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'coordinates' => 'nullable|string|max:100',
        ]);

        $customerName = trim((string) $validated['customer_name']);
        $macAddress = strtoupper(trim((string) $validated['mac_address']));
        $serialNumber = trim((string) $validated['serial_number']);
        $modemType = isset($validated['modem_type']) ? trim((string) $validated['modem_type']) : null;
        $latitude = $validated['latitude'] ?? null;
        $longitude = $validated['longitude'] ?? null;
        $normalizedName = mb_strtolower($customerName);
        $normalizedSerial = mb_strtolower(trim($serialNumber));
        $macCompact = strtoupper((string) preg_replace('/[^A-Fa-f0-9]/', '', $macAddress));

        $customer = null;
        $matchedBy = null;

        if ($normalizedSerial !== '') {
            $customer = \App\Models\Customer::query()
                ->whereRaw('LOWER(TRIM(onu_serial)) = ?', [$normalizedSerial])
                ->first();
            if ($customer) {
                $matchedBy = 'serial ONU';
            }
        }

        if (! $customer && $macCompact !== '') {
            $customer = \App\Models\Customer::query()
                ->whereRaw("REPLACE(REPLACE(UPPER(COALESCE(wan_mac, '')), ':', ''), '-', '') = ?", [$macCompact])
                ->first();
            if ($customer) {
                $matchedBy = 'WAN MAC';
            }
        }

        if (! $customer && $normalizedSerial !== '') {
            $statusBySerial = \App\Models\GenieDeviceStatus::query()
                ->whereNotNull('customer_id')
                ->whereRaw('LOWER(TRIM(onu_serial)) = ?', [$normalizedSerial])
                ->orderByDesc('last_inform')
                ->first();
            if ($statusBySerial && $statusBySerial->customer_id) {
                $customer = \App\Models\Customer::find($statusBySerial->customer_id);
                if ($customer) {
                    $matchedBy = 'mapping GenieACS (serial ONU)';
                }
            }
        }

        if (! $customer) {
            $customer = \App\Models\Customer::query()
                ->whereRaw('LOWER(TRIM(name)) = ?', [$normalizedName])
                ->first();
            if ($customer) {
                $matchedBy = 'nama pelanggan (exact)';
            }
        }

        if (! $customer) {
            $customer = \App\Models\Customer::query()
                ->whereRaw('LOWER(name) LIKE ?', ['%'.$normalizedName.'%'])
                ->orderBy('id')
                ->first();
            if ($customer) {
                $matchedBy = 'nama pelanggan (mirip)';
            }
        }

        if (! $customer) {
            $customer = \App\Models\Customer::create([
                'name' => $customerName,
                'address' => null,
                'status' => 'active',
                'wan_mac' => $macAddress,
                'onu_serial' => $serialNumber,
                'device_model' => $modemType,
                'latitude' => $latitude,
                'longitude' => $longitude,
            ]);
            $matchedBy = 'pelanggan baru otomatis';
        } else {
            $customer->update([
                'wan_mac' => $macAddress,
                'onu_serial' => $serialNumber,
                'device_model' => $modemType ?: $customer->device_model,
                'address' => $customer->address === 'Auto dibuat dari Pendataan Modem' ? null : $customer->address,
                'latitude' => $latitude ?? $customer->latitude,
                'longitude' => $longitude ?? $customer->longitude,
            ]);
        }

        if ($normalizedSerial !== '') {
            \App\Models\GenieDeviceStatus::query()
                ->whereRaw('LOWER(TRIM(onu_serial)) = ?', [$normalizedSerial])
                ->update([
                    'customer_id' => $customer->id,
                    'onu_serial' => $serialNumber,
                    'updated_at' => now(),
                ]);
        }

        $installationNotes = trim(implode("\n", array_filter([
            'Auto dari Pendataan Modem',
            'Type: '.($modemType ?: '-'),
            'MAC: '.$macAddress,
            'SN: '.$serialNumber,
        ])));

        $installation = \App\Models\Installation::firstOrCreate(
            [
                'customer_id' => $customer->id,
                'serial_number' => $serialNumber,
                'mac_address' => $macAddress,
            ],
            [
                'technician_id' => auth()->id(),
                'status' => 'registered',
                'plan_date' => now()->toDateString(),
                'notes' => $installationNotes,
                'coordinates' => $validated['coordinates'] ?? null,
            ]
        );
        $installationMessage = $installation->wasRecentlyCreated
            ? ' Installation baru dibuat (ID '.$installation->id.').'
            : ' Data instalasi sudah ada (ID '.$installation->id.').';

        $syncMessage = ' Data pelanggan berhasil disinkronkan ke customer ID '.$customer->id.' (match: '.($matchedBy ?? 'manual').').';

        DB::table('modem_data_records')->insert([
            'user_id' => auth()->id(),
            'customer_id' => $customer?->id,
            'customer_name' => $customerName,
            'modem_type' => $modemType,
            'mac_address' => $macAddress,
            'serial_number' => $serialNumber,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'coordinates' => $validated['coordinates'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('modem-data.index')->with('success', 'Data modem berhasil disimpan.'.$syncMessage.$installationMessage);
    }
}
