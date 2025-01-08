<?php

namespace App\Http\Controllers;

use App\Models\StaffHoursDetails;
use App\Models\StaffMonthlyHours;
use App\Models\StaffUser;
use App\Models\User;
use App\Models\Holiday;
use App\Models\Supervisors;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Log;

class SupervisorController extends Controller
{
    public function enterWorkingHours(Request $request)
    {
        // Get the selected week start date from request, or use current week if not provided
        $selectedDate = $request->input('week', Carbon::now()->format('Y-m-d'));
        $weekStart = Carbon::parse($selectedDate)->startOfWeek();
        $weekEnd = Carbon::parse($selectedDate)->endOfWeek();

        $dates = CarbonPeriod::create($weekStart, $weekEnd);
        
        
        //if supervisors, get display_midnight_phone from supervisors table
        if (Auth::user()->role == 'supervisor') {
            $staffMembers = StaffUser::where('supervisor', Auth::user()->id)->get();
            $displayMidnightPhone = Supervisors::where('user_id', Auth::user()->id)->first()->display_midnight_phone;
        } else {

            $supervisorId = StaffUser::where('id', Auth::user()->id)->first()->supervisor;
            $staffMembers = StaffUser::where('supervisor', $supervisorId)->get();
            $displayMidnightPhone = Supervisors::where('user_id', $supervisorId)->first()->display_midnight_phone;
        }


        $staffHours = [];
        foreach ($staffMembers as $staff) {
            $hours = StaffMonthlyHours::where('staff_id', $staff->id)
                ->whereBetween('date', [$weekStart, $weekEnd])
                ->get()
                ->keyBy(function ($item) {
                    return $item->date->format('Y-m-d');
                });

            $staffHours[$staff->id] = $hours->map(function ($item) {
                return $item->hours_data;
            })->toArray();
        }

        $receptionData = [];
        $midnightPhoneData = [];
        $staffHoursDetails = StaffHoursDetails::whereBetween('date', [$weekStart, $weekEnd])->get();
        foreach ($staffHoursDetails as $detail) {
            $dateString = $detail->date->format('Y-m-d');
            $receptionData[$dateString] = $detail->reception;
            $midnightPhoneData[$dateString] = $detail->midnight_phone[0] ?? null;
        }

        $holidays = Holiday::whereBetween('holiday_date', [$weekStart, $weekEnd])->pluck('holiday_date');


        return view('supervisor.enter-working-hours', compact('staffMembers', 'staffHours', 'selectedDate', 'dates', 'receptionData', 'midnightPhoneData', 'holidays', 'displayMidnightPhone'));
    }

    public function storeWorkingHours(Request $request)
    {
        $request->validate([
            'week' => 'required|date_format:Y-m-d',
            'hours' => 'required|array',
            'reception' => 'nullable|array',
            'midnight_phone' => 'nullable|array',
        ]);

        $selectedDate = $request->input('week');
        $weekStart = Carbon::parse($selectedDate)->startOfWeek();
        $weekEnd = Carbon::parse($selectedDate)->endOfWeek();
        
        $hoursData = $request->input('hours');
        $receptionData = $request->input('reception', []);
        $midnightPhoneData = $request->input('midnight_phone', []);

        DB::beginTransaction();

        try {
            foreach ($hoursData as $staffId => $staffDailyHours) {
                foreach ($staffDailyHours as $date => $timeRanges) {
                    // Verify the date is within the selected week
                    $currentDate = Carbon::parse($date);
                    if ($currentDate->between($weekStart, $weekEnd)) {
                        // Filter out empty values
                        $validTimeRanges = array_filter($timeRanges, function($timeRange) {
                            return !empty($timeRange);
                        });

                        if (!empty($validTimeRanges)) {
                            $formattedHours = $this->formatHoursData($validTimeRanges);

                            StaffMonthlyHours::updateOrCreate(
                                [
                                    'staff_id' => $staffId,
                                    'date' => $date,
                                ],
                                [
                                    'hours_data' => $formattedHours,
                                ]
                            );
                        } else {
                            // If all time ranges are empty, delete any existing record for this date
                            StaffMonthlyHours::where('staff_id', $staffId)
                                ->where('date', $date)
                                ->delete();
                        }
                    }
                }
            }

            // Save reception and midnight phone data for the week
            foreach ($weekStart->daysUntil($weekEnd) as $date) {
                $dateString = $date->format('Y-m-d');
                
                $staffHoursDetails = StaffHoursDetails::firstOrNew(['date' => $dateString]);
                
                if (isset($receptionData[$dateString])) {
                    $staffHoursDetails->reception = $receptionData[$dateString];
                }
                
                if (isset($midnightPhoneData[$dateString])) {
                    $staffHoursDetails->midnight_phone = [$midnightPhoneData[$dateString]];
                }
                
                $staffHoursDetails->save();
            }

            DB::commit();
            return redirect()->back()->with('success', 'Working hours have been successfully saved.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error saving working hours: ' . $e->getMessage());
            return redirect()->back()->with('error', 'An error occurred while saving working hours. Please try again.');
        }
    }

