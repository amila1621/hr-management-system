<?php
namespace App\Http\Controllers;

use App\Models\Holiday;
use App\Models\StaffMonthlyHours;
use App\Models\StaffMissingHours;
use App\Models\StaffHoursDetails;
use App\Models\LastTours;
use App\Models\SalaryReport;
use App\Models\StaffUser;
use Illuminate\Http\Request;
use Carbon\Carbon;

class SalarySummaryController extends Controller
{
     public function calculateSalaryHours($date = null)
    {
        $date = $date ?? '2025-05-01';

        // Delete existing salary reports for this date, but preserve midnight phone "Next Day" entries
        $deletedCount = SalaryReport::whereDate('date', $date)
            ->where(function($query) {
                $query->where('description', 'NOT LIKE', '%Midnight Phone%Next Day%')
                      ->where('description', 'NOT LIKE', '%Next Day%');
            })
            ->delete();

        // echo "DEBUG: Deleted {$deletedCount} existing salary reports for {$date} (preserved midnight phone Next Day entries)<br>";

        // Only get records for active (non-deleted) staff AND approved records only
        $staffHours = StaffMonthlyHours::where('date', $date)
            ->where('is_approved', 1) // Only get approved records
            ->whereHas('staff') // This ensures the staff exists and is not soft deleted
            ->with('staff')
            ->get();

        // Get missing hours for this applied_date
        $missingHours = StaffMissingHours::whereDate('applied_date', $date)
            ->whereHas('staff') // This ensures the staff exists and is not soft deleted
            ->with('staff')
            ->get();


        // Group missing hours by staff_id for easier processing
        $missingHoursByStaff = $missingHours->groupBy('staff_id');

        // Process all staff who have regular hours
        foreach ($staffHours as $staffHour) {
            $staff = $staffHour->staff;
            $department = $staff->department;

            // Get missing hours for this staff member
            $staffMissingHours = $missingHoursByStaff->get($staff->id, collect());

            // Route to appropriate calculation function based on department
            if (strpos($department, 'Operations') !== false) {
                // Pass to Operations calculation
                $this->calculateOperationsSalary($staffHour, $date, $staffMissingHours);
            } elseif (
                strpos($department, 'Hotel') !== false ||
                strpos($department, 'Hotel Restaurant') !== false ||
                strpos($department, 'Hotel Spa') !== false
            ) {
                // Pass to Hotel departments calculation
                $this->calculateHotelSalary($staffHour, $date, $staffMissingHours);
            } else {
                // Pass to other departments calculation (IT, AM, etc.)
                $this->calculateOtherDepartmentsSalary($staffHour, $date, $staffMissingHours);
            }
        }

        // Process staff who only have missing hours (no regular hours for this date)
        $processedStaffIds = $staffHours->pluck('staff.id')->toArray();
        $missingOnlyStaff = $missingHours->whereNotIn('staff_id', $processedStaffIds);

        // Group missing hours by staff_id
        $missingOnlyStaffGrouped = $missingOnlyStaff->groupBy('staff_id');

        foreach ($missingOnlyStaffGrouped as $staffId => $staffMissingHours) {
            // Get the staff record from the first missing hour (all are for same staff)
            $staff = $staffMissingHours->first()->staff;
            $department = $staff->department ?? 'Unknown';

            \Log::info("Processing " . $staffMissingHours->count() . " missing hours for Staff ID: {$staffId}, Name: {$staff->name}, Dept: {$department}");


            // Store original department value in case it's empty
            if (empty($department)) {
                // Set a default department to ensure processing continues
                $department = 'Standard'; // This will route to calculateOtherDepartmentsSalary
                \Log::warning("Empty department for staff ID: {$staffId}, using default");
            }   

            // Route to appropriate calculation function based on department
            if (strpos($department, 'Operations') !== false) {
                $this->calculateOperationsSalary(null, $date, $staffMissingHours);
            } elseif (
                strpos($department, 'Hotel') !== false ||
                strpos($department, 'Hotel Restaurant') !== false ||
                strpos($department, 'Hotel Spa') !== false
            ) {
                $this->calculateHotelSalary(null, $date, $staffMissingHours);
            } else {
                $this->calculateOtherDepartmentsSalary(null, $date, $staffMissingHours);
            }
        }

        // POST-PROCESSING: Calculate midnight phone hours
        $this->calculateMidnightPhoneHours($date);
    }

