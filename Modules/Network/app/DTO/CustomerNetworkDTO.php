<?php

namespace Modules\Network\DTO;

class CustomerNetworkDTO
{
    public function __construct(
        public readonly int $customerId,
        public readonly string $pppoeUsername,
        public readonly string $pppoePassword,
        public readonly ?string $serviceProfile = null,
        public readonly ?string $ipAddress = null,
        public readonly ?int $routerId = null,
        public readonly ?string $status = null,
        public readonly ?string $onuSerial = null,
        public readonly ?string $wanMac = null,
        public readonly ?string $vlan = null,
    ) {}

    public static function fromCustomer(\App\Models\Customer $customer): self
    {
        return new self(
            customerId: $customer->id,
            pppoeUsername: $customer->pppoe_user,
            pppoePassword: $customer->pppoe_password,
            serviceProfile: $customer->package,
            ipAddress: $customer->ip_address,
            routerId: $customer->router_id,
            status: $customer->status,
            onuSerial: $customer->onu_serial,
            wanMac: $customer->wan_mac,
            vlan: $customer->vlan,
        );
    }
}
