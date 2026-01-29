<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WashTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_code', 
        'customer_name', 
        'plate_number', 
        'total_amount', 
        'amount_paid', 
        'payment_method', 
        'status', 
        'user_id', 
        'notes'
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
