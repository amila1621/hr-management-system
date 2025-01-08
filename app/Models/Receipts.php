<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Receipts extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'receipts';
    protected $fillable = ['receipt', 'user_id','note', 'status', 'approved_by'];


    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }


    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

}
