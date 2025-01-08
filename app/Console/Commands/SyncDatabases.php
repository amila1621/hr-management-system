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
        // Sync Events
        $this->syncEvents();
        
        // Sync Event Salaries
        $this->syncEventSalaries();

        $this->info('Database sync completed successfully!');
    }

    private function syncEvents()
    {
        // Get all new or updated records
        $records = Event::where('updated_at', '>', now()->subDay())->get();

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
        $records = DB::table('event_salaries')
            ->where('updated_at', '>', now()->subDay())
            ->get();

        foreach ($records as $record) {
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
