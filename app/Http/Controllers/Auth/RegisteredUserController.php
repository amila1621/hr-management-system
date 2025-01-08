<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\HrAssistants;
use App\Models\Operations;
use App\Models\StaffUser;
use App\Models\Supervisors;
use App\Models\TeamLeads;
use App\Models\TourGuide;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(RouteServiceProvider::HOME);
    }
    public function newuser() {
        if (Auth::user()->role=="admin" || Auth::user()->role=="supervisor") {
            $supervisors = User::whereIn('role', ['supervisor', 'operation'])->get();
            return view('auth.new-user', compact('supervisors'));
        } else {
            return redirect()->back()->with('error', 'You do not have permission to access this page.');
        }
    }
    
    public function newuserstore(Request $request) {
        // Validate the initial request data
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'full_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'confirmed'],
            'role' => 'required|string',
            'is_intern' => 'required|boolean',
        ]);
    
        // Additional validation for 'guide' role
        if ($request->role == 'guide') {
            $validatedData = array_merge($validatedData, $request->validate([
                'phone_number' => 'required|string|max:15',
                'rate' => 'required|string',
                'allow_report_hours' => 'required|boolean',
                'supervisor' => 'required|exists:users,id', 
                'full_name' => 'required|string|max:255', 
            ]));
        }
        // Additional validation for 'staff' role
        if ($request->role == 'staff') {
            $validatedData = array_merge($validatedData, $request->validate([
                'phone_number' => 'required|string|max:15',
                'rate' => 'required|string',
                'allow_report_hours' => 'required|boolean',
                'supervisor' => 'required|exists:users,id', 
                'color' => 'nullable|string|unique:staff_users,color', 
                'full_name' => 'required|string|max:255', 
            ]));
        }

        if ($request->role == 'hr-assistant') {
            $validatedData = array_merge($validatedData, $request->validate([
                'phone_number' => 'required|string|max:15',
                'rate' => 'required|string',
                'full_name' => 'required|string|max:255', 
                'color' => 'nullable|string|unique:hr_assistant,color', 
            ]));
        }

        if ($request->role == 'team-lead') {
            $validatedData = array_merge($validatedData, $request->validate([
                'phone_number' => 'required|string|max:15',
                'rate' => 'required|string',
                'full_name' => 'required|string|max:255', 
                'color' => 'nullable|string|unique:hr_assistant,color', 
            ]));
        }

        if ($request->role == 'operation') {
            $validatedData = array_merge($validatedData, $request->validate([
                'phone_number' => 'required|string|max:15',
                'rate' => 'required|string',
                'full_name' => 'required|string|max:255', 
                'color' => 'nullable|string|unique:hr_assistant,color', 
            ]));
        }

        if ($request->role == 'supervisor') {
            $validatedData = array_merge($validatedData, $request->validate([
                'phone_number' => 'required|string|max:15',
                'rate' => 'required|string',
                'full_name' => 'required|string|max:255', 
                'color' => 'nullable|string|unique:hr_assistant,color', 
                'display_midnight_phone' => 'required|boolean',
            ]));
        }
    
        // dd($validatedData['role']);
        // Create the user
        $user = User::create([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'password' => Hash::make($validatedData['password']),
            'role' => $validatedData['role'], // Role set from validated data
            'is_intern' => $validatedData['is_intern'],
        ]);
    
        // If the user is a guide, create the tour guide record
        if ($validatedData['role'] === 'guide') {
            TourGuide::create([
                'user_id' => $user->id,
                'name' => $validatedData['name'],
                'full_name' => $validatedData['full_name'],
                'email' => $validatedData['email'],
                'phone_number' => $validatedData['phone_number'],
                'rate' => $validatedData['rate'],
                'allow_report_hours' => $validatedData['allow_report_hours'],
                'supervisor' => $validatedData['supervisor'],
            ]);
        }
        // If the user is a staff, create the staff record
        if ($validatedData['role'] === 'staff') {
            StaffUser::create([
                'user_id' => $user->id,
                'name' => $validatedData['name'],
                'full_name' => $validatedData['full_name'],
                'email' => $validatedData['email'],
                'phone_number' => $validatedData['phone_number'],
                'rate' => $validatedData['rate'],
                'allow_report_hours' => $validatedData['allow_report_hours'],
                'supervisor' => $validatedData['supervisor'],
                'color' => $validatedData['color'],
            ]);
        }

        if ($validatedData['role'] === 'hr-assistant') {
            HrAssistants::create([
                'user_id' => $user->id,
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'phone_number' => $validatedData['phone_number'],
                'rate' => $validatedData['rate'],
                'color' => $validatedData['color'],
            ]);
        }

        if ($validatedData['role'] === 'team-lead') {
            TeamLeads::create([
                'user_id' => $user->id,
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'phone_number' => $validatedData['phone_number'],
                'rate' => $validatedData['rate'],
                'color' => $validatedData['color'],
            ]);
        }

        if ($validatedData['role'] === 'operation') {
            Operations::create([
                'user_id' => $user->id,
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'phone_number' => $validatedData['phone_number'],
                'rate' => $validatedData['rate'],
                'color' => $validatedData['color'],
            ]);
        }

        if ($validatedData['role'] === 'supervisor') {
            Supervisors::create([
                'user_id' => $user->id,
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'phone_number' => $validatedData['phone_number'],
                'rate' => $validatedData['rate'],
                'color' => $validatedData['color'],
                'display_midnight_phone' => $validatedData['display_midnight_phone'],
            ]);
        }
    
        return redirect()->back()->with('success', 'User created successfully.');
    }
    
    public function addsupervisor(Request $request)  {
        //  dd($request->tour_guide_id);
      $user=  TourGuide::where('id',$request->tour_guide_id)->first();
    //   dd( $user);
      $user->supervisor=$request->supervisor_id;
      $user->save();
      return redirect()->back()->with('success', ' Supervisor added successfully');
    }
        
        
}
