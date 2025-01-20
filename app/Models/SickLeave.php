<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SickLeave extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'guide_id',
        'guide_name',
        'tour_name',
        'date',
        'start_time',
        'end_time',
        'normal_hours',
        'normal_night_hours',
        'holiday_hours',
        'holiday_night_hours',
        'applied_at',
        'created_by',
        'updated_by'
    ];

    protected $dates = [
        'date',
        'start_time',
        'end_time',
        'applied_at',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    public function guide()
    {
        return $this->belongsTo(TourGuide::class);
    }
}
