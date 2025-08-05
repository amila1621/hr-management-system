<?php

namespace App\Http\Controllers;

use App\Models\Holiday;
use App\Models\HrAssistants;
use App\Models\StaffMissingHours; // Add import for missing hours model
use App\Models\StaffMonthlyHours;
use App\Models\StaffUser;
use App\Models\StaffHoursDetails;
use App\Models\SupervisorSickLeaves;
use App\Models\Supervisors;
use App\Models\TeamLeads;
use App\Models\SalaryReport;
use App\Models\StaffMidnightPhone;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Hash;

class StaffController extends Controller
{
    public function StaffDashboard(Request $request)
    {
        // Get the selected month from the request, or use the current month if not provided
        $selectedMonth = $request->input('month', Carbon::now()->format('Y-m'));
        $monthYear = Carbon::parse($selectedMonth);

        if (Auth::user()->role == 'supervisor') {
            $currentStaffMember = Supervisors::where('user_id', Auth::user()->id)->first();
        } else {
            $currentStaffMember = StaffUser::where('user_id', Auth::user()->id)->first();

            // we have to find the supervisor for the staff member
            $supervisor = Supervisors::where('department', $currentStaffMember->department)
                ->orWhere('department', 'LIKE', '%' . $currentStaffMember->department . '%')
                ->first();
        }

        if (!$currentStaffMember) {
            return redirect()->back()->with('error', 'Staff member not found.');
        }

        // Parse departments - different logic for supervisors vs staff
        if (Auth::user()->role == 'supervisor') {
            // Define hotel and office departments
            $hotelDepartments = ['Hotel Restaurant', 'Hotel', 'Hotel Spa'];

            // Get supervisor's departments
            $supervisorDepartments = explode(', ', $currentStaffMember->department);
            $supervisorDepartments = array_map('trim', $supervisorDepartments);

            // Check if supervisor manages any hotel departments
            $isHotelSupervisor = !empty(array_intersect($supervisorDepartments, $hotelDepartments));

            // Get all unique departments from StaffUser table
            $allDepartments = StaffUser::select('department')
                ->whereNotNull('department')
                ->where('department', '!=', '')
                ->get()
                ->pluck('department')
                ->flatMap(function ($dept) {
                    return explode(', ', $dept);
                })
                ->map('trim')
                ->unique()
                ->values()
                ->toArray();

            // Filter departments based on supervisor type
            if ($isHotelSupervisor) {
                // Hotel supervisor can only see hotel departments
                $departments = array_intersect($allDepartments, $hotelDepartments);
                $departments = array_values($departments); // Re-index array
            } else {
                // Office supervisor can only see non-hotel departments
                $departments = array_diff($allDepartments, $hotelDepartments);
                $departments = array_values($departments); // Re-index array
            }
        } else {
            // For regular staff, use their own departments
            $staffDepartment = $currentStaffMember->department;
            $departments = explode(', ', $staffDepartment);
            $departments = array_map('trim', $departments);
        }

        $currentUser = Auth::user();
        if (in_array(strtolower($currentUser->email), ['beatriz@nordictravels.eu', 'semi@nordictravels.eu'])) {
            if (!in_array('AM', $departments)) {
                $departments[] = 'AM';
            }
        }


        // Get selected department from request or use first department as default
        $selectedDepartment = $request->input('department', $departments[0] ?? '');

        // Ensure selected department is valid for this supervisor/user
        if (!in_array($selectedDepartment, $departments)) {
            $selectedDepartment = $departments[0] ?? '';
        }

        // Additional validation for supervisors to prevent URL manipulation
        if (Auth::user()->role == 'supervisor' && !empty($selectedDepartment)) {
            $hotelDepartments = ['Hotel Restaurant', 'Hotel', 'Hotel Spa'];
            $supervisorDepartments = explode(', ', $currentStaffMember->department);
            $supervisorDepartments = array_map('trim', $supervisorDepartments);

            $isHotelSupervisor = !empty(array_intersect($supervisorDepartments, $hotelDepartments));
            $isSelectedDepartmentHotel = in_array($selectedDepartment, $hotelDepartments);

            // Hotel supervisor trying to access office department OR office supervisor trying to access hotel department
            if (($isHotelSupervisor && !$isSelectedDepartmentHotel) || (!$isHotelSupervisor && $isSelectedDepartmentHotel)) {
                return redirect()->back()->with('error', 'You do not have permission to view this department.');
            }
        }

        if (empty($selectedDepartment)) {
            return redirect()->back()->with('error', 'No valid department found for this staff member.');
        }

        // Get staff members for the selected department
        $staffMembers = StaffUser::where(function ($query) use ($selectedDepartment) {
            $query->where('department', 'LIKE', $selectedDepartment)
                ->orWhere('department', 'LIKE', $selectedDepartment . ',%')
                ->orWhere('department', 'LIKE', '%, ' . $selectedDepartment)
                ->orWhere('department', 'LIKE', '%, ' . $selectedDepartment . ',%');
        })->get();

        // UPDATED: For non-supervisors, add their supervisor to the top of the list
        if (Auth::user()->role !== 'supervisor' && isset($supervisor)) {
            $currentUser = Auth::user();

            // Skip supervisor display for Semi and Beatriz
            if (!in_array(strtolower($supervisor->email), ['beatriz@nordictravels.eu', 'semi@nordictravels.eu'])) {
                // Find the supervisor's StaffUser record
                $supervisorStaffRecord = StaffUser::where('user_id', $supervisor->user_id)->first();

                if ($supervisorStaffRecord) {
                    // Remove supervisor from the collection if they're already in it
                    $staffMembers = $staffMembers->reject(function ($staffMember) use ($supervisorStaffRecord) {
                        return $staffMember->id === $supervisorStaffRecord->id;
                    });

                    // Add supervisor to the beginning of the collection
                    $staffMembers = $staffMembers->prepend($supervisorStaffRecord);
                }
            }
        }

        // Get display midnight phone setting for the selected department
        $displayMidnightPhone = Supervisors::where(function ($query) use ($selectedDepartment) {
            $query->where('department', $selectedDepartment)
                ->orWhere('department', 'LIKE', $selectedDepartment . ',%')
                ->orWhere('department', 'LIKE', '%, ' . $selectedDepartment)
                ->orWhere('department', 'LIKE', '%, ' . $selectedDepartment . ',%');
        })->first()->display_midnight_phone ?? false;

        // Get the first and last day of the selected month
        $monthStart = $monthYear->copy()->startOfMonth()->format('Y-m-d');
        $monthEnd = $monthYear->copy()->endOfMonth()->format('Y-m-d');

        $holidays = Holiday::whereBetween('holiday_date', [$monthStart, $monthEnd])->pluck('holiday_date');
        $daysInMonth = $monthYear->daysInMonth;

        $staffHours = [];
        $receptionData = [];
        $midnightPhoneData = [];

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = $monthYear->copy()->day($day);
            $dateString = $date->format('Y-m-d');

            // Fetch staff hours for all staff members in the selected department
            foreach ($staffMembers as $staffMember) {
                $monthlyHours = StaffMonthlyHours::where('staff_id', $staffMember->id)
                    ->where('date', $dateString)
                    ->first();

                if ($monthlyHours) {
                    // Process each entry in hours_data to handle original time values
                    $processedHoursData = [];

                    foreach ($monthlyHours->hours_data as $entry) {
                        // Check if entry is already an array
                        if (is_array($entry)) {
                            // Preserve original format
                            $processedHoursData[] = $entry;
                        }
                        // Handle string values (could be JSON or plain text)
                        else if (is_string($entry)) {
                            // Check if it's a JSON string with original times
                            if (substr($entry, 0, 1) === '{' && substr($entry, -1) === '}') {
                                try {
                                    $timeData = json_decode($entry, true);
                                    if (json_last_error() === JSON_ERROR_NONE) {
                                        $processedHoursData[] = $timeData;
                                    } else {
                                        $processedHoursData[] = $entry;
                                    }
                                } catch (\Exception $e) {
                                    $processedHoursData[] = $entry;
                                }
                            }
                            // Handle simple time range or special values like 'V', 'X', 'H'
                            else {
                                $processedHoursData[] = $entry;
                            }
                        }
                    }

                    $staffHours[$staffMember->id][$dateString] = $processedHoursData;
                } else {
                    $staffHours[$staffMember->id][$dateString] = [];
                }
            }

            // Fetch reception and midnight phone data filtered by selected department
            $hoursDetails = StaffHoursDetails::where('date', $dateString)
                ->where('department', $selectedDepartment)
                ->first();

            if ($hoursDetails) {
                $receptionData[$dateString] = $hoursDetails->reception;
                $midnightPhoneData[$dateString] = $hoursDetails->midnight_phone[0] ?? null;
            } else {
                $receptionData[$dateString] = '';
                $midnightPhoneData[$dateString] = null;
            }
        }

