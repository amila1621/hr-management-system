<?php

namespace App\Http\Controllers;

use App\Models\AccountingRecord;
use App\Models\AccountingIncomeExpenseType;
use App\Models\EventSalary;
use App\Models\StaffUser;
use App\Models\TourGuide;
use App\Models\User;
use App\Models\Holiday;
use App\Models\StaffMonthlyHours;
use App\Models\StaffMissingHours;
use App\Models\UserAccess;
use Illuminate\Http\Request;
use DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AccountantController extends Controller
{
    public function manageAccess()
    {
        // Get all active types first
        $types = AccountingIncomeExpenseType::where('active', true)
            ->orderBy('type')
            ->orderBy('name')
            ->get();

        // Get all users with their access permissions
        $allUsers = User::with('accesses')->get();

        // Separate users into two groups: those with access and those without
        $usersWithAccess = [];
        $usersWithoutAccess = [];

        foreach ($allUsers as $user) {
            $hasAnyAccess = false;

            // Check if user has any access permissions for any of the types
            foreach ($types as $type) {
                $accessTypeKey = Str::snake($type->name);
                if ($user->hasAccess($accessTypeKey)) {
                    $hasAnyAccess = true;
                    break;
                }
            }

            if ($hasAnyAccess) {
                $usersWithAccess[] = $user;
            } else {
                $usersWithoutAccess[] = $user;
            }
        }

        // Combine arrays with users having access first
        $users = array_merge($usersWithAccess, $usersWithoutAccess);

        return view('accountant.manage-access', [
            'users' => $users,
            'types' => $types
        ]);
    }

    public function updateAccess(Request $request)
    {
        $accessData = $request->input('access', []);

        // Get all active income/expense types from the database
        $types = AccountingIncomeExpenseType::where('active', true)->get();

        foreach ($accessData as $userId => $permissions) {
            $user = User::find($userId);
            if (!$user) continue;

            // Process each type from the database
            foreach ($types as $type) {
                $accessType = $type->name;
                // Convert the name to snake_case for consistency in database
                $accessTypeKey = Str::snake($accessType);

                UserAccess::updateOrCreate(
                    [
                        'user_id' => $userId,
                        'access_type' => $accessTypeKey
                    ],
                    [
                        'has_access' => !empty($permissions[$accessType]) && $permissions[$accessType] == "1"
                    ]
                );
            }
        }

        return redirect()->back()->with('success', 'Access permissions updated successfully');
    }

    public function createRecord(Request $request)
    {
        // Get selected year and month or default to current
        $selectedYear = $request->input('year', now()->year);
        $selectedMonth = $request->input('month', now()->month);

        // Get staff users first
        $staffUsers = StaffUser::select('user_id as id', 'full_name as full_name', 'name as name')
            ->orderBy('name', 'asc')
            ->get();

        // Get staff full names to filter out duplicates from tour guides
        $staffFullNames = $staffUsers->pluck('full_name')->filter()->toArray();

        // Get tour guides excluding those with full_names that exist in staff users
        $tourGuides = TourGuide::select('user_id as id', 'full_name as full_name', 'name as name')
            ->whereNotIn('full_name', $staffFullNames)
            ->orderBy('name', 'asc')
            ->get();

        // Combine the collections
        $guides = $staffUsers->concat($tourGuides)->sortBy('name');

        // Get all active accounting types
        $accountingTypes = AccountingIncomeExpenseType::where('active', true)
            ->orderBy('type')
            ->orderBy('name')
            ->get();

        $existingRecords = AccountingRecord::with(['user', 'creator'])
            ->whereYear('date', $selectedYear)
            ->whereMonth('date', $selectedMonth)
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        // Generate year options (current year and past 2 years)
        $years = range(now()->year, now()->subYears(2)->year);

        return view('accountant.create-record', [
            'guides' => $guides,
            'existingRecords' => $existingRecords,
            'selectedYear' => $selectedYear,
            'selectedMonth' => $selectedMonth,
            'years' => $years,
            'accountingTypes' => $accountingTypes
        ]);
    }

    public function manageIncomeExpenses()
    {
        $types = AccountingIncomeExpenseType::orderBy('type')->orderBy('name')->get();
        return view('accountant.manage-income-expenses', ['types' => $types]);
    }

    public function storeIncomeExpenseType(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:income,expense',
            'unit' => 'required|string|max:10',
        ]);

        $validated['active'] = $request->has('active');

        AccountingIncomeExpenseType::create($validated);

        return redirect()->route('accountant.manage-income-expenses')
            ->with('success', 'Income/expense type created successfully');
    }

    public function updateIncomeExpenseType(Request $request, $id)
    {
        $type = AccountingIncomeExpenseType::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:income,expense',
            'unit' => 'required|string|max:10',
        ]);

        $validated['active'] = $request->has('active');

        $type->update($validated);

        return redirect()->route('accountant.manage-income-expenses')
            ->with('success', 'Income/expense type updated successfully');
    }

    public function deleteIncomeExpenseType($id)
    {
        $type = AccountingIncomeExpenseType::findOrFail($id);
        $type->delete();

        return redirect()->route('accountant.manage-income-expenses')
            ->with('success', 'Income/expense type deleted successfully');
    }

    public function store(Request $request)
    {
        $records = $request->input('records', []);
        $expenseTypes = $request->input('expense_types', []);
        $selectedYear = $request->input('year', now()->year);
        $selectedMonth = $request->input('month', now()->month);

        // Create date from selected year and month
        $currentDate = Carbon::create($selectedYear, $selectedMonth, 1)->format('Y-m-d');
        $recordsAdded = 0;

        DB::beginTransaction();
        try {
            foreach ($records as $userId => $recordTypes) {
                foreach ($recordTypes as $recordType => $value) {
                    // Skip if value is null or empty string
                    if ($value === null || trim($value) === '') {
                        continue;
                    }

                    // Get expense type for this record (default to 'payback' if not specified)
                    $expenseType = isset($expenseTypes[$userId][$recordType]) ?
                        $expenseTypes[$userId][$recordType] : 'payback';

                    AccountingRecord::create([
                        'user_id' => $userId,
                        'record_type' => $recordType,
                        'amount' => $value,
                        'date' => $currentDate,
                        'status' => 'pending',
                        'created_by' => auth()->id(),
                        'expense_type' => $expenseType
                    ]);

                    $recordsAdded++;
                }
            }

            if ($recordsAdded === 0) {
                return redirect()->back()->with('warning', 'No valid records were found to add');
            }

            DB::commit();
            return redirect()->back()->with('success', "$recordsAdded records have been added successfully");
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error storing records: ' . $e->getMessage());
            return redirect()->back()->with('error', 'An error occurred while saving the records');
        }
    }

    public function deleteRecord(Request $request)
    {
        try {
            $record = AccountingRecord::findOrFail($request->record_id);
            $record->delete();

            return response()->json([
                'success' => true,
                'message' => 'Record deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting record'
            ], 500);
        }
    }

    public function accountantReportCreate()
    {
        return view('accountant.accountant-report-create');
    }


    public function staffReportCreate()
    {
        return view('accountant.staff-report-create');
    }
    public function operationStaffReportCreate()
    {
        return view('accountant.operation-staff-report-create');
    }

    public function hotelReportCreate()
    {
        return view('accountant.hotel-report-create');
    }

    public function accountantGetMonthlyReport(Request $request)
    {
        $monthYear = $request->input('month');
        $date = \Carbon\Carbon::parse($monthYear);

        // Get all guide IDs (tour_guides.id) with hours for the month
        $guideIdsWithHours = EventSalary::whereYear('guide_start_time', $date->year)
            ->whereMonth('guide_start_time', $date->month)
            ->pluck('guideId')
            ->unique();

        // Get accounting records for the month
        $accountingRecords = AccountingRecord::whereYear('date', $date->year)
            ->whereMonth('date', $date->month)
            ->get();

        // Get user IDs who have accounting records
        $userIdsWithAccountingRecords = $accountingRecords->pluck('user_id')->unique()->toArray();

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
        $validUserIds = $validTourGuides->pluck('user_id')->toArray(); // User IDs for accounting records

        // Get the mapping between user_id and tour guides
        $tourGuideMapping = $validTourGuides->keyBy('user_id')->toArray();

        // Get all active accounting types (both income and expense)
        $accountingTypes = AccountingIncomeExpenseType::where('active', true)
            ->orderBy('type')
            ->orderBy('name')
            ->get();

        // Filter accounting records to only include our selected guides
        $accountingRecords = $accountingRecords->whereIn('user_id', $validUserIds);

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
        $eventSalariesWithHours = $query->get()->keyBy('guideId')->map(function ($salary) {
            // Check if this guide is an intern
            $isIntern = $salary->tourGuide->user && $salary->tourGuide->user->is_intern == 1;

            if ($isIntern) {
                // For interns, set all hours to 0:00
                return [
                    'guideId' => $salary->guideId,
                    'totalNormalHours' => '0:00',
                    'totalNormalNightHours' => '0:00',
                    'totalHolidayHours' => '0:00',
                    'totalHolidayNightHours' => '0:00',
                    'tourGuide' => $salary->tourGuide
                ];
            }

            // For non-interns, calculate hours normally
            return [
                'guideId' => $salary->guideId,
                'totalNormalHours' => str_replace('.', ':', $this->sumDecimalHours(explode(',', $salary->normal_hours_list))),
                'totalNormalNightHours' => str_replace('.', ':', $this->sumDecimalHours(explode(',', $salary->normal_night_hours_list))),
                'totalHolidayHours' => str_replace('.', ':', $this->sumDecimalHours(explode(',', $salary->holiday_hours_list))),
                'totalHolidayNightHours' => str_replace('.', ':', $this->sumDecimalHours(explode(',', $salary->holiday_night_hours_list))),
                'tourGuide' => $salary->tourGuide
            ];
        });

        // Create a set of guide IDs that already have hours to prevent duplicates
        $guideIdsWithHours = $eventSalariesWithHours->pluck('guideId')->toArray();

        // Add guides who only have accounting records (including interns with accounting records)
        foreach ($validTourGuides as $guide) {
            // Check if this guide ID already exists in the hours data
            if (!in_array($guide->id, $guideIdsWithHours)) {
                // Use guide ID as key to match the existing pattern
                $eventSalariesWithHours[$guide->id] = [
                    'guideId' => $guide->id,
                    'totalNormalHours' => '0:00',
                    'totalNormalNightHours' => '0:00',
                    'totalHolidayHours' => '0:00',
                    'totalHolidayNightHours' => '0:00',
                    'tourGuide' => $guide
                ];
            }
        }

        // Filter out any entries with null tourGuides to prevent errors
        $eventSalariesWithHours = $eventSalariesWithHours->filter(function ($salary) {
            return $salary['tourGuide'] !== null;
        });

        // NEW: Filter out guides who have 0:00 for all hour columns AND no accounting records
        $eventSalariesWithHours = $eventSalariesWithHours->filter(function ($salary) use ($accountingRecords) {
            // Check if guide has any work hours
            $hasWorkHours = $salary['totalNormalHours'] !== '0:00' ||
                $salary['totalNormalNightHours'] !== '0:00' ||
                $salary['totalHolidayHours'] !== '0:00' ||
                $salary['totalHolidayNightHours'] !== '0:00';

            // Check if guide has accounting records
            $hasAccountingRecords = $accountingRecords->where('user_id', $salary['tourGuide']->user_id)->count() > 0;

            // Include if guide has work hours OR has accounting records
            return $hasWorkHours || $hasAccountingRecords;
        });

        // Organize accounting records for the view
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

        return view('accountant.accountant-monthly-report', [
            'eventSalaries' => $eventSalariesWithHours,
            'monthYear' => $monthYear,
            'accountingRecords' => $organizedRecords,
            'accountingTypes' => $accountingTypes,
            'tourGuideMapping' => $tourGuideMapping
        ]);
    }

    public function staffGetMonthlyReport(Request $request)
    {
        $monthYear = $request->input('month');
        $date = \Carbon\Carbon::parse($monthYear);
        $year = $date->year;
        $month = $date->month;

        // Get accounting records first to identify which interns have data
        $hotelDepartments = ['Hotel', 'Hotel Spa', 'Hotel Restaurant', 'Operations'];
        $accountingRecords = AccountingRecord::whereYear('date', $date->year)
            ->whereMonth('date', $date->month)
            ->get();

        // Get user IDs who have accounting records
        $userIdsWithAccountingRecords = $accountingRecords->pluck('user_id')->unique()->toArray();

        // Get all staff (excluding hotel departments) - include both interns and non-interns
        $allStaffUsers = StaffUser::whereNotIn('department', $hotelDepartments)
            ->orderBy('name')
            ->get();

        // Filter staff: include non-interns + interns who have accounting records
        $staffUsers = $allStaffUsers->filter(function ($staff) use ($userIdsWithAccountingRecords) {
            $isIntern = $staff->user && $staff->user->is_intern == 1;
            $hasAccountingRecords = in_array($staff->user_id, $userIdsWithAccountingRecords);

            // Include if: not an intern OR (is intern AND has accounting records)
            return !$isIntern || ($isIntern && $hasAccountingRecords);
        });

        $staffUserIds = $staffUsers->pluck('user_id')->toArray();

        // Filter accounting records to only include our selected staff
        $accountingRecords = $accountingRecords->whereIn('user_id', $staffUserIds);

        // Get holidays for the selected month and year
        $holidays = Holiday::whereYear('holiday_date', $year)
            ->whereMonth('holiday_date', $month)
            ->pluck('holiday_date')
            ->map(function ($date) {
                return Carbon::parse($date)->format('Y-m-d');
            })
            ->toArray();

        // Get all active accounting types
        $accountingTypes = AccountingIncomeExpenseType::where('active', true)
            ->orderBy('type')
            ->orderBy('name')
            ->get();

        // Get staff hours data
        $staffHoursData = [];

        foreach ($staffUsers as $staff) {
            // Check if this staff member is an intern
            $isIntern = $staff->user && $staff->user->is_intern == 1;

            if ($isIntern) {
                // For interns, set all hours to 0:00
                $staffHoursData[$staff->user_id] = [
                    'staffId' => $staff->user_id,
                    'department' => $staff->department,
                    'totalNormalHours' => '0:00',
                    'totalHolidayHours' => '0:00',
                    'totalNormalNightHours' => '0:00',
                    'totalHolidayNightHours' => '0:00',
                    'staff' => $staff,
                    'is_intern' => true
                ];
                continue; // Skip hours calculation for interns
            }

            // Initialize debugging data (only for non-interns)
            $debugInfo = [
                'staff_id' => $staff->id,
                'staff_name' => $staff->name,
                'shifts' => []
            ];

            // Initialize totals
            $regularHours = 0;
            $holidayHours = 0;
            $nightHours = 0;
            $holidayNightHours = 0;

            // Get all working hours for this staff member
            $staffHours = StaffMonthlyHours::where('staff_id', $staff->id)
                ->whereYear('date', $year)
                ->whereMonth('date', $month)
                ->get();

            // Process each record and calculate hours
            foreach ($staffHours as $record) {
                $recordDate = Carbon::parse($record->date);
                $recordDateStr = $recordDate->format('Y-m-d');
                $isHoliday = in_array($recordDateStr, $holidays) || $recordDate->isSunday();

                // Process each shift in the record
                if (isset($record->hours_data) && is_array($record->hours_data)) {
                    foreach ($record->hours_data as $shiftIndex => $shift) {
                        if (isset($shift['start_time']) && isset($shift['end_time'])) {
                            // Local variables for this specific shift
                            $shiftNightMinutes = 0;
                            $shiftRegularMinutes = 0;
                            $shiftDebug = [
                                'date' => $recordDateStr,
                                'is_holiday' => $isHoliday,
                                'start_time' => $shift['start_time'],
                                'end_time' => $shift['end_time'],
                                'night_periods' => []
                            ];

                            // Create proper Carbon instances for start and end times
                            $startTime = Carbon::parse($recordDate->format('Y-m-d') . ' ' . $shift['start_time']);
                            $endTime = Carbon::parse($recordDate->format('Y-m-d') . ' ' . $shift['end_time']);

                            $shiftDebug['parsed_start'] = $startTime->format('Y-m-d H:i');
                            $shiftDebug['parsed_end'] = $endTime->format('Y-m-d H:i');

                            // Handle overnight shifts
                            if ($endTime->lt($startTime)) {
                                $endTime->addDay();
                                $shiftDebug['overnight'] = true;
                                $shiftDebug['adjusted_end'] = $endTime->format('Y-m-d H:i');
                            } else {
                                $shiftDebug['overnight'] = false;
                            }

                            // Total minutes in this shift
                            $totalShiftMinutes = $endTime->diffInMinutes($startTime);
                            $shiftDebug['total_minutes'] = $totalShiftMinutes;

                            // Define night periods
                            $nightStartEvening = Carbon::parse($recordDate->format('Y-m-d') . ' 20:00');
                            $nightEndMorning = Carbon::parse($recordDate->format('Y-m-d') . ' 06:00')->addDay();
                            $midnight = Carbon::parse($recordDate->format('Y-m-d') . ' 00:00')->addDay();

                            $shiftDebug['night_start_evening'] = $nightStartEvening->format('Y-m-d H:i');
                            $shiftDebug['night_end_morning'] = $nightEndMorning->format('Y-m-d H:i');
                            $shiftDebug['midnight'] = $midnight->format('Y-m-d H:i');

                            // Calculate night minutes for this specific shift

                            // Evening part (20:00-00:00)
                            if ($startTime->lt($midnight) && $endTime->gt($nightStartEvening)) {
                                $nightSegmentStart = max($startTime, $nightStartEvening);
                                $nightSegmentEnd = min($endTime, $midnight);
                                $eveningNightMinutes = $nightSegmentEnd->diffInMinutes($nightSegmentStart);
                                $shiftNightMinutes += $eveningNightMinutes;
                            }

                            // Morning part (00:00-06:00) - Fixed calculation
                            $morningNightStart = Carbon::parse($recordDate->format('Y-m-d') . ' 00:00')->addDay(); // midnight
                            $morningNightEnd = Carbon::parse($recordDate->format('Y-m-d') . ' 06:00')->addDay();   // 6 AM next day

                            // Check if shift overlaps with morning night period (00:00-06:00)
                            if ($startTime->lt($morningNightEnd) && $endTime->gt($morningNightStart)) {
                                $nightSegmentStart = max($startTime, $morningNightStart);
                                $nightSegmentEnd = min($endTime, $morningNightEnd);
                                $morningNightMinutes = $nightSegmentEnd->diffInMinutes($nightSegmentStart);
                                $shiftNightMinutes += $morningNightMinutes;
                            }

                            // Special case for shifts that start before 6:00 AM on the same day (not overnight)
                            $sameDay0600 = Carbon::parse($recordDate->format('Y-m-d') . ' 06:00');
                            if ($startTime->lt($sameDay0600) && $endTime->gt($startTime) && $startTime->format('Y-m-d') === $recordDate->format('Y-m-d')) {
                                $nightSegmentEnd = min($endTime, $sameDay0600);
                                $earlyMorningMinutes = $nightSegmentEnd->diffInMinutes($startTime);
                                $shiftNightMinutes += $earlyMorningMinutes;

                                $shiftDebug['night_periods'][] = [
                                    'type' => 'early_morning_same_day',
                                    'segment_start' => $startTime->format('Y-m-d H:i'),
                                    'segment_end' => $nightSegmentEnd->format('Y-m-d H:i'),
                                    'minutes' => $earlyMorningMinutes
                                ];
                            }

                            // Calculate regular minutes for this shift (total minus night)
                            $shiftRegularMinutes = $totalShiftMinutes - $shiftNightMinutes;

                            $shiftDebug['night_minutes'] = $shiftNightMinutes;
                            $shiftDebug['regular_minutes'] = $shiftRegularMinutes;

                            // Add to the appropriate counters based on holiday status
                            if ($isHoliday) {
                                $holidayHours += $shiftRegularMinutes;
                                $holidayNightHours += $shiftNightMinutes;

                                $shiftDebug['added_to'] = [
                                    'holiday_regular' => $shiftRegularMinutes,
                                    'holiday_night' => $shiftNightMinutes
                                ];
                            } else {
                                $regularHours += $shiftRegularMinutes;
                                $nightHours += $shiftNightMinutes;

                                $shiftDebug['added_to'] = [
                                    'regular' => $shiftRegularMinutes,
                                    'night' => $shiftNightMinutes
                                ];
                            }

                            // Add shift debug info
                            $debugInfo['shifts'][] = $shiftDebug;
                        }
                    }
                }
            }

            // Format all hours as HH:MM and combine regular + night for total work hours
            $staffHoursData[$staff->user_id] = [
                'staffId' => $staff->user_id,
                'department' => $staff->department,
                'totalNormalHours' => $this->formatMinutesToHours($regularHours + $nightHours + $holidayHours + $holidayNightHours), // Combined normal + night
                'totalHolidayHours' => $this->formatMinutesToHours($holidayHours + $holidayNightHours), // Combined holiday + holiday night
                'totalNormalNightHours' => $this->formatMinutesToHours($nightHours + $holidayNightHours), // Night supplement on normal days
                'totalHolidayNightHours' => $this->formatMinutesToHours($holidayNightHours), // Night supplement on holidays
                'staff' => $staff,
                'is_intern' => false
            ];
        }

        // Organize accounting records for the view (keep this part as is)
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

        // Log the debug data for examination
        Log::info('Staff hours calculation debug data', ['staff_hours' => $staffHoursData]);

        return view('accountant.staff-monthly-report', [
            'staffHours' => $staffHoursData,
            'monthYear' => $monthYear,
            'accountingRecords' => $organizedRecords,
            'accountingTypes' => $accountingTypes,
            'staffUsers' => $staffUsers->keyBy('user_id'),
        ]);
    }

