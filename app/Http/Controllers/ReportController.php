<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventSalary;
use App\Models\TourGuide;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\SalaryController;
use App\Models\Holiday;
use App\Models\ManagerGuideAssignment;
use App\Models\SalaryUpdated;
use App\Models\StaffMissingHours; // Add this import for missing hours
use App\Models\StaffUser;
use App\Models\SupervisorSickLeaves;
use App\Models\StaffMonthlyHours;
use App\Models\Supervisors;

class ReportController extends Controller
{
    public function monthlyReportCreate()
    {
        return view('reports.monthly-report-create');
    }

    public function monthlyReportCreateChristmas()
    {
        // Extract year and month from the request
        $monthYear = '2025-01';
        $date = \Carbon\Carbon::parse($monthYear);

        // Create the base query
        $baseQuery = EventSalary::select(
            'guideId',
            'guide_start_time',
            'guide_end_time',
            'normal_hours',
            'normal_night_hours',
            'holiday_hours',
            'holiday_night_hours'
        )
            ->whereYear('guide_start_time', $date->year)
            ->whereMonth('guide_start_time', $date->month)
            ->whereHas('event', function($query) {
                $query->where('event_id', 'NOT LIKE', '%manual-missing-hours%');
            })
            ->whereIn('approval_status', [1, 2]);

        // Get salary updates for the month
        $salaryUpdates = SalaryUpdated::whereYear('effective_date', $date->year)
            ->whereMonth('effective_date', $date->month)
            ->get()
            ->groupBy('guide_id');

        $results = collect();

        // Get all unique guide IDs from event salaries
        $guideIds = $baseQuery->pluck('guideId')->unique();

        foreach ($guideIds as $guideId) {
            // Check if guide has salary update
            $guideUpdates = $salaryUpdates->get($guideId);
            
            if ($guideUpdates && $guideUpdates->isNotEmpty()) {
                $salaryUpdate = $guideUpdates->first();
                
                // Split period calculation
                $beforeUpdate = $baseQuery->clone()
                    ->where('guideId', $guideId)
                    ->where('guide_start_time', '<', $salaryUpdate->effective_date)
                    ->get();

                $afterUpdate = $baseQuery->clone()
                    ->where('guideId', $guideId)
                    ->where('guide_start_time', '>=', $salaryUpdate->effective_date)
                    ->get();

                // Process before update period
                if ($beforeUpdate->isNotEmpty()) {
                    $beforeEndDate = Carbon::parse($salaryUpdate->effective_date)->startOfDay();
                    $afterStartDate = $beforeEndDate->copy();

                    list($bonus3Hours, $bonus5Hours) = $this->calculateChristmasBonusHours(
                        $guideId, 
                        $date->format('Y-m-01'), 
                        $beforeEndDate->format('Y-m-d H:i:s')
                    );

                    $results->push($this->processGuidePeriod(
                        $guideId,
                        $beforeUpdate,
                        'Before Update (' . $date->format('Y-m-01') . ' to ' . $beforeEndDate->subDay()->format('Y-m-d') . ')',
                        $bonus3Hours,
                        $bonus5Hours
                    ));
                }

                // Process after update period
                if ($afterUpdate->isNotEmpty()) {
                    $beforeEndDate = Carbon::parse($salaryUpdate->effective_date)->startOfDay();
                    $afterStartDate = $beforeEndDate->copy();

                    list($bonus3Hours, $bonus5Hours) = $this->calculateChristmasBonusHours(
                        $guideId, 
                        $afterStartDate->format('Y-m-d H:i:s'),
                        $date->format('Y-m-t 23:59:59')
                    );

                    $results->push($this->processGuidePeriod(
                        $guideId,
                        $afterUpdate,
                        'After Update (' . $afterStartDate->format('Y-m-d') . ' to ' . $date->format('Y-m-t') . ')',
                        $bonus3Hours,
                        $bonus5Hours
                    ));
                }
            } else {
                // Process entire month for guides without updates
                list($bonus3Hours, $bonus5Hours) = $this->calculateChristmasBonusHours(
                    $guideId, 
                    $date->format('Y-m-01'), 
                    $date->format('Y-m-t')
                );

                $guideData = $baseQuery->clone()
                    ->where('guideId', $guideId)
                    ->get();

                $results->push($this->processGuidePeriod(
                    $guideId,
                    $guideData,
                    'Full Month',
                    $bonus3Hours,
                    $bonus5Hours
                ));
            }
        }

        return view('reports.monthly-christmas', compact('results', 'monthYear'));
    }

    private function processGuidePeriod($guideId, $salaryData, $period, $bonus3Hours, $bonus5Hours)
    {
        $normalHours = $salaryData->pluck('normal_hours')->filter();
        $normalNightHours = $salaryData->pluck('normal_night_hours')->filter();
        $holidayHours = $salaryData->pluck('holiday_hours')->filter();
        $holidayNightHours = $salaryData->pluck('holiday_night_hours')->filter();

        // Calculate special holiday bonuses for the entire period
        $startTimes = $salaryData->pluck('guide_start_time');
        $endTimes = $salaryData->pluck('guide_end_time');
        
        // Get the period's date range
        $periodStart = Carbon::parse($startTimes->min());
        $periodEnd = Carbon::parse($endTimes->max());

        // Calculate Christmas bonus hours for the entire period
        list($bonus3Hours, $bonus5Hours) = $this->calculateChristmasBonusHours(
            $guideId,
            $periodStart->format('Y-m-d H:i:s'),
            $periodEnd->format('Y-m-d H:i:s')
        );

        // Calculate special holiday bonuses
        list($regularDayFormat, $regularNightFormat, $extrasDayFormat, $extrasNightFormat) = 
            $this->calculateSpecialHolidayBonusHours(
                $guideId,
                $startTimes,
                $endTimes
            );

        return [
            'guideId' => $guideId,
            'period' => $period,
            'totalNormalHours' => $this->sumDecimalHours($normalHours->toArray()),
            'totalNormalNightHours' => $this->sumDecimalHours($normalNightHours->toArray()),
            'totalHolidayHours' => $this->sumDecimalHours($holidayHours->toArray()),
            'totalHolidayNightHours' => $this->sumDecimalHours($holidayNightHours->toArray()),
            'bonus3Hours' => $regularDayFormat,    // Use the special holiday calculation results
            'bonus5Hours' => $regularNightFormat,  // Use the special holiday calculation results
            'specialHolidayDay' => $extrasDayFormat,
            'specialHolidayNight' => $extrasNightFormat,
            'tourGuide' => TourGuide::find($guideId)
        ];
    }

    private function calculateSpecialHolidayBonusHours($guideId, $startTimes, $endTimes)
    {
        // Define special dates and rates with extra flag - exactly as in getGuideTimeReportChristmas
        $specialDates = [
            ['start' => '2024-12-24 06:00', 'end' => '2024-12-24 20:00', 'bonus' => 3, 'extras' => false],
            ['start' => '2024-12-24 20:00', 'end' => '2024-12-25 06:00', 'bonus' => 5, 'extras' => false],
            ['start' => '2024-12-25 06:00', 'end' => '2024-12-25 20:00', 'bonus' => 3, 'extras' => true],
            ['start' => '2024-12-25 20:00', 'end' => '2024-12-26 06:00', 'bonus' => 5, 'extras' => true],
            ['start' => '2024-12-31 06:00', 'end' => '2024-12-31 20:00', 'bonus' => 3, 'extras' => false],
            ['start' => '2024-12-31 20:00', 'end' => '2025-01-01 06:00', 'bonus' => 5, 'extras' => false],
            ['start' => '2025-01-01 06:00', 'end' => '2025-01-01 20:00', 'bonus' => 3, 'extras' => true],
            ['start' => '2025-01-01 20:00', 'end' => '2025-01-02 06:00', 'bonus' => 3, 'extras' => true],
        ];

        $regularBonus3Minutes = 0;
        $regularBonus5Minutes = 0;
        $extrasBonus3Minutes = 0;
        $extrasBonus5Minutes = 0;

        // Combine start and end times into pairs
        $timesPairs = $startTimes->zip($endTimes);

        foreach ($timesPairs as $times) {
            $shiftStart = Carbon::parse($times[0]);
            $shiftEnd = Carbon::parse($times[1]);

            foreach ($specialDates as $period) {
                $periodStart = Carbon::parse($period['start']);
                $periodEnd = Carbon::parse($period['end']);

                // Check if the shift overlaps with the special period
                if ($shiftStart->lt($periodEnd) && $shiftEnd->gt($periodStart)) {
                    // Calculate the overlap
                    $overlapStart = max($shiftStart->timestamp, $periodStart->timestamp);
                    $overlapEnd = min($shiftEnd->timestamp, $periodEnd->timestamp);
                    $overlapMinutes = ($overlapEnd - $overlapStart) / 60;

                    // Add to regular bonuses for ALL special days
                    if ($period['bonus'] == 3) {
                        $regularBonus3Minutes += $overlapMinutes;
                    } else {
                        $regularBonus5Minutes += $overlapMinutes;
                    }

                    // Additionally add to extras bonuses if it's a special day with extras
                    if ($period['extras']) {
                        if ($period['bonus'] == 3) {
                            $extrasBonus3Minutes += $overlapMinutes;
                        } else {
                            $extrasBonus5Minutes += $overlapMinutes;
                        }
                    }
                }
            }
        }

        // Convert minutes to HH.MM format using the same format method
        return [
            $this->minutesToHoursFormat($regularBonus3Minutes),
            $this->minutesToHoursFormat($regularBonus5Minutes),
            $this->minutesToHoursFormat($extrasBonus3Minutes),
            $this->minutesToHoursFormat($extrasBonus5Minutes)
        ];
    }

    private function calculateAndAddOverlap($shiftStart, $shiftEnd, $periodStart, $periodEnd, $type, &$regularMinutes, &$extrasMinutes)
    {
        if ($shiftStart->lt($periodEnd) && $shiftEnd->gt($periodStart)) {
            $overlapStart = max($shiftStart->timestamp, $periodStart->timestamp);
            $overlapEnd = min($shiftEnd->timestamp, $periodEnd->timestamp);
            $minutes = ($overlapEnd - $overlapStart) / 60;
            
            if ($type === 'regular') {
                $regularMinutes += $minutes;
            } else {
                $extrasMinutes += $minutes;
            }
        }
    }

