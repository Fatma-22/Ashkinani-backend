<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'name_ar',
        'position',
        'position_ar',
        'department',
        'department_ar',
        'salary',
        'hire_date',
        'phone',
        'email',
        'national_id',
        'address',
        'is_active',
        'contract_start_date',
        'contract_end_date',
        'contract_file',
        'year_of_birth',
        'nationality',
        'nationality_ar',
        'currency'
    ];

    protected $casts = [
        'salary' => 'decimal:2',
        'hire_date' => 'date',
        'is_active' => 'boolean',
        'contract_start_date' => 'date',
        'contract_end_date' => 'date',
    ];

    public function financialRecords()
    {
        return $this->morphMany(FinancialRecord::class, 'related');
    }
}
