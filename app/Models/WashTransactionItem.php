<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WashTransactionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'wash_transaction_id', 
        'wash_service_id', 
        'price', 
        'quantity', 
        'subtotal'
    ];

    public function service()
    {
        return $this->belongsTo(WashService::class, 'wash_service_id');
    }
}
