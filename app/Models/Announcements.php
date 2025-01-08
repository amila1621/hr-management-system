<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Announcements extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
    ];

    /**
     * The users who have acknowledged this announcement
     */
    public function acknowledgedBy()
    {
        return $this->belongsToMany(User::class, 'announcements_acknowledged')
            ->withTimestamps();
    }
}
