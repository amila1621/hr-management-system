<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffHoursDetails extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'reception',
        'midnight_phone',
    ];

    protected $casts = [
        'date' => 'date',
        'reception' => 'array',
        'midnight_phone' => 'array',
    ];

    // If you need to query by date range frequently, you might want to add this local scope
    public function scopeDateBetween($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

}
