<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountingIncomeExpenseType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type', // 'income' or 'expense'
        'unit',
        'active'
    ];

    protected $casts = [
        'active' => 'boolean',
    ];
}
