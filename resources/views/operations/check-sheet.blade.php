@extends('partials.main')
@section('content')
<div class="content-page">
    <div class="content">
        <div class="container-fluid">
            {{-- Header Section --}}
            <div class="page-title-box">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <div class="page-title-box">
                            <h4 class="page-title">Operations Check Sheet</h4>
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="javascript:void(0);">Home</a></li>
                                <li class="breadcrumb-item active">Check Sheet</li>
                            </ol>
                        </div>
                    </div>
                    <div class="col-md-4 text-right">
                        <div class="date-filter">
                            <h5>{{ now()->format('l, F j, Y') }}</h5>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Table Section --}}
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="operationsTable">
                                    {{-- Table Header --}}
                                    <thead>
                                        <tr class="">
                                            <th width="8%">Duration</th>
                                            <th width="30%">Tour</th>
                                            <th width="10%">Car</th>
                                            <th width="8%">Pick Up Time</th>
                                            <th width="15%">Pick Up place</th>
                                            <th width="8%">Pax</th>
                                            <th width="10%">Guide</th>
                                            <th width="8%" class="bg-warning">Available time</th>
                                            <th>Remark</th>
                                        </tr>
                                    </thead>
                                    
                                    <tbody>
                                        @php
                                            $currentEventId = null;
                                            $rowCount = 0;
                                            $groupedTours = $tours->groupBy('event_id');
                                        @endphp

                                        @foreach($groupedTours as $eventId => $tourGroup)
                                            @foreach($tourGroup as $index => $tour)
                                                <tr class="tour-start">
                                                    @if($index === 0)
                                                        <td rowspan="{{ count($tourGroup) }}" class="tour-duration">
                                                            @php
                                                                $formattedDuration = '#N/A';
                                                                if (is_numeric($tour->duration)) {
                                                                    $hours = floor($tour->duration / 60);
                                                                    $minutes = $tour->duration % 60;
                                                                    $formattedDuration = sprintf("%02d:%02d", $hours, $minutes);
                                                                }
                                                            @endphp
                                                            {{ $formattedDuration }}
                                                        </td>
                                                        <td rowspan="{{ count($tourGroup) }}" class="tour-name font-weight-bold">
                                                            {{ $tour->tour_name }}
                                                        </td>
                                                    @endif
                                                    <td>
                                                        <select class="form-control" name="vehicle" id="vehicle_{{ $tour->id }}">
                                                            <option value="0">{{ $tour->vehicle }}</option>
                                                            @if(isset($vehicles))
                                                                @foreach($vehicles as $vehicle)
                                                                    <option value="{{ $vehicle->id }}"
                                                                        {{ $tour->vehicle_id == $vehicle->id ? 'selected' : '' }}>
                                                                        {{ $vehicle->name }}
                                                                    </option>
                                                                @endforeach
                                                            @endif
                                                        </select>
                                                    </td>
                                                    <td>{{ $tour->pickup_time }}</td>
                                                    <td>{{ $tour->pickup_location }}</td>
                                                    <td class="text-center">{{ $tour->pax }}</td>
                                                    <td>
                                                        <select class="form-control" name="guide" id="guide_{{ $tour->id }}">
                                                            <option value="0">{{ $tour->guide }}</option>
                                                            @if(isset($guides))
                                                                @foreach($guides as $guide)
                                                                    <option value="{{ $guide->id }}"
                                                                        {{ $tour->guide_id == $guide->id ? 'selected' : '' }}>
                                                                        {{ $guide->name }}
                                                                    </option>
                                                                @endforeach
                                                            @endif
                                                        </select>
                                                    </td>
                                                    <td class="bg-warning">{{ $tour->available ?: '#N/A' }}</td>
                                                    <td>{{ $tour->remark }}</td>
                                                </tr>
                                            @endforeach
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



@push('scripts')
<script>
$(document).ready(function() {
    // Initialize DataTable with custom settings
    var table = $('#operationsTable').DataTable({
        ordering: false,
        pageLength: 50,
        searching: true,
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
             '<"row"<"col-sm-12"tr>>' +
             '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        language: {
            search: "Search tours:",
            lengthMenu: "Show _MENU_ tours per page"
        },
        drawCallback: function(settings) {
            // Add border to last row of each tour group
            $('.tour-start').prev('tr').not('.tour-start').css('border-bottom', '2px solid #333');
            
            // Ensure proper alignment of rowspan cells
            $('.tour-duration, .tour-name').css('vertical-align', 'middle');
        }
    });
    
    // Additional styling for mobile responsiveness
    $(window).resize(function() {
        $('.table-responsive').css('max-height', (window.innerHeight - 300) + 'px');
    }).resize();
});
</script>
@endpush