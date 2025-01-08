<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OperationsProcessTours extends Model
{
    use HasFactory;

    protected $table = 'operations_process_tours';

    protected $fillable = ['event_id', 'tour_name', 'tour_date', 'description', 'original_description', 'status'];
}