public function operationStaffGetMonthlyReport(Request $request)
{
    $monthYear = $request->input('month');
    $date = \Carbon\Carbon::parse($monthYear);
    $year = $date->year;
    $month = $date->month;

    // Get accounting records first to identify which interns have data
    $accountingRecords = AccountingRecord::whereYear('date', $date->year)
        ->whereMonth('date', $date->month)
        ->get();

    // Get user IDs who have accounting records
    $userIdsWithAccountingRecords = $accountingRecords->pluck('user_id')->unique()->toArray();

    // Get all staff from Operations department - include both interns and non-interns
    $hotelDepartments = ['Operations'];
    $allStaffUsers = StaffUser::whereIn('department', $hotelDepartments)
        ->orderBy('name')
        ->get();

    // Filter staff: include non-interns + interns who have accounting records
    $staffUsers = $allStaffUsers->filter(function ($staff) use ($userIdsWithAccountingRecords) {
        $isIntern = $staff->user && $staff->user->is_intern == 1;
        $hasAccountingRecords = in_array($staff->user_id, $userIdsWithAccountingRecords);

        // Include if: not an intern OR (is intern AND has accounting records)
        return !$isIntern || ($isIntern && $hasAccountingRecords);
    });

    $staffUserIds = $staffUsers->pluck('user_id')->toArray();

    // Filter accounting records to only include our selected staff
    $accountingRecords = $accountingRecords->whereIn('user_id', $staffUserIds);

    // Get holidays for the selected month and year
    $holidays = Holiday::whereYear('holiday_date', $year)
        ->whereMonth('holiday_date', $month)
        ->pluck('holiday_date')
        ->map(function ($date) {
            return Carbon::parse($date)->format('Y-m-d');
        })
        ->toArray();

    // Get all active accounting types
    $accountingTypes = AccountingIncomeExpenseType::where('active', true)
        ->orderBy('type')
        ->orderBy('name')
        ->get();

    // Get staff hours data
    $staffHoursData = [];

    foreach ($staffUsers as $staff) {
        // Check if this staff member is an intern
        $isIntern = $staff->user && $staff->user->is_intern == 1;

        if ($isIntern) {
            // For interns, set all hours to 0:00
            $staffHoursData[$staff->user_id] = [
                'staffId' => $staff->user_id,
                'department' => $staff->department,
                'totalNormalHours' => '0:00',
                'totalHolidayHours' => '0:00',
                'totalNormalNightHours' => '0:00',
                'totalHolidayNightHours' => '0:00',
                'staff' => $staff,
                'is_intern' => true
            ];
            continue; // Skip hours calculation for interns
        }

        // Initialize debugging data (only for non-interns)
        $debugInfo = [
            'staff_id' => $staff->id,
            'staff_name' => $staff->name,
            'shifts' => []
        ];

        // Initialize totals
        $regularHours = 0;
        $holidayHours = 0;
        $nightHours = 0;
        $holidayNightHours = 0;

        // Get all working hours for this staff member
        $staffHours = StaffMonthlyHours::where('staff_id', $staff->id)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->get();

        // Process each record and calculate hours
        foreach ($staffHours as $record) {
            $recordDate = Carbon::parse($record->date);
            $recordDateStr = $recordDate->format('Y-m-d');
            $isHoliday = in_array($recordDateStr, $holidays) || $recordDate->isSunday();

            // Process each shift in the record
            if (isset($record->hours_data) && is_array($record->hours_data)) {
                foreach ($record->hours_data as $shiftIndex => $shift) {
                    if (isset($shift['start_time']) && isset($shift['end_time'])) {
                        // UPDATED: Check if this is an on-call shift
                        $isOnCall = isset($shift['type']) && $shift['type'] === 'on_call';
                        $divisor = $isOnCall ? 3 : 1;

                        // Local variables for this specific shift
                        $shiftNightMinutes = 0;
                        $shiftRegularMinutes = 0;
                        $shiftDebug = [
                            'date' => $recordDateStr,
                            'is_holiday' => $isHoliday,
                            'start_time' => $shift['start_time'],
                            'end_time' => $shift['end_time'],
                            'type' => $shift['type'] ?? 'normal',
                            'is_on_call' => $isOnCall,
                            'divisor' => $divisor,
                            'night_periods' => []
                        ];

                        // Create proper Carbon instances for start and end times
                        $startTime = Carbon::parse($recordDate->format('Y-m-d') . ' ' . $shift['start_time']);
                        $endTime = Carbon::parse($recordDate->format('Y-m-d') . ' ' . $shift['end_time']);

                        $shiftDebug['parsed_start'] = $startTime->format('Y-m-d H:i');
                        $shiftDebug['parsed_end'] = $endTime->format('Y-m-d H:i');

                        // Handle overnight shifts
                        if ($endTime->lt($startTime)) {
                            $endTime->addDay();
                            $shiftDebug['overnight'] = true;
                            $shiftDebug['adjusted_end'] = $endTime->format('Y-m-d H:i');
                        } else {
                            $shiftDebug['overnight'] = false;
                        }

                        // Total minutes in this shift
                        $totalShiftMinutes = $endTime->diffInMinutes($startTime);
                        $shiftDebug['total_minutes'] = $totalShiftMinutes;

                        // Define night periods
                        $nightStartEvening = Carbon::parse($recordDate->format('Y-m-d') . ' 20:00');
                        $nightEndMorning = Carbon::parse($recordDate->format('Y-m-d') . ' 06:00')->addDay();
                        $midnight = Carbon::parse($recordDate->format('Y-m-d') . ' 00:00')->addDay();

                        $shiftDebug['night_start_evening'] = $nightStartEvening->format('Y-m-d H:i');
                        $shiftDebug['night_end_morning'] = $nightEndMorning->format('Y-m-d H:i');
                        $shiftDebug['midnight'] = $midnight->format('Y-m-d H:i');

                        // Calculate night minutes for this specific shift

                        // Evening part (20:00-00:00)
                        if ($startTime->lt($midnight) && $endTime->gt($nightStartEvening)) {
                            $nightSegmentStart = max($startTime, $nightStartEvening);
                            $nightSegmentEnd = min($endTime, $midnight);
                            $eveningNightMinutes = $nightSegmentEnd->diffInMinutes($nightSegmentStart);
                            $shiftNightMinutes += $eveningNightMinutes;
                        }

                        // Morning part (00:00-06:00) - Fixed calculation
                        $morningNightStart = Carbon::parse($recordDate->format('Y-m-d') . ' 00:00')->addDay(); // midnight
                        $morningNightEnd = Carbon::parse($recordDate->format('Y-m-d') . ' 06:00')->addDay();   // 6 AM next day

                        // Check if shift overlaps with morning night period (00:00-06:00)
                        if ($startTime->lt($morningNightEnd) && $endTime->gt($morningNightStart)) {
                            $nightSegmentStart = max($startTime, $morningNightStart);
                            $nightSegmentEnd = min($endTime, $morningNightEnd);
                            $morningNightMinutes = $nightSegmentEnd->diffInMinutes($nightSegmentStart);
                            $shiftNightMinutes += $morningNightMinutes;
                        }

                        // Special case for shifts that start before 6:00 AM on the same day (not overnight)
                        $sameDay0600 = Carbon::parse($recordDate->format('Y-m-d') . ' 06:00');
                        if ($startTime->lt($sameDay0600) && $endTime->gt($startTime) && $startTime->format('Y-m-d') === $recordDate->format('Y-m-d')) {
                            $nightSegmentEnd = min($endTime, $sameDay0600);
                            $earlyMorningMinutes = $nightSegmentEnd->diffInMinutes($startTime);
                            $shiftNightMinutes += $earlyMorningMinutes;

                            $shiftDebug['night_periods'][] = [
                                'type' => 'early_morning_same_day',
                                'segment_start' => $startTime->format('Y-m-d H:i'),
                                'segment_end' => $nightSegmentEnd->format('Y-m-d H:i'),
                                'minutes' => $earlyMorningMinutes
                            ];
                        }

                        // Calculate regular minutes for this shift (total minus night)
                        $shiftRegularMinutes = $totalShiftMinutes - $shiftNightMinutes;

                        // UPDATED: Apply divisor for on-call hours
                        $adjustedShiftRegularMinutes = intval($shiftRegularMinutes / $divisor);
                        $adjustedShiftNightMinutes = intval($shiftNightMinutes / $divisor);

                        $shiftDebug['night_minutes'] = $shiftNightMinutes;
                        $shiftDebug['regular_minutes'] = $shiftRegularMinutes;
                        $shiftDebug['adjusted_night_minutes'] = $adjustedShiftNightMinutes;
                        $shiftDebug['adjusted_regular_minutes'] = $adjustedShiftRegularMinutes;

                        // Add to the appropriate counters based on holiday status (using adjusted minutes)
                        if ($isHoliday) {
                            $holidayHours += $adjustedShiftRegularMinutes;
                            $holidayNightHours += $adjustedShiftNightMinutes;

                            $shiftDebug['added_to'] = [
                                'holiday_regular' => $adjustedShiftRegularMinutes,
                                'holiday_night' => $adjustedShiftNightMinutes
                            ];
                        } else {
                            $regularHours += $adjustedShiftRegularMinutes;
                            $nightHours += $adjustedShiftNightMinutes;

                            $shiftDebug['added_to'] = [
                                'regular' => $adjustedShiftRegularMinutes,
                                'night' => $adjustedShiftNightMinutes
                            ];
                        }

                        // Add shift debug info
                        $debugInfo['shifts'][] = $shiftDebug;
                    }
                }
            }
        }

        // Format all hours as HH:MM and combine regular + night for total work hours
        $staffHoursData[$staff->user_id] = [
            'staffId' => $staff->user_id,
            'department' => $staff->department,
            'totalNormalHours' => $this->formatMinutesToHours($regularHours + $nightHours + $holidayHours + $holidayNightHours), // Combined normal + night
            'totalHolidayHours' => $this->formatMinutesToHours($holidayHours + $holidayNightHours), // Combined holiday + holiday night
            'totalNormalNightHours' => $this->formatMinutesToHours($nightHours + $holidayNightHours), // Night supplement on normal days
            'totalHolidayNightHours' => $this->formatMinutesToHours($holidayNightHours), // Night supplement on holidays
            'staff' => $staff,
            'is_intern' => false
        ];
    }

    // Organize accounting records for the view (keep this part as is)
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

    return view('accountant.operation-staff-monthly-report', [
        'staffHours' => $staffHoursData,
        'monthYear' => $monthYear,
        'accountingRecords' => $organizedRecords,
        'accountingTypes' => $accountingTypes,
        'staffUsers' => $staffUsers->keyBy('user_id'),
    ]);
}