    /**
     * Calculate salary hours for Operations department
     */
    private function calculateOperationsSalary($staffHour, $date, $missingHours = null)
    {
        // Handle case where we only have missing hours (no regular staffHour)
        if ($staffHour === null && $missingHours && $missingHours->count() > 0) {
            $staff = $missingHours->first()->staff;
            $hoursData = [];
        } else {
            $staff = $staffHour->staff;
            $hoursData = is_array($staffHour->hours_data)
                ? $staffHour->hours_data
                : json_decode($staffHour->hours_data, true);
        }

        // Track work periods for display
        $workPeriods = [];
        $totalWorkMinutes = 0;        // All work minutes
        $totalHolidayMinutes = 0;     // Only holiday work minutes
        $totalNightMinutes = 0;       // All night minutes
        $totalNightHolidayMinutes = 0; // Only night minutes on holidays
        $totalSickLeaveMinutes = 0;   // Total sick leave minutes

        // Check if the date is a holiday
        $isHoliday = $this->isHoliday(Carbon::parse($date));

        // Process regular hours data
        if (is_array($hoursData)) {
            foreach ($hoursData as $shift) {
                // Handle sick leaves separately
                if (isset($shift['type']) && $shift['type'] === 'SL') {
                    // For sick leaves, only calculate total hours and store in sick_leaves column
                    if (isset($shift['start_time']) && isset($shift['end_time']) && 
                        !is_null($shift['start_time']) && !is_null($shift['end_time'])) {
                        
                        $startTime = Carbon::createFromFormat('H:i', $shift['start_time']);
                        $endTime = Carbon::createFromFormat('H:i', $shift['end_time']);
                        
                        $sickLeaveMinutes = $endTime->diffInMinutes($startTime);
                        $totalSickLeaveMinutes += $sickLeaveMinutes;
                        
                        // Add to work periods for display (marked as SL)
                        $workPeriods[] = $shift['start_time'] . ' - ' . $shift['end_time'] . ' (SL)';
                    }
                    continue; // Skip further processing for SL
                }

                // Skip off days, holidays, vacations with null times
                if (
                    isset($shift['type']) && in_array($shift['type'], ['X', 'V', 'H']) &&
                    (is_null($shift['start_time']) || is_null($shift['end_time']))
                ) {
                    continue;
                }

                // Process shifts with valid start and end times
                if (
                    isset($shift['start_time']) && isset($shift['end_time']) &&
                    !is_null($shift['start_time']) && !is_null($shift['end_time'])
                ) {

                    $startTime = Carbon::createFromFormat('H:i', $shift['start_time']);
                    $endTime = Carbon::createFromFormat('H:i', $shift['end_time']);

                    // Calculate total minutes for this shift
                    $shiftMinutes = $endTime->diffInMinutes($startTime);
                    
                    // Calculate night hours (20:00-06:00) for this shift
                    $nightMinutes = $this->calculateNightHours($startTime, $endTime);

                    // Handle on_call shifts - divide by 3 and round up
                    if (isset($shift['type']) && $shift['type'] === 'on_call') {
                        // On-call: divide by 3 and round up
                        $adjustedShiftMinutes = (int) ceil($shiftMinutes / 3);
                        $adjustedNightMinutes = (int) ceil($nightMinutes / 3);
                        
                        // Add to work periods for display (marked as on_call)
                        $workPeriods[] = $shift['start_time'] . ' - ' . $shift['end_time'] . ' (On-Call)';
                    } else {
                        // Normal shifts - full time
                        $adjustedShiftMinutes = $shiftMinutes;
                        $adjustedNightMinutes = $nightMinutes;
                        
                        // Add to work periods for display
                        $workPeriods[] = $shift['start_time'] . ' - ' . $shift['end_time'];
                    }

                    // Add to totals
                    $totalWorkMinutes += $adjustedShiftMinutes;
                    $totalNightMinutes += $adjustedNightMinutes;

                    // If it's a holiday, also add to holiday totals
                    if ($isHoliday) {
                        $totalHolidayMinutes += $adjustedShiftMinutes;
                        $totalNightHolidayMinutes += $adjustedNightMinutes;
                    }
                }
            }
        }

        // Process missing hours data (always full time - no on_call for missing hours)
        if ($missingHours && $missingHours->count() > 0) {
            foreach ($missingHours as $missingHour) {
                $startTime = Carbon::parse($missingHour->start_time);
                $endTime = Carbon::parse($missingHour->end_time);

                // Format for display - show date and time since missing hours can span multiple dates
                $workPeriod = $startTime->format('H:i') . ' - ' . $endTime->format('H:i') . ' (Missing: ' . substr($missingHour->reason, 0, 20) . '...)';
                $workPeriods[] = $workPeriod;

                // Calculate total minutes for this missing hour period
                $missingMinutes = $endTime->diffInMinutes($startTime);
                
                // Convert to H:i format to use existing calculateNightHours function
                $startTimeFormatted = Carbon::createFromFormat('H:i', $startTime->format('H:i'));
                $endTimeFormatted = Carbon::createFromFormat('H:i', $endTime->format('H:i'));
                
                // Calculate night hours (20:00-06:00) for missing hours using existing function
                $nightMinutes = $this->calculateNightHours($startTimeFormatted, $endTimeFormatted);

                // Check if the original date (not applied_date) was a holiday
                $originalDate = Carbon::parse($missingHour->date);
                $wasHoliday = $this->isHoliday($originalDate);

                // Add to totals (missing hours are always full time)
                $totalWorkMinutes += $missingMinutes;
                $totalNightMinutes += $nightMinutes;

                // If the original work date was a holiday, add to holiday totals
                if ($wasHoliday) {
                    $totalHolidayMinutes += $missingMinutes;
                    $totalNightHolidayMinutes += $nightMinutes;
                }
            }
        }

        // If no actual work was done and no sick leave, don't create a salary report
        if ($totalWorkMinutes == 0 && $totalSickLeaveMinutes == 0) {
            return;
        }

        // Format work periods as comma-separated string
        $workPeriodsString = implode(' , ', $workPeriods);

        // Create or update salary report
        $this->createSalaryReport($staff, $date, [
            'description' => 'Operations Department Hours',
            'work_periods' => $workPeriodsString,
            'work_hours' => $this->minutesToTimeFormat($totalWorkMinutes),       // ALL hours worked (with on-call adjusted)
            'holiday_hours' => $this->minutesToTimeFormat($totalHolidayMinutes), // Only holiday hours (with on-call adjusted)
            'evening_hours' => '00:00',                                          // Always 00:00 for operations
            'evening_holiday_hours' => '00:00',                                  // Always 00:00 for operations
            'night_hours' => $this->minutesToTimeFormat($totalNightMinutes),     // ALL night hours (with on-call adjusted)
            'night_holiday_hours' => $this->minutesToTimeFormat($totalNightHolidayMinutes), // Only night hours on holidays (with on-call adjusted)
            'sick_leaves' => $this->minutesToTimeFormat($totalSickLeaveMinutes), // Sick leave hours
        ]);

        // echo "✓ Operations Dept - {$staff->name}: Periods: {$workPeriodsString}<br>";
        if ($totalSickLeaveMinutes > 0) {
            // echo "  - Sick Leave: " . $this->minutesToTimeFormat($totalSickLeaveMinutes) . "<br>";
        }
        if ($missingHours && $missingHours->count() > 0) {
            // echo "  - Missing Hours: " . $missingHours->count() . " period(s)<br>";
        }
    }

