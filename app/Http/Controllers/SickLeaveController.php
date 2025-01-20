<?php

namespace App\Http\Controllers;

use App\Models\Holiday;
use App\Models\SickLeave;
use App\Models\TourGuide;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SickLeaveController extends Controller
{

    public function index(){
        $sickLeaves = SickLeave::orderBy('date','desc')->get();
        $guides = TourGuide::orderBy('name','asc')->get();
        return view('sick_leave.index',compact('sickLeaves','guides'));
    }

    public function createNewSickLeaveTour(){
        $guides = TourGuide::orderBy('name','asc')->get();
        return view('sick_leave.create',compact('guides'));
    }

    public function destroy($id){
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

    public function sickLeaveStoreManual(Request $request) {  
        
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
}
