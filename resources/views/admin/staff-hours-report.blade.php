@extends('partials.main')

@section('content')
<div class="content-page">
    <div class="content">
        <div class="container-fluid">
            <div class="row mb-3" style="z-index: 1000; position: fixed; top: -6px; margin: 0px 40px 10px 20px;">
                <h4 class="page-title" style="margin-top: 23px">
                    Admin Staff Hours Report - {{ $staff->name }}
                </h4>

                <div class="col-md-8" style="margin-top: 23px;position: absolute; left: 60vh;">
                    <form action="{{ route('supervisor.staff-report') }}" method="GET" class="form-inline" style="flex-flow: initial;">
                        <div class="form-group mr-2">
                            <label for="staff_id" class="mr-2">Staff:</label>
                            <select name="staff_id" id="staff_id" class="form-control" onchange="this.form.submit()">
                                <option value="">Select Staff Member</option>
                                @foreach($staffs as $staffOption)
                                    <option value="{{ $staffOption->id }}" {{ $staff->id == $staffOption->id ? 'selected' : '' }}>
                                        {{ $staffOption->name }} ({{ $staffOption->department }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group mr-2">
                            <label for="year" class="mr-2">Year:</label>
                            <select name="year" id="year" class="form-control" onchange="this.form.submit()">
                                @for ($i = date('Y'); $i >= date('Y') - 3; $i--)
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
                                                <th>Office Work</th>
                                                <th>Work Hours</th>
                                                <th>Work hours<br>ONLY with<br>holiday extras</th>
                                                <th>Night supplement<br>hours(20-6)</th>
                                                <th>Night supplement<br>ONLY with<br>holiday extras</th>
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
                                                    $rowspan = count($shifts);
                                                @endphp
                                                
                                                @foreach($shifts as $index => $shift)
                                                    @php
                                                        // Get sick leave status if available
                                                        $slStatus = '';
                                                        $isApprovedSickLeave = isset($shift['is_approved_sick_leave']) ? $shift['is_approved_sick_leave'] : false;
                                                        
                                                        if (isset($shift['type']) && $shift['type'] === 'SL') {
                                                            $sickLeaveKey = $selectedStaffId . '_' . $date;
                                                            if (isset($sickLeaveStatuses[$sickLeaveKey])) {
                                                                $sickLeave = $sickLeaveStatuses[$sickLeaveKey]->first();
                                                                if ($sickLeave) {
                                                                    $status = $sickLeave->status;
                                                                    $isApprovedSickLeave = ($status === '2');
                                                                    switch($status) {
                                                                        case '0': $slStatus = '(Sick Leave - Pending from supervisor)'; break;
                                                                        case '1': $slStatus = '(Sick Leave - Pending from HR)'; break;
                                                                        case '2': $slStatus = '(Sick Leave - Approved)'; break;
                                                                        case '3': $slStatus = '(Sick Leave - Rejected)'; break;
                                                                        case '4': $slStatus = '(Sick Leave - Cancelled)'; break;
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    @endphp
                                                    <tr>
                                                        @if($index === 0)
                                                            <td rowspan="{{ $rowspan }}">
                                                            {{ \Carbon\Carbon::parse($date)->format('d.m.Y') }} ({{ \Carbon\Carbon::parse($date)->format('D') }}.)
                                                            </td>
                                                        @endif
                                                        <td>
                                                            @if($isApprovedSickLeave)
                                                                Sick Leave {{ sprintf('%d:%02d', $shift['original_hours'] ?? 0, $shift['original_minutes'] ?? 0) }}
                                                            @else
                                                                {{ $shift['start_time'] }} - {{ $shift['end_time'] }}
                                                                
                                                                @if(isset($shift['reason']) && $shift['reason'])
                                                                    <small class="text-muted d-block">{{ $shift['reason'] }}</small>
                                                                @endif
                                                            @endif
                                                            @if(isset($shift['type']) && $shift['type'] === 'SL')
                                                                <span class="sick-leave-status">{{ $slStatus }}</span>
                                                            @endif
                                                        </td>
                                                        <!-- Work Hours Display -->
                                                        <td>{{ $isApprovedSickLeave ? '00:00' : sprintf('%02d:%02d', $shift['hours'] ?? 0, $shift['minutes'] ?? 0) }}</td>
                                                        <td>{{ $isApprovedSickLeave ? '00:00' : sprintf('%02d:%02d', $shift['holiday_hours'] ?? 0, $shift['holiday_minutes'] ?? 0) }}</td>
                                                        <td>{{ $isApprovedSickLeave ? '00:00' : sprintf('%02d:%02d', $shift['night_hours'] ?? 0, $shift['night_minutes'] ?? 0) }}</td>
                                                        <td>{{ $isApprovedSickLeave ? '00:00' : sprintf('%02d:%02d', $shift['holiday_night_hours'] ?? 0, $shift['holiday_night_minutes'] ?? 0) }}</td>
                                                    </tr>
                                                    @php
                                                        // Add to totals only if not approved sick leave
                                                        if (!$isApprovedSickLeave) {
                                                            $totalWorkHours += $shift['hours'] ?? 0;
                                                            $totalWorkMinutes += $shift['minutes'] ?? 0;
                                                            $totalHolidayHours += $shift['holiday_hours'] ?? 0;
                                                            $totalHolidayMinutes += $shift['holiday_minutes'] ?? 0;
                                                            $totalNightHours += $shift['night_hours'] ?? 0;
                                                            $totalNightMinutes += $shift['night_minutes'] ?? 0;
                                                            $totalHolidayNightHours += $shift['holiday_night_hours'] ?? 0;
                                                            $totalHolidayNightMinutes += $shift['holiday_night_minutes'] ?? 0;
                                                        }
                                                    @endphp
                                                @endforeach
                                            @endforeach
                                        </tbody>
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
    /* Clean, responsive styling for the report */
    body { overflow-y: auto !important; font-size: 14px; }
    .content-page { min-height: 100vh; display: flex; flex-direction: column; margin-bottom: 60px; overflow: visible !important; }
    .content { flex: 1; padding-bottom: 80px; overflow: visible !important; }
    
    /* Table styling */
    #datatable-buttons td, #datatable-buttons th { 
        padding: 8px 6px; 
        font-size: 12px; 
        vertical-align: middle;
    }
    
    /* Status badges */
    .sick-leave-status {
        color: #856404;
        background-color: #fff3cd;
        border: 1px solid #ffeeba;
        padding: 2px 4px;
        border-radius: 3px;
        font-size: 0.75rem;
        margin-left: 4px;
    }
    
    .badge-info {
        background-color: #17a2b8;
        color: white;
        padding: 3px 6px;
        border-radius: 3px;
        font-size: 0.75rem;
    }
</style>
@endsection

@section('scripts')
<!-- Include the DataTables and Buttons scripts -->
<script src="https://cdn.datatables.net/1.10.25/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/1.7.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/1.7.1/js/buttons.flash.min.js"></script>
<script src="https://cdn.datatables.net/buttons/1.7.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/1.7.1/js/buttons.print.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>

<script>
    $(document).ready(function() {
        $('#datatable-buttons').DataTable({
            dom: 'Bfrtip',
            buttons: [
                'copy', 'csv', 'excel', 'pdf', 'print'
            ],
            paging: false,
            ordering: true,
            info: false
        });
    });
</script>
@endsection
