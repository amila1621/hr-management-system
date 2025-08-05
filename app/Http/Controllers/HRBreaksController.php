<?php

namespace App\Http\Controllers;

use App\Models\BreakDeductionLog;
use App\Models\EventSalary;
use Illuminate\Http\Request;
use App\Services\BreakTimeService;
use Carbon\Carbon;
use App\Models\Event;

class HRBreaksController extends Controller
{
    public function __construct()
    {
        set_time_limit(900); // Set to 5 minutes
    }

    public function processAllGuides()
    {
        set_time_limit(900);

        $guideIds = EventSalary::where('guide_start_time', '!=', null)
        // ->whereMonth('guide_start_time', now()->month)
        // ->whereYear('guide_start_time', now()->year)
        ->whereMonth('guide_start_time', 3)  // January
        ->whereYear('guide_start_time', 2025)
        // ->where('guideId', 81)
        ->where('is_final', 0)
        ->distinct()
        ->pluck('guideId');


        // First run processBreaksSleep
        foreach ($guideIds as $guideId) {
            $eventSalaries = EventSalary::where('guide_start_time', '!=', null)
                 ->whereMonth('guide_start_time', 3)  // January
        ->whereYear('guide_start_time', 2025)
                ->where('guideId', $guideId)
                ->get();

            $breakService = new BreakTimeService();
            
            foreach ($eventSalaries as $salary) {
                $breakService->processBreaksSleep($salary);
            }
            
            \Log::info("Completed sleep break processing for guide: {$guideId}");
        }

        // Then run processEarlyMorningBreaks
        foreach ($guideIds as $guideId) {
            $eventSalaries = EventSalary::where('guide_start_time', '!=', null)
                 ->whereMonth('guide_start_time', 3)  // January
        ->whereYear('guide_start_time', 2025)
                ->where('guideId', $guideId)
                ->get();

            $breakService = new BreakTimeService();
            
            foreach ($eventSalaries as $salary) {
                $breakService->processEarlyMorningBreaks($salary);
            }
            
            \Log::info("Completed early morning break processing for guide: {$guideId}");
        }

        $this->addChoresUsingBreakTime();
    }

    public function processAllGuidesSleep()
    {
        set_time_limit(900); 

        // Get all guides with events this month
        $guideIds = EventSalary::where('guide_start_time', '!=', null)
            // ->whereMonth('guide_start_time', now()->month)
            // ->whereYear('guide_start_time', now()->year)
            ->whereMonth('guide_start_time', 12)  // January
            ->whereYear('guide_start_time', 2024)
            // ->where('guideId', 81)
            // ->where('is_final', 0)
            ->distinct()
            ->pluck('guideId');


        foreach ($guideIds as $guideId) {
          
            // Process one guide
            $eventSalaries = EventSalary::where('guide_start_time', '!=', null)
            ->whereMonth('guide_start_time', 12)  // January
            ->whereYear('guide_start_time', 2024)
                // ->where('is_final', 0)
                ->where('guideId', $guideId)
                ->get();

            $breakService = new BreakTimeService();
            
            foreach ($eventSalaries as $salary) {
                $breakService->processBreaksSleep($salary);
            }

            $this->addChoresUsingBreakTime();
            // $this->addHolidayChoresUsingBreakTime();

            \Log::info("Completed processing guide: {$guideId}");
        }
    }

