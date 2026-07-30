<?php

namespace App\Livewire\ISP\PPPoEUser;

use App\Models\Customer;
use App\Models\HotspotProfile;
use App\Models\Package;
use App\Models\Router;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Edit extends Component
{
    public Customer $customer;

    public $name;
    public $phone;
    public $email;
    public $address;

    public $pppoeUser;
    public $pppoePassword;

    public $customerId;
    public $portalPassword;
    public $createPortalUser = false;

    public $routerId;
    public $packageId;

    public $status;
    public $activationDate;

    public $notes;
    public $packageLegacyName = null;

    public function mount(Customer $customer)
    {
        $this->customer = $customer;
        $this->name = $customer->name;
        $this->phone = $customer->phone;
        $this->email = $customer->email;
        $this->address = $customer->address;
        $this->pppoeUser = $customer->pppoe_user;
        $this->pppoePassword = $customer->pppoe_password;
        $this->customerId = $customer->user?->username ?? '';
        $this->createPortalUser = $customer->user !== null;
        $this->routerId = $customer->router_id;
        $this->status = $customer->status;
        $this->activationDate = $customer->created_at->format('Y-m-d');

        // Backward compatibility: prefer hotspot_profile_id, fallback package_id
        $resolved = null;
        if (! empty($customer->hotspot_profile_id)) {
            $resolved = HotspotProfile::query()->pppoe()->find($customer->hotspot_profile_id);
            if ($resolved) {
                $this->packageId = $customer->hotspot_profile_id;
            }
        }
        if ($resolved === null && ! empty($customer->package_id)) {
            $resolved = HotspotProfile::query()->pppoe()->find($customer->package_id);
            if ($resolved) {
                $this->packageId = $customer->package_id;
            } else {
                // Legacy Package mode: attach info label
                $legacy = Package::find($customer->package_id);
                if ($legacy) {
                    $this->packageLegacyName = $legacy->name;
                    $this->packageId = null; // force user reselect from HotspotProfile PPPoE
                }
            }
        }
    }

    public function save()
    {
        $rules = [
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'pppoeUser' => 'required|string|unique:customers,pppoe_user,' . $this->customer->id . '|max:255',
            'pppoePassword' => 'required|string|max:255',
            'routerId' => 'required|exists:routers,id',
            'packageId' => 'required|exists:hotspot_profiles,id',
            'status' => 'required|in:active,suspend,terminated',
            'activationDate' => 'required|date',
        ];
        $this->validate($rules);

        if ($this->createPortalUser) {
            $this->validate([
                'email' => 'required|email|unique:users,email,' . ($this->customer->user_id ?? 'NULL'),
            ]);
            if ($this->portalPassword) {
                $this->validate(['portalPassword' => 'min:8']);
            }
        }

        DB::beginTransaction();

        try {
            $profile = HotspotProfile::query()->pppoe()->findOrFail($this->packageId);

            $this->customer->update([
                'name' => $this->name,
                'phone' => $this->phone,
                'email' => $this->email,
                'address' => $this->address,
                'pppoe_user' => $this->pppoeUser,
                'pppoe_password' => $this->pppoePassword,
                'router_id' => $this->routerId,
                'package_id' => $this->packageId,
                'hotspot_profile_id' => $this->packageId,
                'package' => $profile->name,
                'status' => $this->status,
            ]);

            if ($this->createPortalUser) {
                $customerRole = \App\Models\Role::where('name', 'customer')->first();
                if ($this->customer->user) {
                    $userData = [
                        'name' => $this->name,
                        'email' => $this->email,
                        'username' => $this->customerId,
                    ];
                    if ($this->portalPassword) {
                        $userData['password'] = bcrypt($this->portalPassword);
                    }
                    $this->customer->user->update($userData);
                } else {
                    $this->validate(['portalPassword' => 'required|min:8']);
                    $user = User::create([
                        'name' => $this->name,
                        'email' => $this->email,
                        'username' => $this->customerId,
                        'password' => bcrypt($this->portalPassword),
                        'role_id' => $customerRole?->id,
                        'is_active' => true,
                    ]);
                    $this->customer->update(['user_id' => $user->id]);
                }
            } else {
                if ($this->customer->user) {
                    $this->customer->user->delete();
                    $this->customer->update(['user_id' => null]);
                }
            }

            DB::commit();

            session()->flash('success', 'Pelanggan PPPoE berhasil diperbarui (Paket: ' . $profile->name . ').');
            return redirect()->route('isp.pppoe-users.index');
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Gagal memperbarui pelanggan: ' . $e->getMessage());
        }
    }

    public function render()
    {
        $packages = HotspotProfile::query()
            ->pppoe()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('price')
            ->get();

        if ($packages->count() === 0 && class_exists(Package::class)) {
            $packages = Package::where('is_active', true)->where('package_type', 'pppoe')->get();
        }

        return view('livewire.isp.pppoe-user.edit', [
            'routers' => Router::where('is_active', true)->get(),
            'packages' => $packages,
        ])->layout('layouts.app', [
            'title' => 'Edit Pelanggan PPPoE'
        ]);
    }
}
