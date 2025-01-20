<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Event;
use DB;

class SyncDatabases extends Command
{
    protected $signature = 'sync:databases';
    protected $description = 'Sync newly created or updated records to the secondary database';

    public function handle()
    {
        // Sync Events first to ensure they exist
        $this->syncEvents();
        
        // Then sync Event Salaries
        $this->syncEventSalaries();

        $this->info('Database sync completed successfully!');
    }

    private function syncEvents()
    {
        // Get all records from January 1st, 2024
        $records = Event::where('updated_at', '>=', '2024-12-31 00:00:00')->get();

        foreach ($records as $record) {
            // Convert the record to array and format datetime fields
            $data = $record->toArray();
            $data['created_at'] = $record->created_at->format('Y-m-d H:i:s');
            $data['updated_at'] = $record->updated_at->format('Y-m-d H:i:s');

            // Check if the record already exists in the secondary database
            $existing = DB::connection('secondary')
                ->table('events')
                ->where('id', $record->id)
                ->first();

            if ($existing) {
                // Update only if the source record is newer
                if ($record->updated_at > $existing->updated_at) {
                    DB::connection('secondary')
                        ->table('events')
                        ->where('id', $record->id)
                        ->update($data);
                }
            } else {
                // Insert new record
                DB::connection('secondary')
                    ->table('events')
                    ->insert($data);
            }
        }
    }

    private function syncEventSalaries()
    {
        $records = DB::table('event_salaries')->where('updated_at', '>=', '2024-12-31 00:00:00')->get();

        foreach ($records as $record) {
            // First, ensure the related event exists in secondary database
            $eventExists = DB::connection('secondary')
                ->table('events')
                ->where('id', $record->eventId)
                ->exists();

            if (!$eventExists) {
                // Fetch and sync the related event first
                $event = Event::find($record->eventId);
                if ($event) {
                    $eventData = $event->toArray();
                    $eventData['created_at'] = $event->created_at->format('Y-m-d H:i:s');
                    $eventData['updated_at'] = $event->updated_at->format('Y-m-d H:i:s');
                    
                    DB::connection('secondary')
                        ->table('events')
                        ->insert($eventData);
                } else {
                    $this->warn("Skipping salary record {$record->id}: Related event {$record->eventId} not found");
                    continue;
                }
            }

            // Convert the record to array and format datetime fields
            $data = (array) $record;
            $data['created_at'] = date('Y-m-d H:i:s', strtotime($record->created_at));
            $data['updated_at'] = date('Y-m-d H:i:s', strtotime($record->updated_at));

            // Check if the record already exists in the secondary database
            $existing = DB::connection('secondary')
                ->table('event_salaries')
                ->where('id', $record->id)
                ->first();

            if ($existing) {
                // Update only if the source record is newer
                if (strtotime($record->updated_at) > strtotime($existing->updated_at)) {
                    DB::connection('secondary')
                        ->table('event_salaries')
                        ->where('id', $record->id)
                        ->update($data);
                }
            } else {
                // Insert new record
                DB::connection('secondary')
                    ->table('event_salaries')
                    ->insert($data);
            }
        }
    }
}
