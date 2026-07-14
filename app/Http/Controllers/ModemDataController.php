<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\GenieDeviceStatus;
use App\Models\Installation;
use App\Models\InventoryItem;
use App\Models\ModemDataRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ModemDataController extends Controller
{
    public function index()
    {
        $modemTypes = InventoryItem::query()
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
            'mac_address' => 'required|string|max:50',
            'serial_number' => 'required|string|max:100',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'coordinates' => 'nullable|string|max:100',
        ]);

        $customerName = trim((string) $validated['customer_name']);
        $macAddress = $this->normalizeMacAddress($validated['mac_address']);
        $serialNumber = $this->normalizeSerialNumber($validated['serial_number']);
        $modemType = isset($validated['modem_type']) ? trim((string) $validated['modem_type']) : null;
        $latitude = $validated['latitude'] ?? null;
        $longitude = $validated['longitude'] ?? null;

        return DB::transaction(function () use ($customerName, $macAddress, $serialNumber, $modemType, $latitude, $longitude, $validated) {
            $normalizedName = mb_strtolower($customerName);
            $normalizedSerial = mb_strtolower($serialNumber);
            $macCompact = strtoupper((string) preg_replace('/[^A-Fa-f0-9]/', '', $macAddress));

            $customer = null;
            $matchedBy = null;

            // 1. Match by ONU Serial
            if ($normalizedSerial !== '') {
                $customer = Customer::query()
                    ->whereRaw('LOWER(TRIM(onu_serial)) = ?', [$normalizedSerial])
                    ->first();
                if ($customer) {
                    $matchedBy = 'serial ONU';
                }
            }

            // 2. Match by WAN MAC
            if (! $customer && $macCompact !== '') {
                $customer = Customer::query()
                    ->whereRaw("REPLACE(REPLACE(UPPER(COALESCE(wan_mac, '')), ':', ''), '-', '') = ?", [$macCompact])
                    ->first();
                if ($customer) {
                    $matchedBy = 'WAN MAC';
                }
            }

            // 3. Match by Genie Device Status
            if (! $customer && $normalizedSerial !== '') {
                $statusBySerial = GenieDeviceStatus::query()
                    ->whereNotNull('customer_id')
                    ->whereRaw('LOWER(TRIM(onu_serial)) = ?', [$normalizedSerial])
                    ->orderByDesc('last_inform')
                    ->first();
                if ($statusBySerial && $statusBySerial->customer_id) {
                    $customer = Customer::find($statusBySerial->customer_id);
                    if ($customer) {
                        $matchedBy = 'mapping GenieACS (serial ONU)';
                    }
                }
            }

            // 4. Match by exact name
            if (! $customer) {
                $customer = Customer::query()
                    ->whereRaw('LOWER(TRIM(name)) = ?', [$normalizedName])
                    ->first();
                if ($customer) {
                    $matchedBy = 'nama pelanggan (exact)';
                }
            }

            // 5. Match by similar name
            if (! $customer) {
                $customer = Customer::query()
                    ->whereRaw('LOWER(name) LIKE ?', ['%'.$normalizedName.'%'])
                    ->orderBy('id')
                    ->first();
                if ($customer) {
                    $matchedBy = 'nama pelanggan (mirip)';
                }
            }

            // 6. Create new customer if no match
            if (! $customer) {
                $customer = Customer::create([
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

            // Update Genie Device Status
            if ($normalizedSerial !== '') {
                GenieDeviceStatus::query()
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

            $installation = Installation::firstOrCreate(
                [
                    'customer_id' => $customer->id,
                    'serial_number' => $serialNumber,
                    'mac_address' => $macAddress,
                ],
                [
                    'technician_id' => auth()->id(),
                    'status' => 'completed',
                    'plan_date' => now()->toDateString(),
                    'notes' => $installationNotes,
                    'coordinates' => $validated['coordinates'] ?? null,
                ]
            );

            if ($installation->status !== 'completed' && $installation->status !== 'cancelled') {
                $installation->update([
                    'technician_id' => $installation->technician_id ?? auth()->id(),
                    'status' => 'completed',
                    'coordinates' => $validated['coordinates'] ?? $installation->coordinates,
                ]);
            }

            $installationMessage = $installation->wasRecentlyCreated
                ? ' Installation baru dibuat (ID '.$installation->id.').'
                : ' Data instalasi sudah ada (ID '.$installation->id.').';

            $syncMessage = ' Data pelanggan berhasil disinkronkan ke customer ID '.$customer->id.' (match: '.($matchedBy ?? 'manual').').';

            ModemDataRecord::create([
                'user_id' => auth()->id(),
                'customer_id' => $customer?->id,
                'customer_name' => $customerName,
                'modem_type' => $modemType,
                'mac_address' => $macAddress,
                'serial_number' => $serialNumber,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'coordinates' => $validated['coordinates'] ?? null,
            ]);

            AuditLog::log('created', null, [], ['modem_data' => $validated], 'Modem data recorded for customer '.$customer->id);

            return redirect()->route('modem-data.index')->with('success', 'Data modem berhasil disimpan.'.$syncMessage.$installationMessage);
        });
    }

    /**
     * Normalize MAC address to AA:BB:CC:DD:EE:FF format.
     */
    protected function normalizeMacAddress(string $mac): string
    {
        $mac = preg_replace('/[^A-Fa-f0-9]/', '', $mac);
        $mac = strtoupper($mac);
        return implode(':', str_split($mac, 2));
    }

    /**
     * Normalize serial number to uppercase, trimmed, no spaces.
     */
    protected function normalizeSerialNumber(string $serial): string
    {
        return strtoupper(trim(preg_replace('/\s+/', '', $serial)));
    }
}