    private function isSpecialHolidayRevised(Carbon $date)
    {
        // Special days with specific time ranges
        $specialPeriods = [
            ['date' => '2024-12-24', 'start' => 6, 'end' => 22],  // Christmas Eve
            ['date' => '2024-12-31', 'start' => 6, 'end' => 22],  // New Year's Eve
        ];

        foreach ($specialPeriods as $period) {
            if ($date->format('Y-m-d') === $period['date']) {
                $hour = $date->hour;
                return $hour >= $period['start'] && $hour < $period['end'];
            }
        }
        return false;
    }

    private function isExtraHolidayRevised(Carbon $date)
    {
        // Extra days with specific time ranges
        $extraPeriods = [
            ['date' => '2024-12-25', 'start' => 6, 'end' => 22],  // Christmas Day
            ['date' => '2024-12-26', 'start' => 6, 'end' => 22],  // Boxing Day
            ['date' => '2025-01-01', 'start' => 6, 'end' => 22],  // New Year's Day
        ];

        foreach ($extraPeriods as $period) {
            if ($date->format('Y-m-d') === $period['date']) {
                $hour = $date->hour;
                return $hour >= $period['start'] && $hour < $period['end'];
            }
        }
        return false;
    }

    private function calculateChristmasBonusHours($guideId, $startDate, $endDate)
    {
        // Define special dates and rates
        $specialDates = [
            // Christmas Eve and Christmas Day
            ['start' => '2024-12-24 06:00', 'end' => '2024-12-24 20:00', 'bonus' => 3], // Day
            ['start' => '2024-12-24 20:00', 'end' => '2024-12-25 06:00', 'bonus' => 5], // Night
            ['start' => '2024-12-25 06:00', 'end' => '2024-12-25 20:00', 'bonus' => 3], // Day
            ['start' => '2024-12-25 20:00', 'end' => '2024-12-26 06:00', 'bonus' => 5], // Night
            
            // New Year's Eve and New Year's Day
            ['start' => '2024-12-31 06:00', 'end' => '2024-12-31 20:00', 'bonus' => 3], // Day
            ['start' => '2024-12-31 20:00', 'end' => '2025-01-01 06:00', 'bonus' => 5], // Night
            ['start' => '2025-01-01 06:00', 'end' => '2025-01-01 20:00', 'bonus' => 3], // Day
            ['start' => '2025-01-01 20:00', 'end' => '2025-01-02 06:00', 'bonus' => 3]  // Night
        ];

        $eventSalaries = EventSalary::where('guideId', $guideId)
            ->whereBetween('guide_start_time', [$startDate, $endDate])
            ->whereIn('approval_status', [1, 2])
            ->whereHas('event', function($query) {
                $query->where('event_id', 'NOT LIKE', '%manual-missing-hours%');
            })
            ->get();

        $bonus3Minutes = 0;
        $bonus5Minutes = 0;

        foreach ($eventSalaries as $shift) {
            $shiftStart = Carbon::parse($shift->guide_start_time);
            $shiftEnd = Carbon::parse($shift->guide_end_time);

            foreach ($specialDates as $period) {
                $periodStart = Carbon::parse($period['start']);
                $periodEnd = Carbon::parse($period['end']);

                // Check if the shift overlaps with the special period
                if ($shiftStart->lt($periodEnd) && $shiftEnd->gt($periodStart)) {
                    // Calculate the overlap
                    $overlapStart = max($shiftStart->timestamp, $periodStart->timestamp);
                    $overlapEnd = min($shiftEnd->timestamp, $periodEnd->timestamp);
                    $overlapMinutes = ($overlapEnd - $overlapStart) / 60;

                    if ($period['bonus'] == 3) {
                        $bonus3Minutes += $overlapMinutes;
                    } else {
                        $bonus5Minutes += $overlapMinutes;
                    }
                }
            }
        }

        // Convert minutes to HH.MM format
        $bonus3Hours = floor($bonus3Minutes / 60);
        $bonus3Mins = round($bonus3Minutes % 60);
        $bonus3Format = sprintf("%d.%02d", $bonus3Hours, $bonus3Mins);

        $bonus5Hours = floor($bonus5Minutes / 60);
        $bonus5Mins = round($bonus5Minutes % 60);
        $bonus5Format = sprintf("%d.%02d", $bonus5Hours, $bonus5Mins);

        return [$bonus3Format, $bonus5Format];
    }

    public function monthlyReportCreateOp(Request $request)
    {
        // Get month and year from request, default to current if not provided
        $year = $request->input('year', date('Y'));
        $month = $request->input('month', date('n'));
        
        // Create date string and Carbon instance
        $monthYear = sprintf('%d-%02d', $year, $month);
        $date = Carbon::createFromFormat('Y-m', $monthYear);

        // Fetch all events that occurred in the specified month and year
        $events = Event::whereYear('start_time', $date->year)
            ->whereMonth('start_time', $date->month)
            ->get();
        
        // Get the event IDs from the filtered events
        $eventIds = $events->pluck('id');

        // Create the query but don't execute it yet
        $query = EventSalary::select(
            'guideId',
            DB::raw('GROUP_CONCAT(DISTINCT guide_start_time) as guide_start_times'),
            DB::raw('GROUP_CONCAT(DISTINCT guide_end_time) as guide_end_times'),
            DB::raw('GROUP_CONCAT(normal_hours) as normal_hours_list'),
            DB::raw('GROUP_CONCAT(normal_night_hours) as normal_night_hours_list'),
            DB::raw('GROUP_CONCAT(holiday_hours) as holiday_hours_list'),
            DB::raw('GROUP_CONCAT(holiday_night_hours) as holiday_night_hours_list')
        )
            ->whereIn('eventId', $eventIds)
            ->whereIn('approval_status', [1, 2])
            ->groupBy('guideId')
            ->with('tourGuide');

        // Now execute the query
        $eventSalaries = $query->get()->map(function ($salary) {
            // Convert string lists to arrays
            $normalHours = explode(',', $salary->normal_hours_list);
            $normalNightHours = explode(',', $salary->normal_night_hours_list);
            $holidayHours = explode(',', $salary->holiday_hours_list);
            $holidayNightHours = explode(',', $salary->holiday_night_hours_list);
            
            // Calculate totals using the existing sumDecimalHours method
            return [
                'guideId' => $salary->guideId,
                'totalNormalHours' => str_replace('.', ':', $this->sumDecimalHours($normalHours)),
                'totalNormalNightHours' => str_replace('.', ':', $this->sumDecimalHours($normalNightHours)),
                'totalHolidayHours' => str_replace('.', ':', $this->sumDecimalHours($holidayHours)),
                'totalHolidayNightHours' => str_replace('.', ':', $this->sumDecimalHours($holidayNightHours)),
                'tourGuide' => $salary->tourGuide
            ];
        });

        return view('reports.monthly-op', compact('events', 'eventSalaries', 'monthYear'));
    }

    public function guideWiseReportCreate()
    {
        if (Auth::user()->role=='admin' || Auth::user()->role=='team-lead' || Auth::user()->role=='hr-assistant') {
            $tourGuides = TourGuide::orderBy('name', 'asc')->get();
        }elseif(Auth::user()->role=='supervisor') {
            $tourGuides = TourGuide::where('supervisor',Auth::id())->orderBy('name', 'asc')->get();
        }
       
        return view('reports.guide-wise-report-create', compact('tourGuides'));
    }

    public function guideWiseReportCustomCreate()
    {
        if (Auth::user()->role=='admin' || Auth::user()->role=='team-lead' || Auth::user()->role=='hr-assistant') {
            $tourGuides = TourGuide::orderBy('name', 'asc')->get();
        }elseif(Auth::user()->role=='supervisor') {
            $tourGuides = TourGuide::where('supervisor',Auth::id())->orderBy('name', 'asc')->get();
        }
       
        return view('reports.guide-wise-report-custom-create', compact('tourGuides'));
    }

    public function terminatedGuideWiseReportCreate()
    {
        if (Auth::user()->role=='admin' || Auth::user()->role=='team-lead' || Auth::user()->role=='hr-assistant') {
            $tourGuides = TourGuide::onlyTrashed()->orderBy('name', 'asc')->get();
        }elseif(Auth::user()->role=='supervisor') {
            $tourGuides = TourGuide::onlyTrashed()->where('supervisor',Auth::id())->orderBy('name', 'asc')->get();
        }
       
        return view('reports.terminated-guide-wise-report-create', compact('tourGuides'));
    }

    public function ManualEntries()
    {
        // Get all edited events
        $editedEvents = Event::where('is_edited', 1)->pluck('id');

        // Fetch all event salaries for the edited events
        $entries = EventSalary::whereIn('eventId', $editedEvents)->orderBy('guide_start_time', 'desc')->get();

        
        return view('reports.manually-added-entries', compact('entries'));
    }

    public function ManualTours()
    {
        // Get all edited events
        $manualEvents = Event::where('event_id', 'like', '%manual%')->pluck('id');

        // Fetch all event salaries for the edited events
        $entries = EventSalary::whereIn('eventId', $manualEvents)->orderBy('guide_start_time', 'desc')->get();

        
        return view('reports.manually-added-entries', compact('entries'));
    }

    public function rejectedHours()
    {
        // Fetch all rejected event salaries
        $rejectedEventSalaries = EventSalary::where('approval_status', 4)->get();

        return view('reports.rejected-hours', compact('rejectedEventSalaries'));
    }

    public function guideTimeReportCreate()
    {
        $tourGuides = TourGuide::orderBy('name', 'asc')->get();
        return view('reports.guide-time-report-create', compact('tourGuides'));
    }

    public function guideTimeReportCreateChristmas()
    {
        $tourGuides = TourGuide::where('is_hidden', false)->orderBy('name', 'asc')->get(); 
        return view('reports.guide-time-report-christmas', compact('tourGuides'));
    }

