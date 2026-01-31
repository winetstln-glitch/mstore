<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AtkTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_number',
        'user_id',
        'employee_id',
        'customer_name',
        'total_amount',
        'amount_paid',
        'payment_method',
        'type',
        'notes',
    ];

    public function items()
    {
        return $this->hasMany(AtkTransactionItem::class);
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
