<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TourGuide extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'full_name',
        'email',          
        'phone_number',   
        'rate',    
        'user_id',
        'supervisor',
        'allow_report_hours',
        'is_hidden'
    ];

      // Define relationship with EventSalary model
      public function eventSalaries()
      {
          return $this->hasMany(EventSalary::class, 'guideId');
      }


    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
