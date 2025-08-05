<?php

namespace App\Http\Controllers;

use App\Models\Holiday;
use App\Models\SickLeave;
use App\Models\StaffUser;
use App\Models\SupervisorSickLeaves;
use App\Models\TourGuide;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SickLeaveController extends Controller
{

    public function index()
    {
        $sickLeaves = SickLeave::orderBy('date', 'desc')->get();
        $guides = TourGuide::orderBy('name', 'asc')->get();
        return view('sick_leave.index', compact('sickLeaves', 'guides'));
    }

    public function createNewSickLeaveTour()
    {
        $guides = TourGuide::orderBy('name', 'asc')->get();
        return view('sick_leave.create', compact('guides'));
    }

    public function destroy($id)
    {
        $sickLeave = SickLeave::findOrFail($id);
        $sickLeave->delete();
        return redirect()->back()->with('success', 'Sick leave deleted successfully');
    }


    public function sickLeaveUpdate(Request $request)
    {

        $sickLeave = SickLeave::findOrFail($request->sick_leave_id);

        $startDateTime = Carbon::parse($request->start_time);
        $endDateTime = Carbon::parse($request->end_time);

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

        // Update sick leave with new values
        $sickLeave->update([
            'guide_id' => $request->guide_id,
            'tour_name' => $request->tour_name,
            'date' => Carbon::parse($request->start_time)->toDateString(),
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'normal_hours' => $normal_hours,
            'normal_night_hours' => $normal_night_hours,
            'holiday_hours' => $holiday_hours,
            'holiday_night_hours' => $holiday_night_hours,
            'updated_by' => auth()->user()->id
        ]);

        return redirect()->back()->with('success', 'Sick leave updated successfully');
    }

    public function sickLeaveStoreManual(Request $request)
    {

        $guide = TourGuide::findOrFail($request->guide_id);

        $startDateTime = Carbon::parse($request->start_time);
        $endDateTime = Carbon::parse($request->end_time);

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

        SickLeave::create([
            'guide_id' => $request->guide_id,
            'guide_name' => $guide->name,
            'tour_name' => $request->tour_name,
            'date' => Carbon::parse($request->start_time)->toDateString(),
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'normal_hours' => $normal_hours,
            'normal_night_hours' => $normal_night_hours,
            'holiday_hours' => $holiday_hours,
            'holiday_night_hours' => $holiday_night_hours,
            'created_by' => auth()->user()->id
        ]);




        return redirect()->back()->with('success', 'Missing Hours Added Successfully');
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


    private function isHoliday(Carbon $date)
    {
        return $date->isSunday() || Holiday::where('holiday_date', $date->format('Y-m-d'))->exists();
    }

    public function requestSickLeaves()
    {
        return view('supervisor-sick-leaves.request-staff');
    }

    public function requestSickLeavesStore(Request $request)
    {
        // Validate the request data
        $validated = $request->validate([
            'start_time' => 'required|date_format:Y-m-d',
            'description' => 'required|string|max:500',
            'end_time' => 'required|date_format:Y-m-d|after_or_equal:start_time',
            'image' => 'required|file|mimes:jpeg,png,jpg,pdf',
        ]);

        try {
            // Get the authenticated user (staff member)
            $staff = StaffUser::where('user_id', auth()->id())->firstOrFail();

            // Handle file upload
            $imagePath = null;
            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $filename = time() . '_' . $file->getClientOriginalName();
                $imagePath = $file->storeAs('prescriptions', $filename, 'public');
            }

            // Get the supervisor for this staff member's department
            $supervisorId =  null;

            // Create the sick leave record
            $sickLeave = SupervisorSickLeaves::create([
                'staff_id' => $staff->id,
                'start_date' => Carbon::parse($request->start_time)->format('Y-m-d'),
                'description' => $request->description,
                'department' => $staff->department,
                'end_date' => Carbon::parse($request->end_time)->format('Y-m-d'),
                'supervisor_id' => $supervisorId,
                'image' => $imagePath,
                'status' => 0, // 0 = pending from supervisor
                'supervisor_remark' => null,
                'admin_id' => null,
                'admin_remark' => null,
            ]);

            return redirect()->back()->with('success', 'Sick leave request submitted successfully. Your request is pending supervisor approval.');
        } catch (\Exception $e) {
            \Log::error('Error creating sick leave request: ' . $e->getMessage());
            return redirect()->back()->with('failed', 'Failed to submit sick leave request: ' . $e->getMessage())->withInput();
        }
    }

    public function manageSickLeaves()
    {
        $staffId = StaffUser::where('user_id', auth()->user()->id)->first()->id;
        $sickLeaves = SupervisorSickLeaves::where('staff_id', $staffId)->orderBy('created_at', 'desc')->get();

        return view('supervisor-sick-leaves.requested-sickleave-list', compact('sickLeaves'));
    }

    public function approveSickLeave($id, Request $request)
    {
        try {
            $sickLeave = SupervisorSickLeaves::findOrFail($id);

            // Check if the user is authorized (must be a supervisor)
            if (auth()->user()->role !== 'supervisor') {
                return response()->json(['message' => 'You are not authorized to approve sick leaves'], 403);
            }

            // Update the sick leave status to approved by supervisor (1)
            $sickLeave->update([
                'status' => 1, // Pending admin approval
                'supervisor_id' => auth()->id(),
                'supervisor_remark' => $request->supervisor_remark // Add the supervisor remark
            ]);

            return response()->json(['message' => 'Sick leave approved successfully']);
        } catch (\Exception $e) {
            \Log::error('Error approving sick leave: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to approve sick leave: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Reject a sick leave request
     * 
     * @param int $id
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function rejectSickLeave($id, Request $request)
    {
        try {
            $sickLeave = SupervisorSickLeaves::findOrFail($id);

            // Validate request
            $request->validate([
                'supervisor_remark' => 'required|string',
            ]);

            // Check if the user is authorized (must be a supervisor)
            if (auth()->user()->role !== 'supervisor') {
                return response()->json(['message' => 'You are not authorized to reject sick leaves'], 403);
            }

            // Update the sick leave status to rejected (3)
            $sickLeave->update([
                'status' => 3, // Rejected
                'supervisor_id' => auth()->id(),
                'supervisor_remark' => $request->supervisor_remark,
            ]);

            return response()->json(['message' => 'Sick leave rejected successfully']);
        } catch (\Exception $e) {
            \Log::error('Error rejecting sick leave: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to reject sick leave: ' . $e->getMessage()], 500);
        }
    }


    public function cancelSickLeave($id)
    {
        try {
            $sickLeave = SupervisorSickLeaves::findOrFail($id);

            // Check if the user is authorized (must be a supervisor or admin)
            if (auth()->user()->role !== 'supervisor' && auth()->user()->role !== 'admin') {
                return response()->json(['message' => 'You are not authorized to cancel sick leaves'], 403);
            }

            // Update the sick leave status to cancelled (4)
            $sickLeave->update([
                'status' => 4, // Cancelled
            ]);

            return response()->json(['message' => 'Sick leave cancelled successfully']);
        } catch (\Exception $e) {
            \Log::error('Error cancelling sick leave: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to cancel sick leave: ' . $e->getMessage()], 500);
        }
    }


    public function adminApprove($id, Request $request)
    {
        try {
            $sickLeave = SupervisorSickLeaves::findOrFail($id);

            // Check if the user is authorized (must be an admin)
            if (auth()->user()->role !== 'admin') {
                return response()->json(['message' => 'You are not authorized to approve sick leaves as HR'], 403);
            }

            // Validate that the sick leave is in the correct status for HR approval
            // if ($sickLeave->status != 1) {
            //     return response()->json([
            //         'message' => 'This sick leave request cannot be approved by HR. It must first be approved by a supervisor.'
            //     ], 400);
            // }

            // Update the sick leave status to fully approved (2)
            $sickLeave->update([
                'status' => 2, // Fully approved
                'admin_id' => auth()->id(),
                'admin_remark' => $request->admin_remark,
                'approved_at' => now(),
            ]);

            // Log this action
            \Log::info('Sick leave #' . $id . ' approved by HR ' . auth()->user()->name);

            $this->updateStaffHoursForSickLeave($sickLeave);

            return response()->json(['message' => 'Sick leave approved successfully by HR']);
        } catch (\Exception $e) {
            \Log::error('Error approving sick leave by HR: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to approve sick leave: ' . $e->getMessage()], 500);
        }
    }


    public function adminReject($id, Request $request)
    {
        try {
            $sickLeave = SupervisorSickLeaves::findOrFail($id);

            // Validate request
            $request->validate([
                'admin_remark' => 'required|string',
            ]);

            // Check if the user is authorized (must be an admin)
            if (auth()->user()->role !== 'admin') {
                return response()->json(['message' => 'You are not authorized to reject sick leaves as HR'], 403);
            }

            // Validate that the sick leave is in the correct status for HR rejection
            if ($sickLeave->status != 1) {
                return response()->json([
                    'message' => 'This sick leave request cannot be rejected by HR. It must first be approved by a supervisor.'
                ], 400);
            }

            // Update the sick leave status to rejected (3)
            $sickLeave->update([
                'status' => 3, // Rejected
                'admin_id' => auth()->id(),
                'admin_remark' => $request->admin_remark,
            ]);

            // Log this action
            \Log::info('Sick leave #' . $id . ' rejected by HR ' . auth()->user()->name);

          

            return response()->json(['message' => 'Sick leave rejected successfully by HR']);
        } catch (\Exception $e) {
            \Log::error('Error rejecting sick leave by HR: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to reject sick leave: ' . $e->getMessage()], 500);
        }
    }


    private function updateStaffHoursForSickLeave($sickLeave)
    {
        try {
            // Create a date range from start_date to end_date
            $startDate = Carbon::parse($sickLeave->start_date);
            $endDate = Carbon::parse($sickLeave->end_date ?? $sickLeave->start_date); // Handle null end_date
            $dateRange = [];
            
            // Generate an array of dates
            for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
                $dateRange[] = $date->format('Y-m-d');
            }
            
            \Log::info('Updating staff hours for sick leave', [
                'sick_leave_id' => $sickLeave->id,
                'staff_id' => $sickLeave->staff_id,
                'date_range' => $dateRange
            ]);
            
            // Find all staff_monthly_hours records for this staff member and dates
            $staffHours = \App\Models\StaffMonthlyHours::where('staff_id', $sickLeave->staff_id)
                ->whereIn(DB::raw('DATE(date)'), $dateRange)
                ->get();
            
            \Log::info('Found ' . $staffHours->count() . ' staff hours records to update');
            
            foreach ($staffHours as $staffHour) {
                // Get hours_data - make sure we're working with the actual data
                $hoursData = $staffHour->hours_data;
                
                // If it's already decoded by Laravel's attribute casting, use it directly
                // If not, decode it
                if (is_string($hoursData)) {
                    $hoursData = json_decode($hoursData, true);
                }
                
                // If decoding failed or it's not an array, log and continue
                if (!is_array($hoursData)) {
                    \Log::error('Invalid hours_data format in record #' . $staffHour->id, [
                        'hours_data' => $staffHour->hours_data
                    ]);
                    continue;
                }
                
                $modified = false;
                
                // Loop through each shift in hours_data
                foreach ($hoursData as $key => $shift) {
                    // If type is "normal", change to "SL"
                    if (isset($shift['type']) && $shift['type'] === 'normal') {
                        $hoursData[$key]['type'] = 'SL';
                        // Preserve original times if not already present
                        if (!isset($shift['original_start_time'])) {
                            $hoursData[$key]['original_start_time'] = $shift['start_time'];
                        }
                        if (!isset($shift['original_end_time'])) {
                            $hoursData[$key]['original_end_time'] = $shift['end_time'];
                        }
                        $modified = true;
                    }
                }
                
                // Update the record if modified - DIRECTLY assign the array
                if ($modified) {
                    // Use the array directly - Laravel will handle the JSON encoding
                    $staffHour->hours_data = $hoursData;
                    $staffHour->save();
                    
                    \Log::info('Updated staff hours record #' . $staffHour->id . ' for sick leave approval', [
                        'staff_id' => $sickLeave->staff_id,
                        'date' => $staffHour->date,
                        'new_hours_data' => $hoursData
                    ]);
                } else {
                    \Log::info('No changes needed for staff hours record #' . $staffHour->id);
                }
            }
        } catch (\Exception $e) {
            \Log::error('Error updating staff monthly hours for sick leave: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            // Don't throw the exception to prevent it from affecting the approval process
        }
    }
}
