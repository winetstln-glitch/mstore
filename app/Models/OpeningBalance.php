<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OpeningBalance extends Model
{
    protected $fillable = ['period_id','account_id','debit','credit'];
}
