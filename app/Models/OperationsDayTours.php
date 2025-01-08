<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OperationsDayTours extends Model
{
    use HasFactory;

    protected $table = 'operation_day_tour';

    protected $fillable = ['event_id', 'tour_date', 'duration', 'tour_name', 'vehicle', 'pickup_time', 'pickup_location', 'pax', 'guide', 'available', 'remark'];
}
