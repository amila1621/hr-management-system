<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExtraHoursRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'guide_id',
        'event_id',
        'explanation',
        'requested_end_time',
        'original_end_time',
        'extra_hours_minutes',
        'status',
        'admin_comment',
        'approved_by',
        'approved_at'
    ];

    protected $casts = [
        'requested_end_time' => 'datetime',
        'original_end_time' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function guide()
    {
        return $this->belongsTo(TourGuide::class, 'guide_id');
    }

    public function event()
    {
        return $this->belongsTo(Event::class, 'event_id');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