    /**
     * Calculate salary hours for Hotel departments (Hotel, Hotel Restaurant, Hotel Spa)
     */
    private function calculateHotelSalary($staffHour, $date, $missingHours = null)
    {
        // Handle case where we only have missing hours (no regular staffHour)
        if ($staffHour === null && $missingHours && $missingHours->count() > 0) {
            $staff = $missingHours->first()->staff;
            $hoursData = [];
        } else {
            $staff = $staffHour->staff;
            $hoursData = is_array($staffHour->hours_data)
                ? $staffHour->hours_data
                : json_decode($staffHour->hours_data, true);
        }

        // Track work periods for display
        $workPeriods = [];
        $totalWorkMinutes = 0;            // All work minutes (normal + evening + night)
        $totalHolidayMinutes = 0;         // Only holiday work minutes
        $totalEveningMinutes = 0;         // All evening minutes (18:00-00:00)
        $totalEveningHolidayMinutes = 0;  // Only evening minutes on holidays
        $totalNightMinutes = 0;           // All night minutes (00:00-06:00)
        $totalNightHolidayMinutes = 0;    // Only night minutes on holidays
        $totalSickLeaveMinutes = 0;       // Total sick leave minutes

        // Check if the date is a holiday
        $isHoliday = $this->isHoliday(Carbon::parse($date));

        // Process regular hours data
        if (is_array($hoursData)) {
            foreach ($hoursData as $shift) {
                // Handle sick leaves separately
                if (isset($shift['type']) && $shift['type'] === 'SL') {
                    // For sick leaves, only calculate total hours and store in sick_leaves column
                    if (isset($shift['start_time']) && isset($shift['end_time']) && 
                        !is_null($shift['start_time']) && !is_null($shift['end_time'])) {
                        
                        $startTime = Carbon::createFromFormat('H:i', $shift['start_time']);
                        $endTime = Carbon::createFromFormat('H:i', $shift['end_time']);
                        
                        $sickLeaveMinutes = $endTime->diffInMinutes($startTime);
                        $totalSickLeaveMinutes += $sickLeaveMinutes;
                        
                        // Add to work periods for display (marked as SL)
                        $workPeriods[] = $shift['start_time'] . ' - ' . $shift['end_time'] . ' (SL)';
                    }
                    continue; // Skip further processing for SL
                }

                // Skip on_call for hotel departments
                if (isset($shift['type']) && $shift['type'] === 'on_call') {
                    continue;
                }

                // Skip off days, holidays, vacations with null times
                if (
                    isset($shift['type']) && in_array($shift['type'], ['X', 'V', 'H']) &&
                    (is_null($shift['start_time']) || is_null($shift['end_time']))
                ) {
                    continue;
                }

                // Process shifts with valid start and end times
                if (
                    isset($shift['start_time']) && isset($shift['end_time']) &&
                    !is_null($shift['start_time']) && !is_null($shift['end_time'])
                ) {

                    $startTime = Carbon::createFromFormat('H:i', $shift['start_time']);
                    $endTime = Carbon::createFromFormat('H:i', $shift['end_time']);

                    // Add to work periods for display
                    $workPeriods[] = $shift['start_time'] . ' - ' . $shift['end_time'];

                    // Calculate total minutes for this shift
                    $shiftMinutes = $endTime->diffInMinutes($startTime);
                    
                    // Calculate evening hours (18:00-00:00) for this shift
                    $eveningMinutes = $this->calculateHotelEveningHours($startTime, $endTime);
                    
                    // Calculate night hours (00:00-06:00) for this shift
                    $nightMinutes = $this->calculateHotelNightHours($startTime, $endTime);

                    // Add to totals
                    $totalWorkMinutes += $shiftMinutes;
                    $totalEveningMinutes += $eveningMinutes;
                    $totalNightMinutes += $nightMinutes;

                    // If it's a holiday, also add to holiday totals
                    if ($isHoliday) {
                        $totalHolidayMinutes += $shiftMinutes;
                        $totalEveningHolidayMinutes += $eveningMinutes;
                        $totalNightHolidayMinutes += $nightMinutes;
                    }
                }
            }
        }

        // Process missing hours data
        if ($missingHours && $missingHours->count() > 0) {
            foreach ($missingHours as $missingHour) {
                $startTime = Carbon::parse($missingHour->start_time);
                $endTime = Carbon::parse($missingHour->end_time);

                // Format for display - show date and time since missing hours can span multiple dates
                $workPeriod = $startTime->format('H:i') . ' - ' . $endTime->format('H:i') . ' (Missing: ' . substr($missingHour->reason, 0, 20) . '...)';
                $workPeriods[] = $workPeriod;

                // Calculate total minutes for this missing hour period
                $missingMinutes = $endTime->diffInMinutes($startTime);
                
                // Convert to H:i format to use existing calculation functions
                $startTimeFormatted = Carbon::createFromFormat('H:i', $startTime->format('H:i'));
                $endTimeFormatted = Carbon::createFromFormat('H:i', $endTime->format('H:i'));
                
                // Calculate evening hours (18:00-00:00) for missing hours
                $eveningMinutes = $this->calculateHotelEveningHours($startTimeFormatted, $endTimeFormatted);
                
                // Calculate night hours (00:00-06:00) for missing hours
                $nightMinutes = $this->calculateHotelNightHours($startTimeFormatted, $endTimeFormatted);

                // Check if the original date (not applied_date) was a holiday
                $originalDate = Carbon::parse($missingHour->date);
                $wasHoliday = $this->isHoliday($originalDate);

                // Add to totals
                $totalWorkMinutes += $missingMinutes;
                $totalEveningMinutes += $eveningMinutes;
                $totalNightMinutes += $nightMinutes;

                // If the original work date was a holiday, add to holiday totals
                if ($wasHoliday) {
                    $totalHolidayMinutes += $missingMinutes;
                    $totalEveningHolidayMinutes += $eveningMinutes;
                    $totalNightHolidayMinutes += $nightMinutes;
                }
            }
        }

        // If no actual work was done and no sick leave, don't create a salary report
        if ($totalWorkMinutes == 0 && $totalSickLeaveMinutes == 0) {
            return;
        }

        // Format work periods as comma-separated string
        $workPeriodsString = implode(' , ', $workPeriods);

        // Create or update salary report
        $this->createSalaryReport($staff, $date, [
            'description' => 'Hotel Department Hours',
            'work_periods' => $workPeriodsString,
            'work_hours' => $this->minutesToTimeFormat($totalWorkMinutes),              // ALL hours worked
            'holiday_hours' => $this->minutesToTimeFormat($totalHolidayMinutes),       // Only holiday hours
            'evening_hours' => $this->minutesToTimeFormat($totalEveningMinutes),       // ALL evening hours (18:00-00:00)
            'evening_holiday_hours' => $this->minutesToTimeFormat($totalEveningHolidayMinutes), // Only evening hours on holidays
            'night_hours' => $this->minutesToTimeFormat($totalNightMinutes),           // ALL night hours (00:00-06:00)
            'night_holiday_hours' => $this->minutesToTimeFormat($totalNightHolidayMinutes), // Only night hours on holidays
            'sick_leaves' => $this->minutesToTimeFormat($totalSickLeaveMinutes),       // Sick leave hours
        ]);

        // echo "✓ Hotel Dept - {$staff->name}: Periods: {$workPeriodsString}<br>";
        if ($totalSickLeaveMinutes > 0) {
            // echo "  - Sick Leave: " . $this->minutesToTimeFormat($totalSickLeaveMinutes) . "<br>";
        }
        if ($missingHours && $missingHours->count() > 0) {
            // echo "  - Missing Hours: " . $missingHours->count() . " period(s)<br>";
        }
    }

