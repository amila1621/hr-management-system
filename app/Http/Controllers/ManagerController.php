<?php

namespace App\Http\Controllers;

use App\Models\EventSalary;
use App\Models\TourGuide;
use App\Models\User;
use App\Models\ManagerGuideAssignment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ManagerController extends Controller
{


    public function managerDashboard(Request $request)
    {
        // Get guides assigned to the logged-in manager
        $assignedGuideIds = ManagerGuideAssignment::where('manager_id', auth()->id())
            ->pluck('guide_id')
            ->toArray();

        // Initialize an array to store working hours for all 5 periods
        $guideWorkingHours = [];

        // Use the date from the request if available, otherwise use the given date
        $currentweek = $request->input('start_date', '2024-10-14');
        
        $fixedStartDate = Carbon::createFromFormat('Y-m-d', $currentweek)->startOfWeek();

        // Loop through each of the 5 3-week periods
        for ($period = 1; $period <= 5; $period++) {
            $startDate = $fixedStartDate->copy()->addWeeks(($period - 1) * 3);
            $endDate = $fixedStartDate->copy()->addWeeks($period * 3 - 1)->endOfWeek();

            // Get event salaries only for assigned guides
            $eventSalaries = EventSalary::whereIn('guideId', $assignedGuideIds)
                ->whereHas('event', function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('start_time', [$startDate, $endDate]);
                })->get();

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
                $guideWorkingHours[$guideId]["period{$period}_hours"] = $hours;
            }
        }

        // Fetch only assigned guides' details and attach working hours
        $guides = TourGuide::whereIn('id', $assignedGuideIds)
            ->whereIn('id', array_keys($guideWorkingHours))
            ->get()
            ->map(function ($guide) use ($guideWorkingHours) {
                $guide->working_hours = $guideWorkingHours[$guide->id];
                return $guide;
            });

        // Sort guides by total hours worked across all periods in descending order
        $guides = $guides->sortByDesc(function ($guide) {
            $totalHours = array_sum($guide->working_hours);
            return $totalHours;
        });

        $updatedate = DB::table('updatedate')
            ->where('id', 1)->first();

        return view('managers.working-hours', compact('guides', 'updatedate', 'currentweek'))
            ->with('dateFormat', 'd.m.Y');
    }

 

    public function managerGuideReport()
    {
        // Get guides assigned to the current manager
        $assignedGuideIds = ManagerGuideAssignment::where('manager_id', auth()->id())
            ->pluck('guide_id')
            ->toArray();
        
        // Get only assigned guides
        $tourGuides = TourGuide::whereIn('id', $assignedGuideIds)
            ->orderBy('name', 'asc')
            ->get();
        
        return view('reports.guide-wise-report-create', compact('tourGuides'));
    }

    public function getManagerGuides($managerId)
        {
            $guides = ManagerGuideAssignment::where('manager_id', $managerId)
                ->with('guide:id,name')
                ->get()
                ->pluck('guide');

            return response()->json(['guides' => $guides]);
        }

    public function assignGuidesToManagers()
    {
        $tourGuides = TourGuide::orderBy('name', 'asc')->get();
        $managers = User::where('role', 'team-lead')->orderBy('name', 'asc')->get();
        
        // Get current assignments
        $currentAssignments = ManagerGuideAssignment::with(['manager', 'guide'])
            ->get()
            ->groupBy('manager_id');

        return view('managers.assign-guides-to-managers', 
            compact('tourGuides', 'managers', 'currentAssignments')
        );
    }

    public function assignGuidesToManagersStore(Request $request)
    {
        $request->validate([
            'manager_id' => 'required|exists:users,id',
            'guide_ids' => 'required|string'
        ]);

        try {
            // Begin transaction
            DB::beginTransaction();

            // Convert comma-separated string to array and filter out empty values
            $guideIds = array_filter(explode(',', $request->guide_ids));

            // Validate that all guide IDs exist
            $validGuideIds = TourGuide::whereIn('id', $guideIds)->pluck('id')->toArray();
            if (count($validGuideIds) !== count($guideIds)) {
                throw new \Exception('Invalid guide ID detected');
            }

            // Remove existing assignments for this manager
            ManagerGuideAssignment::where('manager_id', $request->manager_id)->delete();

            // Create new assignments
            foreach ($guideIds as $guideId) {
                ManagerGuideAssignment::create([
                    'manager_id' => $request->manager_id,
                    'guide_id' => $guideId
                ]);
            }

            DB::commit();
            return redirect()->back()->with('success', 'Guides assigned successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('failed', 'Failed to assign guides: ' . $e->getMessage());
        }
    }
}
