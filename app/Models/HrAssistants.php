<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HrAssistants extends Model
{
    use HasFactory;

    protected $table = 'hr_assistant';

    protected $fillable = ['name', 'email', 'phone_number', 'rate', 'color', 'user_id','allow_report_hours'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    
}
