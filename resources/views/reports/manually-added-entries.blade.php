@extends('partials.main')
@php
if (!function_exists('formatTime')) {
    function formatTime($hours) {
        $wholeHours = floor($hours);
        $fractionalHours = $hours - $wholeHours;
        $minutes = round($fractionalHours * 100);
        
        $totalMinutes = $wholeHours * 60 + $minutes;
        $finalHours = floor($totalMinutes / 60);
        $finalMinutes = $totalMinutes % 60;
        
        if ($finalMinutes == 0) {
            return $finalHours;
        } else {
            return sprintf("%d:%02d", $finalHours, $finalMinutes);
        }
    }
}
@endphp
@section('content')
    <div class="content-page">
        <div class="content">
            <div class="container-fluid">
                <h4 class="page-title">Manually Added Entries</h4>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="mt-0 header-title">Hours Worked</h4>

                                <div class="table-responsive">
                                    <table id="datatable-buttons" class="table table-striped table-bordered" style="border-collapse: collapse; border-spacing: 0; width: 100%">
                                        <thead>
                                            <tr>
                                                <th>Tour Name</th>
                                                <th>Guide</th>
                                                <th>Start Time</th>
                                                <th>End Time</th>
                                                <th>Work Hours</th>
                                                <th>Work hours ONLY with holiday extras</th>
                                                <th>Night supplement hours(20-6)</th>
                                                <th>Night supplement ONLY with holiday extras</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        

                                            @foreach ($entries as $entry)
                                                <tr>
                                                    <td>{{ $entry->event->name }}</td>
                                                    <td>{{ $entry->tourGuide->name }}</td>
                                                    <td>{{ $entry->guide_start_time ? $entry->guide_start_time->format('d.m.Y H:i') : 'N/A' }}</td>
                                                    <td>{{ $entry->guide_end_time ? $entry->guide_end_time->format('d.m.Y H:i') : 'N/A' }}</td>
                                                    <td>{{ formatTime($entry->normal_hours) }}</td>
                                                    <td>{{ formatTime($entry->holiday_hours) }}</td>
                                                    <td>{{ formatTime($entry->normal_night_hours) }}</td>
                                                    <td>{{ formatTime($entry->holiday_night_hours) }}</td>
                                                </tr>

                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <!-- Include your DataTables and other scripts here -->
@endsection
