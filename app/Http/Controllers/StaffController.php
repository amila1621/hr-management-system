<?php

namespace App\Http\Controllers;

use App\Models\Holiday;
use App\Models\HrAssistants;
use App\Models\StaffMonthlyHours;
use App\Models\StaffUser;
use App\Models\StaffHoursDetails;
use App\Models\Supervisors;
use App\Models\TeamLeads;
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
        
        $currentStaffMember = StaffUser::where('user_id', Auth::user()->id)->first();
        
        if (!$currentStaffMember) {
            return redirect()->back()->with('error', 'Staff member not found.');
        }

        $staffMembers = StaffUser::where('supervisor', $currentStaffMember->supervisor)->get();
        $displayMidnightPhone = Supervisors::where('user_id', $currentStaffMember->supervisor)->first()->display_midnight_phone;

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

            // Fetch staff hours for all staff members
            foreach ($staffMembers as $staffMember) {
                $monthlyHours = StaffMonthlyHours::where('staff_id', $staffMember->id)
                    ->where('date', $dateString)
                    ->first();

                $staffHours[$staffMember->id][$dateString] = $monthlyHours ? $monthlyHours->hours_data : [];
            }

            // Fetch reception and midnight phone data
            $hoursDetails = StaffHoursDetails::where('date', $dateString)->first();
            if ($hoursDetails) {
                $receptionData[$dateString] = $hoursDetails->reception;
                $midnightPhoneData[$dateString] = $hoursDetails->midnight_phone[0] ?? null;
            } else {
                $receptionData[$dateString] = '';
                $midnightPhoneData[$dateString] = null;
            }
        }

        return view('staffs.dashboard', compact('currentStaffMember', 'staffMembers', 'staffHours', 'receptionData', 'midnightPhoneData', 'selectedMonth', 'daysInMonth', 'holidays', 'displayMidnightPhone'));
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

    public function destroy($id)
    {
        $staffUser = StaffUser::findOrFail($id);
        $staffUser->delete();
        return redirect()->route('tour-guides.staff-index')->with('success', 'Staff user deleted successfully.');
    }

    public function edit($id)
    {

        $staffUser = StaffUser::findOrFail($id);
        $supervisors = User::where('role', 'supervisor')->get();
        return view('staffs.edit', compact('staffUser','supervisors'));
    }

    public function update(Request $request, $id)
    {
        $staffUser = StaffUser::findOrFail($id);
        
        // Update staff user
        $staffUser->update($request->all());
        
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
        return redirect()->back()->with('success', 'Hr assistant deleted successfully.');
    }

    public function teamLeadsDestroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        $teamLead = TeamLeads::where('user_id', $id)->first();
        $teamLead->delete();
        return redirect()->back()->with('success', 'Team lead deleted successfully.');
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
        return view('users.supervisors-edit', compact('supervisor'));
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
        return redirect()->back()->with('success', 'Hr assistant updated successfully.');
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
        return redirect()->back()->with('success', 'Team lead updated successfully.');
    }

    public function supervisorsUpdate(Request $request, $id)
    {

        $supervisor = Supervisors::findOrFail($id);
        $supervisor->update($request->all());

        $supervisor->update([
            'display_midnight_phone' => $request->input('display_midnight_phone', 0),
        ]);

        $supervisor->user->update([
            'is_intern' => $request->input('is_intern', 0),
            'name' => $request->input('name'),
            'email' => $request->input('email'),
        ]);
        return redirect()->back()->with('success', 'Supervisor updated successfully.');
    }
}
