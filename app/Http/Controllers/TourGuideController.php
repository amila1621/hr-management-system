<?php

namespace App\Http\Controllers;

use App\Models\EventSalary;
use App\Models\Holiday;
use App\Models\TourGuide;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\Event;
use App\Models\StaffUser;
use Illuminate\Support\Facades\Storage;
use App\Models\SickLeave;
use App\Models\Departments;
use App\Models\Announcements;

class TourGuideController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $tourGuides = TourGuide::all();
        return view('tour_guides.index', compact('tourGuides'));
    }

    public function makeGuideStaff(Request $request)
    {

        $guideId = $request->input('tour_guide_id');
        $departmentId = $request->input('department_id');

        $guide = TourGuide::findOrFail($guideId);
        $department = Departments::findOrFail($departmentId)->department;



        $staff = new StaffUser();
        $staff->full_name = $guide->full_name; 
        $staff->name = $guide->name; 
        $staff->email = $guide->email; 
        $staff->department = $department; 
        $staff->phone_number = $guide->phone_number; 
        $staff->rate = $guide->rate; 
        $staff->user_id = $guide->user_id; 
        $staff->color = "#".substr(md5(rand()), 0, 6);
        $staff->allow_report_hours = $guide->allow_report_hours;

        $staff->save();


        $user = User::findOrFail($guide->user_id);
        $user->role = 'guide/staff'; // Change the role to 'staff'
        $user->save();

        return redirect()->route('tour-guides.index')->with('success', 'Tour Guide has been made a Staff User successfully.');
    
    }

    public function staffIndex()
    {
        $staffUsers = StaffUser::where('is_supervisor',0)->get();


        return view('tour_guides.staff-index', compact('staffUsers'));
    }

    public function operationsIndex()
    {
        $operationsUsers = User::where('role', 'operation')->get();
        return view('tour_guides.operations-index', compact('operationsUsers'));
    }

    public function supervisorsIndex()
    {
        $supervisors = User::where('role', 'supervisor')->get();
        return view('tour_guides.supervisors-index', compact('supervisors'));
    }

    public function amSupervisorsIndex()
    {
        $supervisors = User::where('role', 'supervisor')->get();
        return view('tour_guides.am-supervisors-index', compact('supervisors'));
    }

    public function teamLeadsIndex()
    {
        $teamLeads = User::where('role', 'team-lead')->with('teamLead')->get();

        return view('tour_guides.team-leads-index', compact('teamLeads'));
    }

    public function hrAssistantsIndex()
    {
        $hrAssistants = User::where('role', 'hr-assistant')->with('hrAssistant')->get();
        return view('tour_guides.hr-assistants-index', compact('hrAssistants'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('tour_guides.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone_number' => 'nullable|string|max:15',
            'rate' => 'nullable|string',
            'password' => ['required', 'confirmed'],
            'allow_report_hours' => 'required|boolean',
        ]);

        // Create the user with the role of 'guide'
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'guide', // Set role as 'guide'
        ]);

        // Create the tour guide, linking it to the user
        TourGuide::create([
            'user_id' => $user->id,
            'name' => $validatedData['name'],    // Keep name here
            'email' => $validatedData['email'],  // Keep email here
            'phone_number' => $validatedData['phone_number'],
            'rate' => $validatedData['rate'],
            'allow_report_hours' => $validatedData['allow_report_hours'],
        ]);

        return redirect()->route('tour-guides.index')->with('success', 'Tour Guide created successfully.');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        return view('tour_guides.show', compact('tourGuide'));
    }


    public function changePassword(Request $request, $id)
    {
        $validatedData = $request->validate([
            'password' => 'required|string|min:4|confirmed',
        ]);

        $tourGuide = TourGuide::findOrFail($id);

        if ($tourGuide->user) {
            $tourGuide->user->update([
                'password' => Hash::make($validatedData['password']),
            ]);

            return redirect()->back()->with('success', 'Password changed successfully.');
        } else {
            return redirect()->back()->with('error', 'Associated user not found for this tour guide.');
        }
    }


    public function hide($id)
    {
        $tourGuide = TourGuide::findOrFail($id);
        $tourGuide->update(['is_hidden' => true]);
        return redirect()->back()->with('success', 'Tour Guide hidden successfully.');
    }

    public function unhide($id)
    {
        $tourGuide = TourGuide::findOrFail($id);
        $tourGuide->update(['is_hidden' => false]);
        return redirect()->back()->with('success', 'Tour Guide unhidden successfully.');
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $tourGuide = TourGuide::findOrFail($id);
        $supervisors = User::whereIn('role', ['supervisor', 'operation'])->get();
        return view('tour_guides.edit', compact('tourGuide', 'supervisors'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, TourGuide $tourGuide)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'full_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $tourGuide->user->id,
            'phone_number' => 'nullable|string|max:15',
            'rate' => 'nullable|string',
            'allow_report_hours' => 'required|boolean',
            'is_intern' => 'required|boolean',
            'supervisor' => 'required|exists:users,id', 
        ]);

        // Update the user
        $tourGuide->user->update([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'is_intern' => $validatedData['is_intern'],
        ]);

        // Update the tour guide
        $tourGuide->update([
            'name' => $validatedData['name'],
            'full_name' => $validatedData['full_name'],
            'email' => $validatedData['email'],
            'phone_number' => $validatedData['phone_number'],
            'rate' => $validatedData['rate'],
            'allow_report_hours' => $validatedData['allow_report_hours'],
            'supervisor' => $validatedData['supervisor'],
        ]);

        return redirect()->back()->with('success', 'Tour Guide updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(TourGuide $tourGuide)
    {
        // Store the user_id before deleting the tour guide
        $userId = $tourGuide->user_id;
        
        // Delete the tour guide record
        $tourGuide->delete();
        
        // Delete the associated user record if it exists
        if ($userId) {
            $user = User::find($userId);
            if ($user) {
                $user->delete();
            }
        }
        
        return redirect()->route('tour-guides.index')->with('success', 'Tour Guide deleted successfully.');
    }
  
  
    public function Terminate(Request $request)
    {
        $tourGuide = TourGuide::findOrFail($request->id);
        $userId = $tourGuide->user_id;

        $userId = $tourGuide->user_id;
        $user = User::find($userId);

        if ($user) {
            $user->password = Hash::make('shalin@123');
            $user->save();
        }

        $tourGuide->delete();
        return redirect()->route('tour-guides.index')->with('success', 'Tour Guide marked as terminated successfully.');
    }


    public function workReport(Request $request)
    {
        // Fetch the currently authenticated user's ID
        $userId = Auth::id();

        $userType = Auth::user()->role;

        // Get the latest announcement  
        $latestAnnouncement = Announcements::latest()->first();
        $displayAnnouncement = false;

        if($userType == 'guide'){
            
            if ($latestAnnouncement) {
                // Check if the current user has acknowledged this announcement
                $hasAcknowledged = $latestAnnouncement->acknowledgedBy()
                    ->where('user_id', $userId)
                    ->exists();
                    
                if(!$hasAcknowledged){
                    $displayAnnouncement = true;
                }
            }
        }
        // Fetch the corresponding guide_id from the tour_guides table
        $tourGuide = TourGuide::where('user_id', $userId)->firstOrFail();

        // Set the start and end dates to the last 30 days by default
        $startDate = $request->input('start_date')
            ? \Carbon\Carbon::parse($request->input('start_date'))->startOfDay()
            : \Carbon\Carbon::now()->startOfMonth();

        $endDate = $request->input('end_date')
            ? \Carbon\Carbon::parse($request->input('end_date'))->endOfDay()
            : \Carbon\Carbon::now()->endOfMonth();

    // Get event salaries
    $eventSalaries = EventSalary::where('guideId', $tourGuide->id)
        ->whereHas('event', function ($query) use ($startDate, $endDate) {
            $query->whereBetween('start_time', [$startDate, $endDate]);
        })
        ->with('event')
        ->get()
        ->map(function($salary) {
            return [
                'id' => $salary->id,
                'type' => 'event',
                'date' => $salary->guide_start_time,
                'tour_name' => $salary->event->name,
                'start_time' => $salary->guide_start_time,
                'guide_start_time' => $salary->guide_start_time,
                'end_time' => $salary->guide_end_time,
                'guide_end_time' => $salary->guide_end_time,
                'normal_hours' => $salary->normal_hours,
                'holiday_hours' => $salary->holiday_hours,
                'normal_night_hours' => $salary->normal_night_hours,
                'holiday_night_hours' => $salary->holiday_night_hours,
                'approval_status' => $salary->approval_status,
                'approval_comment' => $salary->approval_comment,
                'guide_comment' => $salary->guide_comment,
                'is_chore' => $salary->is_chore,
                'is_guide_updated' => $salary->is_guide_updated
            ];
        });

    // Get sick leaves
    $sickLeaves = SickLeave::where('guide_id', $tourGuide->id)
        ->whereBetween('date', [$startDate, $endDate])
        ->get()
        ->map(function($leave) {
            return [
                'id' => $leave->id,
                'type' => 'sick_leave',
                'tour_name' => $leave->tour_name,
                'date' => $leave->date,
                'start_time' => $leave->start_time,
                'end_time' => $leave->end_time,
                'normal_hours' => $leave->normal_hours,
                'holiday_hours' => $leave->holiday_hours,
                'normal_night_hours' => $leave->normal_night_hours,
                'holiday_night_hours' => $leave->holiday_night_hours
            ];
        });

    // Combine and sort collections
    $combinedData = $eventSalaries->concat($sickLeaves)
        ->sortBy(function($record) {
            return $record['type'] == 'event' 
                ? Carbon::parse($record['start_time'])->timestamp 
                : Carbon::parse($record['date'])->timestamp;
        })
        ->values();


    return view('guides.guide-wise-report', compact(
        'tourGuide', 
        'startDate', 
        'endDate', 
        'combinedData',
        'displayAnnouncement',
        'latestAnnouncement'
    ));}

    public function reportHours()
    {
        return view('guides.report-hours');
    }

    public function reportHoursStore(Request $request)
    {
        // Validate the incoming request
        $validatedData = $request->validate([
            'tourDate' => 'required|date',
            'tourName' => 'required|string|max:255',
            'startTime' => 'required|date_format:Y-m-d H:i',
            'endTime' => 'required|date_format:Y-m-d H:i|after:startTime',
        ]);

        // Get the currently authenticated user (guide)
        $userName = Auth::user()->name;
        $tourGuide = TourGuide::where('name', $userName)->first();

        if (!$tourGuide) {
            return redirect()->back()->with('error', 'Tour guide profile not found for the current user.');
        }

        // Create a new Event
        $event = new Event();
        $event->name = $validatedData['tourName'];
        $event->start_time = Carbon::parse($validatedData['startTime']);
        $event->end_time = Carbon::parse($validatedData['endTime']);
        $event->event_id = 'manual-guide-'. substr(md5(uniqid(rand(), true)), 0, 8);
        $event->status = 1;
        // Add any other necessary fields for the Event model
        $event->save();

        // Create a new EventSalary entry
        $eventSalary = new EventSalary();
        $eventSalary->eventId = $event->id;
        $eventSalary->guideId = $tourGuide->id;
        $eventSalary->guide_start_time = $event->start_time;
        $eventSalary->guide_end_time = $event->end_time;
        $eventSalary->is_guide_updated = true;
        $eventSalary->is_chore = true;
        $eventSalary->save();

        return redirect()->route('guide.report-hours')->with('success', 'Hours reported successfully.');
    }

    public function updateHours(Request $request, $id)
    {
        $request->validate([
            'guide_start_time' => 'required|date_format:d.m.Y H:i',
            'guide_end_time' => 'required|date_format:d.m.Y H:i|after:guide_start_time',
            'guide_comment' => 'nullable|string',
            'guide_image' => 'nullable|image|max:2048', // max 2MB
        ]);

        $eventSalary = EventSalary::findOrFail($id);

        $data = [
            'guide_start_time' => Carbon::createFromFormat('d.m.Y H:i', $request->guide_start_time),
            'guide_end_time' => Carbon::createFromFormat('d.m.Y H:i', $request->guide_end_time),
            'guide_comment' => $request->guide_comment ?? '',
            'is_guide_updated' => 1,
            'approval_comment' => '',
        ];

        if ($request->hasFile('guide_image')) {
            // Delete old image if exists
            if ($eventSalary->guide_image) {
                Storage::delete($eventSalary->guide_image);
            }

            // Store new image
            $path = $request->file('guide_image')->store('guide_images', 'public');
            $data['guide_image'] = $path;
        }

        $eventSalary->update($data);

        return response()->json(['message' => 'Hours updated successfully']);
    }

    public function updateHoursByGuides(Request $request, $id)
    {
        $request->validate([
            'guide_times.*.start' => 'required|date_format:d.m.Y H:i',
            'guide_times.*.end' => 'required|date_format:d.m.Y H:i',
            'guide_comment' => 'nullable|string',
            'guide_image' => 'nullable|image|max:2048', // max 2MB
        ]);

        $eventSalary = EventSalary::findOrFail($id);
        $event = $eventSalary->event;

        // Delete existing entries for this event and guide
        EventSalary::where('eventId', $event->id)
            ->where('guideId', $eventSalary->guideId)
            ->delete();

        // Create new entries for each time slot
        foreach ($request->guide_times as $time) {
            $data = [
                'eventId' => $event->id,
                'guideId' => $eventSalary->guideId,
                'guide_start_time' => Carbon::createFromFormat('d.m.Y H:i', $time['start']),
                'guide_end_time' => Carbon::createFromFormat('d.m.Y H:i', $time['end']),
                'guide_comment' => $request->guide_comment ?? '',
                'is_guide_updated' => 1,
                'approval_comment' => '',
                'is_chore' => $eventSalary->is_chore ?? false,
            ];

            // Only store the image for the first entry
            if ($request->hasFile('guide_image') && !isset($imageStored)) {
                if ($eventSalary->guide_image) {
                    Storage::delete($eventSalary->guide_image);
                }
                $path = $request->file('guide_image')->store('guide_images', 'public');
                $data['guide_image'] = $path;
                $imageStored = true;
            }

            EventSalary::create($data);
        }

        return response()->json(['message' => 'Hours updated successfully']);
    }
}
