<?php

namespace App\Http\Controllers;

use App\Models\OperationsDayTours;
use App\Models\Vehicles;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\OperationsProcessTours;
use Spatie\GoogleCalendar\Event;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class OperationsController extends Controller
{
    private const MODEL = 'claude-3-haiku-20240307';
    private const MAX_TOKENS = 4096;
    private const TEMPERATURE = 0.0;

    public function processEventInternally($eventId)
    {
        try {
            // Fetch the event details from operations_process_tours
            $event = OperationsProcessTours::findOrFail($eventId);

            // Get the event description
            $description = $event->description;

            // Use the existing getResponse method but simulate a request
            $simulatedRequest = new Request(['description' => $description]);
            $response = $this->getResponse($simulatedRequest);

            // Convert the response to array
            $eventData = json_decode($response->getContent(), true);


            return [
                'success' => true,
                'data' => $eventData
            ];
        } catch (\Exception $e) {
            // Return error as array instead of JSON response
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function getResponse(Request $request)
    {
        set_time_limit(120);
        $description = $request->input('description');

        try {

            $client = new Client([
                'timeout' => 120,  // Guzzle timeout also set to 120 seconds
                'connect_timeout' => 120
            ]);


            $response = $this->makeOpenAIRequest($description);

            $formattedResponse = $this->processAPIResponse($response);

            if (!$this->validateResponse($formattedResponse)) {
                throw new \Exception('Invalid response format received from API');
            }

            $cleanedResponse = $this->cleanPrefixes($formattedResponse);
            return response()->json($cleanedResponse);
        } catch (\Exception $e) {
            Log::error('Event processing error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to process event data: ' . $e->getMessage()
            ], 500);
        }
    }


    private function makeOpenAIRequest($description)
    {
        $client = new Client();

        $response = $client->post('https://api.anthropic.com/v1/messages', [
            'headers' => [
                'x-api-key' => env('CLAUDE_API_KEY'),
                'anthropic-version' => '2023-06-01',
                'Content-Type'  => 'application/json',
            ],
            'json' => [
                'model' => self::MODEL,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $description
                    ]
                ],
                'system' => $this->getSystemPrompt(),
                'temperature' => self::TEMPERATURE,
                'max_tokens' => self::MAX_TOKENS,
            ],
        ]);

        return $response;
    }

    private function getSystemPrompt()
    {
        return <<<EOT
You are a tour event data extraction system. Your primary task is to extract and structure detailed event information from tour descriptions.

CRITICAL PREFIX RULES (HIGHEST PRIORITY):
1. ONLY process people as guides if their name has "G-" or "g-" prefix
2. ONLY process people as helpers if their name has "H-" or "h-" prefix
3. STRICTLY IGNORE any person without these exact prefixes
4. If a name appears in the text without G- or H- prefix, DO NOT include them in guides or helpers
5. Words like "helps", "assists", "support" DO NOT make someone a helper - only the H- prefix does

Example:
- "G-John helps with bus" -> Include as GUIDE (has G- prefix)
- "H-Mary helps with pickup" -> Include as helper (has H- prefix)
- "Mikhail helps with bus" -> IGNORE (no prefix)
- "Peter assists with pickup" -> IGNORE (no prefix)
- "Sarah helps guides" -> IGNORE (no prefix)

CRITICAL REQUIREMENTS:
1. You MUST always output valid JSON matching the exact structure specified
2. All required fields MUST be present, even if empty
3. For large descriptions, ensure complete processing of all data
4. Never truncate or omit required fields
5. Always validate JSON structure before responding

Follow these exact rules for data extraction:

1. EVENT INFORMATION
Extract:
- Event type (D/N/X/Z prefix)
- Full event name
- Date
- Duration
- Status (active/cancelled)

2. GUIDE INFORMATION
For names prefixed with "G-" or "g-":
- Extract name (remove prefix)
- Match with vehicle details
- Find earliest pickup time
- Collect all pickup locations
- Group all associated pax details

3. HELPER INFORMATION
For names prefixed with "H-" or "h-":
- Extract name (remove prefix)
- Match with vehicle details
- Extract assigned tasks

4. VEHICLE DETAILS
Extract:
- Registration number
- Total pax count:
  CRITICAL: For each line with "pax paid by":
  1. Extract first number before "+"
  2. Add to running total
  3. Include ALL entries for the guide
  4. Double-check sum before output
- Type if specified
- Current assignment (guide/helper)

5. TIMING INFORMATION
Extract:
- Earliest pickup time per guide
- All pickup locations (remove P- prefix)
- Office time
- Activity schedules
- Duration details

6. SPECIAL REQUIREMENTS
Extract:
- Dietary restrictions
- Language requirements
- Mobility needs
- Special equipment needs
- Transfer requirements

Required Output Structure:
{
    "event_info": {
        "type": "string", // D/N/X/Z
        "name": "string",
        "date": "YYYY-MM-DD",
        "duration": "string",
        "status": "string" // active/cancelled
    },
    "guides": {
        "guide_name": {
            "vehicle": {
                "registration": "string",
                "pax": "integer"
            },
            "pickup_time": "HH:mm",
            "pickup_location": "string",
        }
    },
    "helpers": [
        {
            "name": "string",
            "vehicle": {
                "registration": "string",
                "pax": "integer"
            },
            "task": "string"
        }
    ],
    "office_time": "HH:mm",
    "additional_notes": ["string"],
    "special_requirements": ["string"]
}

Example Input 1:
Event: D Trip to Santa Claus Village
Start: 2024-10-10 00:00:00
Description:
G-Teodora / LZP 974 (8 Seats)
1+0+0 pax paid by NUT / 09:00 / P-Aurora Nest / John Smith / +1234567890
3+0+0 pax paid by NUT / 09:00 / P-Aurora Nest / Jane Smith / +1234567890
*office 10:00
*Santa Reindeer Visit - 10:30

Example Output 1:
{
    "event_info": {
        "type": "D",
        "name": "Trip to Santa Claus Village",
        "date": "2024-10-10",
        "duration": "1 day",
        "status": "active"
    },
    "guides": {
        "Teodora": {
            "vehicle": {
                "registration": "LZP 974",
                "pax": "4"
            },
            "pickup_time": "09:00",
            "pickup_location": "Aurora Nest",
        }
    },
    "helpers": [],
    "office_time": "10:00",
    "additional_notes": ["Santa Reindeer Visit - 10:30"],
    "special_requirements": []
}

Example Input 2:
Event: N Hunting Northern Lights
Start: 2024-10-10 00:00:00
Description:
G-Isaac / AZE 379 (8 Seats)
2+0+0 pax paid by LINK / 20:50 / P-Office / Jane Doe / +9876543210 / *vegetarian
H-Kevin / Help with equipment
*office 21:00

Example Output 2:
{
    "event_info": {
        "type": "N",
        "name": "Hunting Northern Lights",
        "date": "2024-10-10",
        "duration": "1 day",
        "status": "active"
    },
    "guides": {
        "Isaac": {
            "vehicle": {
                "registration": "AZE 379",
                "pax": "2"
            },
            "pickup_time": "20:50",
            "pickup_location": "Office",
            
        }
    },
    "helpers": [
        {
            "name": "Kevin",
            "vehicle": null,
            "task": "Help with equipment"
        }
    ],
    "office_time": "21:00",
    "additional_notes": [],
    "special_requirements": ["vegetarian"]
}

Important Rules:
1. PREFIX RULE IS ABSOLUTE - No exceptions for any reason
2. Remove prefixes in the final output
3. Keep only the earliest pickup time for each guide
4. Include all special requirements and notes
5. Maintain original vehicle registration numbers
6. Process all customer contact information
7. Include payment sources
8. Mark cancelled events (X prefix) in status
9. Process internal tasks (Z prefix) appropriately
10. Track all dietary and language requirements
11. The word "helps" or similar words DO NOT make someone a helper
12. PAX COUNTING MUST BE PRECISE - Add all individual pax numbers for accurate total
13. Each pax entry must be counted separately and summed for the final total
14. Double-check pax sums before including in response

Extract ALL available information, but NEVER include people without proper prefixes in guides or helpers sections, even if they are described as helping.

PAX COUNTING RULES (HIGHEST PRIORITY):
1. ALWAYS count each pax entry separately
2. For each line containing "pax paid by", extract the numbers before "+":
   - Format is always "X+Y+Z pax paid by"
   - Add the first number (X) to the total
3. Sum ALL numbers for final total
4. Verify count by listing each number found:
   Example:
   1+0+0 → add 1
   2+0+0 → add 2
   1+0+0 → add 1
   Total = 1 + 2 + 1 = 4

4. VEHICLE DETAILS
Extract:
- Registration number
- Total pax count:
  CRITICAL: For each line with "pax paid by":
  1. Extract first number before "+"
  2. Add to running total
  3. Include ALL entries for the guide
  4. Double-check sum before output
- Type if specified
- Current assignment (guide/helper)

Example pax counting:
Input:
G-John / ABC 123
1+0+0 pax paid by CARD / pickup1
2+0+0 pax paid by CASH / pickup2
1+0+0 pax paid by LINK / pickup3

Must calculate:
Line 1: 1 (from 1+0+0)
Line 2: 2 (from 2+0+0)
Line 3: 1 (from 1+0+0)
Total = 1 + 2 + 1 = 4

Result should show:
"pax": "4"

FINAL VERIFICATION STEPS:
1. Count every line with "pax paid by"
2. Extract first number before "+" from each line
3. Sum all numbers
4. Verify total matches pax count in output
EOT;
    }

    private function processAPIResponse($response)
    {
        try {
            // Get response body
            $body = $response->getBody()->getContents();

            // First level decode
            $data = json_decode($body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Failed to decode response body: ' . json_last_error_msg());
            }

            // Extract the JSON string from the text field
            if (!isset($data['content'][0]['text'])) {
                throw new \Exception('Invalid response structure');
            }

            $text = $data['content'][0]['text'];

            // Extract JSON data from the text by finding the first '{' and last '}'
            $start = strpos($text, '{');
            $end = strrpos($text, '}');

            if ($start === false || $end === false) {
                throw new \Exception('Could not find valid JSON in response');
            }

            $jsonStr = substr($text, $start, $end - $start + 1);

            // Second level decode - the actual event data
            $eventData = json_decode($jsonStr, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Failed to decode event data: ' . json_last_error_msg());
            }


            // Process guides
            if (isset($eventData['guides'])) {
                $processedGuides = [];
                foreach ($eventData['guides'] as $name => $details) {
                    $processedGuides[$name] = !empty($details) ? $details : [
                        'vehicle' => null,
                        'pickup_time' => null,
                        'pickup_location' => null
                    ];
                }
                $eventData['guides'] = $processedGuides;
            }

            // Process helpers
            if (isset($eventData['helpers']) && is_array($eventData['helpers'])) {
                $processedHelpers = [];
                foreach ($eventData['helpers'] as $helper) {
                    if (isset($helper['name'])) {
                        $processedHelpers[] = $helper;
                    }
                }
                $eventData['helpers'] = $processedHelpers;
            }

            return $eventData;
        } catch (\Exception $e) {
            Log::error('API Response Processing Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    private function cleanPrefixes($formattedResponse)
    {
        // Clean guide names and their data
        $cleanedGuides = [];
        foreach ($formattedResponse['guides'] as $guideName => $details) {
            $cleanName = preg_replace('/^[Gg]-/', '', $guideName);

            // Clean pickup locations
            $details['pickup_location'] = preg_replace('/^[Pp]-/', '', $details['pickup_location']);

            // Clean vehicle details if needed
            if (isset($details['vehicle']['registration'])) {
                $details['vehicle']['registration'] = trim($details['vehicle']['registration']);
            }

            // Clean pax details
            if (isset($details['pax_details'])) {
                foreach ($details['pax_details'] as &$pax) {
                    if (isset($pax['pickup_location'])) {
                        $pax['pickup_location'] = preg_replace('/^[Pp]-/', '', $pax['pickup_location']);
                    }
                }
            }

            $cleanedGuides[$cleanName] = $details;
        }
        $formattedResponse['guides'] = $cleanedGuides;

        // Clean helper names
        if (isset($formattedResponse['helpers']) && is_array($formattedResponse['helpers'])) {
            $formattedResponse['helpers'] = array_map(function ($helper) {
                $helper['name'] = preg_replace('/^[Hh]-/', '', $helper['name']);
                if (isset($helper['vehicle']) && isset($helper['vehicle']['registration'])) {
                    $helper['vehicle']['registration'] = trim($helper['vehicle']['registration']);
                }
                return $helper;
            }, $formattedResponse['helpers']);
        }

        return $formattedResponse;
    }

    private function validateResponse($response)
    {
        if (!is_array($response)) {
            Log::error('Response is not an array:', ['response' => $response]);
            return false;
        }

        $requiredKeys = ['event_info', 'guides', 'helpers', 'office_time'];
        foreach ($requiredKeys as $key) {
            if (!array_key_exists($key, $response)) {
                Log::error("Missing required key in response: $key", ['response' => $response]);
                return false;
            }
        }

        // Validate event_info
        $eventRequired = ['type', 'name', 'date', 'duration', 'status'];
        foreach ($eventRequired as $field) {
            if (!isset($response['event_info'][$field])) {
                Log::error("Missing required event_info field: $field", ['event_info' => $response['event_info'] ?? null]);
                return false;
            }
        }

        // Validate guides structure
        foreach ($response['guides'] as $guide => $details) {
            if (empty($details)) {
                $response['guides'][$guide] = [
                    'vehicle' => null,
                    'pickup_time' => '',
                    'pickup_location' => ''
                ];
                continue;
            }
        }

        return true;
    }


    public function fetchEvents(Request $request)
    {
        $calendarId = env('GOOGLE_CALENDAR_ID');

        // Set date range to today
        $startDateTime = Carbon::today()->startOfDay();
        $endDateTime = Carbon::today()->endOfDay();

        $tourDate = Carbon::today()->format('d.m.Y');

        // Adjust endDateTime to exclude the next day
        $adjustedEndDateTime = $endDateTime->copy()->subSecond();

        // Fetch Google events
        $googleEvents = Event::get($startDateTime, $adjustedEndDateTime, [], $calendarId);

        $ignorePrefixes = ['EV', 'TL', 'DR', 'LINK', 'VP', 'TM', 'TR', 'MT', 'X', 'DJ'];
        $fetchedEventIds = [];

        foreach ($googleEvents as $googleEvent) {
            $eventStartTime = $googleEvent->startDateTime ?? $googleEvent->startDate;

            // Skip events with ignored prefixes
            $skipEvent = false;
            foreach ($ignorePrefixes as $prefix) {
                if (stripos($googleEvent->name, $prefix . ' ') === 0) {
                    $skipEvent = true;
                    break;
                }
            }

            if ($skipEvent) {
                // Delete from operations_process_tours if exists
                OperationsProcessTours::where('event_id', $googleEvent->id)->delete();
                continue;
            }

            // Store the original description without modifications
            $originalDescription = $googleEvent->description;

            // Process the description to make it more readable
            $description = $originalDescription;
            $description = html_entity_decode($description);

            // Replace common HTML tags with readable alternatives
            $description = str_replace(['<br>', '<br/>', '<br />'], "\n", $description);
            $description = str_replace(['<p>', '</p>'], "\n", $description);
            $description = str_replace(['<b>', '</b>', '<strong>', '</strong>'], "", $description);
            $description = str_replace(['<i>', '</i>', '<em>', '</em>'], "", $description);
            $description = str_replace(['&nbsp;'], " ", $description);

            // Remove any remaining HTML tags
            $description = strip_tags($description);

            // Clean up extra whitespace and line breaks
            $description = preg_replace('/\n\s+/', "\n", $description); // Remove spaces at start of lines
            $description = preg_replace('/[ \t]+/', ' ', $description); // Replace multiple spaces with single space
            $description = preg_replace('/\n{3,}/', "\n\n", $description); // Replace multiple line breaks with double line break
            $description = trim($description); // Remove leading and trailing whitespace

            // Check if the event exists and if descriptions have changed
            $processTour = OperationsProcessTours::where('event_id', $googleEvent->id)->first();
            $descriptionChanged = !$processTour ||
                $processTour->original_description !== $originalDescription ||
                $processTour->description !== $description ||
                $processTour->tour_name !== $googleEvent->name;

            if (!$processTour || $descriptionChanged) {
                $processTour = OperationsProcessTours::updateOrCreate(
                    ['event_id' => $googleEvent->id],
                    [
                        'tour_name' => $googleEvent->name,
                        'tour_date' => $tourDate,
                        'description' => $description,
                        'original_description' => $originalDescription,
                        'status' => 0 // Reset status when content changes
                    ]
                );
            }

            // Add the processed event ID to the fetchedEventIds array
            $fetchedEventIds[] = $googleEvent->id;
        }

        // Delete local records that are no longer in Google Calendar
        OperationsProcessTours::whereNotIn('event_id', $fetchedEventIds)->delete();

        $this->createSheet();
        // return redirect()->route('vehicles.index')->with('success', 'Tour descriptions updated successfully.');
    }

    public function createSheet()
    {
        $events = OperationsProcessTours::where('status', 0)->get();

        foreach ($events as $event) {
            try {
                $eventData = $this->processEventInternally($event->id);
                $event = OperationsProcessTours::find($event->id);

                // Check if event_info exists in the response
                if (!isset($eventData['data']['event_info'])) {
                    // Try to extract any available guides from the data
                    $guides = $eventData['data']['guides'] ?? [];
                    
                    if (!empty($guides)) {
                        // If we have guides, create entries for each one with available data
                        foreach ($guides as $guideName => $guide) {
                            OperationsDayTours::updateOrCreate(
                                [
                                    'event_id' => $event->id,
                                    'guide' => $guideName
                                ],
                                [
                                    'tour_name' => $event->tour_name,
                                    'tour_date' => $event->tour_date,
                                    'duration' => 0,
                                    'vehicle' => $guide['vehicle']['registration'] ?? 'NA',
                                    'pickup_time' => $guide['pickup_time'] ?? 'NA',
                                    'pickup_location' => $guide['pickup_location'] ?? 'NA',
                                    'pax' => $guide['vehicle']['pax'] ?? 0,
                                    'available' => 0,
                                    'remark' => 'Partial Data Available',
                                ]
                            );
                        }
                    } else {
                        // If no guides found, create a single NA entry
                        OperationsDayTours::updateOrCreate(
                            [
                                'event_id' => $event->id,
                                'guide' => 'NA'
                            ],
                            [
                                'tour_name' => $event->tour_name,
                                'tour_date' => $event->tour_date,
                                'duration' => 0,
                                'vehicle' => 'NA',
                                'pickup_time' => 'NA',
                                'pickup_location' => 'NA',
                                'pax' => 0,
                                'available' => 0,
                                'remark' => 'Not Enough Data',
                            ]
                        );
                    }
                }

                // Update the status to 1 after successful processing
                $event->update(['status' => 1]);

            } catch (\Exception $e) {
                Log::error('Error processing event', [
                    'event_id' => $event->id,
                    'error' => $e->getMessage(),
                    'eventData' => $eventData ?? null
                ]);
                
                // Create entry with NA values for any exception
                OperationsDayTours::updateOrCreate(
                    ['event_id' => $event->id],
                    [
                        'tour_name' => $event->tour_name,
                        'tour_date' => $event->tour_date,
                        'duration' => 0,
                        'vehicle' => 'NA',
                        'pickup_time' => 'NA', 
                        'pickup_location' => 'NA',
                        'pax' => 0,
                        'guide' => 'NA',
                        'available' => 0,
                        'remark' => 'Error: ' . $e->getMessage(),
                    ]
                );

                // Even in case of error, update the status to 1 as we've created an NA entry
                $event->update(['status' => 1]);
            }
        }
    }


    public function manageVehicles()
    {
        $vehicles = Vehicles::all();
        return view('operations.manage-vehicles', compact('vehicles'));
    }

    public function storeVehicle(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:Sedan,SUV,Van,Bus',
            'number' => 'required|string|max:255|unique:vehicles,number',
            'number_of_seats' => 'required|integer|min:1',
            'number_of_baby_seats' => 'required|integer|min:0',
            'status' => 'required',
        ]);

        Vehicles::create($request->all());

        return redirect()->back()->with('success', 'Vehicle added successfully');
    }

    public function editVehicle(Vehicles $vehicle)
    {
        return view('operations.edit-vehicle', compact('vehicle'));
    }

    public function updateVehicle(Request $request, Vehicles $vehicle)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:Sedan,SUV,Van,Bus',
            'number' => 'required|string|max:255|unique:vehicles,number,' . $vehicle->id,
            'number_of_seats' => 'required|integer|min:1',
            'number_of_baby_seats' => 'required|integer|min:0',
            'status' => 'required',
        ]);

        $vehicle->update($request->all());

        return redirect()->route('vehicles.index')->with('success', 'Vehicle updated successfully');
    }

    public function destroyVehicle(Vehicles $vehicle)
    {
        $vehicle->delete();
        return redirect()->back()->with('success', 'Vehicle deleted successfully');
    }

    public function checkSheet()
    {
        // Get today's date in the format stored in the database
        $today = Carbon::today()->format('d.m.Y');

        // Get events with status 0 and today's date
        $events = OperationsDayTours::where('tour_date', $today)
            ->get();

        return view('operations.check-sheet', compact('events'));
    }
}