    public function addHolidayChoresUsingBreakTime()
    {
        $breakDeductionLogs = BreakDeductionLog::where(function($query) {
            $query->where('holiday_hours', '>', 0)
                  ->orWhere('holiday_night_hours', '>', 0);
        })
        ->orderBy('date')
        ->orderBy('from_time')
        ->get();


        foreach ($breakDeductionLogs as $log) {
            \Log::info('Processing break log:', [
                'log_id' => $log->id,
                'holiday_hours' => $log->holiday_hours,
                'holiday_night_hours' => $log->holiday_night_hours,
                'from_time' => $log->from_time,
                'to_time' => $log->to_time
            ]);

            $eventSalary = EventSalary::where('eventId', $log->event_id)
                ->orderBy('id','desc')
                ->first();
            if (!$eventSalary) {
                \Log::warning('No event salary found for log:', ['log_id' => $log->id]);
                continue;
            }

            $endTime = Carbon::parse($eventSalary->guide_end_time);
            
            // Debug compensation attempt
            \Log::info('Attempting compensation:', [
                'event_id' => $eventSalary->eventId,
                'original_end' => $endTime->format('Y-m-d H:i:s'),
                'holiday_hours' => $log->holiday_hours,
                'holiday_night_hours' => $log->holiday_night_hours
            ]);

            // Add this check before any time modifications
            if ($endTime->format('H:i') < '11:00' || $$eventSalary->guide_start_time->format('H:i') > '13:00') {
                continue; // Skip events outside lunch period
            }

            // First handle night hours if exists
            if ($log->holiday_night_hours > 0) {
                // Convert HH.MM format to minutes
                $hoursAndMinutes = explode('.', number_format($log->holiday_night_hours, 2));
                $hours = (int)$hoursAndMinutes[0];
                $minutes = isset($hoursAndMinutes[1]) ? (int)$hoursAndMinutes[1] : 0;

                $deductedMinutes = ($hours * 60) + $minutes;
                $eightAM = Carbon::parse($eventSalary->guide_end_time->format('Y-m-d') . ' 08:00:00');
                $eightPM = Carbon::parse($eventSalary->guide_end_time->format('Y-m-d') . ' 20:00:00');

                // Check if current end time is in night period
                if ($endTime->lt($eightAM) || $endTime->gte($eightPM)) {
                    // Can extend in night period
                 
                    $eventSalary->guide_end_time = $endTime->copy()->addMinutes($deductedMinutes);
                   
                    $eventSalary->save();
                } else {
                    // Create night chore
                    $this->createHolidayNightChore($log->guide_id, $log->date, $deductedMinutes);
                }
            }

            // Then handle remaining holiday hours
            $remainingHolidayHours = (float)$log->holiday_hours - (float)$log->holiday_night_hours;

            // Convert HH.MM format to minutes
            $hoursAndMinutes = explode('.', number_format($remainingHolidayHours, 2));
            $hours = (int)$hoursAndMinutes[0];
            $minutes = isset($hoursAndMinutes[1]) ? (int)$hoursAndMinutes[1] : 0;

            $deductedMinutes = ($hours * 60) + $minutes;
            if ($deductedMinutes > 0) {

                $eightPM = Carbon::parse($eventSalary->guide_end_time->format('Y-m-d') . ' 20:00:00');
                $eightAM = Carbon::parse($eventSalary->guide_end_time->format('Y-m-d') . ' 08:00:00');

                // Check if can extend within day hours (8am-8pm)
                if ($endTime->gte($eightAM) && $endTime->lt($eightPM) && 
                    $endTime->copy()->addMinutes($deductedMinutes)->lt($eightPM)) {
                    // This condition is allowing modification when it shouldn't
                  
                    $eventSalary->guide_end_time = $endTime->copy()->addMinutes($deductedMinutes);
                   
                    $eventSalary->save();
                } else {
                    // Create daytime chore
                    $this->createHolidayChore($log->guide_id, $log->date, $deductedMinutes);
                }
            }

            // After processing both holiday and night hours
            // Delete or mark as processed
            $log->delete();
            $salaryController = new SalaryController();
            $salaryController->recalculateSpecific([$eventSalary->id]);
            // OR if you want to keep history:
            // $log->update(['processed_at' => now()]);
        }
    }

