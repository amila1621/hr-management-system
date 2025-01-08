<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ManagerGuideAssignment extends Model
{
    protected $fillable = ['manager_id', 'guide_id'];

    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function guide()
    {
        return $this->belongsTo(TourGuide::class, 'guide_id');
    }
}