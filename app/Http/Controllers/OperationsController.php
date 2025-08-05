<?php

namespace App\Http\Controllers;

use App\Models\OperationsDayTours;
use App\Models\OperationsProcessTours;
use App\Models\Vehicles;
use App\Models\TourGuide; 
use Illuminate\Http\Request;
use Carbon\Carbon;
use Spatie\GoogleCalendar\Event;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OperationsController extends Controller
{
    private const MODEL = 'claude-3-haiku-20240307';
    private const MAX_TOKENS = 4096;
    private const TEMPERATURE = 0.0;

    public function processEventInternally($eventId)
    {
        try {
            Log::info('Processing event', ['event_id' => $eventId]);
            
            $event = OperationsProcessTours::findOrFail($eventId);
            $simulatedRequest = new Request(['description' => $event->description]);
            $response = $this->getResponse($simulatedRequest);
            
            return [
                'success' => true,
                'data' => json_decode($response->getContent(), true)
            ];
        } catch (\Exception $e) {
            Log::error('Event processing failed', [
                'event_id' => $eventId,
                'error' => $e->getMessage()
            ]);
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function getResponse(Request $request)
    {
        set_time_limit(120);
        try {
            $description = $request->input('description');
            $response = $this->makeOpenAIRequest($description);
            $formattedResponse = $this->processAPIResponse($response);

            if (!$this->validateResponse($formattedResponse)) {
                throw new \Exception('Invalid response format from API');
            }

            return response()->json($formattedResponse);
        } catch (\Exception $e) {
            Log::error('Response processing error', ['error' => $e->getMessage()]);
            return response()->json([
                'error' => 'Failed to process event data: ' . $e->getMessage()
            ], 500);
        }
    }

    private function makeOpenAIRequest($description)
{
    try {
        $client = new Client([
            'timeout' => 120,
            'connect_timeout' => 120
        ]);

        $response = $client->post('https://api.anthropic.com/v1/messages', [
            'headers' => [
                'x-api-key' => env('CLAUDE_API_KEY'),
                'anthropic-version' => '2023-06-01',
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'model' => self::MODEL,
                'messages' => [
                    ['role' => 'user', 'content' => $description]
                ],
                'system' => $this->getSystemPrompt(),
                'temperature' => self::TEMPERATURE,
                'max_tokens' => self::MAX_TOKENS,
            ],
        ]);

        // Parse the JSON response
        $responseData = json_decode($response->getBody()->getContents(), true);
        
        // Return the content from the first message
        if (isset($responseData['content']) && is_array($responseData['content'])) {
            return $responseData['content'];
        }

        throw new \Exception('Invalid API response structure');
    } catch (\Exception $e) {
        Log::error('API Request Error', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        throw new \Exception('Failed to make API request: ' . $e->getMessage());
    }
}

    private function getSystemPrompt()
    {
        return <<<EOT
You are a tour event data extraction system analyzing event descriptions. Extract information in this exact format:

Required Structure:
{
    "event_info": {
        "tour_name": "string",
        "tour_date": "YYYY-MM-DD",
        "duration": "string (from *office time)"
    },
    "guides": {
        "guide_name": {
            "vehicle": {
                "registration": "string (after / in guide line)",
                "pax": integer (sum of first numbers in X+Y+Z format)
            },
            "pickup_time": "HH:mm (earliest pickup time)",
            "pickup_location": "string (after P-)",
            "available": "string (from remarks)",
            "remark": "string (all * lines)"
        }
    }
}

Rules:
1. GUIDES: Only extract lines starting with G- (ignore H- or other prefixes)
2. VEHICLES: Extract registration after the / in guide line
3. LOCATIONS: Only use locations with P- prefix, remove P- in output
4. PAX: For patterns like "4+0+0", only use first number
5. TIME: Use 24-hour format (HH:mm)
6. REMARKS: Combine all lines starting with * (remove *)
7. DURATION: Get from *office time mentions

Example Input:
"20.01.2025
G-Alissa /MOF 008 (8 Seats)
4+0+0 pax paid by EV / 10:00 / P-Office
*office 10:00
*lunch at christmas house 12:00"

Example Output:
{
    "event_info": {
        "tour_date": "2025-01-20",
        "duration": "10:00"
    },
    "guides": {
        "Alissa": {
            "vehicle": {
                "registration": "MOF 008",
                "pax": 4
            },
            "pickup_time": "10:00",
            "pickup_location": "Office",
            "available": "12:00",
            "remark": "office 10:00, lunch at christmas house 12:00"
        }
    }
}

Important:
- Only include guides with G- prefix
- Only include locations with P- prefix
- Sum all pax numbers for each guide
- Include all remarks starting with *
EOT;
    }

    public function fetchEvents(Request $request)
    {
        try {
            $startDateTime = Carbon::today()->startOfDay();
            $endDateTime = Carbon::today()->endOfDay();
            $tourDate = Carbon::today()->format('Y-m-d');

            $googleEvents = Event::get($startDateTime, $endDateTime, [], env('GOOGLE_CALENDAR_ID'));
            $ignorePrefixes = ['EV', 'TL', 'DR', 'LINK', 'VP', 'TM', 'TR', 'MT', 'X', 'DJ'];
            $fetchedEventIds = [];

            foreach ($googleEvents as $googleEvent) {
                if ($this->shouldSkipEvent($googleEvent, $ignorePrefixes)) {
                    OperationsProcessTours::where('event_id', $googleEvent->id)->delete();
                    continue;
                }

                $description = $this->processEventDescription($googleEvent->description);
                $this->updateOrCreateProcessTour($googleEvent, $description, $tourDate);
                $fetchedEventIds[] = $googleEvent->id;
            }

            OperationsProcessTours::whereNotIn('event_id', $fetchedEventIds)->delete();
            $this->createSheet();
            $this->updateTourDurations();

            return response()->json(['message' => 'Events processed successfully']);
        } catch (\Exception $e) {
            Log::error('Error fetching events', ['error' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    private function shouldSkipEvent($event, $ignorePrefixes)
    {
        foreach ($ignorePrefixes as $prefix) {
            if (stripos($event->name, $prefix . ' ') === 0) {
                return true;
            }
        }
        return false;
    }

    private function processEventDescription($description)
    {
        $description = html_entity_decode($description);
        $description = str_replace(['<br>', '<br/>', '<br />', '<p>', '</p>'], "\n", $description);
        $description = str_replace(['<b>', '</b>', '<strong>', '</strong>', '<i>', '</i>'], '', $description);
        $description = strip_tags($description);
        $description = preg_replace('/\n\s+/', "\n", $description);
        $description = preg_replace('/[ \t]+/', ' ', $description);
        $description = preg_replace('/\n{3,}/', "\n\n", $description);
        return trim($description);
    }

    private function updateOrCreateProcessTour($event, $description, $tourDate)
    {
        return OperationsProcessTours::updateOrCreate(
            ['event_id' => $event->id],
            [
                'tour_name' => $event->name,
                'tour_date' => $tourDate,
                'description' => $description,
                'original_description' => $event->description,
                'status' => 0
            ]
        );
    }

    public function createSheet()
    {
        $events = OperationsProcessTours::where('status', 0)->get();

        foreach ($events as $event) {
            try {
                $eventData = $this->processEventInternally($event->id);
                

                if (!$eventData['success'] || !isset($eventData['data'])) {
                    throw new \Exception('Invalid event data structure');
                }

                $data = $eventData['data'];
                



                // Delete existing entries for this event
                OperationsDayTours::where('event_id', $event->id)->delete();

                if (isset($data['guides']) && is_array($data['guides']) && !empty($data['guides'])) {

                    foreach ($data['guides'] as $guideName => $guideData) {

                        // Verify guide exists with G- prefix
                        // Pattern matching both "+G- Name" and "G-Name" formats
                        if (preg_match('/(^|\s)[+]?G-\s*' . preg_quote($guideName, '/') . '(\s*\/|\s|$)/', $event->description)) {
                            // Extract office time for duration
                            $duration = '';
                    
                            OperationsDayTours::create([
                                'event_id' => $event->id,
                                'tour_date' => $event->tour_date,
                                'duration' => $duration ?: ($data['event_info']['duration'] ?? 'NA'),
                                'tour_name' => $event->tour_name,
                                'vehicle' => $guideData['vehicle']['registration'] ?? 'NA',
                                'pickup_time' => $guideData['pickup_time'] ?? 'NA',
                                'pickup_location' => $guideData['pickup_location'] ?? 'NA',
                                'pax' => $guideData['vehicle']['pax'] ?? 0,
                                'guide' => $guideName,
                                'available' => '',
                                'remark' => $this->formatRemarks($guideData['remark'] ?? '')
                            ]);
                        } else {
                        }
                    }
                } else {
                    $this->createDefaultEntry($event);
                }

                $event->update(['status' => 1]);

            } catch (\Exception $e) {
                Log::error('Error processing event', [
                    'event_id' => $event->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                
                $this->createErrorEntry($event, $e->getMessage());
                $event->update(['status' => 1]);
            }
        }
    }


    public function updateTourDurations(){
        $operationsDayTours = OperationsDayTours::where('is_duration_updated', '0')->get();
        
        foreach($operationsDayTours as $operationDayTour){
            $tourName = $operationDayTour->tour_name;   
            $firstLetter = substr($tourName, 0, 1);
            $tourNameWithoutPrefix = substr($tourName, 1); // Remove first character

            //then we have to figure out whether this is a day tour or night tour
            $isDay = false;
            $isNight = false;
            
            if ($firstLetter === 'D') {
                $isDay = true;
            } elseif ($firstLetter === 'N') {
                $isNight = true;
            } else {
                $isDay = true;
            }

            // Search without the D/N prefix
            $tourDuration = DB::table('tour_durations')->where('tour', $tourNameWithoutPrefix)->first();
                
            // If no exact match, try Levenshtein distance matching
            if (!$tourDuration) {
                $tourDuration = $this->findTourWithLevenshtein($tourNameWithoutPrefix, 10);
                if (!$tourDuration) {
                    $tourDurationTime = 'NA';
                } else {
                    $tourDurationTime = $tourDuration->duration;
                }
            }

            //have to update available time

            $pickupTime = $operationDayTour->pickup_time;
            
            if($pickupTime == 'NA' || $tourDurationTime == 'NA'){
                $available = 'NA';
            } else {
                $pickupTime = Carbon::parse($pickupTime);
                $available = $pickupTime->addMinutes($tourDurationTime)->format('H:i');
            }



            $opDay = OperationsDayTours::find($operationDayTour->id);

            $opDay->duration = $tourDurationTime;
            $opDay->day_night = $isDay ? 'Day' : 'Night';
            $opDay->is_duration_updated = 1;
            $opDay->available = $available;
            $opDay->save();

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

    private function createDefaultEntry($event)
    {
        OperationsDayTours::create([
            'event_id' => $event->id,
            'tour_date' => Carbon::parse($event->tour_date)->format('Y-m-d'),
            'duration' => 'NA',
            'tour_name' => $event->tour_name,
            'vehicle' => 'NA',
            'pickup_time' => 'NA',
            'pickup_location' => 'NA',
            'pax' => 0,
            'guide' => 'NA',
            'available' => '',
            'remark' => 'No guide information available'
        ]);
    }

    private function createErrorEntry($event, $errorMessage)
    {
        OperationsDayTours::create([
            'event_id' => $event->id,
            'tour_date' => Carbon::parse($event->tour_date)->format('Y-m-d'),
            'duration' => 'NA',
            'tour_name' => $event->tour_name,
            'vehicle' => 'NA',
            'pickup_time' => 'NA',
            'pickup_location' => 'NA',
            'pax' => 0,
            'guide' => 'NA',
            'available' => '',
            'remark' => 'Error: ' . $errorMessage
        ]);
    }

    private function formatRemarks($remarks)
    {
        if (empty($remarks)) {
            return '';
        }
        
        if (is_array($remarks)) {
            $remarks = array_filter($remarks, function($line) {
                return !empty(trim($line));
            });
            return implode("\n", $remarks);
        }
        
        return $remarks;
    }

    public function apiEvents(Request $request)
    {
        try {
            $date = $request->input('date', Carbon::today()->format('Y-m-d'));
            
            $tours = OperationsDayTours::where('tour_date', $date)
                ->orderBy('pickup_time')
                ->get()
                ->groupBy('tour_name')
                ->map(function ($tourGroup) {
                    return [
                        'duration' => $tourGroup->first()->duration ?: '#N/A',
                        'tour_name' => $tourGroup->first()->tour_name,
                        'vehicles' => $tourGroup->map(function ($tour) {
                            return [
                                'registration' => $tour->vehicle,
                                'pickup_time' => $tour->pickup_time,
                                'pickup_location' => $tour->pickup_location,
                                'pax' => $tour->pax,
                                'guide' => $tour->guide,
                                'available' => $tour->available ?: '#N/A',
                                'remark' => $tour->remark
                            ];
                        })->values()->toArray()
                    ];
                })->values();

            return response()->json([
                'success' => true,
                'data' => $tours
            ]);
        } catch (\Exception $e) {
            Log::error('Error in apiEvents', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    private function validateResponse($response)
    {
        if (!is_array($response)) {
            return false;
        }

        if (!isset($response['event_info'], $response['guides'])) {
            return false;
        }

        foreach ($response['guides'] as $guideName => $guide) {
            if (!isset($guide['vehicle'], $guide['pickup_time'], $guide['pickup_location'])) {
                return false;
            }
        }

        return true;
    }

//     private function processAPIResponse($response)
// {
//     try {
//         $body = $response->getBody()->getContents();
//         $data = json_decode($body, true);

//         if (json_last_error() !== JSON_ERROR_NONE) {
//             throw new \Exception('Failed to decode API response: ' . json_last_error_msg());
//         }

//         if (!isset($data['content'][0]['text'])) {
//             Log::error('Invalid API response structure', ['response' => $data]);
//             throw new \Exception('Invalid response structure from API');
//         }

//         $text = $data['content'][0]['text'];
        
//         // Find the JSON part in the text
//         if (preg_match('/\{.*\}/s', $text, $matches)) {
//             $jsonStr = $matches[0];
//             $extractedData = json_decode($jsonStr, true);
            
//             if (json_last_error() !== JSON_ERROR_NONE) {
//                 throw new \Exception('Failed to decode extracted data: ' . json_last_error_msg());
//             }

//             // Validate the structure
//             if (!isset($extractedData['event_info']) || !isset($extractedData['guides'])) {
//                 Log::error('Invalid data structure', ['extracted' => $extractedData]);
//                 throw new \Exception('Invalid data structure in API response');
//             }

//             return $extractedData;
//         }

//         throw new \Exception('No valid JSON found in API response');
//     } catch (\Exception $e) {
//         Log::error('API Response Processing Error', [
//             'error' => $e->getMessage(),
//             'trace' => $e->getTraceAsString(),
//             'response' => $body ?? null
//         ]);
//         throw $e;
//     }
// }


private function processAPIResponse($response)
{
    try {
        // Since $response is already the content array, we don't need to decode it
        if (!is_array($response) || empty($response)) {
            throw new \Exception('Invalid API response structure');
        }

        // Extract the text from the first message
        if (!isset($response[0]['text'])) {
            Log::error('Invalid API response structure', ['response' => $response]);
            throw new \Exception('Invalid response structure from API');
        }

        $text = $response[0]['text'];
        
        // Find the JSON part in the text
        if (preg_match('/\{.*\}/s', $text, $matches)) {
            $jsonStr = $matches[0];
            $extractedData = json_decode($jsonStr, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Failed to decode extracted data: ' . json_last_error_msg());
            }

            // Validate the structure
            if (!isset($extractedData['event_info']) || !isset($extractedData['guides'])) {
                Log::error('Invalid data structure', ['extracted' => $extractedData]);
                throw new \Exception('Invalid data structure in API response');
            }

            return $extractedData;
        }

        throw new \Exception('No valid JSON found in API response');
    } catch (\Exception $e) {
        Log::error('API Response Processing Error', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'response' => $response
        ]);
        throw $e;
    }
}

    public function checkSheet()
    {
        try {
            $today = Carbon::today()->format('Y-m-d');
            $tours = OperationsDayTours::where('tour_date', $today)
                ->orderBy('pickup_time')
                ->get();


            $guides = TourGuide::orderBy('name','asc')->get(); 
            $vehicles = Vehicles::all();    
            
            return view('operations.check-sheet', [
                'tours' => $tours,
                'date' => $today,
                'guides' => $guides,
                'vehicles' => $vehicles
            ]);


        } catch (\Exception $e) {
            Log::error('Error loading check sheet', ['error' => $e->getMessage()]);
            return back()->with('error', 'Error loading check sheet: ' . $e->getMessage());
        }
    }

    public function exportDaySheet(Request $request)
    {
        try {
            $date = $request->input('date', Carbon::today()->format('Y-m-d'));
            
            $tours = OperationsDayTours::where('tour_date', $date)
                ->orderBy('pickup_time')
                ->get();
            
            return view('operations.export-sheet', [
                'tours' => $tours,
                'date' => $date
            ]);
        } catch (\Exception $e) {
            Log::error('Error exporting day sheet', ['error' => $e->getMessage()]);
            return back()->with('error', 'Error exporting day sheet: ' . $e->getMessage());
        }
    }

    public function dashboard()
    {
        try {
            $today = Carbon::today()->format('Y-m-d');
            
            $stats = [
                'total_tours' => OperationsDayTours::where('tour_date', $today)
                    ->select('tour_name')
                    ->distinct()
                    ->count(),
                'total_vehicles' => OperationsDayTours::where('tour_date', $today)
                    ->where('vehicle', '!=', 'NA')
                    ->select('vehicle')
                    ->distinct()
                    ->count(),
                'total_guides' => OperationsDayTours::where('tour_date', $today)
                    ->where('guide', '!=', 'NA')
                    ->select('guide')
                    ->distinct()
                    ->count(),
                'total_passengers' => OperationsDayTours::where('tour_date', $today)
                    ->sum('pax')
            ];

            $upcomingTours = OperationsDayTours::where('tour_date', '>=', $today)
                ->orderBy('tour_date')
                ->orderBy('pickup_time')
                ->take(10)
                ->get();

            return view('operations.dashboard', [
                'stats' => $stats,
                'upcomingTours' => $upcomingTours
            ]);
        } catch (\Exception $e) {
            Log::error('Error loading dashboard', ['error' => $e->getMessage()]);
            return back()->with('error', 'Error loading dashboard: ' . $e->getMessage());
        }
    }

    // Vehicle Management Methods
    public function manageVehicles()
    {
        $vehicles = Vehicles::orderBy('name')->get();
        return view('operations.manage-vehicles', compact('vehicles'));
    }

    public function storeVehicle(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:Sedan,SUV,Van,Bus',
            'number' => 'required|string|max:255|unique:vehicles,number',
            'number_of_seats' => 'required|integer|min:1',
            'number_of_baby_seats' => 'required|integer|min:0',
            'status' => 'required|boolean'
        ]);

        try {
            Vehicles::create($validated);
            return redirect()->route('vehicles.index')->with('success', 'Vehicle added successfully');
        } catch (\Exception $e) {
            Log::error('Error storing vehicle', ['error' => $e->getMessage()]);
            return back()->with('error', 'Error adding vehicle: ' . $e->getMessage())->withInput();
        }
    }

    public function updateVehicle(Request $request, Vehicles $vehicle)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:Sedan,SUV,Van,Bus',
            'number' => 'required|string|max:255|unique:vehicles,number,' . $vehicle->id,
            'number_of_seats' => 'required|integer|min:1',
            'number_of_baby_seats' => 'required|integer|min:0',
            'status' => 'required|boolean'
        ]);

        try {
            $vehicle->update($validated);
            return redirect()->route('vehicles.index')->with('success', 'Vehicle updated successfully');
        } catch (\Exception $e) {
            Log::error('Error updating vehicle', ['error' => $e->getMessage()]);
            return back()->with('error', 'Error updating vehicle: ' . $e->getMessage())->withInput();
        }
    }

    public function destroyVehicle(Vehicles $vehicle)
    {
        try {
            $vehicle->delete();
            return redirect()->route('vehicles.index')->with('success', 'Vehicle deleted successfully');
        } catch (\Exception $e) {
            Log::error('Error deleting vehicle', ['error' => $e->getMessage()]);
            return back()->with('error', 'Error deleting vehicle: ' . $e->getMessage());
        }
    }
}