    private function createHolidayChore($guideId, $date, $minutes)
    {
        // Create chore between 1pm-7pm
        // Convert date string to Carbon and set time
        $baseDate = Carbon::parse($date)->format('Y-m-d');
        $startTime = Carbon::parse($baseDate . ' 13:00:00');
        $endTime = $startTime->copy()->addMinutes($minutes);
        
        if ($endTime->format('H:i') > '19:00') {
            // Adjust if exceeding 7pm
            $endTime = Carbon::parse($date . ' 19:00:00');
        }
        
              // Create Event
              $event = new Event();
              $event->event_id = 'CHORE-' . uniqid();
              $event->name = 'Z Chore';
              $event->start_time = $startTime;
              $event->end_time = $endTime;
              $event->status = 1;
              $event->is_final = 1;
              $event->save();
      
              
              // Create EventSalary
              $eventSalary = new EventSalary();
              $eventSalary->eventId = $event->id;
              $eventSalary->guideId = $guideId;
              $eventSalary->total_salary = 0;
              $eventSalary->normal_hours = 0;
              $eventSalary->normal_night_hours = 0;
              $eventSalary->sunday_hours = 0;
              $eventSalary->holiday_hours = 0;
              $eventSalary->holiday_night_hours = 0;
              $eventSalary->guide_start_time = $startTime;
              $eventSalary->guide_end_time = $endTime;
              $eventSalary->approval_status = 1;
              $eventSalary->is_chore = 1;
              $eventSalary->is_final = 1;
              $eventSalary->save();


              $salaryController = new SalaryController();
              $salaryController->recalculateSpecific([$eventSalary->id]);
              
    }

    private function createHolidayNightChore($guideId, $date, $minutes)
    {
        // Try to create chore in night period (20:00-08:00)
        $baseDate = Carbon::parse($date)->format('Y-m-d');
        $startTime = Carbon::parse($baseDate . ' 20:00:00');
        $endTime = $startTime->copy()->addMinutes($minutes);
        
        // Create unique event ID
        $eventId = 'CHORE-' . uniqid();
        
        // Create Event
        $event = new Event();
        $event->event_id = 'CHORE-' . uniqid();
        $event->name = 'Z Chore';
        $event->start_time = $startTime;
        $event->end_time = $endTime;
        $event->status = 1;
        $event->is_final = 1;
        $event->save();

        
        // Create EventSalary
        $eventSalary = new EventSalary();
        $eventSalary->eventId = $event->id;
        $eventSalary->guideId = $guideId;
        $eventSalary->total_salary = 0;
        $eventSalary->normal_hours = 0;
        $eventSalary->normal_night_hours = 0;
        $eventSalary->sunday_hours = 0;
        $eventSalary->holiday_hours = 0;
        $eventSalary->holiday_night_hours = 0;
        $eventSalary->guide_start_time = $startTime;
        $eventSalary->guide_end_time = $endTime;
        $eventSalary->approval_status = 1;
        $eventSalary->is_chore = 1;
        $eventSalary->is_final = 1;
        $eventSalary->save();

        $salaryController = new SalaryController();
        $salaryController->recalculateSpecific([$eventSalary->id]);
    }

