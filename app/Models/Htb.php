<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Htb extends Model
{
    use HasFactory;

    protected static function booted()
    {
        static::created(function ($htb) {
            if ($htb->parent_htb_id) {
                Htb::where('id', $htb->parent_htb_id)->increment('filled');
            } elseif ($htb->odp_id) {
                Odp::where('id', $htb->odp_id)->increment('filled');
            }
        });

        static::updated(function ($htb) {
            // Check for parent HTB change
            if ($htb->isDirty('parent_htb_id')) {
                $oldParentId = $htb->getOriginal('parent_htb_id');
                $newParentId = $htb->parent_htb_id;

                if ($oldParentId) {
                    Htb::where('id', $oldParentId)->decrement('filled');
                }
                if ($newParentId) {
                    Htb::where('id', $newParentId)->increment('filled');
                }

                // If switched to HTB parent, and previously was on ODP (directly), decrement ODP
                // Logic: 
                // Case 1: Null -> HTB (Decrement ODP? No, created handled it. Wait, update.)
                // Case 2: ODP -> HTB (Decrement ODP, Increment HTB)
                // Case 3: HTB -> ODP (Decrement HTB, Increment ODP)
                // Case 4: HTB -> HTB (Decrement old HTB, Increment new HTB)
            }

            // Cascade ODP ID change to children
            if ($htb->isDirty('odp_id')) {
                // Use each() to trigger 'updated' event on children for recursion
                $htb->children()->each(function ($child) use ($htb) {
                    $child->update(['odp_id' => $htb->odp_id]);
                });
            }

            // Complex logic for mixing ODP and HTB parent changes
            // Easier way:
            // 1. Revert old state effects
            // 2. Apply new state effects
            // But we must be careful not to double count if logic overlaps.
            
            $oldParentId = $htb->getOriginal('parent_htb_id');
            $newParentId = $htb->parent_htb_id;
            $oldOdpId = $htb->getOriginal('odp_id');
            $newOdpId = $htb->odp_id;

            // Detect if "Uplink" changed context
            // An Uplink is either (Parent HTB) OR (Parent ODP [if parent_htb is null])
            
            $oldUplinkIsHtb = !is_null($oldParentId);
            $newUplinkIsHtb = !is_null($newParentId);

            // Revert Old
            if ($oldUplinkIsHtb) {
                // If dirty, we already handled it? No, let's do it cleanly.
                if ($htb->isDirty('parent_htb_id')) {
                    Htb::where('id', $oldParentId)->decrement('filled');
                }
            } else {
                // Old uplink was ODP
                if ($oldOdpId && ($htb->isDirty('odp_id') || $newUplinkIsHtb)) {
                     // If ODP changed OR we switched to HTB uplink, decrement old ODP
                     Odp::where('id', $oldOdpId)->decrement('filled');
                }
            }

            // Apply New
            if ($newUplinkIsHtb) {
                 if ($htb->isDirty('parent_htb_id')) {
                    Htb::where('id', $newParentId)->increment('filled');
                 }
            } else {
                // New uplink is ODP
                if ($newOdpId && ($htb->isDirty('odp_id') || $oldUplinkIsHtb)) {
                    // If ODP changed OR we switched from HTB uplink, increment new ODP
                    Odp::where('id', $newOdpId)->increment('filled');
                }
            }
        });

        static::deleted(function ($htb) {
            if ($htb->parent_htb_id) {
                Htb::where('id', $htb->parent_htb_id)->decrement('filled');
            } elseif ($htb->odp_id) {
                Odp::where('id', $htb->odp_id)->decrement('filled');
            }
        });
    }

    protected $fillable = [
        'name',
        'odp_id',
        'parent_htb_id',
        'latitude',
        'longitude',
        'capacity',
        'filled',
        'description',
        'color',
        'path',
    ];

    protected $casts = [
        'path' => 'array',
    ];

    public function odp()
    {
        return $this->belongsTo(Odp::class);
    }

    public function parent()
    {
        return $this->belongsTo(Htb::class, 'parent_htb_id');
    }

    public function children()
    {
        return $this->hasMany(Htb::class, 'parent_htb_id');
    }

    public function customers()
    {
        return $this->hasMany(Customer::class);
    }

    public function isFull()
    {
        if ($this->capacity === null) {
            return false;
        }
        return $this->filled >= $this->capacity;
    }
}
