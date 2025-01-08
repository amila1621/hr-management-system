<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use App\Models\Event;
use App\Models\OperationsProcessTours;

class ClaudeAIController extends Controller
{
    private const MODEL = 'claude-3-haiku-20240307';
    private const MAX_TOKENS = 4096;
    private const TEMPERATURE = 0.0;

    public function getForm()
    {
        return view('claudeai');
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
- Seat capacity
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
                "capacity": "string"
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
                "capacity": "string"
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
                "capacity": "8 Seats"
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
                "capacity": "8 Seats"
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

Extract ALL available information, but NEVER include people without proper prefixes in guides or helpers sections, even if they are described as helping.
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
            $formattedResponse['helpers'] = array_map(function($helper) {
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

    public function analyzeEvent(Request $request)
    {
        try {
            $eventId = $request->input('event_id');
            
            // Fetch the event details from your database
            $event = Event::findOrFail($eventId);
            
            // Get the event description
            $description = $event->description;
            
            // Use the existing getResponse method
            $response = $this->getResponse(new Request(['description' => $description]));
            
            // Convert the response to array
            $eventData = json_decode($response->getContent(), true);

            // Add the original description to the response
            $eventData['original_description'] = $event->original_description;

            return response()->json([
                'success' => true,
                'data' => $eventData
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    

}