    private function validateTimeRange($timeRange)
    {
        if (in_array($timeRange, ['V', 'X', 'SL'])) {
            return true;
        }

        if (preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]-([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $timeRange)) {
            list($start, $end) = explode('-', $timeRange);
            $startTime = Carbon::createFromFormat('H:i', $start);
            $endTime = Carbon::createFromFormat('H:i', $end);

            return $endTime->gt($startTime);
        }

        return false;
    }

    public function displaySchedule()
    {
        $currentDate = Carbon::now();
        $weekStart = $currentDate->copy()->startOfWeek()->setYear(2024);
        $staffMembers = StaffUser::all();
        return view('supervisor.display-schedule', compact('weekStart', 'staffMembers'));
    }

    public function getWeekData(Request $request)
    {
        $weekStart = Carbon::parse($request->week_start)->startOfWeek();
        $weekEnd = $weekStart->copy()->endOfWeek();

        // First get staff IDs under current supervisor
        $staffIds = StaffUser::where('supervisor', Auth::user()->id)
            ->pluck('id');

        // Then get staff hours for those staff members
        $staffHoursDetails = StaffMonthlyHours::whereBetween('date', [
                $weekStart->format('Y-m-d'),
                $weekEnd->format('Y-m-d')
            ])
            ->whereIn('staff_id', $staffIds)
            ->get();

        $staffHours = [];
        $receptionData = [];
        $midnightPhoneData = [];

        foreach ($staffHoursDetails as $detail) {
            $dateString = $detail->date->format('Y-m-d');
            $staffHours[$dateString][$detail->staff_id] = $detail->hours_data;
        }

        $additionalDetails = StaffHoursDetails::whereBetween('date', [
            $weekStart->format('Y-m-d'),
            $weekEnd->format('Y-m-d')
        ])->get();

        foreach ($additionalDetails as $detail) {
            $dateString = $detail->date->format('Y-m-d');
            $receptionData[$dateString] = $detail->reception;
            $midnightPhoneData[$dateString] = $detail->midnight_phone[0] ?? null;
        }

        // Get staff names for midnight phone
        $staffMembers = StaffUser::whereIn('id', array_values($midnightPhoneData))->get()->keyBy('id');
        $midnightPhoneStaff = [];
        foreach ($midnightPhoneData as $date => $staffId) {
            $midnightPhoneStaff[$date] = $staffId ? ($staffMembers[$staffId]->name ?? 'Unknown') : '';
        }

        return response()->json([
            'staffHours' => $staffHours,
            'receptionData' => $receptionData,
            'midnightPhoneStaff' => $midnightPhoneStaff,
            'weekStart' => $weekStart->format('Y-m-d'),
            'weekEnd' => $weekEnd->format('Y-m-d'),
        ]);
    }

    private function getStaffHoursForWeek($weekStart, $weekEnd)
    {
        $staffHoursDetails = StaffMonthlyHours::whereBetween('month_year', [$weekStart->format('Y-m-d'), $weekEnd->format('Y-m-d')])
                                              ->get();

        $staffHours = [];
        foreach ($staffHoursDetails as $detail) {
            $staffHours[$detail->staff_id] = [];
            for ($day = 1; $day <= 31; $day++) {
                $dayColumn = "day_" . $day;
                if ($detail->$dayColumn) {
                    $staffHours[$detail->staff_id][$dayColumn] = json_decode($detail->$dayColumn, true) ?? [];
                }
            }
        }

        return $staffHours;
    }

