<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JournalEntry extends Model
{
    protected $fillable = ['journal_id', 'account_id', 'debit', 'credit', 'memo', 'unit', 'cost_center'];
}
