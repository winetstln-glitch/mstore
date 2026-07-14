<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CrmLead extends Model
{
    protected $fillable = [
        'name',
        'phone',
        'email',
        'service_interest',
        'coverage_area',
        'message',
        'source',
        'status',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_term',
        'utm_content',
    ];
}

