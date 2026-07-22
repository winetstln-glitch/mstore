<?php

namespace App\Livewire\ISP\PPPoEUser;

use App\Models\Customer;
use App\Models\Package;
use App\Models\Router;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Illuminate\Support\Str;

class Create extends Component
{
    // Identitas Pelanggan
    public $name;
    public $phone;
    public $email;
    public $address;

    // Login Internet
    public $pppoeUser;
    public $pppoePassword;

    // Login Portal
    public $customerId;
    public $portalPassword;
    public $createPortalUser = false;

    // Paket
    public $routerId;
    public $packageId;

    // Aktivasi
    public $status = 'active';
    public $activationDate;

    // Notes
    public $notes;

    public function mount()
    {
        $this->activationDate = now()->format('Y-m-d');
        $this->customerId = strtoupper(Str::random(8));
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'pppoeUser' => 'required|string|unique:customers,pppoe_user|max:255',
            'pppoePassword' => 'required|string|max:255',
            'customerId' => 'required|string|max:255',
            'routerId' => 'required|exists:routers,id',
            'packageId' => 'required|exists:packages,id',
            'status' => 'required|in:active,suspend,terminated',
            'activationDate' => 'required|date',
        ]);

        if ($this->createPortalUser) {
            $this->validate([
                'email' => 'required|email|unique:users,email',
                'portalPassword' => 'required|min:8',
            ]);
        }

        DB::beginTransaction();

        try {
            $package = Package::findOrFail($this->packageId);

            $customer = Customer::create([
                'name' => $this->name,
                'phone' => $this->phone,
                'email' => $this->email,
                'address' => $this->address,
                'pppoe_user' => $this->pppoeUser,
                'pppoe_password' => $this->pppoePassword,
                'router_id' => $this->routerId,
                'package_id' => $this->packageId,
                'package' => $package->name,
                'status' => $this->status,
                'created_at' => $this->activationDate,
            ]);

            if ($this->createPortalUser) {
                $customerRole = \App\Models\Role::where('name', 'customer')->first();
                $user = User::create([
                    'name' => $this->name,
                    'email' => $this->email,
                    'username' => $this->customerId,
                    'password' => bcrypt($this->portalPassword),
                    'role_id' => $customerRole?->id,
                    'is_active' => true,
                ]);
                $customer->update(['user_id' => $user->id]);
            }

            DB::commit();

            session()->flash('success', 'Pelanggan PPPoE berhasil dibuat!');
            return redirect()->route('isp.pppoe-users.index');
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Gagal membuat pelanggan: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.isp.pppoe-user.create', [
            'routers' => Router::where('is_active', true)->get(),
            'packages' => Package::where('is_active', true)->get(),
        ])->layout('layouts.app', [
            'title' => 'Tambah Pelanggan PPPoE'
        ]);
    }
}