    /**
     * Calculate evening hours (18:00-00:00) for Hotel departments
     */
    private function calculateHotelEveningHours($startTime, $endTime)
    {
        // Create evening time boundaries (18:00-00:00)
        $eveningStart = Carbon::createFromTime(18, 0); // 18:00
        $eveningEnd = Carbon::createFromTime(23, 59, 59); // 23:59:59 (end of day)

        // If shift doesn't overlap with evening hours, return 0
        if ($endTime->lte($eveningStart) || $startTime->gt($eveningEnd)) {
            return 0;
        }

        // Calculate overlap between shift and evening hours
        $overlapStart = $startTime->gte($eveningStart) ? $startTime : $eveningStart;
        $overlapEnd = $endTime->lte($eveningEnd) ? $endTime : $eveningEnd;

        return $overlapEnd->diffInMinutes($overlapStart);
    }

    /**
     * Calculate night hours (00:00-06:00) for Hotel departments
     */
    private function calculateHotelNightHours($startTime, $endTime)
    {
        // Create night time boundaries (00:00-06:00)
        $nightStart = Carbon::createFromTime(0, 0); // 00:00
        $nightEnd = Carbon::createFromTime(6, 0); // 06:00

        // If shift doesn't overlap with night hours, return 0
        if ($endTime->lte($nightStart) || $startTime->gte($nightEnd)) {
            return 0;
        }

        // Calculate overlap between shift and night hours
        $overlapStart = $startTime->gte($nightStart) ? $startTime : $nightStart;
        $overlapEnd = $endTime->lte($nightEnd) ? $endTime : $nightEnd;

        return $overlapEnd->diffInMinutes($overlapStart);
    }

    /**
     * Calculate salary hours for other departments (IT, AM, etc.)
     */
    private function calculateOtherDepartmentsSalary($staffHour, $date, $missingHours = null)
    {
        // Handle case where we only have missing hours (no regular staffHour)
        if ($staffHour === null && $missingHours && $missingHours->count() > 0) {
            $staff = $missingHours->first()->staff;
            $hoursData = [];
        } else {
            $staff = $staffHour->staff;
            $hoursData = is_array($staffHour->hours_data)
                ? $staffHour->hours_data
                : json_decode($staffHour->hours_data, true);
        }

        // Track work periods for display
        $workPeriods = [];
        $totalWorkMinutes = 0;        // All work minutes
        $totalHolidayMinutes = 0;     // Only holiday work minutes
        $totalNightMinutes = 0;       // All night minutes
        $totalNightHolidayMinutes = 0; // Only night minutes on holidays
        $totalSickLeaveMinutes = 0;   // Total sick leave minutes

        // Check if the date is a holiday
        $isHoliday = $this->isHoliday(Carbon::parse($date));

        // Process regular hours data
        if (is_array($hoursData)) {
            foreach ($hoursData as $shift) {
                // Handle sick leaves separately
                if (isset($shift['type']) && $shift['type'] === 'SL') {
                    // For sick leaves, only calculate total hours and store in sick_leaves column
                    if (isset($shift['start_time']) && isset($shift['end_time']) && 
                        !is_null($shift['start_time']) && !is_null($shift['end_time'])) {
                        
                        $startTime = Carbon::createFromFormat('H:i', $shift['start_time']);
                        $endTime = Carbon::createFromFormat('H:i', $shift['end_time']);
                        
                        $sickLeaveMinutes = $endTime->diffInMinutes($startTime);
                        $totalSickLeaveMinutes += $sickLeaveMinutes;
                        
                        // Add to work periods for display (marked as SL)
                        $workPeriods[] = $shift['start_time'] . ' - ' . $shift['end_time'] . ' (SL)';
                    }
                    continue; // Skip further processing for SL
                }

                // Skip on_call for other departments
                if (isset($shift['type']) && $shift['type'] === 'on_call') {
                    continue;
                }

                // Skip off days, holidays, vacations with null times
                if (
                    isset($shift['type']) && in_array($shift['type'], ['X', 'V', 'H']) &&
                    (is_null($shift['start_time']) || is_null($shift['end_time']))
                ) {
                    continue;
                }

                // Process shifts with valid start and end times
                if (
                    isset($shift['start_time']) && isset($shift['end_time']) &&
                    !is_null($shift['start_time']) && !is_null($shift['end_time'])
                ) {

                    $startTime = Carbon::createFromFormat('H:i', $shift['start_time']);
                    $endTime = Carbon::createFromFormat('H:i', $shift['end_time']);

                    // Add to work periods for display
                    $workPeriods[] = $shift['start_time'] . ' - ' . $shift['end_time'];

                    // Calculate total minutes for this shift
                    $shiftMinutes = $endTime->diffInMinutes($startTime);
                    
                    // Calculate night hours (20:00-06:00) for this shift
                    $nightMinutes = $this->calculateNightHours($startTime, $endTime);

                    // Add to totals
                    $totalWorkMinutes += $shiftMinutes;
                    $totalNightMinutes += $nightMinutes;

                    // If it's a holiday, also add to holiday totals
                    if ($isHoliday) {
                        $totalHolidayMinutes += $shiftMinutes;
                        $totalNightHolidayMinutes += $nightMinutes;
                    }
                }
            }
        }

        // Process missing hours data
        if ($missingHours && $missingHours->count() > 0) {
            foreach ($missingHours as $missingHour) {
                $startTime = Carbon::parse($missingHour->start_time);
                $endTime = Carbon::parse($missingHour->end_time);

                // Format for display - show date and time since missing hours can span multiple dates
                $workPeriod = $startTime->format('H:i') . ' - ' . $endTime->format('H:i') . ' (Missing: ' . substr($missingHour->reason, 0, 20) . '...)';
                $workPeriods[] = $workPeriod;

                // Calculate total minutes for this missing hour period
                $missingMinutes = $endTime->diffInMinutes($startTime);
                
                // Convert to H:i format to use existing calculateNightHours function
                $startTimeFormatted = Carbon::createFromFormat('H:i', $startTime->format('H:i'));
                $endTimeFormatted = Carbon::createFromFormat('H:i', $endTime->format('H:i'));
                
                // Calculate night hours (20:00-06:00) for missing hours using existing function
                $nightMinutes = $this->calculateNightHours($startTimeFormatted, $endTimeFormatted);

                // Check if the original date (not applied_date) was a holiday
                $originalDate = Carbon::parse($missingHour->date);
                $wasHoliday = $this->isHoliday($originalDate);

                // Add to totals
                $totalWorkMinutes += $missingMinutes;
                $totalNightMinutes += $nightMinutes;

                // If the original work date was a holiday, add to holiday totals
                if ($wasHoliday) {
                    $totalHolidayMinutes += $missingMinutes;
                    $totalNightHolidayMinutes += $nightMinutes;
                }
            }
        }

        // If no actual work was done and no sick leave, don't create a salary report
        if ($totalWorkMinutes == 0 && $totalSickLeaveMinutes == 0) {
            return;
        }

        // Format work periods as comma-separated string
        $workPeriodsString = implode(' , ', $workPeriods);

        // Create or update salary report
        $this->createSalaryReport($staff, $date, [
            'description' => 'Standard hours',
            'work_periods' => $workPeriodsString,
            'work_hours' => $this->minutesToTimeFormat($totalWorkMinutes),      // ALL hours worked
            'holiday_hours' => $this->minutesToTimeFormat($totalHolidayMinutes), // Only holiday hours
            'evening_hours' => '00:00',                                          // Always 00:00 for other departments
            'evening_holiday_hours' => '00:00',                                  // Always 00:00 for other departments
            'night_hours' => $this->minutesToTimeFormat($totalNightMinutes),     // ALL night hours
            'night_holiday_hours' => $this->minutesToTimeFormat($totalNightHolidayMinutes), // Only night hours on holidays
            'sick_leaves' => $this->minutesToTimeFormat($totalSickLeaveMinutes), // Sick leave hours
        ]);

        // echo "✓ Other Dept - {$staff->name}: Periods: {$workPeriodsString}<br>";
        if ($totalSickLeaveMinutes > 0) {
            // echo "  - Sick Leave: " . $this->minutesToTimeFormat($totalSickLeaveMinutes) . "<br>";
        }
        if ($missingHours && $missingHours->count() > 0) {
            // echo "  - Missing Hours: " . $missingHours->count() . " period(s)<br>";
        }
    }

