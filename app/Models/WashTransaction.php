<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WashTransaction extends Model
{
    protected $fillable = [
        'user_id', 'transaction_number', 'customer_name', 'vehicle_plate', 
        'total_amount', 'payment_method', 'cash_amount', 'change_amount', 'notes'
    ];

    public function items()
    {
        return $this->hasMany(WashTransactionItem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
