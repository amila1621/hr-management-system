@extends('partials.main')

@section('content')
<style>
    .unapproved-container {
        background-color: rgba(255, 193, 7, 0.2);
        border: 1px solid #ffc107;
        border-radius: 4px;
        padding: 3px;
        position: relative;
    }

    .unapproved-container::before {
        content: "PENDING";
        position: absolute;
        top: -8px;
        right: -5px;
        background: #ffc107;
        color: #212529;
        font-size: 0.6rem;
        font-weight: bold;
        padding: 1px 4px;
        border-radius: 3px;
        z-index: 5;
        border: 1px solid #e0a800;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
    }

    .unapproved-container:hover {
        background-color: rgba(255, 193, 7, 0.3);
        border-color: #e0a800;
        transition: all 0.2s ease;
    }

    /* Mobile responsive */
    @media (max-width: 768px) {
        .unapproved-container::before {
            font-size: 0.5rem;
            padding: 1px 3px;
            top: -6px;
            right: -3px;
        }
    }
</style>
<div class="content-page">
    <div class="content">
        <div class="container-fluid">
            <div class="row mb-3" style="z-index: 1000; position: fixed; top: -6px; margin: 0px 40px 10px 20px;">
                <h4 class="page-title" style="margin-top: 23px">
                    {{ $staff->name }} - Hours Report
                </h4>

                
                @if(Auth::user()->role == 'admin')
                   
                <div class="col-md-6" style="margin-top: 23px;">
                    <form action="{{ route('reports.getOfficeStaffWiseReport') }}" method="GET" class="form-inline" style="flex-flow: initial;">
                        <div class="form-group mr-2">
                            <?php 
                            
                            $staffs = \App\Models\StaffUser::get();
                           $staff_id = request()->get('staff_id', null);
                            ?>
                            <label for="staff_id" class="mr-2">Office Staff:</label>
                            <select name="staff_id" id="staff_id" class="form-control" onchange="this.form.submit()">
                                @foreach($staffs as $staff)
                                    <option value="{{ $staff->id }}" {{ (isset($staff_id) && $staff_id == $staff->id) ? 'selected' : '' }}>
                                        {{ $staff->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group mr-2">
                            <label for="year" class="mr-2">Year:</label>
                            <select name="year" id="year" class="form-control" onchange="this.form.submit()">
                                @for ($i = date('Y'); $i >= date('Y') - 1; $i--)
                                    <option value="{{ $i }}" {{ $year == $i ? 'selected' : '' }}>
                                        {{ $i }}
                                    </option>
                                @endfor
                            </select>
                        </div>
                        <div class="form-group mr-2">
                            <label for="month" class="mr-2">Month:</label>
                            <select name="month" id="month" class="form-control" onchange="this.form.submit()">
                                @foreach (range(1, 12) as $m)
                                    <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>
                                        {{ date('F', mktime(0, 0, 0, $m, 1)) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </form>
                </div>

                @else 

                     <div class="col-md-6" style="margin-top: 23px;">
                    <form action="{{ route('staff.hours-report') }}" method="GET" class="form-inline" style="flex-flow: initial;">
                        <div class="form-group mr-2">
                            <label for="year" class="mr-2">Year:</label>
                            <select name="year" id="year" class="form-control" onchange="this.form.submit()">
                                @for ($i = date('Y'); $i >= date('Y') - 1; $i--)
                                    <option value="{{ $i }}" {{ $year == $i ? 'selected' : '' }}>
                                        {{ $i }}
                                    </option>
                                @endfor
                            </select>
                        </div>
                        <div class="form-group mr-2">
                            <label for="month" class="mr-2">Month:</label>
                            <select name="month" id="month" class="form-control" onchange="this.form.submit()">
                                @foreach (range(1, 12) as $m)
                                    <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>
                                        {{ date('F', mktime(0, 0, 0, $m, 1)) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </form>
                </div>

                @endif
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div style="overflow-y: auto;">
                                <div class="table" style="margin: 10px 0px 10px 0px;">
                                    <table id="datatable-buttons" class="table table-striped table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Office Work</th>
                                                <th>Work Hours</th>
                                                <th>Work hours<br>ONLY with<br>holiday extras</th>
                                                <th>Night supplement<br>hours(20-6)</th>
                                                <th>Night supplement<br>ONLY with<br>holiday extras</th>
                                                <th>Sick Leaves</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php
                                            $totalWorkHours = 0;
                                            $totalWorkMinutes = 0;
                                            $totalHolidayHours = 0;
                                            $totalHolidayMinutes = 0;
                                            $totalNightHours = 0;
                                            $totalNightMinutes = 0;
                                            $totalHolidayNightHours = 0;
                                            $totalHolidayNightMinutes = 0;
                                            @endphp

                                            @foreach($dailyHours as $date => $data)
                                            @php
                                            $shifts = $data['shifts'] ?? [];
                                            // Separate SL shifts from regular shifts
                                            $regularShifts = array_filter($shifts, function($shift) {
                                            return !isset($shift['type']) || $shift['type'] !== 'SL';
                                            });
                                            $slShifts = array_filter($shifts, function($shift) {
                                            return isset($shift['type']) && $shift['type'] === 'SL';
                                            });
                                            $rowspan = max(count($regularShifts), 1); // Ensure at least 1 row per date
                                            @endphp

                                            @if(count($regularShifts) > 0)
                                            @foreach($regularShifts as $index => $shift)
                                            @php
                                            $isApprovedSickLeave = isset($shift['is_approved_sick_leave']) ? $shift['is_approved_sick_leave'] : false;

                                            // UPDATED: Calculate on-call hours (divide by 3 if on_call type)
                                            $isOnCall = ($shift['type'] === 'on_call' || $shift['type'] === 'midnight_phone');
                                            $divisor = $isOnCall ? 3 : 1;

                                            $displayWorkHours = intdiv(($shift['hours'] ?? 0) * 60 + ($shift['minutes'] ?? 0), $divisor);
                                            $displayWorkMinutes = $displayWorkHours % 60;
                                            $displayWorkHours = intdiv($displayWorkHours, 60);

                                            $displayHolidayHours = intdiv(($shift['holiday_hours'] ?? 0) * 60 + ($shift['holiday_minutes'] ?? 0), $divisor);
                                            $displayHolidayMinutes = $displayHolidayHours % 60;
                                            $displayHolidayHours = intdiv($displayHolidayHours, 60);

                                            $displayNightHours = intdiv(($shift['night_hours'] ?? 0) * 60 + ($shift['night_minutes'] ?? 0), $divisor);
                                            $displayNightMinutes = $displayNightHours % 60;
                                            $displayNightHours = intdiv($displayNightHours, 60);

                                            $displayHolidayNightHours = intdiv(($shift['holiday_night_hours'] ?? 0) * 60 + ($shift['holiday_night_minutes'] ?? 0), $divisor);
                                            $displayHolidayNightMinutes = $displayHolidayNightHours % 60;
                                            $displayHolidayNightHours = intdiv($displayHolidayNightHours, 60);
                                            @endphp
                                            <tr>
                                                @if($index === 0)
                                                <td rowspan="{{ $rowspan }}">
                                                    {{ \Carbon\Carbon::parse($date)->format('d.m.Y') }} ({{ \Carbon\Carbon::parse($date)->format('D') }}.)
                                                </td>
                                                @endif
                                                <td>
                                                    @if(isset($shift['type']) && $shift['type'] === 'on_call')
                                                    <i class="fas fa-phone mr-1" style="color: #007bff;"></i>
                                                    @elseif(isset($shift['type']) && $shift['type'] === 'midnight_phone')
                                                    <i class="fas fa-moon mr-1" style="color: #6c757d;"></i>
                                                    <i class="fas fa-phone mr-1" style="color: #6c757d;"></i>
                                                    @endif
                                                    {{ $shift['start_time'] }} - {{ $shift['end_time'] }}
                                                    @if(isset($shift['reason']) && $shift['reason'])
                                                    <small class="text-muted d-block">{{ $shift['reason'] }}</small>
                                                    @endif
                                                </td>
                                                <!-- UPDATED: Work Hours Display with division by 3 for on-call -->
                                                <td>{{ sprintf('%02d:%02d', $displayWorkHours, $displayWorkMinutes) }}</td>
                                                <td>{{ sprintf('%02d:%02d', $displayHolidayHours, $displayHolidayMinutes) }}</td>
                                                <td>{{ sprintf('%02d:%02d', $displayNightHours, $displayNightMinutes) }}</td>
                                                <td>{{ sprintf('%02d:%02d', $displayHolidayNightHours, $displayHolidayNightMinutes) }}</td>
                                                <td>
                                                    @if($index === 0)
                                                    <!-- Display SL shifts from staff_monthly_hours -->
                                                    @if(count($slShifts) > 0)
                                                    @foreach($slShifts as $slShift)
                                                    @php
                                                    // Calculate duration (sick leave hours are NOT divided by 3)
                                                    $startTime = \Carbon\Carbon::createFromFormat('H:i', $slShift['start_time']);
                                                    $endTime = \Carbon\Carbon::createFromFormat('H:i', $slShift['end_time']);
                                                    $duration = $endTime->diffInMinutes($startTime);
                                                    $hours = intdiv($duration, 60);
                                                    $minutes = $duration % 60;
                                                    @endphp
                                                    <div class="sick-leave-entry mb-1">
                                                        {{ sprintf('%02d:%02d', $hours, $minutes) }}
                                                    </div>
                                                    @endforeach
                                                    @endif
                                                    @endif
                                                </td>
                                            </tr>
                                            @php
                                            // UPDATED: Add to totals with division by 3 for on-call hours (only if not approved sick leave)
                                            if (!$isApprovedSickLeave) {
                                            $totalWorkHours += $displayWorkHours;
                                            $totalWorkMinutes += $displayWorkMinutes;
                                            $totalHolidayHours += $displayHolidayHours;
                                            $totalHolidayMinutes += $displayHolidayMinutes;
                                            $totalNightHours += $displayNightHours;
                                            $totalNightMinutes += $displayNightMinutes;
                                            $totalHolidayNightHours += $displayHolidayNightHours;
                                            $totalHolidayNightMinutes += $displayHolidayNightMinutes;
                                            }
                                            @endphp
                                            @endforeach
                                            @else
                                            {{-- Handle dates with only SL shifts or no shifts --}}
                                            <tr>
                                                <td>{{ \Carbon\Carbon::parse($date)->format('d.m.Y') }} ({{ \Carbon\Carbon::parse($date)->format('D') }}.)</td>
                                                <td>
                                                    @if(count($slShifts) > 0)
                                                    @foreach($slShifts as $index => $slShift)
                                                    @php
                                                    // Calculate duration for display (sick leave hours are NOT divided by 3)
                                                    $startTime = \Carbon\Carbon::createFromFormat('H:i', $slShift['start_time']);
                                                    $endTime = \Carbon\Carbon::createFromFormat('H:i', $slShift['end_time']);
                                                    $duration = $endTime->diffInMinutes($startTime);
                                                    $hours = intdiv($duration, 60);
                                                    $minutes = $duration % 60;
                                                    @endphp
                                                    @if($index > 0)<br>@endif
                                                    <span>
                                                        Sick Leave - {{ sprintf('%02d:%02d', $hours, $minutes) }}
                                                    </span>
                                                    @endforeach
                                                    @else
                                                    -
                                                    @endif
                                                </td>
                                                <td>00:00</td>
                                                <td>00:00</td>
                                                <td>00:00</td>
                                                <td>00:00</td>
                                                <td>
                                                    <!-- Display SL shifts from staff_monthly_hours -->
                                                    @if(count($slShifts) > 0)
                                                    @foreach($slShifts as $slShift)
                                                    @php
                                                    // Calculate duration (sick leave hours are NOT divided by 3)
                                                    $startTime = \Carbon\Carbon::createFromFormat('H:i', $slShift['start_time']);
                                                    $endTime = \Carbon\Carbon::createFromFormat('H:i', $slShift['end_time']);
                                                    $duration = $endTime->diffInMinutes($startTime);
                                                    $hours = intdiv($duration, 60);
                                                    $minutes = $duration % 60;
                                                    @endphp
                                                    <div class="sick-leave-entry mb-1">
                                                        {{ sprintf('%02d:%02d', $hours, $minutes) }}
                                                    </div>
                                                    @endforeach
                                                    @endif
                                                </td>
                                            </tr>
                                            @endif
                                            @endforeach
                                        </tbody>
                                        @php
                                        $lastDayOfMonth = \Carbon\Carbon::create($year, $month)->endOfMonth()->format('Y-m-d');
                                        $today = \Carbon\Carbon::now()->format('Y-m-d');
                                        @endphp

                                        @if($today >= $lastDayOfMonth)
                                        <tfoot>
                                            <tr>
                                                <th colspan="2">Total</th>
                                                <th>
                                                    @php
                                                    // Convert excess minutes to hours
                                                    $totalWorkHours += intdiv($totalWorkMinutes, 60);
                                                    $totalWorkMinutes = $totalWorkMinutes % 60;
                                                    @endphp
                                                    {{ sprintf('%02d:%02d', $totalWorkHours, $totalWorkMinutes) }}
                                                </th>
                                                <th>
                                                    @php
                                                    $totalHolidayHours += intdiv($totalHolidayMinutes, 60);
                                                    $totalHolidayMinutes = $totalHolidayMinutes % 60;
                                                    @endphp
                                                    {{ sprintf('%02d:%02d', $totalHolidayHours, $totalHolidayMinutes) }}
                                                </th>
                                                <th>
                                                    @php
                                                    $totalNightHours += intdiv($totalNightMinutes, 60);
                                                    $totalNightMinutes = $totalNightMinutes % 60;
                                                    @endphp
                                                    {{ sprintf('%02d:%02d', $totalNightHours, $totalNightMinutes) }}
                                                </th>
                                                <th>
                                                    @php
                                                    $totalHolidayNightHours += intdiv($totalHolidayNightMinutes, 60);
                                                    $totalHolidayNightMinutes = $totalHolidayNightMinutes % 60;
                                                    @endphp
                                                    {{ sprintf('%02d:%02d', $totalHolidayNightHours, $totalHolidayNightMinutes) }}
                                                </th>
                                            </tr>
                                        </tfoot>
                                        @endif
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<style>
    /* Copy existing styles */
    body {
        overflow-y: auto !important;
        font-size: 14px;
        -webkit-overflow-scrolling: touch;
    }

    .content-page {
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        margin-bottom: 60px;
        overflow: visible !important;
    }

    /* ...rest of existing styles... */
    /* Add to your existing styles */
    .sick-leave-status {
        color: #856404;
        background-color: #fff3cd;
        border: 1px solid #ffeeba;
        padding: 2px 4px;
        border-radius: 3px;
        font-size: 0.75rem;
        margin-left: 4px;
    }
</style>
@endsection