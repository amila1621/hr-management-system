<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Roster - {{ $periodStart->format('d/m/Y') }} - {{ $periodEnd->format('d/m/Y') }}</title>
    <style>
        @media print {
            @page {
                size: A4 landscape;
                margin: 0.5in;
            }
            .no-print { display: none !important; }
            .page-break { page-break-before: always; }
            body { font-size: 14px; }
            table { font-size: 13px; }
            .day-header { font-size: 12px; }
            .week-header { font-size: 16px; }
        }
        
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            font-size: 14px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
        }
        
        .header h1 {
            margin: 0;
            font-size: 28px;
            color: #333;
        }
        
        .header .date-range {
            font-size: 18px;
            color: #666;
            margin-top: 8px;
        }
        
        .month-selector {
            margin-bottom: 25px;
            text-align: center;
        }
        
        .month-selector select {
            padding: 8px 15px;
            font-size: 16px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin: 0 10px;
        }
        
        .department-section {
            margin-bottom: 40px;
        }
        
        .department-header {
            background-color: #f5f5f5;
            padding: 15px;
            border-left: 4px solid #007bff;
            margin-bottom: 20px;
        }
        
        .department-title {
            font-size: 22px;
            font-weight: bold;
            color: #333;
            margin: 0;
        }
        
        .week-section {
            margin-bottom: 35px;
            page-break-inside: avoid;
        }
        
        .week-header {
            background-color: #e9ecef;
            padding: 10px 15px;
            border-left: 3px solid #28a745;
            margin-bottom: 15px;
            font-size: 16px;
            font-weight: bold;
            color: #495057;
        }
        
        .roster-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            table-layout: fixed;
        }
        
        .roster-table th,
        .roster-table td {
            border: 1px solid #ddd;
            padding: 8px 4px;
            text-align: center;
            vertical-align: middle;
        }
        
        .roster-table th {
            background-color: #f8f9fa;
            font-weight: bold;
            font-size: 14px;
            height: 60px;
        }
        
        .staff-name {
            text-align: left;
            font-weight: bold;
            width: 180px;
            background-color: #f9f9f9;
            font-size: 14px;
            padding: 8px 10px;
        }
        
        .supervisor-row {
            background-color: #e3f2fd;
        }
        
        .supervisor-row .staff-name {
            background-color: #bbdefb;
        }
        
        .holiday {
            background-color: #ffebee;
        }
        
        .shift-time {
            font-size: 12px;
            line-height: 1.3;
            margin: 2px 0;
            word-break: break-all;
        }
        
        .shift-normal { color: #333; }
        .shift-on-call { 
            color: #ff6600; 
            font-style: italic;
            font-weight: bold;
        }
        
        .sick-leave {
            background-color: #fff3cd;
            color: #856404;
            font-weight: bold;
            font-size: 12px;
        }
        
        .day-header {
            width: calc((100% - 180px) / 7);
            font-size: 12px;
            padding: 8px 4px;
            min-width: 80px;
        }
        
        .controls {
            margin-bottom: 25px;
            text-align: center;
        }
        
        .btn {
            background-color: #007bff;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin: 0 8px;
            text-decoration: none;
            display: inline-block;
            font-size: 16px;
        }
        
        .btn:hover {
            background-color: #0056b3;
        }
        
        /* Midnight Phone Row Styles */
        .midnight-phone-row {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        
        .midnight-phone-label {
            background-color: #e9ecef !important;
            color: #495057;
            font-weight: bold;
            font-style: italic;
        }
        
        .midnight-phone-cell {
            background-color: #f8f9fa;
            font-weight: bold;
            color: #495057;
            font-size: 12px;
        }
        
        .midnight-phone-row .holiday {
            background-color: #ffebee;
        }

        /* Ensure good page breaks */
        .week-section:nth-child(3n) {
            page-break-after: always;
        }
        
        /* Prevent orphaned headers */
        .week-header {
            page-break-after: avoid;
        }
        
        /* Keep small departments together when possible */
        .small-department .week-section {
            page-break-inside: avoid;
        }
    </style>
</head>
<body>
    <div class="no-print controls">
        <div class="month-selector">
            <label for="period-select">Select 3-Week Period:</label>
            <select id="period-select" onchange="changePeriod()">
                @foreach($periodOptions as $option)
                    <option value="{{ $option['value'] }}" {{ $option['selected'] ? 'selected' : '' }}>
                        {{ $option['label'] }}
                    </option>
                @endforeach
            </select>
            <button class="btn" onclick="window.print()">🖨️ Print Roster</button>
            <a href="{{ route('supervisor.enter-working-hours') }}" class="btn">📝 Enter Hours</a>
        </div>
    </div>

    <div class="header">
        <h1>3-Week Staff Roster</h1>
        <div class="date-range">
            {{ $periodStart->format('d/m/Y') }} - {{ $periodEnd->format('d/m/Y') }}
        </div>
    </div>

    @php
        // Split the 3-week period into individual weeks
        $weeks = [];
        $currentWeekStart = $periodStart->copy()->startOfWeek();
        
        // Generate exactly 3 weeks
        for ($weekIndex = 0; $weekIndex < 3; $weekIndex++) {
            $weekEnd = $currentWeekStart->copy()->endOfWeek();
            $weekDates = [];
            
            // Get all 7 days of the week, but only include dates within our period
            for ($i = 0; $i < 7; $i++) {
                $date = $currentWeekStart->copy()->addDays($i);
                if ($date->gte($periodStart) && $date->lte($periodEnd)) {
                    $weekDates[] = $date;
                }
            }
            
            if (!empty($weekDates)) {
                $weeks[] = [
                    'start' => $currentWeekStart->copy(),
                    'dates' => $weekDates,
                    'label' => 'Week ' . ($weekIndex + 1) . ': ' . $currentWeekStart->format('d/m') . ' - ' . $weekEnd->format('d/m/Y')
                ];
            }
            
            $currentWeekStart->addWeek();
        }
    @endphp

    @foreach($staffByDepartment as $department => $departmentStaff)
        @if($loop->index > 0)
            <div class="page-break"></div>
        @endif
        
        @php
            $sortedStaff = $departmentStaff->sortBy(function ($staff) {
                return [
                    $staff->getAttribute('supervisor_rank') ?? 2,
                    $staff->name
                ];
            });
        @endphp
        
        <div class="department-section">
            <div class="department-header">
                <h2 class="department-title">{{ $department }} Department</h2>
            </div>

            @foreach($weeks as $weekIndex => $week)
                <div class="week-section">
                    <div class="week-header">
                        {{ $week['label'] }}
                    </div>

                    <table class="roster-table">
                        <thead>
                            <tr>
                                <th class="staff-name">Staff Member</th>
                                @foreach($week['dates'] as $date)
                                    <th class="day-header {{ in_array($date->format('Y-m-d'), $holidays->toArray()) || $date->isSunday() ? 'holiday' : '' }}">
                                        {{ $date->format('d') }}<br>
                                        {{ $date->format('D') }}<br>
                                        <small>{{ $date->format('M') }}</small>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($sortedStaff as $staff)
                                @php
                                    $isSupervisor = $staff->getAttribute('is_supervisor') === true || 
                                                   $staff->getAttribute('supervisor_rank') === 1;
                                @endphp
                                <tr class="{{ $isSupervisor ? 'supervisor-row' : '' }}">
                                    <td class="staff-name">
                                        {{ $staff->name }}
                                        @if($isSupervisor)
                                            <br><small><strong>(SUPERVISOR)</strong></small>
                                        @endif
                                    </td>
                                    @foreach($week['dates'] as $date)
                                        @php
                                            $dateStr = $date->format('Y-m-d');
                                            $isHoliday = in_array($dateStr, $holidays->toArray()) || $date->isSunday();
                                            $sickLeaveKey = $staff->id . '_' . $dateStr;
                                            $hasSickLeave = isset($sickLeaveStatuses[$sickLeaveKey]);
                                            $dayHours = isset($staffHours[$staff->id][$dateStr]) ? $staffHours[$staff->id][$dateStr] : [];
                                        @endphp
                                        
                                        <td class="{{ $isHoliday ? 'holiday' : '' }} {{ $hasSickLeave ? 'sick-leave' : '' }}">
                                            @if($hasSickLeave)
                                                SICK
                                            @elseif(!empty($dayHours))
                                                @foreach($dayHours as $shift)
                                                    @if(isset($shift['start_time']) && isset($shift['end_time']))
                                                        <div class="shift-time {{ isset($shift['type']) && $shift['type'] === 'on_call' ? 'shift-on-call' : 'shift-normal' }}">
                                                            {{ substr($shift['start_time'], 0, 5) }}-{{ substr($shift['end_time'], 0, 5) }}
                                                            @if(isset($shift['type']) && $shift['type'] === 'on_call')
                                                                <br><small>On Call</small>
                                                            @endif
                                                        </div>
                                                    @endif
                                                @endforeach
                                            @else
                                                -
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach

                            {{-- Add midnight phone row for Operations department --}}
                            @if(($displayMidnightPhone || Auth::user()->role === 'admin') && strtolower($department) === 'operations')
                                <tr class="midnight-phone-row">
                                    <td class="staff-name midnight-phone-label">
                                        <strong>Midnight Phone</strong>
                                    </td>
                                    @foreach($week['dates'] as $date)
                                        @php
                                            $dateStr = $date->format('Y-m-d');
                                            $isHoliday = in_array($dateStr, $holidays->toArray()) || $date->isSunday();
                                        @endphp
                                        <td class="{{ $isHoliday ? 'holiday' : '' }} midnight-phone-cell">
                                            {{ $midnightPhoneStaff[$dateStr] ?? '-' }}
                                        </td>
                                    @endforeach
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            @endforeach
        </div>
    @endforeach

    <div class="no-print" style="margin-top: 30px; text-align: center;">
        <button class="btn" onclick="window.print()">🖨️ Print Roster</button>
        <a href="{{ route('supervisor.enter-working-hours') }}" class="btn">📝 Enter Hours</a>
    </div>

    <script>
        function changePeriod() {
            const selectedPeriod = document.getElementById('period-select').value;
            const currentUrl = new URL(window.location.href);
            currentUrl.searchParams.set('period', selectedPeriod);
            window.location.href = currentUrl.toString();
        }
        
        window.addEventListener('beforeprint', function() {
            // Any pre-print adjustments
        });
        
        window.addEventListener('afterprint', function() {
            // Any post-print cleanup
        });
    </script>
</body>
</html>