<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Event;
use Carbon\Carbon;
use DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncDatabasesController extends Controller
{
    public function handleSync()
    {
        try {
            $this->syncUsers();         // Add this first
            $this->syncTourGuides();    // Then guides
            $this->syncEvents();        // Then events
            $this->syncEventSalaries(); // Finally salaries
            
            
            // Make HTTP request to another URL
            // $response = Http::get('https://nuthr.nordictravels.tech/hr-breaks');
            
            // if ($response->successful()) {
            //     return response()->json([
            //         'message' => 'Sync completed and external URL called successfully',
            //         'status' => 200
            //     ]);
            // }

               
            
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    private function syncEvents()
    {
        try {
            // Get all events from primary that need syncing
            $events = DB::table('events')
                ->whereNull('deleted_at')
                ->whereBetween('updated_at', [
                    Carbon::parse('2024-07-30'),
                    Carbon::now()
                ])
                ->get();

            Log::info("Found {$events->count()} events to sync");
            $inserted = 0;
            $updated = 0;

            foreach ($events as $event) {
                try {
                    $data = (array) $event;
                    
                    // Check if event exists in secondary
                    $exists = DB::connection('secondary')
                        ->table('events')
                        ->where('id', $event->id)
                        ->exists();

                    if ($exists) {
                        DB::connection('secondary')
                            ->table('events')
                            ->where('id', $event->id)
                            ->update($data);
                        $updated++;
                    } else {
                        DB::connection('secondary')
                            ->table('events')
                            ->insert($data);
                        $inserted++;
                    }
                } catch (\Exception $e) {
                    Log::error("Error syncing event {$event->id}: " . $e->getMessage());
                }
            }

            Log::info("Events sync completed. Inserted: $inserted, Updated: $updated");
            return true;

        } catch (\Exception $e) {
            Log::error("Events sync failed: " . $e->getMessage());
            return false;
        }
    }

    private function syncEventSalaries()
    {
        try {
            // First sync events
            if (!$this->syncEvents()) {
                throw new \Exception("Failed to sync events");
            }

            // Get salary records that need syncing
            $salaries = DB::table('event_salaries AS es')
                ->join('events AS e', 'es.eventId', '=', 'e.id')
                ->whereNull('es.deleted_at')
                ->whereBetween('es.updated_at', [
                    Carbon::parse('2024-07-30'),
                    Carbon::now()
                ])
                ->select('es.*')
                ->get();

            Log::info("Found {$salaries->count()} salary records to sync");
            $inserted = 0;
            $updated = 0;

            foreach ($salaries as $salary) {
                try {
                    $data = (array) $salary;
                    
                    // Verify event exists in secondary
                    $eventExists = DB::connection('secondary')
                        ->table('events')
                        ->where('id', $salary->eventId)
                        ->exists();

                    if (!$eventExists) {
                        Log::error("Event {$salary->eventId} still missing in secondary DB for salary record {$salary->id}");
                        continue;
                    }

                    // Check if salary record exists
                    $exists = DB::connection('secondary')
                        ->table('event_salaries')
                        ->where('id', $salary->id)
                        ->exists();

                    if ($exists) {
                        DB::connection('secondary')
                            ->table('event_salaries')
                            ->where('id', $salary->id)
                            ->update($data);
                        $updated++;
                    } else {
                        DB::connection('secondary')
                            ->table('event_salaries')
                            ->insert($data);
                        $inserted++;
                    }

                } catch (\Exception $e) {
                    Log::error("Error syncing salary record {$salary->id}: " . $e->getMessage());
                }
            }

            Log::info("Salary sync completed. Inserted: $inserted, Updated: $updated");

        } catch (\Exception $e) {
            Log::error("Salary sync failed: " . $e->getMessage());
            throw $e;
        }
    }

    private function syncTourGuides()
    {
        $guides = DB::table('tour_guides')->get();

        foreach ($guides as $guide) {
            $data = (array) $guide;
            $data['created_at'] = date('Y-m-d H:i:s', strtotime($guide->created_at));
            $data['updated_at'] = date('Y-m-d H:i:s', strtotime($guide->updated_at));

            DB::connection('secondary')
                ->table('tour_guides')
                ->updateOrInsert(
                    ['id' => $guide->id],
                    $data
                );
        }
    }

    private function syncUsers()
    {
        $users = DB::table('users')->get();

        foreach ($users as $user) {
            $data = (array) $user;
            $data['created_at'] = date('Y-m-d H:i:s', strtotime($user->created_at));
            $data['updated_at'] = date('Y-m-d H:i:s', strtotime($user->updated_at));

            DB::connection('secondary')
                ->table('users')
                ->updateOrInsert(
                    ['id' => $user->id],
                    $data
                );
        }
    }
}
