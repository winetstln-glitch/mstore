<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected static function booted()
    {
        static::created(function ($customer) {
            if ($customer->htb_id) {
                Htb::where('id', $customer->htb_id)->increment('filled');
            } elseif ($customer->odp_id) {
                Odp::where('id', $customer->odp_id)->increment('filled');
            }
        });

        static::updated(function ($customer) {
            // Check for HTB change
            if ($customer->isDirty('htb_id')) {
                $oldHtbId = $customer->getOriginal('htb_id');
                $newHtbId = $customer->htb_id;

                if ($oldHtbId) {
                    Htb::where('id', $oldHtbId)->decrement('filled');
                }
                if ($newHtbId) {
                    Htb::where('id', $newHtbId)->increment('filled');
                }
            }

            // Check for ODP change (complex interaction with HTB)
            // If HTB is present, ODP filled should NOT change based on customer (HTB handles ODP port)
            // Unless we switch from HTB to Direct ODP or vice versa.

            $oldHtbId = $customer->getOriginal('htb_id');
            $newHtbId = $customer->htb_id;
            $oldOdpId = $customer->getOriginal('odp_id');
            $newOdpId = $customer->odp_id;

            $oldIsHtb = !is_null($oldHtbId);
            $newIsHtb = !is_null($newHtbId);

            // Revert Old
            if (!$oldIsHtb && $oldOdpId) {
                // Was direct ODP, so decrement ODP
                // Only if we changed ODP OR switched to HTB
                if ($customer->isDirty('odp_id') || $newIsHtb) {
                    Odp::where('id', $oldOdpId)->decrement('filled');
                }
            }

            // Apply New
            if (!$newIsHtb && $newOdpId) {
                // Is direct ODP, so increment ODP
                // Only if we changed ODP OR switched from HTB
                if ($customer->isDirty('odp_id') || $oldIsHtb) {
                    Odp::where('id', $newOdpId)->increment('filled');
                }
            }
        });

        static::deleted(function ($customer) {
            if ($customer->htb_id) {
                Htb::where('id', $customer->htb_id)->decrement('filled');
            } elseif ($customer->odp_id) {
                Odp::where('id', $customer->odp_id)->decrement('filled');
            }
        });
    }

    protected $guarded = ['id'];

    protected $casts = [
        'path' => 'array',
    ];

    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    public function odp()
    {
        return $this->belongsTo(Odp::class);
    }

    public function htb()
    {
        return $this->belongsTo(Htb::class);
    }

    public function olt()
    {
        return $this->belongsTo(Olt::class);
    }

    public function loyaltyLogs()
    {
        return $this->hasMany(LoyaltyLog::class);
    }

    public function washTransactions()
    {
        return $this->hasMany(WashTransaction::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    public function installations()
    {
        return $this->hasMany(Installation::class);
    }
}