    /**
     * Create or update salary report - completely revised to prevent duplications
     */
    private function createSalaryReport($staff, $date, $data)
    {
        try {
            // Create a fingerprint of the data being added to detect duplicates
            $dataFingerprint = md5($data['description'] . $data['work_periods']);
            
            // Check if a salary report already exists for this staff and date
            $existingReport = SalaryReport::where('user_id', $staff->id)
                ->where('date', $date)
                ->first();

            // Special handling for midnight phone entries
            if ($existingReport && strpos($data['description'], 'Midnight Phone') !== false) {
                // Check if this exact midnight phone entry might already be included
                if (strpos($existingReport->work_periods, $data['work_periods']) !== false) {
                    \Log::info("Skipping duplicate midnight phone entry for {$staff->name} on {$date}: {$data['work_periods']}");
                    return;
                }
                
                // Parse existing time values
                $existingWorkMinutes = $this->timeFormatToMinutes($existingReport->work_hours);
                $existingHolidayMinutes = $this->timeFormatToMinutes($existingReport->holiday_hours);
                $existingEveningMinutes = $this->timeFormatToMinutes($existingReport->evening_hours);
                $existingEveningHolidayMinutes = $this->timeFormatToMinutes($existingReport->evening_holiday_hours);
                $existingNightMinutes = $this->timeFormatToMinutes($existingReport->night_hours);
                $existingNightHolidayMinutes = $this->timeFormatToMinutes($existingReport->night_holiday_hours);

                // Parse new time values
                $newWorkMinutes = $this->timeFormatToMinutes($data['work_hours']);
                $newHolidayMinutes = $this->timeFormatToMinutes($data['holiday_hours']);
                $newEveningMinutes = $this->timeFormatToMinutes($data['evening_hours']);
                $newEveningHolidayMinutes = $this->timeFormatToMinutes($data['evening_holiday_hours']);
                $newNightMinutes = $this->timeFormatToMinutes($data['night_hours']);
                $newNightHolidayMinutes = $this->timeFormatToMinutes($data['night_holiday_hours']);

                // Combine work periods, avoiding duplicates
                $existingPeriods = array_map('trim', explode(',', $existingReport->work_periods));
                $newPeriods = array_map('trim', explode(',', $data['work_periods']));
                $allPeriods = array_merge($existingPeriods, $newPeriods);
                $uniquePeriods = array_unique(array_filter($allPeriods));
                $combinedWorkPeriods = implode(' , ', $uniquePeriods);

                // Update description, avoiding duplicates
                $newDescription = $existingReport->description;
                if (strpos($existingReport->description, $data['description']) === false) {
                    $newDescription .= ' + ' . $data['description'];
                }
                
                // Update with combined values
                $existingReport->update([
                    'description' => $newDescription,
                    'work_periods' => $combinedWorkPeriods,
                    'work_hours' => $this->minutesToTimeFormat($existingWorkMinutes + $newWorkMinutes),
                    'holiday_hours' => $this->minutesToTimeFormat($existingHolidayMinutes + $newHolidayMinutes),
                    'evening_hours' => $this->minutesToTimeFormat($existingEveningMinutes + $newEveningMinutes),
                    'evening_holiday_hours' => $this->minutesToTimeFormat($existingEveningHolidayMinutes + $newEveningHolidayMinutes),
                    'night_hours' => $this->minutesToTimeFormat($existingNightMinutes + $newNightMinutes),
                    'night_holiday_hours' => $this->minutesToTimeFormat($existingNightHolidayMinutes + $newNightHolidayMinutes),
                ]);
                
                \Log::info("Added midnight phone to existing report for {$staff->name} on {$date}: " . $data['work_periods']);
                return;
            }
            
            // Special handling for Next Day midnight phone entries
            if ($existingReport && strpos($existingReport->description, 'Next Day') !== false && 
                strpos($data['description'], 'Midnight Phone') === false) {
                
                // Check if this might be a duplicate
                if (strpos($existingReport->description, $data['description']) !== false &&
                    strpos($existingReport->work_periods, $data['work_periods']) !== false) {
                    \Log::info("Skipping duplicate regular hours with Next Day entry for {$staff->name} on {$date}");
                    return;
                }
                
                // Parse time values as before...
                $existingWorkMinutes = $this->timeFormatToMinutes($existingReport->work_hours);
                $existingHolidayMinutes = $this->timeFormatToMinutes($existingReport->holiday_hours);
                $existingEveningMinutes = $this->timeFormatToMinutes($existingReport->evening_hours);
                $existingEveningHolidayMinutes = $this->timeFormatToMinutes($existingReport->evening_holiday_hours);
                $existingNightMinutes = $this->timeFormatToMinutes($existingReport->night_hours);
                $existingNightHolidayMinutes = $this->timeFormatToMinutes($existingReport->night_holiday_hours);

                $newWorkMinutes = $this->timeFormatToMinutes($data['work_hours']);
                $newHolidayMinutes = $this->timeFormatToMinutes($data['holiday_hours']);
                $newEveningMinutes = $this->timeFormatToMinutes($data['evening_hours']);
                $newEveningHolidayMinutes = $this->timeFormatToMinutes($data['evening_holiday_hours']);
                $newNightMinutes = $this->timeFormatToMinutes($data['night_hours']);
                $newNightHolidayMinutes = $this->timeFormatToMinutes($data['night_holiday_hours']);

                // Combine work periods, avoiding duplicates
                $existingPeriods = array_map('trim', explode(',', $existingReport->work_periods));
                $newPeriods = array_map('trim', explode(',', $data['work_periods']));
                $allPeriods = array_merge($newPeriods, $existingPeriods); // Regular hours first
                $uniquePeriods = array_unique(array_filter($allPeriods));
                $combinedWorkPeriods = implode(' , ', $uniquePeriods);

                // Combine descriptions, avoiding duplicates
                if (strpos($existingReport->description, $data['description']) === false) {
                    $newDescription = $data['description'] . ' + ' . $existingReport->description;
                } else {
                    $newDescription = $existingReport->description;
                }
                
                // Update with combined values
                $existingReport->update([
                    'description' => $newDescription,
                    'work_periods' => $combinedWorkPeriods,
                    'work_hours' => $this->minutesToTimeFormat($existingWorkMinutes + $newWorkMinutes),
                    'holiday_hours' => $this->minutesToTimeFormat($existingHolidayMinutes + $newHolidayMinutes),
                    'evening_hours' => $this->minutesToTimeFormat($existingEveningMinutes + $newEveningMinutes),
                    'evening_holiday_hours' => $this->minutesToTimeFormat($existingEveningHolidayMinutes + $newEveningHolidayMinutes),
                    'night_hours' => $this->minutesToTimeFormat($existingNightMinutes + $newNightMinutes),
                    'night_holiday_hours' => $this->minutesToTimeFormat($existingNightHolidayMinutes + $newNightHolidayMinutes),
                    'sick_leaves' => $data['sick_leaves'] ?? '00:00',
                ]);
                
                \Log::info("Added regular hours to Next Day entry for {$staff->name} on {$date}");
                return;
            }
            
            // For regular entries, check if we have this exact report already
            if ($existingReport && 
                $existingReport->description === $data['description'] && 
                $existingReport->work_periods === $data['work_periods']) {
                \Log::info("Skipping duplicate entry for {$staff->name} on {$date}: {$data['description']}");
                return;
            }

            // If we have an existing report for regular entries (non-midnight phone), 
            // replace it completely
            if ($existingReport && strpos($data['description'], 'Midnight Phone') === false) {
                $existingReport->delete();
            }

            // Get user role and intern status safely
            $staffRole = 'Unknown';
            $isIntern = false;
            
            if ($staff->user) {
                $staffRole = $staff->user->role ?? 'Unknown';
                $isIntern = $staff->user->is_intern ?? false;
            }

            // Create new record
            SalaryReport::create([
                'user_id' => $staff->id,
                'date' => $date,
                'staff_name' => $staff->name,
                'staff_full_name' => $staff->full_name ?? $staff->name,
                'staff_role' => $staffRole,
                'staff_email' => $staff->email,
                'staff_department' => $staff->department,
                'description' => $data['description'],
                'work_periods' => $data['work_periods'] ?? '',
                'work_hours' => $data['work_hours'],
                'holiday_hours' => $data['holiday_hours'],
                'evening_hours' => $data['evening_hours'],
                'evening_holiday_hours' => $data['evening_holiday_hours'],
                'night_hours' => $data['night_hours'],
                'night_holiday_hours' => $data['night_holiday_hours'],
                'sick_leaves' => $data['sick_leaves'] ?? '00:00',
                'is_intern' => $isIntern,
            ]);
            
            \Log::info("Created new salary report for {$staff->name} on {$date}: {$data['description']}");
        } catch (\Exception $e) {
            \Log::error("Error in createSalaryReport for {$staff->id} on {$date}: " . $e->getMessage());
        }
    }

