<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LastTours extends Model
{
    use HasFactory;

    protected $table = 'last_tour';
    protected $fillable = ['tour_name', 'tour_date', 'guide', 'eventId', 'start_time', 'end_time'];
}
