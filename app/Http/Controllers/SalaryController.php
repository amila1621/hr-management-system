<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventSalary;
use App\Models\Holiday;
use App\Models\Notification;
use App\Models\TourGuide;
use DB;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Google\Service\AnalyticsReporting\EventData;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

use Illuminate\Support\Facades\Cache;

use App\Models\SalaryTimeAdjustment;

class SalaryController extends Controller
{

    private function getLocationDuration($pickupLocation)
    {

        $defaultDuration = config('app.default_pickup_duration', 0);

        $cacheKey = 'pickup_duration_' . md5($pickupLocation);

        Log::info("Starting getLocationDuration for pickup location: $pickupLocation");





        // Append ", Finland" to the location for better results
        $pickupLocation .= ", Rovaniemi, Finland";

        $officeLocation = config('app.office_coordinates');
        $apiKey = config('services.google.maps_api_key');

        // Log configuration status
        Log::info("Office location config value: " . ($officeLocation ?: 'Not set'));
        Log::info("API Key config status: " . ($apiKey ? 'Set' : 'Not set'));

        if (!$officeLocation || !$apiKey) {
            Log::error("Missing configuration for office location or Google Maps API key");
            return $defaultDuration;
        }

        $client = new Client();

        try {
            Log::info("Sending request to Google Maps API for: $pickupLocation");
            $response = $client->get('https://maps.googleapis.com/maps/api/distancematrix/json', [
                'query' => [
                    'origins' => $officeLocation,
                    'destinations' => $pickupLocation,
                    'mode' => 'driving',
                    'key' => $apiKey
                ]
            ]);

            $data = json_decode($response->getBody(), true);
            
            Log::info("Google Maps API Response: " . json_encode($data));

            if ($data['status'] === 'OK' && isset($data['rows'][0]['elements'][0]['duration']['value'])) {
                $durationInMinutes = round($data['rows'][0]['elements'][0]['duration']['value'] / 60);
                Cache::put($cacheKey, $durationInMinutes, now()->addDays(7));
                Log::info("Calculated location duration: $durationInMinutes minutes for pickup location: $pickupLocation");
                return $durationInMinutes;
            } else {
                Log::error("Invalid response from Google Maps API: " . json_encode($data));
                return $defaultDuration;
            }
        } catch (\Exception $e) {
            Log::error("Exception when calling Google Maps API: " . $e->getMessage());
            Log::error("Exception stack trace: " . $e->getTraceAsString());
            return $defaultDuration;
        }
    }


