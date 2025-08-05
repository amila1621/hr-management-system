<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AmSupervisors extends Model
{
    use HasFactory;

    
    protected $table = 'am_supervisor';

    protected $fillable = ['name', 'email', 'phone_number', 'rate', 'color', 'user_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
