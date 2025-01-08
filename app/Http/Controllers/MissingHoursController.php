<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventSalary;
use App\Models\Holiday;
use App\Models\MissingHours;
use App\Models\TourGuide;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Carbon\Carbon;

class MissingHoursController extends Controller
{
    public function manageMissingHours()
    {
        $guides = TourGuide::where('is_hidden', false)->orderBy('name', 'asc')->get();
        $missingHours = MissingHours::orderBy('created_at', 'desc')->get();
        return view('missing-hours.manage-missing-hours', compact('guides', 'missingHours'));
    }

    public function storeMissingHours(Request $request)
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

        // Store original time format in MissingHours
        MissingHours::create([
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
            'applied_at' => $request->applied_at . '-01',
            'created_by' => auth()->user()->id
        ]);

        //first we have to create an event, if it doesn't exist
        $startDate = $request->applied_at . '-01';
        $eventName = $guide->name . ' - missing hours ' . $request->applied_at;
        
        // Check if event already exists
        $event = Event::where('name', $eventName)
            ->where('start_time', $startDate)
            ->first();
            
        // Only create if event doesn't exist
        if (!$event) {
            $randomString = Str::random(10);
            $event = Event::create([
                'event_id' => 'manual-missing-hours-' . $randomString,
                'name' => $eventName,
                'description' => $eventName,
                'original_description' => $eventName,
                'start_time' => $startDate,
                'end_time' => $startDate,
                'status' => 1,
            ]);
        }

        // Use decimal format for EventSalary
        $eventSalary = EventSalary::where('guideId', $request->guide_id)
            ->where('guide_start_time', $request->applied_at . '-01 00:00:00')
            ->where('eventId', $event->id)
            ->first();

        if (!$eventSalary) {
            EventSalary::create([
                'eventId' => $event->id,   
                'guideId' => $request->guide_id,   
                'date' => $request->applied_at,
                'normal_hours' => $normal_hours,
                'normal_night_hours' => $normal_night_hours,
                'holiday_hours' => $holiday_hours,
                'holiday_night_hours' => $holiday_night_hours,
                'approval_status' => '1',
                'guide_start_time' => $request->applied_at . '-01 00:00:00',
                'guide_end_time' => $request->applied_at . '-01 00:00:00',
            ]);
        } else {
            $eventSalary->update([
                'normal_hours' => $this->addHours($eventSalary->normal_hours, $normal_hours),
                'normal_night_hours' => $this->addHours($eventSalary->normal_night_hours, $normal_night_hours),
                'holiday_hours' => $this->addHours($eventSalary->holiday_hours, $holiday_hours),
                'holiday_night_hours' => $this->addHours($eventSalary->holiday_night_hours, $holiday_night_hours),
            ]);
        }

