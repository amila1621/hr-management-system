<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CombinedStaffGuideHours extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'year',
        'month',
        'total_work_hours',
        'total_holiday_hours',
        'total_night_hours',
        'total_holiday_night_hours',
        'total_sick_leaves',
        'calculated_at',
    ];

    protected $casts = [
        'calculated_at' => 'datetime',
    ];

    /**
     * Get the user that owns the hours record.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}