    private function extractEventData($title,$description)
    {
        $data = [
            'guides' => [],
            'helpers' => [],
            'office_time' => null
        ];

        // Clean the description - decode HTML entities and remove tags
        $description = html_entity_decode(strip_tags($description));
        
        $lines = explode("\n", $description);
        $currentPersonnel = [];
        $hasPersonnel = false;

        foreach ($lines as $line) {
            $line = trim($line);

            // Stop processing if we encounter more than 15 dashes
            if (substr_count($line, '-') > 15) {
                break;
            }

            // Extract guide and helper data
            if (preg_match_all('/\b([GgHh])\s*-\s*([A-Za-z][A-Za-z\s.]+)(?=\s*[\/+]|\s*$|\s*,)/i', $line, $matches, PREG_SET_ORDER)) {
                $currentPersonnel = [];
                foreach ($matches as $match) {
                    $hasPersonnel = true;
                    $type = strtoupper($match[1]);
                    $name = trim($match[2]);
                    // Ignore names containing "Office Key"
                    if (stripos($name, 'Office Key') === false) {
                        $this->addPersonnel($data, $type, $name, $currentPersonnel);
                    }
                }
            }
            // Additional check for lines starting with G- or H-
            elseif (preg_match('/^([GgHh])\s*-\s*([A-Za-z][A-Za-z\s.]+)/', $line, $match)) {
                $hasPersonnel = true;
                $type = strtoupper($match[1]);
                $name = trim($match[2]);
                if (stripos($name, 'Office Key') === false) {
                    $this->addPersonnel($data, $type, $name, $currentPersonnel);
                }
            }
            
            // Extract vehicle data for the last guide
            if (preg_match('/([A-Z]{3}\s+\d{3})\s*\((\d+)\s*seats?\)/i', $line, $vehicleMatches)) {
                $lastGuide = end($currentPersonnel);
                if ($lastGuide['type'] === 'guide') {
                    $data['guides'][$lastGuide['name']]['vehicle'] = $vehicleMatches[0];
                }
            }

            // Process data for the current personnel
            elseif (!empty($currentPersonnel)) {
                // Extract time (pickup time)
                if (preg_match('/(\d{1,2}:\d{2})/', $line, $matches)) {
                    $time = $matches[1];
                    foreach ($currentPersonnel as $person) {
                        if ($person['type'] === 'guide') {
                            $guide = $person['name'];
                            if (empty($data['guides'][$guide]['pickup_time']) || $time < $data['guides'][$guide]['pickup_time']) {
                                $data['guides'][$guide]['pickup_time'] = $time;
                            }
                        }
                    }
                }

                // Extract pickup location
                if (preg_match('/P\s*-\s*(.+?)(?=\s*\/|\s*$)/', $line, $matches)) {
                    $location = trim($matches[1]);
                    foreach ($currentPersonnel as $person) {
                        if ($person['type'] === 'guide') {
                            $guide = $person['name'];
                            if (empty($data['guides'][$guide]['pickup_location'])) {
                                $data['guides'][$guide]['pickup_location'] = $location;
                            }
                        }
                    }
                }

                // Extract pax details
                if (preg_match('/(\d+)\+(\d+)\+(\d+)\s*pax/', $line, $matches)) {
                    foreach ($currentPersonnel as $person) {
                        if ($person['type'] === 'guide') {
                            $guide = $person['name'];
                            $data['guides'][$guide]['pax_details'][] = $matches[0];
                        }
                    }
                }
            }

            // Extract office time - improved pattern
            if (preg_match('/\*\s*office\s*(\d{1,2}:\d{2})/i', $line, $matches)) {
                Log::info("Found office time in line: " . $line);
                $data['office_time'] = $matches[1];
                Log::info("Extracted office time: " . $matches[1]);
            }
        }

        // Post-processing
        foreach ($data['guides'] as $guide => &$guideData) {
            // If no pickup time was found, use office time
            if (!isset($guideData['pickup_time']) && isset($data['office_time'])) {
                $guideData['pickup_time'] = $data['office_time'];
            }
        }

        // Check if any personnel (guides or helpers) were found
        if (!$hasPersonnel) {
            throw new \Exception("No guides or helpers found in the event description.");
        }
       
        // Check if there are only helpers (no guides)
        if (!empty($data['guides'])) {
            if (!isset($data['office_time']) || empty($data['office_time'])) {
                Log::error("Office time validation failed for helper-only event: $title");
                throw new \Exception("Office time is required when guides are assigned. Please ensure the description includes '*office HH:MM' format.");
            }
            
            // Validate office time format
            if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $data['office_time'])) {
                Log::error("Invalid office time format found: {$data['office_time']}");
                throw new \Exception("Invalid office time format. Please use 24-hour format (HH:MM).");
            }
        }
       

        return $data;
    }

    private function addPersonnel(&$data, $type, $name, &$currentPersonnel)
    {
        if ($type === 'G') {
            $currentPersonnel[] = ['type' => 'guide', 'name' => $name];
            if (!isset($data['guides'][$name])) {
                $data['guides'][$name] = [
                    'pickup_time' => null,
                    'pickup_location' => null,
                    'vehicle' => null,
                    'pax_details' => []
                ];
            }
        } elseif ($type === 'H') {
            $currentPersonnel[] = ['type' => 'helper', 'name' => $name];
            if (!in_array($name, $data['helpers'])) {
                $data['helpers'][] = $name;
            }
        }
    }






    public function eventsalary($id, $processedData = null)
    {
        try {
            DB::beginTransaction();

            $event = Event::findOrFail($id);
            EventSalary::where('eventId', $id)->delete();

            $startDateTime = Carbon::parse($event->start_time);
            $title = $event->name;

            // Extract event data
            // If processedData is not provided, use extractData
            $eventData =  $processedData ?? $this->extractEventData($title, $event->description);
            // dd($eventData);
            // Check for specific tour types
            if (stripos($title, 'Sauna') !== false) {
                $this->manageSaunaTours($event, $eventData);
            } elseif (preg_match('/^Z\s*/i', $title)) {
                $this->manageChores($event, $eventData);
            } elseif (stripos($title, 'Snowy Trails Husky Safari') !== false) {
                $this->manageHuskySafari($event, $eventData);
            } elseif (stripos($title, 'Highlights of Rovaniemi Day Tour') !== false) {
                $this->manageHighlightsOfRovaniemi($event, $eventData);
            } elseif (stripos($title, 'Transfer Service') !== false) {
                $this->manageTransferService($event, $eventData);
            } elseif (preg_match('/\b[2]-day[s]?\b/i', $title)) {
                $this->manageChores($event, $eventData);
            } else {
                $this->manageGeneralTour($event, $eventData);
            }

            $event->status = 1;
            $event->save();

            DB::commit();

            Notification::where('eventId', $event->id)->delete();

            return redirect()->back()->with('success', "Event salary for '$title' processed successfully.");
        } catch (\Exception $e) {
            DB::rollBack();
            $this->createOrUpdateNotification($id, $e->getMessage());
            Log::error("Error processing event salary for ID $id: " . $e->getMessage());
            return redirect()->back()->with('error', "Error processing event salary: " . $e->getMessage());
        }
    }


    private function manageGeneralTour($event, $eventData)
    {
        $title = $event->name;
        $startDateTime = Carbon::parse($event->start_time);

        $cleanedTitle = preg_replace('/^(N|D|EV|X)\s*/i', '', $title);
        $tourNames = array_map('trim', explode('+', $cleanedTitle));


        // Find the tour with the longest duration
        $longestDuration = 0;
        $longestTourName = '';
        foreach ($tourNames as $tourName) {
            // Try exact match first
            $tourDuration = DB::table('tour_durations')->where('tour', $tourName)->first();
            
            // If no exact match, try Levenshtein distance matching
            if (!$tourDuration) {
                $tourDuration = $this->findTourWithLevenshtein($tourName,10);
                if ($tourDuration) {
                    Log::info("Found tour using Levenshtein distance: Original: '$tourName', Matched: '{$tourDuration->tour}'");
                } else {
                    throw new \Exception("Tour name not found in tour_durations table (including similar matches): $tourName");
                }
            }


            if ($tourDuration->duration > $longestDuration) {
                $longestDuration = $tourDuration->duration;
                $longestTourName = $tourName;
            }
        }

        $durationInMinutes = $longestDuration;

        $isDaOrNa = false;
        if (preg_match('/^(DA|NA)\s+/i', $title)) {
            $isDaOrNa = true;
            // $durationInMinutes += 90;    
        }


        Log::info("Using tour duration of $durationInMinutes minutes from tour: $longestTourName");

        // Process guides
        $processedGuides = [];
        foreach ($eventData['guides'] as $guideName => $guideData) {
            // Skip if we've already processed this guide or if it contains "Office Key"
            if (in_array($guideName, $processedGuides) || stripos($guideName, 'Office Key') !== false) {
                continue;
            }

            // Get location duration
            $locationDuration = 0;
            if(!$isDaOrNa){
                $locationDuration = $this->getLocationDuration($guideData['pickup_location']);
            }
            $guideModel = TourGuide::whereRaw('LOWER(name) = ?', [strtolower($guideName)])->first();

            if (!$guideModel) {
                throw new \Exception("Guide not found in database: $guideName");
            }


            // Initialize guide start time
            $guideStartTime = $guideData['pickup_time']
                ? Carbon::parse($startDateTime->format('Y-m-d') . ' ' . $guideData['pickup_time'])
                : ($eventData['office_time']
                    ? Carbon::parse($startDateTime->format('Y-m-d') . ' ' . $eventData['office_time'])
                    : $startDateTime);


            // If office time is earlier than pickup time, use office time as pickup time
            if ($eventData['office_time'] && (!$guideData['pickup_time'] || $eventData['office_time'] < $guideData['pickup_time'])) {
                $guideStartTime = Carbon::parse($startDateTime->format('Y-m-d') . ' ' . $eventData['office_time']);
            }


            // Check if pickup location is office
            $isOfficePickup = stripos($guideData['pickup_location'], 'office') !== false;

            // Calculate initial adjusted start time
            $initialAdjustedStartTime = $isOfficePickup ? $guideStartTime->copy() : $guideStartTime->copy()->subMinutes($locationDuration);

            // Calculate duration to office

            if (!isset($eventData['office_time']) || $eventData['office_time'] === null) {
                throw new \Exception("Office time is missing in the event description.");
            }
            
            $officeTime = Carbon::parse($startDateTime->format('Y-m-d') . ' ' . $eventData['office_time']);
            $durationToOffice = $isOfficePickup ? 0 : $initialAdjustedStartTime->diffInMinutes($officeTime);

            

            // Apply 30-minute deduction rule
            if ($durationToOffice > 30) {
                $adjustedDuration = $durationToOffice - 30;
            } else {
                $adjustedDuration = 0;
            }



            // Calculate final adjusted start time
            $adjustedGuideStartTime = $guideStartTime->copy()->subMinutes($adjustedDuration);
            Log::info("Adjusted duration 001: {$durationToOffice}");
            // Calculate end time
            $guideEndTime = $guideStartTime->copy()->addMinutes($durationInMinutes)->addMinutes($adjustedDuration);

            // Check if the tour spans across midnight
            if ($adjustedGuideStartTime->day != $guideEndTime->day) {
                $firstDayEnd = $adjustedGuideStartTime->copy()->endOfDay()->addSecond();
                $this->createGuideSalaryEntry($event->id, $guideModel->id, $adjustedGuideStartTime, $firstDayEnd);
                $this->createGuideSalaryEntry($event->id, $guideModel->id, $firstDayEnd, $guideEndTime);
            } else {
                $this->createGuideSalaryEntry($event->id, $guideModel->id, $adjustedGuideStartTime, $guideEndTime);
            }

            Log::info("Adjusted guide Start time 001: {$guideStartTime}");
            Log::info("Created EventSalary entries for guide $guideName:");

            // Mark this guide as processed
            $processedGuides[] = $guideName;
        }

        // Process helpers
        $processedHelpers = [];
        foreach ($eventData['helpers'] as $helperName) {
            // Skip if we've already processed this helper or if it contains "Office Key"
            if (in_array($helperName, $processedHelpers) || stripos($helperName, 'Office Key') !== false) {
                continue;
            }

            $helperData = TourGuide::whereRaw('LOWER(name) = ?', [strtolower($helperName)])->first();

            if (!$helperData) {
                throw new \Exception("Helper not found in database: $helperName");
            }

            $addsalary = new EventSalary();
            $addsalary->eventId = $event->id;
            $addsalary->guideId = $helperData->id;
            $addsalary->is_chore = 1;
            $addsalary->approval_comment = '';
            $addsalary->normal_hours = 0;
            $addsalary->normal_night_hours = 0;
            $addsalary->holiday_hours = 0;
            $addsalary->holiday_night_hours = 0;
            $addsalary->approval_status = 0;
            $addsalary->save();

            // Mark this helper as processed
            $processedHelpers[] = $helperName;
        }
    }


    private function calculateHours(EventSalary $eventSalary)
    {
        $startDateTime = Carbon::parse($eventSalary->guide_start_time);
        $endDateTime = Carbon::parse($eventSalary->guide_end_time);

        Log::info("Calculating hours for event {$eventSalary->eventId}, guide {$eventSalary->guideId}");
        Log::info("Start time: {$startDateTime}, End time: {$endDateTime}");

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
        $eventSalary->normal_hours = $this->formatHoursMinutes(
            $normalHours + $nightHours + $holidayHours + $holidayNightHours,
            $normalMinutes + $nightMinutes + $holidayMinutes + $holidayNightMinutes
        );
        $eventSalary->normal_night_hours = $this->formatHoursMinutes(
            $nightHours + $holidayNightHours,
            $nightMinutes + $holidayNightMinutes
        );
        $eventSalary->holiday_hours = $this->formatHoursMinutes(
            $holidayHours + $holidayNightHours,
            $holidayMinutes + $holidayNightMinutes
        );
        $eventSalary->holiday_night_hours = $this->formatHoursMinutes(
            $holidayNightHours,
            $holidayNightMinutes
        );

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

    private function manageSaunaTours($event, $eventData)
    {
        try {
            $title = $event->name;
            $description = html_entity_decode(strip_tags($event->description));
            $description = preg_replace('/[\x{00A0}\x{200B}]/u', ' ', $description);

            // Process description to extract tour durations for each guide
            $lines = explode("\n", $description);
            $processedLines = [];
            foreach ($lines as $line) {
                $line = trim($line);
                if (substr_count($line, '-') > 15) {
                    break;
                }
                if (strpos($line, '*') === 0) {
                    continue;
                }
                $processedLines[] = $line;
            }
            $description = implode("\n", $processedLines);

            // Calculate longest duration for each guide from the description
            $guideDurations = [];
            $currentGuide = null;
            foreach (explode("\n", $description) as $line) {
                $line = trim($line);

                // Check for guide marker
                if (preg_match('/\b[Gg]\s*-\s*([A-Za-z][A-Za-z\s.-]+)\b/', $line, $matches)) {
                    $currentGuide = trim($matches[1]);
                    if (!isset($guideDurations[$currentGuide])) {
                        $guideDurations[$currentGuide] = [
                            'longest_duration' => 0,
                            'longest_tour_name' => ''
                        ];
                    }
                }

                // Process tour information for current guide
                if ($currentGuide) {
                    $tourName = trim(preg_replace('/\d+\+\d+\+\d+.*$/', '', $line));
                    if (trim($tourName) !== '') {
                        $tourDuration = DB::table('sauna_tour_durations')
                            ->where('tour', $tourName)
                            ->value('duration');

                        if ($tourDuration !== null && $tourDuration > $guideDurations[$currentGuide]['longest_duration']) {
                            $guideDurations[$currentGuide]['longest_duration'] = $tourDuration;
                            $guideDurations[$currentGuide]['longest_tour_name'] = $tourName;
                        }
                    }
                }
            }

            // Process guides using AI-provided data and calculated durations
            $processedGuides = [];
            foreach ($eventData['guides'] as $guideName => $guideData) {
                if (in_array($guideName, $processedGuides) || stripos($guideName, 'Office Key') !== false) {
                    continue;
                }

                $guideModel = TourGuide::whereRaw('LOWER(name) = ?', [strtolower($guideName)])->first();
                if (!$guideModel) {
                    throw new \Exception("Guide not found: $guideName");
                }

                if (!isset($guideDurations[$guideName]) || $guideDurations[$guideName]['longest_duration'] === 0) {
                    throw new \Exception("No valid sauna tour found for guide: $guideName");
                }

                $startDateTime = Carbon::parse($event->start_time);
                
                // Use AI-provided pickup time or office time
                $guideStartTime = $guideData['pickup_time']
                    ? Carbon::parse($startDateTime->format('Y-m-d') . ' ' . $guideData['pickup_time'])
                    : ($eventData['office_time']
                        ? Carbon::parse($startDateTime->format('Y-m-d') . ' ' . $eventData['office_time'])
                        : $startDateTime);

                if ($eventData['office_time'] && (!$guideData['pickup_time'] || $eventData['office_time'] < $guideData['pickup_time'])) {
                    $guideStartTime = Carbon::parse($startDateTime->format('Y-m-d') . ' ' . $eventData['office_time']);
                }

                $guideEndTime = $guideStartTime->copy()->addMinutes($guideDurations[$guideName]['longest_duration']);

                // Handle midnight crossing
                if ($guideEndTime->format('Y-m-d') !== $guideStartTime->format('Y-m-d')) {
                    $midnight = $guideStartTime->copy()->endOfDay()->addSecond();
                    $this->createGuideSalaryEntry($event->id, $guideModel->id, $guideStartTime, $midnight);
                    $this->createGuideSalaryEntry($event->id, $guideModel->id, $midnight, $guideEndTime);
                } else {
                    $this->createGuideSalaryEntry($event->id, $guideModel->id, $guideStartTime, $guideEndTime);
                }

                $processedGuides[] = $guideName;
            }

            // Process helpers from AI data
            foreach ($eventData['helpers'] as $helperName) {
                if (stripos($helperName, 'Office Key') !== false) {
                    continue;
                }

                $helperModel = TourGuide::whereRaw('LOWER(name) = ?', [strtolower($helperName)])->first();
                if (!$helperModel) {
                    throw new \Exception("Helper not found: $helperName");
                }

                $addsalary = new EventSalary();
                $addsalary->eventId = $event->id;
                $addsalary->guideId = $helperModel->id;
                $addsalary->is_chore = 1;
                $addsalary->approval_comment = '';
                $addsalary->normal_hours = 0;
                $addsalary->normal_night_hours = 0;
                $addsalary->holiday_hours = 0;
                $addsalary->holiday_night_hours = 0;
                $addsalary->approval_status = 0;
                $addsalary->save();
            }

            $event->status = 1;
            $event->save();

            Notification::where('eventId', $event->id)->delete();

            return "Sauna tours processed and stored successfully.";
        } catch (\Exception $e) {
            Log::error("Error in manageSaunaTours: " . $e->getMessage());
            $errorMessage = $e->getMessage();
            $this->createOrUpdateNotification($event->id, $errorMessage);
            throw new \Exception($errorMessage);
        }
    }

    /**
     * Parse a datetime string flexibly.
     *
     * @param string $dateTimeString
     * @return \Carbon\Carbon
     * @throws \Exception
     */
    private function parseFlexibleDateTime($dateTimeString)
    {
        $formats = [
            'd.m.Y H:i:s',
            'd.m.Y H:i',
            'Y-m-d H:i:s',
            'Y-m-d H:i',
            'd.m.Y',
            'Y-m-d',
        ];

        foreach ($formats as $format) {
            try {
                return Carbon::createFromFormat($format, $dateTimeString);
            } catch (\Exception $e) {
                continue;
            }
        }

        throw new \Exception("Unable to parse date/time: $dateTimeString");
    }

    private function manageChores($event, $eventData)
    {
        try {

            $title = $event->name;
            $processedGuides = [];
            $notFoundGuides = [];
            $guideFound = false;

            // Process guides
            foreach ($eventData['guides'] as $guideName => $guideData) {
                $guideFound = true;
                
                // Skip if we've already processed this guide
                if (in_array($guideName, $processedGuides)) {
                    continue;
                }

                $guide = TourGuide::whereRaw('LOWER(name) = ?', [strtolower($guideName)])->first();

                if (!$guide) {
                    $notFoundGuides[] = $guideName;
                    continue; // Continue processing other guides
                }

                $processedGuides[] = $guideName;

                // Create EventSalary entry for guide
                EventSalary::create([
                    'eventId' => $event->id,
                    'guideId' => $guide->id,
                    'normal_hours' => 0,
                    'normal_night_hours' => 0,
                    'holiday_hours' => 0,
                    'holiday_night_hours' => 0,
                    'is_chore' => 1,
                    'approval_comment' => ''
                ]);
            }

            // Process helpers
            foreach ($eventData['helpers'] as $helperName) {
                $guideFound = true;
                
                $helper = TourGuide::whereRaw('LOWER(name) = ?', [strtolower($helperName)])->first();

                if (!$helper) {
                    $notFoundGuides[] = $helperName;
                    continue; // Continue processing other helpers
                }

                // Create EventSalary entry for helper
                EventSalary::create([
                    'eventId' => $event->id,
                    'guideId' => $helper->id,
                    'normal_hours' => 0,
                    'normal_night_hours' => 0,
                    'holiday_hours' => 0,
                    'holiday_night_hours' => 0,
                    'is_chore' => 1,
                    'approval_comment' => ''
                ]);
            }

            if (!$guideFound) {
                $notFoundMessage = "No guides or helpers found for chore task '$title'.";
                $this->createOrUpdateNotification($event->id, $notFoundMessage);
                return "Chore task processing stopped due to no guides/helpers found.";
            }

            if (!empty($notFoundGuides)) {
                $notFoundMessage = "The following personnel were not found in the database: " . implode(', ', $notFoundGuides);
                throw new \Exception($notFoundMessage);
            }

            // Update event status to 1
            $event->status = 1;
            $event->save();

            // Delete any existing notifications for this event
            Notification::where('eventId', $event->id)->delete();

            return "Chore task processed successfully.";
        } catch (\Exception $e) {
            // Create or update a notification for this event
            $this->createOrUpdateNotification($event->id, $e->getMessage());

            throw $e; // Re-throw the exception to be caught in the calling method
        }
    }

    private function manageHuskySafari($event, $eventData)
    {
        {
            $title = $event->name;
            $startDateTime = Carbon::parse($event->start_time);
    
            $cleanedTitle = preg_replace('/^(N|D|EV|X)\s*/i', '', $title);
            $tourNames = array_map('trim', explode('+', $cleanedTitle));
    
    
    
            // Find the tour with the longest duration
            $longestDuration = 0;
            $longestTourName = '';
            foreach ($tourNames as $tourName) {
                // Try exact match first
                $tourDuration = DB::table('tour_durations')->where('tour', $tourName)->first();
                
                // If no exact match, try Levenshtein distance matching
                if (!$tourDuration) {
                    $tourDuration = $this->findTourDurationsForSnowyTrails($tourName,5);
                    if ($tourDuration) {
                        Log::info("Found tour using Levenshtein distance: Original: '$tourName', Matched: '{$tourDuration->tour}'");
                    } else {
                        throw new \Exception("Tour name not found in tour_durations table (including similar matches): $tourName");
                    }
                }

    
    
                if ($tourDuration->duration > $longestDuration) {
                    $longestDuration = $tourDuration->duration;
                    $longestTourName = $tourName;
                }
            }
    
            $durationInMinutes = $longestDuration;
    
            $isDaOrNa = false;
            if (preg_match('/^(DA|NA)\s+/i', $title)) {
                $isDaOrNa = true;
                // $durationInMinutes += 90;    
            }
    
    
            Log::info("Using tour duration of $durationInMinutes minutes from tour: $longestTourName");
    
            // Process guides
            $processedGuides = [];
            foreach ($eventData['guides'] as $guideName => $guideData) {
                // Skip if we've already processed this guide or if it contains "Office Key"
                if (in_array($guideName, $processedGuides) || stripos($guideName, 'Office Key') !== false) {
                    continue;
                }
    
                // Get location duration
                $locationDuration = 0;
                if(!$isDaOrNa){
                    $locationDuration = $this->getLocationDuration($guideData['pickup_location']);
                }
                $guideModel = TourGuide::whereRaw('LOWER(name) = ?', [strtolower($guideName)])->first();
    
                if (!$guideModel) {
                    throw new \Exception("Guide not found in database: $guideName");
                }
    
    
                // Initialize guide start time
                $guideStartTime = $guideData['pickup_time']
                    ? Carbon::parse($startDateTime->format('Y-m-d') . ' ' . $guideData['pickup_time'])
                    : ($eventData['office_time']
                        ? Carbon::parse($startDateTime->format('Y-m-d') . ' ' . $eventData['office_time'])
                        : $startDateTime);
    
    
                // If office time is earlier than pickup time, use office time as pickup time
                if ($eventData['office_time'] && (!$guideData['pickup_time'] || $eventData['office_time'] < $guideData['pickup_time'])) {
                    $guideStartTime = Carbon::parse($startDateTime->format('Y-m-d') . ' ' . $eventData['office_time']);
                }
    
    
                // Check if pickup location is office
                $isOfficePickup = stripos($guideData['pickup_location'], 'office') !== false;
    
                // Calculate initial adjusted start time
                $initialAdjustedStartTime = $isOfficePickup ? $guideStartTime->copy() : $guideStartTime->copy()->subMinutes($locationDuration);
    
                // Calculate duration to office
    
                if (!isset($eventData['office_time']) || $eventData['office_time'] === null) {
                    throw new \Exception("Office time is missing in the event description.");
                }
                
                $officeTime = Carbon::parse($startDateTime->format('Y-m-d') . ' ' . $eventData['office_time']);
                $durationToOffice = $isOfficePickup ? 0 : $initialAdjustedStartTime->diffInMinutes($officeTime);
    
                
    
                // Apply 30-minute deduction rule
                if ($durationToOffice > 30) {
                    $adjustedDuration = $durationToOffice - 30;
                } else {
                    $adjustedDuration = 0;
                }
    
    
                // Calculate final adjusted start time
                $adjustedGuideStartTime = $guideStartTime->copy()->subMinutes($adjustedDuration);
                Log::info("Adjusted duration 001: {$durationToOffice}");
                // Calculate end time
                $guideEndTime = $guideStartTime->copy()->addMinutes($durationInMinutes)->addMinutes($adjustedDuration);
    
                // Check if the tour spans across midnight
                if ($adjustedGuideStartTime->day != $guideEndTime->day) {
                    $firstDayEnd = $adjustedGuideStartTime->copy()->endOfDay()->addSecond();
                    $this->createGuideSalaryEntry($event->id, $guideModel->id, $adjustedGuideStartTime, $firstDayEnd);
                    $this->createGuideSalaryEntry($event->id, $guideModel->id, $firstDayEnd, $guideEndTime);
                } else {
                    $this->createGuideSalaryEntry($event->id, $guideModel->id, $adjustedGuideStartTime, $guideEndTime);
                }
    
                Log::info("Adjusted guide Start time 001: {$guideStartTime}");
                Log::info("Created EventSalary entries for guide $guideName:");
    
                // Mark this guide as processed
                $processedGuides[] = $guideName;
            }
    
            // Process helpers
            $processedHelpers = [];
            foreach ($eventData['helpers'] as $helperName) {
                // Skip if we've already processed this helper or if it contains "Office Key"
                if (in_array($helperName, $processedHelpers) || stripos($helperName, 'Office Key') !== false) {
                    continue;
                }
    
                $helperData = TourGuide::whereRaw('LOWER(name) = ?', [strtolower($helperName)])->first();
    
                if (!$helperData) {
                    throw new \Exception("Helper not found in database: $helperName");
                }
    
                $addsalary = new EventSalary();
                $addsalary->eventId = $event->id;
                $addsalary->guideId = $helperData->id;
                $addsalary->is_chore = 1;
                $addsalary->approval_comment = '';
                $addsalary->normal_hours = 0;
                $addsalary->normal_night_hours = 0;
                $addsalary->holiday_hours = 0;
                $addsalary->holiday_night_hours = 0;
                $addsalary->approval_status = 0;
                $addsalary->save();
    
                // Mark this helper as processed
                $processedHelpers[] = $helperName;
            }
        }
    }

    private function manageHighlightsOfRovaniemi($event,$eventData)
    {
        try {
            $startDateTime = Carbon::parse($event->start_time);
            $title = $event->name;

            $cleanedTitle = preg_replace('/^(N|D|EV|X)\s*/i', '', $title);

            // Split the title by '+' and trim each part
            $tourNames = array_map('trim', explode('+', $cleanedTitle));

            // Find the tour with the longest duration
            $longestDuration = 0;
            $longestTourName = '';
            foreach ($tourNames as $tourName) {
                // Try exact match first
                $tourDuration = DB::table('tour_durations')->where('tour', $tourName)->first();
                
                // If no exact match, try Levenshtein distance matching
                if (!$tourDuration) {
                    $tourDuration = $this->findTourWithLevenshtein($tourName, 10);
                    if ($tourDuration) {
                    } else {
                        throw new \Exception("Tour name not found in tour_durations table (including similar matches): $tourName");
                    }


                }

                if ($tourDuration->duration > $longestDuration) {
                    $longestDuration = $tourDuration->duration;
                    $longestTourName = $tourName;
                }
            }


            $durationInMinutes = $longestDuration;

            $isDaOrNa = false;  
            if (preg_match('/^(DA|NA)\s+/i', $title)) {
                $isDaOrNa = true;
                // $durationInMinutes += 90;
            }

            // Deduct an hour if it's Monday and not December
            if ($startDateTime->isMonday() && $startDateTime->month !== 12) {
                $durationInMinutes -= 60;
            }
            
            // Process guides
            $processedGuides = [];

            foreach ($eventData['guides'] as $guideName => $guideData) {
                // Skip if we've already processed this guide or if it contains "Office Key"
                if (in_array($guideName, $processedGuides) || stripos($guideName, 'Office Key') !== false) {
                    continue;
                }

                // Get location duration
                $locationDuration = 0;
                if(!$isDaOrNa){
                    $locationDuration = $this->getLocationDuration($guideData['pickup_location']);
                }
                $guideModel = TourGuide::whereRaw('LOWER(name) = ?', [strtolower($guideName)])->first();

                if (!$guideModel) {
                    throw new \Exception("Guide not found in database: $guideName");
                }

                // Initialize guide start time
                $guideStartTime = $guideData['pickup_time']
                    ? Carbon::parse($startDateTime->format('Y-m-d') . ' ' . $guideData['pickup_time'])
                    : ($eventData['office_time']
                        ? Carbon::parse($startDateTime->format('Y-m-d') . ' ' . $eventData['office_time'])
                        : $startDateTime);

                // If office time is earlier than pickup time, use office time as pickup time
                if ($eventData['office_time'] && (!$guideData['pickup_time'] || $eventData['office_time'] < $guideData['pickup_time'])) {
                    $guideStartTime = Carbon::parse($startDateTime->format('Y-m-d') . ' ' . $eventData['office_time']);
                }

                // Check if pickup location is office
                $isOfficePickup = stripos($guideData['pickup_location'], 'office') !== false;

                // Calculate initial adjusted start time
                $initialAdjustedStartTime = $isOfficePickup ? $guideStartTime->copy() : $guideStartTime->copy()->subMinutes($locationDuration);

                // Calculate duration to office
                $officeTime = Carbon::parse($startDateTime->format('Y-m-d') . ' ' . $eventData['office_time']);
                $durationToOffice = $isOfficePickup ? 0 : $initialAdjustedStartTime->diffInMinutes($officeTime);

                // Apply 30-minute deduction rule
                if ($durationToOffice > 30) {
                    $adjustedDuration = $durationToOffice - 30;
                } else {
                    $adjustedDuration = 0;
                }

                // Calculate final adjusted start time
                $adjustedGuideStartTime = $guideStartTime->copy()->subMinutes($adjustedDuration);
                Log::info("Adjusted duration 001: {$durationToOffice}");
                // Calculate end time
                $guideEndTime = $guideStartTime->copy()->addMinutes($durationInMinutes)->addMinutes($adjustedDuration);

                // Check if the tour spans across midnight
                if ($adjustedGuideStartTime->day != $guideEndTime->day) {
                    $firstDayEnd = $adjustedGuideStartTime->copy()->endOfDay()->addSecond();
                    $this->createGuideSalaryEntry($event->id, $guideModel->id, $adjustedGuideStartTime, $firstDayEnd);
                    $this->createGuideSalaryEntry($event->id, $guideModel->id, $firstDayEnd, $guideEndTime);
                } else {
                    $this->createGuideSalaryEntry($event->id, $guideModel->id, $adjustedGuideStartTime, $guideEndTime);
                }

                Log::info("Adjusted guide Start time 001: {$guideStartTime}");
                Log::info("Created EventSalary entries for guide $guideName:");

                // Mark this guide as processed
                $processedGuides[] = $guideName;
            }

            // Process helpers
            $processedHelpers = [];
            foreach ($eventData['helpers'] as $helperName) {
                // Skip if we've already processed this helper or if it contains "Office Key"
                if (in_array($helperName, $processedHelpers) || stripos($helperName, 'Office Key') !== false) {
                    continue;
                }

                $helperData = TourGuide::whereRaw('LOWER(name) = ?', [strtolower($helperName)])->first();

                if (!$helperData) {
                    throw new \Exception("Helper not found in database: $helperName");
                }

                $addsalary = new EventSalary();
                $addsalary->eventId = $event->id;
                $addsalary->guideId = $helperData->id;
                $addsalary->is_chore = 1;
                $addsalary->approval_comment = '';
                $addsalary->normal_hours = 0;
                $addsalary->normal_night_hours = 0;
                $addsalary->holiday_hours = 0;
                $addsalary->holiday_night_hours = 0;
                $addsalary->approval_status = 0;
                $addsalary->save();

                // Mark this helper as processed
                $processedHelpers[] = $helperName;
            }

            $event->status = 1;
            $event->save();

            Notification::where('eventId', $event->id)->delete();

            return "Highlights of Rovaniemi Day Tour processed and stored successfully.";
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            $this->createOrUpdateNotification($event->id, $errorMessage);
            throw new \Exception($errorMessage);
        }
    }


    //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    private function manageTransferService($event, $eventData)
    {
        try {
            // Find the tour duration
            $tourDuration = DB::table('tour_durations')->where('tour', 'Transfer Service')->first();
            if (!$tourDuration) {
                throw new \Exception("Transfer Service not found in tour_durations table");
            }

            $durationInMinutes = $tourDuration->duration;
            Log::info("Using tour duration of $durationInMinutes minutes for Transfer Service");

            // Process guides from eventData
            foreach ($eventData['guides'] as $guideName => $guideData) {
                if (stripos($guideName, 'Office Key') !== false) {
                    continue;
                }

                $personData = TourGuide::whereRaw('LOWER(name) = ?', [strtolower($guideName)])->first();
                if (!$personData) {
                    throw new \Exception("Guide not found in database: $guideName");
                }

                $startDateTime = Carbon::parse($event->start_time);
                $personStartTime = $guideData['pickup_time'] 
                    ? Carbon::parse($startDateTime->format('Y-m-d') . ' ' . $guideData['pickup_time'])
                    : ($eventData['office_time'] 
                        ? Carbon::parse($startDateTime->format('Y-m-d') . ' ' . $eventData['office_time'])
                        : $startDateTime);

                $personEndTime = $personStartTime->copy()->addMinutes($durationInMinutes);

                $this->createHelperSalaryEntry($event->id, $personData->id, $personStartTime, $personEndTime);

                Log::info("Created helper EventSalary entry for $guideName:");
                Log::info("Start time: {$personStartTime}, End time: {$personEndTime}");
            }

            // Process helpers from eventData
            foreach ($eventData['helpers'] as $helperName) {
                if (stripos($helperName, 'Office Key') !== false) {
                    continue;
                }

                $personData = TourGuide::whereRaw('LOWER(name) = ?', [strtolower($helperName)])->first();
                if (!$personData) {
                    throw new \Exception("Helper not found in database: $helperName");
                }

                $startDateTime = Carbon::parse($event->start_time);
                $personStartTime = $eventData['office_time']
                    ? Carbon::parse($startDateTime->format('Y-m-d') . ' ' . $eventData['office_time'])
                    : $startDateTime;

                $personEndTime = $personStartTime->copy()->addMinutes($durationInMinutes);

                $this->createHelperSalaryEntry($event->id, $personData->id, $personStartTime, $personEndTime);

                Log::info("Created helper EventSalary entry for $helperName:");
                Log::info("Start time: {$personStartTime}, End time: {$personEndTime}");
            }

            // Check if we have any personnel
            if (empty($eventData['guides']) && empty($eventData['helpers'])) {
                throw new \Exception("No personnel found for Transfer Service event: {$event->name}");
            }

            $event->status = 1;
            $event->save();

            Notification::where('eventId', $event->id)->delete();

            return "Transfer Service processed and stored successfully.";
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            $this->createOrUpdateNotification($event->id, $errorMessage);
            throw new \Exception($errorMessage);
        }
    }

    private function createHelperSalaryEntry($eventId, $personId, $startTime, $endTime)
    {
        $eventSalary = new EventSalary();
        $eventSalary->eventId = $eventId;
        $eventSalary->guideId = $personId;
        $eventSalary->guide_start_time = $startTime;
        $eventSalary->guide_end_time = $endTime;
        $eventSalary->is_chore = 1; // Mark as a chore/helper task
        $eventSalary->approval_comment = '';
        $eventSalary->normal_hours = 0;
        $eventSalary->normal_night_hours = 0;
        $eventSalary->holiday_hours = 0;
        $eventSalary->holiday_night_hours = 0;
        $eventSalary->approval_status = 0;
        $eventSalary->save();

        Log::info("Saved helper EventSalary for person {$personId}:", $eventSalary->toArray());
    }


    public function manualCalculation(Request $request)
    {
        $eventId = $request->input('eventId');
        $guides = $request->input('guides');

        $event = Event::findOrFail($eventId);
        DB::beginTransaction();

        try {
            // Delete existing EventSalary entries for this event

            foreach ($guides as $guideData) {

            EventSalary::where('eventId', $eventId)->where('guideId', $guideData['name'])->delete();

                $guide = TourGuide::findOrFail($guideData['name']); // 'name' now contains the guide's ID
                $startTime = $guideData['startTime'] ? Carbon::parse($guideData['startTime']) : null;
                $endTime = $guideData['endTime'] ? Carbon::parse($guideData['endTime']) : null;

                if ($startTime && $endTime) {
                    // Check if the tour spans across midnight
                    if ($startTime->day != $endTime->day) {
                        // Create first entry (for the start day)
                        $firstDayEnd = $startTime->copy()->endOfDay()->addSecond();
                        $this->createGuideSalaryEntry($event->id, $guide->id, $startTime, $firstDayEnd);

                        // Create second entry (for the end day)
                        $secondDayStart = $firstDayEnd->copy();
                        $this->createGuideSalaryEntry($event->id, $guide->id, $secondDayStart, $endTime);
                    } else {
                        // Create a single entry if the tour doesn't span across midnight
                        $this->createGuideSalaryEntry($event->id, $guide->id, $startTime, $endTime);
                    }

                    Log::info("Created EventSalary entries for guide {$guide->name}:");
                    Log::info("Start time: {$startTime}, End time: {$endTime}");
                } else {
                    // Both start and end times are empty, create a chore entry
                    $this->createChoreEntry($event->id, $guide->id);
                    Log::info("Created chore entry for guide {$guide->name}");
                }
            }

            // Remove the error notification
            Notification::where('eventId', $eventId)->delete();

            // Update event status to 1 and set is_edited to 1
            $event->status = 1;
            $event->is_edited = 1;
            $event->save();

            DB::commit();

            return redirect()->back()->with('success', 'Manual entry processed successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error in manual calculation for event $eventId: " . $e->getMessage());
            return redirect()->route('errors.log')->with('error', 'Error processing manual entry: ' . $e->getMessage());
        }
    }

    public function manualCalculationAjax(Request $request)
    {
        $eventId = $request->input('eventId');
        $guides = $request->input('guides');

        $event = Event::findOrFail($eventId);
        DB::beginTransaction();

        try {
            $updatedSalaries = [];
            
            foreach ($guides as $guideData) {
                EventSalary::where('eventId', $eventId)->where('guideId', $guideData['name'])->delete();

                $guide = TourGuide::findOrFail($guideData['name']); 
                $startTime = $guideData['startTime'] ? Carbon::parse($guideData['startTime']) : null;
                $endTime = $guideData['endTime'] ? Carbon::parse($guideData['endTime']) : null;

                if ($startTime && $endTime) {
                    // Check if the tour spans across midnight
                    if ($startTime->day != $endTime->day) {
                        $firstDayEnd = $startTime->copy()->endOfDay()->addSecond();
                        $firstEntry = $this->createGuideSalaryEntryAjax($event->id, $guide->id, $startTime, $firstDayEnd);
                        $updatedSalaries[] = $firstEntry;

                        $secondDayStart = $firstDayEnd->copy();
                        $secondEntry = $this->createGuideSalaryEntryAjax($event->id, $guide->id, $secondDayStart, $endTime);
                        $updatedSalaries[] = $secondEntry;
                    } else {
                        $entry = $this->createGuideSalaryEntryAjax($event->id, $guide->id, $startTime, $endTime);
                        $updatedSalaries[] = $entry;
                    }

                    Log::info("Created EventSalary entries for guide {$guide->name}:");
                    Log::info("Start time: {$startTime}, End time: {$endTime}");
                } else {
                    $choreEntry = $this->createChoreEntryAjax($event->id, $guide->id);
                    $updatedSalaries[] = $choreEntry;
                    Log::info("Created chore entry for guide {$guide->name}");
                }
            }

            // Remove the error notification
            Notification::where('eventId', $eventId)->delete();

            // Update event status
            $event->status = 1;
            $event->is_edited = 1;
            $event->save();

            // Format the updated salaries for response
            $formattedSalaries = collect($updatedSalaries)->map(function ($salary) {
                return [
                    'start_time' => $salary->guide_start_time ? $salary->guide_start_time->format('d.m.Y H:i') : 'N/A',
                    'end_time' => $salary->guide_end_time ? $salary->guide_end_time->format('d.m.Y H:i') : 'N/A',
                    'normal_hours' => $salary->normal_hours,
                    'holiday_hours' => $salary->holiday_hours,
                    'normal_night_hours' => $salary->normal_night_hours,
                    'holiday_night_hours' => $salary->holiday_night_hours
                ];
            });

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Manual entry processed successfully.',
                'data' => $formattedSalaries
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error in manual calculation for event $eventId: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error processing manual entry: ' . $e->getMessage()
            ], 500);
        }
    }

    // New AJAX-specific functions that return the created entries
    private function createGuideSalaryEntryAjax($eventId, $guideId, $startTime, $endTime)
    {
        $eventSalary = new EventSalary();
        $eventSalary->eventId = $eventId;
        $eventSalary->guideId = $guideId;
        $eventSalary->guide_start_time = $startTime;
        $eventSalary->guide_end_time = $endTime;
        $eventSalary->is_chore = 0;
        $eventSalary->approval_comment = '';

        $this->calculateHours($eventSalary);
        $eventSalary->approval_status = 1;
        $eventSalary->save();

        return $eventSalary;
    }

    private function createChoreEntryAjax($eventId, $guideId)
    {
        $eventSalary = new EventSalary();
        $eventSalary->eventId = $eventId;
        $eventSalary->guideId = $guideId;
        $eventSalary->is_chore = 1;
        $eventSalary->approval_comment = '';
        $eventSalary->normal_hours = 0;
        $eventSalary->normal_night_hours = 0;
        $eventSalary->holiday_hours = 0;
        $eventSalary->holiday_night_hours = 0;
        $eventSalary->approval_status = 0;
        $eventSalary->save();

        return $eventSalary;
    }


    // Add this new method to create a chore entry
    private function createChoreEntry($eventId, $guideId)
    {
        $eventSalary = new EventSalary();
        $eventSalary->eventId = $eventId;
        $eventSalary->guideId = $guideId;
        $eventSalary->is_chore = 1;
        $eventSalary->approval_comment = '';
        $eventSalary->normal_hours = 0;
        $eventSalary->normal_night_hours = 0;
        $eventSalary->holiday_hours = 0;
        $eventSalary->holiday_night_hours = 0;
        $eventSalary->approval_status = 0;
        $eventSalary->save();

        Log::info("Saved chore EventSalary for guide {$guideId}:", $eventSalary->toArray());
    }

    private function createGuideSalaryEntry($eventId, $guideId, $startTime, $endTime)
    {

        $eventSalary = new EventSalary();
        $eventSalary->eventId = $eventId;
        $eventSalary->guideId = $guideId;
        $eventSalary->guide_start_time = $startTime;
        $eventSalary->guide_end_time = $endTime;
        $eventSalary->is_chore = 0;
        $eventSalary->approval_comment = '';

        $this->calculateHours($eventSalary);

        // Check if the tour duration is longer than 10 hours
        $tourDuration = $endTime->diffInHours($startTime);
        $eventSalary->approval_status = 1;

        if ($tourDuration >= 10) {
            $eventSalary->is_guide_updated = 1;
            $eventSalary->guide_comment = "System Detected - More than 10 hours";
            $eventSalary->approval_status = 0;
        }

        $eventSalary->save();

        Log::info("Saved EventSalary for guide {$guideId}:", $eventSalary->toArray());
    }



    public function ignoreEvent(Request $request)
    {
        $eventId = $request->input('eventId');

        // Update the event status to 1 (ignored)
        $event = Event::find($eventId);
        if ($event) {
            $event->status = 1;
            $event->save();

            // Remove notifications related to this event
            Notification::where('eventId', $eventId)->delete();

            return response()->json(['success' => true, 'message' => 'Event ignored successfully.']);
        }

        return response()->json(['success' => false, 'message' => 'Event not found.'], 404);
    }

    public function calculateAll()
    {
        // Retrieve all events with status 0
        $events = Event::where('status', 0)->get();

        $errors = []; // Array to collect errors from notifications

        foreach ($events as $event) {
            // Call eventsalary function for each event and catch any exceptions or errors
            try {
                $this->eventsalary($event->id);
            } catch (\Exception $e) {
                // Log the error and continue processing other events
                $errors[] = [
                    'eventId' => $event->id,
                    'desc' => $e->getMessage(),
                ];
            }
        }

        // Fetch all notifications related to errors during the process
        $notifications = Notification::whereIn('eventId', $events->pluck('id'))->get();

        // Collect errors and associated eventId from notifications
        foreach ($notifications as $notification) {
            $errors[] = [
                'eventId' => $notification->eventId,
                'desc' => $notification->desc,
            ];
        }

        // Redirect to the error display page with the errors and event IDs
        if (!empty($errors)) {
            return redirect()->route('error.display')->with('errors', $errors);
        } else {
            return redirect()->back()->with('success', 'All events processed successfully.');
        }
    }

    public function errorDisplay()
    {
        // Retrieve errors from session
        $errors = session('errors', []);

        $guides = TourGuide::all(); // Fetch all guides from the database
        return view('notifications.errors', compact('errors', 'guides'))
            ->with('dateFormat', 'd.m.Y'); // Add this line
    }

    public function errorLog()
    {
        // Retrieve errors with additional event information
        $errors = Notification::join('events', 'notifications.eventId', '=', 'events.id')
            ->select('notifications.*', 'events.start_time as tour_date', 'events.name as event_name')
            ->orderBy('events.start_time', 'asc')
            ->get();

        $guides = TourGuide::all(); // Fetch all guides from the database
        return view('notifications.errors', compact('errors', 'guides'))
            ->with('dateFormat', 'd.m.Y');
    }

    public function errorFilter(Request $request)
    {
        $fromDate = $request->input('fromDate');
        $toDate = $request->input('toDate');

        // Fetch notifications with additional event information
        $errors = Notification::whereHas('event', function ($query) use ($fromDate, $toDate) {
            $query->whereBetween('start_time', [$fromDate, $toDate]);
        })->orderBy('created_at', 'desc')->get();

        // Pass the filtered notifications to the view
        $guides = TourGuide::all();
        return view('notifications.errors', compact('errors', 'guides'))
            ->with('dateFormat', 'd.m.Y'); // Add this line
    }


   


    /**
     * Create or update notification for a specific eventId
     */
    private function createOrUpdateNotification($eventId, $message)
    {
        // Fetch the event details
        $event = Event::findOrFail($eventId);

        // Parse the start_time string into a Carbon instance
        $startTime = Carbon::parse($event->start_time);

        // Prepare the detailed error message
        $detailedMessage = sprintf(
            "Date: %s\nTour Name: %s\nError: %s",
            $startTime->format('d.m.Y'), // Changed format here to remove time
            $event->name,
            $message
        );

        // Delete any existing notification for this event
        Notification::where('eventId', $eventId)->delete();

        // Create a new notification
        Notification::create([
            'eventId' => $eventId,
            'desc' => $detailedMessage,
        ]);
    }

    public function recalculate()
    {
        $eventSalaries = EventSalary::where('approval_status','!=', 0)->get();

        foreach ($eventSalaries as $salary) {

        $eventSalary = EventSalary::find($salary->id);


        $this->calculateHours($eventSalary);

        $eventSalary->save();

    }
    }

    private function findTourWithLevenshtein($searchTourName, $maxDistance = 3)
    {
        // Only remove specific terms in parentheses (FULL) or (no kids)
        $cleanedSearchName = preg_replace('/\s*\((FULL|no kids)\)/i', '', $searchTourName);
        // Remove times in format HH:MM
        $cleanedSearchName = preg_replace('/\s*\d{1,2}:\d{2}\s*/', '', $cleanedSearchName);
        $cleanedSearchName = preg_replace('/^[NDEX]\s+/', '', $cleanedSearchName); // Remove N, D, E, X prefix
        $cleanedSearchName = trim($cleanedSearchName); // Remove extra whitespace
        
        // Get all tour names from the database
        $allTours = DB::table('tour_durations')->pluck('tour')->toArray();
        
        $bestMatch = null;
        $shortestDistance = PHP_INT_MAX;
        
        foreach ($allTours as $tour) {
            $distance = levenshtein(strtolower($cleanedSearchName), strtolower($tour));
            
            if ($distance < $shortestDistance && $distance <= $maxDistance) {
                $shortestDistance = $distance;
                $bestMatch = $tour;
            }
        }
        
        if ($bestMatch) {
            return DB::table('tour_durations')->where('tour', $bestMatch)->first();
        }
        
        return null;
    }

    private function findTourDurationsForSnowyTrails($searchTourName, $maxDistance = 3)
    {
        // Extract the distance (km) from the search name
        preg_match('/(\d+)\s*Km/i', $searchTourName, $searchDistanceMatch);
        $searchDistance = $searchDistanceMatch[1] ?? null;
        
        // Extract the farm name from the search name
        preg_match('/-\s*([A-Za-z]+)/i', $searchTourName, $searchFarmMatch);
        $searchFarm = strtolower($searchFarmMatch[1] ?? '');
        
        // Clean the search tour name
        $cleanedSearchName = preg_replace([
            '/\s*\((FULL|no kids)\)/i',  // Remove (FULL) or (no kids)
            '/\s*\d{1,2}:\d{2}\s*/',     // Remove times
            '/^[NDEX]\s+/',              // Remove N, D, E, X prefix
            '/-\s*[A-Za-z]+\s*/',        // Remove farm name with hyphen
            '/\(\d+\s*Km\)/',           // Remove the km part
        ], '', $searchTourName);
        $cleanedSearchName = trim($cleanedSearchName);
        
        // Get all tours from database
        $allTours = DB::table('tour_durations')->pluck('tour')->toArray();
        
        $bestMatch = null;
        $shortestDistance = PHP_INT_MAX;
        
        foreach ($allTours as $tour) {
            // Extract distance from database tour
            preg_match('/(\d+)\s*Km/i', $tour, $tourDistanceMatch);
            $tourDistance = $tourDistanceMatch[1] ?? null;
            
            // Extract farm name from database tour
            preg_match('/\(([^)]+)husky\)/i', $tour, $tourFarmMatch);
            $tourFarm = strtolower($tourFarmMatch[1] ?? '');
            
            // Skip this tour if the distance doesn't match
            if ($searchDistance && $tourDistance && $searchDistance !== $tourDistance) {
                continue;
            }
            
            // Calculate base Levenshtein distance
            $distance = levenshtein(strtolower($cleanedSearchName), strtolower($tour));
            
            // Apply distance bonuses/penalties
            if ($searchDistance === $tourDistance) {
                $distance -= 10; // Increased priority for matching km
            }
            
            if ($searchFarm && strpos($tourFarm, $searchFarm) !== false) {
                $distance -= 8; // High priority for matching farm
            }
            
            if ($distance < $shortestDistance && $distance <= $maxDistance) {
                $shortestDistance = $distance;
                $bestMatch = $tour;
            }
        }
        
        if ($bestMatch) {
            Log::info("Husky Safari match found: Original: '$searchTourName', Matched: '$bestMatch'");
            return DB::table('tour_durations')->where('tour', $bestMatch)->first();
        }
        
        Log::warning("No matching Husky Safari tour found for: '$searchTourName'");
        return null;
    }

    public function calculateFromAI(Request $request)
    {
        try {
            $formData = $request->all();
            $eventId = $request->input('event_id');
            $errors = [];
            
            if (!$eventId) {
                throw new \Exception('Event ID is required');
            }
            
            $event = Event::find($eventId);
            
            if (!$event) {
                throw new \Exception('Event not found');
            }
            
            // Initialize the processed data structure
            $processedData = [
                'guides' => [],
                'helpers' => [],
                'office_time' => $formData['office_time']
            ];

            // Process guides data
            if (isset($formData['guides'])) {
                // Debug the incoming data
                \Log::info('Incoming guides data:', $formData['guides']);
                
                foreach ($formData['guides'] as $guideName => $guideDetails) {
                    // If guideDetails is a string, it might be encoded
                    if (is_string($guideDetails)) {
                        $guideDetails = json_decode($guideDetails, true);
                    }
                    
                    $processedData['guides'][$guideName] = [
                        'pickup_time' => $guideDetails['pickup_time'] ?? null,
                        'pickup_location' => $guideDetails['pickup_location'] ?? null,
                        'vehicle' => null,
                        'pax_details' => []
                    ];
                }
            }

            // Process helpers
            if (isset($formData['helpers'])) {
                // If helpers is a string (JSON), decode it
                if (is_string($formData['helpers'])) {
                    $formData['helpers'] = json_decode($formData['helpers'], true);
                }
                $processedData['helpers'] = array_values(array_filter($formData['helpers']));
            }

            try {
                $result = $this->eventSalary($eventId, $processedData);
            } catch (\Exception $e) {
                // Capture the error message
                $errors[] = $e->getMessage();
            }

            // If there were any errors, include them in the response
            if (!empty($errors)) {
                return response()->json([
                    'success' => false,
                    'errors' => implode(', ', $errors),
                    'message' => 'Some calculations could not be completed'
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Calculation completed successfully'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function addExtraHours(Request $request)
    {
        $request->validate([
            'salary_id' => 'required|exists:event_salaries,id',
            'extra_time' => 'required'
        ]);

        $salary = EventSalary::findOrFail($request->salary_id);
        
        if (!$salary->guide_end_time) {
            return redirect()->back()->with('error', 'No end time found to update');
        }

        $preserveIds = [];
        list($hours, $minutes) = explode(':', $request->extra_time);
        
        $startTime = Carbon::parse($salary->guide_start_time);
        $originalEndTime = Carbon::parse($salary->guide_end_time);
        $endTime = $originalEndTime->copy()
            ->addHours(intval($hours))
            ->addMinutes(intval($minutes));

        // Get the user_id from tour_guides table
        $guide = TourGuide::find($salary->guideId);
        if (!$guide || !$guide->user_id) {
            return redirect()->back()->with('error', 'Guide user information not found');
        }

        // Log the adjustment with the correct user_id
        SalaryTimeAdjustment::create([
            'event_id' => $salary->eventId,
            'guide_id' => $guide->user_id, // Use the user_id from tour_guides table
            'adjusted_by' => auth()->id(),
            'original_end_time' => $originalEndTime,
            'added_time' => $request->extra_time,
            'new_end_time' => $endTime,
            'note' => $request->note ?? null
        ]);

        // If start and end dates are different, split into two entries
        if ($startTime->format('Y-m-d') != $endTime->format('Y-m-d')) {
            // First entry - from start time to midnight of start date
            $midnightTime = $startTime->copy()->startOfDay()->addDay(); // Get midnight (00:00) of next day
            
            $firstEntry = new EventSalary();
            $firstEntry->eventId = $salary->eventId;
            $firstEntry->guideId = $salary->guideId;
            $firstEntry->guide_start_time = $startTime;
            $firstEntry->guide_end_time = $midnightTime;
            $firstEntry->approval_status = 1;
            $this->calculateHours($firstEntry);
            $firstEntry->save();

            $preserveIds[] = $firstEntry->id;

            // Second entry - from midnight to end time
            $secondEntry = new EventSalary();
            $secondEntry->eventId = $salary->eventId;
            $secondEntry->guideId = $salary->guideId;
            $secondEntry->guide_start_time = $midnightTime;
            $secondEntry->guide_end_time = $endTime;
            $secondEntry->approval_status = 1;
            $this->calculateHours($secondEntry);
            $secondEntry->save();

            $preserveIds[] = $secondEntry->id;
        } else {
            // Same day entry
            $newEntry = new EventSalary();
            $newEntry->eventId = $salary->eventId;
            $newEntry->guideId = $salary->guideId;
            $newEntry->guide_start_time = $startTime;
            $newEntry->guide_end_time = $endTime;
            $newEntry->approval_status = 1;
            $this->calculateHours($newEntry);
            $newEntry->save();

            $preserveIds[] = $newEntry->id;
        }

        // Delete all old entries for this event and guide
        EventSalary::where('eventId', $salary->eventId)
            ->where('guideId', $salary->guideId)
            ->whereNotIn('id', $preserveIds)
            ->delete();

        return redirect()->back()->with('success', 'Hours updated successfully');
    }

    
    public function addExtraHoursAjax(Request $request)
    {
        try {
            $request->validate([
                'salary_id' => 'required|exists:event_salaries,id',
                'extra_time' => 'required'
            ]);

            $salary = EventSalary::findOrFail($request->salary_id);
            
            if (!$salary->guide_end_time) {
                return response()->json([
                    'success' => false,
                    'message' => 'No end time found to update'
                ], 400);
            }

            $preserveIds = [];
            list($hours, $minutes) = explode(':', $request->extra_time);
            
            $startTime = Carbon::parse($salary->guide_start_time);
            $originalEndTime = Carbon::parse($salary->guide_end_time);
            $endTime = $originalEndTime->copy()
                ->addHours(intval($hours))
                ->addMinutes(intval($minutes));

            // Get the user_id from tour_guides table
            $guide = TourGuide::find($salary->guideId);
            if (!$guide || !$guide->user_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Guide user information not found'
                ], 400);
            }

            // Log the adjustment
            SalaryTimeAdjustment::create([
                'event_id' => $salary->eventId,
                'guide_id' => $guide->user_id,
                'adjusted_by' => auth()->id(),
                'original_end_time' => $originalEndTime,
                'added_time' => $request->extra_time,
                'new_end_time' => $endTime,
                'note' => $request->note ?? null
            ]);

            $updatedEntries = [];

            // If start and end dates are different, split into two entries
            if ($startTime->format('Y-m-d') != $endTime->format('Y-m-d')) {
                $midnightTime = $startTime->copy()->startOfDay()->addDay();
                
                $firstEntry = new EventSalary();
                $firstEntry->eventId = $salary->eventId;
                $firstEntry->guideId = $salary->guideId;
                $firstEntry->guide_start_time = $startTime;
                $firstEntry->guide_end_time = $midnightTime;
                $firstEntry->approval_status = 1;
                $this->calculateHours($firstEntry);
                $firstEntry->save();

                $preserveIds[] = $firstEntry->id;
                $updatedEntries[] = $firstEntry;

                $secondEntry = new EventSalary();
                $secondEntry->eventId = $salary->eventId;
                $secondEntry->guideId = $salary->guideId;
                $secondEntry->guide_start_time = $midnightTime;
                $secondEntry->guide_end_time = $endTime;
                $secondEntry->approval_status = 1;
                $this->calculateHours($secondEntry);
                $secondEntry->save();

                $preserveIds[] = $secondEntry->id;
                $updatedEntries[] = $secondEntry;
            } else {
                $newEntry = new EventSalary();
                $newEntry->eventId = $salary->eventId;
                $newEntry->guideId = $salary->guideId;
                $newEntry->guide_start_time = $startTime;
                $newEntry->guide_end_time = $endTime;
                $newEntry->approval_status = 1;
                $this->calculateHours($newEntry);
                $newEntry->save();

                $preserveIds[] = $newEntry->id;
                $updatedEntries[] = $newEntry;
            }

            // Delete all old entries for this event and guide
            EventSalary::where('eventId', $salary->eventId)
                ->where('guideId', $salary->guideId)
                ->whereNotIn('id', $preserveIds)
                ->delete();

            // Format the updated entries for response
            $formattedEntries = collect($updatedEntries)->map(function ($entry) {
                return [
                    'start_time' => $entry->guide_start_time ? $entry->guide_start_time->format('d.m.Y H:i') : 'N/A',
                    'end_time' => $entry->guide_end_time ? $entry->guide_end_time->format('d.m.Y H:i') : 'N/A',
                    'normal_hours' => $entry->normal_hours,
                    'holiday_hours' => $entry->holiday_hours,
                    'normal_night_hours' => $entry->normal_night_hours,
                    'holiday_night_hours' => $entry->holiday_night_hours
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Extra hours added successfully',
                'data' => $formattedEntries
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error adding extra hours: ' . $e->getMessage()
            ], 500);
        }
    }

    public function timeAdjustments()
    {
        $adjustments = SalaryTimeAdjustment::with(['event', 'guide', 'adjuster'])
            ->latest()
            ->paginate(20);

        return view('reports.time-adjustments', compact('adjustments'));
    }

    public function calculatePickupDuration(Request $request)
    {
        $location = $request->input('location');
        $locationDuration = $this->getLocationDuration($location);
        
        return response()->json([
            'duration' => $locationDuration
        ]);
    }

}