    /**
     * Convert HH:MM format to minutes
     */
       private function timeFormatToMinutes($timeString)
    {
        // Handle null, empty, or invalid values
        if (empty($timeString) || is_null($timeString)) {
            return 0;
        }

        // Convert to string if not already
        $timeString = (string) $timeString;
        
        // Handle common "zero" cases
        if ($timeString === '00:00' || $timeString === '0:00' || $timeString === '0') {
            return 0;
        }

        // Split by colon
        $parts = explode(':', $timeString);
        if (count($parts) !== 2) {
            return 0;
        }

        // Parse hours and minutes
        $hours = (int) $parts[0];
        $minutes = (int) $parts[1];

        // Validate ranges
        if ($hours < 0 || $minutes < 0 || $minutes >= 60) {
            return 0;
        }

        return $hours * 60 + $minutes;
    }

    /**
     * Convert minutes to HH:MM format
     */
    private function minutesToTimeFormat($minutes)
    {
        if ($minutes <= 0) {
            return '00:00';
        }

        $hours = floor($minutes / 60);
        $mins = $minutes % 60;

        return sprintf('%02d:%02d', $hours, $mins);
    }

    /**
     * Calculate night hours (20:00-06:00)
     */
    private function calculateNightHours($startTime, $endTime)
    {
        // Handle same-day times (like from Operations calculations)
        if (!$startTime instanceof Carbon) {
            $startTime = Carbon::createFromFormat('H:i', $startTime->format('H:i'));
            $endTime = Carbon::createFromFormat('H:i', $endTime->format('H:i'));
            
            // Create night time boundaries
            $nightStart = Carbon::createFromTime(20, 0); // 20:00
            $nightEnd = Carbon::createFromTime(6, 0)->addDay(); // 06:00 next day

            // If shift doesn't overlap with night hours, return 0
            if ($endTime->lte($nightStart) || $startTime->gte($nightEnd)) {
                return 0;
            }

            // Calculate overlap between shift and night hours
            $overlapStart = $startTime->gte($nightStart) ? $startTime : $nightStart;
            $overlapEnd = $endTime->lte($nightEnd) ? $endTime : $nightEnd;

            return $overlapEnd->diffInMinutes($overlapStart);
        }
        
        // Handle full datetime instances (like from midnight phone calculations)
        $nightMinutes = 0;
        
        // Night period 1: 20:00 to 23:59:59 on the same day
        $nightStart1 = $startTime->copy()->setTime(20, 0, 0);
        $nightEnd1 = $startTime->copy()->setTime(23, 59, 59);
        
        if ($startTime->lte($nightEnd1) && $endTime->gte($nightStart1)) {
            $overlapStart1 = $startTime->gte($nightStart1) ? $startTime : $nightStart1;
            $overlapEnd1 = $endTime->lte($nightEnd1) ? $endTime : $nightEnd1;
            $nightMinutes += $overlapEnd1->diffInMinutes($overlapStart1);
        }
        
        // Night period 2: 00:00 to 06:00 on the next day
        $nightStart2 = $endTime->copy()->startOfDay();
        $nightEnd2 = $endTime->copy()->setTime(6, 0, 0);
        
        if ($startTime->lte($nightEnd2) && $endTime->gte($nightStart2)) {
            $overlapStart2 = $startTime->gte($nightStart2) ? $startTime : $nightStart2;
            $overlapEnd2 = $endTime->lte($nightEnd2) ? $endTime : $nightEnd2;
            $nightMinutes += $overlapEnd2->diffInMinutes($overlapStart2);
        }
        
        return $nightMinutes;
    }