    private function getMidnightPhoneForWeek($weekStart, $weekEnd)
    {
        $midnightPhone = [];
        $currentDate = $weekStart->copy();

        while ($currentDate <= $weekEnd) {
            $monthYear = $currentDate->format('Y-m');
            $day = $currentDate->day;

            $staffHours = StaffHoursDetails::where('month_year', $monthYear)->first();
            if ($staffHours && $staffHours->midnight_phone) {
                $midnightPhoneData = json_decode($staffHours->midnight_phone, true);
                if (isset($midnightPhoneData[$day])) {
                    $midnightPhone[$currentDate->format('Y-m-d')] = $midnightPhoneData[$day];
                }
            }

            $currentDate->addDay();
        }

        return $midnightPhone;
    }

    public function saveMidnightPhone(Request $request)
    {
        $request->validate([
            'staff_id' => 'required|exists:staff_users,id',
            'date' => 'required|date',
        ]);

        $date = Carbon::parse($request->date);
        $monthYear = $date->format('Y-m');
        $day = $date->day;

        $staffHours = StaffHoursDetails::firstOrCreate(['month_year' => $monthYear]);
        
        $midnightPhone = $staffHours->midnight_phone ? json_decode($staffHours->midnight_phone, true) : [];
        $midnightPhone[$day] = $request->staff_id;
        
        $staffHours->midnight_phone = json_encode($midnightPhone);
        $staffHours->save();

        return response()->json(['success' => true]);
    }

    private function formatHoursData($timeRanges)
    {
        return array_map(function($timeRange) {
            if (in_array($timeRange, ['V', 'X', 'SL'])) {
                return ['type' => $timeRange];
            } else {
                list($start, $end) = explode('-', $timeRange);
                return [
                    'start_time' => $start,
                    'end_time' => $end,
                ];
            }
        }, $timeRanges);
    }


    public function viewTimePlan(Request $request)
    {
        // Define the initial start date
        $baseStartDate = Carbon::parse('2024-07-01');
        $currentDate = Carbon::now();
        
        // Calculate current period if none selected
        $currentPeriod = floor($currentDate->diffInDays($baseStartDate) / 21);
        $defaultStart = $baseStartDate->copy()->addDays($currentPeriod * 21);
        
        // Get selected period start date from request, or use current period
        $selectedPeriod = $request->input('period', $defaultStart->format('Y-m-d'));
        $periodStart = Carbon::parse($selectedPeriod);
        $periodEnd = $periodStart->copy()->addDays(20);

        // Check if a supervisor is selected in the GET request
        $selectedSupervisor = $request->input('supervisor');

        if ($selectedSupervisor) {
            $staffMembers = StaffUser::where('supervisor', $selectedSupervisor)->get();
        } else {
            $staffMembers = StaffUser::where('supervisor', Auth::user()->id)->get();
            $selectedSupervisor = Auth::user()->id;
        }

        $displayMidnightPhone = Supervisors::where('user_id', $selectedSupervisor)->first()->display_midnight_phone;

        $staffHours = [];
        $receptionData = [];
        $midnightPhoneData = [];

        // Fetch all staff hours for the period at once
        $allStaffHours = StaffMonthlyHours::whereBetween('date', [
                $periodStart->format('Y-m-d'),
                $periodEnd->format('Y-m-d')
            ])
            ->whereIn('staff_id', $staffMembers->pluck('id'))
            ->get();

        // Organize staff hours by staff_id and date
        foreach ($allStaffHours as $hours) {
            $staffHours[$hours->staff_id][$hours->date->format('Y-m-d')] = $hours->hours_data;
        }

        // Fetch all hours details for the period at once
        $allHoursDetails = StaffHoursDetails::whereBetween('date', [
                $periodStart->format('Y-m-d'),
                $periodEnd->format('Y-m-d')
            ])
            ->get();

        // Organize reception and midnight phone data by date
        foreach ($allHoursDetails as $detail) {
            $dateString = $detail->date->format('Y-m-d');
            $receptionData[$dateString] = $detail->reception;
            $midnightPhoneData[$dateString] = $detail->midnight_phone[0] ?? null;
        }

        // Initialize empty arrays for dates with no data
        $currentDate = $periodStart->copy();
        while ($currentDate <= $periodEnd) {
            $dateString = $currentDate->format('Y-m-d');
            
            // Initialize staff hours if not set
            foreach ($staffMembers as $staffMember) {
                if (!isset($staffHours[$staffMember->id][$dateString])) {
                    $staffHours[$staffMember->id][$dateString] = [];
                }
            }

            // Initialize reception and midnight phone if not set
            if (!isset($receptionData[$dateString])) {
                $receptionData[$dateString] = '';
            }
            if (!isset($midnightPhoneData[$dateString])) {
                $midnightPhoneData[$dateString] = null;
            }

            $currentDate->addDay();
        }

        $supervisors = User::where('role', 'supervisor')->get();

        $holidays = Holiday::whereBetween('holiday_date', [
            $periodStart->format('Y-m-d'),
            $periodEnd->format('Y-m-d')
        ])->pluck('holiday_date');

        return view('supervisor.view-time-plan', compact(
            'staffMembers',
            'staffHours',
            'receptionData',
            'midnightPhoneData',
            'selectedPeriod',
            'supervisors',
            'selectedSupervisor',
            'holidays',
            'displayMidnightPhone'
        ));
    }
    
