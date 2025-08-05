<?php
namespace App\Http\Controllers;

use App\Models\AccountingRecord;
use App\Models\AccountingIncomeExpenseType;
use App\Models\EventSalary;
use App\Models\StaffUser;
use App\Models\TourGuide;
use App\Models\SalaryReport;
use App\Models\Holiday;
use App\Models\StaffMonthlyHours;
use App\Models\StaffMissingHours;
use App\Models\CombinedStaffGuideHours;
use App\Services\CombinedStaffGuideHoursService;
use Illuminate\Http\Request;
use DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CombinedReportController extends Controller
{
    /**
     * Show hotel report creation page
     */
    public function hotelReportCreate()
    {
        return view('combined-reports.hotel-report-create');
    }

    /**
     * Show combined report creation page  
     */
    public function combinedReportCreate()
    {
        return view('combined-reports.combined-report-create');
    }

    /**
     * Generate hotel monthly report (follows guides pattern exactly)
     */
    public function hotelGetMonthlyReport(Request $request)
    {
        try {
            $monthYear = $request->input('month');
            $date = Carbon::parse($monthYear);

            // Log debug information
            Log::info("Processing hotel report for month: " . $date->format('Y-m'));

            // Get accounting records for the month
            $accountingRecords = AccountingRecord::whereYear('date', $date->year)
                ->whereMonth('date', $date->month)
                ->get();

            Log::info("Found " . $accountingRecords->count() . " total accounting records for the month");

            // Get user IDs who have accounting records
            $userIdsWithAccountingRecords = $accountingRecords->pluck('user_id')->unique()->toArray();

            Log::info("User IDs with accounting records: " . implode(', ', $userIdsWithAccountingRecords));

            // Get hotel departments
            $hotelDepartments = ['Hotel', 'Hotel Spa', 'Hotel Restaurant'];

            // Get hotel staff with hours for the month (using SalaryReport)
            // Since salary_report.user_id = staff_id, we need to get staff IDs, not user IDs
            $staffIdsWithHours = SalaryReport::whereYear('date', $date->year)
                ->whereMonth('date', $date->month)
                ->pluck('user_id') // This is actually staff_id
                ->unique();

            // Get hotel staff using staff IDs from salary reports
            $hotelStaffWithHours = StaffUser::whereIn('id', $staffIdsWithHours->toArray()) // Use staff.id
                ->whereIn('department', $hotelDepartments)
                ->whereNotNull('user_id') // Only include staff with valid user_id
                ->with('user')
                ->get()
                ->filter(function($staff) {
                    return $staff->user !== null; // Filter out staff without user records
                });

            // Get all hotel staff with accounting records (using user IDs)
            $hotelStaffWithAccountingRecords = StaffUser::whereIn('user_id', $userIdsWithAccountingRecords)
                ->whereIn('department', $hotelDepartments)
                ->whereNotNull('user_id') // Only include staff with valid user_id
                ->with('user')
                ->get()
                ->filter(function($staff) {
                    return $staff->user !== null; // Filter out staff without user records
                });

            Log::info("Hotel staff with accounting records: " . $hotelStaffWithAccountingRecords->count());
            foreach ($hotelStaffWithAccountingRecords as $staff) {
                Log::info("- {$staff->name} (user_id: {$staff->user_id})");
            }

            // Combine both collections and remove duplicates
            $allHotelStaff = $hotelStaffWithHours->merge($hotelStaffWithAccountingRecords)->unique('id');

            // Filter staff: include ALL non-interns + ONLY interns who have accounting records
            $validHotelStaff = $allHotelStaff->filter(function ($staff) use ($userIdsWithAccountingRecords) {
                // Extra safety check - ensure user exists
                if (!$staff->user) {
                    return false;
                }
                
                $isIntern = $staff->user->is_intern == 1;
                $hasAccountingRecords = in_array($staff->user_id, $userIdsWithAccountingRecords);

                // Include if: not an intern OR (is intern AND has accounting records)
                return !$isIntern || ($isIntern && $hasAccountingRecords);
            });

            $validStaffIds = $validHotelStaff->pluck('id')->toArray(); // Get staff IDs for salary reports
            $validUserIds = $validHotelStaff->pluck('user_id')->toArray(); // Get user IDs for accounting records

            Log::info("Valid user IDs for accounting: " . implode(', ', $validUserIds));

            // Get all active accounting types (both income and expense)
            $accountingTypes = AccountingIncomeExpenseType::where('active', true)
                ->orderBy('type')
                ->orderBy('name')
                ->get();

            // Filter accounting records to only include our valid hotel staff
            $filteredAccountingRecords = $accountingRecords->whereIn('user_id', $validUserIds);
            
            Log::info("Filtered accounting records for hotel staff: " . $filteredAccountingRecords->count());
            foreach ($filteredAccountingRecords as $record) {
                Log::info("- User ID: {$record->user_id}, Type: {$record->record_type}, Amount: {$record->amount}");
            }

            // Get salary reports using staff IDs (since salary_report.user_id = staff_id)
            $salaryReports = SalaryReport::whereYear('date', $date->year)
                ->whereMonth('date', $date->month)
                ->whereIn('user_id', $validStaffIds) // user_id field contains staff_id
                ->get();

            // Group by staff_id (which is stored in user_id field)
            $salaryReports = $salaryReports->groupBy('user_id');

            // Process salary data for staff with hours
            $staffSalariesWithHours = collect();

            foreach ($salaryReports as $staffId => $userReports) {
                // Find the staff member by staff_id
                $staff = $validHotelStaff->where('id', $staffId)->first();
                
                if (!$staff || !$staff->user) {
                    continue; // Skip if no staff or no user found
                }
                
                // Check if this staff member is an intern
                $isIntern = $staff->user->is_intern == 1;

                if ($isIntern) {
                    // Calculate sick leaves first
                    $sickLeaveMinutes = $this->getSickLeavesForStaff($staff->user_id, $date);
                    
                    // For interns, set all hours to 0:00
                    $staffSalariesWithHours[$staff->user_id] = [
                        'staffId' => $staff->user_id,
                        'totalNormalHours' => '0:00',
                        'totalNormalNightHours' => '0:00',
                        'totalHolidayHours' => '0:00',
                        'totalHolidayNightHours' => '0:00',
                        'totalEveningHours' => '0:00',
                        'totalEveningHolidayHours' => '0:00',
                        'totalSickLeaves' => $this->formatMinutesToHours($sickLeaveMinutes),
                        'staff' => $staff
                    ];
                } else {
                    // For non-interns, calculate sick leaves first to determine actual work hours
                    $sickLeaveMinutes = $this->getSickLeavesForStaff($staff->user_id, $date);
                    
                    // If there are sick leave hours, work hours should be reduced accordingly
                    if ($sickLeaveMinutes > 0) {
                        // Staff member has sick leaves - set work hours to 0 and only show sick leave
                        $staffSalariesWithHours[$staff->user_id] = [
                            'staffId' => $staff->user_id,
                            'totalNormalHours' => '0:00',
                            'totalNormalNightHours' => '0:00',
                            'totalHolidayHours' => '0:00',
                            'totalHolidayNightHours' => '0:00',
                            'totalEveningHours' => '0:00',
                            'totalEveningHolidayHours' => '0:00',
                            'totalSickLeaves' => $this->formatMinutesToHours($sickLeaveMinutes),
                            'staff' => $staff
                        ];
                    } else {
                        // No sick leaves, calculate normal work hours
                        $totalWorkMinutes = 0;
                        $totalHolidayMinutes = 0;
                        $totalEveningMinutes = 0;
                        $totalEveningHolidayMinutes = 0;
                        $totalNightMinutes = 0;
                        $totalNightHolidayMinutes = 0;

                        foreach ($userReports as $report) {
                            $totalWorkMinutes += $this->timeFormatToMinutes($report->work_hours);
                            $totalHolidayMinutes += $this->timeFormatToMinutes($report->holiday_hours);
                            $totalEveningMinutes += $this->timeFormatToMinutes($report->evening_hours);
                            $totalEveningHolidayMinutes += $this->timeFormatToMinutes($report->evening_holiday_hours);
                            $totalNightMinutes += $this->timeFormatToMinutes($report->night_hours);
                            $totalNightHolidayMinutes += $this->timeFormatToMinutes($report->night_holiday_hours);
                        }

                        // Use user_id as key for consistency with accounting records
                        $staffSalariesWithHours[$staff->user_id] = [
                            'staffId' => $staff->user_id,
                            'totalNormalHours' => $this->formatMinutesToHours($totalWorkMinutes),
                            'totalNormalNightHours' => $this->formatMinutesToHours($totalNightMinutes),
                            'totalHolidayHours' => $this->formatMinutesToHours($totalHolidayMinutes),
                            'totalHolidayNightHours' => $this->formatMinutesToHours($totalNightHolidayMinutes),
                            'totalEveningHours' => $this->formatMinutesToHours($totalEveningMinutes),
                            'totalEveningHolidayHours' => $this->formatMinutesToHours($totalEveningHolidayMinutes),
                            'totalSickLeaves' => '0:00',
                            'staff' => $staff
                        ];
                    }
                }
            }

            // Create a set of user IDs that already have hours to prevent duplicates
            $userIdsWithHours = $staffSalariesWithHours->pluck('staffId')->toArray();

            // Add staff who only have accounting records (including interns with accounting records)
            foreach ($validHotelStaff as $staff) {
                // Extra safety check - ensure user exists
                if (!$staff->user) {
                    continue;
                }
                
                // Check if this user ID already exists in the hours data
                if (!in_array($staff->user_id, $userIdsWithHours)) {
                    // Calculate sick leaves for staff without salary reports
                    $sickLeaveMinutes = $this->getSickLeavesForStaff($staff->user_id, $date);
                    
                    // Use user ID as key to match the existing pattern
                    $staffSalariesWithHours[$staff->user_id] = [
                        'staffId' => $staff->user_id,
                        'totalNormalHours' => '0:00',
                        'totalNormalNightHours' => '0:00',
                        'totalHolidayHours' => '0:00',
                        'totalHolidayNightHours' => '0:00',
                        'totalEveningHours' => '0:00',
                        'totalEveningHolidayHours' => '0:00',
                        'totalSickLeaves' => $this->formatMinutesToHours($sickLeaveMinutes),
                        'staff' => $staff
                    ];
                }
            }

            // Filter out any entries with null staff to prevent errors
            $staffSalariesWithHours = $staffSalariesWithHours->filter(function ($salary) {
                return $salary['staff'] !== null && $salary['staff']->user !== null;
            });

            // Filter out staff who have 0:00 for all hour columns AND no accounting records
            $staffSalariesWithHours = $staffSalariesWithHours->filter(function ($salary) use ($filteredAccountingRecords) {
                // Check if staff has any work hours
                $hasWorkHours = $salary['totalNormalHours'] !== '0:00' ||
                    $salary['totalNormalNightHours'] !== '0:00' ||
                    $salary['totalHolidayHours'] !== '0:00' ||
                    $salary['totalHolidayNightHours'] !== '0:00' ||
                    $salary['totalEveningHours'] !== '0:00' ||
                    $salary['totalEveningHolidayHours'] !== '0:00' ||
                    $salary['totalSickLeaves'] !== '0:00';

                // Check if staff has accounting records (using user_id)
                $hasAccountingRecords = $filteredAccountingRecords->where('user_id', $salary['staff']->user_id)->count() > 0;

                // Include if staff has work hours OR has accounting records
                return $hasWorkHours || $hasAccountingRecords;
            });

            // Organize accounting records for the view
            $organizedRecords = $this->organizeAccountingRecords($filteredAccountingRecords);

            Log::info("Organized accounting records:");
            foreach ($organizedRecords as $userId => $records) {
                Log::info("User ID: {$userId}");
                foreach ($records as $type => $typeRecords) {
                    Log::info("  Type: {$type} - " . count($typeRecords) . " records");
                }
            }

            return view('combined-reports.hotel-monthly-report', [
                'staffSalaries' => $staffSalariesWithHours,
                'monthYear' => $monthYear,
                'accountingRecords' => $organizedRecords,
                'accountingTypes' => $accountingTypes,
                'staffMapping' => $validHotelStaff->keyBy('user_id')->toArray()
            ]);

        } catch (\Exception $e) {
            Log::error('Hotel report error: ' . $e->getMessage());
            return redirect()->back()->with('failed', 'Error: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for creating all departments report
     */
    public function allDepartmentsCreate()
    {
        return view('combined-reports.all-departments-report-create');
    }

    /**
     * Show the form for creating combined accountant report
     */
    public function combinedAccountantCreate()
    {
        return view('combined-reports.combined-accountant-report-create');
    }

    /**
     * Get combined accountant monthly report (NUT Staff + Guides)
     */
    public function combinedAccountantGetMonthlyReport(Request $request)
    {
        try {
            $monthYear = $request->input('month');
            
            if (!$monthYear) {
                return redirect()->back()->with('error', 'Month is required');
            }
            
            $date = Carbon::parse($monthYear);

            // First, calculate salary hours for the month to ensure all data is up to date
            $this->calculateSalaryHoursForMonth($date);

            // Get accounting records for the month
            $accountingRecords = AccountingRecord::whereYear('date', $date->year)
                ->whereMonth('date', $date->month)
                ->get();

            // Get user IDs who have accounting records
            $userIdsWithAccountingRecords = $accountingRecords->pluck('user_id')->unique()->toArray();

            // Get all active accounting types
            $accountingTypes = AccountingIncomeExpenseType::where('active', true)
                ->orderBy('type')
                ->orderBy('name')
                ->get();

            // Combined array to hold both staff and guides
            $combinedSalaries = [];

            // ========== IDENTIFY DUAL-ROLE USERS (STAFF WHO ARE ALSO GUIDES) ==========
            
            // Get all staff user_ids
            $allStaffUserIds = StaffUser::whereNotIn('department', ['Hotel', 'Hotel Spa', 'Hotel Restaurant'])
                ->whereNotNull('user_id')
                ->pluck('user_id')
                ->toArray();
            
            // Get all guide user_ids
            $allGuideUserIds = TourGuide::where('is_hidden', 0)
                ->whereNotNull('user_id')
                ->pluck('user_id')
                ->toArray();
            
            // Find dual-role users (exist in both tables)
            $dualRoleUserIds = array_intersect($allStaffUserIds, $allGuideUserIds);

            // ========== PRE-CALCULATE DUAL-ROLE USERS FROM PERSONAL REPORTS ==========
            
            $combinedHoursService = app(CombinedStaffGuideHoursService::class);
            $dualRoleCalculatedHours = [];
            
            foreach ($dualRoleUserIds as $userId) {
                try {
                    $calculatedHours = $combinedHoursService->getOrCalculate($userId, $date->year, $date->month);
                    $dualRoleCalculatedHours[$userId] = $calculatedHours;
                    
                    Log::info("Successfully calculated combined hours for dual-role user {$userId}", [
                        'total_work_hours' => $calculatedHours->total_work_hours,
                        'total_holiday_hours' => $calculatedHours->total_holiday_hours,
                        'total_night_hours' => $calculatedHours->total_night_hours,
                        'total_holiday_night_hours' => $calculatedHours->total_holiday_night_hours
                    ]);
                } catch (\Exception $e) {
                    Log::error("Failed to calculate combined hours for dual-role user {$userId}: " . $e->getMessage());
                    // Continue with original calculation logic as fallback
                }
            }

            // ========== PROCESS NUT STAFF DATA ==========
            
            // Define departments to EXCLUDE (hotel departments)
            $excludedDepartments = ['Hotel', 'Hotel Spa', 'Hotel Restaurant'];

            // Get staff IDs with salary reports for the month (excluding hotel departments)
            $staffIdsWithHours = SalaryReport::whereYear('date', $date->year)
                ->whereMonth('date', $date->month)
                ->whereNotIn('staff_department', $excludedDepartments)
                ->pluck('user_id') // This is actually staff_id
                ->unique();

            // Get staff with hours (excluding hotel departments)
            $staffWithHours = StaffUser::whereIn('id', $staffIdsWithHours->toArray())
                ->whereNotIn('department', $excludedDepartments)
                ->whereNotNull('user_id')
                ->with('user')
                ->get()
                ->filter(function($staff) {
                    return $staff->user !== null;
                });

            // Get staff with accounting records (excluding hotel departments)
            $staffWithAccountingRecords = StaffUser::whereIn('user_id', $userIdsWithAccountingRecords)
                ->whereNotIn('department', $excludedDepartments)
                ->whereNotNull('user_id')
                ->with('user')
                ->get()
                ->filter(function($staff) {
                    return $staff->user !== null;
                });

            // Combine both collections and remove duplicates
            $allStaff = $staffWithHours
                ->merge($staffWithAccountingRecords)
                ->unique('id'); // Use staff ID for uniqueness

            // Filter staff: include ALL non-interns + ONLY interns who have accounting records
            $validStaff = $allStaff->filter(function ($staff) use ($userIdsWithAccountingRecords) {
                if (!$staff->user) {
                    return false;
                }
                
                $isIntern = $staff->user->is_intern == 1;
                $hasAccountingRecords = in_array($staff->user_id, $userIdsWithAccountingRecords);

                return !$isIntern || ($isIntern && $hasAccountingRecords);
            });

            $validStaffIds = $validStaff->pluck('id')->toArray();

            // Get salary reports using staff IDs (excluding hotel departments)
            $salaryReports = SalaryReport::whereYear('date', $date->year)
                ->whereMonth('date', $date->month)
                ->whereIn('user_id', $validStaffIds)
                ->whereNotIn('staff_department', $excludedDepartments)
                ->get()
                ->groupBy('user_id');

            // Process staff data
            foreach ($salaryReports as $staffId => $userReports) {
                $staff = $validStaff->where('id', $staffId)->first();
                
                if (!$staff || !$staff->user) {
                    continue;
                }
                
                $isIntern = $staff->user->is_intern == 1;

                if ($isIntern) {
                    // Calculate missing hours and sick leaves for interns too
                    $isDualRole = in_array($staff->user_id, $dualRoleUserIds);
                    $missingAndSickLeaves = $this->calculateStaffMissingHoursAndSickLeaves($staff->id, $date->year, $date->month, $isDualRole);
                    
                    $combinedSalaries[$staff->user_id] = [
                        'staffId' => $staff->user_id,
                        'name' => $staff->name,
                        'full_name' => $staff->full_name ?? $staff->name,
                        'department' => $staff->department,
                        'totalNormalHours' => '0:00',
                        'totalNormalNightHours' => '0:00',
                        'totalHolidayHours' => '0:00',
                        'totalHolidayNightHours' => '0:00',
                        'totalSickLeaves' => $missingAndSickLeaves['sick_leaves'],
                        'staff' => $staff,
                        'type' => in_array($staff->user_id, $dualRoleUserIds) ? 'Staff+Guide' : 'Staff'
                    ];
                } else {
                    // Check if this is a dual-role user and we have pre-calculated data
                    $isDualRole = in_array($staff->user_id, $dualRoleUserIds);
                    
                    if ($isDualRole && isset($dualRoleCalculatedHours[$staff->user_id])) {
                        // Use pre-calculated data from personal reports
                        $calculatedData = $dualRoleCalculatedHours[$staff->user_id];
                        
                        $combinedSalaries[$staff->user_id] = [
                            'staffId' => $staff->user_id,
                            'name' => $staff->name,
                            'full_name' => $staff->full_name ?? $staff->name,
                            'department' => $staff->department,
                            'totalNormalHours' => $calculatedData->total_work_hours,
                            'totalNormalNightHours' => $calculatedData->total_night_hours,
                            'totalHolidayHours' => $calculatedData->total_holiday_hours,
                            'totalHolidayNightHours' => $calculatedData->total_holiday_night_hours,
                            'totalSickLeaves' => $calculatedData->total_sick_leaves,
                            'staff' => $staff,
                            'type' => 'Staff+Guide'
                        ];
                        
                        Log::info("Used pre-calculated data for dual-role user {$staff->user_id}", [
                            'totalNormalHours' => $calculatedData->total_work_hours,
                            'totalHolidayHours' => $calculatedData->total_holiday_hours,
                            'totalNormalNightHours' => $calculatedData->total_night_hours,
                            'totalHolidayNightHours' => $calculatedData->total_holiday_night_hours
                        ]);
                        
                        continue; // Skip the normal calculation logic
                    }
                    
                    // Calculate missing hours and sick leaves first (for non-dual-role or fallback)
                    $missingAndSickLeaves = $this->calculateStaffMissingHoursAndSickLeaves($staff->id, $date->year, $date->month, $isDualRole);
                    $sickLeaveMinutes = $this->timeFormatToMinutes($missingAndSickLeaves['sick_leaves']);
                    
                    // For pure staff, ALWAYS use salary_report data regardless of sick leave status
                    {
                        // Calculate normal work hours (either no sick leaves OR dual-role with sick leaves)
                        $totalWorkMinutes = 0;
                        $totalHolidayMinutes = 0;
                        $totalNightMinutes = 0;
                        $totalNightHolidayMinutes = 0;

                        foreach ($userReports as $report) {
                            $totalWorkMinutes += $this->timeFormatToMinutes($report->work_hours);
                            $totalHolidayMinutes += $this->timeFormatToMinutes($report->holiday_hours);
                            $totalNightMinutes += $this->timeFormatToMinutes($report->night_hours);
                            $totalNightHolidayMinutes += $this->timeFormatToMinutes($report->night_holiday_hours);
                        }
                        
                        // NOTE: Do NOT add missing hours here - salary_report already includes them
                        
                        $nightHoursForDisplay = $isDualRole ? 
                            $this->formatMinutesToHours($totalNightMinutes + $totalNightHolidayMinutes) : 
                            $this->formatMinutesToHours($totalNightMinutes);
                        
                        $combinedSalaries[$staff->user_id] = [
                            'staffId' => $staff->user_id,
                            'name' => $staff->name,
                            'full_name' => $staff->full_name ?? $staff->name,
                            'department' => $staff->department,
                            'totalNormalHours' => $this->formatMinutesToHours($totalWorkMinutes),
                            'totalNormalNightHours' => $nightHoursForDisplay,
                            'totalHolidayHours' => $this->formatMinutesToHours($totalHolidayMinutes),
                            'totalHolidayNightHours' => $this->formatMinutesToHours($totalNightHolidayMinutes),
                            'totalSickLeaves' => $sickLeaveMinutes > 0 ? $missingAndSickLeaves['sick_leaves'] : '0:00',
                            'staff' => $staff,
                            'type' => $isDualRole ? 'Staff+Guide' : 'Staff'
                        ];
                        
                    }
                }
            }

            // Add remaining staff (those without salary reports but have accounting records)
            $userIdsWithHours = array_column($combinedSalaries, 'staffId');

            foreach ($validStaff as $staff) {
                if (!$staff->user || in_array($staff->user_id, $userIdsWithHours)) {
                    continue;
                }

                // Check if this is a dual-role user
                $isDualRole = in_array($staff->user_id, $dualRoleUserIds);
                
                if ($isDualRole && isset($dualRoleCalculatedHours[$staff->user_id])) {
                    // Use pre-calculated data from personal reports
                    $calculatedData = $dualRoleCalculatedHours[$staff->user_id];
                    
                    $combinedSalaries[$staff->user_id] = [
                        'staffId' => $staff->user_id,
                        'name' => $staff->name,
                        'full_name' => $staff->full_name ?? $staff->name,
                        'department' => $staff->department,
                        'totalNormalHours' => $calculatedData->total_work_hours,
                        'totalNormalNightHours' => $calculatedData->total_night_hours,
                        'totalHolidayHours' => $calculatedData->total_holiday_hours,
                        'totalHolidayNightHours' => $calculatedData->total_holiday_night_hours,
                        'totalSickLeaves' => $calculatedData->total_sick_leaves,
                        'staff' => $staff,
                        'type' => 'Staff+Guide'
                    ];
                    
                    continue; // Skip the normal calculation logic
                }
                
                // Calculate missing hours and sick leaves (for non-dual-role users)
                $missingAndSickLeaves = $this->calculateStaffMissingHoursAndSickLeaves($staff->id, $date->year, $date->month, $isDualRole);
                $sickLeaveMinutes = $this->timeFormatToMinutes($missingAndSickLeaves['sick_leaves']);
                
                // For pure staff, show missing hours as work hours regardless of sick leave status
                {
                    // No sick leaves OR dual-role with sick leaves, show missing hours as work hours
                    $combinedSalaries[$staff->user_id] = [
                        'staffId' => $staff->user_id,
                        'name' => $staff->name,
                        'full_name' => $staff->full_name ?? $staff->name,
                        'department' => $staff->department,
                        'totalNormalHours' => $missingAndSickLeaves['missing_hours'],
                        'totalNormalNightHours' => '0:00',
                        'totalHolidayHours' => '0:00',
                        'totalHolidayNightHours' => '0:00',
                        'totalSickLeaves' => $sickLeaveMinutes > 0 ? $missingAndSickLeaves['sick_leaves'] : '0:00',
                        'staff' => $staff,
                        'type' => $isDualRole ? 'Staff+Guide' : 'Staff'
                    ];
                    
                }
            }

            // ========== PROCESS TOUR GUIDE DATA ==========
            
            // Get all guide IDs (tour_guides.id) with hours for the month
            $guideIdsWithHours = EventSalary::whereYear('guide_start_time', $date->year)
                ->whereMonth('guide_start_time', $date->month)
                ->pluck('guideId')
                ->unique();

            // Get all TourGuides with hours (using guide IDs, not user IDs)
            $tourGuidesWithHours = TourGuide::whereIn('id', $guideIdsWithHours->toArray())
                ->where('is_hidden', 0)
                ->with('user')
                ->get();

            // Get all TourGuides with accounting records (using user IDs)
            $tourGuidesWithAccountingRecords = TourGuide::whereIn('user_id', $userIdsWithAccountingRecords)
                ->where('is_hidden', 0)
                ->with('user')
                ->get();

            // Combine both collections and remove duplicates
            $allTourGuides = $tourGuidesWithHours->merge($tourGuidesWithAccountingRecords)->unique('id');

            // Filter guides: include ALL non-interns + ONLY interns who have accounting records
            $validTourGuides = $allTourGuides->filter(function ($guide) use ($userIdsWithAccountingRecords) {
                $isIntern = $guide->user && $guide->user->is_intern == 1;
                $hasAccountingRecords = in_array($guide->user_id, $userIdsWithAccountingRecords);

                // Include if: not an intern OR (is intern AND has accounting records)
                return !$isIntern || ($isIntern && $hasAccountingRecords);
            });

            $validGuideIds = $validTourGuides->pluck('id')->toArray(); // Use guide IDs, not user IDs

            // Filter EventSalary query to include only valid guides
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
                ->whereHas('event', function ($query) {
                    $query->where('event_id', 'NOT LIKE', '%manual-missing-hours%');
                })
                ->whereIn('guideId', $validGuideIds) // Use guide IDs directly
                ->whereIn('approval_status', [1, 2])
                ->groupBy('guideId')
                ->with('tourGuide.user');

            // Get salary data for guides with hours
            $eventSalariesWithHours = [];
            $queryResults = $query->get();
            
            foreach ($queryResults as $salary) {
                // Check if this guide is an intern
                $isIntern = $salary->tourGuide->user && $salary->tourGuide->user->is_intern == 1;

                if ($isIntern) {
                    // Calculate missing hours and sick leaves for intern guides too
                    $missingAndSickLeaves = $this->calculateGuideMissingHoursAndSickLeaves($salary->guideId, $date->year, $date->month);
                    
                    // For interns, set all hours to 0:00 but include missing hours and sick leaves
                    $eventSalariesWithHours[$salary->guideId] = [
                        'guideId' => $salary->guideId,
                        'staffId' => $salary->tourGuide->user_id, // For accounting lookup
                        'name' => $salary->tourGuide->name,
                        'full_name' => $salary->tourGuide->full_name ?? $salary->tourGuide->name,
                        'department' => 'Guide',
                        'totalNormalHours' => '0:00',
                        'totalNormalNightHours' => '0:00',
                        'totalHolidayHours' => '0:00',
                        'totalHolidayNightHours' => '0:00',
                        'totalSickLeaves' => $missingAndSickLeaves['sick_leaves'],
                        'tourGuide' => $salary->tourGuide,
                        'type' => 'Guide'
                    ];
                } else {
                    // Calculate missing hours and sick leaves for non-intern guides
                    $missingAndSickLeaves = $this->calculateGuideMissingHoursAndSickLeaves($salary->guideId, $date->year, $date->month);
                    
                    // Calculate total work hours including missing hours (properly separated)
                    $totalNormalHours = $this->sumDecimalHours(explode(',', $salary->normal_hours_list));
                    $totalNormalNightHours = $this->sumDecimalHours(explode(',', $salary->normal_night_hours_list));
                    $totalHolidayHours = $this->sumDecimalHours(explode(',', $salary->holiday_hours_list));
                    $totalHolidayNightHours = $this->sumDecimalHours(explode(',', $salary->holiday_night_hours_list));
                    
                    // Add missing hours to respective categories
                    $totalNormalHours += $this->timeFormatToMinutes($missingAndSickLeaves['normal_hours']) / 60;
                    $totalNormalNightHours += $this->timeFormatToMinutes($missingAndSickLeaves['normal_night_hours']) / 60;
                    $totalHolidayHours += $this->timeFormatToMinutes($missingAndSickLeaves['holiday_hours']) / 60;
                    $totalHolidayNightHours += $this->timeFormatToMinutes($missingAndSickLeaves['holiday_night_hours']) / 60;
                    
                    // For non-interns, calculate hours normally - keep original guide logic
                    $eventSalariesWithHours[$salary->guideId] = [
                        'guideId' => $salary->guideId,
                        'staffId' => $salary->tourGuide->user_id, // For accounting lookup
                        'name' => $salary->tourGuide->name,
                        'full_name' => $salary->tourGuide->full_name ?? $salary->tourGuide->name,
                        'department' => 'Guide',
                        'totalNormalHours' => str_replace('.', ':', number_format($totalNormalHours, 2)),
                        'totalNormalNightHours' => str_replace('.', ':', number_format($totalNormalNightHours, 2)),
                        'totalHolidayHours' => str_replace('.', ':', number_format($totalHolidayHours, 2)),
                        'totalHolidayNightHours' => str_replace('.', ':', number_format($totalHolidayNightHours, 2)),
                        'totalSickLeaves' => $missingAndSickLeaves['sick_leaves'],
                        'tourGuide' => $salary->tourGuide,
                        'type' => 'Guide'
                    ];
                }
            }

            // Create a set of guide IDs that already have hours to prevent duplicates
            $guideIdsWithHours = array_column($eventSalariesWithHours, 'guideId');

            // Add guides who only have accounting records (including interns with accounting records)
            foreach ($validTourGuides as $guide) {
                // Check if this guide ID already exists in the hours data
                if (!in_array($guide->id, $guideIdsWithHours)) {
                    // Calculate missing hours and sick leaves for guides without hours
                    $missingAndSickLeaves = $this->calculateGuideMissingHoursAndSickLeaves($guide->id, $date->year, $date->month);
                    
                    // Use guide user_id as key to match accounting records
                    $eventSalariesWithHours[$guide->user_id] = [
                        'guideId' => $guide->id,
                        'staffId' => $guide->user_id, // For accounting records lookup
                        'name' => $guide->name,
                        'full_name' => $guide->full_name ?? $guide->name,
                        'department' => 'Guide',
                        'totalNormalHours' => $missingAndSickLeaves['normal_hours'],
                        'totalNormalNightHours' => $missingAndSickLeaves['normal_night_hours'],
                        'totalHolidayHours' => $missingAndSickLeaves['holiday_hours'],
                        'totalHolidayNightHours' => $missingAndSickLeaves['holiday_night_hours'],
                        'totalSickLeaves' => $missingAndSickLeaves['sick_leaves'],
                        'tourGuide' => $guide,
                        'type' => 'Guide'
                    ];
                }
            }

            // Add guide data to combined collection
            foreach ($eventSalariesWithHours as $key => $guideData) {
                if (isset($guideData['tourGuide']) && $guideData['tourGuide'] !== null) {
                    $userId = $guideData['tourGuide']->user_id;
                    
                    
                    // Check if this is a dual-role user (staff + guide)
                    if (in_array($userId, $dualRoleUserIds)) {
                        // Skip dual-role users as they are handled with pre-calculated data from personal reports
                        if (isset($dualRoleCalculatedHours[$userId])) {
                            Log::info("Skipping guide processing for dual-role user {$userId} - using pre-calculated data");
                            continue;
                        }
                        
                        // Fallback: If pre-calculated data is not available, use old logic
                        if (isset($combinedSalaries[$userId])) {
                            $combinedSalaries[$userId] = $this->addGuideHoursToStaff(
                                $combinedSalaries[$userId], 
                                $guideData, 
                                $date
                            );
                        } else {
                            // Staff not in system but guide exists - shouldn't happen, but handle gracefully
                            $combinedSalaries[$userId] = $guideData;
                            $combinedSalaries[$userId]['type'] = 'Staff+Guide';
                        }
                    } else {
                        // Pure guide (not staff) - add normally
                        $combinedSalaries[$userId] = $guideData;
                    }
                }
            }

            // Filter out entries with null staff/guide
            $combinedSalaries = array_filter($combinedSalaries, function ($salary) {
                return (isset($salary['staff']) && $salary['staff'] !== null && $salary['staff']->user !== null) ||
                       (isset($salary['tourGuide']) && $salary['tourGuide'] !== null);
            });

            // Filter out entries who have 0:00 for all hour columns AND no accounting records
            $combinedSalaries = array_filter($combinedSalaries, function ($salary) use ($accountingRecords) {
                $hasWorkHours = $salary['totalNormalHours'] !== '0:00' ||
                    $salary['totalNormalNightHours'] !== '0:00' ||
                    $salary['totalHolidayHours'] !== '0:00' ||
                    $salary['totalHolidayNightHours'] !== '0:00' ||
                    (isset($salary['totalSickLeaves']) && $salary['totalSickLeaves'] !== '0:00');

                // Get user_id for accounting records lookup
                $userId = isset($salary['staff']) ? $salary['staff']->user_id : 
                         (isset($salary['tourGuide']) ? $salary['tourGuide']->user_id : null);
                
                $hasAccountingRecords = $userId && $accountingRecords->where('user_id', $userId)->count() > 0;

                return $hasWorkHours || $hasAccountingRecords;
            });

            // Organize accounting records
            $organizedRecords = $this->organizeAccountingRecords($accountingRecords);

            return view('combined-reports.combined-accountant-monthly-report', [
                'staffSalaries' => collect($combinedSalaries),
                'monthYear' => $monthYear,
                'accountingRecords' => $organizedRecords,
                'accountingTypes' => $accountingTypes,
                'staffMapping' => $validStaff->keyBy('user_id')->toArray()
            ]);

        } catch (\Exception $e) {
            Log::error('Combined accountant report error: ' . $e->getMessage());
            return redirect()->back()->with('failed', 'Error: ' . $e->getMessage());
        }
    }

    /**
     * Get monthly report for all departments (excluding hotel departments)
     */
    public function allDepartmentsGetMonthlyReport(Request $request)
    {
        try {
            $monthYear = $request->input('month');
            $date = Carbon::parse($monthYear);

            // Get accounting records for the month
            $accountingRecords = AccountingRecord::whereYear('date', $date->year)
                ->whereMonth('date', $date->month)
                ->get();

            // Get user IDs who have accounting records
            $userIdsWithAccountingRecords = $accountingRecords->pluck('user_id')->unique()->toArray();

            // Define departments to EXCLUDE (hotel departments)
            $excludedDepartments = ['Hotel', 'Hotel Spa', 'Hotel Restaurant'];

            // Get staff IDs with salary reports for the month (excluding hotel departments)
            $staffIdsWithHours = SalaryReport::whereYear('date', $date->year)
                ->whereMonth('date', $date->month)
                ->whereNotIn('staff_department', $excludedDepartments)
                ->pluck('user_id') // This is actually staff_id
                ->unique();

            // Get staff with hours (excluding hotel departments)
            $staffWithHours = StaffUser::whereIn('id', $staffIdsWithHours->toArray())
                ->whereNotIn('department', $excludedDepartments)
                ->whereNotNull('user_id')
                ->with('user')
                ->get()
                ->filter(function($staff) {
                    return $staff->user !== null;
                });

            // Get staff with accounting records (excluding hotel departments)
            $staffWithAccountingRecords = StaffUser::whereIn('user_id', $userIdsWithAccountingRecords)
                ->whereNotIn('department', $excludedDepartments)
                ->whereNotNull('user_id')
                ->with('user')
                ->get()
                ->filter(function($staff) {
                    return $staff->user !== null;
                });

            // Combine both collections and remove duplicates
            $allStaff = $staffWithHours
                ->merge($staffWithAccountingRecords)
                ->unique('id'); // Use staff ID for uniqueness

            // Filter staff: include ALL non-interns + ONLY interns who have accounting records
            $validStaff = $allStaff->filter(function ($staff) use ($userIdsWithAccountingRecords) {
                if (!$staff->user) {
                    return false;
                }
                
                $isIntern = $staff->user->is_intern == 1;
                $hasAccountingRecords = in_array($staff->user_id, $userIdsWithAccountingRecords);

                return !$isIntern || ($isIntern && $hasAccountingRecords);
            });

            $validStaffIds = $validStaff->pluck('id')->toArray();
            $validUserIds = $validStaff->pluck('user_id')->toArray();

            // Get all active accounting types
            $accountingTypes = AccountingIncomeExpenseType::where('active', true)
                ->orderBy('type')
                ->orderBy('name')
                ->get();

            // Filter accounting records
            $filteredAccountingRecords = $accountingRecords->whereIn('user_id', $validUserIds);

            // Get salary reports using staff IDs (excluding hotel departments)
            $salaryReports = SalaryReport::whereYear('date', $date->year)
                ->whereMonth('date', $date->month)
                ->whereIn('user_id', $validStaffIds)
                ->whereNotIn('staff_department', $excludedDepartments)
                ->get()
                ->groupBy('user_id');

            // Process staff data
            $staffSalariesWithHours = collect();

            foreach ($salaryReports as $staffId => $userReports) {
                $staff = $validStaff->where('id', $staffId)->first();
                
                if (!$staff || !$staff->user) {
                    continue;
                }
                
                $isIntern = $staff->user->is_intern == 1;

                if ($isIntern) {
                    // Calculate sick leaves first
                    $sickLeaveMinutes = $this->getSickLeavesForStaff($staff->user_id, $date);
                    
                    $staffSalariesWithHours[$staff->user_id] = [
                        'staffId' => $staff->user_id,
                        'totalNormalHours' => '0:00',
                        'totalNormalNightHours' => '0:00',
                        'totalHolidayHours' => '0:00',
                        'totalHolidayNightHours' => '0:00',
                        'totalSickLeaves' => $this->formatMinutesToHours($sickLeaveMinutes),
                        'staff' => $staff,
                        'type' => 'staff'
                    ];
                } else {
                    // Calculate sick leaves first to determine actual work hours
                    $sickLeaveMinutes = $this->getSickLeavesForStaff($staff->user_id, $date);
                    
                    if ($sickLeaveMinutes > 0) {
                        // Staff member has sick leaves - set work hours to 0 and only show sick leave
                        $staffSalariesWithHours[$staff->user_id] = [
                            'staffId' => $staff->user_id,
                            'totalNormalHours' => '0:00',
                            'totalNormalNightHours' => '0:00',
                            'totalHolidayHours' => '0:00',
                            'totalHolidayNightHours' => '0:00',
                            'totalSickLeaves' => $this->formatMinutesToHours($sickLeaveMinutes),
                            'staff' => $staff,
                            'type' => 'staff'
                        ];
                    } else {
                        // No sick leaves, calculate normal work hours
                        $totalWorkMinutes = 0;
                        $totalHolidayMinutes = 0;
                        $totalNightMinutes = 0;
                        $totalNightHolidayMinutes = 0;

                        foreach ($userReports as $report) {
                            $totalWorkMinutes += $this->timeFormatToMinutes($report->work_hours);
                            $totalHolidayMinutes += $this->timeFormatToMinutes($report->holiday_hours);
                            $totalNightMinutes += $this->timeFormatToMinutes($report->night_hours);
                            $totalNightHolidayMinutes += $this->timeFormatToMinutes($report->night_holiday_hours);
                        }

                        $staffSalariesWithHours[$staff->user_id] = [
                            'staffId' => $staff->user_id,
                            'totalNormalHours' => $this->formatMinutesToHours($totalWorkMinutes),
                            'totalNormalNightHours' => $this->formatMinutesToHours($totalNightMinutes),
                            'totalHolidayHours' => $this->formatMinutesToHours($totalHolidayMinutes),
                            'totalHolidayNightHours' => $this->formatMinutesToHours($totalNightHolidayMinutes),
                            'totalSickLeaves' => '0:00',
                            'staff' => $staff,
                            'type' => 'staff'
                        ];
                    }
                }
            }

            // Add remaining staff (those without salary reports but have accounting records)
            $userIdsWithHours = $staffSalariesWithHours->pluck('staffId')->toArray();

            foreach ($validStaff as $staff) {
                if (!$staff->user || in_array($staff->user_id, $userIdsWithHours)) {
                    continue;
                }

                // Calculate sick leaves for staff without salary reports
                $sickLeaveMinutes = $this->getSickLeavesForStaff($staff->user_id, $date);

                $staffSalariesWithHours[$staff->user_id] = [
                    'staffId' => $staff->user_id,
                    'totalNormalHours' => '0:00',
                    'totalNormalNightHours' => '0:00',
                    'totalHolidayHours' => '0:00',
                    'totalHolidayNightHours' => '0:00',
                    'totalSickLeaves' => $this->formatMinutesToHours($sickLeaveMinutes),
                    'staff' => $staff,
                    'type' => 'staff'
                ];
            }

            // Filter out entries with null staff
            $staffSalariesWithHours = $staffSalariesWithHours->filter(function ($salary) {
                return $salary['staff'] !== null && $salary['staff']->user !== null;
            });

            // Filter out staff who have 0:00 for all hour columns AND no accounting records
            $staffSalariesWithHours = $staffSalariesWithHours->filter(function ($salary) use ($filteredAccountingRecords) {
                $hasWorkHours = $salary['totalNormalHours'] !== '0:00' ||
                    $salary['totalNormalNightHours'] !== '0:00' ||
                    $salary['totalHolidayHours'] !== '0:00' ||
                    $salary['totalHolidayNightHours'] !== '0:00' ||
                    $salary['totalSickLeaves'] !== '0:00';

                $hasAccountingRecords = $filteredAccountingRecords->where('user_id', $salary['staff']->user_id)->count() > 0;

                return $hasWorkHours || $hasAccountingRecords;
            });

            // Organize accounting records
            $organizedRecords = $this->organizeAccountingRecords($filteredAccountingRecords);

            return view('combined-reports.all-departments-monthly-report', [
                'staffSalaries' => $staffSalariesWithHours,
                'monthYear' => $monthYear,
                'accountingRecords' => $organizedRecords,
                'accountingTypes' => $accountingTypes,
                'staffMapping' => $validStaff->keyBy('user_id')->toArray()
            ]);

        } catch (\Exception $e) {
            Log::error('All departments report error: ' . $e->getMessage());
            return redirect()->back()->with('failed', 'Error: ' . $e->getMessage());
        }
    }

    /**
     * Calculate salary hours for an entire month
     */
    public function calculateMonthSalaryHours(Request $request)
    {
        try {
            $monthYear = $request->input('month');
            $date = Carbon::parse($monthYear);
            
            $startOfMonth = $date->copy()->startOfMonth();
            $endOfMonth = $date->copy()->endOfMonth();
            
            $calculatedDays = [];
            $failedDays = [];
            
            // Calculate for each day in the month
            for ($currentDate = $startOfMonth->copy(); $currentDate->lte($endOfMonth); $currentDate->addDay()) {
                $dateString = $currentDate->format('Y-m-d');
                
                try {
                    app(SalarySummaryController::class)->calculateSalaryHours($dateString);
                    $calculatedDays[] = $dateString;
                } catch (\Exception $e) {
                    $failedDays[] = $dateString . ' (' . $e->getMessage() . ')';
                }
            }
            
            $message = "Calculation completed for " . $date->format('F Y') . ". ";
            $message .= "Successfully calculated: " . count($calculatedDays) . " days. ";
            if (!empty($failedDays)) {
                $message .= "Failed: " . count($failedDays) . " days.";
            }
            
            return response()->json([
                'success' => true,
                'message' => $message,
                'calculated_days' => $calculatedDays,
                'failed_days' => $failedDays
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate salary hours for the entire month
     */
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

    /**
     * Helper method to convert time format to minutes
     */
    private function timeFormatToMinutes($timeString)
    {
        if (empty($timeString) || $timeString === '0:00' || $timeString === '00:00') {
            return 0;
        }
        
        $parts = explode(':', $timeString);
        if (count($parts) !== 2) {
            return 0;
        }
        
        return (int)$parts[0] * 60 + (int)$parts[1];
    }

    /**
     * Helper method to format minutes to HH:MM
     */
    private function formatMinutesToHours($minutes)
    {
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;
        return sprintf("%02d:%02d", $hours, $mins);
    }

    /**
     * Helper method to organize accounting records
     */
    private function organizeAccountingRecords($accountingRecords)
    {
        $organizedRecords = [];
        foreach ($accountingRecords as $record) {
            if (!isset($organizedRecords[$record->user_id])) {
                $organizedRecords[$record->user_id] = [];
            }

            if (!isset($organizedRecords[$record->user_id][$record->record_type])) {
                $organizedRecords[$record->user_id][$record->record_type] = [];
            }

            $organizedRecords[$record->user_id][$record->record_type][] = [
                'id' => $record->id,
                'amount' => $record->amount,
                'expense_type' => $record->expense_type ?? 'payback',
                'date' => $record->date,
                'created_by' => $record->created_by
            ];
        }
        return $organizedRecords;
    }

    /**
     * Helper method to get sick leaves for a staff member
     */
    private function getSickLeavesForStaff($userId, $date)
    {
        // Get staff_id by user_id first
        $staff = StaffUser::where('user_id', $userId)->first();
        if (!$staff) {
            return 0;
        }

        // Get sick leaves for the month from salary_reports using staff_id
        $sickLeaveReports = SalaryReport::whereYear('date', $date->year)
            ->whereMonth('date', $date->month)
            ->where('user_id', $staff->id) // salary_report.user_id = staff_id
            ->get();

        $totalSickLeaveMinutes = 0;
        foreach ($sickLeaveReports as $report) {
            $totalSickLeaveMinutes += $this->timeFormatToMinutes($report->sick_leaves);
        }

        return $totalSickLeaveMinutes;
    }

    /**
     * Helper method to sum decimal hours (from AccountantController)
     */
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

    /**
     * Helper method to convert decimal to minutes (from AccountantController)
     */
    private function convertDecimalToMinutes($decimal)
    {
        // If the decimal part is 30, it means 30 minutes
        // If it's 40, it means 40 minutes, etc.
        return intval(str_pad($decimal, 2, '0'));
    }

    /**
     * Add guide hours to staff data, calculating only non-overlapping portions
     */
    private function addGuideHoursToStaff($staffData, $guideData, $date)
    {
        // Get staff working periods from salary reports to determine overlap
        $staffId = null;
        if (isset($staffData['staff'])) {
            $staffId = $staffData['staff']->id;
        }
        
        
        // Debug logging for Ramanjot (staff_id=113, user_id=228, guide_id=142)
        $isRamanjot = ($staffId == 113) || (isset($staffData['staff']) && $staffData['staff']->user_id == 228) || (isset($guideData['guideId']) && $guideData['guideId'] == 142);
        
        
        if ($isRamanjot) {
            Log::info("=== RAMANJOT CALCULATION START ===", [
                'staff_id' => $staffId,
                'user_id' => isset($staffData['staff']) ? $staffData['staff']->user_id : null,
                'guide_id' => $guideData['guideId'] ?? null,
                'staff_name' => isset($staffData['staff']) ? $staffData['staff']->name : null,
                'month' => $date->format('Y-m')
            ]);
        }
        
        // Get correct staff hours from salary_report table instead of recalculating
        
        if ($isRamanjot) {
            Log::info("Ramanjot searching for salary_report:", [
                'user_id' => $staffData['staff']->id,
                'year' => $date->year,
                'month' => $date->month
            ]);
        }
        
        $salaryReports = \App\Models\SalaryReport::where('user_id', $staffData['staff']->id)
            ->whereYear('date', $date->year)
            ->whereMonth('date', $date->month)
            ->get();
        
        
        if ($isRamanjot) {
            Log::info("Ramanjot salary_report query result:", [
                'found' => $salaryReports->count() > 0 ? 'YES' : 'NO',
                'count' => $salaryReports->count(),
                'records' => $salaryReports->toArray()
            ]);
        }
        
        if ($salaryReports->count() > 0) {
            // Sum all daily records to get monthly totals
            $totalWorkMinutes = 0;
            $totalHolidayMinutes = 0;
            $totalNightMinutes = 0;
            $totalHolidayNightMinutes = 0;
            $totalSickLeaveMinutes = 0;
            $allWorkPeriods = [];
            
            foreach ($salaryReports as $report) {
                $totalWorkMinutes += $this->timeFormatToMinutes($report->work_hours ?? '00:00');
                $totalHolidayMinutes += $this->timeFormatToMinutes($report->holiday_hours ?? '00:00');
                $totalNightMinutes += $this->timeFormatToMinutes($report->night_hours ?? '00:00');
                $totalHolidayNightMinutes += $this->timeFormatToMinutes($report->night_holiday_hours ?? '00:00');
                $totalSickLeaveMinutes += $this->timeFormatToMinutes($report->sick_leaves ?? '00:00');
                
                // Collect work periods
                if ($report->work_periods) {
                    $allWorkPeriods[] = $report->date->format('Y-m-d') . ': ' . $report->work_periods;
                }
            }
            
            // Use the aggregated values
            $staffData['totalNormalHours'] = $this->formatMinutesToHours($totalWorkMinutes);
            $staffData['totalHolidayHours'] = $this->formatMinutesToHours($totalHolidayMinutes);
            $staffData['totalNormalNightHours'] = $this->formatMinutesToHours($totalNightMinutes);
            $staffData['totalHolidayNightHours'] = $this->formatMinutesToHours($totalHolidayNightMinutes);
            $staffData['totalSickLeaves'] = $this->formatMinutesToHours($totalSickLeaveMinutes);
            
            
            if ($isRamanjot) {
                Log::info("Ramanjot STAFF hours aggregated from salary_report table:", [
                    'totalWorkMinutes' => $totalWorkMinutes,
                    'totalHolidayMinutes' => $totalHolidayMinutes,
                    'totalNightMinutes' => $totalNightMinutes,
                    'totalHolidayNightMinutes' => $totalHolidayNightMinutes,
                    'totalSickLeaveMinutes' => $totalSickLeaveMinutes,
                    'work_hours' => $staffData['totalNormalHours'],
                    'holiday_hours' => $staffData['totalHolidayHours'],
                    'night_hours' => $staffData['totalNormalNightHours'],
                    'night_holiday_hours' => $staffData['totalHolidayNightHours'],
                    'sick_leaves' => $staffData['totalSickLeaves'],
                    'work_periods_combined' => implode("\n", $allWorkPeriods)
                ]);
            }
        } else {
            
            if ($isRamanjot) {
                Log::info("Ramanjot NO salary_report found - using original staff data");
            }
        }
        
        
        if ($isRamanjot) {
            Log::info("Ramanjot STAFF hours before combining:", [
                'totalNormalHours' => $staffData['totalNormalHours'],
                'totalHolidayHours' => $staffData['totalHolidayHours'],
                'totalNormalNightHours' => $staffData['totalNormalNightHours'],
                'totalHolidayNightHours' => $staffData['totalHolidayNightHours'],
                'totalSickLeaves' => $staffData['totalSickLeaves']
            ]);
            
            Log::info("Ramanjot GUIDE hours before combining:", [
                'totalNormalHours' => $guideData['totalNormalHours'],
                'totalHolidayHours' => $guideData['totalHolidayHours'],
                'totalNormalNightHours' => $guideData['totalNormalNightHours'],
                'totalHolidayNightHours' => $guideData['totalHolidayNightHours'],
                'totalSickLeaves' => $guideData['totalSickLeaves']
            ]);
        }
        
        if (!$staffId) {
            // Can't determine overlap without staff ID, return staff data as-is
            return $staffData;
        }

        // Get staff working periods for the month
        $staffWorkingPeriods = $this->getStaffWorkingPeriods($staffId, $date);
        
        // Get guide working periods for the month  
        $guideWorkingPeriods = $this->getGuideWorkingPeriods($guideData['guideId'], $date);
        
        
        if ($isRamanjot) {
            Log::info("Ramanjot working periods:", [
                'staff_periods_count' => count($staffWorkingPeriods),
                'guide_periods_count' => count($guideWorkingPeriods),
                'staff_periods' => $staffWorkingPeriods,
                'guide_periods' => $guideWorkingPeriods
            ]);
        }
        
        // Use the EXACT same logic as personal staff-guide report
        $nonOverlappingGuideHours = $this->calculateCombinedHoursUsingPersonalReportLogic(
            $staffId,
            $guideData['guideId'],
            $date,
            $isRamanjot
        );
        
        // Add non-overlapping guide hours to staff totals
        $staffNormalMinutes = $this->timeFormatToMinutes($staffData['totalNormalHours']);
        $staffHolidayMinutes = $this->timeFormatToMinutes($staffData['totalHolidayHours']);
        $staffNightMinutes = $this->timeFormatToMinutes($staffData['totalNormalNightHours']);
        $staffHolidayNightMinutes = $this->timeFormatToMinutes($staffData['totalHolidayNightHours']);
        
        
        if ($isRamanjot) {
            Log::info("Ramanjot non-overlapping guide hours calculated:", $nonOverlappingGuideHours);
            Log::info("Ramanjot staff minutes before adding guide hours:", [
                'staffNormalMinutes' => $staffNormalMinutes,
                'staffHolidayMinutes' => $staffHolidayMinutes,
                'staffNightMinutes' => $staffNightMinutes,
                'staffHolidayNightMinutes' => $staffHolidayNightMinutes
            ]);
        }
        
        // Add guide hours
        $totalNormalMinutes = $staffNormalMinutes + $nonOverlappingGuideHours['normal'];
        $totalHolidayMinutes = $staffHolidayMinutes + $nonOverlappingGuideHours['holiday'];
        $totalNightMinutes = $staffNightMinutes + $nonOverlappingGuideHours['night'];
        $totalHolidayNightMinutes = $staffHolidayNightMinutes + $nonOverlappingGuideHours['holiday_night'];
        
        // Combine regular and holiday night hours into totalNormalNightHours
        $combinedNightMinutes = $totalNightMinutes + $totalHolidayNightMinutes;
        
        // Handle sick leaves - staff sick leaves are already in salary_report, just add guide sick leaves
        $staffSickLeaveMinutes = $this->timeFormatToMinutes($staffData['totalSickLeaves']);
        $guideSickLeaveMinutes = $this->timeFormatToMinutes($guideData['totalSickLeaves']);
        
        $totalSickLeaveMinutes = $staffSickLeaveMinutes + $guideSickLeaveMinutes;
        
        
        if ($isRamanjot) {
            Log::info("Ramanjot sick leaves FINAL (guide missing hours moved to work calculation):", [
                'staffSickLeaveMinutes' => $staffSickLeaveMinutes,
                'guideSickLeaveMinutes' => $guideSickLeaveMinutes,
                'totalSickLeaveMinutes' => $totalSickLeaveMinutes,
                'staff_sick_leaves_formatted' => $staffData['totalSickLeaves'],
                'guide_sick_leaves_formatted' => $guideData['totalSickLeaves']
            ]);
        }
        
        
        if ($isRamanjot) {
            Log::info("Ramanjot totals after adding guide hours:", [
                'totalNormalMinutes' => $totalNormalMinutes,
                'totalHolidayMinutes' => $totalHolidayMinutes,
                'totalNightMinutes' => $totalNightMinutes,
                'totalHolidayNightMinutes' => $totalHolidayNightMinutes,
                'combinedNightMinutes' => $combinedNightMinutes,
                'totalSickLeaveMinutes' => $totalSickLeaveMinutes
            ]);
        }
        
        // Update the staff data with combined hours
        $staffData['totalNormalHours'] = $this->formatMinutesToHours($totalNormalMinutes);
        $staffData['totalHolidayHours'] = $this->formatMinutesToHours($totalHolidayMinutes);
        $staffData['totalNormalNightHours'] = $this->formatMinutesToHours($combinedNightMinutes);
        $staffData['totalHolidayNightHours'] = $this->formatMinutesToHours($totalHolidayNightMinutes);
        $staffData['totalSickLeaves'] = $this->formatMinutesToHours($totalSickLeaveMinutes);
        $staffData['type'] = 'Staff+Guide';
        
        
        if ($isRamanjot) {
            Log::info("=== RAMANJOT FINAL COMBINED RESULTS ===", [
                'totalNormalHours' => $staffData['totalNormalHours'],
                'totalHolidayHours' => $staffData['totalHolidayHours'],
                'totalNormalNightHours' => $staffData['totalNormalNightHours'],
                'totalHolidayNightHours' => $staffData['totalHolidayNightHours'],
                'totalSickLeaves' => $staffData['totalSickLeaves']
            ]);
        }
        
        return $staffData;
    }

    /**
     * Get staff working periods from StaffMonthlyHours table
     */
    private function getStaffWorkingPeriods($staffId, $date)
    {
        // Get actual staff working hours from StaffMonthlyHours
        $staffHours = StaffMonthlyHours::where('staff_id', $staffId)
            ->whereYear('date', $date->year)
            ->whereMonth('date', $date->month)
            ->get();
        
        $periods = [];
        foreach ($staffHours as $record) {
            $recordDate = \Carbon\Carbon::parse($record->date);
            $recordDateStr = $recordDate->format('Y-m-d');
            
            // Process each shift in the record
            $hoursData = $record->hours_data;
            
            // Handle JSON decoding if needed
            if (is_string($hoursData)) {
                $hoursData = json_decode($hoursData, true);
            }
            
            if (isset($hoursData) && is_array($hoursData)) {
                foreach ($hoursData as $shift) {
                    if (isset($shift['start_time']) && isset($shift['end_time'])) {
                        try {
                            // Create proper Carbon instances for start and end times
                            $startTime = \Carbon\Carbon::parse($recordDateStr . ' ' . $shift['start_time']);
                            $endTime = \Carbon\Carbon::parse($recordDateStr . ' ' . $shift['end_time']);
                            
                            // Handle overnight shifts
                            if ($endTime->lt($startTime)) {
                                $endTime->addDay();
                            }
                            
                            $periods[] = [
                                'date' => $recordDateStr,
                                'start_time' => $startTime,
                                'end_time' => $endTime,
                                'shift_type' => $shift['type'] ?? 'normal'
                            ];
                        } catch (\Exception $e) {
                            // Log error but continue processing other shifts
                            Log::warning("Error parsing staff shift time for staff {$staffId} on {$recordDateStr}: " . $e->getMessage());
                            continue;
                        }
                    }
                }
            }
        }
        
        return $periods;
    }

    /**
     * Get guide working periods from event salary
     */
    private function getGuideWorkingPeriods($guideId, $date)
    {
        
        $guidePeriods = EventSalary::select('guide_start_time', 'guide_end_time', 'normal_hours', 'normal_night_hours', 'holiday_hours', 'holiday_night_hours')
            ->whereYear('guide_start_time', $date->year)
            ->whereMonth('guide_start_time', $date->month)
            ->where('guideId', $guideId)
            ->whereHas('event', function ($query) {
                $query->where('event_id', 'NOT LIKE', '%manual-missing-hours%');
            })
            ->whereIn('approval_status', [1, 2])
            ->get();
        
        return $guidePeriods;
    }

    /**
     * Calculate non-overlapping guide hours with precise night hour calculations
     */
    private function calculateNonOverlappingGuideHoursWithNightCalculation($guideWorkingPeriods, $staffWorkingPeriods, $date)
    {
        $totalNonOverlapping = [
            'normal' => 0,
            'holiday' => 0,
            'night' => 0,
            'holiday_night' => 0
        ];
        
        // Get holidays for the month from holidays table
        $holidays = Holiday::whereYear('holiday_date', $date->year)
            ->whereMonth('holiday_date', $date->month)
            ->pluck('holiday_date')
            ->map(function($date) {
                return \Carbon\Carbon::parse($date)->format('Y-m-d');
            })
            ->toArray();
        
        foreach ($guideWorkingPeriods as $guidePeriod) {
            $guideStart = \Carbon\Carbon::parse($guidePeriod->guide_start_time);
            $guideEnd = \Carbon\Carbon::parse($guidePeriod->guide_end_time);
            $guideDate = $guideStart->format('Y-m-d');
            
            
            // Get all staff shifts for this date
            $staffShiftsOnDate = collect($staffWorkingPeriods)->where('date', $guideDate);
            
            if ($staffShiftsOnDate->isEmpty()) {
                // No staff work on this date, calculate all guide hours with night rules
                $guideHours = $this->calculatePreciseHoursFromTimePeriod($guideStart, $guideEnd, $holidays);
                $totalNonOverlapping['normal'] += $guideHours['normal'];
                $totalNonOverlapping['holiday'] += $guideHours['holiday'];
                $totalNonOverlapping['night'] += $guideHours['night'];
                $totalNonOverlapping['holiday_night'] += $guideHours['holiday_night'];
            } else {
                // Staff worked on this date - calculate precise non-overlapping portions
                $nonOverlappingPeriods = $this->calculateNonOverlappingTimePeriods(
                    $guideStart, 
                    $guideEnd, 
                    $staffShiftsOnDate->toArray()
                );
                
                // Calculate hours for each non-overlapping period
                foreach ($nonOverlappingPeriods as $period) {
                    $periodHours = $this->calculatePreciseHoursFromTimePeriod(
                        $period['start'], 
                        $period['end'], 
                        $holidays
                    );
                    $totalNonOverlapping['normal'] += $periodHours['normal'];
                    $totalNonOverlapping['holiday'] += $periodHours['holiday'];
                    $totalNonOverlapping['night'] += $periodHours['night'];
                    $totalNonOverlapping['holiday_night'] += $periodHours['holiday_night'];
                }
            }
        }
        
        return $totalNonOverlapping;
    }

    /**
     * Calculate non-overlapping guide hours using actual staff working periods (legacy method)
     */
    private function calculateNonOverlappingGuideHours($guideWorkingPeriods, $staffWorkingPeriods, $date, $staffData = null, $giselleStaffId = null)
    {
        
        $totalNonOverlapping = [
            'normal' => 0,
            'holiday' => 0,
            'night' => 0,
            'holiday_night' => 0
        ];
        
        $guidePeriodIndex = 0;
        foreach ($guideWorkingPeriods as $guidePeriod) {
            $guidePeriodIndex++;
            $guideStart = \Carbon\Carbon::parse($guidePeriod->guide_start_time);
            $guideEnd = \Carbon\Carbon::parse($guidePeriod->guide_end_time);
            $guideDate = $guideStart->format('Y-m-d');
            
            if (false) {
                Log::info("Giselle processing guide period {$guidePeriodIndex}:", [
                    'date' => $guideDate,
                    'guide_start' => $guideStart->format('Y-m-d H:i:s'),
                    'guide_end' => $guideEnd->format('Y-m-d H:i:s'),
                    'original_normal_hours' => $guidePeriod->normal_hours,
                    'original_holiday_hours' => $guidePeriod->holiday_hours,
                    'original_normal_night_hours' => $guidePeriod->normal_night_hours,
                    'original_holiday_night_hours' => $guidePeriod->holiday_night_hours
                ]);
            }
            
            
            // Get all staff shifts for this date
            $staffShiftsOnDate = collect($staffWorkingPeriods)->where('date', $guideDate);
            
            
            if ($staffShiftsOnDate->isEmpty()) {
                // No staff work on this date, use original guide hours from EventSalary database
                $normalMinutes = $this->convertDecimalHoursToMinutes($guidePeriod->normal_hours);
                $holidayMinutes = $this->convertDecimalHoursToMinutes($guidePeriod->holiday_hours);
                $nightMinutes = $this->convertDecimalHoursToMinutes($guidePeriod->normal_night_hours);
                $holidayNightMinutes = $this->convertDecimalHoursToMinutes($guidePeriod->holiday_night_hours);
                
                
                $totalNonOverlapping['normal'] += $normalMinutes;
                $totalNonOverlapping['holiday'] += $holidayMinutes;
                $totalNonOverlapping['night'] += $nightMinutes;
                $totalNonOverlapping['holiday_night'] += $holidayNightMinutes;
            } else {
                // Use EXACT same overlap calculation as personal staff-guide report
                $totalNonOverlappingMinutes = $this->calculateDailyNonOverlappingMinutes(
                    $guideStart, 
                    $guideEnd, 
                    $staffShiftsOnDate->toArray(),
                    false
                );
                
                $totalGuideMinutes = $guideEnd->diffInMinutes($guideStart);
                $overlappingMinutes = $totalGuideMinutes - $totalNonOverlappingMinutes;
                
                if (false) {
                    Log::info("Giselle overlap calculation for {$guideDate}:", [
                        'totalGuideMinutes' => $totalGuideMinutes,
                        'totalNonOverlappingMinutes' => $totalNonOverlappingMinutes,
                        'overlappingMinutes' => $overlappingMinutes
                    ]);
                }
                
                if ($totalNonOverlappingMinutes > 0) {
                    // Distribute the non-overlapping minutes based on the original guide hour types
                    if ($totalGuideMinutes > 0) {
                        $ratio = $totalNonOverlappingMinutes / $totalGuideMinutes;
                        
                        // Keep original logic for normal and holiday hours 
                        $normalToAdd = intval($this->convertDecimalHoursToMinutes($guidePeriod->normal_hours) * $ratio);
                        $holidayToAdd = intval($this->convertDecimalHoursToMinutes($guidePeriod->holiday_hours) * $ratio);
                        
                        if (false) {
                            Log::info("Giselle non-overlapping ratio calculation:", [
                                'ratio' => $ratio,
                                'normalToAdd' => $normalToAdd,
                                'holidayToAdd' => $holidayToAdd
                            ]);
                        }
                        
                        $totalNonOverlapping['normal'] += $normalToAdd;
                        $totalNonOverlapping['holiday'] += $holidayToAdd;
                        
                        // Get holidays for night calculation
                        $holidays = Holiday::whereYear('holiday_date', $date->year)
                            ->whereMonth('holiday_date', $date->month)
                            ->pluck('holiday_date')
                            ->map(function($date) {
                                return \Carbon\Carbon::parse($date)->format('Y-m-d');
                            })
                            ->toArray();
                        
                        // Use pre-calculated night hours from database with ratio adjustment (like personal report)
                        $originalNightMinutes = $this->convertDecimalHoursToMinutes($guidePeriod->normal_night_hours);
                        $originalHolidayNightMinutes = $this->convertDecimalHoursToMinutes($guidePeriod->holiday_night_hours);
                        
                        // Apply the same ratio as normal/holiday hours
                        $nightToAdd = intval($originalNightMinutes * $ratio);
                        $holidayNightToAdd = intval($originalHolidayNightMinutes * $ratio);
                        
                        if (false) {
                            Log::info("Giselle night hours from database with ratio:", [
                                'originalNightMinutes' => $originalNightMinutes,
                                'originalHolidayNightMinutes' => $originalHolidayNightMinutes,
                                'ratio' => $ratio,
                                'nightToAdd' => $nightToAdd,
                                'holidayNightToAdd' => $holidayNightToAdd
                            ]);
                        }
                        
                        $totalNonOverlapping['night'] += $nightToAdd;
                        $totalNonOverlapping['holiday_night'] += $holidayNightToAdd;
                    }
                } else {
                    if (false) {
                        Log::info("Giselle complete overlap - no guide hours added for {$guideDate}");
                    }
                }
            }
            
        }
        
        if (false) {
            Log::info("=== GISELLE OVERLAP CALCULATION COMPLETE ===", [
                'final_totals' => $totalNonOverlapping
            ]);
        }
        
        return $totalNonOverlapping;
    }

    /**
     * Calculate precise non-overlapping minutes between guide period and multiple staff shifts
     * Using the same simple and accurate logic as staff-guide hours report
     */
    private function calculatePreciseNonOverlappingMinutes($guideStart, $guideEnd, $staffShifts)
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
     * Calculate night hours using simple logic from staff-guide report
     */
    private function calculateSimpleNightHours($guideStart, $guideEnd, $staffShifts, $holidays)
    {
        $dateStr = $guideStart->format('Y-m-d');
        $isHoliday = in_array($dateStr, $holidays);
        
        // Calculate night hours in non-overlapping periods
        $nightHours = ['night' => 0, 'holiday_night' => 0];
        
        // Define night periods (20:00-00:00 and 00:00-06:00)
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
                
                $nightMinutes = max(0, $nightMinutes);
                
                if ($isHoliday) {
                    $nightHours['holiday_night'] += $nightMinutes;
                } else {
                    $nightHours['night'] += $nightMinutes;
                }
            }
        }
        
        return $nightHours;
    }
    
    /**
     * OLD BUGGY METHOD - keeping for reference but not used
     */
    private function calculatePreciseNonOverlappingMinutesOLD($guideStart, $guideEnd, $staffShifts)
    {
        $totalGuideMinutes = $guideEnd->diffInMinutes($guideStart);
        $totalOverlapMinutes = 0;
        
        // Create time segments to track overlaps across multiple staff shifts
        $guideSegments = [['start' => $guideStart, 'end' => $guideEnd, 'overlapped' => false]];
        
        foreach ($staffShifts as $staffShift) {
            $staffStart = $staffShift['start_time'];
            $staffEnd = $staffShift['end_time'];
            
            // Process each guide segment against this staff shift
            $newSegments = [];
            foreach ($guideSegments as $segment) {
                if ($segment['overlapped']) {
                    // Already overlapped, keep as is
                    $newSegments[] = $segment;
                    continue;
                }
                
                $segStart = $segment['start'];
                $segEnd = $segment['end'];
                
                // Check if this guide segment overlaps with staff shift
                $overlapStart = max($segStart, $staffStart);
                $overlapEnd = min($segEnd, $staffEnd);
                
                if ($overlapStart < $overlapEnd) {
                    // There is overlap, split the segment
                    
                    // Add non-overlapping part before overlap (if any)
                    if ($segStart < $overlapStart) {
                        $newSegments[] = [
                            'start' => $segStart,
                            'end' => $overlapStart,
                            'overlapped' => false
                        ];
                    }
                    
                    // Add overlapped part
                    $newSegments[] = [
                        'start' => $overlapStart,
                        'end' => $overlapEnd,
                        'overlapped' => true
                    ];
                    
                    // Add non-overlapping part after overlap (if any)
                    if ($overlapEnd < $segEnd) {
                        $newSegments[] = [
                            'start' => $overlapEnd,
                            'end' => $segEnd,
                            'overlapped' => false
                        ];
                    }
                } else {
                    // No overlap, keep segment as is
                    $newSegments[] = $segment;
                }
            }
            
            $guideSegments = $newSegments;
        }
        
        // Calculate total non-overlapping minutes
        $nonOverlappingMinutes = 0;
        foreach ($guideSegments as $segment) {
            if (!$segment['overlapped']) {
                $nonOverlappingMinutes += $segment['end']->diffInMinutes($segment['start']);
            }
        }
        
        return $nonOverlappingMinutes;
    }

    /**
     * Calculate non-overlapping minutes between guide and staff periods (legacy method)
     */
    private function calculateNonOverlappingMinutes($guideStart, $guideEnd, $staffStart, $staffEnd)
    {
        $totalGuideMinutes = $guideEnd->diffInMinutes($guideStart);
        
        // Find overlap period
        $overlapStart = max($guideStart, $staffStart);
        $overlapEnd = min($guideEnd, $staffEnd);
        
        $overlapMinutes = 0;
        if ($overlapStart < $overlapEnd) {
            $overlapMinutes = $overlapEnd->diffInMinutes($overlapStart);
        }
        
        return max(0, $totalGuideMinutes - $overlapMinutes);
    }

    /**
     * Convert decimal hours to minutes for guide calculations
     */
    private function convertDecimalHoursToMinutes($decimalHours)
    {
        if (empty($decimalHours) || $decimalHours === '0.00') {
            return 0;
        }
        
        // Handle decimal format (e.g., 6.40 means 6 hours and 40 minutes)
        if (strpos($decimalHours, '.') !== false) {
            list($h, $m) = explode('.', $decimalHours);
            return (intval($h) * 60) + $this->convertDecimalToMinutes($m);
        }
        
        return intval($decimalHours) * 60;
    }

    /**
     * Calculate precise hours from a time period with night and holiday rules
     */
    private function calculatePreciseHoursFromTimePeriod($startTime, $endTime, $holidays)
    {
        $totalMinutes = $endTime->diffInMinutes($startTime);
        $dateStr = $startTime->format('Y-m-d');
        $isHoliday = in_array($dateStr, $holidays);
        
        // Initialize hour counters
        $hours = [
            'normal' => 0,
            'holiday' => 0,
            'night' => 0,
            'holiday_night' => 0
        ];
        
        // Define night periods for the current date
        $nightPeriods = $this->getNightPeriodsForDate($startTime);
        
        // Process the work period minute by minute to categorize correctly
        $currentTime = $startTime->copy();
        
        while ($currentTime < $endTime) {
            $nextMinute = $currentTime->copy()->addMinute();
            if ($nextMinute > $endTime) {
                $nextMinute = $endTime;
            }
            
            $minutesToAdd = $nextMinute->diffInMinutes($currentTime);
            $isNightTime = $this->isTimeInNightPeriods($currentTime, $nightPeriods);
            
            if ($isHoliday) {
                if ($isNightTime) {
                    $hours['holiday_night'] += $minutesToAdd;
                } else {
                    $hours['holiday'] += $minutesToAdd;
                }
            } else {
                if ($isNightTime) {
                    $hours['night'] += $minutesToAdd;
                } else {
                    $hours['normal'] += $minutesToAdd;
                }
            }
            
            $currentTime = $nextMinute;
        }
        
        return $hours;
    }
    
    /**
     * Get night periods for a specific date (20:00-00:00 and 00:00-06:00)
     */
    private function getNightPeriodsForDate($dateTime)
    {
        $date = $dateTime->format('Y-m-d');
        $previousDate = $dateTime->copy()->subDay()->format('Y-m-d');
        
        return [
            // Previous day 20:00 to current day 00:00
            [
                'start' => \Carbon\Carbon::parse($previousDate . ' 20:00:00'),
                'end' => \Carbon\Carbon::parse($date . ' 00:00:00')
            ],
            // Current day 00:00 to 06:00
            [
                'start' => \Carbon\Carbon::parse($date . ' 00:00:00'),
                'end' => \Carbon\Carbon::parse($date . ' 06:00:00')
            ],
            // Current day 20:00 to next day 00:00
            [
                'start' => \Carbon\Carbon::parse($date . ' 20:00:00'),
                'end' => \Carbon\Carbon::parse($date . ' 23:59:59')->addMinute()
            ]
        ];
    }
    
    /**
     * Check if a time falls within night periods
     */
    private function isTimeInNightPeriods($time, $nightPeriods)
    {
        foreach ($nightPeriods as $period) {
            if ($time >= $period['start'] && $time < $period['end']) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Calculate non-overlapping time periods between guide and staff shifts
     */
    private function calculateNonOverlappingTimePeriods($guideStart, $guideEnd, $staffShifts)
    {
        // Create time segments to track overlaps across multiple staff shifts
        $guideSegments = [['start' => $guideStart, 'end' => $guideEnd, 'overlapped' => false]];
        
        foreach ($staffShifts as $staffShift) {
            $staffStart = $staffShift['start_time'];
            $staffEnd = $staffShift['end_time'];
            
            // Process each guide segment against this staff shift
            $newSegments = [];
            foreach ($guideSegments as $segment) {
                if ($segment['overlapped']) {
                    // Already overlapped, keep as is
                    $newSegments[] = $segment;
                    continue;
                }
                
                $segStart = $segment['start'];
                $segEnd = $segment['end'];
                
                // Check if this guide segment overlaps with staff shift
                $overlapStart = max($segStart, $staffStart);
                $overlapEnd = min($segEnd, $staffEnd);
                
                if ($overlapStart < $overlapEnd) {
                    // There is overlap, split the segment
                    
                    // Add non-overlapping part before overlap (if any)
                    if ($segStart < $overlapStart) {
                        $newSegments[] = [
                            'start' => $segStart,
                            'end' => $overlapStart,
                            'overlapped' => false
                        ];
                    }
                    
                    // Add overlapped part
                    $newSegments[] = [
                        'start' => $overlapStart,
                        'end' => $overlapEnd,
                        'overlapped' => true
                    ];
                    
                    // Add non-overlapping part after overlap (if any)
                    if ($overlapEnd < $segEnd) {
                        $newSegments[] = [
                            'start' => $overlapEnd,
                            'end' => $segEnd,
                            'overlapped' => false
                        ];
                    }
                } else {
                    // No overlap, keep segment as is
                    $newSegments[] = $segment;
                }
            }
            
            $guideSegments = $newSegments;
        }
        
        // Return only non-overlapping periods
        $nonOverlappingPeriods = [];
        foreach ($guideSegments as $segment) {
            if (!$segment['overlapped']) {
                $nonOverlappingPeriods[] = [
                    'start' => $segment['start'],
                    'end' => $segment['end']
                ];
            }
        }
        
        return $nonOverlappingPeriods;
    }
    
    /**
     * Calculate only night hours from a time period (simpler version for night supplements)
     */
    private function calculateNightHoursFromTimePeriod($startTime, $endTime, $holidays)
    {
        // Initialize night hour counters only
        $nightHours = [
            'night' => 0,
            'holiday_night' => 0
        ];
        
        // Get the date range that this time period spans
        $currentDate = $startTime->copy()->startOfDay();
        $endDate = $endTime->copy()->startOfDay();
        
        // Process each date that the period spans
        while ($currentDate <= $endDate) {
            $dateStr = $currentDate->format('Y-m-d');
            $isHoliday = in_array($dateStr, $holidays);
            
            // Define night periods for this specific date: 20:00-00:00 and 00:00-06:00
            $nightPeriods = [
                // This day 20:00 to next day 00:00
                [
                    'start' => \Carbon\Carbon::parse($dateStr . ' 20:00:00'),
                    'end' => \Carbon\Carbon::parse($dateStr . ' 23:59:59')->addMinute(),
                    'holiday' => $isHoliday
                ],
                // This day 00:00 to 06:00 
                [
                    'start' => \Carbon\Carbon::parse($dateStr . ' 00:00:00'),
                    'end' => \Carbon\Carbon::parse($dateStr . ' 06:00:00'),
                    'holiday' => $isHoliday
                ]
            ];
            
            // Check overlap with each night period for this date
            foreach ($nightPeriods as $nightPeriod) {
                $overlapStart = max($startTime, $nightPeriod['start']);
                $overlapEnd = min($endTime, $nightPeriod['end']);
                
                if ($overlapStart < $overlapEnd) {
                    $overlapMinutes = $overlapEnd->diffInMinutes($overlapStart);
                    
                    if ($nightPeriod['holiday']) {
                        $nightHours['holiday_night'] += $overlapMinutes;
                    } else {
                        $nightHours['night'] += $overlapMinutes;
                    }
                }
            }
            
            $currentDate->addDay();
        }
        
        return $nightHours;
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
        $startOfMonth = Carbon::create($year, $month, 1)->startOfDay();
        $endOfMonth = Carbon::create($year, $month, 1)->endOfMonth()->endOfDay();
        
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
     * Calculate missing hours and sick leaves totals for staff
     */
    private function calculateStaffMissingHoursAndSickLeaves($staffId, $year, $month, $isDualRole = false)
    {
        $missingHours = $this->getStaffMissingHours($staffId, $year, $month);
        
        // Calculate missing hours totals
        $totalMissingMinutes = 0;
        foreach ($missingHours as $missing) {
            $startTime = Carbon::parse($missing->start_time);
            $endTime = Carbon::parse($missing->end_time);
            $totalMissingMinutes += $endTime->diffInMinutes($startTime);
        }
        
        // Calculate sick leave totals
        $totalSickLeaveMinutes = 0;
        
        if ($isDualRole) {
            // For dual-role (Staff+Guide), use supervisor sick leaves table
            $sickLeaves = $this->getStaffSickLeaves($staffId, $year, $month);
            $startOfMonth = Carbon::create($year, $month, 1)->startOfDay();
            $endOfMonth = Carbon::create($year, $month, 1)->endOfMonth()->endOfDay();
            
            foreach ($sickLeaves as $sickLeave) {
                $startDate = Carbon::parse($sickLeave->start_date)->startOfDay();
                $endDate = Carbon::parse($sickLeave->end_date)->endOfDay();
                
                // Calculate overlapping days with the report month
                $overlapStart = max($startDate, $startOfMonth);
                $overlapEnd = min($endDate, $endOfMonth);
                
                if ($overlapStart <= $overlapEnd) {
                    $days = $overlapStart->diffInDays($overlapEnd) + 1;
                    $totalSickLeaveMinutes += $days * 8 * 60; // 8 hours per day
                }
            }
        } else {
            // For staff-only employees, use sick leave hours from salary report
            // Note: In salary_reports table, user_id field contains the staff_id
            $salaryReports = SalaryReport::whereYear('date', $year)
                ->whereMonth('date', $month)
                ->where('user_id', $staffId)
                ->get();
                
            foreach ($salaryReports as $report) {
                $totalSickLeaveMinutes += $this->timeFormatToMinutes($report->sick_leaves ?? '0:00');
            }
        }
        
        return [
            'missing_hours' => $this->formatMinutesToHours($totalMissingMinutes),
            'sick_leaves' => $this->formatMinutesToHours($totalSickLeaveMinutes)
        ];
    }

    /**
     * Calculate missing hours and sick leaves totals for guides
     */
    private function calculateGuideMissingHoursAndSickLeaves($guideId, $year, $month)
    {
        $missingHours = $this->getGuideMissingHours($guideId, $year, $month);
        $sickLeaves = $this->getGuideSickLeaves($guideId, $year, $month);
        
        // Calculate missing hours totals including ALL hour types (like Staff+Guide report)
        $totalNormalMinutes = 0;
        $totalNormalNightMinutes = 0;
        $totalHolidayMinutes = 0;
        $totalHolidayNightMinutes = 0;
        
        foreach ($missingHours as $missing) {
            $totalNormalMinutes += $missing->normal_hours * 60;
            $totalNormalNightMinutes += $missing->normal_night_hours * 60;
            $totalHolidayMinutes += $missing->holiday_hours * 60;
            $totalHolidayNightMinutes += $missing->holiday_night_hours * 60;
        }
        
        // Calculate sick leave totals
        $totalSickLeaveMinutes = 0;
        foreach ($sickLeaves as $sickLeave) {
            $startTime = Carbon::parse($sickLeave->start_time);
            $endTime = Carbon::parse($sickLeave->end_time);
            $totalSickLeaveMinutes += $endTime->diffInMinutes($startTime);
        }
        
        return [
            'normal_hours' => $this->formatMinutesToHours($totalNormalMinutes),
            'normal_night_hours' => $this->formatMinutesToHours($totalNormalNightMinutes),
            'holiday_hours' => $this->formatMinutesToHours($totalHolidayMinutes),
            'holiday_night_hours' => $this->formatMinutesToHours($totalHolidayNightMinutes),
            'sick_leaves' => $this->formatMinutesToHours($totalSickLeaveMinutes)
        ];
    }

    /**
     * Calculate daily night hours - EXACT copy from StaffController
     * This ensures identical night hours calculation as personal staff-guide report
     */
    private function calculateDailyNightHours($guideStart, $guideEnd, $staffShifts, $holidays, $debugMode = false)
    {
        $dateStr = $guideStart->format('Y-m-d');
        $isHoliday = in_array($dateStr, $holidays);
        
        if ($debugMode) {
            Log::info("Debug calculateDailyNightHours:", [
                'date' => $dateStr,
                'isHoliday' => $isHoliday,
                'guideStart' => $guideStart->format('Y-m-d H:i:s'),
                'guideEnd' => $guideEnd->format('Y-m-d H:i:s'),
                'staffShiftsCount' => count($staffShifts)
            ]);
        }
        
        // Use EXACT same logic as StaffController calculateNightHours method
        $totalNightMinutes = $this->calculateNightHours($guideStart, $guideEnd);
        
        if ($debugMode) {
            Log::info("Debug night hours from calculateNightHours method:", [
                'totalNightMinutes' => $totalNightMinutes
            ]);
        }
        
        // Return in the expected format
        $nightHours = ['night' => 0, 'holiday_night' => 0];
        
        if ($totalNightMinutes > 0) {
            if ($isHoliday) {
                $nightHours['holiday_night'] = $totalNightMinutes;
            } else {
                $nightHours['night'] = $totalNightMinutes;
            }
        }
        
        if ($debugMode) {
            Log::info("Debug night hours calculation complete:", [
                'nightHours' => $nightHours,
                'total_night_minutes' => $nightHours['night'],
                'total_holiday_night_minutes' => $nightHours['holiday_night']
            ]);
        }
        
        return $nightHours;
    }
    
    /**
     * Calculate night hours using EXACT same logic as StaffController
     * This is the core night hours calculation method from personal staff-guide report
     */
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
    
    /**
     * Calculate combined hours using EXACT same logic as personal staff-guide report
     * This directly replicates the StaffController::generateStaffGuideDailyHours logic
     */
    private function calculateCombinedHoursUsingPersonalReportLogic($staffId, $guideId, $date, $debugMode = false)
    {
        // Get holidays for the month
        $holidays = Holiday::whereYear('holiday_date', $date->year)
            ->whereMonth('holiday_date', $date->month)
            ->pluck('holiday_date')
            ->map(function($date) {
                return \Carbon\Carbon::parse($date)->format('Y-m-d');
            })
            ->toArray();
        
        // Get staff working periods from salary_report table (contains correctly processed periods)
        $salaryReports = \App\Models\SalaryReport::where('user_id', $staffId)
            ->whereYear('date', $date->year)
            ->whereMonth('date', $date->month)
            ->get();
        
        $staffWorkingPeriods = [];
        if ($salaryReports->count() > 0) {
            // Combine all work periods from all days
            $allWorkPeriodsText = [];
            foreach ($salaryReports as $report) {
                if ($report->work_periods) {
                    $allWorkPeriodsText[] = $report->date->format('Y-m-d') . ': ' . $report->work_periods;
                }
            }
            $combinedWorkPeriods = implode("\n", $allWorkPeriodsText);
            
            if ($debugMode) {
                Log::info("Debug parsing work_periods from salary_report:", [
                    'reports_count' => $salaryReports->count(),
                    'work_periods_raw' => $combinedWorkPeriods
                ]);
            }
            
            // Parse work_periods from salary_report (format: "2025-07-01: 10:00 - 18:00 (SL)")
            $periodLines = explode("\n", $combinedWorkPeriods);
            
            if ($debugMode) {
                Log::info("Debug work_periods split into lines:", [
                    'lines_count' => count($periodLines),
                    'lines' => $periodLines
                ]);
            }
            
            foreach ($periodLines as $lineIndex => $line) {
                $trimmed = trim($line);
                if (empty($trimmed)) continue;
                
                if ($debugMode) {
                    Log::info("Debug processing line {$lineIndex}:", [
                        'raw_line' => $line,
                        'trimmed_line' => $trimmed
                    ]);
                }
                
                if (preg_match('/(\d{4}-\d{2}-\d{2}):\s*(\d{2}:\d{2})\s*-\s*(\d{2}:\d{2})(?:\s*\(([^)]+)\))?/', $trimmed, $matches)) {
                    $date_str = $matches[1];
                    $start_time = $matches[2];
                    $end_time = $matches[3];
                    $shift_type = isset($matches[4]) ? $matches[4] : 'normal';
                    
                    $period = [
                        'date' => $date_str,
                        'start_time' => Carbon::parse($date_str . ' ' . $start_time),
                        'end_time' => Carbon::parse($date_str . ' ' . $end_time),
                        'shift_type' => $shift_type
                    ];
                    
                    $staffWorkingPeriods[] = $period;
                    
                    if ($debugMode) {
                        Log::info("Debug parsed period successfully:", [
                            'matches' => $matches,
                            'parsed_period' => $period
                        ]);
                    }
                } else {
                    if ($debugMode) {
                        Log::info("Debug line did NOT match regex pattern:", [
                            'line' => $trimmed,
                            'pattern' => '/(\d{4}-\d{2}-\d{2}):\s*(\d{2}:\d{2})\s*-\s*(\d{2}:\d{2})(?:\s*\(([^)]+)\))?/'
                        ]);
                    }
                }
            }
        }
        
        if ($debugMode) {
            Log::info("Debug staff periods from salary_report FINAL:", [
                'periods_count' => count($staffWorkingPeriods),
                'periods' => $staffWorkingPeriods
            ]);
        }
        
        // Get guide working periods for the month using StaffController method
        $staffController = app(\App\Http\Controllers\StaffController::class);
        $guideWorkingPeriods = $staffController->getGuideWorkingPeriodsForDaily($guideId, $date);
        
        if ($debugMode) {
            Log::info("Debug using personal report logic:", [
                'staff_periods_count' => count($staffWorkingPeriods),
                'guide_periods_count' => count($guideWorkingPeriods)
            ]);
        }
        
        $totalHours = [
            'normal' => 0,
            'holiday' => 0, 
            'night' => 0,
            'holiday_night' => 0
        ];
        
        // Add guide missing hours as additional guide periods (they should be treated as normal work)
        $guideMissingHours = \App\Models\MissingHours::where('guide_id', $guideId)
            ->whereYear('date', $date->year)
            ->whereMonth('date', $date->month)
            ->get();
        
        if ($debugMode) {
            Log::info("Debug guide missing hours found for work calculation:", [
                'count' => $guideMissingHours->count(),
                'missing_hours' => $guideMissingHours->toArray()
            ]);
        }
        
        // Convert missing hours to guide period format for processing
        foreach ($guideMissingHours as $missing) {
            $missingStart = Carbon::parse($missing->start_time);
            $missingEnd = Carbon::parse($missing->end_time);
            
            // Create a pseudo guide period object for missing hours
            $missingPeriod = (object)[
                'guide_start_time' => $missing->start_time,
                'guide_end_time' => $missing->end_time,
                'normal_hours' => $missing->normal_hours,
                'normal_night_hours' => $missing->normal_night_hours,
                'holiday_hours' => $missing->holiday_hours,
                'holiday_night_hours' => $missing->holiday_night_hours
            ];
            
            $guideWorkingPeriods[] = $missingPeriod;
            
            if ($debugMode) {
                Log::info("Debug added missing hours as guide period:", [
                    'missing_id' => $missing->id,
                    'date' => $missing->date,
                    'start_time' => $missing->start_time,
                    'end_time' => $missing->end_time,
                    'normal_hours' => $missing->normal_hours,
                    'normal_night_hours' => $missing->normal_night_hours
                ]);
            }
        }
        
        if ($debugMode) {
            Log::info("Debug total guide periods including missing hours:", [
                'original_count' => count($guideWorkingPeriods) - $guideMissingHours->count(),
                'missing_hours_count' => $guideMissingHours->count(),
                'total_count' => count($guideWorkingPeriods)
            ]);
        }
        
        // Process each guide period exactly like personal report does
        foreach ($guideWorkingPeriods as $guidePeriod) {
            $guideStart = Carbon::parse($guidePeriod->guide_start_time);
            $guideEnd = Carbon::parse($guidePeriod->guide_end_time);
            $guideDate = $guideStart->format('Y-m-d');
            
            // Get staff shifts for this specific date
            $staffShiftsOnDate = collect($staffWorkingPeriods)->where('date', $guideDate);
            
            if ($debugMode) {
                Log::info("Debug processing guide period (personal logic):", [
                    'date' => $guideDate,
                    'guide_start' => $guideStart->format('Y-m-d H:i:s'),
                    'guide_end' => $guideEnd->format('Y-m-d H:i:s'),
                    'staff_shifts_count' => $staffShiftsOnDate->count()
                ]);
            }
            
            // Calculate non-overlapping minutes using StaffController method
            $nonOverlappingMinutes = $staffController->calculateDailyNonOverlappingMinutes(
                $guideStart, 
                $guideEnd, 
                $staffShiftsOnDate->toArray()
            );
            
            // Calculate night hours using StaffController method  
            $nightHours = $staffController->calculateDailyNightHours(
                $guideStart, 
                $guideEnd, 
                $staffShiftsOnDate->toArray(), 
                $holidays
            );
            
            // Determine if this is a holiday
            $isHoliday = in_array($guideDate, $holidays);
            $holidayMinutes = $isHoliday ? $nonOverlappingMinutes : 0;
            
            if ($debugMode) {
                Log::info("Debug guide period results (personal logic):", [
                    'nonOverlappingMinutes' => $nonOverlappingMinutes,
                    'holidayMinutes' => $holidayMinutes,
                    'nightHours' => $nightHours,
                    'isHoliday' => $isHoliday
                ]);
            }
            
            // Add to totals (only non-overlapping portions count)
            $totalHours['normal'] += $nonOverlappingMinutes;
            $totalHours['holiday'] += $holidayMinutes;  
            $totalHours['night'] += $nightHours['night'];
            $totalHours['holiday_night'] += $nightHours['holiday_night'];
        }
        
        if ($debugMode) {
            Log::info("Debug total guide hours from personal logic:", $totalHours);
        }
        
        return $totalHours;
    }

    /**
     * Calculate daily non-overlapping minutes - EXACT copy from StaffController  
     * This ensures identical overlap calculation as personal staff-guide report
     */
    private function calculateDailyNonOverlappingMinutes($guideStart, $guideEnd, $staffShifts, $debugMode = false)
    {
        $totalGuideMinutes = $guideEnd->diffInMinutes($guideStart);
        $totalOverlapMinutes = 0;
        
        if ($debugMode) {
            Log::info("Debug calculateDailyNonOverlappingMinutes:", [
                'guideStart' => $guideStart->format('Y-m-d H:i:s'),
                'guideEnd' => $guideEnd->format('Y-m-d H:i:s'),
                'totalGuideMinutes' => $totalGuideMinutes,
                'staffShiftsCount' => count($staffShifts)
            ]);
        }
        
        foreach ($staffShifts as $staffShift) {
            $staffStart = $staffShift['start_time'];
            $staffEnd = $staffShift['end_time'];
            
            $overlapStart = max($guideStart, $staffStart);
            $overlapEnd = min($guideEnd, $staffEnd);
            
            if ($overlapStart < $overlapEnd) {
                $overlapMinutes = $overlapEnd->diffInMinutes($overlapStart);
                $totalOverlapMinutes += $overlapMinutes;
                
                if ($debugMode) {
                    Log::info("Debug found overlap with staff shift:", [
                        'staffStart' => $staffStart->format('Y-m-d H:i:s'),
                        'staffEnd' => $staffEnd->format('Y-m-d H:i:s'),
                        'overlapStart' => $overlapStart->format('Y-m-d H:i:s'),
                        'overlapEnd' => $overlapEnd->format('Y-m-d H:i:s'),
                        'overlapMinutes' => $overlapMinutes,
                        'totalOverlapMinutes' => $totalOverlapMinutes
                    ]);
                }
            }
        }
        
        $result = max(0, $totalGuideMinutes - $totalOverlapMinutes);
        
        if ($debugMode) {
            Log::info("Debug non-overlapping minutes result:", [
                'totalGuideMinutes' => $totalGuideMinutes,
                'totalOverlapMinutes' => $totalOverlapMinutes,
                'nonOverlappingMinutes' => $result
            ]);
        }
        
        return $result;
    }

}