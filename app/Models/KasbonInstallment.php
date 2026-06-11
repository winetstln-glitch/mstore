kan <?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KasbonInstallment extends Model
{
    protected $fillable = [
        'kasbon_loan_id',
        'amount',
        'date',
        'description',
        'salary_adjustment_id',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'date' => 'date',
        ];
    }

    public function kasbonLoan(): BelongsTo
    {
        return $this->belongsTo(KasbonLoan::class);
    }

    public function salaryAdjustment(): BelongsTo
    {
        return $this->belongsTo(SalaryAdjustment::class);
    }
}