        return view('staffs.dashboard', compact(
            'currentStaffMember',
            'staffMembers',
            'staffHours',
            'receptionData',
            'midnightPhoneData',
            'selectedMonth',
            'daysInMonth',
            'holidays',
            'displayMidnightPhone',
            'departments',           // Departments from staff user's department field
            'selectedDepartment'     // Selected department
        ));
    }

        private function calculateSalaryHoursForMonth($date)
    {
        // Get all days in the month
        $startOfMonth = $date->copy()->startOfMonth();
        $endOfMonth = $date->copy()->endOfMonth();
        
        // Calculate for each day in the month
        for ($currentDate = $startOfMonth->copy(); $currentDate->lte($endOfMonth); $currentDate->addDay()) {
            $dateString = $currentDate->format('Y-m-d');
            
            // Call the SalarySummaryController's calculateSalaryHours method
            app(SalarySummaryController::class)->calculateSalaryHours($dateString);
        }
    }

    public function StaffHoursReport(Request $request)
    {
        $staffId = StaffUser::where('user_id', Auth::user()->id)->first()->id;
        $staff = StaffUser::find($staffId);
        $staffDepartment = $staff->department;

        // Check for year and month parameters
        $year = $request->input('year', Carbon::now()->year);
        $month = $request->input('month', Carbon::now()->month);
        
        // Create a proper date with both year and month
        $date = Carbon::createFromDate($year, $month, 1);

        // First, calculate salary hours for the month to ensure all data is up to date
        $this->calculateSalaryHoursForMonth($date);

        if ($staffDepartment == 'Hotel') {
            return $this->processHotelStaffHours($request, $staff, $staffId);
        } elseif ($staffDepartment == 'Operations') {
            return $this->processOperationsStaffHours($request, $staff, $staffId);
        } else {
            return $this->processDefaultStaffHours($request, $staff, $staffId);
        }
    }


    public function getOfficeStaffWiseReport(Request $request)
    {
        // Use staff_id from request if present, otherwise use the first staff user
        $staffUser = null;
        if ($request->has('staff_id')) {
            $staffUser = StaffUser::find($request->input('staff_id'));
        }
        if (!$staffUser) {
            $staffUser = StaffUser::first();
        }

        if (!$staffUser) {
            return redirect()->back()->with('error', 'Staff member not found.');
        }

        $staff = $staffUser;
        $staffDepartment = $staff->department;

        if ($staffDepartment == 'Hotel') {
            return $this->processHotelStaffHours($request, $staff, $staff->id);
        } elseif ($staffDepartment == 'Operations') {
            return $this->processOperationsStaffHours($request, $staff, $staff->id);
        } else {
            return $this->processDefaultStaffHours($request, $staff, $staff->id);
        }
    }

    private function processHotelStaffHours(Request $request, StaffUser $staff, $staffId)
    {
        // Get selected year and month, default to current
        $currentDate = Carbon::now();
        $year = $request->input('year', $currentDate->year);
        $month = $request->input('month', $currentDate->month);

        // Always use the current staff, ignore any staff_id from request
        $selectedStaffId = $staffId; // Ignore request input and always use current user
        $selectedStaff = $staff;

        // Create a collection with only the current staff member
        $staffs = collect([$staff]);

        // Get holidays for the selected month and year
        $holidays = Holiday::whereYear('holiday_date', $year)
            ->whereMonth('holiday_date', $month)
            ->pluck('holiday_date')
            ->map(function ($date) {
                return Carbon::parse($date)->format('Y-m-d');
            })
            ->toArray();

        // Initialize results array for current staff only
        $staffResults = [];

        // Get working hours for the current staff member
        $staffHours = StaffMonthlyHours::where('staff_id', $staffId)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->get();

        // Initialize record data for the current staff only
        $staffResults[$staffId] = [
            'staff_name' => $staff->name,
            'records' => []
        ];

        // Process each record and calculate hours
        foreach ($staffHours as $record) {
            // Format the record date to match holiday date format
            $recordDateStr = Carbon::parse($record->date)->format('Y-m-d');
            $isHoliday = in_array($recordDateStr, $holidays);
            $recordDate = Carbon::parse($record->date);

            foreach ($record->hours_data as $shift) {
                if (isset($shift['start_time']) && isset($shift['end_time'])) {
                    // Create proper Carbon instances for start and end times
                    $startTime = Carbon::parse($recordDate->format('Y-m-d') . ' ' . $shift['start_time']);
                    $endTime = Carbon::parse($recordDate->format('Y-m-d') . ' ' . $shift['end_time']);

                    // Handle overnight shifts
                    if ($endTime->lt($startTime)) {
                        $endTime->addDay();
                    }

                    // Initialize record data with default "00:00" for all time fields
                    $recordData = [
                        'date' => $recordDate->format('Y-m-d'),
                        'start_time' => $startTime->format('H:i'),
                        'end_time' => $endTime->format('H:i'),
                        'work_hours' => '00:00',
                        'holiday_hours' => '00:00',
                        'evening_hours' => '00:00',
                        'evening_holiday_hours' => '00:00',
                        'night_hours' => '00:00',
                        'night_holiday_hours' => '00:00',
                        'type' => $shift['type'] ?? 'normal'
                    ];

                    // Calculate total minutes
                    $totalMinutes = $endTime->diffInMinutes($startTime);
                    $workHours = floor($totalMinutes / 60);
                    $workMinutes = $totalMinutes % 60;

                    // Format as HH:MM
                    $formattedWorkHours = sprintf("%02d:%02d", $workHours, $workMinutes);

                    // On holidays, count hours in both regular and holiday columns
                    if ($isHoliday) {
                        $recordData['work_hours'] = $formattedWorkHours;
                        $recordData['holiday_hours'] = $formattedWorkHours;
                    } else {
                        $recordData['work_hours'] = $formattedWorkHours;
                        $recordData['holiday_hours'] = '00:00';
                    }

                    // Evening hours (18:00-00:00)
                    $eveningStart = Carbon::parse($recordDate->format('Y-m-d') . ' 18:00');
                    // Use 00:00 of the next day instead of 23:59
                    $eveningEnd = Carbon::parse($recordDate->format('Y-m-d') . ' 00:00')->addDay();
                    if ($startTime->lt($eveningEnd) && $endTime->gt($eveningStart)) {
                        // Calculate overlapping minutes
                        $eveningMinutes = $this->calculateOverlappingMinutes($startTime, $endTime, $eveningStart, $eveningEnd);
                        $eveningHours = floor($eveningMinutes / 60);
                        $eveningMins = $eveningMinutes % 60;
                        $formattedEveningHours = sprintf("%02d:%02d", $eveningHours, $eveningMins);

                        if ($isHoliday) {
                            $recordData['evening_hours'] = $formattedEveningHours;
                            $recordData['evening_holiday_hours'] = $formattedEveningHours;
                        } else {
                            $recordData['evening_hours'] = $formattedEveningHours;
                            $recordData['evening_holiday_hours'] = '00:00';
                        }
                    }

                    // Night hours (00:00-06:00) - Fixed calculation
                    $nightMinutes = 0;

                    // Check for night hours from midnight to 6 AM (next day)
                    $nightStart = Carbon::parse($recordDate->format('Y-m-d') . ' 00:00')->addDay();
                    $nightEnd = Carbon::parse($recordDate->format('Y-m-d') . ' 06:00')->addDay();

                    if ($startTime->lt($nightEnd) && $endTime->gt($nightStart)) {
                        $nightMinutes = $this->calculateOverlappingMinutes($startTime, $endTime, $nightStart, $nightEnd);
                    }

                    // Also check for early morning shifts on the same day (before 06:00)
                    $sameDayNightEnd = Carbon::parse($recordDate->format('Y-m-d') . ' 06:00');
                    if ($startTime->lt($sameDayNightEnd) && $startTime->format('Y-m-d') === $recordDate->format('Y-m-d')) {
                        $sameDayNightMinutes = $this->calculateOverlappingMinutes($startTime, $endTime, $startTime, $sameDayNightEnd);
                        $nightMinutes += $sameDayNightMinutes;
                    }

                    if ($nightMinutes > 0) {
                        $nightHours = floor($nightMinutes / 60);
                        $nightMins = $nightMinutes % 60;
                        $formattedNightHours = sprintf("%02d:%02d", $nightHours, $nightMins);

                        if ($isHoliday) {
                            $recordData['night_hours'] = $formattedNightHours;
                            $recordData['night_holiday_hours'] = $formattedNightHours;
                        } else {
                            $recordData['night_hours'] = $formattedNightHours;
                            $recordData['night_holiday_hours'] = '00:00';
                        }
                    }

                    $staffResults[$selectedStaff->id]['records'][] = $recordData;
                }
            }
        }

        // Process any missing hours for the current staff
        $missingHours = StaffMissingHours::where('staff_id', $staffId)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->get();

        foreach ($missingHours as $missing) {
            try {
                $recordDate = $missing->date;
                $recordDateStr = $recordDate->format('Y-m-d');
                $isHoliday = in_array($recordDateStr, $holidays);

                // Parse start and end times
                if (strpos($missing->start_time, ':') !== false && strlen($missing->start_time) <= 5) {
                    // Format is H:i
                    $startTime = Carbon::parse($recordDate->format('Y-m-d') . ' ' . $missing->start_time);
                    $endTime = Carbon::parse($recordDate->format('Y-m-d') . ' ' . $missing->end_time);
                } else {
                    // Full datetime format
                    $startTime = Carbon::parse($missing->start_time);
                    $endTime = Carbon::parse($missing->end_time);
                }

                // Handle overnight shifts
                if ($endTime->lt($startTime)) {
                    $endTime->addDay();
                }

                // Initialize record data with default "00:00" for all time fields
                $recordData = [
                    'date' => $recordDate->format('Y-m-d'),
                    'start_time' => $startTime->format('H:i'),
                    'end_time' => $endTime->format('H:i'),
                    'work_hours' => '00:00',
                    'holiday_hours' => '00:00',
                    'evening_hours' => '00:00',
                    'evening_holiday_hours' => '00:00',
                    'night_hours' => '00:00',
                    'night_holiday_hours' => '00:00',
                    'type' => 'missing'
                ];

                // Calculate total minutes (missing hours are NOT divided)
                $totalMinutes = $endTime->diffInMinutes($startTime);
                $workHours = floor($totalMinutes / 60);
                $workMinutes = $totalMinutes % 60;
                $formattedWorkHours = sprintf("%02d:%02d", $workHours, $workMinutes);

                // On holidays, count hours in both regular and holiday columns
                if ($isHoliday) {
                    $recordData['work_hours'] = $formattedWorkHours;
                    $recordData['holiday_hours'] = $formattedWorkHours;
                } else {
                    $recordData['work_hours'] = $formattedWorkHours;
                    $recordData['holiday_hours'] = '00:00';
                }

                // Evening hours (18:00-00:00)
                $eveningStart = Carbon::parse($recordDate->format('Y-m-d') . ' 18:00');
                // Use 00:00 of the next day instead of 23:59
                $eveningEnd = Carbon::parse($recordDate->format('Y-m-d') . ' 00:00')->addDay();
                if ($startTime->lt($eveningEnd) && $endTime->gt($eveningStart)) {
                    $eveningMinutes = $this->calculateOverlappingMinutes($startTime, $endTime, $eveningStart, $eveningEnd);
                    $eveningHours = floor($eveningMinutes / 60);
                    $eveningMins = $eveningMinutes % 60;
                    $formattedEveningHours = sprintf("%02d:%02d", $eveningHours, $eveningMins);

                    if ($isHoliday) {
                        $recordData['evening_hours'] = $formattedEveningHours;
                        $recordData['evening_holiday_hours'] = $formattedEveningHours;
                    } else {
                        $recordData['evening_hours'] = $formattedEveningHours;
                        $recordData['evening_holiday_hours'] = '00:00';
                    }
                }

                // Night hours (00:00-06:00) - Fixed calculation for missing hours
                $nightMinutes = 0;

                // Check for night hours from midnight to 6 AM (next day)
                $nightStart = Carbon::parse($recordDate->format('Y-m-d') . ' 00:00')->addDay();
                $nightEnd = Carbon::parse($recordDate->format('Y-m-d') . ' 06:00')->addDay();

                if ($startTime->lt($nightEnd) && $endTime->gt($nightStart)) {
                    $nightMinutes = $this->calculateOverlappingMinutes($startTime, $endTime, $nightStart, $nightEnd);
                }

                // Also check for early morning shifts on the same day (before 06:00)
                $sameDayNightEnd = Carbon::parse($recordDate->format('Y-m-d') . ' 06:00');
                if ($startTime->lt($sameDayNightEnd) && $startTime->format('Y-m-d') === $recordDate->format('Y-m-d')) {
                    $sameDayNightMinutes = $this->calculateOverlappingMinutes($startTime, $endTime, $startTime, $sameDayNightEnd);
                    $nightMinutes += $sameDayNightMinutes;
                }

                if ($nightMinutes > 0) {
                    $nightHours = floor($nightMinutes / 60);
                    $nightMins = $nightMinutes % 60;
                    $formattedNightHours = sprintf("%02d:%02d", $nightHours, $nightMins);

                    if ($isHoliday) {
                        $recordData['night_hours'] = $formattedNightHours;
                        $recordData['night_holiday_hours'] = $formattedNightHours;
                    } else {
                        $recordData['night_hours'] = $formattedNightHours;
                        $recordData['night_holiday_hours'] = '00:00';
                    }
                }

                $staffResults[$selectedStaff->id]['records'][] = $recordData;
            } catch (\Exception $e) {
                \Log::error('Error processing missing hours record ' . $missing->id . ': ' . $e->getMessage());
                continue;
            }
        }

        // Return the view with all necessary data for the existing UI
        return view('supervisor.staff-hour-hotel-report', compact(
            'staff',
            'staffs',
            'staffResults',
            'year',
            'month',
            'selectedStaff'
        ));
    }



    private function calculateOverlappingMinutes($start1, $end1, $start2, $end2)
    {
        $latest_start = max($start1, $start2);
        $earliest_end = min($end1, $end2);
        $overlap_minutes = max(0, $earliest_end->diffInMinutes($latest_start));
        return $overlap_minutes;
    }

    private function processOperationsStaffHours(Request $request, StaffUser $staff, $staffId)
    {
        // Get selected year and month, default to current
        $year = $request->input('year', date('Y'));
        $month = $request->input('month', date('n'));

        // Create date range for the selected month
        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth();

        // Get sick leave statuses first
        $sickLeaveStatuses = SupervisorSickLeaves::whereIn('staff_id', [$staffId])
            ->where(function ($query) use ($startDate, $endDate) {
                // Check if either start_date or end_date falls within the date range
                // Or if start_date is before the range and end_date is after (spanning the entire month)
                $query->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhereBetween('end_date', [$startDate, $endDate])
                    ->orWhere(function ($q) use ($startDate, $endDate) {
                        $q->where('start_date', '<=', $startDate)
                            ->where('end_date', '>=', $endDate);
                    });
            })
            ->get();

        // Debug log
        \Log::info('Sick Leave Query Results', [
            'staff_id' => $staffId,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'sick_leaves_found' => $sickLeaveStatuses->count(),
            'sick_leaves' => $sickLeaveStatuses->toArray()
        ]);

        // Process sick leaves into a dedicated array
        $sickLeaveHours = [];
        foreach ($sickLeaveStatuses as $sickLeave) {
            $sickLeaveStartDate = Carbon::parse($sickLeave->start_date);
            $sickLeaveEndDate = Carbon::parse($sickLeave->end_date);

            // Determine status message
            $statusMessage = '';
            switch ($sickLeave->status) {
                case '0':
                    $statusMessage = 'Sick Leave - Pending from supervisor';
                    break;
                case '1':
                    $statusMessage = 'Sick Leave - Pending from HR';
                    break;
                case '2':
                    $statusMessage = 'Sick Leave - Approved';
                    break;
                case '3':
                    $statusMessage = 'Sick Leave - Rejected';
                    break;
                case '4':
                    $statusMessage = 'Sick Leave - Cancelled';
                    break;
                default:
                    $statusMessage = 'Sick Leave - Unknown status';
            }

            // Create entries for each date in the sick leave range that falls within the month
            $currentDate = max($sickLeaveStartDate, Carbon::createFromDate($year, $month, 1)->startOfMonth());
            $rangeEndDate = min($sickLeaveEndDate, Carbon::createFromDate($year, $month, 1)->endOfMonth());

            \Log::info('Processing sick leave date range', [
                'original_start' => $sickLeave->start_date,
                'original_end' => $sickLeave->end_date,
                'current_date' => $currentDate->format('Y-m-d'),
                'range_end_date' => $rangeEndDate->format('Y-m-d')
            ]);

            while ($currentDate <= $rangeEndDate) {
                $dateKey = $currentDate->format('Y-m-d');

                if (!isset($sickLeaveHours[$dateKey])) {
                    $sickLeaveHours[$dateKey] = [];
                }

                $sickLeaveHours[$dateKey][] = [
                    'type' => 'sick_leave',
                    'status' => $sickLeave->status,
                    'status_message' => $statusMessage,
                    'description' => $sickLeave->description ?? '',
                    'start_date' => $sickLeave->start_date,
                    'end_date' => $sickLeave->end_date,
                    'supervisor_remark' => $sickLeave->supervisor_remark ?? '',
                    'admin_remark' => $sickLeave->admin_remark ?? '',
                    'image' => $sickLeave->image ?? '',
                    'created_at' => $sickLeave->created_at
                ];

                \Log::info('Added sick leave for date', ['date' => $dateKey, 'status' => $statusMessage]);

                $currentDate->addDay();
            }
        }

        \Log::info('Final sick leave hours array', ['sick_leave_hours' => $sickLeaveHours]);

        // Initialize the dailyHours array
        $dailyHours = [];

        // Get regular monthly hours
        $staffMonthlyData = StaffMonthlyHours::where('staff_id', $staffId)
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        // Process regular hours
        foreach ($staffMonthlyData as $record) {
            $dateKey = $record->date->format('Y-m-d');
            $isHoliday = $this->isHoliday($record->date);

            $shifts = is_array($record->hours_data)
                ? $record->hours_data
                : json_decode($record->hours_data, true);

            if (!empty($shifts)) {
                $dailyHours[$dateKey] = [
                    'date' => $dateKey,
                    'shifts' => [],
                    'is_approved' => $record->is_approved // Add approval status
                ];

                // Process each shift
                foreach ($shifts as $shift) {
                    if (isset($shift['start_time']) && isset($shift['end_time'])) {
                        $startTime = Carbon::createFromFormat('H:i', $shift['start_time']);
                        $endTime = Carbon::createFromFormat('H:i', $shift['end_time']);

                        // Check for sick leave status first
                        $slStatus = '';
                        $hideHoursForSickLeave = false;

                        if (isset($shift['type']) && $shift['type'] === 'SL') {
                            $sickLeaveKey = $staffId . '_' . $dateKey;

                            if (isset($sickLeaveStatuses[$sickLeaveKey])) {
                                $sickLeave = $sickLeaveStatuses[$sickLeaveKey]->first();

                                if ($sickLeave) {
                                    $status = $sickLeave->status;
                                    // Hide hours if sick leave status is approved (2), rejected (3), or cancelled (4)
                                    $hideHoursForSickLeave = in_array($status, ['2', '3', '4']);

                                    switch ($status) {
                                        case '0':
                                            $slStatus = '(Sick Leave - Pending from supervisor)';
                                            break;
                                        case '1':
                                            $slStatus = '(Sick Leave - Pending from HR)';
                                            break;
                                        case '2':
                                            $slStatus = '(Sick Leave - Approved)';
                                            break;
                                        case '3':
                                            $slStatus = '(Sick Leave - Rejected)';
                                            break;
                                        case '4':
                                            $slStatus = '(Sick Leave - Cancelled)';
                                            break;
                                    }
                                }
                            }
                        }

                        // Calculate hours
                        $minutes = 0;
                        $nightMinutes = 0;
                        $holidayHours = 0;
                        $holidayNightHours = 0;

                        if (!$hideHoursForSickLeave) {
                            $minutes = $endTime->diffInMinutes($startTime);
                            $nightMinutes = $this->calculateNightHours($startTime, $endTime);
                            $holidayHours = $isHoliday ? $minutes : 0;
                            $holidayNightHours = $isHoliday ? $nightMinutes : 0;
                        }

                        $dailyHours[$dateKey]['shifts'][] = [
                            'start_time' => $shift['start_time'],
                            'end_time' => $shift['end_time'],
                            'type' => $shift['type'] ?? 'normal',
                            'sick_leave_status' => $slStatus,
                            'is_approved_sick_leave' => $hideHoursForSickLeave,
                            'original_hours' => floor($endTime->diffInMinutes($startTime) / 60),
                            'original_minutes' => $endTime->diffInMinutes($startTime) % 60,
                            'hours' => $hideHoursForSickLeave ? 0 : floor($minutes / 60),
                            'minutes' => $hideHoursForSickLeave ? 0 : ($minutes % 60),
                            'holiday_hours' => $hideHoursForSickLeave ? 0 : floor($holidayHours / 60),
                            'holiday_minutes' => $hideHoursForSickLeave ? 0 : ($holidayHours % 60),
                            'night_hours' => $hideHoursForSickLeave ? 0 : floor($nightMinutes / 60),
                            'night_minutes' => $hideHoursForSickLeave ? 0 : ($nightMinutes % 60),
                            'holiday_night_hours' => $hideHoursForSickLeave ? 0 : floor($holidayNightHours / 60),
                            'holiday_night_minutes' => $holidayNightHours % 60
                        ];
                    }
                }
            }
        }

        // Get missing hours for this staff member
        $missingHours = StaffMissingHours::where('staff_id', $staffId)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date')
            ->get();

        // Add missing hours to the dailyHours array
        foreach ($missingHours as $missingHour) {
            $dateKey = $missingHour->date->format('Y-m-d');
            $isHoliday = $this->isHoliday($missingHour->date);

            // Create the date entry if it doesn't exist
            if (!isset($dailyHours[$dateKey])) {
                $dailyHours[$dateKey] = [
                    'date' => $dateKey,
                    'shifts' => []
                ];
            }

            // Format start and end time - handle different possible formats
            if (strpos($missingHour->start_time, ':') !== false && strlen($missingHour->start_time) <= 5) {
                // Format is already H:i
                $startTime = Carbon::createFromFormat('H:i', $missingHour->start_time);
                $endTime = Carbon::createFromFormat('H:i', $missingHour->end_time);
            } else {
                // Full datetime format (Y-m-d H:i:s)
                $startTime = Carbon::parse($missingHour->start_time);
                $endTime = Carbon::parse($missingHour->end_time);
            }

            // Handle overnight shifts
            if ($endTime->lt($startTime)) {
                $endTime->addDay();
            }

            // Calculate total regular hours
            $minutes = $endTime->diffInMinutes($startTime);

            // Calculate night hours (20:00-06:00) properly
            // Create night time range for proper calculation
            $nightStartTime = Carbon::parse($missingHour->date->format('Y-m-d') . ' 20:00');
            $nightEndTime = Carbon::parse($missingHour->date->format('Y-m-d') . ' 06:00')->addDay();

            // Initialize night minutes
            $nightMinutes = 0;

            // Check if shift overlaps with night hours
            if ($startTime->lt($nightEndTime) && $endTime->gt($nightStartTime)) {
                // Calculate the overlap between shift and night hours
                $overlapStart = max($startTime, $nightStartTime);
                $overlapEnd = min($endTime, $nightEndTime);
                $nightMinutes = $overlapEnd->diffInMinutes($overlapStart);
            }

            // Calculate holiday hours
            $holidayHours = $isHoliday ? $minutes : 0;
            $holidayNightHours = $isHoliday ? $nightMinutes : 0;

            // Add missing hour as a shift with the proper formatting
            $dailyHours[$dateKey]['shifts'][] = [
                'start_time' => $startTime->format('H:i'),
                'end_time' => $endTime->format('H:i'),
                'type' => 'missing', // Special type for missing hours
                'reason' => $missingHour->reason,
                'sick_leave_status' => '',
                'is_approved_sick_leave' => false,
                'missing_hour_id' => $missingHour->id,
                'missing_hour_reason' => $missingHour->reason,
                'missing_hour_status' => $missingHour->status ?? 'pending',
                'missing_hour_applied_date' => $missingHour->applied_date,
                'missing_hour_created_by' => $missingHour->created_by,
                'original_hours' => floor($minutes / 60),
                'original_minutes' => $minutes % 60,
                'hours' => floor($minutes / 60),
                'minutes' => $minutes % 60,
                'holiday_hours' => floor($holidayHours / 60),
                'holiday_minutes' => $holidayHours % 60,
                'night_hours' => floor($nightMinutes / 60),
                'night_minutes' => $nightMinutes % 60,
                'holiday_night_hours' => floor($holidayNightHours / 60),
                'holiday_night_minutes' => $holidayNightHours % 60
            ];
        }

        // Sort by date
        ksort($dailyHours);
        ksort($sickLeaveHours);

        // Further sort shifts within each date by start time
        foreach ($dailyHours as $dateKey => &$dayData) {
            if (isset($dayData['shifts']) && is_array($dayData['shifts'])) {
                usort($dayData['shifts'], function ($a, $b) {
                    return strcmp($a['start_time'], $b['start_time']);
                });
            }
        }

        // Midnight phone hours
        $midnightHours = SalaryReport::where('user_id', $staffId)
            ->whereBetween('date', [$startDate, $endDate])
            ->where('work_periods', 'LIKE', "%Midnight Phone%")
            ->get();

        $midnightPhoneTimings = [];
        foreach ($midnightHours as $record) {
            // Find all time ranges with (Midnight Phone)
            if (preg_match_all('/(\d{2}:\d{2}\s*-\s*\d{2}:\d{2})\s*\(Midnight Phone\)/i', $record->work_periods, $matches)) {
                foreach ($matches[1] as $timeRange) {
                    $midnightPhoneTimings[] = [
                        'date' => $record->date,
                        'time_range' => $timeRange,
                    ];
                }
            }
        }

        foreach ($midnightPhoneTimings as $entry) {
            $date = is_object($entry['date']) ? $entry['date']->format('Y-m-d') : $entry['date'];
            [$start, $end] = array_map('trim', explode('-', $entry['time_range']));

            // Helper to get Carbon time for a given date and time string
            $getTime = function ($date, $time) {
                return Carbon::parse($date . ' ' . $time);
            };

            $isHoliday = $this->isHoliday(Carbon::parse($date));

            if (strtotime($end) > strtotime($start) && strtotime($end) <= strtotime('23:59')) {
                // Normal shift, same day
                $startTime = $getTime($date, $start);
                $endTime = $getTime($date, $end);

                $minutes = $endTime->diffInMinutes($startTime);
                $hours = floor($minutes / 60);
                $mins = $minutes % 60;

                // Night hours (20:00-06:00)
                $nightStartTime = Carbon::parse($date . ' 20:00');
                $nightEndTime = Carbon::parse($date . ' 06:00')->addDay();
                $nightMinutes = 0;
                if ($startTime->lt($nightEndTime) && $endTime->gt($nightStartTime)) {
                    $overlapStart = max($startTime, $nightStartTime);
                    $overlapEnd = min($endTime, $nightEndTime);
                    $nightMinutes = $overlapEnd->diffInMinutes($overlapStart);
                }

                $holidayHours = $isHoliday ? $minutes : 0;
                $holidayNightHours = $isHoliday ? $nightMinutes : 0;

                if (!isset($dailyHours[$date])) {
                    $dailyHours[$date] = ['date' => $date, 'shifts' => []];
                }
                $dailyHours[$date]['shifts'][] = [
                    'start_time' => $start,
                    'end_time' => $end,
                    'type' => 'midnight_phone',
                    'sick_leave_status' => '',
                    'is_approved_sick_leave' => false,
                    'original_hours' => $hours,
                    'original_minutes' => $mins,
                    'hours' => $hours,
                    'minutes' => $mins,
                    'holiday_hours' => floor($holidayHours / 60),
                    'holiday_minutes' => $holidayHours % 60,
                    'night_hours' => floor($nightMinutes / 60),
                    'night_minutes' => $nightMinutes % 60,
                    'holiday_night_hours' => floor($holidayNightHours / 60),
                    'holiday_night_minutes' => $holidayNightHours % 60,
                ];
            } else {
                // Crosses midnight, split into two shifts

                // First part: start -> 00:00 on current day
                $startTime = $getTime($date, $start);
                $endTime = Carbon::parse($date)->addDay()->setTime(0, 0);
                $minutes = $endTime->diffInMinutes($startTime);
                $hours = floor($minutes / 60);
                $mins = $minutes % 60;

                // Night hours (20:00-06:00)
                $nightStartTime = Carbon::parse($date . ' 20:00');
                $nightEndTime = Carbon::parse($date . ' 06:00')->addDay();
                $nightMinutes = 0;
                if ($startTime->lt($nightEndTime) && $endTime->gt($nightStartTime)) {
                    $overlapStart = max($startTime, $nightStartTime);
                    $overlapEnd = min($endTime, $nightEndTime);
                    $nightMinutes = $overlapEnd->diffInMinutes($overlapStart);
                }

                $holidayHours = $isHoliday ? $minutes : 0;
                $holidayNightHours = $isHoliday ? $nightMinutes : 0;

                if (!isset($dailyHours[$date])) {
                    $dailyHours[$date] = ['date' => $date, 'shifts' => []];
                }
                $dailyHours[$date]['shifts'][] = [
                    'start_time' => $start,
                    'end_time' => '00:00',
                    'type' => 'midnight_phone',
                    'sick_leave_status' => '',
                    'is_approved_sick_leave' => false,
                    'original_hours' => $hours,
                    'original_minutes' => $mins,
                    'hours' => $hours,
                    'minutes' => $mins,
                    'holiday_hours' => floor($holidayHours / 60),
                    'holiday_minutes' => $holidayHours % 60,
                    'night_hours' => floor($nightMinutes / 60),
                    'night_minutes' => $nightMinutes % 60,
                    'holiday_night_hours' => floor($holidayNightHours / 60),
                    'holiday_night_minutes' => $holidayNightHours % 60,
                ];

                // Second part: 00:00 -> end on next day
                $nextDate = Carbon::parse($date)->addDay()->format('Y-m-d');
                $isHolidayNext = $this->isHoliday(Carbon::parse($nextDate));
                $startTime2 = $getTime($nextDate, '00:00');
                $endTime2 = $getTime($nextDate, $end);
                $minutes2 = $endTime2->diffInMinutes($startTime2);
                $hours2 = floor($minutes2 / 60);
                $mins2 = $minutes2 % 60;

                // Night hours (20:00-06:00) for next day
                $nightMinutes2 = 0;

                // 1. 20:00-23:59 on nextDate
                $nightStartEvening2 = Carbon::parse($nextDate . ' 20:00');
                $nightEndMorning2 = Carbon::parse($nextDate)->addDay()->setTime(6, 0);

                // Overlap with 20:00-06:00 (spanning two days)
                if ($startTime2->lt($nightEndMorning2) && $endTime2->gt($nightStartEvening2)) {
                    $overlapStart2 = max($startTime2, $nightStartEvening2);
                    $overlapEnd2 = min($endTime2, $nightEndMorning2);
                    if ($overlapStart2 < $overlapEnd2) {
                        $nightMinutes2 += $overlapEnd2->diffInMinutes($overlapStart2);
                    }
                }

                // 2. 00:00-06:00 on nextDate (for early morning shifts)
                $nightStartMorning2 = Carbon::parse($nextDate . ' 00:00');
                $nightEndMorning2 = Carbon::parse($nextDate . ' 06:00');
                if ($startTime2->lt($nightEndMorning2) && $endTime2->gt($nightStartMorning2)) {
                    $overlapStart2 = max($startTime2, $nightStartMorning2);
                    $overlapEnd2 = min($endTime2, $nightEndMorning2);
                    if ($overlapStart2 < $overlapEnd2) {
                        $nightMinutes2 += $overlapEnd2->diffInMinutes($overlapStart2);
                    }
                }

                $holidayHours2 = $isHolidayNext ? $minutes2 : 0;
                $holidayNightHours2 = $isHolidayNext ? $nightMinutes2 : 0;

                if (!isset($dailyHours[$nextDate])) {
                    $dailyHours[$nextDate] = ['date' => $nextDate, 'shifts' => []];
                }
                $dailyHours[$nextDate]['shifts'][] = [
                    'start_time' => '00:00',
                    'end_time' => $end,
                    'type' => 'midnight_phone',
                    'sick_leave_status' => '',
                    'is_approved_sick_leave' => false,
                    'original_hours' => $hours2,
                    'original_minutes' => $mins2,
                    'hours' => $hours2,
                    'minutes' => $mins2,
                    'holiday_hours' => floor($holidayHours2 / 60),
                    'holiday_minutes' => $holidayHours2 % 60,
                    'night_hours' => floor($nightMinutes2 / 60),
                    'night_minutes' => $nightMinutes2 % 60,
                    'holiday_night_hours' => floor($holidayNightHours2 / 60),
                    'holiday_night_minutes' => $holidayNightHours2 % 60,
                ];
            }
        }

        // Now sort by date again to ensure correct order
        ksort($dailyHours);

        return view('staffs.op-hours-report', compact(
            'staff',
            'year',
            'month',
            'dailyHours',
            'sickLeaveStatuses',
            'sickLeaveHours', // Add the new sick leave hours array
            'missingHours',
            // 'midnightPhoneHours' // Add midnight phone hours to the view
        ));
    }

    private function processDefaultStaffHours(Request $request, StaffUser $staff, $staffId)
    {
        $midnightPhoneHours = [];
        // Get selected year and month, default to current
        $year = $request->input('year', date('Y'));
        $month = $request->input('month', date('n'));

        // Create date range for the selected month
        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth();

        // Get sick leave statuses first
        $sickLeaveStatuses = SupervisorSickLeaves::whereIn('staff_id', [$staffId])
            ->where(function ($query) use ($startDate, $endDate) {
                // Check if either start_date or end_date falls within the date range
                // Or if start_date is before the range and end_date is after (spanning the entire month)
                $query->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhereBetween('end_date', [$startDate, $endDate])
                    ->orWhere(function ($q) use ($startDate, $endDate) {
                        $q->where('start_date', '<=', $startDate)
                            ->where('end_date', '>=', $endDate);
                    });
            })
            ->get();

        // Process sick leaves into a dedicated array
        $sickLeaveHours = [];
        foreach ($sickLeaveStatuses as $sickLeave) {
            $sickLeaveStartDate = Carbon::parse($sickLeave->start_date);
            $sickLeaveEndDate = Carbon::parse($sickLeave->end_date);

            // Determine status message
            $statusMessage = '';
            switch ($sickLeave->status) {
                case '0':
                    $statusMessage = 'Sick Leave - Pending from supervisor';
                    break;
                case '1':
                    $statusMessage = 'Sick Leave - Pending from HR';
                    break;
                case '2':
                    $statusMessage = 'Sick Leave - Approved';
                    break;
                case '3':
                    $statusMessage = 'Sick Leave - Rejected';
                    break;
                case '4':
                    $statusMessage = 'Sick Leave - Cancelled';
                    break;
                default:
                    $statusMessage = 'Sick Leave - Unknown status';
            }

            // Create entries for each date in the sick leave range that falls within the month
            $currentDate = max($sickLeaveStartDate, Carbon::createFromDate($year, $month, 1)->startOfMonth());
            $rangeEndDate = min($sickLeaveEndDate, Carbon::createFromDate($year, $month, 1)->endOfMonth());

            while ($currentDate <= $rangeEndDate) {
                $dateKey = $currentDate->format('Y-m-d');

                if (!isset($sickLeaveHours[$dateKey])) {
                    $sickLeaveHours[$dateKey] = [];
                }

                $sickLeaveHours[$dateKey][] = [
                    'type' => 'sick_leave',
                    'status' => $sickLeave->status,
                    'status_message' => $statusMessage,
                    'description' => $sickLeave->description ?? '',
                    'start_date' => $sickLeave->start_date,
                    'end_date' => $sickLeave->end_date,
                    'supervisor_remark' => $sickLeave->supervisor_remark ?? '',
                    'admin_remark' => $sickLeave->admin_remark ?? '',
                    'image' => $sickLeave->image ?? '',
                    'created_at' => $sickLeave->created_at
                ];

                $currentDate->addDay();
            }
        }

        // Group sick leave statuses for regular hour processing (keep existing logic)
        $sickLeaveStatusesGrouped = $sickLeaveStatuses->groupBy(function ($item) {
            return $item->staff_id . '_' . Carbon::parse($item->start_date)->format('Y-m-d');
        });

        // Get regular monthly hours
        $staffMonthlyData = StaffMonthlyHours::where('staff_id', $staffId)
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        // Get missing hours for this staff member
        $missingHours = StaffMissingHours::where('staff_id', $staffId)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date')
            ->get();

        $dailyHours = [];

        // Process regular hours
        foreach ($staffMonthlyData as $record) {
            $dateKey = $record->date->format('Y-m-d');
            $isHoliday = $this->isHoliday($record->date);

            $shifts = is_array($record->hours_data)
                ? $record->hours_data
                : json_decode($record->hours_data, true);

            if (!empty($shifts)) {
                $dailyHours[$dateKey] = [
                    'date' => $dateKey,
                    'shifts' => [],
                    'is_approved' => $record->is_approved // Add approval status
                ];

                // Process each shift
                foreach ($shifts as $shift) {
                    if (isset($shift['start_time']) && isset($shift['end_time'])) {
                        $startTime = Carbon::createFromFormat('H:i', $shift['start_time']);
                        $endTime = Carbon::createFromFormat('H:i', $shift['end_time']);

                        // Check for sick leave status first
                        $slStatus = '';
                        $hideHoursForSickLeave = false;

                        if (isset($shift['type']) && $shift['type'] === 'SL') {
                            $sickLeaveKey = $staffId . '_' . $dateKey;

                            if (isset($sickLeaveStatusesGrouped[$sickLeaveKey])) {
                                $sickLeave = $sickLeaveStatusesGrouped[$sickLeaveKey]->first();

                                if ($sickLeave) {
                                    $status = $sickLeave->status;
                                    // Hide hours if sick leave status is approved (2), rejected (3), or cancelled (4)
                                    $hideHoursForSickLeave = in_array($status, ['2', '3', '4']);

                                    switch ($status) {
                                        case '0':
                                            $slStatus = '(Sick Leave - Pending from supervisor)';
                                            break;
                                        case '1':
                                            $slStatus = '(Sick Leave - Pending from HR)';
                                            break;
                                        case '2':
                                            $slStatus = '(Sick Leave - Approved)';
                                            break;
                                        case '3':
                                            $slStatus = '(Sick Leave - Rejected)';
                                            break;
                                        case '4':
                                            $slStatus = '(Sick Leave - Cancelled)';
                                            break;
                                    }
                                }
                            }
                        }

                        // Calculate hours
                        $minutes = 0;
                        $nightMinutes = 0;
                        $holidayHours = 0;
                        $holidayNightHours = 0;

                        if (!$hideHoursForSickLeave) {
                            $minutes = $endTime->diffInMinutes($startTime);
                            $nightMinutes = $this->calculateNightHours($startTime, $endTime);
                            $holidayHours = $isHoliday ? $minutes : 0;
                            $holidayNightHours = $isHoliday ? $nightMinutes : 0;
                        }

                        $dailyHours[$dateKey]['shifts'][] = [
                            'start_time' => $shift['start_time'],
                            'end_time' => $shift['end_time'],
                            'type' => $shift['type'] ?? 'normal',
                            'sick_leave_status' => $slStatus,
                            'is_approved_sick_leave' => $hideHoursForSickLeave,
                            'original_hours' => floor($endTime->diffInMinutes($startTime) / 60),
                            'original_minutes' => $endTime->diffInMinutes($startTime) % 60,
                            'hours' => $hideHoursForSickLeave ? 0 : floor($minutes / 60),
                            'minutes' => $hideHoursForSickLeave ? 0 : ($minutes % 60),
                            'holiday_hours' => $hideHoursForSickLeave ? 0 : floor($holidayHours / 60),
                            'holiday_minutes' => $hideHoursForSickLeave ? 0 : ($holidayHours % 60),
                            'night_hours' => $hideHoursForSickLeave ? 0 : floor($nightMinutes / 60),
                            'night_minutes' => $hideHoursForSickLeave ? 0 : ($nightMinutes % 60),
                            'holiday_night_hours' => $hideHoursForSickLeave ? 0 : floor($holidayNightHours / 60),
                            'holiday_night_minutes' => $holidayNightHours % 60
                        ];
                    }
                }
            }
        }

        // Add missing hours to the dailyHours array (keep existing logic)
        foreach ($missingHours as $missingHour) {
            $dateKey = $missingHour->date->format('Y-m-d');
            $isHoliday = $this->isHoliday($missingHour->date);

            // Create the date entry if it doesn't exist
            if (!isset($dailyHours[$dateKey])) {
                $dailyHours[$dateKey] = [
                    'date' => $dateKey,
                    'shifts' => []
                ];
            }

            // Format start and end time - handle different possible formats
            if (strpos($missingHour->start_time, ':') !== false && strlen($missingHour->start_time) <= 5) {
                // Format is already H:i
                $startTime = Carbon::createFromFormat('H:i', $missingHour->start_time);
                $endTime = Carbon::createFromFormat('H:i', $missingHour->end_time);
            } else {
                // Full datetime format (Y-m-d H:i:s)
                $startTime = Carbon::parse($missingHour->start_time);
                $endTime = Carbon::parse($missingHour->end_time);
            }

            // Handle overnight shifts
            if ($endTime->lt($startTime)) {
                $endTime->addDay();
            }

            // Calculate total regular hours
            $minutes = $endTime->diffInMinutes($startTime);

            // Calculate night hours (20:00-06:00) properly
            // Create night time range for proper calculation
            $nightStartTime = Carbon::parse($missingHour->date->format('Y-m-d') . ' 20:00');
            $nightEndTime = Carbon::parse($missingHour->date->format('Y-m-d') . ' 06:00')->addDay();

            // Initialize night minutes
            $nightMinutes = 0;

            // Check if shift overlaps with night hours
            if ($startTime->lt($nightEndTime) && $endTime->gt($nightStartTime)) {
                // Calculate the overlap between shift and night hours
                $overlapStart = max($startTime, $nightStartTime);
                $overlapEnd = min($endTime, $nightEndTime);
                $nightMinutes = $overlapEnd->diffInMinutes($overlapStart);
            }

            // Calculate holiday hours
            $holidayHours = $isHoliday ? $minutes : 0;
            $holidayNightHours = $isHoliday ? $nightMinutes : 0;

            // Add missing hour as a shift with the proper formatting
            $dailyHours[$dateKey]['shifts'][] = [
                'start_time' => $startTime->format('H:i'),
                'end_time' => $endTime->format('H:i'),
                'type' => 'missing', // Special type for missing hours
                'reason' => $missingHour->reason,
                'sick_leave_status' => '',
                'is_approved_sick_leave' => false,
                'missing_hour_id' => $missingHour->id,
                'missing_hour_reason' => $missingHour->reason,
                'missing_hour_status' => $missingHour->status ?? 'pending', // Default to pending if no status
                'missing_hour_applied_date' => $missingHour->applied_date,
                'missing_hour_created_by' => $missingHour->created_by,
                'original_hours' => floor($minutes / 60),
                'original_minutes' => $minutes % 60,
                'hours' => floor($minutes / 60),
                'minutes' => $minutes % 60,
                'holiday_hours' => floor($holidayHours / 60),
                'holiday_minutes' => $holidayHours % 60,
                'night_hours' => floor($nightMinutes / 60),
                'night_minutes' => $nightMinutes % 60,
                'holiday_night_hours' => floor($holidayNightHours / 60),
                'holiday_night_minutes' => $holidayNightHours % 60
            ];
        }

        // Sort by date
        ksort($dailyHours);

        // Further sort shifts within each date by start time
        foreach ($dailyHours as $dateKey => &$dayData) {
            if (isset($dayData['shifts']) && is_array($dayData['shifts'])) {
                usort($dayData['shifts'], function ($a, $b) {
                    return strcmp($a['start_time'], $b['start_time']);
                });
            }
        }

        return view('staffs.hours-report', compact(
            'staff',
            'year',
            'month',
            'dailyHours',
            'sickLeaveStatuses',
            'sickLeaveHours',
            'missingHours',
            'midnightPhoneHours' // Add midnight phone hours to the view
        ));
    }

    private function calculateNightHours($startTime, $endTime)
    {
        // Handle shifts crossing midnight
        if ($startTime->format('H:i') > $endTime->format('H:i')) {
            $endTime->addDay();
        }

        $totalNightMinutes = 0;

        // Create base date reference (without time) to use for all calculations
        $baseDate = $startTime->copy()->startOfDay();

        // Define night period (20:00 today to 06:00 tomorrow)
        $nightStartEvening = $baseDate->copy()->setTime(20, 0);
        $nightEndMorning = $baseDate->copy()->addDay()->setTime(6, 0);

        // Check overlap with night hours (20:00-06:00)
        if ($startTime->lt($nightEndMorning) && $endTime->gt($nightStartEvening)) {
            // Calculate overlap
            $overlapStart = max($startTime, $nightStartEvening);
            $overlapEnd = min($endTime, $nightEndMorning);

            if ($overlapStart->lt($overlapEnd)) {
                $totalNightMinutes = $overlapEnd->diffInMinutes($overlapStart);
            }
        }

        // For early morning shifts check if they start before 06:00 on their day
        if ($startTime->format('H') < 6) {
            $morningNightEnd = $baseDate->copy()->setTime(6, 0);
            if ($startTime->lt($morningNightEnd) && $endTime->gt($startTime)) {
                $morningOverlapEnd = min($endTime, $morningNightEnd);
                $morningNightMinutes = $morningOverlapEnd->diffInMinutes($startTime);
                $totalNightMinutes += $morningNightMinutes;
            }
        }

        return $totalNightMinutes;
    }

    public function changePassword(Request $request, $id)
    {
        $validatedData = $request->validate([
            'password' => 'required|string|min:4|confirmed',
        ]);

        $tourGuide = StaffUser::findOrFail($id);
        if ($tourGuide->user) {
            $tourGuide->user->update([
                'password' => Hash::make($validatedData['password']),
            ]);
            return redirect()->back()->with('success', 'Password changed successfully.');
        } else {
            return redirect()->back()->with('error', 'Associated user not found for this tour guide.');
        }
    }

    private function isHoliday(Carbon $date)
    {
        return $date->isSunday() || Holiday::where('holiday_date', $date->format('Y-m-d'))->exists();
    }

    public function destroy($id)
    {
        $staffUser = StaffUser::findOrFail($id);
        $userId = $staffUser->user_id; // Get the associated user ID

        // Delete the StaffUser record
        $staffUser->delete();

        // Delete the associated User record if it exists
        if ($userId) {
            $user = User::find($userId);
            if ($user) {
                $user->delete();
            }
        }

        return redirect()->route('tour-guides.staff-index')->with('success', 'Staff user deleted successfully.');
    }

    public function edit($id)
    {
        $staffUser = StaffUser::findOrFail($id);
        $supervisors = User::where('role', 'supervisor')->get();
        return view('staffs.edit', compact('staffUser', 'supervisors'));
    }

    public function update(Request $request, $id)
    {
        $staffUser = StaffUser::findOrFail($id);

        // Update staff user
        $staffUser->update($request->all());

        // ... other validation rules
        $validated = $request->validate([
            'supervisors' => 'array',
            'supervisors.*' => 'exists:users,id'
        ]);

        // ... other updates
        // $staffUser->supervisors()->sync($request->supervisors);

        // Update associated user's intern status
        if ($staffUser->user) {
            $staffUser->user->update([
                'is_intern' => $request->input('is_intern', 0)
            ]);
        }

        return redirect()->route('tour-guides.staff-index')->with('success', 'Staff user updated successfully.');
    }

    public function operationsChangePassword(Request $request, $id)
    {
        $validatedData = $request->validate([
            'password' => 'required|string|min:4|confirmed',
        ]);

        $tourGuide = User::findOrFail($id);
        if ($tourGuide) {
            $tourGuide->update([
                'password' => Hash::make($validatedData['password']),
            ]);
            return redirect()->back()->with('success', 'Password changed successfully.');
        } else {
            return redirect()->back()->with('error', 'Associated user not found for this tour guide.');
        }
    }

    public function operationsDestroy($id)
    {
        $staffUser = User::findOrFail($id);
        $staffUser->delete();
        return redirect()->back()->with('success', 'Staff user deleted successfully.');
    }

    public function hrAssistantDestroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        $hrAssistant = HrAssistants::where('user_id', $id)->first();
        $hrAssistant->delete();
        return redirect()->back()->with('success', 'Guide Supervisor deleted successfully.');
    }

    public function teamLeadsDestroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        $teamLead = TeamLeads::where('user_id', $id)->first();
        $teamLead->delete();
        return redirect()->back()->with('success', 'Bus Driver Supervisor deleted successfully.');
    }

    public function supervisorsDestroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        $supervisor = Supervisors::where('user_id', $id)->first();
        $supervisor->delete();
        return redirect()->back()->with('success', 'Supervisor deleted successfully.');
    }

    public function operationsEdit($id)
    {
        $user = User::findOrFail($id);
        return view('users.edit', compact('user'));
    }

    public function hrAssistantsEdit($id)
    {
        $hrAssistant = HrAssistants::where('user_id', $id)->first();
        return view('users.hr-assistants-edit', compact('hrAssistant'));
    }

    public function teamLeadsEdit($id)
    {
        $teamLead = TeamLeads::where('user_id', $id)->first();
        return view('users.team-leads-edit', compact('teamLead'));
    }

    public function supervisorsEdit($id)
    {
        $supervisor = Supervisors::where('user_id', $id)->first();
        $staffUsers = StaffUser::where('user_id', $id)->first();

        return view('users.supervisors-edit', compact('supervisor', 'staffUsers'));
    }

    public function operationsUpdate(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $user->update($request->all());

        $user->operation->update($request->all());
        return redirect()->back()->with('success', 'User updated successfully.');
    }

    public function hrAssistantsUpdate(Request $request, $id)
    {
        $hrAssistant = HrAssistants::findOrFail($id);
        $hrAssistant->update($request->all());

        $hrAssistant->user->update([
            'is_intern' => $request->input('is_intern', 0),
            'name' => $request->input('name'),
            'email' => $request->input('email'),
        ]);
        return redirect()->back()->with('success', 'Guide Supervisor updated successfully.');
    }

    public function teamLeadsUpdate(Request $request, $id)
    {
        $teamLead = TeamLeads::findOrFail($id);
        $teamLead->update($request->all());

        $teamLead->user->update([
            'is_intern' => $request->input('is_intern', 0),
            'name' => $request->input('name'),
            'email' => $request->input('email'),
        ]);
        return redirect()->back()->with('success', 'Bus Driver Supervisor updated successfully.');
    }

    public function supervisorsUpdate(Request $request, $id)
    {
        // Validate the department data
        $request->validate([
            'departments' => 'required|array|min:1',
            'departments.*' => 'string',
        ]);

        $supervisor = Supervisors::findOrFail($id);

        // Convert departments array to comma-separated string
        $departmentString = implode(', ', $request->departments);

        // Update supervisor with all request data except departments
        $supervisor->update($request->except('departments'));

        // Then update with the formatted departments string and ensure midnight phone is set
        $supervisor->update([
            'department' => $departmentString,
            'display_midnight_phone' => $request->input('display_midnight_phone', 0),
        ]);

        // Update associated user data
        $supervisor->user->update([
            'is_intern' => $request->input('is_intern', 0),
            'name' => $request->input('name'),
            
            'email' => $request->input('email'),
        ]);

        $staffUser = StaffUser::where('user_id', $supervisor->user_id)->first();
        if ($staffUser) {
            $staffUser->full_name = $request->full_name;
            $staffUser->save();
        }

        return redirect()->back()->with('success', 'Supervisor updated successfully.');
    }

    public function reportStaffHours(Request $request)
    {

        // Get the selected week start date from request, or use current week if not provided
        $selectedDate = $request->input('week', Carbon::now()->format('Y-m-d'));
        $weekStart = Carbon::parse($selectedDate)->startOfWeek();
        $weekEnd = Carbon::parse($selectedDate)->endOfWeek();

        $dates = CarbonPeriod::create($weekStart, $weekEnd);

        // Check user role and allow_report_hours permission
        if (Auth::user()->role == 'admin') {
            // For admin: get ALL staff members from ALL departments
            $staffMembers = StaffUser::all();

            // Get all unique departments for admin
            $supervisorDepartments = StaffUser::distinct()->pluck('department')->filter()->flatMap(function ($dept) {
                return explode(', ', $dept);
            })->map('trim')->unique()->values()->toArray();

            $displayMidnightPhone = true; // Admin can see midnight phone
        } elseif (Auth::user()->role == 'supervisor') {
            // For supervisors: get their department staff
            $supervisor = Supervisors::where('user_id', auth()->id())->first();
            $supervisorDepartments = explode(', ', $supervisor->department);

            // Get staff that belong to any of the supervisor's departments
            $staffMembers = StaffUser::where(function ($query) use ($supervisorDepartments) {
                foreach ($supervisorDepartments as $department) {
                    $query->orWhere('department', 'LIKE', $department)
                        ->orWhere('department', 'LIKE', $department . ',%')
                        ->orWhere('department', 'LIKE', '%, ' . $department)
                        ->orWhere('department', 'LIKE', '%, ' . $department . ',%');
                }
            })->get();

            // Get supervisors from the same departments and add them to the staff collection
            $departmentSupervisors = User::where('role', 'supervisor')
                ->whereHas('supervisorRecord', function ($query) use ($supervisorDepartments) {
                    foreach ($supervisorDepartments as $department) {
                        $query->orWhere('department', 'LIKE', $department)
                            ->orWhere('department', 'LIKE', $department . ',%')
                            ->orWhere('department', 'LIKE', '%, ' . $department)
                            ->orWhere('department', 'LIKE', '%, ' . $department . ',%');
                    }
                })->get();

            // Convert supervisors to StaffUser format and add to collection
            foreach ($departmentSupervisors as $supervisorUser) {
                $existingStaff = StaffUser::where('user_id', $supervisorUser->id)->first();

                if ($existingStaff) {
                    $existingStaff->setAttribute('is_supervisor', true);
                    $existingStaff->setAttribute('supervisor_rank', 1);

                    $staffMembers = $staffMembers->reject(function ($staff) use ($existingStaff) {
                        return $staff->id === $existingStaff->id;
                    });

                    $staffMembers->prepend($existingStaff);
                } else {
                    $newStaff = StaffUser::create([
                        'name' => $supervisorUser->name,
                        'full_name' => $supervisorUser->name,
                        'email' => $supervisorUser->email,
                        'department' => $supervisorDepartments[0],
                        'phone_number' => '',
                        'is_supervisor' => true,
                        'rate' => '',
                        'user_id' => $supervisorUser->id,
                        'color' => "#" . substr(md5(rand()), 0, 6),
                        'allow_report_hours' => 1,
                    ]);

                    $newStaff->setAttribute('is_supervisor', true);
                    $newStaff->setAttribute('supervisor_rank', 1);
                    $staffMembers->prepend($newStaff);
                }
            }

            // Sort the collection to put supervisors first
            $staffMembers = $staffMembers->sortBy(function ($staff) {
                return [
                    $staff->getAttribute('supervisor_rank') ?? 2,
                    $staff->name
                ];
            })->values();

            $displayMidnightPhone = Supervisors::where('user_id', Auth::user()->id)->first()->display_midnight_phone;
        } else {
            // For regular staff users who have allow_report_hours permission
            $currentStaffUser = null;

            // Check if user is a StaffUser with allow_report_hours permission
            if (Auth::user()->staff && Auth::user()->staff->allow_report_hours) {
                $currentStaffUser = Auth::user()->staff;
            }
            // Check if user is a TourGuide with allow_report_hours permission  
            elseif (Auth::user()->tourGuide && Auth::user()->tourGuide->allow_report_hours) {
                $currentStaffUser = Auth::user()->tourGuide;
            }

            if ($currentStaffUser) {
                // User can report their own hours - show only themselves
                $staffMembers = collect([$currentStaffUser]);

                // Get their department(s)
                $supervisorDepartments = explode(', ', $currentStaffUser->department);
                $supervisorDepartments = array_map('trim', $supervisorDepartments);

                // Set midnight phone display to false for regular staff
                $displayMidnightPhone = false;
            } else {
                // User doesn't have permission to report hours
                return redirect()->back()->with('error', 'You do not have permission to report hours.');
            }
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
                return [
                    'hours_data' => $item->hours_data,
                    'is_approved' => $item->is_approved // Add this line
                ];
            })->toArray();
        }

        // Filter StaffHoursDetails by supervisor's departments (or all for admin)
        $receptionData = [];
        $midnightPhoneData = [];

        if (Auth::user()->role == 'admin') {
            // Admin sees all departments
            $staffHoursDetails = StaffHoursDetails::whereBetween('date', [$weekStart, $weekEnd])->get();
        } else {
            // Supervisors and staff see only their departments
            $staffHoursDetails = StaffHoursDetails::whereBetween('date', [$weekStart, $weekEnd])
                ->where(function ($query) use ($supervisorDepartments) {
                    foreach ($supervisorDepartments as $department) {
                        $query->orWhere('department', $department);
                    }
                })
                ->get();
        }

        foreach ($staffHoursDetails as $detail) {
            $dateString = $detail->date->format('Y-m-d');
            $receptionData[$dateString] = $detail->reception;
            $midnightPhoneData[$dateString] = $detail->midnight_phone[0] ?? null;
        }

        $holidays = Holiday::whereBetween('holiday_date', [$weekStart, $weekEnd])->pluck('holiday_date');

        // Sick leave processing
        $sickLeaveStatuses = [];
        $sickLeaves = SupervisorSickLeaves::whereIn('staff_id', $staffMembers->pluck('id'))
            ->where(function ($query) use ($weekStart, $weekEnd) {
                $query->whereBetween('start_date', [$weekStart, $weekEnd])
                    ->orWhereBetween('end_date', [$weekStart, $weekEnd])
                    ->orWhere(function ($q) use ($weekStart, $weekEnd) {
                        $q->where('start_date', '<=', $weekStart)
                            ->where('end_date', '>=', $weekEnd);
                    });
            })
            ->get();

        // Process each sick leave to cover all affected dates
        foreach ($sickLeaves as $sickLeave) {
            $startDate = Carbon::parse($sickLeave->start_date);
            $endDate = Carbon::parse($sickLeave->end_date);

            $currentDate = max($startDate, $weekStart);
            $rangeEndDate = min($endDate, $weekEnd);

            while ($currentDate <= $rangeEndDate) {
                $dateKey = $currentDate->format('Y-m-d');
                $staffDateKey = $sickLeave->staff_id . '_' . $dateKey;

                // Store the sick leave object directly, not in an array
                $sickLeaveStatuses[$staffDateKey] = $sickLeave;

                $currentDate->addDay();
            }
        }

        $staffByDepartment = [];
        foreach ($staffMembers as $staff) {
            $departments = explode(', ', $staff->department); // Fixed: was 'exploded'
            foreach ($departments as $dept) {
                $dept = trim($dept);
                if (Auth::user()->role == 'admin' || in_array($dept, $supervisorDepartments)) {
                    if (!isset($staffByDepartment[$dept])) {
                        $staffByDepartment[$dept] = collect();
                    }
                    $staffByDepartment[$dept]->push($staff);
                }
            }
        }

        // Add this line to create a flattened collection for the dropdown:
        $allStaffFlattened = collect($staffByDepartment)->flatten();

        return view('staffs.report-staff-hours', compact(
            'selectedDate',
            'dates',
            'staffByDepartment',
            'allStaffFlattened',
            'supervisorDepartments',
            'staffHours',
            'holidays',
            'receptionData',
            'midnightPhoneData',
            'displayMidnightPhone',
            'sickLeaveStatuses'
        ));
    }

    /**
     * Staff+Guide Hours Report - Day-by-day report with combined calculation logic
     */
    public function StaffGuideHoursReport(Request $request)
    {
        // Get the current user's staff info
        $currentStaff = StaffUser::where('user_id', Auth::user()->id)->first();
        
        if (!$currentStaff) {
            return redirect()->back()->with('error', 'Staff member not found.');
        }
        
        // Check if this user is also a guide (dual-role)
        $tourGuide = \App\Models\TourGuide::where('user_id', Auth::user()->id)->where('is_hidden', 0)->first();
        
        if (!$tourGuide) {
            return redirect()->back()->with('error', 'You are not registered as a guide. This report is only for Staff+Guide users.');
        }
        
        // Get selected year and month, default to current
        $currentDate = Carbon::now();
        $year = $request->input('year', $currentDate->year);
        $month = $request->input('month', $currentDate->month);
        $date = Carbon::createFromDate($year, $month, 1);
        
        // Get holidays for the selected month
        $holidays = \App\Models\Holiday::whereYear('holiday_date', $year)
            ->whereMonth('holiday_date', $month)
            ->pluck('holiday_date')
            ->map(function ($date) {
                return Carbon::parse($date)->format('Y-m-d');
            })
            ->toArray();
        
        // Generate day-by-day hours with combined Staff+Guide logic
        $dailyHours = $this->generateStaffGuideDailyHours($currentStaff, $tourGuide, $year, $month, $holidays);
        
        return view('staff.staff-guide-hours-report', compact(
            'currentStaff',
            'tourGuide',
            'year',
            'month',
            'holidays',
            'dailyHours',
            'date'
        ));
    }
    
    /**
     * Generate day-by-day hours for Staff+Guide users with combined calculation
     */
    private function generateStaffGuideDailyHours($currentStaff, $tourGuide, $year, $month, $holidays)
    {
        $startDate = Carbon::createFromDate($year, $month, 1);
        $endDate = $startDate->copy()->endOfMonth();
        $dailyHours = [];
        
        // Get combined controller to use its overlap calculation logic
        $combinedController = app(\App\Http\Controllers\CombinedReportController::class);
        
        // Get staff working periods for the month
        $staffWorkingPeriods = $this->getStaffWorkingPeriodsForDaily($currentStaff->id, $startDate);
        
        // Get guide working periods for the month
        $guideWorkingPeriods = $this->getGuideWorkingPeriodsForDaily($tourGuide->id, $startDate);
        
        // Get missing hours for both staff and guide
        $staffMissingHours = $this->getStaffMissingHours($currentStaff->id, $year, $month);
        $guideMissingHours = $this->getGuideMissingHours($tourGuide->id, $year, $month);
        
        // Get sick leaves for both staff and guide
        $staffSickLeaves = $this->getStaffSickLeaves($currentStaff->id, $year, $month);
        $guideSickLeaves = $this->getGuideSickLeaves($tourGuide->id, $year, $month);
        
        // Process each day in the month
        for ($date = $startDate->copy(); $date <= $endDate; $date->addDay()) {
            $dateStr = $date->format('Y-m-d');
            $dayData = ['shifts' => [], 'is_approved' => 1];
            
            // Get staff shifts for this date
            $staffShiftsOnDate = collect($staffWorkingPeriods)->where('date', $dateStr);
            
            // Get guide periods for this date
            $guidePeriodsOnDate = collect($guideWorkingPeriods)->filter(function($period) use ($dateStr) {
                $guideDate = Carbon::parse($period->guide_start_time)->format('Y-m-d');
                return $guideDate === $dateStr;
            });
            
            // Process staff shifts
            foreach ($staffShiftsOnDate as $staffShift) {
                $startTime = Carbon::parse($staffShift['start_time']);
                $endTime = Carbon::parse($staffShift['end_time']);
                
                // Check if this is an on-call shift by checking the type field in original hours_data
                $isOnCallShift = isset($staffShift['original_shift_type']) && $staffShift['original_shift_type'] === 'on_call';
                
                // Apply divide-by-3 rule for on-call/midnight hours (1 hour = 20 minutes)
                $totalMinutes = $staffShift['total_minutes'];
                $nightMinutes = $staffShift['night_minutes'];
                $holidayNightMinutes = $staffShift['holiday_night_minutes'];
                
                if ($isOnCallShift) {
                    // Divide by 3 for on-call hours (1 hour = 20 minutes)
                    $totalMinutes = floor($totalMinutes / 3);
                    $nightMinutes = floor($nightMinutes / 3);
                    $holidayNightMinutes = floor($holidayNightMinutes / 3);
                }
                
                // Holiday minutes should be calculated based on the adjusted total minutes for on-call shifts
                $holidayMinutes = $staffShift['holiday_minutes'];
                if ($holidayMinutes > 0 && $isOnCallShift) {
                    $holidayMinutes = floor($holidayMinutes / 3);
                }
                
                $dayData['shifts'][] = [
                    'start_time' => $startTime->format('H:i'),
                    'end_time' => $endTime->format('H:i'),
                    'hours' => floor($totalMinutes / 60),
                    'minutes' => $totalMinutes % 60,
                    'holiday_hours' => floor($holidayMinutes / 60),
                    'holiday_minutes' => $holidayMinutes % 60,
                    'night_hours' => floor($nightMinutes / 60),
                    'night_minutes' => $nightMinutes % 60,
                    'holiday_night_hours' => floor($holidayNightMinutes / 60),
                    'holiday_night_minutes' => $holidayNightMinutes % 60,
                    'type' => $isOnCallShift ? 'Staff (On-call/Midnight)' : 'Staff',
                    'reason' => $isOnCallShift ? 'Staff Work (On-call/Midnight)' : 'Staff Work'
                ];
            }
            
            // Process ALL guide periods (both overlapping and non-overlapping)
            foreach ($guidePeriodsOnDate as $guidePeriod) {
                $guideStart = Carbon::parse($guidePeriod->guide_start_time);
                $guideEnd = Carbon::parse($guidePeriod->guide_end_time);
                
                // Calculate total guide minutes
                $totalGuideMinutes = $guideEnd->diffInMinutes($guideStart);
                
                // Calculate non-overlapping portions
                $nonOverlappingMinutes = $this->calculateDailyNonOverlappingMinutes(
                    $guideStart, 
                    $guideEnd, 
                    $staffShiftsOnDate->toArray()
                );
                
                // Determine if this period overlaps with staff hours
                $overlappingMinutes = $totalGuideMinutes - $nonOverlappingMinutes;
                $isOverlapping = $overlappingMinutes > 0;
                
                // Show all guide periods, but only count non-overlapping minutes for calculations
                $displayMinutes = $nonOverlappingMinutes; // Only non-overlapping minutes count
                $displayType = $isOverlapping ? 'Guide (Overlapping)' : 'Guide (Non-overlapping)';
                
                // Calculate night hours for the non-overlapping portions only
                $nightHours = $this->calculateDailyNightHours($guideStart, $guideEnd, $staffShiftsOnDate->toArray(), $holidays);
                
                // Calculate holiday hours for guide periods on holidays
                $isHoliday = in_array($dateStr, $holidays);
                $holidayMinutes = $isHoliday ? $displayMinutes : 0; // Non-overlapping minutes on holidays
                
                $dayData['shifts'][] = [
                    'start_time' => $guideStart->format('H:i'),
                    'end_time' => $guideEnd->format('H:i'),
                    'hours' => floor($displayMinutes / 60),
                    'minutes' => $displayMinutes % 60,
                    'holiday_hours' => floor($holidayMinutes / 60),
                    'holiday_minutes' => $holidayMinutes % 60,
                    'night_hours' => floor($nightHours['night'] / 60),
                    'night_minutes' => $nightHours['night'] % 60,
                    'holiday_night_hours' => floor($nightHours['holiday_night'] / 60),
                    'holiday_night_minutes' => $nightHours['holiday_night'] % 60,
                    'type' => $displayType,
                    'reason' => 'Guide Work - ' . ($guidePeriod->event ? ($guidePeriod->event->summary ?? 'Tour') : 'Tour') . 
                               ($isOverlapping ? ' (Overlap: ' . floor($overlappingMinutes / 60) . 'h ' . ($overlappingMinutes % 60) . 'm)' : '')
                ];
            }
            
            // Process staff missing hours for this date
            $staffMissingOnDate = $staffMissingHours->filter(function($missing) use ($dateStr) {
                return Carbon::parse($missing->date)->format('Y-m-d') === $dateStr;
            });
            
            foreach ($staffMissingOnDate as $missing) {
                $startTime = Carbon::parse($missing->start_time);
                $endTime = Carbon::parse($missing->end_time);
                $totalMinutes = $endTime->diffInMinutes($startTime);
                
                // Check if missing hours date is a holiday
                $isHoliday = in_array($dateStr, $holidays);
                $nightMinutes = $this->calculateStaffNightMinutes($startTime, $endTime);
                
                $dayData['shifts'][] = [
                    'start_time' => $startTime->format('H:i'),
                    'end_time' => $endTime->format('H:i'),
                    'hours' => floor($totalMinutes / 60),
                    'minutes' => $totalMinutes % 60,
                    'holiday_hours' => $isHoliday ? floor($totalMinutes / 60) : 0,
                    'holiday_minutes' => $isHoliday ? ($totalMinutes % 60) : 0,
                    'night_hours' => floor($nightMinutes['night'] / 60),
                    'night_minutes' => $nightMinutes['night'] % 60,
                    'holiday_night_hours' => floor($nightMinutes['holiday_night'] / 60),
                    'holiday_night_minutes' => $nightMinutes['holiday_night'] % 60,
                    'type' => 'Staff (Missing Hours)',
                    'reason' => 'Missing Hours - ' . ($missing->reason ?? 'Staff Work')
                ];
            }
            
            // Process guide missing hours for this date
            $guideMissingOnDate = $guideMissingHours->filter(function($missing) use ($dateStr) {
                return Carbon::parse($missing->date)->format('Y-m-d') === $dateStr;
            });
            
            foreach ($guideMissingOnDate as $missing) {
                $startTime = Carbon::parse($missing->start_time);
                $endTime = Carbon::parse($missing->end_time);
                $totalMinutes = $endTime->diffInMinutes($startTime);
                
                // Use the hours from the database (already calculated)
                $normalHours = $missing->normal_hours * 60; // Convert to minutes
                $normalNightHours = $missing->normal_night_hours * 60;
                $holidayHours = $missing->holiday_hours * 60;
                $holidayNightHours = $missing->holiday_night_hours * 60;
                
                $dayData['shifts'][] = [
                    'start_time' => $startTime->format('H:i'),
                    'end_time' => $endTime->format('H:i'),
                    'hours' => floor($normalHours / 60),
                    'minutes' => $normalHours % 60,
                    'holiday_hours' => floor($holidayHours / 60),
                    'holiday_minutes' => $holidayHours % 60,
                    'night_hours' => floor($normalNightHours / 60),
                    'night_minutes' => $normalNightHours % 60,
                    'holiday_night_hours' => floor($holidayNightHours / 60),
                    'holiday_night_minutes' => $holidayNightHours % 60,
                    'type' => 'Guide (Missing Hours)',
                    'reason' => 'Missing Hours - ' . ($missing->tour_name ?? 'Guide Work')
                ];
            }
            
            // Process sick leaves for this date and store separately
            $sickLeavesOnDate = [];
            $hasStaffSickLeave = false;
            $hasGuideSickLeave = false;
            
            // Staff sick leaves (handle date ranges)
            $staffSickLeavesOnDate = $staffSickLeaves->filter(function($sickLeave) use ($dateStr) {
                $currentDate = Carbon::parse($dateStr);
                $startDate = Carbon::parse($sickLeave->start_date)->startOfDay();
                $endDate = Carbon::parse($sickLeave->end_date)->endOfDay();
                
                // Check if current date falls within the sick leave period
                return $currentDate->between($startDate, $endDate);
            });
            
            foreach ($staffSickLeavesOnDate as $sickLeave) {
                $hasStaffSickLeave = true; // Flag that we have staff sick leave
                
                // For staff sick leaves, assume full day (8 hours) per day  
                $sickLeavesOnDate[] = [
                    'hours' => 8,
                    'minutes' => 0,
                    'type' => 'Staff Sick Leave',
                    'reason' => $sickLeave->description ?? 'Sick Leave'
                ];
            }
            
            // Guide sick leaves 
            $guideSickLeavesOnDate = $guideSickLeaves->filter(function($sickLeave) use ($dateStr) {
                return Carbon::parse($sickLeave->date)->format('Y-m-d') === $dateStr;
            });
            
            foreach ($guideSickLeavesOnDate as $sickLeave) {
                $hasGuideSickLeave = true; // Flag that we have guide sick leave
                $startTime = Carbon::parse($sickLeave->start_time);
                $endTime = Carbon::parse($sickLeave->end_time);
                $duration = $endTime->diffInMinutes($startTime);
                
                $sickLeavesOnDate[] = [
                    'hours' => floor($duration / 60),
                    'minutes' => $duration % 60,
                    'type' => 'Guide Sick Leave',
                    'reason' => $sickLeave->tour_name ?? 'Sick Leave'
                ];
            }
            
            // If staff has sick leave, zero out all other hours
            if ($hasStaffSickLeave) {
                // Reset all regular shifts to 00:00
                foreach ($dayData['shifts'] as &$shift) {
                    $shift['hours'] = 0;
                    $shift['minutes'] = 0;
                    $shift['holiday_hours'] = 0;
                    $shift['holiday_minutes'] = 0;
                    $shift['night_hours'] = 0;
                    $shift['night_minutes'] = 0;
                    $shift['holiday_night_hours'] = 0;
                    $shift['holiday_night_minutes'] = 0;
                }
            }
            
            // Add sick leaves to day data
            if (!empty($sickLeavesOnDate)) {
                $dayData['sick_leaves'] = $sickLeavesOnDate;
            }
            
            if (!empty($dayData['shifts']) || !empty($dayData['sick_leaves'])) {
                $dailyHours[$dateStr] = $dayData;
            }
        }
        
        return $dailyHours;
    }
    
    /**
     * Get staff working periods for daily report (simpler version)
     */
    public function getStaffWorkingPeriodsForDaily($staffId, $date)
    {
        // Get staff working hours from StaffMonthlyHours
        $staffHours = StaffMonthlyHours::where('staff_id', $staffId)
            ->whereYear('date', $date->year)
            ->whereMonth('date', $date->month)
            ->get();
        
        $periods = [];
        foreach ($staffHours as $record) {
            $recordDate = Carbon::parse($record->date);
            $dateStr = $recordDate->format('Y-m-d');
            $isHoliday = \App\Models\Holiday::where('holiday_date', $dateStr)->exists();
            
            $hoursData = $record->hours_data;
            if (is_string($hoursData)) {
                $hoursData = json_decode($hoursData, true);
            }
            
            if (isset($hoursData) && is_array($hoursData)) {
                foreach ($hoursData as $shift) {
                    if (isset($shift['start_time']) && isset($shift['end_time'])) {
                        try {
                            $startTime = Carbon::parse($dateStr . ' ' . $shift['start_time']);
                            $endTime = Carbon::parse($dateStr . ' ' . $shift['end_time']);
                            
                            if ($endTime->lt($startTime)) {
                                $endTime->addDay();
                            }
                            
                            $totalMinutes = $endTime->diffInMinutes($startTime);
                            $nightMinutes = $this->calculateStaffNightMinutes($startTime, $endTime);
                            
                            $periods[] = [
                                'date' => $dateStr,
                                'start_time' => $startTime,
                                'end_time' => $endTime,
                                'total_minutes' => $totalMinutes,
                                'holiday_minutes' => $isHoliday ? $totalMinutes : 0,
                                'night_minutes' => $nightMinutes['night'],
                                'holiday_night_minutes' => $nightMinutes['holiday_night'],
                                'original_shift_type' => $shift['type'] ?? null  // Include original shift type
                            ];
                        } catch (\Exception $e) {
                            continue;
                        }
                    }
                }
            }
        }
        
        return $periods;
    }
    
    /**
     * Get staff missing hours for the month
     */
    private function getStaffMissingHours($staffId, $year, $month)
    {
        return \DB::table('staff_missing_hours')
            ->where('staff_id', $staffId)
            ->whereYear('applied_date', $year)
            ->whereMonth('applied_date', $month)
            ->whereNull('deleted_at')
            ->get();
    }
    
    /**
     * Get guide missing hours for the month
     */
    private function getGuideMissingHours($guideId, $year, $month)
    {
        return \DB::table('missing_hours')
            ->where('guide_id', $guideId)
            ->whereYear('applied_at', $year)
            ->whereMonth('applied_at', $month)
            ->whereNull('deleted_at')
            ->get();
    }
    
    /**
     * Get staff sick leaves for the month
     */
    private function getStaffSickLeaves($staffId, $year, $month)
    {
        $startOfMonth = \Carbon\Carbon::create($year, $month, 1)->startOfDay();
        $endOfMonth = \Carbon\Carbon::create($year, $month, 1)->endOfMonth()->endOfDay();
        
        return \DB::table('supervisor_sick_leaves')
            ->where('staff_id', $staffId)
            ->where('status', 2) // Only approved sick leaves
            ->where(function($query) use ($startOfMonth, $endOfMonth) {
                // Sick leave period overlaps with the report month
                $query->where(function($q) use ($startOfMonth, $endOfMonth) {
                    $q->whereBetween('start_date', [$startOfMonth, $endOfMonth])
                      ->orWhereBetween('end_date', [$startOfMonth, $endOfMonth])
                      ->orWhere(function($q2) use ($startOfMonth, $endOfMonth) {
                          $q2->where('start_date', '<=', $startOfMonth)
                             ->where('end_date', '>=', $endOfMonth);
                      });
                });
            })
            ->get();
    }
    
    /**
     * Get guide sick leaves for the month
     */
    private function getGuideSickLeaves($guideId, $year, $month)
    {
        return \DB::table('sick_leaves')
            ->where('guide_id', $guideId)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->whereNull('deleted_at')
            ->get();
    }

    /**
     * Get guide working periods for daily report
     */
    public function getGuideWorkingPeriodsForDaily($guideId, $date)
    {
        return \App\Models\EventSalary::select('guide_start_time', 'guide_end_time', 'normal_hours', 'normal_night_hours', 'holiday_hours', 'holiday_night_hours')
            ->with('event')
            ->whereYear('guide_start_time', $date->year)
            ->whereMonth('guide_start_time', $date->month)
            ->where('guideId', $guideId)
            ->whereHas('event', function ($query) {
                $query->where('event_id', 'NOT LIKE', '%manual-missing-hours%');
            })
            ->whereIn('approval_status', [1, 2])
            ->get();
    }
    
    /**
     * Calculate non-overlapping minutes for daily view
     */
    public function calculateDailyNonOverlappingMinutes($guideStart, $guideEnd, $staffShifts)
    {
        $totalGuideMinutes = $guideEnd->diffInMinutes($guideStart);
        $totalOverlapMinutes = 0;
        
        foreach ($staffShifts as $staffShift) {
            $staffStart = $staffShift['start_time'];
            $staffEnd = $staffShift['end_time'];
            
            $overlapStart = max($guideStart, $staffStart);
            $overlapEnd = min($guideEnd, $staffEnd);
            
            if ($overlapStart < $overlapEnd) {
                $totalOverlapMinutes += $overlapEnd->diffInMinutes($overlapStart);
            }
        }
        
        return max(0, $totalGuideMinutes - $totalOverlapMinutes);
    }
    
    
    /**
     * Calculate night hours for daily view
     */
    public function calculateDailyNightHours($guideStart, $guideEnd, $staffShifts, $holidays)
    {
        $dateStr = $guideStart->format('Y-m-d');
        $isHoliday = in_array($dateStr, $holidays);
        
        // Calculate night hours in non-overlapping periods
        $nightHours = ['night' => 0, 'holiday_night' => 0];
        
        // Define night periods
        $nightPeriods = [
            ['start' => $guideStart->copy()->setTime(20, 0), 'end' => $guideStart->copy()->addDay()->setTime(0, 0)],
            ['start' => $guideStart->copy()->setTime(0, 0), 'end' => $guideStart->copy()->setTime(6, 0)]
        ];
        
        foreach ($nightPeriods as $nightPeriod) {
            $overlapStart = max($guideStart, $nightPeriod['start']);
            $overlapEnd = min($guideEnd, $nightPeriod['end']);
            
            if ($overlapStart < $overlapEnd) {
                $nightMinutes = $overlapEnd->diffInMinutes($overlapStart);
                
                // Subtract staff overlaps
                foreach ($staffShifts as $staffShift) {
                    $staffOverlapStart = max($overlapStart, $staffShift['start_time']);
                    $staffOverlapEnd = min($overlapEnd, $staffShift['end_time']);
                    
                    if ($staffOverlapStart < $staffOverlapEnd) {
                        $nightMinutes -= $staffOverlapEnd->diffInMinutes($staffOverlapStart);
                    }
                }
                
                if ($nightMinutes > 0) {
                    if ($isHoliday) {
                        $nightHours['holiday_night'] += $nightMinutes;
                    } else {
                        $nightHours['night'] += $nightMinutes;
                    }
                }
            }
        }
        
        return $nightHours;
    }
    
    /**
     * Calculate night minutes for staff shifts
     */
    private function calculateStaffNightMinutes($startTime, $endTime)
    {
        $dateStr = $startTime->format('Y-m-d');
        $isHoliday = \App\Models\Holiday::where('holiday_date', $dateStr)->exists();
        
        $nightMinutes = ['night' => 0, 'holiday_night' => 0];
        
        // Night periods: 20:00-00:00 and 00:00-06:00
        $nightPeriods = [
            ['start' => $startTime->copy()->setTime(20, 0), 'end' => $startTime->copy()->addDay()->setTime(0, 0)],
            ['start' => $startTime->copy()->setTime(0, 0), 'end' => $startTime->copy()->setTime(6, 0)]
        ];
        
        foreach ($nightPeriods as $nightPeriod) {
            $overlapStart = max($startTime, $nightPeriod['start']);
            $overlapEnd = min($endTime, $nightPeriod['end']);
            
            if ($overlapStart < $overlapEnd) {
                $minutes = $overlapEnd->diffInMinutes($overlapStart);
                
                if ($isHoliday) {
                    $nightMinutes['holiday_night'] += $minutes;
                } else {
                    $nightMinutes['night'] += $minutes;
                }
            }
        }
        
        return $nightMinutes;
    }
}