    /**
     * Check if a date is a holiday
     */
    private function isHoliday($date)
    {
        // Convert string to Carbon if needed
        if (is_string($date)) {
            $carbonDate = Carbon::parse($date);
        } else {
            $carbonDate = $date;
        }
        
        return $carbonDate->isSunday() || Holiday::where('holiday_date', $carbonDate->format('Y-m-d'))->exists();
    }

    /**
     * Post-processing step: Calculate midnight phone hours
     */
    private function calculateMidnightPhoneHours($date)
    {
        // Skip midnight phone calculation for June 10 - August 1
        $currentDate = $date instanceof Carbon ? $date : Carbon::parse($date);
        $startSkipDate = Carbon::parse('2025-06-10');
        $endSkipDate = Carbon::parse('2025-08-31');
        
        if ($currentDate->gte($startSkipDate) && $currentDate->lte($endSkipDate)) {
            // echo "✓ Skipping midnight phone calculations for {$date} (summer period)<br>";
            return;
        }

        // Get midnight phone assignment for this date
        $hoursDetail = StaffHoursDetails::where('date', $date)->first();
        
        if (!$hoursDetail || !$hoursDetail->midnight_phone) {
            // echo "✓ No midnight phone assignment for {$date}<br>";
            return;
        }

        // Get staff ID from midnight_phone array (assuming it's stored as ["58"])
        $midnightPhoneStaffIds = $hoursDetail->midnight_phone;
        if (empty($midnightPhoneStaffIds)) {
            return;
        }

        foreach ($midnightPhoneStaffIds as $staffId) {
            $this->processMidnightPhoneForStaff($staffId, $date);
        }
    }

    /**
     * Process midnight phone calculation for a specific staff member
     */
    private function processMidnightPhoneForStaff($staffId, $date)
    {
        // Get staff information
        $staff = StaffUser::find($staffId);
        if (!$staff) {
            // echo "✗ Staff ID {$staffId} not found for midnight phone<br>";
            return;
        }

        // Get last tour end time for this date
        $lastTourEndTime = $this->getLastTourEndTime($date);
        if (!$lastTourEndTime) {
            // echo "✓ No last tour found for {$date}, skipping midnight phone calculation<br>";
            return;
        }

        // Get staff's last shift end time for this date
        $staffLastShiftEnd = $this->getStaffLastShiftEndTime($staffId, $date);
        if (!$staffLastShiftEnd) {
            // echo "✗ No shift end time found for staff {$staff->name} on {$date}<br>";
            return;
        }

        // Calculate midnight phone on-call period
        $this->calculateMidnightPhoneOnCallHours($staff, $date, $staffLastShiftEnd, $lastTourEndTime);
    }

    /**
     * Get the last tour end time for a specific date
     */
    private function getLastTourEndTime($date)
    {
        // Get last tour record for this date
        $lastTour = LastTours::where('tour_date', $date)->first();
        
        if (!$lastTour || !$lastTour->end_time) {
            return null;
        }

        // Return the end_time directly from last_tours table
        return Carbon::parse($lastTour->end_time);
    }

    /**
     * Get staff's last shift end time for a specific date
     */
    private function getStaffLastShiftEndTime($staffId, $date)
    {
        $staffHour = StaffMonthlyHours::where('staff_id', $staffId)
            ->where('date', $date)
            ->where('is_approved', 1)
            ->first();

        if (!$staffHour) {
            return null;
        }

        $hoursData = is_array($staffHour->hours_data)
            ? $staffHour->hours_data
            : json_decode($staffHour->hours_data, true);

        if (!is_array($hoursData)) {
            return null;
        }

        // Find the latest end_time from all shifts
        $latestEndTime = null;
        foreach ($hoursData as $shift) {
            if (isset($shift['end_time']) && !is_null($shift['end_time'])) {
                $endTime = Carbon::createFromFormat('H:i', $shift['end_time']);
                if (!$latestEndTime || $endTime->gt($latestEndTime)) {
                    $latestEndTime = $endTime;
                }
            }
        }

        return $latestEndTime;
    }

