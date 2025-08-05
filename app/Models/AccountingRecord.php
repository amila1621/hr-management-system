<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountingRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'record_type',
        'amount',
        'date',
        'status',
        'created_by',
        'description', // Include this only if the field exists in your database
        'expense_type', // Include this only if the field exists in your database
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}