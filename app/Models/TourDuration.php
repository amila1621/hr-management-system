<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TourDuration extends Model
{
    use HasFactory;

    protected $table = 'tour_durations';

    protected $fillable = [
        'tour', 'duration'
    ];
}
