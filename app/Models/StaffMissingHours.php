<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StaffMissingHours extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'staff_missing_hours';

    protected $fillable = [
        'staff_id',
        'staff_name',
        'reason',
        'date',
        'start_time',
        'end_time',
        'applied_date',
        'created_by'
    ];

    protected $dates = [
        'date',
        'start_time',
        'end_time',
        'applied_date',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    // Relationship with User model for staff
    public function staff()
    {
        return $this->belongsTo(StaffUser::class, 'staff_id');
    }

    // Relationship with User model for creator
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}