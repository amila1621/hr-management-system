<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Event extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'event_id', 'name', 'description','original_description', 'start_time', 'end_time','status','condition'
    ];

    public function eventSalary()
    {
        return $this->hasMany(EventSalary::class, 'eventId');
    }
}
