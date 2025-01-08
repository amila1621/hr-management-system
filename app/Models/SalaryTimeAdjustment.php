<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalaryTimeAdjustment extends Model
{
    protected $fillable = [
        'event_id',
        'guide_id',
        'adjusted_by',
        'original_end_time',
        'added_time',
        'new_end_time',
        'note'
    ];

    protected $casts = [
        'original_end_time' => 'datetime',
        'new_end_time' => 'datetime',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function guide()
    {
        return $this->belongsTo(User::class, 'guide_id');
    }

    public function adjuster()
    {
        return $this->belongsTo(User::class, 'adjusted_by');
    }
} 