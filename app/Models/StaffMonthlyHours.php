<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffMonthlyHours extends Model
{
    use HasFactory;
    protected $fillable = ['staff_id', 'date', 'hours_data'];

    protected $casts = [
        'date' => 'date',
        'hours_data' => 'array',
    ];


    public function staff()
    {
        return $this->belongsTo(StaffUser::class);
    }
}