       public function viewHolidayTimePlan(Request $request)
    {
        // Define the initial start date
        $baseStartDate = Carbon::parse('2024-07-01');
        $currentDate = Carbon::now();
        
        // Calculate current period if none selected
        $currentPeriod = floor($currentDate->diffInDays($baseStartDate) / 21);
        $defaultStart = $baseStartDate->copy()->addDays($currentPeriod * 21);
        
        // Get selected period start date from request, or use current period
        $selectedPeriod = $request->input('period', $defaultStart->format('Y-m-d'));
        $periodStart = Carbon::parse($selectedPeriod);
        $periodEnd = $periodStart->copy()->addDays(20);

        // Check if a supervisor is selected in the GET request
        $selectedSupervisor = $request->input('supervisor');

        if ($selectedSupervisor) {
            $staffMembers = StaffUser::where('supervisor', $selectedSupervisor)->get();
        } else {
            $staffMembers = StaffUser::where('supervisor', Auth::user()->id)->get();
            $selectedSupervisor = Auth::user()->id;
        }

        $staffHours = [];
        $receptionData = [];
        $midnightPhoneData = [];

        // Get all holidays in the period
        $holidays = Holiday::whereBetween('holiday_date', [
            $periodStart->format('Y-m-d'),
            $periodEnd->format('Y-m-d')
        ])->pluck('holiday_date');

        // Fetch all staff hours for the period at once, but only for holiday dates
        $allStaffHours = StaffMonthlyHours::whereBetween('date', [
                $periodStart->format('Y-m-d'),
                $periodEnd->format('Y-m-d')
            ])
            ->whereIn('date', $holidays)
            ->whereIn('staff_id', $staffMembers->pluck('id'))
            ->get();

        // Organize staff hours by staff_id and date
        foreach ($allStaffHours as $hours) {
            $staffHours[$hours->staff_id][$hours->date->format('Y-m-d')] = $hours->hours_data;
        }

        // Fetch all hours details for the period at once, but only for holiday dates
        $allHoursDetails = StaffHoursDetails::whereBetween('date', [
                $periodStart->format('Y-m-d'),
                $periodEnd->format('Y-m-d')
            ])
            ->whereIn('date', $holidays)
            ->get();

        // Organize reception and midnight phone data by date
        foreach ($allHoursDetails as $detail) {
            $dateString = $detail->date->format('Y-m-d');
            $receptionData[$dateString] = $detail->reception;
            $midnightPhoneData[$dateString] = $detail->midnight_phone[0] ?? null;
        }

        // Initialize empty arrays for holiday dates with no data
        $currentDate = $periodStart->copy();
        while ($currentDate <= $periodEnd) {
            $dateString = $currentDate->format('Y-m-d');
            
            // Only process if it's a holiday
            if ($holidays->contains($dateString)) {
                // Initialize staff hours if not set
                foreach ($staffMembers as $staffMember) {
                    if (!isset($staffHours[$staffMember->id][$dateString])) {
                        $staffHours[$staffMember->id][$dateString] = [];
                    }
                }

                // Initialize reception and midnight phone if not set
                if (!isset($receptionData[$dateString])) {
                    $receptionData[$dateString] = '';
                }
                if (!isset($midnightPhoneData[$dateString])) {
                    $midnightPhoneData[$dateString] = null;
                }
            }

            $currentDate->addDay();
        }

        $supervisors = User::where('role', 'supervisor')->get();

        return view('supervisor.view-holiday-time-plan', compact(
            'staffMembers',
            'staffHours',
            'receptionData',
            'midnightPhoneData',
            'selectedPeriod',
            'supervisors',
            'selectedSupervisor'
        ));
    }
}
