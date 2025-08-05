<?php

namespace App\Services;

use App\Models\CombinedStaffGuideHours;
use App\Http\Controllers\StaffController;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CombinedStaffGuideHoursService
{
    /**
     * Calculate and store combined hours for a staff+guide user
     */
    public function calculateAndStore(int $userId, int $year, int $month): CombinedStaffGuideHours
    {
        Log::info("Starting calculation for user {$userId}", ['year' => $year, 'month' => $month]);
        
        try {
            // Get staff and guide information
            $staff = \App\Models\StaffUser::where('user_id', $userId)->first();
            $guide = \App\Models\TourGuide::where('user_id', $userId)->where('is_hidden', 0)->first();
            
            Log::info("Retrieved staff and guide for user {$userId}", [
                'staff_found' => $staff ? true : false,
                'guide_found' => $guide ? true : false,
                'staff_id' => $staff ? $staff->id : null,
                'guide_id' => $guide ? $guide->id : null
            ]);
            
            if (!$staff || !$guide) {
                throw new \Exception("User {$userId} is not a valid staff+guide combination");
            }
            
            // Get holidays for the month
            $holidays = \App\Models\Holiday::whereYear('holiday_date', $year)
                ->whereMonth('holiday_date', $month)
                ->pluck('holiday_date')
                ->map(function($date) {
                    return \Carbon\Carbon::parse($date)->format('Y-m-d');
                })
                ->toArray();
            
            // Get the StaffController and use its internal method
            $staffController = app(\App\Http\Controllers\StaffController::class);
            
            // Use reflection to access the private method
            $reflection = new \ReflectionClass($staffController);
            $method = $reflection->getMethod('generateStaffGuideDailyHours');
            $method->setAccessible(true);
            
            // Call the method to get the combined hours
            Log::info("About to call generateStaffGuideDailyHours for user {$userId}");
            $dailyHours = $method->invoke($staffController, $staff, $guide, $year, $month, $holidays);
            
            Log::info("Daily hours calculated for user {$userId}", [
                'daily_hours_count' => count($dailyHours),
                'sample_day' => !empty($dailyHours) ? array_keys($dailyHours)[0] : 'none',
                'sample_data' => !empty($dailyHours) ? reset($dailyHours) : 'none'
            ]);
            
            // Calculate totals from daily hours
            $totals = $this->calculateTotalsFromDailyHours($dailyHours);
            
            Log::info("Calculated totals for user {$userId}", ['totals' => $totals]);
            
            // Store or update the calculated hours
            $record = CombinedStaffGuideHours::updateOrCreate(
                [
                    'user_id' => $userId,
                    'year' => $year,
                    'month' => $month,
                ],
                [
                    'total_work_hours' => $totals['totalNormalHours'] ?? '0:00',
                    'total_holiday_hours' => $totals['totalHolidayHours'] ?? '0:00',
                    'total_night_hours' => $totals['totalNormalNightHours'] ?? '0:00',
                    'total_holiday_night_hours' => $totals['totalHolidayNightHours'] ?? '0:00',
                    'total_sick_leaves' => $totals['totalSickLeaves'] ?? '0:00',
                    'calculated_at' => now(),
                ]
            );
            
            Log::info("Successfully calculated and stored combined hours for user {$userId}", [
                'year' => $year,
                'month' => $month,
                'totals' => $totals
            ]);
            
            return $record;
            
        } catch (\Exception $e) {
            Log::error("Failed to calculate combined hours for user {$userId}", [
                'year' => $year,
                'month' => $month,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }
    
    /**
     * Get combined hours for a user, calculating if not exists or outdated
     */
    public function getOrCalculate(int $userId, int $year, int $month): CombinedStaffGuideHours
    {
        $record = CombinedStaffGuideHours::where([
            'user_id' => $userId,
            'year' => $year,
            'month' => $month,
        ])->first();
        
        // Always recalculate for fresh data as hours change regularly
        return $this->calculateAndStore($userId, $year, $month);
    }
    
    /**
     * Calculate combined hours for all dual-role users for a given month
     */
    public function calculateForAllDualRoleUsers(int $year, int $month): array
    {
        // Get all dual-role users (staff who are also guides)
        $dualRoleUserIds = \App\Models\StaffUser::whereNotIn('department', ['Hotel', 'Hotel Spa', 'Hotel Restaurant'])
            ->whereNotNull('user_id')
            ->whereHas('user', function($query) {
                $query->whereIn('id', function($subQuery) {
                    $subQuery->select('user_id')
                        ->from('tour_guides')
                        ->where('is_hidden', 0)
                        ->whereNotNull('user_id');
                });
            })
            ->pluck('user_id')
            ->toArray();
        
        $results = [];
        
        foreach ($dualRoleUserIds as $userId) {
            try {
                $record = $this->calculateAndStore($userId, $year, $month);
                $results[$userId] = $record;
            } catch (\Exception $e) {
                Log::warning("Failed to calculate hours for dual-role user {$userId}", [
                    'error' => $e->getMessage()
                ]);
                $results[$userId] = null;
            }
        }
        
        return $results;
    }
    
    /**
     * Calculate monthly totals from daily hours array
     */
    private function calculateTotalsFromDailyHours(array $dailyHours): array
    {
        Log::info("calculateTotalsFromDailyHours called", [
            'daily_hours_count' => count($dailyHours),
            'first_day_structure' => !empty($dailyHours) ? array_keys(reset($dailyHours)) : 'empty'
        ]);
        
        $totalNormalMinutes = 0;
        $totalHolidayMinutes = 0;
        $totalNightMinutes = 0;
        $totalHolidayNightMinutes = 0;
        $totalSickLeaveMinutes = 0;
        
        foreach ($dailyHours as $day => $dayData) {
            Log::info("Processing day {$day}", ['day_data_keys' => array_keys($dayData)]);
            
            // Process shifts data instead of looking for combined_hours
            if (isset($dayData['shifts']) && is_array($dayData['shifts'])) {
                foreach ($dayData['shifts'] as $shift) {
                    // Add regular work time (hours * 60 + minutes)
                    $totalNormalMinutes += ($shift['hours'] ?? 0) * 60 + ($shift['minutes'] ?? 0);
                    
                    // Add holiday time (hours * 60 + minutes)
                    $totalHolidayMinutes += ($shift['holiday_hours'] ?? 0) * 60 + ($shift['holiday_minutes'] ?? 0);
                    
                    // Add night time (hours * 60 + minutes)
                    $totalNightMinutes += ($shift['night_hours'] ?? 0) * 60 + ($shift['night_minutes'] ?? 0);
                    
                    // Add holiday night time (hours * 60 + minutes)
                    $totalHolidayNightMinutes += ($shift['holiday_night_hours'] ?? 0) * 60 + ($shift['holiday_night_minutes'] ?? 0);
                }
                
                Log::info("Processed shifts for day {$day}", [
                    'shift_count' => count($dayData['shifts']),
                    'day_normal_minutes' => array_sum(array_map(function($shift) {
                        return ($shift['hours'] ?? 0) * 60 + ($shift['minutes'] ?? 0);
                    }, $dayData['shifts'])),
                    'day_holiday_minutes' => array_sum(array_map(function($shift) {
                        return ($shift['holiday_hours'] ?? 0) * 60 + ($shift['holiday_minutes'] ?? 0);
                    }, $dayData['shifts'])),
                    'day_night_minutes' => array_sum(array_map(function($shift) {
                        return ($shift['night_hours'] ?? 0) * 60 + ($shift['night_minutes'] ?? 0);
                    }, $dayData['shifts']))
                ]);
            }
            
            // Process sick leaves
            if (isset($dayData['sick_leaves']) && is_array($dayData['sick_leaves'])) {
                foreach ($dayData['sick_leaves'] as $sickLeave) {
                    $totalSickLeaveMinutes += $sickLeave['minutes'] ?? 0;
                }
                Log::info("Processed sick leaves for day {$day}", [
                    'sick_leave_count' => count($dayData['sick_leaves']),
                    'day_sick_minutes' => array_sum(array_column($dayData['sick_leaves'], 'minutes'))
                ]);
            }
        }
        
        Log::info("Final totals calculated", [
            'totalNormalMinutes' => $totalNormalMinutes,
            'totalHolidayMinutes' => $totalHolidayMinutes,
            'totalNightMinutes' => $totalNightMinutes,
            'totalHolidayNightMinutes' => $totalHolidayNightMinutes,
            'totalSickLeaveMinutes' => $totalSickLeaveMinutes
        ]);
        
        // Combine night hours: regular night hours + holiday night hours
        $combinedNightMinutes = $totalNightMinutes + $totalHolidayNightMinutes;
        
        return [
            'totalNormalHours' => $this->formatMinutesToHours($totalNormalMinutes),
            'totalHolidayHours' => $this->formatMinutesToHours($totalHolidayMinutes),
            'totalNormalNightHours' => $this->formatMinutesToHours($combinedNightMinutes),
            'totalHolidayNightHours' => $this->formatMinutesToHours($totalHolidayNightMinutes),
            'totalSickLeaves' => $this->formatMinutesToHours($totalSickLeaveMinutes),
        ];
    }
    
    /**
     * Convert time format (HH:MM) to minutes
     */
    private function timeFormatToMinutes(string $timeFormat): int
    {
        if (empty($timeFormat) || $timeFormat === '0:00') {
            return 0;
        }
        
        $parts = explode(':', $timeFormat);
        return (int)$parts[0] * 60 + (int)$parts[1];
    }
    
    /**
     * Convert minutes to time format (HHH:MM)
     */
    private function formatMinutesToHours(int $minutes): string
    {
        $hours = intval($minutes / 60);
        $mins = $minutes % 60;
        return sprintf('%d:%02d', $hours, $mins);
    }
}