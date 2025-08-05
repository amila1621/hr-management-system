<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class SalaryReport extends Model
{
    use HasFactory;

    protected $table = 'salary_reports';

    protected $fillable = [
        'user_id',
        'staff_name',
        'staff_full_name',
        'staff_role',
        'staff_email',
        'staff_department',
        'date',
        'description',
        'work_periods',
        'work_hours',
        'holiday_hours',
        'evening_hours',
        'evening_holiday_hours',
        'night_hours',
        'night_holiday_hours',
        'sick_leaves',
        'is_intern',
    ];

    protected $casts = [
        'date' => 'date',
        'is_intern' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function staff()
    {
        return $this->belongsTo(StaffUser::class, 'user_id');
    }

    // Accessor methods to convert time strings to minutes for calculations
    public function getWorkHoursInMinutesAttribute()
    {
        return $this->timeToMinutes($this->work_hours);
    }

    public function getHolidayHoursInMinutesAttribute()
    {
        return $this->timeToMinutes($this->holiday_hours);
    }

    public function getEveningHoursInMinutesAttribute()
    {
        return $this->timeToMinutes($this->evening_hours);
    }

    public function getEveningHolidayHoursInMinutesAttribute()
    {
        return $this->timeToMinutes($this->evening_holiday_hours);
    }

    public function getNightHoursInMinutesAttribute()
    {
        return $this->timeToMinutes($this->night_hours);
    }

    public function getNightHolidayHoursInMinutesAttribute()
    {
        return $this->timeToMinutes($this->night_holiday_hours);
    }

    // Get total hours in minutes
    public function getTotalHoursInMinutesAttribute()
    {
        return $this->work_hours_in_minutes + 
               $this->holiday_hours_in_minutes + 
               $this->evening_hours_in_minutes + 
               $this->evening_holiday_hours_in_minutes + 
               $this->night_hours_in_minutes + 
               $this->night_holiday_hours_in_minutes;
    }

    // Get total hours formatted
    public function getTotalHoursFormattedAttribute()
    {
        return $this->minutesToTime($this->total_hours_in_minutes);
    }

    // Mutator methods to ensure consistent time format
    public function setWorkHoursAttribute($value)
    {
        $this->attributes['work_hours'] = $this->formatTimeString($value);
    }

    public function setHolidayHoursAttribute($value)
    {
        $this->attributes['holiday_hours'] = $this->formatTimeString($value);
    }

    public function setEveningHoursAttribute($value)
    {
        $this->attributes['evening_hours'] = $this->formatTimeString($value);
    }

    public function setEveningHolidayHoursAttribute($value)
    {
        $this->attributes['evening_holiday_hours'] = $this->formatTimeString($value);
    }

    public function setNightHoursAttribute($value)
    {
        $this->attributes['night_hours'] = $this->formatTimeString($value);
    }

    public function setNightHolidayHoursAttribute($value)
    {
        $this->attributes['night_holiday_hours'] = $this->formatTimeString($value);
    }

    // Helper methods
    private function timeToMinutes($timeString)
    {
        if (empty($timeString) || $timeString === '00:00') {
            return 0;
        }

        $parts = explode(':', $timeString);
        if (count($parts) !== 2) {
            return 0;
        }

        return (int)$parts[0] * 60 + (int)$parts[1];
    }

    private function minutesToTime($minutes)
    {
        if ($minutes <= 0) {
            return '00:00';
        }

        $hours = floor($minutes / 60);
        $mins = $minutes % 60;

        return sprintf('%02d:%02d', $hours, $mins);
    }

    private function formatTimeString($value)
    {
        if (empty($value)) {
            return '00:00';
        }

        // If it's already in HH:MM format
        if (preg_match('/^\d{1,2}:\d{2}$/', $value)) {
            $parts = explode(':', $value);
            return sprintf('%02d:%02d', (int)$parts[0], (int)$parts[1]);
        }

        // If it's just hours (convert to HH:00)
        if (is_numeric($value)) {
            return sprintf('%02d:00', (int)$value);
        }

        return '00:00';
    }

    // Scope methods
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForDate($query, $date)
    {
        return $query->where('date', $date);
    }

    public function scopeForDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    public function scopeForDepartment($query, $department)
    {
        return $query->where('staff_department', $department);
    }

    public function scopeInterns($query)
    {
        return $query->where('is_intern', true);
    }

    public function scopeNonInterns($query)
    {
        return $query->where('is_intern', false);
    }

}
