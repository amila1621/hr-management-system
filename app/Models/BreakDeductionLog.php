<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\TourGuide;
class BreakDeductionLog extends Model
{
    protected $fillable = ['guide_id','event_id', 'date', 'deducted_minutes', 'from_time', 'to_time','normal_hours','normal_night_hours','holiday_hours','holiday_night_hours'];

    protected $casts = [
        'date' => 'date',
        'from_time' => 'datetime',
        'to_time' => 'datetime',
    ];

 
    public function guide()
    {
        return $this->belongsTo(TourGuide::class);
    }
}