        return redirect()->back()->with('success', 'Missing Hours Added Successfully');
    }



    public function destroyMissingHours($id)
    {
        $missingHours = MissingHours::findOrFail($id);
        
        // Find the corresponding event
        $eventName = $missingHours->guide_name . ' - missing hours ' . date('Y-m', strtotime($missingHours->applied_at));
        $event = Event::where('name', $eventName)
            ->where('start_time', $missingHours->applied_at)
            ->first();

        if ($event) {
            // Find and update EventSalary
            $eventSalary = EventSalary::where('guideId', $missingHours->guide_id)
                ->where('guide_start_time', $missingHours->applied_at . ' 00:00:00')
                ->where('eventId', $event->id)
                ->first();

            if ($eventSalary) {
                // Convert time format HH:MM to our decimal format
                $normal_hours = $missingHours->normal_hours;
                $normal_night_hours = $missingHours->normal_night_hours;
                $holiday_hours = $missingHours->holiday_hours;
                $holiday_night_hours = $missingHours->holiday_night_hours;

                // Subtract the hours
                $eventSalary->update([
                    'normal_hours' => $this->subtractHours($eventSalary->normal_hours, $normal_hours),
                    'normal_night_hours' => $this->subtractHours($eventSalary->normal_night_hours, $normal_night_hours),
                    'holiday_hours' => $this->subtractHours($eventSalary->holiday_hours, $holiday_hours),
                    'holiday_night_hours' => $this->subtractHours($eventSalary->holiday_night_hours, $holiday_night_hours),
                ]);

                // If all hours are 0, we can delete the EventSalary record
                if ($eventSalary->normal_hours == 0 && 
                    $eventSalary->normal_night_hours == 0 && 
                    $eventSalary->holiday_hours == 0 && 
                    $eventSalary->holiday_night_hours == 0) {
                    $eventSalary->delete();
                    
                    // If this was the last EventSalary for this event, we can delete the event too
                    if (!EventSalary::where('eventId', $event->id)->exists()) {
                        $event->delete();
                    }
                }
            }
        }

        // Mark who deleted it and delete the missing hours record
        $missingHours->updated_by = auth()->user()->id;
        $missingHours->delete();

        return redirect()->back()->with('success', 'Missing Hours Deleted Successfully');
    }

    private function subtractHours($existing, $subtract) 
    {
        // Convert string inputs to float and extract hours and minutes
        $existingHours = floor((float)$existing);
        $existingMinutes = (int)(((float)$existing - $existingHours) * 100);

        $subtractHours = floor((float)$subtract);
        $subtractMinutes = (int)(((float)$subtract - $subtractHours) * 100);

        // Rest of the function remains the same
        $totalExistingMinutes = ($existingHours * 60) + $existingMinutes;
        $totalSubtractMinutes = ($subtractHours * 60) + $subtractMinutes;

        $resultMinutes = $totalExistingMinutes - $totalSubtractMinutes;

        $resultHours = floor($resultMinutes / 60);
        $resultMinutesRemaining = $resultMinutes % 60;

        return sprintf('%d.%02d', $resultHours, $resultMinutesRemaining);
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


    private function addHours($existing, $new) 
    {
        // Extract hours and minutes
        $existingHours = floor($existing);
        $existingMinutes = (int)(($existing - $existingHours) * 100); // Get actual minutes

        $newHours = floor($new);
        $newMinutes = (int)(($new - $newHours) * 100); // Get actual minutes

        // Add separately
        $totalHours = $existingHours + $newHours;
        $totalMinutes = $existingMinutes + $newMinutes;

        // Handle minute overflow
        if ($totalMinutes >= 60) {
            $totalHours += floor($totalMinutes / 60);
            $totalMinutes = $totalMinutes % 60;
        }

        // Format back to hours.minutes format
        return sprintf('%d.%02d', $totalHours, $totalMinutes);
    }

    public function update(Request $request, $id)
    {   
        try {
            $missingHours = MissingHours::findOrFail($id);
            $guide = TourGuide::findOrFail($request->guide_id);

            // Store old values for updating EventSalary later
            $oldValues = [
                'normal_hours' => $missingHours->normal_hours,
                'normal_night_hours' => $missingHours->normal_night_hours,
                'holiday_hours' => $missingHours->holiday_hours,
                'holiday_night_hours' => $missingHours->holiday_night_hours,
                'applied_at' => Carbon::parse($missingHours->applied_at)->format('Y-m'),
                'guide_id' => $missingHours->guide_id
            ];

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

            // Calculate new hours
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

            // Update MissingHours record
            $missingHours->update([
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
                'applied_at' => $request->applied_at . '-01'
            ]);

            // Handle EventSalary updates
            // First, subtract old values from old month if month changed
            if ($oldValues['applied_at'] !== $request->applied_at) {
                $oldEvent = Event::where('name', $guide->name . ' - missing hours ' . $oldValues['applied_at'])
                    ->where('start_time', $oldValues['applied_at'] . '-01')
                    ->first();

                if ($oldEvent) {
                    $oldEventSalary = EventSalary::where('guideId', $oldValues['guide_id'])
                        ->where('guide_start_time', $oldValues['applied_at'] . '-01 00:00:00')
                        ->where('eventId', $oldEvent->id)
                        ->first();

                    if ($oldEventSalary) {
                        $oldEventSalary->update([
                            'normal_hours' => $this->subtractHours($oldEventSalary->normal_hours, $oldValues['normal_hours']),
                            'normal_night_hours' => $this->subtractHours($oldEventSalary->normal_night_hours, $oldValues['normal_night_hours']),
                            'holiday_hours' => $this->subtractHours($oldEventSalary->holiday_hours, $oldValues['holiday_hours']),
                            'holiday_night_hours' => $this->subtractHours($oldEventSalary->holiday_night_hours, $oldValues['holiday_night_hours']),
                        ]);
                    }
                }
            }

            // Add new values to new month
            $startDate = $request->applied_at . '-01';
            $eventName = $guide->name . ' - missing hours ' . $request->applied_at;
            
            $event = Event::where('name', $eventName)
                ->where('start_time', $startDate)
                ->first();
                
            if (!$event) {
                $randomString = Str::random(10);
                $event = Event::create([
                    'event_id' => 'manual-missing-hours-' . $randomString,
                    'name' => $eventName,
                    'description' => $eventName,
                    'original_description' => $eventName,
                    'start_time' => $startDate,
                    'end_time' => $startDate,
                    'status' => 1,
                ]);
            }

            $eventSalary = EventSalary::where('guideId', $request->guide_id)
                ->where('guide_start_time', $request->applied_at . '-01 00:00:00')
                ->where('eventId', $event->id)
                ->first();

            if (!$eventSalary) {
                EventSalary::create([
                    'eventId' => $event->id,   
                    'guideId' => $request->guide_id,   
                    'date' => $request->applied_at,
                    'normal_hours' => $normal_hours,
                    'normal_night_hours' => $normal_night_hours,
                    'holiday_hours' => $holiday_hours,
                    'holiday_night_hours' => $holiday_night_hours,
                    'approval_status' => '1',
                    'guide_start_time' => $request->applied_at . '-01 00:00:00',
                    'guide_end_time' => $request->applied_at . '-01 00:00:00',
                ]);
            } else {
                // If it's the same month, subtract old values first
                if ($oldValues['applied_at'] === $request->applied_at) {
                    $eventSalary->update([
                        'normal_hours' => $this->addHours(
                            $this->subtractHours($eventSalary->normal_hours, $oldValues['normal_hours']),
                            $normal_hours
                        ),
                        'normal_night_hours' => $this->addHours(
                            $this->subtractHours($eventSalary->normal_night_hours, $oldValues['normal_night_hours']),
                            $normal_night_hours
                        ),
                        'holiday_hours' => $this->addHours(
                            $this->subtractHours($eventSalary->holiday_hours, $oldValues['holiday_hours']),
                            $holiday_hours
                        ),
                        'holiday_night_hours' => $this->addHours(
                            $this->subtractHours($eventSalary->holiday_night_hours, $oldValues['holiday_night_hours']),
                            $holiday_night_hours
                        ),
                    ]);
                } else {
                    $eventSalary->update([
                        'normal_hours' => $this->addHours($eventSalary->normal_hours, $normal_hours),
                        'normal_night_hours' => $this->addHours($eventSalary->normal_night_hours, $normal_night_hours),
                        'holiday_hours' => $this->addHours($eventSalary->holiday_hours, $holiday_hours),
                        'holiday_night_hours' => $this->addHours($eventSalary->holiday_night_hours, $holiday_night_hours),
                    ]);
                }
            }

            return redirect()->back()->with('success', 'Missing Hours Updated Successfully');
        } catch (\Exception $e) {
            return redirect()->back()->with('failed', 'Failed to update missing hours: ' . $e->getMessage());
        }
    }

    
}
