<?php

namespace App\Http\Controllers;

use App\Models\StaffHoursDetails;
use App\Models\StaffMonthlyHours;
use App\Models\StaffUser;
use App\Models\User;
use App\Models\Holiday;
use App\Models\StaffMissingHours;
use App\Models\Supervisors;
use App\Models\HrAssistants;
use App\Models\TeamLeads;
use App\Models\SupervisorSickLeaves;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Log;
use App\Models\StaffMidnightPhone; // Make sure this import exists at the top

class SupervisorController extends Controller
{
    public function enterWorkingHours(Request $request)
    {
        // Get the selected week start date from request, or use current week if not provided
        $selectedDate = $request->input('week', Carbon::now()->format('Y-m-d'));
        $weekStart = Carbon::parse($selectedDate)->startOfWeek();
        $weekEnd = Carbon::parse($selectedDate)->endOfWeek();

        $dates = CarbonPeriod::create($weekStart, $weekEnd);

        // Check user role first
        if (Auth::user()->role == 'admin') {
            // For admin: get ALL staff members from ALL departments
            $staffMembers = StaffUser::orderBy('order')->get();

            // Get all unique departments for admin
            $supervisorDepartments = StaffUser::distinct()->pluck('department')->filter()->flatMap(function ($dept) {
                return explode(', ', $dept);
            })->map('trim')->unique()->values()->toArray();

            $displayMidnightPhone = true; // Admin can see midnight phone
        } elseif (Auth::user()->role == 'supervisor') {
            // Replace the existing code with this
            $supervisor = Supervisors::where('user_id', auth()->id())->first();
            $supervisorDepartments = explode(', ', $supervisor->department);

            // Get staff that belong to any of the supervisor's departments
            $staffMembers = StaffUser::where(function ($query) use ($supervisorDepartments) {
                foreach ($supervisorDepartments as $department) {
                    // Use LIKE queries to find staff with this department
                    // This handles both exact matches and cases where the department is part of a list
                    $query->orWhere('department', 'LIKE', $department)
                        ->orWhere('department', 'LIKE', $department . ',%')
                        ->orWhere('department', 'LIKE', '%, ' . $department)
                        ->orWhere('department', 'LIKE', '%, ' . $department . ',%');
                }
            })
                ->where('hide', 0)
                ->orderBy('order')
                ->get();




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
                // Check if supervisor already exists in staff_users table
                $existingStaff = StaffUser::where('user_id', $supervisorUser->id)->first();

                if ($existingStaff) {
                    // If exists, mark as supervisor and ensure it's in the collection
                    $existingStaff->setAttribute('is_supervisor', true);
                    $existingStaff->setAttribute('supervisor_rank', 1); // For sorting

                    // Remove from staffMembers if already there to avoid duplicates
                    $staffMembers = $staffMembers->reject(function ($staff) use ($existingStaff) {
                        return $staff->id === $existingStaff->id;
                    });

                    // Add to the beginning of collection
                    $staffMembers->prepend($existingStaff);
                } else {
                    // Create a new StaffUser record for this supervisor
                    $newStaff = StaffUser::create([
                        'name' => $supervisorUser->name,
                        'full_name' => $supervisorUser->name,
                        'email' => $supervisorUser->email,
                        'department' => $supervisorDepartments[0], // Use first department
                        'phone_number' => '',
                        'is_supervisor' => true,
                        'rate' => '',
                        'user_id' => $supervisorUser->id,
                        'color' => "#" . substr(md5(rand()), 0, 6),
                        'allow_report_hours' => 1,
                    ]);

                    $newStaff->setAttribute('is_supervisor', true);
                    $newStaff->setAttribute('supervisor_rank', 1); // For sorting

                    // Add to the beginning of collection
                    $staffMembers->prepend($newStaff);
                }
            }

            // Sort the collection to put supervisors first
            $staffMembers = $staffMembers->sortBy(function ($staff) {
                return [
                    $staff->getAttribute('supervisor_rank') ?? 2, // Supervisors get rank 1, others get 2
                    $staff->name // Then sort by name within each group
                ];
            })->values(); // Reset keys

            $displayMidnightPhone = Supervisors::where('user_id', Auth::user()->id)->first()->display_midnight_phone;
        } elseif (Auth::user()->role == 'hr-assistant') {
            // Set department for HR Assistant
            $supervisorDepartments = ['Guide Supervisor'];

            // Check if HR Assistant already exists in staff_users table
            $existingStaff = StaffUser::where('user_id', Auth::user()->id)->first();

            if ($existingStaff) {
                $staffMembers = collect([$existingStaff]);
            } else {
                // Create empty collection if HR assistant doesn't exist in staff_users yet
                $staffMembers = collect([]);
            }

            $displayMidnightPhone = false; // HR assistants don't display midnight phone
        } elseif (Auth::user()->role == 'team-lead') {
            // Set department for Team Lead
            $supervisorDepartments = ['Bus Driver Supervisor'];

            // Check if Team Lead already exists in staff_users table
            $existingStaff = StaffUser::where('user_id', Auth::user()->id)->first();

            if ($existingStaff) {
                $staffMembers = collect([$existingStaff]);
            } else {
                // Create empty collection if team lead doesn't exist in staff_users yet
                $staffMembers = collect([]);
            }

            $displayMidnightPhone = false; // Team leads don't display midnight phone
        } else {
            // For non-supervisors, get their supervisor's staff
            $supervisorIds = DB::table('staff_supervisor')
                ->where('staff_user_id', Auth::user()->id)
                ->pluck('supervisor_id');

            // Get supervisor departments for filtering
            $supervisorRecord = Supervisors::whereIn('user_id', $supervisorIds)->first();
            $supervisorDepartments = $supervisorRecord ? explode(', ', $supervisorRecord->department) : [];

            // Get first supervisor's display_midnight_phone setting
            $displayMidnightPhone = Supervisors::whereIn('user_id', $supervisorIds)
                ->first()->display_midnight_phone ?? false;

            $staffMembers = StaffUser::whereHas('supervisors', function ($query) use ($supervisorIds) {
                $query->whereIn('supervisor_id', $supervisorIds);
            })->get();
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
                    'is_approved' => $item->is_approved
                ];
            })->toArray();
        }

        // UPDATED: Filter StaffHoursDetails by supervisor's departments (or all for admin)
        $receptionData = [];
        $midnightPhoneData = [];

        if (Auth::user()->role == 'admin') {
            // Admin sees all departments
            $staffHoursDetails = StaffHoursDetails::whereBetween('date', [$weekStart, $weekEnd])->get();
        } else {
            // Filter by specific departments for each role
            $staffHoursDetails = StaffHoursDetails::whereBetween('date', [$weekStart, $weekEnd])
                ->where(function ($query) use ($supervisorDepartments) {
                    foreach ($supervisorDepartments as $department) {
                        $query->orWhere('department', $department);
                    }
                })
                ->get();
        }

        // FIXED: Organize reception data by department AND date
        $organizedReceptionData = [];
        foreach ($staffHoursDetails as $detail) {
            $dateString = $detail->date->format('Y-m-d');
            $department = $detail->department;

            if (!isset($organizedReceptionData[$dateString])) {
                $organizedReceptionData[$dateString] = [];
            }

            $organizedReceptionData[$dateString][$department] = $detail->reception;
            $midnightPhoneData[$dateString] = $detail->midnight_phone[0] ?? null;
        }

        // Keep backward compatibility for simple receptionData
        foreach ($dates as $date) {
            $dateString = $date->format('Y-m-d');
            $receptionData[$dateString] = '';

            // Use first available department's data as fallback
            foreach ($supervisorDepartments as $department) {
                if (isset($organizedReceptionData[$dateString][$department])) {
                    $receptionData[$dateString] = $organizedReceptionData[$dateString][$department];
                    break;
                }
            }
        }

        $holidays = Holiday::whereBetween('holiday_date', [$weekStart, $weekEnd])->pluck('holiday_date');

        // FIXED: Sick leave processing
        $sickLeaveStatuses = [];
        if ($staffMembers->isNotEmpty()) {
            $sickLeaves = SupervisorSickLeaves::whereIn('staff_id', $staffMembers->pluck('id'))
                ->where(function ($query) use ($weekStart, $weekEnd) {
                    // Check if either start_date or end_date falls within the date range
                    // Or if start_date is before the range and end_date is after (spanning the entire week)
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

                // Create entries for each date in the sick leave range that falls within the week
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
        }

        $staffByDepartment = [];
        foreach ($staffMembers as $staff) {
            $departments = explode(', ', $staff->department);
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

        // For HR assistants and team leads, ensure their department appears even if no staff
        if (Auth::user()->role == 'hr-assistant' && !isset($staffByDepartment['Guide Supervisor'])) {
            $staffByDepartment['Guide Supervisor'] = collect();
        }
        if (Auth::user()->role == 'team-lead' && !isset($staffByDepartment['Bus Driver Supervisor'])) {
            $staffByDepartment['Bus Driver Supervisor'] = collect();
        }


        foreach ($staffByDepartment as $dept => $staffCollection) {
            $staffByDepartment[$dept] = $staffCollection->sortBy(function ($staff) {
                // Use integer value for order, fallback to high value if missing
                return is_numeric($staff->order) ? (int)$staff->order : 9999;
            })->values();
        }


        // Add this line to create a flattened collection for the dropdown:
        $allStaffFlattened = collect($staffByDepartment)->flatten();


        // Check if mobile device or force mobile view
        $isMobile = session('is_mobile', false) || $request->has('mobile');
        
        // Choose view based on device type
        $viewName = $isMobile ? 'staffs.mobile.report-staff-hours' : 'supervisor.enter-working-hours';
        
        return view($viewName, compact(
            'selectedDate',
            'dates',
            'staffByDepartment',
            'allStaffFlattened',
            'supervisorDepartments',
            'staffHours',
            'holidays',
            'receptionData',
            'organizedReceptionData', // Pass this for department-specific access
            'midnightPhoneData',
            'displayMidnightPhone',
            'sickLeaveStatuses'
        ));
    }


    public function enterWorkingHoursGuideSupervisor(Request $request)
    {
        // Get the selected week start date from request, or use current week if not provided
        $selectedDate = $request->input('week', Carbon::now()->format('Y-m-d'));
        $weekStart = Carbon::parse($selectedDate)->startOfWeek();
        $weekEnd = Carbon::parse($selectedDate)->endOfWeek();

        $dates = CarbonPeriod::create($weekStart, $weekEnd);

        if (Auth::user()->role == 'hr-assistant') {
            // Set department for HR Assistant
            $supervisorDepartments = ['Guide Supervisor'];

            // Check if HR Assistant already exists in staff_users table
            $existingStaff = StaffUser::where('user_id', Auth::user()->id)->first();

            if ($existingStaff) {
                // If exists, mark as hr-assistant and ensure it's in the collection
                $existingStaff->setAttribute('is_hr_assistant', true);
                $existingStaff->setAttribute('rank', 1); // For sorting
                $staffMembers = collect([$existingStaff]);
            } else {
                // Create a new StaffUser record for this HR Assistant
                $hrAssistant = HrAssistants::where('user_id', Auth::user()->id)->first();
                $newStaff = StaffUser::create([
                    'name' => Auth::user()->name,
                    'full_name' => Auth::user()->name,
                    'email' => Auth::user()->email,
                    'department' => 'Guide Supervisor',
                    'phone_number' => $hrAssistant->phone_number ?? '',
                    'is_supervisor' => false,
                    'rate' => $hrAssistant->rate ?? '',
                    'user_id' => Auth::user()->id,
                    'color' => $hrAssistant->color ?? "#" . substr(md5(rand()), 0, 6),
                    'allow_report_hours' => $hrAssistant->allow_report_hours ?? 1,
                ]);

                $newStaff->setAttribute('is_hr_assistant', true);
                $newStaff->setAttribute('rank', 1);
                $staffMembers = collect([$newStaff]);
            }

            $displayMidnightPhone = false; // HR assistants don't display midnight phone
        } elseif (Auth::user()->role == 'team-lead') {
            // Set department for Team Lead
            $supervisorDepartments = ['Bus Driver Supervisor'];

            // Check if Team Lead already exists in staff_users table
            $existingStaff = StaffUser::where('user_id', Auth::user()->id)->first();

            if ($existingStaff) {
                // If exists, mark as team-lead and ensure it's in the collection
                $existingStaff->setAttribute('is_team_lead', true);
                $existingStaff->setAttribute('rank', 1); // For sorting
                $staffMembers = collect([$existingStaff]);
            } else {
                // Create a new StaffUser record for this Team Lead
                $teamLead = TeamLeads::where('user_id', Auth::user()->id)->first();
                $newStaff = StaffUser::create([
                    'name' => Auth::user()->name,
                    'full_name' => Auth::user()->name,
                    'email' => Auth::user()->email,
                    'department' => 'Bus Driver Supervisor',
                    'phone_number' => $teamLead->phone_number ?? '',
                    'is_supervisor' => false,
                    'rate' => $teamLead->rate ?? '',
                    'user_id' => Auth::user()->id,
                    'color' => $teamLead->color ?? "#" . substr(md5(rand()), 0, 6),
                    'allow_report_hours' => $teamLead->allow_report_hours ?? 1,
                ]);

                $newStaff->setAttribute('is_team_lead', true);
                $newStaff->setAttribute('rank', 1);
                $staffMembers = collect([$newStaff]);
            }

            $displayMidnightPhone = false; // Team leads don't display midnight phone
        } else {
            // Handle other roles if needed
            return redirect()->back()->with('error', 'Unauthorized access');
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
                    'is_approved' => $item->is_approved
                ];
            })->toArray();
        }

        // FIXED: Get notes data from StaffHoursDetails reception field - Department-specific
        $receptionData = [];
        $midnightPhoneData = [];

        // Get StaffHoursDetails for the specific department
        $staffHoursDetails = StaffHoursDetails::whereBetween('date', [$weekStart, $weekEnd])
            ->where(function ($query) use ($supervisorDepartments) {
                foreach ($supervisorDepartments as $department) {
                    $query->orWhere('department', $department);
                }
            })
            ->get();

        // FIXED: Organize reception data by department AND date instead of just date
        $organizedReceptionData = [];
        foreach ($staffHoursDetails as $detail) {
            $dateString = $detail->date->format('Y-m-d');
            $department = $detail->department;

            if (!isset($organizedReceptionData[$dateString])) {
                $organizedReceptionData[$dateString] = [];
            }

            $organizedReceptionData[$dateString][$department] = $detail->reception;
            $midnightPhoneData[$dateString] = null; // Not used for HR assistants/team leads
        }

        // Create the final receptionData structure that the view expects
        foreach ($dates as $date) {
            $dateString = $date->format('Y-m-d');

            // For each department
            foreach ($supervisorDepartments as $department) {
                if (isset($organizedReceptionData[$dateString][$department])) {
                    $receptionData[$dateString] = $organizedReceptionData[$dateString][$department];
                    break; // Use the first department's data as default
                }
            }

            // If no data found, initialize empty
            if (!isset($receptionData[$dateString])) {
                $receptionData[$dateString] = '';
            }
        }

        $holidays = Holiday::whereBetween('holiday_date', [$weekStart, $weekEnd])->pluck('holiday_date');

        // No sick leave processing needed for individual HR assistants/team leads
        $sickLeaveStatuses = [];

        // Create staffByDepartment structure
        $staffByDepartment = [];
        foreach ($staffMembers as $staff) {
            $department = $supervisorDepartments[0]; // Use the assigned department
            if (!isset($staffByDepartment[$department])) {
                $staffByDepartment[$department] = collect();
            }
            $staffByDepartment[$department]->push($staff);
        }

        // Add this line to create a flattened collection for the dropdown:
        $allStaffFlattened = collect($staffByDepartment)->flatten();

        // Check if mobile device or force mobile view
        $isMobile = session('is_mobile', false) || $request->has('mobile');
        
        // Choose view based on device type
        $viewName = $isMobile ? 'staffs.mobile.report-staff-hours' : 'supervisor.enter-working-hours';

        return view($viewName, compact(
            'selectedDate',
            'dates',
            'staffByDepartment',
            'allStaffFlattened',
            'supervisorDepartments',
            'staffHours',
            'holidays',
            'receptionData',
            'organizedReceptionData', // Add this for department-specific access in the view
            'midnightPhoneData',
            'displayMidnightPhone',
            'sickLeaveStatuses'
        ));
    }


    public function printRoster(Request $request)
    {
        // Define the base start date (May 12th)
        $baseStartDate = Carbon::parse('2025-05-12'); // Adjust year as needed

        // Get the selected period from request, or calculate current period
        $selectedPeriod = $request->input('period');

        if ($selectedPeriod) {
            $periodStart = Carbon::parse($selectedPeriod);
        } else {
            // Calculate current period based on today's date
            $today = Carbon::now();
            $daysSinceBase = $today->diffInDays($baseStartDate);
            $currentPeriodIndex = floor($daysSinceBase / 21);
            $periodStart = $baseStartDate->copy()->addDays($currentPeriodIndex * 21);
        }

        $periodEnd = $periodStart->copy()->addDays(20); // 21 days total (0-20)
        $dates = CarbonPeriod::create($periodStart, $periodEnd);

        // Generate period options for the dropdown (show past and future periods)
        $periodOptions = [];
        for ($i = -6; $i <= 12; $i++) { // Show 6 past and 12 future periods
            $optionStart = $baseStartDate->copy()->addDays($i * 21);
            $optionEnd = $optionStart->copy()->addDays(20);

            $periodOptions[] = [
                'value' => $optionStart->format('Y-m-d'),
                'label' => $optionStart->format('d/m') . ' - ' . $optionEnd->format('d/m/Y'),
                'selected' => $optionStart->format('Y-m-d') === $periodStart->format('Y-m-d')
            ];
        }

        // Check user role first
        if (Auth::user()->role == 'admin') {
            $staffMembers = StaffUser::all();
            $supervisorDepartments = StaffUser::distinct()->pluck('department')->filter()->flatMap(function ($dept) {
                return explode(', ', $dept);
            })->map('trim')->unique()->values()->toArray();
            $displayMidnightPhone = true;
        } elseif (Auth::user()->role == 'supervisor') {
            $supervisor = Supervisors::where('user_id', auth()->id())->first();
            $supervisorDepartments = explode(', ', $supervisor->department);

            $staffMembers = StaffUser::where(function ($query) use ($supervisorDepartments) {
                foreach ($supervisorDepartments as $department) {
                    $query->orWhere('department', 'LIKE', $department)
                        ->orWhere('department', 'LIKE', $department . ',%')
                        ->orWhere('department', 'LIKE', '%, ' . $department)
                        ->orWhere('department', 'LIKE', '%, ' . $department . ',%');
                }
            })->get();

            $departmentSupervisors = User::where('role', 'supervisor')
                ->whereHas('supervisorRecord', function ($query) use ($supervisorDepartments) {
                    foreach ($supervisorDepartments as $department) {
                        $query->orWhere('department', 'LIKE', $department)
                            ->orWhere('department', 'LIKE', $department . ',%')
                            ->orWhere('department', 'LIKE', '%, ' . $department)
                            ->orWhere('department', 'LIKE', '%, ' . $department . ',%');
                    }
                })->get();

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

            // After getting staffMembers, ensure all supervisors are properly marked
            foreach ($staffMembers as $staff) {
                if ($staff->getAttribute('is_supervisor')) {
                    $staff->setAttribute('supervisor_rank', 1);
                } else {
                    $staff->setAttribute('supervisor_rank', 2);
                }
            }

            $staffMembers = $staffMembers->sortBy(function ($staff) {
                return [
                    $staff->getAttribute('supervisor_rank') ?? 2,
                    $staff->name
                ];
            })->values();

            $displayMidnightPhone = Supervisors::where('user_id', Auth::user()->id)->first()->display_midnight_phone;
        } else {
            $supervisorIds = DB::table('staff_supervisor')
                ->where('staff_user_id', Auth::user()->id)
                ->pluck('supervisor_id');

            $supervisorRecord = Supervisors::whereIn('user_id', $supervisorIds)->first();
            $supervisorDepartments = $supervisorRecord ? explode(', ', $supervisorRecord->department) : [];

            $displayMidnightPhone = Supervisors::whereIn('user_id', $supervisorIds)
                ->first()->display_midnight_phone ?? false;

            $staffMembers = StaffUser::whereHas('supervisors', function ($query) use ($supervisorIds) {
                $query->whereIn('supervisor_id', $supervisorIds);
            })->get();
        }

        $staffHours = [];
        foreach ($staffMembers as $staff) {
            $hours = StaffMonthlyHours::where('staff_id', $staff->id)
                ->whereBetween('date', [$periodStart, $periodEnd])
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

        if (Auth::user()->role == 'admin') {
            $staffHoursDetails = StaffHoursDetails::whereBetween('date', [$periodStart, $periodEnd])->get();
        } else {
            $staffHoursDetails = StaffHoursDetails::whereBetween('date', [$periodStart, $periodEnd])
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

        $holidays = Holiday::whereBetween('holiday_date', [$periodStart, $periodEnd])->pluck('holiday_date');

        $sickLeaveStatuses = [];
        $sickLeaves = SupervisorSickLeaves::whereIn('staff_id', $staffMembers->pluck('id'))
            ->where(function ($query) use ($periodStart, $periodEnd) {
                $query->whereBetween('start_date', [$periodStart, $periodEnd])
                    ->orWhereBetween('end_date', [$periodStart, $periodEnd])
                    ->orWhere(function ($q) use ($periodStart, $periodEnd) {
                        $q->where('start_date', '<=', $periodStart)
                            ->where('end_date', '>=', $periodEnd);
                    });
            })
            ->get();

        foreach ($sickLeaves as $sickLeave) {
            $startDate = Carbon::parse($sickLeave->start_date);
            $endDate = Carbon::parse($sickLeave->end_date);
            $currentDate = max($startDate, $periodStart);
            $rangeEndDate = min($endDate, $periodEnd);

            while ($currentDate <= $rangeEndDate) {
                $dateKey = $currentDate->format('Y-m-d');
                $staffDateKey = $sickLeave->staff_id . '_' . $dateKey;
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

        // Sort each department's staff to put supervisors first
        foreach ($staffByDepartment as $dept => $staff) {
            $staffByDepartment[$dept] = $staff->sortBy(function ($member) {
                return [
                    $member->getAttribute('supervisor_rank') ?? 2,
                    $member->name
                ];
            })->values(); // Reset collection keys
        }

        $midnightPhoneStaff = [];
        foreach ($midnightPhoneData as $date => $staffId) {
            if ($staffId) {
                $staff = $staffMembers->where('id', $staffId)->first();
                $midnightPhoneStaff[$date] = $staff ? $staff->name : 'Unknown';
            } else {
                $midnightPhoneStaff[$date] = '';
            }
        }

        return view('supervisor.print-roster', compact(
            'selectedPeriod',
            'periodOptions',
            'dates',
            'staffByDepartment',
            'supervisorDepartments',
            'staffHours',
            'holidays',
            'receptionData',
            'midnightPhoneData',
            'midnightPhoneStaff',
            'displayMidnightPhone',
            'sickLeaveStatuses',
            'periodStart',
            'periodEnd'
        ));
    }


    public function amEnterWorkingHours(Request $request)
    {
        // Get the selected week start date from request, or use current week if not provided
        $selectedDate = $request->input('week', Carbon::now()->format('Y-m-d'));
        $weekStart = Carbon::parse($selectedDate)->startOfWeek();
        $weekEnd = Carbon::parse($selectedDate)->endOfWeek();

        $dates = CarbonPeriod::create($weekStart, $weekEnd);

        // Set AM Staff as the department for this function
        $currentDepartment = 'AM';

        //if supervisors, get display_midnight_phone from supervisors table
        if (Auth::user()->role == 'supervisor') {
            // Replace the existing code with this
            $supervisor = Supervisors::where('user_id', auth()->id())->first();
            $supervisorDepartments = explode(', ', $supervisor->department);

            // Get only specific staff members by email
            $staffMembers = StaffUser::whereIn('email', ['beatriz@nordictravels.eu', 'semi@nordictravels.eu'])->get();

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
                // Check if supervisor already exists in staff_users table
                $existingStaff = StaffUser::where('user_id', $supervisorUser->id)->first();

                if ($existingStaff) {
                    // If exists, mark as supervisor and ensure it's in the collection
                    $existingStaff->setAttribute('is_supervisor', true);
                    $existingStaff->setAttribute('supervisor_rank', 1); // For sorting

                    // Remove from staffMembers if already there to avoid duplicates
                    $staffMembers = $staffMembers->reject(function ($staff) use ($existingStaff) {
                        return $staff->id === $existingStaff->id;
                    });

                    // Add to the beginning of collection
                    $staffMembers->prepend($existingStaff);
                } else {
                    // Create a new StaffUser record for this supervisor
                    $newStaff = StaffUser::create([
                        'name' => $supervisorUser->name,
                        'full_name' => $supervisorUser->name,
                        'email' => $supervisorUser->email,
                        'department' => $currentDepartment, // Use AM Staff department
                        'phone_number' => '',
                        'is_supervisor' => true,
                        'rate' => '',
                        'user_id' => $supervisorUser->id,
                        'color' => "#" . substr(md5(rand()), 0, 6),
                        'allow_report_hours' => 1,
                    ]);

                    $newStaff->setAttribute('is_supervisor', true);
                    $newStaff->setAttribute('supervisor_rank', 1); // For sorting

                    // Add to the beginning of collection
                    $staffMembers->prepend($newStaff);
                }
            }

            // Sort the collection to put supervisors first
            $staffMembers = $staffMembers->sortBy(function ($staff) {
                return [
                    $staff->getAttribute('supervisor_rank') ?? 2, // Supervisors get rank 1, others get 2
                    $staff->name // Then sort by name within each group
                ];
            })->values(); // Reset keys

            $displayMidnightPhone = Supervisors::where('user_id', Auth::user()->id)->first()->display_midnight_phone;
        } else {
            // For non-supervisors, get only specific staff members by email
            $staffMembers = StaffUser::whereIn('email', ['beatriz@nordictravels.eu', 'semi@nordictravels.eu'])->get();

            // Get first supervisor's display_midnight_phone setting
            $supervisorIds = DB::table('staff_supervisor')
                ->where('staff_user_id', Auth::user()->id)
                ->pluck('supervisor_id');

            $displayMidnightPhone = Supervisors::whereIn('user_id', $supervisorIds)
                ->first()->display_midnight_phone ?? false;
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

        // UPDATED: Filter StaffHoursDetails by AM Staff department
        $receptionData = [];
        $midnightPhoneData = [];
        $staffHoursDetails = StaffHoursDetails::whereBetween('date', [$weekStart, $weekEnd])
            ->where('department', $currentDepartment) // Only get AM Staff department data
            ->get();

        foreach ($staffHoursDetails as $detail) {
            $dateString = $detail->date->format('Y-m-d');
            $receptionData[$dateString] = $detail->reception;
            $midnightPhoneData[$dateString] = $detail->midnight_phone[0] ?? null;
        }

        $holidays = Holiday::whereBetween('holiday_date', [$weekStart, $weekEnd])->pluck('holiday_date');

        // FIXED: Sick leave processing
        $sickLeaveStatuses = [];
        $sickLeaves = SupervisorSickLeaves::whereIn('staff_id', $staffMembers->pluck('id'))
            ->where(function ($query) use ($weekStart, $weekEnd) {
                // Check if either start_date or end_date falls within the date range
                // Or if start_date is before the range and end_date is after (spanning the entire week)
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

            // Create entries for each date in the sick leave range that falls within the week
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

        // If no departments found, create a default one
        if (empty($staffByDepartment)) {
            $staffByDepartment[$currentDepartment] = $staffMembers;
        }

        // Set supervisor departments for the view to AM Staff
        $supervisorDepartments = [$currentDepartment];

        // Add this line to create a flattened collection for the dropdown:
        $allStaffFlattened = collect($staffByDepartment)->flatten();

        return view('supervisor.enter-working-hours', compact(
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

    public function storeWorkingHours(Request $request)
    {
        try {
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

            // Determine the department based on user role
            $department = null;
            if (Auth::user()->role === 'hr-assistant') {
                $department = 'Guide Supervisor';
            } elseif (Auth::user()->role === 'team-lead') {
                $department = 'Bus Driver Supervisor';
            } elseif (Auth::user()->role === 'supervisor') {
                $supervisor = Supervisors::where('user_id', auth()->id())->first();
                $supervisorDepartments = explode(', ', $supervisor->department);
                $department = $supervisorDepartments[0]; // Use first department
            }

            foreach ($hoursData as $staffId => $staffDailyHours) {

                if (strpos($staffId, 'supervisor_') === 0) {
                    $supervisorUserId = str_replace('supervisor_', '', $staffId);

                    // Find or create StaffUser record for supervisor with correct department
                    $supervisorRecord = Supervisors::where('user_id', $supervisorUserId)->first();
                    $staffUser = StaffUser::where('user_id', $supervisorUserId)->first();

                    if (!$staffUser && $supervisorRecord) {
                        $staffUser = StaffUser::create([
                            'name' => $supervisorRecord->user->name,
                            'full_name' => $supervisorRecord->user->name,
                            'email' => $supervisorRecord->user->email,
                            'department' => $supervisorRecord->department,
                            'phone_number' => $supervisorRecord->phone_number ?? '',
                            'is_supervisor' => true,
                            'rate' => $supervisorRecord->rate ?? '',
                            'user_id' => $supervisorRecord->user_id,
                            'color' => $supervisorRecord->color ?? "#" . substr(md5(rand()), 0, 6),
                            'allow_report_hours' => 1,
                        ]);
                    }
                    $staffId = $staffUser->id; // Use the real staff ID for database operations
                }

                foreach ($staffDailyHours as $date => $timeRanges) {
                    $currentDate = Carbon::parse($date);
                    if ($currentDate->between($weekStart, $weekEnd)) {
                        $formattedHours = [];

                        foreach ($timeRanges as $timeRange) {
                            if (empty($timeRange)) continue;

                            // Check if this entry is marked as sick leave
                            $isSickLeave = isset($request->input('sick_leave')[$staffId][$date]);

                            // Handle special values (V, X, H)
                            if (in_array($timeRange, ['V', 'X', 'H'])) {
                                $formattedHours[] = [
                                    'type' => $timeRange,
                                    'start_time' => null,
                                    'end_time' => null
                                ];
                                continue;
                            }

                            // Handle JSON format for entries with original values
                            if (substr($timeRange, 0, 1) === '{') {
                                try {
                                    $timeData = json_decode($timeRange, true);
                                    if (json_last_error() === JSON_ERROR_NONE && isset($timeData['start_time']) && isset($timeData['end_time'])) {

                                        // For special type entries (on_call, reception), only include original times if they actually exist in the data
                                        if (isset($timeData['type']) && in_array($timeData['type'], ['on_call', 'reception'])) {
                                            $formattedEntry = [
                                                'start_time' => $timeData['start_time'],
                                                'end_time' => $timeData['end_time'],
                                                'type' => $timeData['type']
                                            ];

                                            // Only add original times if they exist AND are different from current times
                                            if (
                                                isset($timeData['original_start_time']) &&
                                                isset($timeData['original_end_time']) &&
                                                ($timeData['original_start_time'] !== $timeData['start_time'] ||
                                                    $timeData['original_end_time'] !== $timeData['end_time'])
                                            ) {
                                                $formattedEntry['original_start_time'] = $timeData['original_start_time'];
                                                $formattedEntry['original_end_time'] = $timeData['original_end_time'];
                                            }

                                            $formattedHours[] = $formattedEntry;
                                        } else {
                                            // For other JSON entries (normal time updates), include original times as before
                                            $formattedHours[] = [
                                                'start_time' => $timeData['start_time'],
                                                'end_time' => $timeData['end_time'],
                                                'type' => $isSickLeave ? 'SL' : ($timeData['type'] ?? 'normal'),
                                                'original_start_time' => $timeData['original_start_time'] ?? $timeData['start_time'],
                                                'original_end_time' => $timeData['original_end_time'] ?? $timeData['end_time']
                                            ];
                                        }
                                        continue;
                                    }
                                } catch (\Exception $e) {
                                    Log::error('Failed to parse time range JSON', [
                                        'timeRange' => $timeRange,
                                        'error' => $e->getMessage()
                                    ]);
                                }
                            }

                            // Handle time ranges
                            if (strpos($timeRange, '-') !== false) {
                                list($start, $end) = explode('-', $timeRange);
                                $formattedHours[] = [
                                    'start_time' => trim($start),
                                    'end_time' => trim($end),
                                    'type' => $isSickLeave ? 'SL' : 'normal'
                                ];
                            }
                        }

                        // Save the formatted hours
                        if (!empty($formattedHours)) {
                            // Get existing record to check if data has changed
                            $existingRecord = StaffMonthlyHours::where('staff_id', $staffId)
                                ->where('date', $date)
                                ->first();

                            // Determine approval status based on user role and data changes
                            $isApproved = 1; // Default for supervisors

                            // Check if this is for HR assistant or team lead
                            if (Auth::user()->role === 'hr-assistant' || Auth::user()->role === 'team-lead') {
                                if ($existingRecord) {
                                    // Check if the hours data has actually changed
                                    $existingHours = $existingRecord->hours_data;
                                    $hasChanged = json_encode($existingHours) !== json_encode($formattedHours);
                                    
                                    if ($hasChanged) {
                                        // Data has changed, mark as pending approval
                                        $isApproved = 0;
                                    } else {
                                        // Data hasn't changed, keep existing approval status
                                        $isApproved = $existingRecord->is_approved;
                                    }
                                } else {
                                    // New record, mark as pending approval
                                    $isApproved = 0;
                                }
                            }
                            // Check if this is a regular staff member reporting their own hours
                            elseif (Auth::user()->role !== 'admin' && Auth::user()->role !== 'supervisor') {
                                // Regular staff users (guide/staff, etc.) with allow_report_hours permission
                                // Check if they are reporting their own hours
                                $currentStaffUser = StaffUser::where('user_id', Auth::user()->id)->first();
                                if ($currentStaffUser && $currentStaffUser->id == $staffId) {
                                    // Staff member is reporting their own hours
                                    if ($existingRecord) {
                                        // Check if the hours data has actually changed
                                        $existingHours = $existingRecord->hours_data;
                                        $hasChanged = json_encode($existingHours) !== json_encode($formattedHours);
                                        
                                        if ($hasChanged) {
                                            // Data has changed, mark as pending approval
                                            $isApproved = 0;
                                        } else {
                                            // Data hasn't changed, keep existing approval status
                                            $isApproved = $existingRecord->is_approved;
                                        }
                                    } else {
                                        // New record, mark as pending approval
                                        $isApproved = 0;
                                    }
                                }
                            }

                            StaffMonthlyHours::updateOrCreate(
                                [
                                    'staff_id' => $staffId,
                                    'date' => $date,
                                ],
                                [
                                    'hours_data' => $formattedHours,
                                    'is_approved' => $isApproved,
                                ]
                            );
                        } else {
                            StaffMonthlyHours::where('staff_id', $staffId)
                                ->where('date', $date)
                                ->delete();
                        }

                        // Process sick leaves in supervisor_sick_leaves table
                        $hasSickLeave = collect($formattedHours)->contains(function ($entry) {
                            return isset($entry['type']) && $entry['type'] === 'SL';
                        });

                        // Update this query to use start_date instead of date
                        $existingSL = SupervisorSickLeaves::where('staff_id', $staffId)
                            ->whereDate('start_date', $date)
                            ->first();

                        if ($hasSickLeave) {
                            if (!$existingSL) {
                                SupervisorSickLeaves::create([
                                    'staff_id' => $staffId,
                                    'start_date' => Carbon::parse($date)->startOfDay(),
                                    'end_date' => Carbon::parse($date)->startOfDay(),
                                    'supervisor_id' => auth()->id(),
                                    'status' => '0'
                                ]);
                            } else if ($existingSL->status === '4') {
                                // Reactivate cancelled sick leave
                                $existingSL->update([
                                    'status' => '0',
                                    'supervisor_id' => auth()->id(),
                                    'updated_at' => now(),
                                    'image' => null,
                                    'supervisor_remark' => null,
                                    'admin_remark' => null,
                                    'admin_id' => null
                                ]);
                            }
                        } else if ($existingSL && $existingSL->status !== '4') {
                            // Cancel existing sick leave if no longer marked as SL
                            // $existingSL->update([
                            //     'status' => '4',
                            //     'updated_at' => now()
                            // ]);
                        }
                    }
                }
            }

            // Process reception and midnight phone data
            foreach ($weekStart->daysUntil($weekEnd) as $date) {
                $dateString = $date->format('Y-m-d');

                // Check if we have any data to process for this date
                $shouldProcessReception = isset($receptionData[$dateString]);
                $shouldProcessMidnightPhone = array_key_exists($dateString, $midnightPhoneData);

                if ($shouldProcessReception || $shouldProcessMidnightPhone) {
                    // Get the department from the form submission
                    $currentDepartment = $request->input('department');

                    // Always use department-specific lookup for ALL roles
                    $staffHoursDetails = StaffHoursDetails::where('date', $dateString)
                        ->where('department', $currentDepartment)
                        ->first();

                    if (!$staffHoursDetails) {
                        $staffHoursDetails = new StaffHoursDetails();
                        $staffHoursDetails->date = $dateString;
                        $staffHoursDetails->department = $currentDepartment;
                    }

                    // Process reception data
                    if ($shouldProcessReception) {
                        $staffHoursDetails->reception = $receptionData[$dateString];
                    }

                    // Process midnight phone data (only for Operations department)
                    if ($shouldProcessMidnightPhone && $currentDepartment === 'Operations') {
                        $midnightPhoneValue = $midnightPhoneData[$dateString];

                        if (empty($midnightPhoneValue) || $midnightPhoneValue === '' || $midnightPhoneValue === null) {
                            $staffHoursDetails->midnight_phone = [];
                        } else {
                            $staffHoursDetails->midnight_phone = [(int)$midnightPhoneValue];
                        }
                    }

                    $staffHoursDetails->save();

                    Log::info('Updated StaffHoursDetails', [
                        'date' => $dateString,
                        'department' => $staffHoursDetails->department,
                        'midnight_phone' => $staffHoursDetails->midnight_phone,
                        'reception' => $staffHoursDetails->reception ?? 'not set',
                        'user_role' => Auth::user()->role
                    ]);
                }
            }

            DB::commit();
            return redirect()->back()->with('success', 'Working hours and sick leaves have been successfully saved.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to store working hours', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()->with('error', 'An error occurred while saving. Please try again.');
        }
    }

    public function staffStoreWorkingHours(Request $request)
    {
        try {
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

            foreach ($hoursData as $staffId => $staffDailyHours) {

                if (strpos($staffId, 'supervisor_') === 0) {
                    $supervisorUserId = str_replace('supervisor_', '', $staffId);

                    // Find or create StaffUser record for supervisor with correct department
                    $supervisorRecord = Supervisors::where('user_id', $supervisorUserId)->first();
                    $staffUser = StaffUser::where('user_id', $supervisorUserId)->first();

                    if (!$staffUser && $supervisorRecord) {
                        $staffUser = StaffUser::create([
                            'name' => $supervisorRecord->user->name,
                            'full_name' => $supervisorRecord->user->name,
                            'email' => $supervisorRecord->user->email,
                            'department' => $supervisorRecord->department,
                            'phone_number' => $supervisorRecord->phone_number ?? '',
                            'is_supervisor' => true,
                            'rate' => $supervisorRecord->rate ?? '',
                            'user_id' => $supervisorRecord->user_id,
                            'color' => $supervisorRecord->color ?? "#" . substr(md5(rand()), 0, 6),
                            'allow_report_hours' => 1,
                        ]);
                    }
                    $staffId = $staffUser->id; // Use the real staff ID for database operations
                }

                foreach ($staffDailyHours as $date => $timeRanges) {
                    $currentDate = Carbon::parse($date);
                    if ($currentDate->between($weekStart, $weekEnd)) {
                        $formattedHours = [];

                        foreach ($timeRanges as $timeRange) {
                            if (empty($timeRange)) continue;

                            // Check if this entry is marked as sick leave
                            $isSickLeave = isset($request->input('sick_leave')[$staffId][$date]);

                            // Handle special values (V, X, H)
                            if (in_array($timeRange, ['V', 'X', 'H'])) {
                                $formattedHours[] = [
                                    'type' => $timeRange,
                                    'start_time' => null,
                                    'end_time' => null
                                ];
                                continue;
                            }

                            // Handle JSON format for entries with original values
                            if (substr($timeRange, 0, 1) === '{') {
                                try {
                                    $timeData = json_decode($timeRange, true);
                                    if (json_last_error() === JSON_ERROR_NONE && isset($timeData['start_time']) && isset($timeData['end_time'])) {

                                        // Debug: Log the received time data
                                        if (isset($timeData['notes'])) {
                                            Log::info('Time entry with notes received', [
                                                'staff_id' => $staffId,
                                                'date' => $date,
                                                'time_data' => $timeData
                                            ]);
                                        }

                                        // Use helper function to process time data with notes
                                        $formattedHours[] = $this->processTimeDataWithNotes($timeData, $isSickLeave);
                                        continue;
                                    }
                                } catch (\Exception $e) {
                                    Log::error('Failed to parse time range JSON', [
                                        'timeRange' => $timeRange,
                                        'error' => $e->getMessage()
                                    ]);
                                }
                            }

                            // Handle time ranges
                            if (strpos($timeRange, '-') !== false) {
                                list($start, $end) = explode('-', $timeRange);
                                $formattedHours[] = [
                                    'start_time' => trim($start),
                                    'end_time' => trim($end),
                                    'type' => $isSickLeave ? 'SL' : 'normal'
                                ];
                            }
                        }

                        // Save the formatted hours
                        if (!empty($formattedHours)) {
                            // Get existing record to check if data has changed
                            $existingRecord = StaffMonthlyHours::where('staff_id', $staffId)
                                ->where('date', $date)
                                ->first();

                            // Determine approval status based on user role and data changes
                            $isApproved = 1; // Default for supervisors

                            // Check if this is for HR assistant or team lead
                            if (Auth::user()->role === 'hr-assistant' || Auth::user()->role === 'team-lead') {
                                if ($existingRecord) {
                                    // Check if the hours data has actually changed
                                    $existingHours = $existingRecord->hours_data;
                                    $hasChanged = json_encode($existingHours) !== json_encode($formattedHours);
                                    
                                    if ($hasChanged) {
                                        // Data has changed, mark as pending approval
                                        $isApproved = 0;
                                    } else {
                                        // Data hasn't changed, keep existing approval status
                                        $isApproved = $existingRecord->is_approved;
                                    }
                                } else {
                                    // New record, mark as pending approval
                                    $isApproved = 0;
                                }
                            }
                            // Check if this is a regular staff member reporting their own hours
                            elseif (Auth::user()->role !== 'admin' && Auth::user()->role !== 'supervisor') {
                                // Regular staff users (guide/staff, etc.) with allow_report_hours permission
                                // Check if they are reporting their own hours
                                $currentStaffUser = StaffUser::where('user_id', Auth::user()->id)->first();
                                if ($currentStaffUser && $currentStaffUser->id == $staffId) {
                                    // Staff member is reporting their own hours
                                    if ($existingRecord) {
                                        // Check if the hours data has actually changed
                                        $existingHours = $existingRecord->hours_data;
                                        $hasChanged = json_encode($existingHours) !== json_encode($formattedHours);
                                        
                                        if ($hasChanged) {
                                            // Data has changed, mark as pending approval
                                            $isApproved = 0;
                                        } else {
                                            // Data hasn't changed, keep existing approval status
                                            $isApproved = $existingRecord->is_approved;
                                        }
                                    } else {
                                        // New record, mark as pending approval
                                        $isApproved = 0;
                                    }
                                }
                            }

                            StaffMonthlyHours::updateOrCreate(
                                [
                                    'staff_id' => $staffId,
                                    'date' => $date,
                                ],
                                [
                                    'hours_data' => $formattedHours,
                                    'is_approved' => $isApproved,
                                ]
                            );
                        } else {
                            StaffMonthlyHours::where('staff_id', $staffId)
                                ->where('date', $date)
                                ->delete();
                        }

                        // Process sick leaves in supervisor_sick_leaves table
                        $hasSickLeave = collect($formattedHours)->contains(function ($entry) {
                            return isset($entry['type']) && $entry['type'] === 'SL';
                        });

                        // Update this query to use start_date instead of date
                        $existingSL = SupervisorSickLeaves::where('staff_id', $staffId)
                            ->whereDate('start_date', $date)
                            ->first();

                        if ($hasSickLeave) {
                            if (!$existingSL) {
                                SupervisorSickLeaves::create([
                                    'staff_id' => $staffId,
                                    'start_date' => Carbon::parse($date)->startOfDay(),
                                    'end_date' => Carbon::parse($date)->startOfDay(),
                                    'supervisor_id' => auth()->id(),
                                    'status' => '0'
                                ]);
                            } else if ($existingSL->status === '4') {
                                // Reactivate cancelled sick leave
                                $existingSL->update([
                                    'status' => '0',
                                    'supervisor_id' => auth()->id(),
                                    'updated_at' => now(),
                                    'image' => null,
                                    'supervisor_remark' => null,
                                    'admin_remark' => null,
                                    'admin_id' => null
                                ]);
                            }
                        } else if ($existingSL && $existingSL->status !== '4') {
                            // Cancel existing sick leave if no longer marked as SL
                            // $existingSL->update([
                            //     'status' => '4',
                            //     'updated_at' => now()
                            // ]);
                        }
                    }
                }
            }

            // Process reception and midnight phone data
            foreach ($weekStart->daysUntil($weekEnd) as $date) {
                $dateString = $date->format('Y-m-d');

                // Check if we have any data to process for this date
                $shouldProcessReception = isset($receptionData[$dateString]);
                $shouldProcessMidnightPhone = array_key_exists($dateString, $midnightPhoneData);

                if ($shouldProcessReception || $shouldProcessMidnightPhone) {
                    $staffHoursDetails = StaffHoursDetails::firstOrNew(['date' => $dateString]);

                    // Process reception data
                    if ($shouldProcessReception) {
                        $staffHoursDetails->reception = $receptionData[$dateString];
                    }

                    // Process midnight phone data
                    if ($shouldProcessMidnightPhone) {
                        $midnightPhoneValue = $midnightPhoneData[$dateString];

                        if (empty($midnightPhoneValue) || $midnightPhoneValue === '' || $midnightPhoneValue === null) {
                            // User selected "-- No Assignment --" or cleared the selection
                            $staffHoursDetails->midnight_phone = [];
                        } else {
                            // User selected a specific staff member
                            $staffHoursDetails->midnight_phone = [(int)$midnightPhoneValue];
                        }
                    }

                    // Set department if provided
                    if ($request->has('department')) {
                        $staffHoursDetails->department = $request->department;
                    }

                    $staffHoursDetails->save();

                    Log::info('Updated StaffHoursDetails', [
                        'date' => $dateString,
                        'midnight_phone' => $staffHoursDetails->midnight_phone,
                        'reception' => $staffHoursDetails->reception ?? 'not set'
                    ]);
                }
            }

            DB::commit();
            return redirect()->back()->with('success', 'Working hours and sick leaves have been successfully saved.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to store working hours', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()->with('error', 'An error occurred while saving. Please try again.');
        }
    }

    private function validateTimeRange($timeRange)
    {
        if (in_array($timeRange, ['V', 'X', 'SL', 'H'])) {
            return true;
        }

        if (preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]-([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $timeRange)) {
            $times = explode('-', $timeRange);
            return count($times) === 2;
        }

        return false;
    }
    
    /**
     * Process time data and include notes if available
     */
    private function processTimeDataWithNotes($timeData, $isSickLeave = false)
    {
        $formattedEntry = [
            'start_time' => $timeData['start_time'],
            'end_time' => $timeData['end_time'],
            'type' => $isSickLeave ? 'SL' : ($timeData['type'] ?? 'normal')
        ];

        // For special types (on_call, reception), only add original times if they exist and are different
        if (isset($timeData['type']) && in_array($timeData['type'], ['on_call', 'reception'])) {
            // Only add original times if they exist AND are different from current times
            if (
                isset($timeData['original_start_time']) &&
                isset($timeData['original_end_time']) &&
                ($timeData['original_start_time'] !== $timeData['start_time'] ||
                    $timeData['original_end_time'] !== $timeData['end_time'])
            ) {
                $formattedEntry['original_start_time'] = $timeData['original_start_time'];
                $formattedEntry['original_end_time'] = $timeData['original_end_time'];
            }
        } else {
            // For normal entries, add original times if they exist, otherwise use current times
            $formattedEntry['original_start_time'] = $timeData['original_start_time'] ?? $timeData['start_time'];
            $formattedEntry['original_end_time'] = $timeData['original_end_time'] ?? $timeData['end_time'];
        }

        // Add notes if present
        if (isset($timeData['notes']) && !empty(trim($timeData['notes']))) {
            $formattedEntry['notes'] = trim($timeData['notes']);
        }

        return $formattedEntry;
    }

    public function displaySchedule()
    {
        $currentDate = Carbon::now();
        $weekStart = $currentDate->copy()->startOfWeek()->setYear(2025);
        $staffMembers = StaffUser::all();
        return view('supervisor.display-schedule', compact('weekStart', 'staffMembers'));
    }

    public function getWeekData(Request $request)
    {
        $weekStart = Carbon::parse($request->week_start)->startOfWeek();
        $weekEnd = $weekStart->copy()->endOfWeek();

        // Get supervisor's departments as array
        $supervisor = Supervisors::where('user_id', auth()->id())->first();
        $supervisorDepartments = explode(', ', $supervisor->department);

        // Get staff IDs that belong to any of the supervisor's departments
        $staffIds = StaffUser::where(function ($query) use ($supervisorDepartments) {
            foreach ($supervisorDepartments as $department) {
                // Use LIKE queries to find staff with this department
                // This handles both exact matches and cases where the department is part of a list
                $query->orWhere('department', 'LIKE', $department)
                    ->orWhere('department', 'LIKE', $department . ',%')
                    ->orWhere('department', 'LIKE', '%, ' . $department)
                    ->orWhere('department', 'LIKE', '%, ' . $department . ',%');
            }
        })->pluck('id');

        // Rest of the method remains the same...
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
        return array_map(function ($timeRange) {
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
        $baseStartDate = Carbon::parse('2025-03-31');
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

        // Get staff members based on supervisor selection
        if ($selectedSupervisor) {
            // When a specific supervisor is selected from dropdown
            $staffMembers = StaffUser::where(function ($query) use ($selectedSupervisor) {
                $query->where('department', 'LIKE', $selectedSupervisor)
                    ->orWhere('department', 'LIKE', $selectedSupervisor . ',%')
                    ->orWhere('department', 'LIKE', '%, ' . $selectedSupervisor)
                    ->orWhere('department', 'LIKE', '%, ' . $selectedSupervisor . ',%');
            })->get();
        } else {
            // Get current supervisor's departments
            $supervisor = Supervisors::where('user_id', auth()->id())->first();
            $supervisorDepartments = explode(', ', $supervisor->department);

            // Get staff that belong to any of the supervisor's departments
            $staffMembers = StaffUser::where(function ($query) use ($supervisorDepartments) {
                foreach ($supervisorDepartments as $department) {
                    // Use LIKE queries to find staff with this department
                    $query->orWhere('department', 'LIKE', $department)
                        ->orWhere('department', 'LIKE', $department . ',%')
                        ->orWhere('department', 'LIKE', '%, ' . $department)
                        ->orWhere('department', 'LIKE', '%, ' . $department . ',%');
                }
            })->get();

            // Set selected supervisor to the first department for display purposes
            $selectedSupervisor = $supervisorDepartments[0] ?? '';
        }

        // Get display_midnight_phone setting
        $displayMidnightPhone = false;
        if ($selectedSupervisor) {
            $supervisorRecord = Supervisors::where(function ($query) use ($selectedSupervisor) {
                $query->where('department', 'LIKE', $selectedSupervisor)
                    ->orWhere('department', 'LIKE', $selectedSupervisor . ',%')
                    ->orWhere('department', 'LIKE', '%, ' . $selectedSupervisor)
                    ->orWhere('department', 'LIKE', '%, ' . $selectedSupervisor . ',%');
            })->first();

            if ($supervisorRecord) {
                $displayMidnightPhone = $supervisorRecord->display_midnight_phone ?? false;
            }
        }

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
            $dateString = $hours->date->format('Y-m-d');

            // Process each entry in hours_data to ensure original time values are preserved
            $processedHoursData = [];

            foreach ($hours->hours_data as $entry) {
                // Check if entry is already an array
                if (is_array($entry)) {
                    // Add SL prefix for sick leave entries
                    if (isset($entry['type']) && $entry['type'] === 'SL') {
                        $entry['display_prefix'] = 'SL - ';
                    }
                    $processedHoursData[] = $entry;
                }
                // Handle string values (could be JSON or plain text)
                else if (is_string($entry)) {
                    // Check if it's a JSON string with original times
                    if (substr($entry, 0, 1) === '{' && substr($entry, -1) === '}') {
                        try {
                            $timeData = json_decode($entry, true);
                            if (json_last_error() === JSON_ERROR_NONE) {
                                // Add SL prefix for sick leave entries
                                if (isset($timeData['type']) && $timeData['type'] === 'SL') {
                                    $timeData['display_prefix'] = 'SL - ';
                                }
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

            $staffHours[$hours->staff_id][$dateString] = $processedHoursData;
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
        ])->pluck('holiday_date')->map(function ($date) {
            return Carbon::parse($date)->format('Y-m-d');
        })->toArray();

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

    public function adminManageSickLeaves(Request $request)
    {
        $query = SupervisorSickLeaves::query();


        // Apply date range filter
        if ($request->filled('start_date')) {
            $query->whereDate('date', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('date', '<=', $request->end_date);
        }

        // Apply status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        } else {
            $query->whereIn('status', [0, 1]);
        }

        $sickLeaves = $query->orderBy('start_date', 'desc')
            ->paginate(20)
            ->appends($request->query());

        return view('supervisor.admin-manage-sick-leaves', compact('sickLeaves'));
    }

    public function supervisorsManageSickLeaves(Request $request)
    {
        $query = SupervisorSickLeaves::query();

        // Get supervisor's departments as array
        $supervisor = Supervisors::where('user_id', Auth::user()->id)->first();

        // Apply supervisor filter only if not admin or HR
        if (Auth::user()->role !== 'admin' && Auth::user()->role !== 'hr') {
            $supervisorDepartments = explode(', ', $supervisor->department);

            // Use a where clause that checks if department matches any of the supervisor's departments
            $query->where(function ($q) use ($supervisorDepartments) {
                foreach ($supervisorDepartments as $department) {
                    // Use LIKE queries to find sick leaves with this department
                    // This handles both exact matches and cases where the department is part of a list
                    $q->orWhere('department', 'LIKE', $department)
                        ->orWhere('department', 'LIKE', $department . ',%')
                        ->orWhere('department', 'LIKE', '%, ' . $department)
                        ->orWhere('department', 'LIKE', '%, ' . $department . ',%');
                }
            });
        }

        // Apply date range filter
        if ($request->filled('start_date')) {
            $query->whereDate('start_date', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('end_date', '<=', $request->end_date);
        }

        // Apply status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        } else {
            $query->where('status', 0);
        }

        $sickLeaves = $query->orderBy('start_date', 'desc')
            ->paginate(20)
            ->appends($request->query());

        return view('supervisor.sick-leaves', compact('sickLeaves'));
    }

    public function uploadPrescription(Request $request)
    {
        $request->validate([
            'leave_id' => 'required|exists:supervisor_sick_leaves,id',
            'prescription' => 'required|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'comment' => 'required|string|max:255'
        ]);

        try {
            DB::beginTransaction();

            $sickLeave = SupervisorSickLeaves::findOrFail($request->leave_id);

            // Handle file upload
            if ($request->hasFile('prescription')) {
                $file = $request->file('prescription');
                $filename = time() . '_' . $file->getClientOriginalName();
                $path = $file->storeAs('prescriptions', $filename, 'public');

                $sickLeave->update([
                    'image' => $path,
                    'status' => 1,
                    'supervisor_remark' => $request->comment
                ]);
            }

            DB::commit();
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function updateSickLeaveStatus(Request $request)
    {
        $request->validate([
            'leave_id' => 'required|exists:supervisor_sick_leaves,id',
            'status' => 'required|in:2,3',
            'admin_remark' => 'required|string'
        ]);

        try {
            DB::beginTransaction();

            SupervisorSickLeaves::where('id', $request->leave_id)
                ->update([
                    'status' => $request->status,
                    'admin_id' => auth()->id(),
                    'admin_remark' => $request->admin_remark
                ]);

            DB::commit();
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function cancelSickLeave($id)
    {
        try {
            DB::beginTransaction();

            SupervisorSickLeaves::where('id', $id)->update([
                'status' => 4,
                'updated_at' => now()
            ]);

            DB::commit();
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function manageStaff()
    {
        if (Auth::user()->role == 'supervisor') {
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
            })
                ->orderBy('order') // Order by the order column
                ->with(['user:id,name,email'])
                ->get();
        } else {
            $staffMembers = StaffUser::orderBy('order')
                ->with(['user:id,name,email'])
                ->get();
        }

        return view('supervisor.manage-staff', compact('staffMembers'));
    }

    public function updateStaffColor(Request $request, $id)
    {
        try {
            $staff = StaffUser::findOrFail($id);
            $staff->color = $request->color;
            $staff->save();

            return response()->json([
                'success' => true,
                'message' => 'Color updated successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating staff color', [
                'staff_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error updating color'
            ], 500);
        }
    }

    public function populatePreviousWeek(Request $request)
    {
        // Get the current week date from request
        $currentWeek = $request->query('week', Carbon::now()->format('Y-m-d'));
        $currentWeekDate = Carbon::parse($currentWeek);

        // Calculate previous week
        $previousWeekDate = $currentWeekDate->copy()->subWeek();

        // Start and end dates for both weeks
        $currentWeekStart = $currentWeekDate->copy()->startOfWeek();
        $currentWeekEnd = $currentWeekDate->copy()->endOfWeek();
        $previousWeekStart = $previousWeekDate->copy()->startOfWeek();
        $previousWeekEnd = $previousWeekDate->copy()->endOfWeek();

        // Get supervisor's department and staff
        if (Auth::user()->role == 'supervisor') {
            $supervisorDepartment = Supervisors::where('user_id', auth()->id())->first()->department;
            $staffMembers = StaffUser::where('department', $supervisorDepartment)->get();
        } else {
            // For non-supervisors, get their supervisor's staff
            $supervisorIds = DB::table('staff_supervisor')
                ->where('staff_user_id', Auth::user()->id)
                ->pluck('supervisor_id');

            $staffMembers = StaffUser::whereHas('supervisors', function ($query) use ($supervisorIds) {
                $query->whereIn('supervisor_id', $supervisorIds);
            })->get();
        }

        try {
            DB::beginTransaction();

            // 1. Check if previous week has data
            $previousWeekData = StaffMonthlyHours::whereIn('staff_id', $staffMembers->pluck('id'))
                ->whereBetween('date', [$previousWeekStart, $previousWeekEnd])
                ->get();

            if ($previousWeekData->isEmpty()) {
                return redirect()
                    ->route('supervisor.enter-working-hours', ['week' => $currentWeek])
                    ->with('error', 'No data found for the previous week to populate.');
            }

            // 2. Delete any existing data for the current week to avoid duplicates
            StaffMonthlyHours::whereIn('staff_id', $staffMembers->pluck('id'))
                ->whereBetween('date', [$currentWeekStart, $currentWeekEnd])
                ->delete();

            // Also delete any StaffHoursDetails for the current week
            StaffHoursDetails::whereBetween('date', [$currentWeekStart, $currentWeekEnd])
                ->delete();

            // 3. Clone previous week's staff hours to current week, but filter out sick leave entries
            foreach ($previousWeekData as $prevHours) {
                // FIXED: Calculate the correct new date using day difference from week start
                $prevDate = Carbon::parse($prevHours->date);
                $daysSinceWeekStart = $prevDate->diffInDays($previousWeekStart);
                $newDate = $currentWeekStart->copy()->addDays($daysSinceWeekStart);

                // Filter out sick leave entries from hours_data
                $filteredHoursData = array_filter($prevHours->hours_data, function ($entry) {
                    // Keep entries that don't have 'type' => 'SL'
                    return !(isset($entry['type']) && $entry['type'] === 'SL');
                });

                // Only create record if there's remaining data after filtering
                if (!empty($filteredHoursData)) {
                    StaffMonthlyHours::create([
                        'staff_id' => $prevHours->staff_id,
                        'date' => $newDate->format('Y-m-d'),
                        'hours_data' => array_values($filteredHoursData), // Reset array keys
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            // 4. Clone previous week's reception and midnight phone data
            $prevDetails = StaffHoursDetails::whereBetween('date', [$previousWeekStart, $previousWeekEnd])
                ->get();

            foreach ($prevDetails as $detail) {
                // FIXED: Calculate the correct new date using day difference from week start
                $prevDate = Carbon::parse($detail->date);
                $daysSinceWeekStart = $prevDate->diffInDays($previousWeekStart);
                $newDate = $currentWeekStart->copy()->addDays($daysSinceWeekStart);

                // Create new record for current week
                StaffHoursDetails::create([
                    'date' => $newDate->format('Y-m-d'),
                    'reception' => $detail->reception,
                    'midnight_phone' => $detail->midnight_phone,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // We don't copy sick leaves as they need to be newly approved for the current week

            DB::commit();

            return redirect()
                ->route('supervisor.enter-working-hours', ['week' => $currentWeek])
                ->with('success', 'Successfully populated the current week with last week\'s data (excluding sick leaves). Please review and make any necessary adjustments.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to populate previous week data', [
                'user_id' => auth()->id(),
                'week' => $currentWeek,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()
                ->route('supervisor.enter-working-hours', ['week' => $currentWeek])
                ->with('error', 'An error occurred while copying data: ' . $e->getMessage());
        }
    }

    public function clearCurrentWeek(Request $request)
    {
        // Get the current week date from request
        $currentWeek = $request->query('week', Carbon::now()->format('Y-m-d'));
        $currentWeekDate = Carbon::parse($currentWeek);

        // Start and end dates for current week
        $currentWeekStart = $currentWeekDate->copy()->startOfWeek();
        $currentWeekEnd = $currentWeekDate->copy()->endOfWeek();

        // Get staff members based on user role
        if (Auth::user()->role == 'supervisor') {
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
        } elseif (Auth::user()->role == 'hr-assistant') {
            // For HR assistants, only clear their own data
            $staffMembers = StaffUser::where('user_id', Auth::user()->id)->get();

            // If HR assistant doesn't exist in staff_users, create empty collection
            if ($staffMembers->isEmpty()) {
                $staffMembers = collect([]);
            }
        } elseif (Auth::user()->role == 'team-lead') {
            // For team leads, only clear their own data
            $staffMembers = StaffUser::where('user_id', Auth::user()->id)->get();

            // If team lead doesn't exist in staff_users, create empty collection
            if ($staffMembers->isEmpty()) {
                $staffMembers = collect([]);
            }
        } else {
            // For non-supervisors, get their supervisor's staff
            $supervisorIds = DB::table('staff_supervisor')
                ->where('staff_user_id', Auth::user()->id)
                ->pluck('supervisor_id');

            $staffMembers = StaffUser::whereHas('supervisors', function ($query) use ($supervisorIds) {
                $query->whereIn('supervisor_id', $supervisorIds);
            })->get();
        }

        try {
            DB::beginTransaction();

            // Only proceed if we have staff members to clear
            if ($staffMembers->isNotEmpty()) {
                // Delete any existing data for the current week
                StaffMonthlyHours::whereIn('staff_id', $staffMembers->pluck('id'))
                    ->whereBetween('date', [$currentWeekStart, $currentWeekEnd])
                    ->delete();

                // Cancel any existing sick leaves for the current week
                SupervisorSickLeaves::whereIn('staff_id', $staffMembers->pluck('id'))
                    ->where(function ($query) use ($currentWeekStart, $currentWeekEnd) {
                        // Check if either start_date or end_date falls within the current week
                        // Or if start_date is before the week and end_date is after (spanning the entire week)
                        $query->whereBetween('start_date', [$currentWeekStart, $currentWeekEnd])
                            ->orWhereBetween('end_date', [$currentWeekStart, $currentWeekEnd])
                            ->orWhere(function ($q) use ($currentWeekStart, $currentWeekEnd) {
                                $q->where('start_date', '<=', $currentWeekStart)
                                    ->where('end_date', '>=', $currentWeekEnd);
                            });
                    })
                    ->update([
                        'status' => '4', // Cancelled status
                        'updated_at' => now()
                    ]);
            }

            // Delete StaffHoursDetails based on user role and department
            if (Auth::user()->role == 'hr-assistant') {
                // Clear StaffHoursDetails for Guide Supervisor department
                StaffHoursDetails::whereBetween('date', [$currentWeekStart, $currentWeekEnd])
                    ->where('department', 'Guide Supervisor')
                    ->delete();
            } elseif (Auth::user()->role == 'team-lead') {
                // Clear StaffHoursDetails for Bus Driver Supervisor department
                StaffHoursDetails::whereBetween('date', [$currentWeekStart, $currentWeekEnd])
                    ->where('department', 'Bus Driver Supervisor')
                    ->delete();
            } elseif (Auth::user()->role == 'supervisor') {
                // Clear StaffHoursDetails for supervisor's departments
                $supervisor = Supervisors::where('user_id', auth()->id())->first();
                $supervisorDepartments = explode(', ', $supervisor->department);

                StaffHoursDetails::whereBetween('date', [$currentWeekStart, $currentWeekEnd])
                    ->where(function ($query) use ($supervisorDepartments) {
                        foreach ($supervisorDepartments as $department) {
                            $query->orWhere('department', $department);
                        }
                    })
                    ->delete();
            } else {
                // For other roles, delete all StaffHoursDetails for the week (existing behavior)
                StaffHoursDetails::whereBetween('date', [$currentWeekStart, $currentWeekEnd])
                    ->delete();
            }

            DB::commit();

            return redirect()
                ->back()
                ->with('success', 'Successfully cleared all data for the current week.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to clear current week data', [
                'user_id' => auth()->id(),
                'user_role' => Auth::user()->role,
                'week' => $currentWeek,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()
                ->back()
                ->with('error', 'An error occurred while clearing data: ' . $e->getMessage());
        }
    }

    public function manageMissingHours()
    {
        $supervisorId = Auth()->id();
        $supervisor = Supervisors::where('user_id', $supervisorId)->first();
        $supervisorDepartments = explode(', ', $supervisor->department);


        // Get staff that belong to any of the supervisor's departments
        $staffs = StaffUser::where(function ($query) use ($supervisorDepartments) {
            foreach ($supervisorDepartments as $department) {
                // Use LIKE queries to find staff with this department
                // This handles both exact matches and cases where the department is part of a list
                $query->orWhere('department', 'LIKE', $department)
                    ->orWhere('department', 'LIKE', $department . ',%')
                    ->orWhere('department', 'LIKE', '%, ' . $department)
                    ->orWhere('department', 'LIKE', '%, ' . $department . ',%');
            }
        })->get();

        $missingHours = StaffMissingHours::where('created_by', $supervisorId)->get();

        return view('supervisor.manage-missing-hours', compact('staffs', 'missingHours'));
    }

    public function store(Request $request)
    {
        // Validate the request
        $validated = $request->validate([
            'tour_name' => 'required|string|max:255',
            'guide_id' => 'required',
            'start_time' => 'required|date_format:Y-m-d H:i',

            'end_time' => 'required|date_format:Y-m-d H:i|after:start_time',
            'applied_at' => 'required|date_format:Y-m'
        ]);

        try {
            // Find the staff
            $staff = StaffUser::findOrFail($request->guide_id);

            // Create the missing hours record
            StaffMissingHours::create([
                'staff_id' => $staff->id,
                'staff_name' => $staff->name,
                'reason' => $request->tour_name,
                'date' => Carbon::parse($request->start_time)->toDateString(),
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'applied_date' => Carbon::parse($request->applied_at)->endOfMonth()->toDateString(),

                'created_by' => auth()->id()
            ]);

            return redirect()
                ->back()
                ->with('success', 'Missing hours created successfully!');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('failed', 'Error creating missing hours: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'tour_name' => 'required|string|max:255',
            'guide_id' => 'required',
            'start_time' => 'required|date_format:Y-m-d H:i',
            'end_time' => 'required|date_format:Y-m-d H:i|after:start_time',
            'applied_at' => 'required|date_format:Y-m'
        ]);

        try {
            $missingHours = StaffMissingHours::findOrFail($id);
            $staff = StaffUser::findOrFail($request->guide_id);

            // Check if user has permission to edit
            // if ($missingHours->created_by !== auth()->id()) {
            //     return redirect()
            //         ->back()
            //         ->with('failed', 'You do not have permission to edit this record.');
            // }

            $missingHours->update([
                'staff_id' => $staff->id,
                'staff_name' => $staff->name,
                'reason' => $request->tour_name,
                'date' => Carbon::parse($request->start_time)->toDateString(),
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'applied_date' => Carbon::parse($request->applied_at)->endOfMonth()->toDateString()
            ]);

            return redirect()
                ->back()
                ->with('success', 'Missing hours updated successfully!');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('failed', 'Error updating missing hours: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function destroy($id)
    {
        try {
            $missingHours = StaffMissingHours::findOrFail($id);

            // Check if the user has permission to delete this record
            // if ($missingHours->created_by !== auth()->id()) {
            //     return redirect()
            //         ->back()
            //         ->with('failed', 'You do not have permission to delete this record.');
            // }

            $missingHours->delete();

            return redirect()
                ->back()
                ->with('success', 'Missing hours record deleted successfully!');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('failed', 'Error deleting missing hours: ' . $e->getMessage());
        }
    }

    public function staffHourReport(Request $request)
    {
        // Verify user is admin or HR
        if (!in_array(Auth::user()->role, ['admin', 'hr'])) {
            return redirect()->back()->with('error', 'Unauthorized access');
        }

        // Get selected year and month, default to current
        $year = $request->input('year', date('Y'));
        $month = $request->input('month', date('n'));

        // Get all staff members for the dropdown
        $staffs = StaffUser::whereNotIn('department', ['Hotel', 'Hotel Spa', 'Hotel Restuarant'])->orderBy('name')->get();

        // Get selected staff member or default to first one
        $selectedStaffId = $request->input('staff_id', $staffs->first()->id ?? null);

        // If no staff exists or none selected, show message
        if (!$selectedStaffId) {
            return redirect()->back()->with('error', 'No staff members found');
        }

        // Convert selectedStaffId to integer to ensure consistent type comparison
        $selectedStaffId = (int)$selectedStaffId;

        $staff = StaffUser::find($selectedStaffId);
        $staffDepartment = $staff->department;

        // Create date range for the selected month
        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth();

        // Get sick leave statuses for the selected staff
        $sickLeaveStatuses = SupervisorSickLeaves::where('staff_id', $selectedStaffId)
            ->whereBetween('start_date', [$startDate, $endDate])
            ->get()
            ->groupBy(function ($item) {
                return $item->staff_id . '_' . $item->date->format('Y-m-d');
            });

        // Initialize the dailyHours array
        $dailyHours = [];

        // Get regular monthly hours
        $staffMonthlyData = StaffMonthlyHours::where('staff_id', $selectedStaffId)
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
                    'shifts' => []
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
                            $sickLeaveKey = $selectedStaffId . '_' . $dateKey;

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
        $missingHours = StaffMissingHours::where('staff_id', $selectedStaffId)
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

            Log::info("Added missing hour shift", [
                'staff_id' => $selectedStaffId,
                'date' => $dateKey,
                'start' => $startTime->format('H:i'),
                'end' => $endTime->format('H:i'),
                'original_minutes' => $minutes,
                'night_minutes' => $nightMinutes,
                'is_holiday' => $isHoliday
            ]);
        }

        // Get midnight phone hours for this staff member - Complete replacement with working implementation
        Log::info('Fetching midnight phone hours for staff member', ['staff_id' => $selectedStaffId]);

        try {
            // Direct query without any type conversions in the where clause
            $midnightPhoneHours = StaffMidnightPhone::whereRaw('staff_id = ?', [$selectedStaffId])
                ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                ->orderBy('date')
                ->orderBy('start_time')
                ->get();

            Log::info('Found midnight phone records', [
                'count' => $midnightPhoneHours->count(),
                'staff_id' => $selectedStaffId,
                'record_ids' => $midnightPhoneHours->pluck('id')->toArray(),
                'records' => $midnightPhoneHours->map(function ($record) {
                    return [
                        'id' => $record->id,
                        'date' => $record->date->format('Y-m-d'),
                        'staff_id' => $record->staff_id,
                        'staff_id_type' => gettype($record->staff_id),
                        'start_time' => $record->start_time,
                        'end_time' => $record->end_time
                    ];
                })->toArray()
            ]);
        } catch (\Exception $e) {
            Log::error("Error fetching midnight phone hours", [
                'staff_id' => $selectedStaffId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $midnightPhoneHours = collect([]);
        }

        // Process midnight phone hours as separate entries
        foreach ($midnightPhoneHours as $midnightPhone) {
            try {
                $dateKey = $midnightPhone->date->format('Y-m-d');
                $isHoliday = $this->isHoliday($midnightPhone->date);

                // Double check the staff_id match
                if ((int)$midnightPhone->staff_id !== $selectedStaffId) {
                    Log::warning('Staff ID mismatch in midnight phone record', [
                        'record_id' => $midnightPhone->id,
                        'record_staff_id' => $midnightPhone->staff_id,
                        'record_staff_id_type' => gettype($midnightPhone->staff_id),
                        'selected_staff_id' => $selectedStaffId,
                        'selected_staff_id_type' => gettype($selectedStaffId)
                    ]);
                    continue;
                }

                // Create the date entry if it doesn't exist
                if (!isset($dailyHours[$dateKey])) {
                    $dailyHours[$dateKey] = [
                        'date' => $dateKey,
                        'shifts' => []
                    ];
                }

                // Get start and end time directly from database
                $startTime = Carbon::parse($midnightPhone->start_time);
                $endTime = Carbon::parse($midnightPhone->end_time);

                // Format the display times - handle 23:59:59 as 00:00
                $displayEndTime = preg_match('/23:59:59$/', $midnightPhone->end_time) ? '00:00' : $endTime->format('H:i');

                // Log the specific record processing for debugging
                Log::info('Processing midnight phone record', [
                    'id' => $midnightPhone->id,
                    'date' => $dateKey,
                    'staff_id' => $midnightPhone->staff_id,
                    'start_time' => $startTime->format('H:i'),
                    'raw_end_time' => $midnightPhone->end_time,
                    'formatted_end_time' => $displayEndTime
                ]);

                // Calculate duration with special handling for midnight transitions
                if ($startTime->format('H:i') === '18:00' && preg_match('/23:59:59$/', $midnightPhone->end_time)) {
                    // Exact 6 hours for 18:00-00:00 shift instead of 5:59
                    $minutes = 360; // 6 hours
                } else {
                    $minutes = $startTime->copy()->diffInMinutes($endTime);
                }

                // For night hours (20:00-06:00), we need special calculation for midnight phone
                // Create proper night time range for the date
                $nightStartTime = Carbon::parse($midnightPhone->date->format('Y-m-d') . ' 20:00');
                $nightEndTime = Carbon::parse($midnightPhone->date->format('Y-m-d') . ' 06:00')->addDay();

                // Calculate night minutes - overlapping time between shift and night hours
                $nightMinutes = 0;
                if ($startTime->lt($nightEndTime) && $endTime->gt($nightStartTime)) {
                    $overlapStart = max($startTime, $nightStartTime);
                    $overlapEnd = min($endTime, $nightEndTime);
                    $nightMinutes = $overlapEnd->diffInMinutes($overlapStart);
                }

                $holidayHours = $isHoliday ? $minutes : 0;
                $holidayNightHours = $isHoliday ? $nightMinutes : 0;

                // For midnight phone, divide all hours by 3 (take 1/3 of actual time)
                $minutes = ceil($minutes / 3);
                $nightMinutes = ceil($nightMinutes / 3);
                $holidayHours = ceil($holidayHours / 3);
                $holidayNightHours = ceil($holidayNightHours / 3);

                // Add midnight phone entry to shifts with detailed logging
                $shiftEntry = [
                    'start_time' => $startTime->format('H:i'),
                    'end_time' => $displayEndTime,
                    'type' => 'midnight_phone',
                    'reason' => $midnightPhone->reason ?? 'Midnight Phone',
                    'sick_leave_status' => '',
                    'is_approved_sick_leave' => false,
                    'staff_id' => (int)$midnightPhone->staff_id,
                    'hours' => floor($minutes / 60),
                    'minutes' => $minutes % 60,
                    'holiday_hours' => floor($holidayHours / 60),
                    'holiday_minutes' => $holidayHours % 60,
                    'night_hours' => floor($nightMinutes / 60),
                    'night_minutes' => $nightMinutes % 60,
                    'holiday_night_hours' => floor($holidayNightHours / 60),
                    'holiday_night_minutes' => $holidayNightHours % 60
                ];

                // Log the entry being added
                Log::info("Adding midnight phone shift", [
                    'staff_id' => $selectedStaffId,
                    'date' => $dateKey,
                    'start' => $startTime->format('H:i'),
                    'end' => $displayEndTime,
                    'original_minutes' => $startTime->copy()->diffInMinutes($endTime),
                    'calculated_minutes' => $minutes,
                    'night_minutes' => $nightMinutes,
                    'is_holiday' => $isHoliday
                ]);

                $dailyHours[$dateKey]['shifts'][] = $shiftEntry;
            } catch (\Exception $e) {
                Log::error('Error processing midnight phone record', [
                    'record_id' => $midnightPhone->id ?? 'unknown',
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                continue;
            }
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

        // Return admin view with data
        return view('admin.staff-hours-report', compact(
            'staff',
            'staffs',
            'year',
            'month',
            'dailyHours',
            'sickLeaveStatuses',
            'missingHours',
            'selectedStaffId'
        ));
    }

    private function isHoliday(Carbon $date)
    {
        return $date->isSunday() || Holiday::where('holiday_date', $date->format('Y-m-d'))->exists();
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


    public function hotelStaffHourReport(Request $request)
    {
        $supervisorId = Auth()->id();
        $supervisorDepartment = 'Hotel';

        // Get selected year and month, default to current
        $currentDate = Carbon::now();
        $year = $request->input('year', $currentDate->year);
        $month = $request->input('month', $currentDate->month);

        // Get all hotel staff
        $staffs = StaffUser::where('department', $supervisorDepartment)->get();

        // Get the selected staff member or first staff
        $selectedStaffId = $request->input('staff_id', $staffs->first()->id);
        $selectedStaff = $staffs->where('id', $selectedStaffId)->first();

        // Get holidays for the selected month and year
        $holidays = Holiday::whereYear('holiday_date', $year)
            ->whereMonth('holiday_date', $month)
            ->pluck('holiday_date')
            ->map(function ($date) {
                return Carbon::parse($date)->format('Y-m-d');
            })
            ->toArray();

        // Initialize results array
        $staffResults = [];

        // Get working hours only for selected staff
        $staffHours = StaffMonthlyHours::where('staff_id', $selectedStaffId)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->get();

        // Initialize record data for selected staff
        $staffResults[$selectedStaffId] = [
            'staff_name' => $selectedStaff->name,
            'records' => []
        ];

        // Process regular hours
        foreach ($staffHours as $record) {
            $dateKey = $record->date->format('Y-m-d');
            $isHoliday = $this->isHoliday($record->date);

            $shifts = is_array($record->hours_data)
                ? $record->hours_data
                : json_decode($record->hours_data, true);

            if (!empty($shifts)) {
                $staffResults[$selectedStaffId]['records'][$dateKey] = [
                    'date' => $dateKey,
                    'shifts' => []
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
                            $sickLeaveKey = $selectedStaffId . '_' . $dateKey;

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

                        $staffResults[$selectedStaffId]['records'][$dateKey]['shifts'][] = [
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
        $missingHours = StaffMissingHours::where('staff_id', $selectedStaffId)
            ->whereBetween('date', $startDate, $endDate)
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
                'reason' => $missingHour->tour_name,
                'sick_leave_status' => '',
                'is_approved_sick_leave' => false,
                'missing_hour_id' => $missingHour->id,
                'missing_hour_reason' => $missingHour->reason,
                'missing_hour_status' => $missingHour->status ?? 'pending', // Default to pending if no status
                'missing_hour_applied_date' => $missingHour->applied_date,
                'missing_hour_created_by' => $missingHour->created_by,
                'original_hours' => floor($minutes / 60),
                'original_minutes' => $minutes % 60,
                'hours' => $minutes > 0 ? floor($minutes / 60) : 0,
                'minutes' => $minutes > 0 ? $minutes % 60 : 0,
                'holiday_hours' => floor($holidayHours / 60),
                'holiday_minutes' => $holidayHours % 60,
                'night_hours' => floor($nightMinutes / 60),
                'night_minutes' => $nightMinutes % 60,
                'holiday_night_hours' => floor($holidayNightHours / 60),
                'holiday_night_minutes' => $holidayNightHours % 60
            ];

            Log::info("Added missing hour shift", [
                'staff_id' => $selectedStaffId,
                'date' => $dateKey,
                'start' => $startTime->format('H:i'),
                'end' => $endTime->format('H:i'),
                'original_minutes' => $minutes,
                'night_minutes' => $nightMinutes,
                'is_holiday' => $isHoliday
            ]);
        }

        // Get midnight phone hours for this staff member - Complete replacement with working implementation
        Log::info('Fetching midnight phone hours for staff member', ['staff_id' => $selectedStaffId]);

        try {
            // Direct query without any type conversions in the where clause
            $midnightPhoneHours = StaffMidnightPhone::whereRaw('staff_id = ?', [$selectedStaffId])
                ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                ->orderBy('date')
                ->orderBy('start_time')
                ->get();

            Log::info('Found midnight phone records', [
                'count' => $midnightPhoneHours->count(),
                'staff_id' => $selectedStaffId,
                'record_ids' => $midnightPhoneHours->pluck('id')->toArray(),
                'records' => $midnightPhoneHours->map(function ($record) {
                    return [
                        'id' => $record->id,
                        'date' => $record->date->format('Y-m-d'),
                        'staff_id' => $record->staff_id,
                        'staff_id_type' => gettype($record->staff_id),
                        'start_time' => $record->start_time,
                        'end_time' => $record->end_time
                    ];
                })->toArray()
            ]);
        } catch (\Exception $e) {
            Log::error("Error fetching midnight phone hours", [
                'staff_id' => $selectedStaffId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $midnightPhoneHours = collect([]);
        }

        // Process midnight phone hours as separate entries
        foreach ($midnightPhoneHours as $midnightPhone) {
            try {
                $dateKey = $midnightPhone->date->format('Y-m-d');
                $isHoliday = $this->isHoliday($midnightPhone->date);

                // Double check the staff_id match
                if ((int)$midnightPhone->staff_id !== $selectedStaffId) {
                    Log::warning('Staff ID mismatch in midnight phone record', [
                        'record_id' => $midnightPhone->id,
                        'record_staff_id' => $midnightPhone->staff_id,
                        'record_staff_id_type' => gettype($midnightPhone->staff_id),
                        'selected_staff_id' => $selectedStaffId,
                        'selected_staff_id_type' => gettype($selectedStaffId)
                    ]);
                    continue;
                }

                // Create the date entry if it doesn't exist
                if (!isset($dailyHours[$dateKey])) {
                    $dailyHours[$dateKey] = [
                        'date' => $dateKey,
                        'shifts' => []
                    ];
                }

                // Get start and end time directly from database
                $startTime = Carbon::parse($midnightPhone->start_time);
                $endTime = Carbon::parse($midnightPhone->end_time);

                // Format the display times - handle 23:59:59 as 00:00
                $displayEndTime = preg_match('/23:59:59$/', $midnightPhone->end_time) ? '00:00' : $endTime->format('H:i');

                // Log the specific record processing for debugging
                Log::info('Processing midnight phone record', [
                    'id' => $midnightPhone->id,
                    'date' => $dateKey,
                    'staff_id' => $midnightPhone->staff_id,
                    'start_time' => $startTime->format('H:i'),
                    'raw_end_time' => $midnightPhone->end_time,
                    'formatted_end_time' => $displayEndTime
                ]);

                // Calculate duration with special handling for midnight transitions
                if ($startTime->format('H:i') === '18:00' && preg_match('/23:59:59$/', $midnightPhone->end_time)) {
                    // Exact 6 hours for 18:00-00:00 shift instead of 5:59
                    $minutes = 360; // 6 hours
                } else {
                    $minutes = $startTime->copy()->diffInMinutes($endTime);
                }

                // For night hours (20:00-06:00), we need special calculation for midnight phone
                // Create proper night time range for the date
                $nightStartTime = Carbon::parse($midnightPhone->date->format('Y-m-d') . ' 20:00');
                $nightEndTime = Carbon::parse($midnightPhone->date->format('Y-m-d') . ' 06:00')->addDay();

                // Calculate night minutes - overlapping time between shift and night hours
                $nightMinutes = 0;
                if ($startTime->lt($nightEndTime) && $endTime->gt($nightStartTime)) {
                    $overlapStart = max($startTime, $nightStartTime);
                    $overlapEnd = min($endTime, $nightEndTime);
                    $nightMinutes = $overlapEnd->diffInMinutes($overlapStart);
                }

                $holidayHours = $isHoliday ? $minutes : 0;
                $holidayNightHours = $isHoliday ? $nightMinutes : 0;

                // For midnight phone, divide all hours by 3 (take 1/3 of actual time)
                $minutes = ceil($minutes / 3);
                $nightMinutes = ceil($nightMinutes / 3);
                $holidayHours = ceil($holidayHours / 3);
                $holidayNightHours = ceil($holidayNightHours / 3);

                // Add midnight phone entry to shifts with detailed logging
                $shiftEntry = [
                    'start_time' => $startTime->format('H:i'),
                    'end_time' => $displayEndTime,
                    'type' => 'midnight_phone',
                    'reason' => $midnightPhone->reason ?? 'Midnight Phone',
                    'sick_leave_status' => '',
                    'is_approved_sick_leave' => false,
                    'staff_id' => (int)$midnightPhone->staff_id,
                    'hours' => floor($minutes / 60),
                    'minutes' => $minutes % 60,
                    'holiday_hours' => floor($holidayHours / 60),
                    'holiday_minutes' => $holidayHours % 60,
                    'night_hours' => floor($nightMinutes / 60),
                    'night_minutes' => $nightMinutes % 60,
                    'holiday_night_hours' => floor($holidayNightHours / 60),
                    'holiday_night_minutes' => $holidayNightHours % 60
                ];

                // Log the entry being added
                Log::info("Adding midnight phone shift", [
                    'staff_id' => $selectedStaffId,
                    'date' => $dateKey,
                    'start' => $startTime->format('H:i'),
                    'end' => $displayEndTime,
                    'original_minutes' => $startTime->copy()->diffInMinutes($endTime),
                    'calculated_minutes' => $minutes,
                    'night_minutes' => $nightMinutes,
                    'is_holiday' => $isHoliday
                ]);

                $dailyHours[$dateKey]['shifts'][] = $shiftEntry;
            } catch (\Exception $e) {
                Log::error('Error processing midnight phone record', [
                    'record_id' => $midnightPhone->id ?? 'unknown',
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                continue;
            }
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

        // Return admin view with data
        return view('admin.staff-hours-report', compact(
            'staff',
            'staffs',
            'year',
            'month',
            'dailyHours',
            'sickLeaveStatuses',
            'missingHours',
            'selectedStaffId'
        ));
    }

    private function calculateOverlappingMinutes($start1, $end1, $start2, $end2)
    {
        $latest_start = max($start1, $start2);
        $earliest_end = min($end1, $end2);
        $overlap_minutes = max(0, $earliest_end->diffInMinutes($latest_start));
        return $overlap_minutes;
    }

    public function reorderStaff(Request $request)
    {
        $order = $request->input('order', []);
        foreach ($order as $index => $staffId) {
            StaffUser::where('id', $staffId)->update(['order' => $index]);
        }
        return response()->json(['success' => true]);
    }

    public function toggleStaffVisibility(Request $request, $id)
    {
        $staff = StaffUser::findOrFail($id);
        $staff->hide = $request->input('hide', 0);
        $staff->save();

        return response()->json(['success' => true]);
    }
}