    public function getMonthlyReport(Request $request)
    {
        // Extract year and month from the request
        $monthYear = $request->input('month');
        $date = \Carbon\Carbon::parse($monthYear);

        $query = EventSalary::select(
            'guideId',
            DB::raw('GROUP_CONCAT(DISTINCT guide_start_time) as guide_start_times'),
            DB::raw('GROUP_CONCAT(DISTINCT guide_end_time) as guide_end_times'),
            DB::raw('GROUP_CONCAT(normal_hours) as normal_hours_list'),
            DB::raw('GROUP_CONCAT(normal_night_hours) as normal_night_hours_list'),
            DB::raw('GROUP_CONCAT(holiday_hours) as holiday_hours_list'),
            DB::raw('GROUP_CONCAT(holiday_night_hours) as holiday_night_hours_list')
        )
            ->whereYear('guide_start_time', $date->year)
            ->whereMonth('guide_start_time', $date->month)
            ->whereHas('event', function($query) {
                $query->where('event_id', 'NOT LIKE', '%manual-missing-hours%');
            })
            ->whereIn('approval_status', [1, 2])
            ->groupBy('guideId')
            ->with('tourGuide');

        // Now execute the query
        $eventSalaries = $query->get()->map(function ($salary) {
            // Convert string lists to arrays
            $normalHours = explode(',', $salary->normal_hours_list);
            $normalNightHours = explode(',', $salary->normal_night_hours_list);
            $holidayHours = explode(',', $salary->holiday_hours_list);
            $holidayNightHours = explode(',', $salary->holiday_night_hours_list);
            
            // Calculate totals using the existing sumDecimalHours method
            return [
                'guideId' => $salary->guideId,
                'totalNormalHours' => str_replace('.', ':', $this->sumDecimalHours($normalHours)),
                'totalNormalNightHours' => str_replace('.', ':', $this->sumDecimalHours($normalNightHours)),
                'totalHolidayHours' => str_replace('.', ':', $this->sumDecimalHours($holidayHours)),
                'totalHolidayNightHours' => str_replace('.', ':', $this->sumDecimalHours($holidayNightHours)),
                'tourGuide' => $salary->tourGuide
            ];
        });


        return view('reports.monthly', compact('eventSalaries', 'monthYear'));
    }

    public function getMonthlyReportOp(Request $request)
    {}

