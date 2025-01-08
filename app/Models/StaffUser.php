<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffUser extends Model
{
    use HasFactory;

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
    ];

    protected $casts = [
        'allow_report_hours' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the supervisor of this staff user.
     */
    public function supervisorUser()
    {
        return $this->belongsTo(User::class, 'supervisor');
    }

    /**
     * Get the staff users supervised by this user.
     */
    public function supervisedStaff()
    {
        return $this->hasMany(StaffUser::class, 'supervisor', 'user_id');
    }

}
