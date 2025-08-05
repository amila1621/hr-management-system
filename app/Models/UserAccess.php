<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserAccess extends Model
{
    protected $fillable = ['user_id', 'access_type', 'has_access'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}