    public function getGuideWiseReport(Request $request)
    {
        // Validate input
        $request->validate([
            'guide_id' => 'required|exists:tour_guides,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        // Fetch the selected guide
        $guideId = $request->input('guide_id');
        $startDate = \Carbon\Carbon::parse($request->input('start_date'))->startOfDay();
        $endDate = \Carbon\Carbon::parse($request->input('end_date'))->endOfDay();

        // Fetch event salaries for the selected guide within the specified date range
        $eventSalaries = EventSalary::where('guideId', $guideId)
            ->whereBetween('guide_start_time', [$startDate, $endDate])
            ->whereIn('approval_status', [1, 2])
            ->whereHas('event', function($query) {
                $query->whereRaw("event_id NOT LIKE '%manual-missing-hours%'");
            })
            ->with('event')
            ->orderBy('guide_start_time', 'asc') // Sort by date ascending
            ->get()
            ->groupBy(function($item) {
                return Carbon::parse($item->guide_start_time)->format('Y-m-d'); // Group by date
            });

        $tourGuide = TourGuide::find($guideId);

        // Pass the guide, event salaries, and date range to the view
        if(Auth::user()->role == 'team-lead'){
            $assignedGuideIds = ManagerGuideAssignment::where('manager_id', auth()->id())->pluck('guide_id')->toArray();
            $tourGuides = TourGuide::where('is_hidden', false)->whereIn('id', $assignedGuideIds)->orderBy('name', 'asc')->get();
        } else {
            $tourGuides = TourGuide::where('is_hidden', false)->orderBy('name', 'asc')->get();
        }

        return view('reports.guide-wise-report', compact('tourGuide', 'tourGuides', 'eventSalaries', 'startDate', 'endDate'));
    }
    

    public function getGuideWiseCustomReport(Request $request)
    {
        // Validate input
        $request->validate([
            'guide_id' => 'required|exists:tour_guides,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        // Fetch the selected guide
        $guideId = $request->input('guide_id');
        $startDate = \Carbon\Carbon::parse($request->input('start_date'))->startOfDay();
        $endDate = \Carbon\Carbon::parse($request->input('end_date'))->endOfDay();

        // Fetch event salaries for the selected guide within the specified date range
        $eventSalaries = EventSalary::where('guideId', $guideId)
            ->whereBetween('guide_start_time', [$startDate, $endDate])
            ->whereIn('approval_status', [1, 2])
            ->whereHas('event', function($query) {
                $query->whereRaw("event_id NOT LIKE '%manual-missing-hours%'");
            })
            ->with('event')
            ->orderBy('guide_start_time', 'asc') // Sort by date ascending
            ->get()
            ->groupBy(function($item) {
                return Carbon::parse($item->guide_start_time)->format('Y-m-d'); // Group by date
            });

        $tourGuide = TourGuide::find($guideId);

        // Pass the guide, event salaries, and date range to the view
        if(Auth::user()->role == 'team-lead'){
            $assignedGuideIds = ManagerGuideAssignment::where('manager_id', auth()->id())->pluck('guide_id')->toArray();
            $tourGuides = TourGuide::where('is_hidden', false)->whereIn('id', $assignedGuideIds)->orderBy('name', 'asc')->get();
        } else {
            $tourGuides = TourGuide::where('is_hidden', false)->orderBy('name', 'asc')->get();
        }

        return view('reports.guide-wise-custom-report', compact('tourGuide', 'tourGuides', 'eventSalaries', 'startDate', 'endDate'));
    }

    public function getTerminatedGuideWiseReport(Request $request)
    {
        // Validate input
        $request->validate([
            'guide_id' => 'required|exists:tour_guides,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $guideId = $request->input('guide_id');
        $startDate = \Carbon\Carbon::parse($request->input('start_date'))->startOfDay();
        $endDate = \Carbon\Carbon::parse($request->input('end_date'))->endOfDay();

        // Fetch event salaries (keep existing logic)
        $eventSalaries = EventSalary::where('guideId', $guideId)
            ->whereBetween('guide_start_time', [$startDate, $endDate])
            ->whereIn('approval_status', [1, 2])
            ->whereHas('event', function($query) {
                $query->whereRaw("event_id NOT LIKE '%manual-missing-hours%'");
            })
            ->with('event')
            ->orderBy('guide_start_time', 'asc')
            ->get()
            ->groupBy(function($item) {
                return Carbon::parse($item->guide_start_time)->format('Y-m-d');
            });

        // Include trashed guides in queries
        $tourGuide = TourGuide::withTrashed()->find($guideId);
        $tourGuides = TourGuide::onlyTrashed()->orderBy('name', 'asc')->get();

        return view('reports.terminated-guide-wise-report', compact('tourGuide', 'tourGuides', 'eventSalaries', 'startDate', 'endDate'));
    }
    public function getGuideWiseReportByMonth(Request $request, $guideId)
    {
        // Validate input
        $request->validate([
            'year' => 'required|integer|min:1900|max:2100',
            'month' => 'required|integer|min:1|max:12',
        ]);

        $guideId = $request->input('guide_id');
        $year = $request->input('year', date('Y'));
        $month = $request->input('month', date('n'));

        // Create date range for the selected month
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        // Fetch the selected guide
        $tourGuide = TourGuide::findOrFail($guideId);

        // Fetch event salaries for the selected guide within the specified date range
        // $eventSalaries = EventSalary::where('guideId', $guideId)
        // ->whereBetween('guide_start_time', [$startDate, $endDate])
        // ->whereIn('approval_status', [1, 2])
        // ->whereHas('event', function($query) {
        //     $query->whereRaw("event_id NOT LIKE '%manual-missing-hours%'");
        // })
        // ->with('event') // Eager load the event details
        // ->orderBy('guide_start_time', 'asc')
        // ->get();

        
        $eventSalaries = EventSalary::where('guideId', $guideId)
        ->whereBetween('guide_start_time', [$startDate, $endDate])
        ->whereIn('approval_status', [1, 2])
        ->whereHas('event', function($query) {
            $query->whereRaw("event_id NOT LIKE '%manual-missing-hours%'");
        })
        ->with('event')
        ->orderBy('guide_start_time', 'asc') // Sort by date ascending
        ->get()
        ->groupBy(function($item) {
            return Carbon::parse($item->guide_start_time)->format('Y-m-d'); // Group by date
        });


        // Fetch all guides for the modal
        if(Auth::user()->role == 'team-lead'){
            $assignedGuideIds = ManagerGuideAssignment::where('manager_id', auth()->id())->pluck('guide_id')->toArray();
            $tourGuides = TourGuide::where('is_hidden', false)->whereIn('id', $assignedGuideIds)->orderBy('name', 'asc')->get();
        } else {
            $tourGuides = TourGuide::where('is_hidden', false)->orderBy('name', 'asc')->get();
        }

        // $tourGuides = TourGuide::orderBy('name','asc')->get();

        return view('reports.guide-wise-report', compact('tourGuide', 'tourGuides', 'eventSalaries', 'year', 'month'));
    }

    
    public function getTerminatedGuideWiseReportByMonth(Request $request, $guideId)
    {
        // Validate input
        $request->validate([
            'year' => 'required|integer|min:1900|max:2100',
            'month' => 'required|integer|min:1|max:12',
        ]);

        $guideId = $request->input('guide_id');
        $year = $request->input('year', date('Y'));
        $month = $request->input('month', date('n'));

        // Create date range for the selected month
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        // Fetch the selected guide
        $tourGuide = TourGuide::withTrashed()->findOrFail($guideId);

        // Keep existing EventSalary query
        $eventSalaries = EventSalary::where('guideId', $guideId)
            ->whereBetween('guide_start_time', [$startDate, $endDate])
            ->whereIn('approval_status', [1, 2])
            ->whereHas('event', function($query) {
                $query->whereRaw("event_id NOT LIKE '%manual-missing-hours%'");
            })
            ->with('event')
            ->orderBy('guide_start_time', 'asc')
            ->get()
            ->groupBy(function($item) {
                return Carbon::parse($item->guide_start_time)->format('Y-m-d');
            });

        // Fetch all soft deleted guides for the modal
        $tourGuides = TourGuide::onlyTrashed()->orderBy('name','asc')->get();

        return view('reports.terminated-guide-wise-report', compact('tourGuide', 'tourGuides', 'eventSalaries', 'year', 'month'));
    }

    public function getGuideTimeReport(Request $request)
    {
        // Validate input
        $request->validate([
            'guide_id' => 'required|exists:tour_guides,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        // Fetch the selected guide
        $guideId = $request->input('guide_id');
        $startDate = Carbon::parse($request->input('start_date'))->startOfDay();
        $endDate = Carbon::parse($request->input('end_date'))->endOfDay();


        // Fetch event salaries for the selected guide within the specified date range
        $eventSalaries = EventSalary::where('guideId', $guideId)
            ->whereBetween('guide_start_time', [$startDate, $endDate])
            ->whereIn('approval_status', [1, 2])
            ->whereHas('event', function($query) {
                $query->whereRaw("event_id NOT LIKE '%manual-missing-hours%'");
            })
            ->with('event') // Eager load the event details
            ->get();

        // Group event salaries by date
        $groupedEventSalaries = $eventSalaries->groupBy(function ($item) {
            return Carbon::parse($item->guide_start_time)->format('Y-m-d');
        })->map(function ($group) {
            $startTime = $group->first()->guide_start_time;
            $date = $startTime instanceof Carbon ? $startTime->format('Y-m-d') : Carbon::parse($startTime)->format('Y-m-d');
            
            $normalHours = $group->pluck('normal_hours')->toArray();
            $normalNightHours = $group->pluck('normal_night_hours')->toArray();
            $holidayHours = $group->pluck('holiday_hours')->toArray();
            $holidayNightHours = $group->pluck('holiday_night_hours')->toArray();
            
            $sumNormalHours = $this->sumDecimalHours($normalHours);
            $sumNormalNightHours = $this->sumDecimalHours($normalNightHours);
            $sumHolidayHours = $this->sumDecimalHours($holidayHours);
            $sumHolidayNightHours = $this->sumDecimalHours($holidayNightHours);
            
            $totalHours = $this->sumDecimalHours([$sumNormalHours, $sumNormalNightHours, $sumHolidayHours, $sumHolidayNightHours]);

            Log::info("Date: $date", [
                'raw_normal_hours' => $normalHours,
                'sum_normal_hours' => $this->formatDecimalHours($sumNormalHours),
                'total_hours' => $this->formatDecimalHours($totalHours),
            ]);

            return [
                'date' => $date,
                'normal_hours' => $this->formatDecimalHours($sumNormalHours),
                'normal_night_hours' => $this->formatDecimalHours($sumNormalNightHours),
                'holiday_hours' => $this->formatDecimalHours($sumHolidayHours),
                'holiday_night_hours' => $this->formatDecimalHours($sumHolidayNightHours),
                'total_hours' => $this->formatDecimalHours($totalHours),
                'events' => $group->pluck('event'),
            ];
        })->sortKeys();

        // Pass the guide, grouped event salaries, and date range to the view
        $tourGuide = TourGuide::find($request->input('guide_id'));

        return view('reports.guide-time-report', compact('tourGuide', 'groupedEventSalaries', 'startDate', 'endDate'));
    }

    public function getGuideTimeReportChristmas(Request $request)
    {
        $request->validate([
            'guide_id' => 'required|exists:tour_guides,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        // Modify these lines to include the full end date
        $startDate = Carbon::parse($request->input('start_date'))->startOfDay();
        $endDate = Carbon::parse($request->input('end_date'))->endOfDay();

        
    // Define special dates and rates with extra flag
    $specialDates = [
        ['start' => '2024-12-24 06:00', 'end' => '2024-12-24 20:00', 'bonus' => 3, 'extras' => false],
        ['start' => '2024-12-24 20:00', 'end' => '2024-12-25 06:00', 'bonus' => 5, 'extras' => false],
        ['start' => '2024-12-25 06:00', 'end' => '2024-12-25 20:00', 'bonus' => 3, 'extras' => true],
        ['start' => '2024-12-25 20:00', 'end' => '2024-12-26 06:00', 'bonus' => 5, 'extras' => true],
        ['start' => '2024-12-31 06:00', 'end' => '2024-12-31 20:00', 'bonus' => 3, 'extras' => false],
        ['start' => '2024-12-31 20:00', 'end' => '2025-01-01 06:00', 'bonus' => 5, 'extras' => false],
        ['start' => '2025-01-01 06:00', 'end' => '2025-01-01 20:00', 'bonus' => 3, 'extras' => true],
        ['start' => '2025-01-01 20:00', 'end' => '2025-01-02 06:00', 'bonus' => 3, 'extras' => true],
    ];

    // Fetch event salaries
    $eventSalaries = EventSalary::where('guideId', $request->guide_id)
        ->whereBetween('guide_start_time', [$startDate, $endDate])
        ->whereIn('approval_status', [1, 2])
        ->whereHas('event', function($query) {
            $query->whereRaw("event_id NOT LIKE '%manual-missing-hours%'");
        })
        ->with('event')
        ->get();


    // Add the helper function
    $sumTimeFormat = function($times) {
        $totalMinutes = 0;
        foreach ($times as $time) {
            if (empty($time)) continue;
            
            $parts = explode('.', $time);
            $hours = intval($parts[0]);
            $minutes = isset($parts[1]) ? intval($parts[1]) : 0;
            
            $totalMinutes += ($hours * 60) + $minutes;
        }
        
        $hours = floor($totalMinutes / 60);
        $minutes = $totalMinutes % 60;
        return sprintf("%d.%02d", $hours, $minutes);
    };

    // Group event salaries by date
    $groupedEventSalaries = $eventSalaries->groupBy(function ($item) {
        return Carbon::parse($item->guide_start_time)->format('Y-m-d');
    })->map(function ($group) use ($specialDates) {
        $startTime = $group->first()->guide_start_time;
        $date = $startTime instanceof Carbon ? $startTime->format('Y-m-d') : Carbon::parse($startTime)->format('Y-m-d');
        
        // Calculate regular hours
        $normalHours = $group->pluck('normal_hours')->toArray();
        $normalNightHours = $group->pluck('normal_night_hours')->toArray();
        $holidayHours = $group->pluck('holiday_hours')->toArray();
        $holidayNightHours = $group->pluck('holiday_night_hours')->toArray();
        
        $sumNormalHours = $this->sumDecimalHours($normalHours);
        $sumNormalNightHours = $this->sumDecimalHours($normalNightHours);
        $sumHolidayHours = $this->sumDecimalHours($holidayHours);
        $sumHolidayNightHours = $this->sumDecimalHours($holidayNightHours);
        
        $totalHours = $this->sumDecimalHours([$sumNormalHours, $sumNormalNightHours, $sumHolidayHours, $sumHolidayNightHours]);

        // Calculate bonus hours for each shift with separate extras tracking
        $bonusHours = $group->map(function ($shift) use ($specialDates) {
            $shiftStart = Carbon::parse($shift->guide_start_time);
            $shiftEnd = Carbon::parse($shift->guide_end_time);
            
            // Regular bonus minutes (will include both regular and extras)
            $regularBonus3Minutes = 0;
            $regularBonus5Minutes = 0;
            
            // Extra bonus minutes (only for special days with extras)
            $extrasBonus3Minutes = 0;
            $extrasBonus5Minutes = 0;

            foreach ($specialDates as $period) {
                $periodStart = Carbon::parse($period['start']);
                $periodEnd = Carbon::parse($period['end']);

                // Check if the shift overlaps with the special period
                if ($shiftStart->lt($periodEnd) && $shiftEnd->gt($periodStart)) {
                    // Calculate the overlap
                    $overlapStart = max($shiftStart->timestamp, $periodStart->timestamp);
                    $overlapEnd = min($shiftEnd->timestamp, $periodEnd->timestamp);
                    $overlapMinutes = ($overlapEnd - $overlapStart) / 60;

                    // Add to regular bonuses for ALL special days
                    if ($period['bonus'] == 3) {
                        $regularBonus3Minutes += $overlapMinutes;
                    } else {
                        $regularBonus5Minutes += $overlapMinutes;
                    }

                    // Additionally add to extras bonuses if it's a special day with extras
                    if ($period['extras']) {
                        if ($period['bonus'] == 3) {
                            $extrasBonus3Minutes += $overlapMinutes;
                        } else {
                            $extrasBonus5Minutes += $overlapMinutes;
                        }
                    }
                }
            }

            // Convert all minute values to HH.MM format
            return [
                'regularBonus3Hours' => $this->minutesToHoursFormat($regularBonus3Minutes),
                'regularBonus5Hours' => $this->minutesToHoursFormat($regularBonus5Minutes),
                'extrasBonus3Hours' => $this->minutesToHoursFormat($extrasBonus3Minutes),
                'extrasBonus5Hours' => $this->minutesToHoursFormat($extrasBonus5Minutes),
                'event' => $shift->event
            ];
        });

        return [
            'date' => $date,
            'normal_hours' => $this->formatDecimalHours($sumNormalHours),
            'normal_night_hours' => $this->formatDecimalHours($sumNormalNightHours),
            'holiday_hours' => $this->formatDecimalHours($sumHolidayHours),
            'holiday_night_hours' => $this->formatDecimalHours($sumHolidayNightHours),
            'total_hours' => $this->formatDecimalHours($totalHours),
            'regularBonus3Hours' => $this->sumHoursMinutes($bonusHours->pluck('regularBonus3Hours')->toArray()),
            'regularBonus5Hours' => $this->sumHoursMinutes($bonusHours->pluck('regularBonus5Hours')->toArray()),
            'extrasBonus3Hours' => $this->sumHoursMinutes($bonusHours->pluck('extrasBonus3Hours')->toArray()),
            'extrasBonus5Hours' => $this->sumHoursMinutes($bonusHours->pluck('extrasBonus5Hours')->toArray()),
            'events' => $bonusHours->pluck('event'),
        ];
    })->sortKeys();

    $tourGuide = TourGuide::find($request->guide_id);
    return view('reports.guide-wise-time-report-christmas', compact('tourGuide', 'groupedEventSalaries', 'startDate', 'endDate', 'sumTimeFormat'));
}

    private function sumDecimalHours(array $hours)
    {
        $totalMinutes = 0;
        foreach ($hours as $hour) {
            if (empty($hour)) continue;
            
            // Handle decimal format (e.g., 6.40 means 6 hours and 40 minutes)
            if (strpos($hour, '.') !== false) {
                list($h, $m) = explode('.', $hour);
                // Convert the decimal part to actual minutes
                // If it's 40, treat it as 40 minutes, not 4 minutes
                $minutes = (intval($h) * 60) + $this->convertDecimalToMinutes($m);
                
                Log::info("Processing hour value: $hour", [
                    'hours' => $h,
                    'decimal_part' => $m,
                    'converted_minutes' => $this->convertDecimalToMinutes($m),
                    'total_minutes' => $minutes
                ]);
            }
            // Handle HH:MM format
            else if (strpos($hour, ':') !== false) {
                list($h, $m) = explode(':', $hour);
                $minutes = (intval($h) * 60) + intval($m);
            }
            // Handle whole numbers
            else {
                $minutes = intval($hour) * 60;
            }
            
            $totalMinutes += $minutes;
        }

        // Convert total minutes back to decimal hours format (HH.MM)
        $hours = floor($totalMinutes / 60);
        $minutes = $totalMinutes % 60;
        
        return sprintf("%d.%02d", $hours, $minutes);
    }

    private function convertDecimalToMinutes($decimal)
    {
        // If the decimal part is 30, it means 30 minutes
        // If it's 40, it means 40 minutes, etc.
        return intval(str_pad($decimal, 2, '0'));
    }

    private function formatDecimalHours($decimalHours)
    {
        // If the input is in HH:MM format
        if (is_string($decimalHours) && strpos($decimalHours, ':') !== false) {
            list($hours, $minutes) = explode(':', $decimalHours);
            return sprintf("%d.%02d", (int)$hours, (int)$minutes);
        }
        
        // If it's already a number or decimal string
        $hours = floor(floatval($decimalHours));
        $minutes = round(($decimalHours - $hours) * 100);
        return sprintf("%d.%02d", $hours, $minutes);
    }

    public function pendingApprovalsDateUpdate(Request $request)
    {
        $untilDate = $request->input('until_date'); 
        
        // Update the updatedate table
        DB::table('updatedate')
            ->where('id', 1)
            ->update(['until_date_pending_approvals' => $untilDate]);

        return redirect()->route('admin.pending-approvals')->with('success', 'Update date has been set successfully.');
    }

    // Display pending approvals
    public function pendingApprovals()
    {
        // For admin, show all pending approvals
        if (auth()->user()->role === 'admin') {
            $pendingApprovals = EventSalary::where('is_guide_updated', 1)
                ->whereIn('approval_status', [0, 3])
                ->with('event', 'tourGuide')
                ->get();
        }
        // For user with id 6, show all pending approvals except those assigned to managers
        else if (auth()->user()->role == 'hr-assistant') {
            // Get all guide IDs assigned to managers
            $managerAssignedGuideIds = ManagerGuideAssignment::pluck('guide_id')->toArray();

            $pendingApprovals = EventSalary::where('is_guide_updated', 1)
                ->whereIn('approval_status', [0, 3])
                ->whereNotIn('guideId', $managerAssignedGuideIds)
                ->with('event', 'tourGuide')
                ->get();
        }
        // For manager, show only pending approvals of assigned guides
        else if (auth()->user()->role === 'team-lead') {
            $assignedGuideIds = ManagerGuideAssignment::where('manager_id', auth()->id())
                ->pluck('guide_id')
                ->toArray();

            $pendingApprovals = EventSalary::where('is_guide_updated', 1)
                ->whereIn('approval_status', [0, 3])
                ->whereIn('guideId', $assignedGuideIds)
                ->with('event', 'tourGuide')
                ->get();
        }

        return view('reports.pending-approvals', compact('pendingApprovals'));
    }
    // Display 16 plus pending approvals
    public function pending16plusApprovals()
    {
        // For admin, show all pending approvals
        if (auth()->user()->role === 'admin') {
            $pendingApprovals = EventSalary::whereIn('approval_status', [5])
                ->with('event', 'tourGuide')
                ->get();

        }
        // For user with id 6, show all pending approvals except those assigned to managers
        else if (auth()->user()->role == 'hr-assistant') {
            // Get all guide IDs assigned to managers
            $managerAssignedGuideIds = ManagerGuideAssignment::pluck('guide_id')->toArray();

            $pendingApprovals = EventSalary::where('is_guide_updated', 1)
                ->whereIn('approval_status', [5])
                ->whereNotIn('guideId', $managerAssignedGuideIds)
                ->with('event', 'tourGuide')
                ->get();
        }
        // For manager, show only pending approvals of assigned guides
        else if (auth()->user()->role === 'team-lead') {
            $assignedGuideIds = ManagerGuideAssignment::where('manager_id', auth()->id())
                ->pluck('guide_id')
                ->toArray();

            $pendingApprovals = EventSalary::where('is_guide_updated', 1)
                ->whereIn('approval_status', [0, 3])
                ->whereIn('guideId', $assignedGuideIds)
                ->with('event', 'tourGuide')
                ->get();
        }

        return view('reports.pending-approvals', compact('pendingApprovals'));
    }

    // Approve the guide's updated hours with an optional comment
    public function approve(Request $request, $id)
    {
        $eventSalary = EventSalary::findOrFail($id);
        $salaryController = new SalaryController();
        
        $startDateTime = Carbon::parse($eventSalary->guide_start_time);
        $endDateTime = Carbon::parse($eventSalary->guide_end_time);

        // Check if the work spans across midnight
        if ($startDateTime->format('Y-m-d') !== $endDateTime->format('Y-m-d')) {
            // Split the shift at midnight
            $midnightDateTime = $startDateTime->copy()->endOfDay();

            // First part of the shift (until midnight, including the last second)
            $firstShift = new EventSalary();
            $firstShift->guideId = $eventSalary->guideId;
            $firstShift->eventId = $eventSalary->eventId;
            $firstShift->guide_start_time = $startDateTime;
            $firstShift->guide_end_time = $midnightDateTime->copy()->addSecond();
            $this->calculateHours($firstShift);
          
            $firstShift->approval_comment = $request->input('approval_comment');
            
            if($firstShift->normal_hours > 15 && Auth()->user()->role != 'admin'){
                $firstShift->approval_status = 5; // Needs admin approval
            } else {
                $firstShift->approval_status = 1; // Approved
            }
            
            $firstShift->save();

            // Second part of the shift (from midnight)
            $secondShift = new EventSalary();
            $secondShift->guideId = $eventSalary->guideId;
            $secondShift->eventId = $eventSalary->eventId;
            $secondShift->guide_start_time = $midnightDateTime->copy()->addSecond();
            $secondShift->guide_end_time = $endDateTime;
            $this->calculateHours($secondShift);
           
                         
            if($secondShift->normal_hours > 15 && Auth()->user()->role != 'admin'){
                $secondShift->approval_status = 5; // Needs admin approval
            } else {
                $secondShift->approval_status = 1; // Approved
            }
            

            $secondShift->approval_comment = $request->input('approval_comment');
            $secondShift->save();

            // Delete the original entry
            $eventSalary->delete();
        } else {
            // If the shift doesn't span across midnight, process as before
            $this->calculateHours($eventSalary);

            if($eventSalary->normal_hours > 15 && Auth()->user()->role != 'admin'){
                $eventSalary->approval_status = 5; // Needs admin approval
            } else {
                $eventSalary->approval_status = 1; // Approved
            }
            $eventSalary->approval_comment = $request->input('approval_comment');
            $eventSalary->save();
        }

        // Update the is_edited column in the events table
        $event = Event::findOrFail($eventSalary->eventId);
        $event->is_edited = 1;
        $event->save();

        return redirect()->back()->with('success', 'Guide hours approved and calculated successfully. Event marked as edited.');
    }

    // Reject the guide's updated hours with an optional comment
    public function reject(Request $request, $id)
    {
        $eventSalary = EventSalary::findOrFail($id);
        $salaryController = new SalaryController();
        
        $startDateTime = Carbon::parse($eventSalary->guide_start_time);
        $endDateTime = Carbon::parse($eventSalary->guide_end_time);

        // Check if the work spans across midnight
        if ($startDateTime->format('Y-m-d') !== $endDateTime->format('Y-m-d')) {
            // Split the shift at midnight
            $midnightDateTime = $startDateTime->copy()->endOfDay();

            // First part of the shift (until midnight, including the last second)
            $firstShift = new EventSalary();
            $firstShift->guideId = $eventSalary->guideId;
            $firstShift->eventId = $eventSalary->eventId;
            $firstShift->guide_start_time = $startDateTime;
            $firstShift->guide_end_time = $midnightDateTime->copy()->addSecond();
            $this->calculateHours($firstShift);
            $firstShift->approval_status = 4; // Reject
            $firstShift->approval_comment = $request->input('approval_comment');
            $firstShift->save();

            // Second part of the shift (from midnight)
            $secondShift = new EventSalary();
            $secondShift->guideId = $eventSalary->guideId;
            $secondShift->eventId = $eventSalary->eventId;
            $secondShift->guide_start_time = $midnightDateTime->copy()->addSecond();
            $secondShift->guide_end_time = $endDateTime;
            $this->calculateHours($secondShift);
            $secondShift->approval_status = 4; // Reject
            $secondShift->approval_comment = $request->input('approval_comment');
            $secondShift->save();

            // Delete the original entry
            $eventSalary->delete();
        } else {
            // If the shift doesn't span across midnight, process as before
            $this->calculateHours($eventSalary);
            $eventSalary->approval_status = 4; // Reject
            $eventSalary->approval_comment = $request->input('approval_comment');
            $eventSalary->save();
        }

        // Update the is_edited column in the events table
        $event = Event::findOrFail($eventSalary->eventId);
        $event->is_edited = 1;
        $event->save();

        return redirect()->back()->with('success', 'Guide hours Rejected successfully. Event marked as edited.');
    }

    // Reject the guide's updated hours with an optional comment and new start/end times
    public function adjust(Request $request, $id)
    {
         $request->validate([
            'guide_start_time' => 'required|date_format:Y-m-d H:i',
            'guide_end_time' => 'required|date_format:Y-m-d H:i|after:guide_start_time',
        ]);

        $eventSalary = EventSalary::findOrFail($id);
        
        $startDateTime = Carbon::parse($request->input('guide_start_time'));
        $endDateTime = Carbon::parse($request->input('guide_end_time'));

        // Check if the work spans across midnight
        if ($startDateTime->format('Y-m-d') !== $endDateTime->format('Y-m-d')) {
            // Split the shift at midnight
            $midnightDateTime = $startDateTime->copy()->endOfDay();

            // First part of the shift (until midnight, including the last second)
            $firstShift = new EventSalary();
            $firstShift->guideId = $eventSalary->guideId;
            $firstShift->eventId = $eventSalary->eventId;
            $firstShift->guide_start_time = $startDateTime;
            $firstShift->guide_end_time = $midnightDateTime->copy()->addSecond();
            $this->calculateHours($firstShift);

                    
            if($firstShift->normal_hours > 15 && Auth()->user()->role != 'admin'){
                $firstShift->approval_status = 5; // Needs admin approval
            } else {
                $firstShift->approval_status = 2; // Adjusted
            }

            $firstShift->approval_comment = $request->input('approval_comment');
            $firstShift->save();

            // Second part of the shift (from midnight)
            $secondShift = new EventSalary();
            $secondShift->guideId = $eventSalary->guideId;
            $secondShift->eventId = $eventSalary->eventId;
            $secondShift->guide_start_time = $midnightDateTime->copy()->addSecond();
            $secondShift->guide_end_time = $endDateTime;
            $this->calculateHours($secondShift);
          
                         
            if($secondShift->normal_hours > 15 && Auth()->user()->role != 'admin'){
                $secondShift->approval_status = 5; // Needs admin approval
            } else {
                $secondShift->approval_status = 2; // Adjusted
            }

            $secondShift->approval_comment = $request->input('approval_comment');
            $secondShift->save();

            // Delete the original entry
            $eventSalary->delete();
        } else {
            // If the shift doesn't span across midnight, process as before
            $eventSalary->guide_start_time = $startDateTime;
            $eventSalary->guide_end_time = $endDateTime;
            $this->calculateHours($eventSalary);
            $eventSalary->approval_status = 2; // Rejected
            $eventSalary->approval_comment = $request->input('approval_comment');
            $eventSalary->save();
        }

        // Update the is_edited column in the events table
        $event = Event::findOrFail($eventSalary->eventId);
        $event->is_edited = 1;
        $event->save();

        return redirect()->back()->with('success', 'Guide hours Adjusted and recalculated successfully. Event marked as edited.');
    }
    // Modify the guide's updated hours with an optional comment and new start/end times
    public function modify(Request $request, $id)
    {
         $request->validate([
            'guide_start_time' => 'required|date_format:Y-m-d H:i',
            'guide_end_time' => 'required|date_format:Y-m-d H:i|after:guide_start_time',
        ]);

        $eventSalary = EventSalary::findOrFail($id);
        
        $startDateTime = Carbon::parse($request->input('guide_start_time'));
        $endDateTime = Carbon::parse($request->input('guide_end_time'));

        // Check if the work spans across midnight
        if ($startDateTime->format('Y-m-d') !== $endDateTime->format('Y-m-d')) {
            // Split the shift at midnight
            $midnightDateTime = $startDateTime->copy()->endOfDay();

            // First part of the shift (until midnight, including the last second)
            $firstShift = new EventSalary();
            $firstShift->guideId = $eventSalary->guideId;
            $firstShift->eventId = $eventSalary->eventId;
            $firstShift->guide_start_time = $startDateTime;
            $firstShift->guide_end_time = $midnightDateTime->copy()->addSecond();
            $this->calculateHours($firstShift);
            $firstShift->approval_status = 1; // Rejected
            $firstShift->approval_comment = $request->input('approval_comment');
            $firstShift->save();

            // Second part of the shift (from midnight)
            $secondShift = new EventSalary();
            $secondShift->guideId = $eventSalary->guideId;
            $secondShift->eventId = $eventSalary->eventId;
            $secondShift->guide_start_time = $midnightDateTime->copy()->addSecond();
            $secondShift->guide_end_time = $endDateTime;
            $this->calculateHours($secondShift);
            $secondShift->approval_status = 1; // Approved
            $secondShift->approval_comment = $request->input('approval_comment');
            $secondShift->save();

            // Delete the original entry
            $eventSalary->delete();
        } else {
            // If the shift doesn't span across midnight, process as before
            $eventSalary->guide_start_time = $startDateTime;
            $eventSalary->guide_end_time = $endDateTime;
            $this->calculateHours($eventSalary);
            $eventSalary->approval_status = 1; // Approved
            $eventSalary->approval_comment = $request->input('approval_comment');
            $eventSalary->save();
        }

        // Update the is_edited column in the events table
        $event = Event::findOrFail($eventSalary->eventId);
        $event->is_edited = 1;
        $event->save();

        return redirect()->back()->with('success', 'Guide hours rejected, updated, and recalculated successfully. Event marked as edited.');
    }

    // Request more information with an optional comment
    public function needsInfo(Request $request, $id)
    {
        $eventSalary = EventSalary::findOrFail($id);
        $eventSalary->approval_status = 3; // Needs more info
        $eventSalary->approval_comment = $request->input('approval_comment');
        $eventSalary->save();

        return redirect()->back()->with('success', 'Guide hours marked as needing more information.');
    }

   
    private function calculateHours(EventSalary $eventSalary)
    {
        $startDateTime = Carbon::parse($eventSalary->guide_start_time);
        $endDateTime = Carbon::parse($eventSalary->guide_end_time);

        Log::info("Calculating hours for event {$eventSalary->eventId}, guide {$eventSalary->guideId}");
        Log::info("Start time: {$startDateTime}, End time: {$endDateTime}");

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
        $eventSalary->normal_hours = $this->formatHoursMinutes(
            $normalHours + $nightHours + $holidayHours + $holidayNightHours,
            $normalMinutes + $nightMinutes + $holidayMinutes + $holidayNightMinutes
        );
        $eventSalary->normal_night_hours = $this->formatHoursMinutes(
            $nightHours + $holidayNightHours,
            $nightMinutes + $holidayNightMinutes
        );
        $eventSalary->holiday_hours = $this->formatHoursMinutes(
            $holidayHours + $holidayNightHours,
            $holidayMinutes + $holidayNightMinutes
        );
        $eventSalary->holiday_night_hours = $this->formatHoursMinutes(
            $holidayNightHours,
            $holidayNightMinutes
        );
    
        
    
    }

    private function minutesToDecimalHours($minutes)
    {
        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;
        return number_format($hours + ($remainingMinutes / 100), 2, '.', '');
    }

    private function isHoliday(Carbon $date)
    {
        return $date->isSunday() || Holiday::where('holiday_date', $date->format('Y-m-d'))->exists();
    }


    public function createNewTour()
    {
        $guides = TourGuide::where('is_hidden', false)->orderBy('name', 'asc')->get();
        return view('tours.create-a-new-tour', compact('guides'));
    }

    public function storeManual(Request $request)
    {
        $request->validate([
            'tourName' => 'required|string|max:255',
            'guides' => 'required|array|min:1',
            'guides.*.name' => 'required|exists:tour_guides,id',
            'guides.*.startTime' => 'nullable|date_format:Y-m-d H:i',
            'guides.*.endTime' => 'nullable|date_format:Y-m-d H:i|after:guides.*.startTime',
        ]);

        DB::beginTransaction();

        try {
              // Generate a unique event_id
            $uniqueId = 'manual-' . substr(md5(uniqid(rand(), true)), 0, 8);

            // Get the first valid start time from guides array
            $eventStartTime = collect($request->guides)
                ->where('startTime', '!=', '')
                ->pluck('startTime')
                ->first();

            // Create the event
            $event = Event::create([
                'name' => $request->tourName,
                'event_id' => $uniqueId,
                'start_time' => $eventStartTime,
                'end_time' => max(array_column($request->guides, 'endTime')),
                'is_edited' => 1,
                'status' => 1,
                // Add other necessary fields
            ]);

            // Create event salaries for each guide
            foreach ($request->guides as $guideData) {
                if($guideData['startTime'] == '' || $guideData['endTime'] == ''){
                    $eventSalary = EventSalary::create([
                        'eventId' => $event->id,
                        'guideId' => $guideData['name'],
                        'guide_start_time' => null,
                        'guide_end_time' => null,
                        'approval_status' => 0,
                        'is_chore' => 1,
                        // Calculate and add other necessary fields like total_salary, hours, etc.
                    ]);
                } else {
                    $eventSalary = EventSalary::create([
                        'eventId' => $event->id,
                        'guideId' => $guideData['name'],
                        'guide_start_time' => $guideData['startTime'],
                        'guide_end_time' => $guideData['endTime'],
                        'approval_status' => 1,
                        // Calculate and add other necessary fields like total_salary, hours, etc.
                    ]);
                }
              

                // Calculate hours for the event salary
                $this->calculateHours($eventSalary);
                $eventSalary->save();
            }

            DB::commit();
            return redirect()->back()->with('success', 'Manual tour created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to create manual tour. ' . $e->getMessage())->withInput();
        }


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

    public function rankingForHoursBusDrivers(Request $request){

        //we only need the drivers who are bus drivers
        $juhas_guide_id = 74;
        $busDriversId = ManagerGuideAssignment::where('manager_id', $juhas_guide_id)->pluck('guide_id')->toArray();

         // Get the earliest pending approval date
         $earliestPendingDate = EventSalary::where('is_guide_updated', 1)
         ->where('approval_status', 0)
         ->min('guide_start_time');

     // Initialize an array to store working hours for all 5 periods
     $guideWorkingHours = [];

     // Use the date from the request if available, otherwise use the given date
     $currentweek = $request->input('start_date', '2025-02-17');
     
     $fixedStartDate = Carbon::createFromFormat('Y-m-d', $currentweek)->startOfWeek();

     // Loop through each of the 5 3-week periods
     for ($period = 1; $period <= 6; $period++) {
         // Define the start and end dates for the current 3-week period
         $startDate = $fixedStartDate->copy()->addWeeks(($period - 1) * 3);
         $endDate = $fixedStartDate->copy()->addWeeks($period * 3 - 1)->endOfWeek();

         // Log the start and end dates for the current period
         $eventSalaries = EventSalary::whereIn('guideId', $busDriversId)
         ->whereHas('event', function ($query) use ($startDate, $endDate) {
             $query->whereBetween('start_time', [$startDate, $endDate])
                   ->where('event_id', 'not like', 'manual-missing-hours%');
         })
         
         ->whereIn('approval_status', [1, 2])  // Only get approved (1) or adjusted (2) entries
         ->get();

         // Group by guide and calculate the total working hours for this period
         $currentPeriodWorkingHours = $eventSalaries->groupBy('guideId')->map(function ($salaries) {
             $totalMinutes = $salaries->sum(function ($salary) {
                 $parts = explode('.', (string)$salary->normal_hours);
                 $hours = intval($parts[0]);
                 $minutes = isset($parts[1]) ? intval(substr($parts[1] . '00', 0, 2)) : 0;
                 return $hours * 60 + $minutes;
             });

             $totalHours = floor($totalMinutes / 60);
             $remainingMinutes = $totalMinutes % 60;

             return sprintf('%d.%02d', $totalHours, $remainingMinutes);
         });


         // Add the working hours for the current period to the guideWorkingHours array
         foreach ($currentPeriodWorkingHours as $guideId => $hours) {
             if (!isset($guideWorkingHours[$guideId])) {
                 $guideWorkingHours[$guideId] = [];
             }
             // Store the total hours for this 3-week period
             $guideWorkingHours[$guideId]["period{$period}_hours"] = $hours;

             // Log the guide's working hours for this period
         }
     }

     // Fetch the guide details and attach the working hours for all periods
     $guides = TourGuide::whereIn('id', array_keys($guideWorkingHours))->get()->map(function ($guide) use ($guideWorkingHours) {
         $guide->working_hours = $guideWorkingHours[$guide->id];
         return $guide;
     });

     // Sort guides by total hours worked across all periods in descending order
     $guides = $guides->sortByDesc(function ($guide) {
         $totalHours = array_sum($guide->working_hours); // Sum all period hours
         return $totalHours;
     });
     Log::info($guides);
     // Define the number of weeks (15 weeks in total since 5 periods of 3 weeks)
     $updatedate =  DB::table('updatedate')
         ->where('id', 1)->first();

     // Render the view and pass the required variables
     return view('reports.ranking-for-hours-bus-drivers', compact('guides', 'updatedate', 'currentweek', 'earliestPendingDate'))
         ->with('dateFormat', 'd.m.Y');
    }

    public function calculateWorkingHours(Request $request)
    {
        // Get the earliest pending approval date
        $earliestPendingDate = EventSalary::where('is_guide_updated', 1)
            ->where('approval_status', 0)
            ->min('guide_start_time');

        // Initialize an array to store working hours for all 5 periods
        $guideWorkingHours = [];

        // Use the date from the request if available, otherwise use the given date
        $currentweek = $request->input('start_date', '2025-06-23');
        
        $fixedStartDate = Carbon::createFromFormat('Y-m-d', $currentweek)->startOfWeek();

        // Loop through each of the 5 3-week periods
        for ($period = 1; $period <= 6; $period++) {
            // Define the start and end dates for the current 3-week period
            $startDate = $fixedStartDate->copy()->addWeeks(($period - 1) * 3);
            $endDate = $fixedStartDate->copy()->addWeeks($period * 3 - 1)->endOfWeek();

            // Log the start and end dates for the current period
            $eventSalaries = EventSalary::whereHas('event', function ($query) use ($startDate, $endDate) {
                $query->whereBetween('start_time', [$startDate, $endDate])
                      ->where('event_id', 'not like', 'manual-missing-hours%');
            })
            ->whereIn('approval_status', [1, 2])  // Only get approved (1) or adjusted (2) entries
            ->get();

            // Group by guide and calculate the total working hours for this period
            $currentPeriodWorkingHours = $eventSalaries->groupBy('guideId')->map(function ($salaries) {
                $totalMinutes = $salaries->sum(function ($salary) {
                    $parts = explode('.', (string)$salary->normal_hours);
                    $hours = intval($parts[0]);
                    $minutes = isset($parts[1]) ? intval(substr($parts[1] . '00', 0, 2)) : 0;
                    return $hours * 60 + $minutes;
                });

                $totalHours = floor($totalMinutes / 60);
                $remainingMinutes = $totalMinutes % 60;

                return sprintf('%d.%02d', $totalHours, $remainingMinutes);
            });


            // Add the working hours for the current period to the guideWorkingHours array
            foreach ($currentPeriodWorkingHours as $guideId => $hours) {
                if (!isset($guideWorkingHours[$guideId])) {
                    $guideWorkingHours[$guideId] = [];
                }
                // Store the total hours for this 3-week period
                $guideWorkingHours[$guideId]["period{$period}_hours"] = $hours;

            }
        }

        // Fetch the guide details and attach the working hours for all periods
        $guides = TourGuide::whereIn('id', array_keys($guideWorkingHours))->get()->map(function ($guide) use ($guideWorkingHours) {
            $guide->working_hours = $guideWorkingHours[$guide->id];
            return $guide;
        });

        // Sort guides by total hours worked across all periods in descending order
        $guides = $guides->sortByDesc(function ($guide) {
            $totalHours = array_sum($guide->working_hours); // Sum all period hours
            return $totalHours;
        });
        // Define the number of weeks (15 weeks in total since 5 periods of 3 weeks)
        $updatedate =  DB::table('updatedate')
            ->where('id', 1)->first();


        

        // Render the view and pass the required variables
        return view('reports.working-hours', compact('guides', 'updatedate', 'currentweek', 'earliestPendingDate'))
            ->with('dateFormat', 'd.m.Y');
    }


    public function calculateWorkingHoursSupervisors(Request $request)
{
    // Set default start date to 2025-02-17
    $currentweek = $request->input('start_date', '2025-06-23');
    $startDate = Carbon::createFromFormat('Y-m-d', $currentweek)->startOfWeek();
    
    // Initialize periods array starting from currentweek
    $periods = [];
    for ($i = 0; $i < 6; $i++) {
        $periodStart = $startDate->copy()->addWeeks($i * 3);
        $periodEnd = $periodStart->copy()->addWeeks(3)->subDay();
        $periods[$i + 1] = ['start' => $periodStart, 'end' => $periodEnd];
    }

    // Get supervisor's departments as array
    $supervisor = Supervisors::where('user_id', auth()->id())->first();
    $supervisorDepartments = explode(', ', $supervisor->department);

    // Get staff that belong to any of the supervisor's departments
    $staffIds = StaffUser::where(function($query) use ($supervisorDepartments) {
        foreach ($supervisorDepartments as $department) {
            // Use LIKE queries to find staff with this department
            // This handles both exact matches and cases where the department is part of a list
            $query->orWhere('department', 'LIKE', $department)
                  ->orWhere('department', 'LIKE', $department . ',%')
                  ->orWhere('department', 'LIKE', '%, ' . $department)
                  ->orWhere('department', 'LIKE', '%, ' . $department . ',%');
        }
    })->pluck('id');

    $staffWorkingHours = [];

    foreach ($periods as $period => $dates) {
        // Process regular hours from StaffMonthlyHours
        $monthlyHours = StaffMonthlyHours::whereBetween('date', [$dates['start'], $dates['end']])
            ->whereIn('staff_id', $staffIds)
            ->get();

        foreach ($monthlyHours as $record) {
            try {
                $hoursData = is_array($record->hours_data) 
                    ? $record->hours_data 
                    : json_decode($record->hours_data, true);

                if (!is_array($hoursData)) {
                    \Log::info('Invalid hours_data format for record: ' . $record->id);
                    continue;
                }

                $dailyMinutes = 0;

        foreach ($hoursData as $shift) {
            // Skip ALL sick leaves regardless of status
            if (isset($shift['type']) && $shift['type'] === 'SL') {
                continue;
            }
            
            // Only process shifts with valid start and end times
            if (isset($shift['start_time']) && isset($shift['end_time']) && 
                !is_null($shift['start_time']) && !is_null($shift['end_time'])) {
                $startTime = Carbon::createFromFormat('H:i', $shift['start_time']);
                $endTime = Carbon::createFromFormat('H:i', $shift['end_time']);
                $dailyMinutes += $endTime->diffInMinutes($startTime);
            }
        }

        // Convert total minutes to decimal hours (not the flawed format you were using)
        $totalDecimalHours = $dailyMinutes / 60;

        if (!isset($staffWorkingHours[$record->staff_id])) {
            $staffWorkingHours[$record->staff_id] = [];
        }

        if (!isset($staffWorkingHours[$record->staff_id]["period{$period}_hours"])) {
            $staffWorkingHours[$record->staff_id]["period{$period}_hours"] = 0;
        }

        $staffWorkingHours[$record->staff_id]["period{$period}_hours"] += $totalDecimalHours;

            } catch (\Exception $e) {
                \Log::error('Error processing record ' . $record->id . ': ' . $e->getMessage());
                continue;
            }
        }
        
        // Process missing hours
        $missingHours = StaffMissingHours::whereBetween('date', [$dates['start'], $dates['end']])
            ->whereIn('staff_id', $staffIds)
            ->get();
            
        foreach ($missingHours as $missing) {
            try {
                // Parse start and end times
                if (strpos($missing->start_time, ':') !== false && strlen($missing->start_time) <= 5) {
                    // Format is H:i
                    $startTime = Carbon::createFromFormat('H:i', $missing->start_time);
                    $endTime = Carbon::createFromFormat('H:i', $missing->end_time);
                } else {
                    // Full datetime format
                    $startTime = Carbon::parse($missing->start_time);
                    $endTime = Carbon::parse($missing->end_time);
                }
                
                $missingMinutes = $endTime->diffInMinutes($startTime);
                $missingHours = floor($missingMinutes / 60);
                $missingRemainingMinutes = $missingMinutes % 60;
                $missingHoursFormatted = sprintf('%d.%02d', $missingHours, $missingRemainingMinutes);
                
                if (!isset($staffWorkingHours[$missing->staff_id])) {
                    $staffWorkingHours[$missing->staff_id] = [];
                }
                
                if (!isset($staffWorkingHours[$missing->staff_id]["period{$period}_hours"])) {
                    $staffWorkingHours[$missing->staff_id]["period{$period}_hours"] = 0;
                }
                
                $staffWorkingHours[$missing->staff_id]["period{$period}_hours"] += (float)$missingHoursFormatted;
                
            } catch (\Exception $e) {
                \Log::error('Error processing missing hours record ' . $missing->id . ': ' . $e->getMessage());
                continue;
            }
        }
    }

    $assignedEmployees = StaffUser::whereIn('id', array_keys($staffWorkingHours))
        ->get()
        ->map(function ($employee) use ($staffWorkingHours) {
            $employee->working_hours = $staffWorkingHours[$employee->id];
            return $employee;
        });

    $assignedEmployees = $assignedEmployees->sortByDesc(function ($employee) {
        return array_sum($employee->working_hours);
    });

    $updatedate = DB::table('updatedate')->where('id', 1)->first();
    
    return view('reports.working-hours-supervisors', compact('assignedEmployees', 'updatedate', 'startDate'))
        ->with('dateFormat', 'd.m.Y');
}


    public function calculateWorkingHoursStaff(Request $request)
{
    // Set default start date to 2025-06-23
    $currentweek = $request->input('start_date', '2025-06-23');
    $startDate = Carbon::createFromFormat('Y-m-d', $currentweek)->startOfWeek();
    
    // Initialize periods array starting from currentweek
    $periods = [];
    for ($i = 0; $i < 6; $i++) {
        $periodStart = $startDate->copy()->addWeeks($i * 3);
        $periodEnd = $periodStart->copy()->addWeeks(3)->subDay();
        $periods[$i + 1] = ['start' => $periodStart, 'end' => $periodEnd];
    }

    // Get all departments
    $allDepartments = StaffUser::select('department')
        ->whereNotNull('department')
        ->where('department', '!=', '')
        ->distinct()
        ->pluck('department')
        ->flatMap(function ($departments) {
            // Split comma-separated departments and trim whitespace
            return array_map('trim', explode(',', $departments));
        })
        ->unique()
        ->filter()
        ->sort()
        ->values();

    // Get all staff members
    $allStaffIds = StaffUser::whereNotNull('department')
        ->where('department', '!=', '')
        ->pluck('id');

    $staffWorkingHours = [];

    foreach ($periods as $period => $dates) {
        // Process regular hours from StaffMonthlyHours
        $monthlyHours = StaffMonthlyHours::whereBetween('date', [$dates['start'], $dates['end']])
            ->whereIn('staff_id', $allStaffIds)
            ->get();

        foreach ($monthlyHours as $record) {
            try {
                $hoursData = is_array($record->hours_data) 
                    ? $record->hours_data 
                    : json_decode($record->hours_data, true);

                if (!is_array($hoursData)) {
                    \Log::info('Invalid hours_data format for record: ' . $record->id);
                    continue;
                }

                $dailyMinutes = 0;

                foreach ($hoursData as $shift) {
                    // Skip ALL sick leaves regardless of status
                    if (isset($shift['type']) && $shift['type'] === 'SL') {
                        continue;
                    }
                    
                    // Only process shifts with valid start and end times
                    if (isset($shift['start_time']) && isset($shift['end_time']) && 
                        !is_null($shift['start_time']) && !is_null($shift['end_time'])) {
                        $startTime = Carbon::createFromFormat('H:i', $shift['start_time']);
                        $endTime = Carbon::createFromFormat('H:i', $shift['end_time']);
                        $dailyMinutes += $endTime->diffInMinutes($startTime);
                    }
                }

                // Convert total minutes to decimal hours
                $totalDecimalHours = $dailyMinutes / 60;

                if (!isset($staffWorkingHours[$record->staff_id])) {
                    $staffWorkingHours[$record->staff_id] = [];
                }

                if (!isset($staffWorkingHours[$record->staff_id]["period{$period}_hours"])) {
                    $staffWorkingHours[$record->staff_id]["period{$period}_hours"] = 0;
                }

                $staffWorkingHours[$record->staff_id]["period{$period}_hours"] += $totalDecimalHours;

            } catch (\Exception $e) {
                \Log::error('Error processing record ' . $record->id . ': ' . $e->getMessage());
                continue;
            }
        }
        
        // Process missing hours
        $missingHours = StaffMissingHours::whereBetween('date', [$dates['start'], $dates['end']])
            ->whereIn('staff_id', $allStaffIds)
            ->get();
            
        foreach ($missingHours as $missing) {
            try {
                // Parse start and end times
                if (strpos($missing->start_time, ':') !== false && strlen($missing->start_time) <= 5) {
                    // Format is H:i
                    $startTime = Carbon::createFromFormat('H:i', $missing->start_time);
                    $endTime = Carbon::createFromFormat('H:i', $missing->end_time);
                } else {
                    // Full datetime format
                    $startTime = Carbon::parse($missing->start_time);
                    $endTime = Carbon::parse($missing->end_time);
                }
                
                $missingMinutes = $endTime->diffInMinutes($startTime);
                $missingHours = floor($missingMinutes / 60);
                $missingRemainingMinutes = $missingMinutes % 60;
                $missingHoursFormatted = sprintf('%d.%02d', $missingHours, $missingRemainingMinutes);
                
                if (!isset($staffWorkingHours[$missing->staff_id])) {
                    $staffWorkingHours[$missing->staff_id] = [];
                }
                
                if (!isset($staffWorkingHours[$missing->staff_id]["period{$period}_hours"])) {
                    $staffWorkingHours[$missing->staff_id]["period{$period}_hours"] = 0;
                }
                
                $staffWorkingHours[$missing->staff_id]["period{$period}_hours"] += (float)$missingHoursFormatted;
                
            } catch (\Exception $e) {
                \Log::error('Error processing missing hours record ' . $missing->id . ': ' . $e->getMessage());
                continue;
            }
        }
    }

    // Get all staff members with their working hours and group by department
    $allStaff = StaffUser::whereNotNull('department')
        ->where('department', '!=', '')
        ->get()
        ->map(function ($employee) use ($staffWorkingHours) {
            $employee->working_hours = $staffWorkingHours[$employee->id] ?? [];
            
            // Calculate total hours across all periods
            $employee->total_hours = array_sum($employee->working_hours);
            
            return $employee;
        });

    // Group staff by department
    $staffByDepartment = [];
    
    foreach ($allStaff as $staff) {
        // Handle multiple departments (comma-separated)
        $staffDepartments = array_map('trim', explode(',', $staff->department));
        
        foreach ($staffDepartments as $department) {
            if (!isset($staffByDepartment[$department])) {
                $staffByDepartment[$department] = collect();
            }
            $staffByDepartment[$department]->push($staff);
        }
    }

    // Sort staff within each department by total hours (descending)
    foreach ($staffByDepartment as $department => $staff) {
        $staffByDepartment[$department] = $staff->sortByDesc('total_hours');
    }

    // Sort departments alphabetically
    ksort($staffByDepartment);

    $updatedate = DB::table('updatedate')->where('id', 1)->first();
    
    return view('reports.working-hours-staff-all', compact('staffByDepartment', 'allDepartments', 'updatedate', 'startDate'))
        ->with('dateFormat', 'd.m.Y');
}

    public function deleteWorkHours($id)
    {

        $eventSalary = EventSalary::find($id);
        $eventSalary->delete();
        return redirect()->back()->with('success', 'Work hours deleted successfully.');
    }

    // Helper function to sum hours in HH.MM format
    private function sumHoursMinutes($times) 
    {
        $totalMinutes = 0;
        foreach ($times as $time) {
            if (empty($time)) continue;
            
            $parts = explode('.', $time);
            $hours = intval($parts[0]);
            $minutes = isset($parts[1]) ? intval($parts[1]) : 0;
            $totalMinutes += ($hours * 60) + $minutes;
        }
        
        $hours = floor($totalMinutes / 60);
        $minutes = $totalMinutes % 60;
        
        return sprintf("%d.%02d", $hours, $minutes);
    }

    // Helper method to convert minutes to HH.MM format
    private function minutesToHoursFormat($minutes)
    {
        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;
        return sprintf("%d.%02d", $hours, $remainingMinutes);
    }

    private function sumTimeFormat($times)
    {
        $totalMinutes = 0;
        
        foreach ($times as $time) {
            if (empty($time)) continue;
            
            $parts = explode('.', $time);
            $hours = intval($parts[0]);
            $minutes = isset($parts[1]) ? intval($parts[1]) : 0;
            
            // Convert hours to minutes and add the additional minutes
            $totalMinutes += ($hours * 60) + $minutes;
        }
        
        // Convert back to hours and minutes
        $hours = floor($totalMinutes / 60);
        $minutes = $totalMinutes % 60;
        
        // Format as HH.MM
        return sprintf("%d.%02d", $hours, $minutes);
    }
}