    public function addChoresUsingBreakTime()
    {
        $breakDeductionLogs = BreakDeductionLog::where(function($query) {
            $query->where('normal_hours', '>', 0)
                  ->orWhere('normal_night_hours', '>', 0);
        })
        ->orderBy('date')
        ->orderBy('from_time')
        ->get();
    
        foreach ($breakDeductionLogs as $log) {
            $eventSalary = EventSalary::where('eventId', $log->event_id)
                ->where('guideId', $log->guide_id)
                ->orderBy('id','desc')
                ->first();
                
            if (!$eventSalary) continue;
    
            $endTime = Carbon::parse($eventSalary->guide_end_time);
            // First handle night hours if exists
            if ($log->normal_night_hours > 0) {
                $hoursAndMinutes = explode('.', number_format($log->normal_night_hours, 2));
                $hours = (int)$hoursAndMinutes[0];
                $minutes = isset($hoursAndMinutes[1]) ? (int)$hoursAndMinutes[1] : 0;
    
                $deductedMinutes = ($hours * 60) + $minutes;
                
                // Parse the times correctly
                $eightAM = Carbon::parse($endTime->format('Y-m-d') . ' 08:00:00');
                $eightPM = Carbon::parse($endTime->format('Y-m-d') . ' 20:00:00');
    
                if ($endTime->lt($eightAM) || $endTime->gte($eightPM)) {
                    $eventSalary->guide_end_time = $endTime->copy()->addMinutes($deductedMinutes);
                    $eventSalary->save();
                } else {
                    $this->createNightChore($log->guide_id, $log->date, $deductedMinutes);
                }
            }
    
            // Then handle remaining normal hours
            $remainingNormalHours = (float)$log->normal_hours - (float)$log->normal_night_hours;
            
            $hoursAndMinutes = explode('.', number_format($remainingNormalHours, 2));
            $hours = (int)$hoursAndMinutes[0];
            $minutes = isset($hoursAndMinutes[1]) ? (int)$hoursAndMinutes[1] : 0;
    
            $deductedMinutes = ($hours * 60) + $minutes;
    
            if ($deductedMinutes > 0) {
                // Parse the times correctly
                $eightPM = Carbon::parse($endTime->format('Y-m-d') . ' 20:00:00');
                $eightAM = Carbon::parse($endTime->format('Y-m-d') . ' 08:00:00');
    
                if ($endTime->gte($eightAM) && $endTime->lt($eightPM) && $endTime->copy()->addMinutes($deductedMinutes)->lt($eightPM)) {
                    $eventSalary->guide_end_time = $endTime->copy()->addMinutes($deductedMinutes);
                    $eventSalary->save();
                } else {
                    $this->createNormalChore($log->guide_id, $log->date, $deductedMinutes);
                }
            }
    
            $log->delete();
            $salaryController = new SalaryController();
            $salaryController->recalculateSpecific([$eventSalary->id]);
        }
    }
    private function createNightChore($guideId, $date, $minutes)
    {
        $baseDate = Carbon::parse($date)->format('Y-m-d');
        $startTime = Carbon::parse($baseDate . ' 20:00:00');
        $endTime = $startTime->copy()->addMinutes($minutes);
        
        $eventId = 'CHORE-' . uniqid();
        
        $event = new Event();
        $event->event_id = 'CHORE-' . uniqid();
        $event->name = 'Z Chore';
        $event->start_time = $startTime;
        $event->end_time = $endTime;
        $event->status = 1;
        $event->is_final = 1;
        $event->save();

        
        // Create EventSalary
        $eventSalary = new EventSalary();
        $eventSalary->eventId = $event->id;
        $eventSalary->guideId = $guideId;
        $eventSalary->total_salary = 0;
        $eventSalary->normal_hours = 0;
        $eventSalary->normal_night_hours = 0;
        $eventSalary->sunday_hours = 0;
        $eventSalary->holiday_hours = 0;
        $eventSalary->holiday_night_hours = 0;
        $eventSalary->guide_start_time = $startTime;
        $eventSalary->guide_end_time = $endTime;
        $eventSalary->approval_status = 1;
        $eventSalary->is_chore = 1;
        $eventSalary->is_final = 1;
        $eventSalary->save();

        $salaryController = new SalaryController();
        $salaryController->recalculateSpecific([$eventSalary->id]);
    }

    private function createNormalChore($guideId, $date, $minutes)
    {
        $baseDate = Carbon::parse($date)->format('Y-m-d');
        $startTime = Carbon::parse($baseDate . ' 13:00:00');
        $endTime = $startTime->copy()->addMinutes($minutes);
        
        if ($endTime->format('H:i') > '19:00') {
            $endTime = Carbon::parse($baseDate . ' 19:00:00');
        }
        
        $eventId = 'CHORE-' . uniqid();
        
        $event = new Event();
        $event->event_id = 'CHORE-' . uniqid();
        $event->name = 'Z Chore';
        $event->start_time = $startTime;
        $event->end_time = $endTime;
        $event->status = 1;
        $event->is_final = 1;
        $event->save();

        
        // Create EventSalary
        $eventSalary = new EventSalary();
        $eventSalary->eventId = $event->id;
        $eventSalary->guideId = $guideId;
        $eventSalary->total_salary = 0;
        $eventSalary->normal_hours = 0;
        $eventSalary->normal_night_hours = 0;
        $eventSalary->sunday_hours = 0;
        $eventSalary->holiday_hours = 0;
        $eventSalary->holiday_night_hours = 0;
        $eventSalary->guide_start_time = $startTime;
        $eventSalary->guide_end_time = $endTime;
        $eventSalary->approval_status = 1;
        $eventSalary->is_chore = 1;
        $eventSalary->is_final = 1;
        $eventSalary->save();

        $salaryController = new SalaryController();
        $salaryController->recalculateSpecific([$eventSalary->id]);

    }
}
