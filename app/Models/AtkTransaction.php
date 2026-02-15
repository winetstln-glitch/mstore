<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AtkTransaction extends Model
{
    protected $fillable = [
        'user_id', 'transaction_number', 'total_amount', 'payment_method', 'is_debt', 'cash_amount', 'change_amount', 'amount_paid', 'coordinator_id', 'customer_name', 'customer_phone'
    ];

    public function items()
    {
        return $this->hasMany(AtkTransactionItem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function coordinator()
    {
        return $this->belongsTo(Coordinator::class);
    }
}
