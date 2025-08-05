<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StaffUser extends Model
{
    use HasFactory, SoftDeletes;
    

    protected $fillable = [
        'name',
        'full_name',
        'email',
        'phone_number',
        'rate',
        'allow_report_hours',
        'user_id',
        'supervisor',
        'color',
        'department',
        'is_supervisor',
        'hide',
        'order',
    ];

    protected $casts = [
        'allow_report_hours' => 'boolean',
        'is_supervisor' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

  
    public function supervisorUser()
    {
        return $this->belongsTo(User::class, 'supervisor');
    }

    public function supervisedStaff()
    {
        return $this->hasMany(StaffUser::class, 'supervisor', 'user_id');
    }

    public function supervisors()
    {
        return $this->belongsToMany(User::class, 'staff_supervisor', 'staff_user_id', 'supervisor_id')
                    ->where('role', 'supervisor')
                    ->withTimestamps();
    }

}
