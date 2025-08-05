<?php

namespace App\Http\Controllers;

use Spatie\GoogleCalendar\Event;
use App\Models\Event as LocalEvent;
use App\Models\EventSalary;
use App\Models\LastTours;
use App\Models\Notification;
use App\Models\TourGuide;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class GoogleCalendarController extends Controller
{
    public function fixxx()
    {

        // Define the time range
        $start = Carbon::parse('2025-01-13 17:00:24');
        $end = Carbon::parse('2025-01-13 17:10:24');

        // Restore records deleted within the time range
        EventSalary::onlyTrashed()
            ->whereBetween('deleted_at', [$start, $end])
            ->restore();


        // Update status to 1 for matching records
        LocalEvent::whereBetween('updated_at', [$start, $end])
            ->update(['status' => 1]);
    }

    public function dashboard()
    {
        if (Auth::user()->role == "admin" || Auth::user()->role == "hr-assistant") {
            $lastTours = LastTours::orderBy('tour_date', 'desc')
                ->take(40)
                ->get();
            return view('fetch-events', compact('lastTours'));
        } elseif (Auth::user()->role == "guide") {
            return redirect()->route('guide.work-report');
        } elseif (Auth::user()->role == "guide/staff") {
            return redirect()->route('guide.work-report');
        } elseif (Auth::user()->role == "supervisor" || Auth::user()->role == "operation") {
            return redirect()->route('supervisors.working-hours');
        } elseif (Auth::user()->role == "am-supervisor") {
            return redirect()->route('am-supervisor.enter-working-hours');
        } elseif (Auth::user()->role == "staff") {
            return redirect()->route('staff.schedule');
        } elseif (Auth::user()->role == "team-lead") {
            return redirect()->route('manager.guide-report');
        } elseif (Auth::user()->role == "hr") {
            return redirect()->route('supervisor.manage-sick-leaves');
        }
    }


    public function fetchFilterEvents(Request $request)
    {
        $calendarId = env('GOOGLE_CALENDAR_ID');

        // Validate and parse the start and end dates
        try {
            $startDateTime = Carbon::createFromFormat('Y-m-d', $request->start)->startOfDay();
            $endDateTime = Carbon::createFromFormat('Y-m-d', $request->end)->endOfDay();
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Invalid date format. Please use YYYY-MM-DD.');
        }

        // Adjust endDateTime to exclude the next day
        $adjustedEndDateTime = $endDateTime->copy()->subSecond();

        // Fetch Google events
        $googleEvents = Event::get($startDateTime, $adjustedEndDateTime, [], $calendarId);

        $ignorePrefixes = ['EV', 'TL', 'DR', 'LINK', 'VP', 'TM', 'TR', 'MT', 'X', 'DJ'];
        $fetchedEventIds = [];

        foreach ($googleEvents as $googleEvent) {
            $eventStartTime = $googleEvent->startDateTime ?? $googleEvent->startDate;
            $eventEndTime = $googleEvent->endDateTime ?? null;

            // Skip events that start after the adjusted end time
            if ($eventStartTime > $adjustedEndDateTime) {
                continue;
            }

            // Case-insensitive check to skip events with ignored prefixes
            $skipEvent = false;
            foreach ($ignorePrefixes as $prefix) {
                if (stripos($googleEvent->name, $prefix . ' ') === 0) {
                    $skipEvent = true;
                    break;
                }
            }

            if ($skipEvent) {
                // If the event is marked for deletion (e.g., 'X' prefix), delete it from local database
                LocalEvent::where('event_id', $googleEvent->id)->delete();
                continue;
            }

            // Store the original description without any modifications
            $originalDescription = $googleEvent->description;

            // Process the description to preserve formatting
            $description = $originalDescription;
            $description = html_entity_decode($description);
            $description = str_replace('<br>', "\n", $description);
            $description = str_replace('<b>', "", $description);
            $description = str_replace('</b>', "", $description);

            // Check if the event exists and if the original description has changed
            $localEvent = LocalEvent::where('event_id', $googleEvent->id)->first();
            $descriptionChanged = !$localEvent || $localEvent->original_description !== $originalDescription;

            if (!$localEvent || $descriptionChanged) {
                $localEvent = LocalEvent::updateOrCreate(
                    ['event_id' => $googleEvent->id],
                    [
                        'name' => $googleEvent->name,
                        'description' => $description,
                        'original_description' => $originalDescription,
                        'start_time' => $eventStartTime,
                        'end_time' => $eventEndTime,
                    ]
                );

                // If the event was updated and is_edited is 0, reset its status, delete related records, and update or create notification
                if (!$localEvent->wasRecentlyCreated && $localEvent->wasChanged() && $localEvent->is_edited == 0) {
                    $localEvent->status = 0;
                    $localEvent->save();

                    // Delete all related EventSalary records
                    EventSalary::where('eventId', $localEvent->id)->delete();

                    // Prepare the detailed message
                    $detailedMessage = sprintf(
                        "Date: %s\nTour Name: %s\nError: %s",
                        $localEvent->start_time->format('d.m.Y'),
                        $localEvent->name,
                        "Event updated. Please recalculate the guide hours."
                    );

                    // Update existing notification or create a new one
                    Notification::updateOrCreate(
                        ['eventId' => $localEvent->id],
                        ['desc' => $detailedMessage]
                    );
                }
            }

            // Add the processed event ID to the fetchedEventIds array
            $fetchedEventIds[] = $googleEvent->id;
        }

        // Delete local events that are no longer in Google Calendar
        LocalEvent::where(function ($query) use ($startDateTime, $adjustedEndDateTime) {
            $query->whereBetween('start_time', [$startDateTime, $adjustedEndDateTime])
                ->orWhereBetween('end_time', [$startDateTime, $adjustedEndDateTime])
                ->orWhere(function ($q) use ($startDateTime, $adjustedEndDateTime) {
                    $q->where('start_time', '<=', $startDateTime)
                        ->where('end_time', '>=', $adjustedEndDateTime);
                });
        })->whereNotIn('event_id', $fetchedEventIds)
            ->where('event_id', 'not like', 'manual%')  // Add this line to exclude manual events
            ->delete();

        // Fetch and filter local events based on the date range
        $eventes = LocalEvent::where(function ($query) use ($startDateTime, $endDateTime) {
            $query->whereBetween('start_time', [$startDateTime, $endDateTime])
                ->orWhereBetween('end_time', [$startDateTime, $endDateTime])
                ->orWhere(function ($q) use ($startDateTime, $endDateTime) {
                    $q->where('start_time', '<=', $startDateTime)
                        ->where('end_time', '>=', $endDateTime);
                });
        })->get();
        $this->saveLastTour();
        return redirect()->back()->with('success', 'Google Calendar events updated successfully.');
    }


    public function fetchFilterChores(Request $request)
    {
        $calendarId = env('GOOGLE_CALENDAR_ID');

        // Validate and parse the start and end dates
        try {
            $startDateTime = Carbon::createFromFormat('Y-m-d', $request->start)->startOfDay();
            $endDateTime = Carbon::createFromFormat('Y-m-d', $request->end)->endOfDay();
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Invalid date format. Please use YYYY-MM-DD.');
        }

        // Adjust endDateTime to exclude the next day
        $adjustedEndDateTime = $endDateTime->copy()->subSecond();

        // Fetch Google events
        $googleEvents = Event::get($startDateTime, $adjustedEndDateTime, [], $calendarId);

        $allowedPrefix = 'Z';
        $fetchedEventIds = [];

        foreach ($googleEvents as $googleEvent) {
            if($googleEvent->name == '' && $googleEvent->description == ''){
                continue;
              }
            $eventStartTime = $googleEvent->startDateTime ?? $googleEvent->startDate;
            $eventEndTime = $googleEvent->endDateTime ?? null;

            // Skip events that start after the adjusted end time
            if ($eventStartTime > $adjustedEndDateTime) {
                continue;
            }

            // Case-insensitive check to only allow events starting with Z
            $allowEvent = (stripos($googleEvent->name, $allowedPrefix . ' ') === 0);

            if (!$allowEvent) {
                continue;
            }

            // Store the original description without any modifications
            $originalDescription = $googleEvent->description;

            // Process the description to preserve formatting
            $description = $originalDescription;
            $description = html_entity_decode($description);
            $description = str_replace('<br>', "\n", $description);
            $description = str_replace('<b>', "", $description);
            $description = str_replace('</b>', "", $description);

            // Check if the event exists and if the original description has changed
            $localEvent = LocalEvent::where('event_id', $googleEvent->id)->first();
            $descriptionChanged = !$localEvent || $localEvent->original_description !== $originalDescription;

            if (!$localEvent || $descriptionChanged) {
                $localEvent = LocalEvent::updateOrCreate(
                    ['event_id' => $googleEvent->id],
                    [
                        'name' => $googleEvent->name,
                        'description' => $description,
                        'original_description' => $originalDescription,
                        'start_time' => $eventStartTime,
                        'end_time' => $eventEndTime,
                    ]
                );

                // If the event was updated and is_edited is 0, reset its status, delete related records, and update or create notification
                if (!$localEvent->wasRecentlyCreated && $localEvent->wasChanged() && $localEvent->is_edited == 0) {
                    $localEvent->status = 0;
                    $localEvent->save();

                    // Delete all related EventSalary records
                    EventSalary::where('eventId', $localEvent->id)->delete();

                    // Prepare the detailed message
                    $detailedMessage = sprintf(
                        "Date: %s\nTour Name: %s\nError: %s",
                        $localEvent->start_time->format('d.m.Y'),
                        $localEvent->name,
                        "Event updated. Please recalculate the guide hours."
                    );

                    // Update existing notification or create a new one
                    Notification::updateOrCreate(
                        ['eventId' => $localEvent->id],
                        ['desc' => $detailedMessage]
                    );
                }
            }

            // Add the processed event ID to the fetchedEventIds array
            $fetchedEventIds[] = $googleEvent->id;
        }

        // Delete local events that are no longer in Google Calendar
        LocalEvent::where(function ($query) use ($startDateTime, $adjustedEndDateTime) {
            $query->whereBetween('start_time', [$startDateTime, $adjustedEndDateTime])
                ->orWhereBetween('end_time', [$startDateTime, $adjustedEndDateTime])
                ->orWhere(function ($q) use ($startDateTime, $adjustedEndDateTime) {
                    $q->where('start_time', '<=', $startDateTime)
                        ->where('end_time', '>=', $adjustedEndDateTime);
                });
        })->whereNotIn('event_id', $fetchedEventIds)
            ->where('event_id', 'not like', 'manual%')  // Add this line to exclude manual events
            ->where('name', 'like', 'Z%')
            ->delete();

        // Fetch and filter local events based on the date range
        $eventes = LocalEvent::where(function ($query) use ($startDateTime, $endDateTime) {
            $query->whereBetween('start_time', [$startDateTime, $endDateTime])
                ->orWhereBetween('end_time', [$startDateTime, $endDateTime])
                ->orWhere(function ($q) use ($startDateTime, $endDateTime) {
                    $q->where('start_time', '<=', $startDateTime)
                        ->where('end_time', '>=', $endDateTime);
                });
        })->get();

        $this->saveLastTour();
        return redirect()->back()->with('success', 'Google Calendar events updated successfully.');
    }

    public function saveLastTour()
    {
        $controller = new LastToursController();
        $result = $controller->updateLastTours();
        return response()->json($result);
    }


    public function fetchAllEvents(Request $request)
    {


        $guides = TourGuide::all(); // Fetch all guides from the database
        $eventes = LocalEvent::orderBy('start_time', 'asc')->where('status', 0)->get();
        $updatedate =  DB::table('updatedate')
            ->where('id', 1)->first();

        return view('view-events', compact('eventes', 'guides', 'updatedate'));
    }
}
