<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    use HasFactory;

    protected $table = 'activity_log';
    protected $fillable = [
        'logged_in_user',
        'page_endpoint_route',
        'ip_address'
    ];

}
