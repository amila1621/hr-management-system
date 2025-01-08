<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MissingHours extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'missing_hours';
    protected $fillable = ['guide_id', 'guide_name', 'tour_name', 'date', 'normal_hours', 'normal_night_hours', 'holiday_hours', 'holiday_night_hours', 'applied_at', 'created_by', 'updated_by','start_time','end_time'];
}