    /**
     * Calculate midnight phone on-call hours and create salary report
     */
    private function calculateMidnightPhoneOnCallHours($staff, $date, $staffEndTime, $lastTourEndTime)
    {
        // Create proper Carbon instances with the same date for comparison
        $startTimeToday = Carbon::parse($date)->setTime($staffEndTime->hour, $staffEndTime->minute);
        $midnightTime = Carbon::parse($date)->addDay()->startOfDay(); // 00:00 next day
        
        // echo "DEBUG: Staff end time: " . $startTimeToday->format('Y-m-d H:i') . "<br>";
        // echo "DEBUG: Last tour end time: " . $lastTourEndTime->format('Y-m-d H:i') . "<br>";
        
        // Calculate TOTAL on-call period in minutes from staff end time to last tour end time
        $totalOnCallMinutes = $lastTourEndTime->diffInMinutes($startTimeToday);
        
        // echo "DEBUG: Total on-call minutes: {$totalOnCallMinutes}<br>";
        
        if ($totalOnCallMinutes <= 0) {
            // echo "✓ No midnight phone hours needed for {$staff->name} on {$date}<br>";
            return;
        }

        // Calculate night hours (20:00-06:00) for ENTIRE on-call period
        $nightMinutes = $this->calculateNightHours($startTimeToday, $lastTourEndTime);
        
        // echo "DEBUG: Night minutes: {$nightMinutes}<br>";

        // Apply on-call logic: divide by 3 and round up
        $adjustedOnCallMinutes = (int) ceil($totalOnCallMinutes / 3);
        $adjustedNightMinutes = (int) ceil($nightMinutes / 3);

        // echo "DEBUG: Adjusted on-call minutes: {$adjustedOnCallMinutes}<br>";
        // echo "DEBUG: Adjusted night minutes: {$adjustedNightMinutes}<br>";

        // Check if it's a holiday (use the original date only)
        $isHoliday = $this->isHoliday(Carbon::parse($date));
        $holidayMinutes = $isHoliday ? $adjustedOnCallMinutes : 0;
        $nightHolidayMinutes = $isHoliday ? $adjustedNightMinutes : 0;

        // Create work period showing the FULL range including past midnight
        $workPeriod = $staffEndTime->format('H:i') . ' - ' . $lastTourEndTime->format('H:i') . ' (Midnight Phone)';
        
        $this->createSalaryReport($staff, $date, [
            'description' => 'Midnight Phone On-Call Hours',
            'work_periods' => $workPeriod,
            'work_hours' => $this->minutesToTimeFormat($adjustedOnCallMinutes),
            'holiday_hours' => $this->minutesToTimeFormat($holidayMinutes),
            'evening_hours' => '00:00', // No evening hours for midnight phone
            'evening_holiday_hours' => '00:00',
            'night_hours' => $this->minutesToTimeFormat($adjustedNightMinutes),
            'night_holiday_hours' => $this->minutesToTimeFormat($nightHolidayMinutes),
            'sick_leaves' => '00:00',
        ]);

        // echo "✓ Midnight Phone - {$staff->name}: {$workPeriod} (On-call: " . $this->minutesToTimeFormat($adjustedOnCallMinutes) . ")<br>";
        
        // Remove this call to prevent creating a separate next day record
        // if ($lastTourEndTime->format('Y-m-d') !== $date) {
        //     $this->handleNextDayMidnightPhone($staff, $date, $lastTourEndTime);
        // }
    }

    /**
     * Handle next day portion of midnight phone
     */
    private function handleNextDayMidnightPhone($staff, $originalDate, $lastTourEndTime)
    {
        $nextDate = Carbon::parse($originalDate)->addDay()->format('Y-m-d');
        $midnightTime = Carbon::parse($originalDate)->addDay()->startOfDay(); // 00:00 next day

        // Calculate next day minutes: 00:00 to lastTourEndTime
        $nextDayMinutes = $lastTourEndTime->diffInMinutes($midnightTime);
        $nextDayNightMinutes = $this->calculateNightHours($midnightTime, $lastTourEndTime);

        // Apply on-call logic
        $adjustedNextDayMinutes = (int) ceil($nextDayMinutes / 3);
        $adjustedNextDayNightMinutes = (int) ceil($nextDayNightMinutes / 3);

        // Check if next day is holiday
        $isHolidayNextDay = $this->isHoliday(Carbon::parse($nextDate));
        $nextDayHolidayMinutes = $isHolidayNextDay ? $adjustedNextDayMinutes : 0;
        $nextDayNightHolidayMinutes = $isHolidayNextDay ? $adjustedNextDayNightMinutes : 0;

        if ($adjustedNextDayMinutes > 0) {
            $nextDayWorkPeriod = '00:00 - ' . $lastTourEndTime->format('H:i') . ' (Midnight Phone)';
            $this->createSalaryReport($staff, $nextDate, [
                'description' => 'Midnight Phone On-Call Hours (Next Day)',
                'work_periods' => $nextDayWorkPeriod,
                'work_hours' => $this->minutesToTimeFormat($adjustedNextDayMinutes),
                'holiday_hours' => $this->minutesToTimeFormat($nextDayHolidayMinutes),
                'evening_hours' => '00:00',
                'evening_holiday_hours' => '00:00',
                'night_hours' => $this->minutesToTimeFormat($adjustedNextDayNightMinutes),
                'night_holiday_hours' => $this->minutesToTimeFormat($nextDayNightHolidayMinutes),
                'sick_leaves' => '00:00',
            ]);

            // echo "✓ Midnight Phone (Next Day) - {$staff->name}: {$nextDayWorkPeriod} (On-call: " . $this->minutesToTimeFormat($adjustedNextDayMinutes) . ")<br>";
        }
    }


public function calculateSalaryHoursForMonth($month = null)
{
    // If no month is passed, use current month
    $now = Carbon::now();
    $monthsToProcess = [];

    if ($month) {
        // If a month is provided, process only that month
        $monthsToProcess[] = $month;
    } else {
        // Always process current month
        $monthsToProcess[] = $now->format('Y-m');

        // If today is 1st, 2nd, or 3rd, also process previous month
        if ($now->day <= 3) {
            $previousMonth = $now->copy()->subMonth()->format('Y-m');
            $monthsToProcess[] = $previousMonth;
        }
    }

    foreach ($monthsToProcess as $m) {
        $startDate = Carbon::parse($m . '-01')->startOfMonth();
        $endDate = Carbon::parse($m . '-01')->endOfMonth();

        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $this->calculateSalaryHours($date->format('Y-m-d'));
        }
    }

    return response()->json([
        'status' => 'success',
        'message' => 'Salary hours calculated for: ' . implode(', ', $monthsToProcess)
    ]);
}

    

}