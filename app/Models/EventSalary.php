<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EventSalary extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'eventId', 'guideId', 'total_salary', 'normal_hours', 'normal_night_hours','sunday_hours','holiday_hours','holiday_night_hours','approval_status', 'approval_comment','is_chore','is_guide_updated','guide_comment','guide_start_time','guide_end_time','guide_image'

    ];

    protected $casts = [
        'guide_start_time' => 'datetime',
        'guide_end_time' => 'datetime',
    ];

    protected $attributes = [
        'approval_comment' => '',
    ];

       // Define relationship with Event model
       public function event()
       {
           return $this->belongsTo(Event::class, 'eventId');
       }
   
       // Define relationship with Guide model
       public function tourGuide()
       {
           return $this->belongsTo(TourGuide::class, 'guideId');
       }
}
