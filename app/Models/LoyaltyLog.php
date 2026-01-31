<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoyaltyLog extends Model
{
    use HasFactory;

    protected $fillable = ['customer_id', 'wash_transaction_id', 'points', 'description'];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function washTransaction()
    {
        return $this->belongsTo(WashTransaction::class);
    }
}
