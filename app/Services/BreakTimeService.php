<?php

namespace App\Services;

use App\Http\Controllers\SalaryController;
use App\Models\EventSalary;
use App\Models\Holiday;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BreakTimeService
{
    private const LUNCH_START = '11:00';
    private const LUNCH_END = '13:00';
    private const LUNCH_MIN_DURATION = 30;
    private const LUNCH_MAX_DURATION = 60;

    private const SLEEP_START = '01:30';
    private const SLEEP_END = '07:00';

    public function processBreaks(EventSalary $eventSalary)
    {
        try {
            DB::beginTransaction();

            $startTime = Carbon::parse($eventSalary->guide_start_time);
            $endTime = Carbon::parse($eventSalary->guide_end_time);
            $originalDuration = $endTime->diffInMinutes($startTime);

            // Process breaks and get segments
            $segments = collect([['start' => $startTime, 'end' => $endTime, 'guideId' => $eventSalary->guideId, 'eventId' => $eventSalary->eventId]]);
            $segments = $this->processLunchBreak($segments);

            // Calculate total duration of new segments
            $newDuration = $segments->sum(function ($segment) {
                return Carbon::parse($segment['end'])->diffInMinutes(Carbon::parse($segment['start']));
            });

            // Create new records
            $newRecords = $segments->map(function ($segment) use ($eventSalary) {
                $newRecord = $eventSalary->replicate();
                $newRecord->guide_start_time = $segment['start'];
                $newRecord->guide_end_time = $segment['end'];
                $newRecord->is_final = 1;
                $newRecord->save();
                return $newRecord;
            });

            // // Delete original record
            $eventSalary->delete();

            DB::commit();

            // Recalculate only affected records
            $salaryController = new SalaryController();
            $salaryController->recalculateSpecific($newRecords->pluck('id'));

            return $newRecords;


        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error in processBreaks: ' . $e->getMessage());
            throw $e;
        }
    }


    public function processBreaksSleep(EventSalary $eventSalary)
{
    try {
        DB::beginTransaction();

        $startTime = Carbon::parse($eventSalary->guide_start_time);
        $endTime = Carbon::parse($eventSalary->guide_end_time);
        
        // Process breaks and get segments
        $segments = collect([['start' => $startTime, 'end' => $endTime, 'guideId' => $eventSalary->guideId, 'eventId' => $eventSalary->eventId]]);
        $adjustedSegments = $this->processStartTimeAdjustments($segments);

        // If start time was adjusted, update the original record
        if ($adjustedSegments->first()['start'] != $startTime) {
            $eventSalary->guide_start_time = $adjustedSegments->first()['start'];
            // $eventSalary->is_final = 1;
            $eventSalary->save();

            // Recalculate only this record
            $salaryController = new SalaryController();
            $salaryController->recalculateSpecific([$eventSalary->id]);
        }

        DB::commit();
        return $eventSalary;

    } catch (\Exception $e) {
        DB::rollBack();
        \Log::error('Error in processBreaksSleep: ' . $e->getMessage());
        throw $e;
    }
}

    public function processEarlyMorningBreaks(EventSalary $eventSalary)
    {
        try {
            DB::beginTransaction();

            $startTime = Carbon::parse($eventSalary->guide_start_time);
            $endTime = Carbon::parse($eventSalary->guide_end_time);
            
            // Process breaks and get segments
            $segments = collect([['start' => $startTime, 'end' => $endTime, 'guideId' => $eventSalary->guideId, 'eventId' => $eventSalary->eventId]]);
            $adjustedSegments = $this->processEarlyMorningAdjustments($segments);

            // If start time was adjusted, update the original record
            if ($adjustedSegments->first()['start'] != $startTime) {
                $eventSalary->guide_start_time = $adjustedSegments->first()['start'];
                $eventSalary->is_final = 1;
                $eventSalary->save();

                // Recalculate only this record
                $salaryController = new SalaryController();
                $salaryController->recalculateSpecific([$eventSalary->id]);
            } else {
                // Even if no adjustment needed, mark as processed
                $eventSalary->is_final = 1;
                $eventSalary->save();
            }
    

            DB::commit();
            return $eventSalary;

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error in processEarlyMorningBreaks: ' . $e->getMessage());
            throw $e;
        }
    }

    private function processLunchBreak(Collection $segments): Collection
    {
        return $segments->flatMap(function ($segment) {
            $start = Carbon::parse($segment['start']);
            $end = Carbon::parse($segment['end']);
            
            // Define lunch period
            $lunchStart = $start->copy()->setTimeFromTimeString(self::LUNCH_START);
            $lunchEnd = $start->copy()->setTimeFromTimeString(self::LUNCH_END);
            
            // Replace current validation with:
            if ($end->format('H:i') <= self::LUNCH_START || 
                $start->format('H:i') >= self::LUNCH_END) {
                return [$segment];
            }

            // Only process tours that overlap lunch period (start before 11:00 and end after 13:00)
            if ($start->format('H:i') < self::LUNCH_START && 
                $end->format('H:i') > self::LUNCH_END) {
                // Random start time between 11:00 and 12:20
                $randomMinutes = rand(0, 80);
                $breakStart = $lunchStart->copy()->addMinutes($randomMinutes);
                $breakDuration = rand(self::LUNCH_MIN_DURATION, self::LUNCH_MAX_DURATION);
                
                // Log break
                $this->logDeductedMinutes(
                    $segment['guideId'],
                    $segment['eventId'],
                    $start->toDateString(),
                    $breakDuration,
                    $breakStart->format('H:i'),
                    $breakStart->copy()->addMinutes($breakDuration)->format('H:i')
                );
                
                return [
                    [
                        'start' => $start,
                        'end' => $breakStart,
                        'guideId' => $segment['guideId'],
                        'eventId' => $segment['eventId']
                    ],
                    [
                        'start' => $breakStart->copy()->addMinutes($breakDuration),
                        'end' => $end,
                        'guideId' => $segment['guideId'],
                        'eventId' => $segment['eventId']
                    ]
                ];
            }
            
            return [$segment];
        });
    }

   
    private function processStartTimeAdjustments(Collection $segments): Collection
    {
        return $segments->flatMap(function ($segment) {
            $currentGuideId = $segment['guideId'];
            $currentDate = Carbon::parse($segment['start'])->format('Y-m-d');
            $prevDate = Carbon::parse($segment['start'])->subDay()->format('Y-m-d');
            
            // Get all shifts for this guide on current and previous date
            $guideDailyShifts = EventSalary::where('guideId', $currentGuideId)
                ->where(function ($query) use ($currentDate, $prevDate) {
                    $query->whereDate('guide_start_time', $currentDate)
                          ->orWhereDate('guide_start_time', $prevDate);
                })
                ->orderBy('guide_start_time', 'asc')
                ->get()
                ->filter(function ($shift) {
                    $startTime = Carbon::parse($shift->guide_start_time)->format('H:i');
                    $endTime = Carbon::parse($shift->guide_end_time)->format('H:i');
                    return !($startTime === '00:00' && $endTime === '00:00');
                });

            // Find the previous shift that ended in the early morning
            $previousShift = $guideDailyShifts
                ->filter(function ($shift) use ($segment) {
                    $shiftEnd = Carbon::parse($shift->guide_end_time);
                    $currentStart = Carbon::parse($segment['start']);
                    
                    // Check if this shift ended before the current segment starts
                    return $shiftEnd->lt($currentStart);
                })
                ->sortByDesc('guide_end_time')
                ->first();

            if ($previousShift) {
                $previousEndTime = Carbon::parse($previousShift->guide_end_time);
                $currentStartTime = Carbon::parse($segment['start']);
                
                // Check if previous shift ended between 00:00 and 04:00
                $endHour = (int)$previousEndTime->format('H');
                if ($endHour >= 0 && $endHour <= 4) {
                    $hoursDifference = $currentStartTime->diffInHours($previousEndTime);
                    
                    if ($hoursDifference < 8) {
                        $randomMinutes = rand(0, 60);
                        $newStartTime = $previousEndTime->copy()->addHours(8)->addMinutes($randomMinutes);
                        
                        // Rest of your existing code for logging and adjusting...
                        $adjustedMinutes = $newStartTime->diffInMinutes($currentStartTime);
                        
                        $this->logDeductedMinutes(
                            $currentGuideId,
                            $segment['eventId'],
                            $currentDate,
                            $adjustedMinutes,
                            $currentStartTime->format('H:i'),
                            $newStartTime->format('H:i')
                        );

                        // Log the adjustment
                        \Log::warning("Adjusting shift start time for Guide ID {$currentGuideId}", [
                            'date' => $currentDate,
                            'original_start' => $currentStartTime->format('H:i'),
                            'adjusted_start' => $newStartTime->format('H:i'),
                            'previous_end' => $previousEndTime->format('H:i'),
                            'minutes_adjusted' => $adjustedMinutes
                        ]);

                        return [
[
                            'start' => $newStartTime,
                            'end' => Carbon::parse($segment['end']),
                            'guideId' => $currentGuideId,
                            'eventId' => $segment['eventId']
                        ]];
                    }
                }
            }
            
            return [$segment];
        });
    }
    
    private function processEarlyMorningAdjustments(Collection $segments): Collection
    {
        return $segments->flatMap(function ($segment) {
            $currentGuideId = $segment['guideId'];
            $currentStartTime = Carbon::parse($segment['start']);
            
            // Only process if current shift starts between 5am and 8am
            $startHour = (int)$currentStartTime->format('H');
            if ($startHour < 5 || $startHour > 8) {
                return [$segment];
            }
            
            // Get previous day's date
            $prevDate = $currentStartTime->copy()->subDay()->format('Y-m-d');
            
            // Find the last shift from previous day
            $previousShift = EventSalary::where('guideId', $currentGuideId)
                ->whereDate('guide_start_time', $prevDate)
                ->orderBy('guide_end_time', 'desc')
                ->first();

            if ($previousShift) {
                $previousEndTime = Carbon::parse($previousShift->guide_end_time);
                $endHour = (int)$previousEndTime->format('H');
                
                // Check if previous shift ended between 21:00 and 23:59
                if ($endHour >= 21 && $endHour <= 23) {
                    $hoursDifference = $currentStartTime->diffInHours($previousEndTime);
                    
                    if ($hoursDifference < 8) {
                        $randomMinutes = rand(0, 60);
                        $newStartTime = $previousEndTime->copy()->addHours(8)->addMinutes($randomMinutes);
                        
                        $adjustedMinutes = $newStartTime->diffInMinutes($currentStartTime);
                        
                        // Log the deducted minutes
                        $this->logDeductedMinutes(
                            $currentGuideId,
                            $segment['eventId'],
                            $currentStartTime->format('Y-m-d'),
                            $adjustedMinutes,
                            $currentStartTime->format('H:i'),
                            $newStartTime->format('H:i')
                        );

                        // Log the adjustment
                        \Log::warning("Adjusting early morning shift start time for Guide ID {$currentGuideId}", [
                            'date' => $currentStartTime->format('Y-m-d'),
                            'original_start' => $currentStartTime->format('H:i'),
                            'adjusted_start' => $newStartTime->format('H:i'),
                            'previous_end' => $previousEndTime->format('H:i'),
                            'minutes_adjusted' => $adjustedMinutes
                        ]);

                        return [[
                            'start' => $newStartTime,
                            'end' => Carbon::parse($segment['end']),
                            'guideId' => $currentGuideId,
                            'eventId' => $segment['eventId']
                        ]];
                    }
                }
            }
            
            return [$segment];
        });
    }

    private function processSleepBreak(Collection $segments): Collection
    {
        return $segments->flatMap(function ($segment) {
            $start = Carbon::parse($segment['start']);
            $end = Carbon::parse($segment['end']);
            
            $sleepStart = $start->copy()->setTimeFromTimeString(self::SLEEP_START);
            
            if ($end <= $sleepStart) {
                return [$segment];
            }

            $deductedMinutes = $end->diffInMinutes($sleepStart);
            if ($deductedMinutes > 0) {
                $this->logDeductedMinutes($segment['guideId'], $segment['eventId'], $start->toDateString(), $deductedMinutes, 'sleep');
            }

            return [
                ['start' => $start, 'end' => $sleepStart, 'guideId' => $segment['guideId'], 'eventId' => $segment['eventId']]
            ];
        });
    }
  

    private function logDeductedMinutes(int $guideId, int $eventId, string $date, int $deductedMinutes, string $fromTime, string $toTime)
    {
        
        $startDateTime = Carbon::parse($date . ' ' . $fromTime);
        $endDateTime = Carbon::parse($date . ' ' . $toTime);

        $normalMinutes = 0;
        $nightMinutes = 0;
        $holidayMinutes = 0;
        $holidayNightMinutes = 0;

        while ($startDateTime->lessThan($endDateTime)) {
            $currentHourEnd = $startDateTime->copy()->addMinute()->min($endDateTime);

            $isNight = $startDateTime->hour >= 20 || $startDateTime->hour < 6;
            $isHoliday = $this->isHoliday($startDateTime);

            $duration = $currentHourEnd->diffInMinutes($startDateTime);



            if ($isHoliday) {
                if ($isNight) {
                    $holidayNightMinutes += $duration;
                } else {
                    $holidayMinutes += $duration;
                }
            } elseif ($isNight) {
                $nightMinutes += $duration;
            } else {
                $normalMinutes += $duration;
            }

            $startDateTime = $currentHourEnd;
        }


        // Convert minutes to hours and minutes
        [$normalHours, $normalMinutes] = $this->minutesToHoursAndMinutes($normalMinutes);
        [$nightHours, $nightMinutes] = $this->minutesToHoursAndMinutes($nightMinutes);
        [$holidayHours, $holidayMinutes] = $this->minutesToHoursAndMinutes($holidayMinutes);
        [$holidayNightHours, $holidayNightMinutes] = $this->minutesToHoursAndMinutes($holidayNightMinutes);

        // Store hours and minutes separately
        $normal_hours = $this->formatHoursMinutes(
            $normalHours + $nightHours + $holidayHours + $holidayNightHours,
            $normalMinutes + $nightMinutes + $holidayMinutes + $holidayNightMinutes
        );
        $normal_night_hours = $this->formatHoursMinutes(
            $nightHours + $holidayNightHours,
            $nightMinutes + $holidayNightMinutes
        );
        $holiday_hours = $this->formatHoursMinutes(
            $holidayHours + $holidayNightHours,
            $holidayMinutes + $holidayNightMinutes
        );
        $holiday_night_hours = $this->formatHoursMinutes(
            $holidayNightHours,
            $holidayNightMinutes
        );
        
        
        
        DB::table('break_deduction_logs')->insert([
            'guide_id' => $guideId,
            'event_id' => $eventId,
            'date' => $date,
            'deducted_minutes' => $deductedMinutes,
            'from_time' => $fromTime,
            'to_time' => $toTime,
            'normal_hours' => $normal_hours,
            'normal_night_hours' => $normal_night_hours,
            'holiday_hours' => $holiday_hours,
            'holiday_night_hours' => $holiday_night_hours,
            'created_at' => now(),
            'updated_at' => now()
        ]);


        // we have to recalculate this


    }

    private function isHoliday(Carbon $date)
    {
        return $date->isSunday() || Holiday::where('holiday_date', $date->format('Y-m-d'))->exists();
    }

    
    private function minutesToHoursAndMinutes($minutes)
    {
        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;
        return [$hours, $remainingMinutes];
    }

    private function formatHoursMinutes($hours, $minutes)
    {
        // Adjust for minutes >= 60
        $hours += floor($minutes / 60);
        $minutes = $minutes % 60;
        
        return sprintf("%d.%02d", $hours, $minutes);
    }
}