<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WashTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_code', 
        'customer_id',
        'customer_name', 
        'plate_number', 
        'total_amount', 
        'amount_paid', 
        'payment_method', 
        'status', 
        'user_id', 
        'employee_id',
        'notes'
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(WashTransactionItem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }
}
