<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalaryUpdated extends Model
{
    use HasFactory;

    protected $table = 'salary_updated';

    protected $fillable = ['guide_id', 'guide_name', 'effective_date'];

    public function guide()
    {
        return $this->belongsTo(TourGuide::class, 'guide_id');
    }
}
