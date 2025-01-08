<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supervisors extends Model
{
    use HasFactory;

    protected $table = 'supervisors';

    protected $fillable = [
        'name',
        'email',
        'phone_number',
        'rate',
        'color',
        'display_midnight_phone',
        'user_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
