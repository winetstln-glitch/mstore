<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AtkTransaction extends Model
{
    protected $fillable = [
        'user_id', 'transaction_number', 'total_amount', 'payment_method', 'cash_amount', 'change_amount', 'amount_paid'
    ];

    public function items()
    {
        return $this->hasMany(AtkTransactionItem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
