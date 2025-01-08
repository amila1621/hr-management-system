<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaunaTourDurations extends Model
{
    use HasFactory;

    protected $table = 'sauna_tour_durations';

    protected $fillable = [
        'tour', 'duration'
    ];
}
