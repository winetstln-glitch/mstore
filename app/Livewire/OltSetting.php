<?php
// app/Livewire/OltSetting.php

namespace App\Livewire;

use App\Models\OLT;
use App\Services\OLT\OLTFactory;
use Livewire\Component;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class OltSetting extends Component
{
    public $oltId = null;
    public $form = [
        'name' => '',
        'ip_address' => '',
        'vendor' => '',
        'model' => '',
        'location' => '',
        'read_community' => '',
        'write_community' => '',
        'snmp_version' => 'v2c',
        'snmpv3_username' => '',
        'snmpv3_auth_protocol' => 'MD5',
        'snmpv3_auth_password' => '',
        'snmpv3_priv_protocol' => '',
        'snmpv3_priv_password' => '',
        'poll_interval' => 300,
        'snmp_timeout' => 10,
        'snmp_retries' => 2,
    ];

    public function mount($id = null)
    {
        if ($id) {
            $olt = OLT::findOrFail($id);
            $this->oltId = $olt->id;
            $this->form = [
                'name' => $olt->name,
                'ip_address' => $olt->ip_address,
                'vendor' => $olt->vendor,
                'model' => $olt->model,
                'location' => $olt->location,
                'read_community' => $olt->read_community,
                'write_community' => $olt->write_community,
                'snmp_version' => $olt->snmp_version ?? 'v2c',
                'snmpv3_username' => $olt->snmpv3_config['username'] ?? '',
                'snmpv3_auth_protocol' => $olt->snmpv3_config['auth_protocol'] ?? 'MD5',
                'snmpv3_auth_password' => '',
                'snmpv3_priv_protocol' => $olt->snmpv3_config['priv_protocol'] ?? '',
                'snmpv3_priv_password' => '',
                'poll_interval' => $olt->poll_interval ?? 300,
                'snmp_timeout' => $olt->snmp_timeout ?? 10,
                'snmp_retries' => $olt->snmp_retries ?? 2,
            ];
        }
    }

    public function save()
    {
        $rules = [
            'form.name' => 'required|string|max:100',
            'form.ip_address' => [
                'required',
                'ip',
                Rule::unique('olts', 'ip_address')->ignore($this->oltId),
            ],
            'form.vendor' => 'required|string|max:50',
            'form.model' => 'nullable|string|max:100',
            'form.location' => 'nullable|string|max:200',
            'form.read_community' => 'required|string|max:100',
            'form.write_community' => 'nullable|string|max:100',
            'form.snmp_version' => 'in:v1,v2c,v3',
            'form.poll_interval' => 'integer|min:30|max:86400',
            'form.snmp_timeout' => 'integer|min:1|max:60',
            'form.snmp_retries' => 'integer|min:0|max:10',
        ];

        // Validation untuk SNMPv3
        if ($this->form['snmp_version'] === 'v3') {
            $rules['form.snmpv3_username'] = 'required|string|max:100';
            $rules['form.snmpv3_auth_password'] = 'required|string|min:8';
        }

        $this->validate($rules);

        $data = [
            'name' => $this->form['name'],
            'ip_address' => $this->form['ip_address'],
            'vendor' => $this->form['vendor'],
            'model' => $this->form['model'],
            'location' => $this->form['location'],
            'read_community' => $this->form['read_community'],
            'write_community' => $this->form['write_community'] ?: null,
            'snmp_version' => $this->form['snmp_version'],
            'poll_interval' => $this->form['poll_interval'],
        ];

        // SNMPv3 config
        if ($this->form['snmp_version'] === 'v3') {
            $data['snmpv3_config'] = [
                'username' => $this->form['snmpv3_username'],
                'auth_protocol' => $this->form['snmpv3_auth_protocol'],
                'auth_password' => $this->form['snmpv3_auth_password'],
                'priv_protocol' => $this->form['snmpv3_priv_protocol'],
                'priv_password' => $this->form['snmpv3_priv_password'],
            ];
        }

        if ($this->oltId) {
            $olt = OLT::findOrFail($this->oltId);
            $olt->update($data);
            session()->flash('message', 'OLT berhasil diupdate');
        } else {
            OLT::create($data + ['is_active' => true]);
            session()->flash('message', 'OLT berhasil dibuat');
        }
        $this->emit('refresh');
    }
}