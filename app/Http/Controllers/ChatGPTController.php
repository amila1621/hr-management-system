<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class ChatGPTController extends Controller
{
    private const MODEL = 'gpt-4o-mini';
    private const MAX_TOKENS = 5000;
    private const TEMPERATURE = 0.0;

    public function getForm()
    {
        return view('chatgpt');
    }

    public function getResponse(Request $request)
    {
        $description = $request->input('description');
        
        try {
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
        
        $response = $client->post('https://api.openai.com/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . env('OPENAI_API_KEY'),
                'Content-Type'  => 'application/json',
            ],
            'json' => [
                'model' => self::MODEL,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $this->getSystemPrompt()
                    ],
                    [
                        'role' => 'user',
                        'content' => $description
                    ]
                ],
                'temperature' => self::TEMPERATURE,
                'max_tokens' => self::MAX_TOKENS,
            ],
        ]);

        return $response;
    }

    private function getSystemPrompt()
    {
        return <<<EOT
You are a tour event data extraction system. Your task is to extract and structure detailed event information from tour descriptions.

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

5. PAX (PASSENGER) DETAILS
Extract for each booking:
- Passenger count (format: X+Y+Z)
- Customer name
- Contact information
- Payment source
- Special requirements
- Group by assigned guide

6. TIMING INFORMATION
Extract:
- Earliest pickup time per guide
- All pickup locations (remove P- prefix)
- Office time
- Activity schedules
- Duration details

7. SPECIAL REQUIREMENTS
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
            "pax_details": [
                {
                    "count": "string", // X+Y+Z format
                    "customer_name": "string",
                    "contact": "string",
                    "payment_source": "string",
                    "special_requirements": ["string"]
                }
            ]
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
            "pax_details": [
                {
                    "count": "1+0+0",
                    "customer_name": "John Smith",
                    "contact": "+1234567890",
                    "payment_source": "NUT",
                    "special_requirements": []
                }
            ]
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
            "pax_details": [
                {
                    "count": "2+0+0",
                    "customer_name": "Jane Doe",
                    "contact": "+9876543210",
                    "payment_source": "LINK",
                    "special_requirements": ["vegetarian"]
                }
            ]
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
1. Always remove G-, g-, H-, h-, P-, p- prefixes in the final output
2. Keep only the earliest pickup time for each guide
3. Aggregate all pax details for each guide
4. Include all special requirements and notes
5. Maintain original vehicle registration numbers
6. Process all customer contact information
7. Include payment sources
8. Mark cancelled events (X prefix) in status
9. Process internal tasks (Z prefix) appropriately
10. Track all dietary and language requirements

Extract ALL available information, even if patterns vary. Include uncertain data in additional_notes.
EOT;
    }

    private function processAPIResponse($response)
    {
        $data = json_decode($response->getBody()->getContents(), true);
        return json_decode($data['choices'][0]['message']['content'], true);
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
            return false;
        }

        $requiredKeys = ['event_info', 'guides', 'helpers', 'office_time'];
        foreach ($requiredKeys as $key) {
            if (!array_key_exists($key, $response)) {
                Log::warning("Missing required key in response: $key");
                return false;
            }
        }

        // Validate event_info
        if (!isset($response['event_info']['type']) || 
            !isset($response['event_info']['name']) || 
            !isset($response['event_info']['date'])) {
            Log::warning("Missing required event_info fields");
            return false;
        }

        // Validate guides structure
        foreach ($response['guides'] as $guide => $details) {
            if (!isset($details['vehicle']) || 
                !isset($details['pickup_time']) || 
                !isset($details['pickup_location']) || 
                !isset($details['pax_details'])) {
                Log::warning("Invalid guide details structure for guide: $guide");
                return false;
            }
        }

        return true;
    }
}