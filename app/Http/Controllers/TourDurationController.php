<?php

namespace App\Http\Controllers;

use App\Models\SaunaTourDurations;
use App\Models\TourDuration;
use DB;
use Illuminate\Http\Request;

class TourDurationController extends Controller
{
    public function index(){
        $tourDurations = TourDuration::orderBy('tour','asc')->get();
        return view('tour_duration.index',compact('tourDurations'));
    }
    public function indexSauna(){
        $tourDurations = SaunaTourDurations::orderBy('tour','asc')->get();
        return view('tour_duration.sauna-index',compact('tourDurations'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'tour' => 'required|string|max:255',
            'duration' => 'required|regex:/^\d+\.\d{2}$/',
        ]);

        $durationParts = explode('.', $request->duration);
        $hours = intval($durationParts[0]);
        $minutes = intval($durationParts[1]);
        $totalMinutes = ($hours * 60) + $minutes;

        TourDuration::create([
            'tour' => $request->tour,
            'duration' => $totalMinutes,
        ]);

        return redirect()->route('tour-durations.index')->with('success', 'Tour duration added successfully.');
    }

    public function storeSauna(Request $request)
    {
        $request->validate([
            'tour' => 'required|string|max:255',
            'duration' => 'required|regex:/^\d+\.\d{2}$/',
        ]);

        $durationParts = explode('.', $request->duration);
        $hours = intval($durationParts[0]);
        $minutes = intval($durationParts[1]);
        $totalMinutes = ($hours * 60) + $minutes;

        SaunaTourDurations::create([
            'tour' => $request->tour,
            'duration' => $totalMinutes,
        ]);

        return redirect()->back()->with('success', 'Sauna Tour duration added successfully.');
    }

    public function edit($id)
    {
        $tourDuration = TourDuration::findOrFail($id);
        return view('tour_duration.edit', compact('tourDuration'));
    }
    public function editSauna($id)
    {
        $tourDuration = SaunaTourDurations::findOrFail($id);
        return view('tour_duration.sauna-edit', compact('tourDuration'));
    }

    public function update(Request $request, $id)
    {
        $tourDuration = TourDuration::findOrFail($id);
        $tourDuration->tour = $request->input('tour');
        
        $duration = $request->input('duration');
        $hours = floor($duration);
        $minutes = ($duration - $hours) * 100; // Extract minutes from decimal part
        $totalMinutes = ($hours * 60) + $minutes;
        
        $tourDuration->duration = $totalMinutes;
        $tourDuration->save();

        return redirect()->route('tour-durations.index')->with('success', 'Tour Duration updated successfully!');
    }

    public function updateSauna(Request $request, $id)
    {
        $tourDuration = SaunaTourDurations::findOrFail($id);
        $tourDuration->tour = $request->input('tour');
        
        $duration = $request->input('duration');
        $hours = floor($duration);
        $minutes = ($duration - $hours) * 100; // Extract minutes from decimal part
        $totalMinutes = ($hours * 60) + $minutes;
        
        $tourDuration->duration = $totalMinutes;
        $tourDuration->save();

        return redirect()->route('tour-durations-sauna.index')->with('success', 'Sauna Tour Duration updated successfully!');
    }

    public function destroy($id)
    {
        $tourDuration = TourDuration::findOrFail($id);
        $tourDuration->delete();

        return redirect()->route('tour-durations.index')->with('success', 'Tour Duration deleted successfully!');
    }

    public function destroySauna($id)
    {
        $tourDuration = SaunaTourDurations::findOrFail($id);
        $tourDuration->delete();

        return redirect()->route('tour-durations-sauna.index')->with('success', 'SaunaTour Duration deleted successfully!');
    }
    public function updatedate(Request $request)
    {
        DB::table('updatedate')
        ->where('id', 1)
        ->update([
            'date' => $request->input('date'),
         
        ]);
    
        return redirect()->back()->with('success', 'Updated successfully!');
    }

   
}
