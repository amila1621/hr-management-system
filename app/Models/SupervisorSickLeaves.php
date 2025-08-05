<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupervisorSickLeaves extends Model
{
    use HasFactory;
    
    protected $table = 'supervisor_sick_leaves';

    protected $fillable = [
        'staff_id',
        'start_date',
        'end_date',
        'description',
        'department',
        'supervisor_id',
        'supervisor_remark',
        'image',
        'admin_id',
        'admin_remark',
        'status',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function staff()
    {
        return $this->belongsTo(StaffUser::class, 'staff_id', 'id');
    }


}


