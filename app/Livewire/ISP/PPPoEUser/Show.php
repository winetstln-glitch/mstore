<?php

namespace App\Livewire\ISP\PPPoEUser;

use App\Models\Customer;
use Livewire\Component;

class Show extends Component
{
    public Customer $customer;
    public $activeTab = 'identity';

    public function mount(Customer $customer)
    {
        $this->customer = $customer;
    }

    public function setTab($tab)
    {
        $this->activeTab = $tab;
    }

    public function suspend()
    {
        $this->customer->update(['status' => 'suspend']);
        session()->flash('success', 'Pelanggan berhasil disuspend!');
    }

    public function activate()
    {
        $this->customer->update(['status' => 'active']);
        session()->flash('success', 'Pelanggan berhasil diaktifkan!');
    }

    public function render()
    {
        return view('livewire.isp.pppoe-user.show');
    }
}
