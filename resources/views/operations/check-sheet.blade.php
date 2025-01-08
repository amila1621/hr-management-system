@extends('partials.main')
@section('content')
<div class="content-page">
    <div class="content">
        <div class="container-fluid">
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
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            @if ($errors->any())
                                <div class="alert alert-danger">
                                    <ul class="mb-0">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <div class="table-responsive">
                                <table class="table table-bordered table-hover">
                                    <thead>
                                        <tr>
                                            <th>Tour Name</th>
                                            <th>Guide</th>
                                            <th>Vehicle</th>
                                            <th>Pickup Time</th>
                                            <th>Pickup Location</th>
                                            <th>PAX</th>
                                            <th>Available Seats</th>
                                            <th>Remarks</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $currentTour = '';
                                            $tourCount = 0;
                                        @endphp
                                        @forelse($events as $key => $event)
                                            @if($currentTour !== $event->tour_name)
                                                @php
                                                    $currentTour = $event->tour_name;
                                                    $tourCount = $events->where('tour_name', $currentTour)->count();
                                                @endphp
                                                <tr>
                                                    <td rowspan="{{ $tourCount }}">{{ $event->tour_name }}</td>
                                                    <td>{{ $event->guide }}</td>
                                                    <td>{{ $event->vehicle }}</td>
                                                    <td>{{ $event->pickup_time }}</td>
                                                    <td>{{ $event->pickup_location }}</td>
                                                    <td>{{ $event->pax }}</td>
                                                    <td>{{ $event->available }}</td>
                                                    <td>{{ $event->remark }}</td>
                                                </tr>
                                            @else
                                                <tr>
                                                    <td>{{ $event->guide }}</td>
                                                    <td>{{ $event->vehicle }}</td>
                                                    <td>{{ $event->pickup_time }}</td>
                                                    <td>{{ $event->pickup_location }}</td>
                                                    <td>{{ $event->pax }}</td>
                                                    <td>{{ $event->available }}</td>
                                                    <td>{{ $event->remark }}</td>
                                                </tr>
                                            @endif
                                        @empty
                                            <tr>
                                                <td colspan="8" class="text-center">No tours scheduled for today</td>
                                            </tr>
                                        @endforelse
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

<script>
    $(document).ready(function() {
        // Initialize any datepickers or timepickers if needed
        $('input[name="pickup_time"]').datetimepicker({
            format: 'HH:mm'
        });
    });
</script>
@endsection 