<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\LastTours;
use App\Models\StaffHoursDetails;
use App\Models\StaffMissingHours;
use App\Models\StaffMidnightPhone;
use App\Models\StaffMonthlyHours;
use App\Models\StaffUser;
use App\Models\TourGuide;
use App\Models\Holiday;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LastToursController extends Controller
{
    public function updateLastTours()
    {
        $startMonth = now()->startOfMonth()->startOfDay();
        $endMonth = now()->endOfMonth()->addDay()->startOfDay();
        
        //  $startMonth = now()->subMonth()->startOfMonth()->startOfDay(); // March 1st, 00:00:00
        //  $endMonth = now()->subMonth()->endOfMonth()->addDay()->startOfDay(); // April 1st, 00:00:00
    
        $currentDate = $startMonth->copy();

        while ($currentDate < $endMonth) {
            $lastTours = Event::whereBetween('start_time', [
                    $currentDate->format('Y-m-d 00:00:00'),
                    $currentDate->format('Y-m-d 23:59:59')
                ])
                ->where('name', 'LIKE', 'N%')
                ->whereHas('eventSalary')
                ->with(['eventSalary' => function($query) {
                    $query->orderBy('guide_end_time', 'desc');
                }, 'eventSalary'])
                ->get();

            $latestTour = $lastTours->sortByDesc(function($event) {
                return $event->eventSalary->first()->guide_end_time;
            })->first();

            if ($latestTour) {
                $eventSalary = $latestTour->eventSalary->first();
                
                LastTours::updateOrCreate(
                    ['tour_date' => $currentDate->format('Y-m-d')],
                    [
                        'tour_name' => $latestTour->name,
                        'guide' => TourGuide::find($eventSalary->guideId)->name,
                        'eventId' => $latestTour->id,
                        'start_time' => $eventSalary->guide_start_time,
                        'end_time' => $eventSalary->guide_end_time
                    ]
                );
            }

            $currentDate->addDay();
        }

        return ['message' => 'Updated last tours for March 2025'];
    }

    public function updateMidnightPhone(Request $request)
    {
        // Default to February 2025 for testing purposes
        $year = $request->input('year', 2025);
        $month = $request->input('month', 4);
        
        // Create date range for the month
        $startDate = Carbon::createFromDate($year, $month, 1);
        $endDate = $startDate->copy()->endOfMonth();
        $currentDate = $startDate->copy();
        
        // Get all holidays for the month for quick lookup
        $holidays = Holiday::whereYear('holiday_date', $year)
            ->whereMonth('holiday_date', $month)
            ->pluck('holiday_date')
            ->map(function($date) {
                return Carbon::parse($date)->format('Y-m-d');
            })
            ->toArray();
        
        $processedDays = 0;
        $createdEntries = 0;
        $errors = [];
        
        try {
            DB::beginTransaction();
            
            // Process each day of the month
            while ($currentDate <= $endDate) {
                $dateString = $currentDate->format('Y-m-d');
                $processedDays++;
                
                // Step 1: Get the staff ID who had midnight phone for this day
                $staffHoursDetail = StaffHoursDetails::where('date', $dateString)->first();
                
                if ($staffHoursDetail && isset($staffHoursDetail->midnight_phone[0])) {
                    $staffId = $staffHoursDetail->midnight_phone[0];
                    $staffUser = StaffUser::find($staffId);
                    
                    if (!$staffUser) {
                        $errors[] = "Staff ID {$staffId} not found for date {$dateString}";
                        $currentDate->addDay();
                        continue;
                    }
                    
                    // Step 2: Get the staff's end time (OFF time) for this day
                    $staffHours = StaffMonthlyHours::where('staff_id', $staffId)
                        ->where('date', $dateString)
                        ->first();
                    
                    $offTime = null;
                    if ($staffHours && !empty($staffHours->hours_data)) {
                        // Find the latest end time for this staff on this day
                        $latestEndTime = null;
                        
                        foreach ($staffHours->hours_data as $shift) {
                            if (isset($shift['end_time'])) {
                                $currentEndTime = $shift['end_time'];
                                if ($latestEndTime === null || $currentEndTime > $latestEndTime) {
                                    $latestEndTime = $currentEndTime;
                                }
                            }
                        }
                        
                        if ($latestEndTime) {
                            $offTime = Carbon::parse($dateString . ' ' . $latestEndTime);
                        }
                    }
                    
                    if (!$offTime) {
                        $errors[] = "No end time found for staff {$staffUser->name} on {$dateString}";
                        $currentDate->addDay();
                        continue;
                    }
                    
                    // Step 3: Get the LastTours information for this day
                    $lastTour = \App\Models\LastTours::where('tour_date', $dateString)->first();
                    
                    if ($lastTour) {
                        // Get the latest guide end time from event salary
                        $latestEventSalary = \App\Models\EventSalary::where('eventId', $lastTour->eventId)
                            ->orderBy('guide_end_time', 'desc')
                            ->first();
                        
                        if ($latestEventSalary) {
                            // Process the midnight phone entry
                            $startTime = Carbon::parse($dateString . ' 18:00:00');
                            $endTime = Carbon::parse($latestEventSalary->guide_end_time);
                            
                            // Check if this is an overnight shift
                            $isOvernight = $startTime->format('Y-m-d') !== $endTime->format('Y-m-d');
                            
                            // Check if the date is a holiday
                            $isHoliday = $this->isHoliday($startTime, $holidays);
                            
                            // Delete any existing records for this staff on this date to avoid duplicates
                            StaffMidnightPhone::where('staff_id', $staffId)
                                ->where('date', $dateString)
                                ->delete();
                            
                            if ($isOvernight) {
                                // Split into two records if overnight shift
                                // First part - from start time to midnight
                                $firstDayEnd = Carbon::parse($dateString . ' 23:59:59');
                                $firstDayIsHoliday = $isHoliday;
                                
                                // Create record for first day
                                $this->createMidnightPhoneRecord(
                                    $staffId,
                                    $staffUser->name,
                                    $dateString,
                                    $startTime,
                                    $firstDayEnd,
                                    $firstDayIsHoliday
                                );
                                $createdEntries++;
                                
                                // Second part - from midnight to end time on next day
                                $nextDayString = $endTime->format('Y-m-d');
                                $nextDayStart = Carbon::parse($nextDayString . ' 00:00:00');
                                $nextDayIsHoliday = $this->isHoliday($nextDayStart, $holidays) || Carbon::parse($nextDayString)->isSunday();
                                
                                // Check if there's an existing record for the next day
                                $existingNextDayRecord = StaffMidnightPhone::where('staff_id', $staffId)
                                    ->where('date', $nextDayString)
                                    ->first();
                                
                                // Only create second record if it doesn't exist already
                                if (!$existingNextDayRecord) {
                                    $this->createMidnightPhoneRecord(
                                        $staffId,
                                        $staffUser->name,
                                        $nextDayString,
                                        $nextDayStart,
                                        $endTime,
                                        $nextDayIsHoliday
                                    );
                                    $createdEntries++;
                                }
                            } else {
                                // Single record for same-day shift
                                $this->createMidnightPhoneRecord(
                                    $staffId,
                                    $staffUser->name,
                                    $dateString,
                                    $startTime,
                                    $endTime,
                                    $isHoliday
                                );
                                $createdEntries++;
                            }
                        } else {
                            $errors[] = "No event salary found for tour ID {$lastTour->eventId} on {$dateString}";
                        }
                    } else {
                        $errors[] = "No last tour found for date {$dateString}";
                    }
                } else {
                    $errors[] = "No midnight phone staff assigned for date {$dateString}";
                }
                
                $currentDate->addDay();
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => "Processed {$processedDays} days and created {$createdEntries} midnight phone entries",
                'errors' => $errors
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to update midnight phone entries', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing midnight phone entries',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Create a midnight phone record with calculations for night supplement and holiday hours
     */
    private function createMidnightPhoneRecord($staffId, $staffName, $dateString, $startTime, $endTime, $isHoliday)
    {
        // Calculate total minutes
        $totalMinutes = $endTime->diffInMinutes($startTime);
        
        // Calculate night minutes (night hours between 00:00-06:00)
        $nightMinutes = $this->calculateNightHours($startTime, $endTime);
        
        // Calculate holiday hours
        $holidayMinutes = $isHoliday ? $totalMinutes : 0;
        $holidayNightMinutes = $isHoliday ? $nightMinutes : 0;
        
        // Create the record with calculated fields
        return StaffMidnightPhone::create([
            'staff_id' => $staffId,
            'staff_name' => $staffName,
            'reason' => 'Midnight Phone',
            'date' => $dateString,
            'start_time' => $startTime->format('Y-m-d H:i:s'),
            'end_time' => $endTime->format('Y-m-d H:i:s'),
            'applied_date' => now()->format('Y-m-d'),
            'created_by' => auth()->id(),
            'total_minutes' => $totalMinutes,
            'night_minutes' => $nightMinutes,
            'holiday_minutes' => $holidayMinutes,
            'holiday_night_minutes' => $holidayNightMinutes
        ]);
    }
    
    /**
     * Calculate night hours (hours between 00:00 and 06:00)
     */
    private function calculateNightHours($startTime, $endTime)
    {
        $nightStart = Carbon::createFromTime(0, 0);  // 00:00
        $nightEnd = Carbon::createFromTime(6, 0);    // 06:00
        $totalNightMinutes = 0;
        
        // Handle all possible overlapping scenarios
        if ($startTime->copy()->setTime(0, 0, 0)->diffInDays($endTime->copy()->setTime(0, 0, 0)) > 0) {
            // Overnight shift spanning multiple days
            
            // Check for night hours on first day (if start time is before 06:00)
            $firstDayStart = max($startTime, $startTime->copy()->setTime(0, 0, 0));
            $firstDayEnd = min($endTime, $startTime->copy()->setTime(6, 0, 0));
            if ($firstDayStart < $firstDayEnd) {
                $totalNightMinutes += $firstDayEnd->diffInMinutes($firstDayStart);
            }
            
            // Check for night hours on last day (if end time is after 00:00 and before 06:00)
            if ($endTime->hour < 6) {
                $lastDayStart = max($startTime, $endTime->copy()->setTime(0, 0, 0));
                $lastDayEnd = min($endTime, $endTime->copy()->setTime(6, 0, 0));
                if ($lastDayStart < $lastDayEnd) {
                    $totalNightMinutes += $lastDayEnd->diffInMinutes($lastDayStart);
                }
            }
            
            // Add full night hours for any complete days in between
            $completeDays = $startTime->copy()->setTime(0, 0, 0)->diffInDays($endTime->copy()->setTime(0, 0, 0)) - 1;
            if ($completeDays > 0) {
                $totalNightMinutes += $completeDays * 6 * 60; // 6 hours = 360 minutes for each full day
            }
        } else {
            // Same day shift
            $dayStart = $startTime->copy()->setTime(0, 0, 0);
            $dayEnd = $dayStart->copy()->addHours(6);
            
            // Check if shift overlaps with night hours (00:00-06:00)
            if ($startTime < $dayEnd || $endTime > $dayStart->addDay()) {
                $nightShiftStart = max($startTime, $dayStart);
                $nightShiftEnd = min($endTime, $dayEnd);
                if ($nightShiftStart < $nightShiftEnd) {
                    $totalNightMinutes = $nightShiftEnd->diffInMinutes($nightShiftStart);
                }
            }
        }
        
        return $totalNightMinutes;
    }
    
    /**
     * Check if the given date is a holiday
     */
    private function isHoliday($date, $holidays)
    {
        $dateString = $date->format('Y-m-d');
        return in_array($dateString, $holidays) || $date->isSunday();
    }
}