public function hotelGetMonthlyReport(Request $request)
{
    $monthYear = $request->input('month');
    $date = \Carbon\Carbon::parse($monthYear);
    $year = $date->year;
    $month = $date->month;

    // Get accounting records first to identify which interns have data
    $accountingRecords = AccountingRecord::whereYear('date', $date->year)
        ->whereMonth('date', $date->month)
        ->get();

    // Get user IDs who have accounting records
    $userIdsWithAccountingRecords = $accountingRecords->pluck('user_id')->unique()->toArray();

    // Get all staff from hotel departments - include both interns and non-interns
    $hotelDepartments = ['Hotel', 'Hotel Spa', 'Hotel Restaurant'];
    $allStaffUsers = StaffUser::whereIn('department', $hotelDepartments)
        ->orderBy('department')
        ->orderBy('name')
        ->get();

    // Filter staff: include non-interns + interns who have accounting records
    $staffUsers = $allStaffUsers->filter(function ($staff) use ($userIdsWithAccountingRecords) {
        $isIntern = $staff->user && $staff->user->is_intern == 1;
        $hasAccountingRecords = in_array($staff->user_id, $userIdsWithAccountingRecords);

        // Include if: not an intern OR (is intern AND has accounting records)
        return !$isIntern || ($isIntern && $hasAccountingRecords);
    });

    $staffUserIds = $staffUsers->pluck('user_id')->toArray();

    // Filter accounting records to only include our selected staff
    $accountingRecords = $accountingRecords->whereIn('user_id', $staffUserIds);

    // Get holidays for the selected month and year
    $holidays = Holiday::whereYear('holiday_date', $year)
        ->whereMonth('holiday_date', $month)
        ->pluck('holiday_date')
        ->map(function ($date) {
            return Carbon::parse($date)->format('Y-m-d');
        })
        ->toArray();

    // Get all active accounting types
    $accountingTypes = AccountingIncomeExpenseType::where('active', true)
        ->orderBy('type')
        ->orderBy('name')
        ->get();

    // Get staff hours data
    $staffHoursData = [];

    foreach ($staffUsers as $staff) {
        // Check if this staff member is an intern
        $isIntern = $staff->user && $staff->user->is_intern == 1;

        if ($isIntern) {
            // For interns, set all hours to 0:00
            $staffHoursData[$staff->user_id] = [
                'staffId' => $staff->user_id,
                'department' => $staff->department,
                'totalWorkHours' => '0:00',
                'totalHolidayHours' => '0:00',
                'totalEveningHours' => '0:00',
                'totalEveningHolidayHours' => '0:00',
                'totalNightHours' => '0:00',
                'totalNightHolidayHours' => '0:00',
                'staff' => $staff,
                'is_intern' => true
            ];
            continue; // Skip hours calculation for interns
        }

        // Initialize totals (in minutes) - only for non-interns
        $regularHours = 0;
        $holidayHours = 0;
        $eveningHours = 0;
        $eveningHolidayHours = 0;
        $nightHours = 0;
        $nightHolidayHours = 0;

        // Get all working hours for this staff member
        $staffHours = StaffMonthlyHours::where('staff_id', $staff->id)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->get();

        // Process each record and calculate hours
        foreach ($staffHours as $record) {
            $recordDate = Carbon::parse($record->date);
            $recordDateStr = $recordDate->format('Y-m-d');
            $isHoliday = in_array($recordDateStr, $holidays) || $recordDate->isSunday();

            // Process each shift in the record
            if (isset($record->hours_data) && is_array($record->hours_data)) {
                foreach ($record->hours_data as $shift) {
                    if (isset($shift['start_time']) && isset($shift['end_time'])) {
                        // Create proper Carbon instances for start and end times
                        $startTime = Carbon::parse($recordDate->format('Y-m-d') . ' ' . $shift['start_time']);
                        $endTime = Carbon::parse($recordDate->format('Y-m-d') . ' ' . $shift['end_time']);

                        // Handle overnight shifts
                        if ($endTime->lt($startTime)) {
                            $endTime->addDay();
                        }

                        // Calculate total shift minutes
                        $totalShiftMinutes = $endTime->diffInMinutes($startTime);

                        // Evening hours (18:00-00:00)
                        $eveningStart = Carbon::parse($recordDate->format('Y-m-d') . ' 18:00');
                        $eveningEnd = Carbon::parse($recordDate->format('Y-m-d') . ' 00:00')->addDay();
                        $shiftEveningMinutes = 0;
                        if ($startTime->lt($eveningEnd) && $endTime->gt($eveningStart)) {
                            $shiftEveningMinutes = $this->calculateOverlappingMinutes($startTime, $endTime, $eveningStart, $eveningEnd);
                            $eveningHours += $shiftEveningMinutes;
                            if ($isHoliday) {
                                $eveningHolidayHours += $shiftEveningMinutes;
                            }
                        }

                        // Night hours (00:00-06:00)
                        $shiftNightMinutes = 0;

                        // Check for night hours from midnight to 6 AM (next day)
                        $nightStart = Carbon::parse($recordDate->format('Y-m-d') . ' 00:00')->addDay();
                        $nightEnd = Carbon::parse($recordDate->format('Y-m-d') . ' 06:00')->addDay();

                        if ($startTime->lt($nightEnd) && $endTime->gt($nightStart)) {
                            $nextDayNightMinutes = $this->calculateOverlappingMinutes($startTime, $endTime, $nightStart, $nightEnd);
                            $shiftNightMinutes += $nextDayNightMinutes;
                        }

                        // Also check for early morning shifts on the same day (before 06:00)
                        $sameDayNightEnd = Carbon::parse($recordDate->format('Y-m-d') . ' 06:00');
                        if ($startTime->lt($sameDayNightEnd) && $startTime->format('Y-m-d') === $recordDate->format('Y-m-d')) {
                            $sameDayNightMinutes = $this->calculateOverlappingMinutes($startTime, $endTime, $startTime, $sameDayNightEnd);
                            $shiftNightMinutes += $sameDayNightMinutes;
                        }

                        if ($shiftNightMinutes > 0) {
                            $nightHours += $shiftNightMinutes;
                            if ($isHoliday) {
                                $nightHolidayHours += $shiftNightMinutes;
                            }
                        }

                        // Calculate regular hours (total minus evening and night)
                        $shiftRegularMinutes = $totalShiftMinutes - $shiftEveningMinutes - $shiftNightMinutes;

                        // Add to totals based on holiday status
                        if ($isHoliday) {
                            $holidayHours += $totalShiftMinutes; // Total hours on holiday
                        } else {
                            $regularHours += $totalShiftMinutes; // Total hours on regular days
                        }
                    }
                }
            }
        }

        // Process any missing hours for this staff member
        $missingHours = StaffMissingHours::where('staff_id', $staff->id)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->get();

        foreach ($missingHours as $missing) {
            try {
                $recordDate = $missing->date;
                $recordDateStr = $recordDate->format('Y-m-d');
                $isHoliday = in_array($recordDateStr, $holidays) || $recordDate->isSunday();

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

                // Calculate total missing minutes
                $totalMissingMinutes = $endTime->diffInMinutes($startTime);

                // Evening hours (18:00-00:00) for missing hours
                $eveningStart = Carbon::parse($recordDate->format('Y-m-d') . ' 18:00');
                $eveningEnd = Carbon::parse($recordDate->format('Y-m-d') . ' 00:00')->addDay();
                $missingEveningMinutes = 0;
                if ($startTime->lt($eveningEnd) && $endTime->gt($eveningStart)) {
                    $missingEveningMinutes = $this->calculateOverlappingMinutes($startTime, $endTime, $eveningStart, $eveningEnd);
                    $eveningHours += $missingEveningMinutes;
                    if ($isHoliday) {
                        $eveningHolidayHours += $missingEveningMinutes;
                    }
                }

                // Night hours (00:00-06:00) for missing hours
                $missingNightMinutes = 0;

                // Check for night hours from midnight to 6 AM (next day)
                $nightStart = Carbon::parse($recordDate->format('Y-m-d') . ' 00:00')->addDay();
                $nightEnd = Carbon::parse($recordDate->format('Y-m-d') . ' 06:00')->addDay();

                if ($startTime->lt($nightEnd) && $endTime->gt($nightStart)) {
                    $nextDayNightMinutes = $this->calculateOverlappingMinutes($startTime, $endTime, $nightStart, $nightEnd);
                    $missingNightMinutes += $nextDayNightMinutes;
                }

                // Also check for early morning shifts on the same day (before 06:00)
                $sameDayNightEnd = Carbon::parse($recordDate->format('Y-m-d') . ' 06:00');
                if ($startTime->lt($sameDayNightEnd) && $startTime->format('Y-m-d') === $recordDate->format('Y-m-d')) {
                    $sameDayNightMinutes = $this->calculateOverlappingMinutes($startTime, $endTime, $startTime, $sameDayNightEnd);
                    $missingNightMinutes += $sameDayNightMinutes;
                }

                if ($missingNightMinutes > 0) {
                    $nightHours += $missingNightMinutes;
                    if ($isHoliday) {
                        $nightHolidayHours += $missingNightMinutes;
                    }
                }

                // Add missing hours to totals
                if ($isHoliday) {
                    $holidayHours += $totalMissingMinutes;
                } else {
                    $regularHours += $totalMissingMinutes;
                }
            } catch (\Exception $e) {
                \Log::error('Error processing missing hours record ' . $missing->id . ': ' . $e->getMessage());
                continue;
            }
        }

        // Format all hours as HH:MM and calculate totals
        $totalWorkHours = $regularHours + $holidayHours; // All work hours

        $staffHoursData[$staff->user_id] = [
            'staffId' => $staff->user_id,
            'department' => $staff->department,
            'totalWorkHours' => $this->formatMinutesToHours($totalWorkHours),
            'totalHolidayHours' => $this->formatMinutesToHours($holidayHours),
            'totalEveningHours' => $this->formatMinutesToHours($eveningHours),
            'totalEveningHolidayHours' => $this->formatMinutesToHours($eveningHolidayHours),
            'totalNightHours' => $this->formatMinutesToHours($nightHours),
            'totalNightHolidayHours' => $this->formatMinutesToHours($nightHolidayHours),
            'staff' => $staff,
            'is_intern' => false
        ];
    }

    // Organize accounting records for the view
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

    return view('accountant.hotel-monthly-report', [
        'staffHours' => $staffHoursData,
        'monthYear' => $monthYear,
        'accountingRecords' => $organizedRecords,
        'accountingTypes' => $accountingTypes,
        'staffUsers' => $staffUsers->keyBy('user_id')
    ]);
}

    private function calculateOverlappingMinutes($start1, $end1, $start2, $end2)
    {
        $latest_start = max($start1, $start2);
        $earliest_end = min($end1, $end2);
        $overlap_minutes = max(0, $earliest_end->diffInMinutes($latest_start));
        return $overlap_minutes;
    }

    private function formatMinutesToHours($minutes)
    {
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;
        return sprintf("%02d:%02d", $hours, $mins);
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
}
