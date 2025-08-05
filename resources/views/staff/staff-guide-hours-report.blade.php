@extends('partials.main')

@section('content')
<div class="content-page">
    <div class="content">
        <div class="container-fluid">
            <div class="row mb-3" style="z-index: 1000; position: fixed; top: -6px; margin: 0px 40px 10px 20px;">
                <h4 class="page-title" style="margin-top: 23px">
                    {{ $currentStaff->name }} - Staff+Guide Hours Report
                </h4>

                <div class="col-md-6" style="margin-top: 23px;">
                    <form action="{{ route('staff-guide.hours-report') }}" method="GET" class="form-inline" style="flex-flow: initial;">
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
                                                <th>Work Details</th>
                                                <th>Work Hours</th>
                                                <th>Work hours<br>ONLY with<br>holiday extras</th>
                                                <th>Night supplement<br>hours (20-6)</th>
                                                <th>Night supplement<br>ONLY with<br>holiday extras</th>
                                                <th>Sick Leaves</th>
                                                <th>Type</th>
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
                                                $totalSickLeaveHours = 0;
                                                $totalSickLeaveMinutes = 0;
                                            @endphp
                                            
                                            @foreach($dailyHours as $date => $data)
                                                @php 
                                                    $shifts = $data['shifts'] ?? [];
                                                    $isApproved = $data['is_approved'] ?? 1;
                                                    $rowspan = max(count($shifts), 1);
                                                @endphp
                                                
                                                @if(count($shifts) > 0)
                                                    @foreach($shifts as $index => $shift)
                                                        <tr>
                                                            @if($index === 0)
                                                                <td rowspan="{{ $rowspan }}">
                                                                    {{ \Carbon\Carbon::parse($date)->format('d.m.Y') }} ({{ \Carbon\Carbon::parse($date)->format('D') }}.)
                                                                </td>
                                                            @endif
                                                            <td>
                                                                {{ $shift['start_time'] }} - {{ $shift['end_time'] }}
                                                                @if(isset($shift['reason']) && $shift['reason'])
                                                                    <small class="text-muted d-block">{{ $shift['reason'] }}</small>
                                                                @endif
                                                            </td>
                                                            <!-- Work Hours Display -->
                                                            <td>{{ sprintf('%02d:%02d', $shift['hours'] ?? 0, $shift['minutes'] ?? 0) }}</td>
                                                            <td>{{ sprintf('%02d:%02d', $shift['holiday_hours'] ?? 0, $shift['holiday_minutes'] ?? 0) }}</td>
                                                            <td>
                                                                @php
                                                                    // Combine night and holiday night hours for display (like the combined report)
                                                                    $combinedNightHours = ($shift['night_hours'] ?? 0) + ($shift['holiday_night_hours'] ?? 0);
                                                                    $combinedNightMinutes = ($shift['night_minutes'] ?? 0) + ($shift['holiday_night_minutes'] ?? 0);
                                                                    
                                                                    // Handle minute overflow
                                                                    if ($combinedNightMinutes >= 60) {
                                                                        $combinedNightHours += floor($combinedNightMinutes / 60);
                                                                        $combinedNightMinutes = $combinedNightMinutes % 60;
                                                                    }
                                                                @endphp
                                                                {{ sprintf('%02d:%02d', $combinedNightHours, $combinedNightMinutes) }}
                                                            </td>
                                                            <td>{{ sprintf('%02d:%02d', $shift['holiday_night_hours'] ?? 0, $shift['holiday_night_minutes'] ?? 0) }}</td>
                                                            <td>
                                                                @if($index === 0)
                                                                    <!-- Display sick leaves for this date -->
                                                                    @if(isset($data['sick_leaves']) && count($data['sick_leaves']) > 0)
                                                                        @foreach($data['sick_leaves'] as $sickLeave)
                                                                            <div class="sick-leave-entry mb-1">
                                                                                {{ sprintf('%02d:%02d', $sickLeave['hours'], $sickLeave['minutes']) }}
                                                                                <small class="text-muted d-block">{{ $sickLeave['type'] }}</small>
                                                                            </div>
                                                                        @endforeach
                                                                    @else
                                                                        -
                                                                    @endif
                                                                @endif
                                                            </td>
                                                            <td>
                                                                @php
                                                                    $badgeClass = 'warning'; // default for guide
                                                                    if (strpos($shift['type'], 'Staff') !== false) {
                                                                        $badgeClass = 'primary';
                                                                    } elseif (strpos($shift['type'], 'Sick Leave') !== false) {
                                                                        $badgeClass = 'info';
                                                                    }
                                                                @endphp
                                                                <span class="badge badge-{{ $badgeClass }}">
                                                                    {{ $shift['type'] }}
                                                                </span>
                                                            </td>
                                                        </tr>
                                                        
                                                        @php
                                                            // Add to totals
                                                            $totalWorkMinutes += ($shift['hours'] ?? 0) * 60 + ($shift['minutes'] ?? 0);
                                                            $totalHolidayMinutes += ($shift['holiday_hours'] ?? 0) * 60 + ($shift['holiday_minutes'] ?? 0);
                                                            $totalNightMinutes += ($shift['night_hours'] ?? 0) * 60 + ($shift['night_minutes'] ?? 0);
                                                            $totalHolidayNightMinutes += ($shift['holiday_night_hours'] ?? 0) * 60 + ($shift['holiday_night_minutes'] ?? 0);
                                                        @endphp
                                                    @endforeach
                                                @else
                                                    {{-- Handle dates with only sick leaves --}}
                                                    @if(isset($data['sick_leaves']) && count($data['sick_leaves']) > 0)
                                                        <tr>
                                                            <td>{{ \Carbon\Carbon::parse($date)->format('d.m.Y') }} ({{ \Carbon\Carbon::parse($date)->format('D') }}.)</td>
                                                            <td>-</td>
                                                            <td>00:00</td>
                                                            <td>00:00</td>
                                                            <td>00:00</td>
                                                            <td>00:00</td>
                                                            <td>
                                                                @foreach($data['sick_leaves'] as $sickLeave)
                                                                    <div class="sick-leave-entry mb-1">
                                                                        {{ sprintf('%02d:%02d', $sickLeave['hours'], $sickLeave['minutes']) }}
                                                                        <small class="text-muted d-block">{{ $sickLeave['type'] }}</small>
                                                                    </div>
                                                                @endforeach
                                                            </td>
                                                            <td><span class="badge badge-info">Sick Leave</span></td>
                                                        </tr>
                                                    @endif
                                                @endif
                                            @endforeach
                                            
                                            @php
                                                // Calculate sick leave totals from all dates
                                                foreach($dailyHours as $date => $data) {
                                                    if (isset($data['sick_leaves']) && count($data['sick_leaves']) > 0) {
                                                        foreach($data['sick_leaves'] as $sickLeave) {
                                                            $totalSickLeaveMinutes += ($sickLeave['hours'] ?? 0) * 60 + ($sickLeave['minutes'] ?? 0);
                                                        }
                                                    }
                                                }
                                            @endphp
                                            
                                            @php
                                                // Convert total minutes to hours:minutes
                                                $totalWorkHours = floor($totalWorkMinutes / 60);
                                                $totalWorkMinutes = $totalWorkMinutes % 60;
                                                
                                                $totalHolidayHours = floor($totalHolidayMinutes / 60);
                                                $totalHolidayMinutes = $totalHolidayMinutes % 60;
                                                
                                                $totalNightHours = floor($totalNightMinutes / 60);
                                                $totalNightMinutes = $totalNightMinutes % 60;
                                                
                                                $totalHolidayNightHours = floor($totalHolidayNightMinutes / 60);
                                                $totalHolidayNightMinutes = $totalHolidayNightMinutes % 60;
                                                
                                                $totalSickLeaveHours = floor($totalSickLeaveMinutes / 60);
                                                $totalSickLeaveMinutes = $totalSickLeaveMinutes % 60;
                                                
                                                // Combined night hours (regular + holiday) - like the combined report
                                                $combinedTotalNightMinutes = ($totalNightHours * 60 + $totalNightMinutes) + ($totalHolidayNightHours * 60 + $totalHolidayNightMinutes);
                                                $combinedTotalNightHours = floor($combinedTotalNightMinutes / 60);
                                                $combinedTotalNightMinutes = $combinedTotalNightMinutes % 60;
                                            @endphp
                                            
                                            @php
                                                $lastDayOfMonth = \Carbon\Carbon::create($year, $month)->endOfMonth()->format('Y-m-d');
                                                $today = \Carbon\Carbon::now()->format('Y-m-d');
                                                $currentMonth = \Carbon\Carbon::now()->format('Y-m');
                                                $reportMonth = \Carbon\Carbon::create($year, $month)->format('Y-m');
                                                
                                                // Show totals if:
                                                // 1. It's a previous month (not current month)
                                                // 2. It's current month AND today is the last day of the month
                                                $showTotals = ($reportMonth < $currentMonth) || ($reportMonth == $currentMonth && $today >= $lastDayOfMonth);

                                                $showTotals = 1;
                                            @endphp
                                            
                                            @if($showTotals)
                                                <!-- Total Row -->
                                                <tr style=" font-weight: bold;">
                                                    <td colspan="2"><strong>TOTAL</strong></td>
                                                    <td><strong>{{ sprintf('%02d:%02d', $totalWorkHours, $totalWorkMinutes) }}</strong></td>
                                                    <td><strong>{{ sprintf('%02d:%02d', $totalHolidayHours, $totalHolidayMinutes) }}</strong></td>
                                                    <td><strong>{{ sprintf('%02d:%02d', $combinedTotalNightHours, $combinedTotalNightMinutes) }}</strong></td>
                                                    <td><strong>{{ sprintf('%02d:%02d', $totalHolidayNightHours, $totalHolidayNightMinutes) }}</strong></td>
                                                    <td><strong>{{ $totalSickLeaveHours > 0 || $totalSickLeaveMinutes > 0 ? sprintf('%02d:%02d', $totalSickLeaveHours, $totalSickLeaveMinutes) : '-' }}</strong></td>
                                                    <td><span class="badge badge-success">Staff+Guide</span></td>
                                                </tr>
                                            @endif
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Info Card -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">
                                <i class="fas fa-info-circle"></i> Staff+Guide Calculation Logic
                            </h5>
                            <div class="alert alert-info">
                                <strong>How the hours are calculated:</strong>
                                <ul class="mb-0">
                                    <li><strong>Staff hours:</strong> All your regular staff work hours are included</li>
                                    <li><strong>Guide hours:</strong> Only non-overlapping guide work hours are added</li>
                                    <li><strong>Overlap handling:</strong> When you work both staff and guide roles simultaneously, only staff hours count to avoid double-counting</li>
                                    <li><strong>Night supplements:</strong> Combined total of regular + holiday night hours (matches combined report logic)</li>
                                    <li><strong>On-call hours:</strong> Calculated at 1 hour = 20 minutes (divided by 3)</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection