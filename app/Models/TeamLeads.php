<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeamLeads extends Model
{
    use HasFactory;


    protected $table = 'team_leads';

    protected $fillable = ['name', 'email', 'phone_number', 'rate', 'color', 'user_id','allow_report_hours', 'is_intern'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
