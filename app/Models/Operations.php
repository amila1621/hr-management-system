<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Operations extends Model
{
    use HasFactory;

    protected $table = 'operations';
    protected $fillable = [
        'user_id',
        'name',
        'email',
        'phone_number',
        'rate',
        'color',
        'user_id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
