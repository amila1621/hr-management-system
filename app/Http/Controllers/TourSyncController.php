<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

class TourSyncController extends Controller
{
    private $apiBaseUrl = 'https://nut-console.site/api/export/tour-data';

    public function fetchTours(Request $request)
    {
        try {
            $request->validate([
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date'
            ]);

            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');

            // Fetch data from external API
            $response = Http::timeout(30)->get($this->apiBaseUrl, [
                'start_date' => $startDate,
                'end_date' => $endDate
            ]);

            if (!$response->successful()) {
                throw new Exception('Failed to fetch tours from external API: ' . $response->status());
            }

            $data = $response->json();

            if (!$data['success']) {
                throw new Exception('API returned error: ' . ($data['message'] ?? 'Unknown error'));
            }

            $tours = $data['data'];
            $syncedCount = 0;
            $skippedCount = 0;
            $errors = [];

            foreach ($tours as $tour) {
                try {
                    // Check if event already exists based on event_id and start_time
                    $existingEvent = Event::where('event_id', $tour['event_id'])
                        ->where('start_time', $tour['start_time'])
                        ->first();

                    if ($existingEvent) {
                        $skippedCount++;
                        continue;
                    }

                    // Create new event
                    $event = new Event();
                    $event->event_id = $tour['event_id'];
                    $event->name = $tour['name'];
                    $event->description = $tour['description'];
                    $event->original_description = $tour['original_description'] ?? $tour['description'];
                    $event->start_time = Carbon::parse($tour['start_time']);
                    $event->end_time = Carbon::parse($tour['end_time']);
                    $event->condition = $tour['condition'];
                    $event->status = $tour['status'] ?? 0;
                    $event->is_edited = $tour['is_edited'] ?? 0;
                    $event->created_at = now();
                    $event->updated_at = now();
                    
                    $event->save();
                    $syncedCount++;

                    Log::info("Synced tour: {$tour['name']} (ID: {$tour['event_id']})");

                } catch (Exception $e) {
                    $errors[] = "Error syncing tour ID {$tour['event_id']}: " . $e->getMessage();
                    Log::error("Error syncing tour ID {$tour['event_id']}: " . $e->getMessage());
                }
            }

            $message = "Tour sync completed. Synced: {$syncedCount}, Skipped: {$skippedCount}";
            
            if (!empty($errors)) {
                $message .= ". Errors: " . count($errors);
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'synced' => $syncedCount,
                'skipped' => $skippedCount,
                'errors' => $errors,
                'total_fetched' => count($tours)
            ]);

        } catch (Exception $e) {
            Log::error('Tour sync error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error fetching tours: ' . $e->getMessage()
            ], 500);
        }
    }

    public function syncToursPage()
    {
        return view('tours.sync');
    }
}