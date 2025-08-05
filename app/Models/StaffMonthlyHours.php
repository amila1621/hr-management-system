<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffMonthlyHours extends Model
{
    use HasFactory;
    protected $fillable = ['staff_id', 'date', 'hours_data', 'is_approved'];

    protected $casts = [
        'hours_data' => 'array',
        'date' => 'datetime',
    ];


    public function staff()
    {
        return $this->belongsTo(StaffUser::class);
    }
}
