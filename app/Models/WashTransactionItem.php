<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WashTransactionItem extends Model
{
    protected $fillable = [
        'wash_transaction_id', 'wash_service_id', 'service_name', 'price', 'quantity', 'subtotal', 'employee_id'
    ];

    public function transaction()
    {
        return $this->belongsTo(WashTransaction::class);
    }

    public function service()
    {
        return $this->belongsTo(WashService::class, 'wash_service_id');
    }

    public function employee()
    {
        return $this->belongsTo(WashEmployee::class, 'employee_id');
    }
}
