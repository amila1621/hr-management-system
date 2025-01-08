@extends('partials.main')

@section('content')
    <div class="content-page">
        <div class="content">
            <div class="container-fluid">
                <div class="page-title-box">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="mt-0 header-title">Time Plan for {{ $selectedPeriod }}</h4>
                                <div class="row mb-3">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="month">Select Period</label>
                                            <select name="period" id="period" class="form-control" required>
                                                @php
                                                    $startDate = \Carbon\Carbon::parse('2024-07-01');
                                                    $currentDate = \Carbon\Carbon::now();
                                                    
                                                    // Get the selected period from the request, or use current period as default
                                                    $selectedPeriodDate = isset($selectedPeriod) 
                                                        ? \Carbon\Carbon::parse($selectedPeriod) 
                                                        : $currentDate;
                                                    
                                                    // Calculate which period is selected
                                                    $selectedPeriodNumber = floor($selectedPeriodDate->diffInDays($startDate) / 21);
                                                    
                                                    // Generate periods
                                                    for ($i = 0; $i < 10; $i++) {
                                                        $periodStart = $startDate->copy()->addDays($i * 21);
                                                        $periodEnd = $periodStart->copy()->addDays(20);
                                                        $periodLabel = $periodStart->format('M d') . ' - ' . $periodEnd->format('M d, Y');
                                                        $periodValue = $periodStart->format('Y-m-d');
                                                        $selected = ($i == $selectedPeriodNumber) ? 'selected' : '';
                                                        echo "<option value='{$periodValue}' {$selected}>{$periodLabel}</option>";
                                                    }
                                                @endphp
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3 offset-md-6">
                                        <div class="form-group">
                                            <label for="supervisor">Select Supervisor</label>
                                            <select name="supervisor" id="supervisor" class="form-control">
                                                <option value="" selected disabled>Select Supervisor</option>
                                                @foreach($supervisors as $supervisor)
                                                    <option value="{{ $supervisor->id }}" {{ request('supervisor') == $supervisor->id ? 'selected' : '' }}>
                                                        {{ $supervisor->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="table-responsive-container">
                                    <div class="table-responsive">
                                        <table class="table table-bordered" id="timePlanTable">
                                            <thead>
                                                <tr>
                                                    <th>Office Worker</th>
                                                    @php
                                                        $startDate = isset($selectedPeriod) 
                                                            ? \Carbon\Carbon::parse($selectedPeriod) 
                                                            : \Carbon\Carbon::now()->subDays($currentDate->dayOfWeek)->addDays($currentPeriod * 21);
                                                        $endDate = $startDate->copy()->addDays(20);
                                                    @endphp
                                                    @for ($date = $startDate; $date <= $endDate; $date->addDay())
                                                        <th class="{{ in_array($date->format('Y-m-d'), $holidays->toArray()) ? 'holiday-column' : '' }}">
                                                            {{ $date->format('d M') }} ({{ $date->format('D') }})
                                                        </th>
                                                    @endfor
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <!-- Reception row -->
                                                <tr>
                                                    <td>Reception</td>
                                                    @for ($date = Carbon\Carbon::parse($selectedPeriod); $date <= Carbon\Carbon::parse($selectedPeriod)->addDays(20); $date->addDay())
                                                        <td class="{{ in_array($date->format('Y-m-d'), $holidays->toArray()) ? '' : '' }}">
                                                            {{ $receptionData[$date->format('Y-m-d')] ?? '' }}
                                                        </td>
                                                    @endfor
                                                </tr>
                                                
                                                <!-- Staff hours rows -->
                                                @foreach($staffMembers as $staffMember)
                                                    <tr>
                                                        <td>{{ $staffMember->name }}</td>
                                                        @for ($date = Carbon\Carbon::parse($selectedPeriod); $date <= Carbon\Carbon::parse($selectedPeriod)->addDays(20); $date->addDay())
                                                            @php
                                                                $dateString = $date->format('Y-m-d');
                                                                $timeValues = $staffHours[$staffMember->id][$dateString] ?? [];
                                                            @endphp
                                                            <td class="{{ in_array($date->format('Y-m-d'), $holidays->toArray()) ? '' : '' }}">
                                                                @foreach($timeValues as $timeValue)
                                                                    @if(isset($timeValue['type']))
                                                                        {{ $timeValue['type'] }}
                                                                    @else
                                                                        {{ $timeValue['start_time'] }}-{{ $timeValue['end_time'] }}<br>
                                                                    @endif
                                                                @endforeach
                                                            </td>
                                                        @endfor
                                                    </tr>
                                                @endforeach
                                                
                                                <!-- Midnight Phone row -->
                                                @if($displayMidnightPhone == 1)
                                                <tr>
                                                    <td>Midnight Phone</td>
                                                    @for ($date = Carbon\Carbon::parse($selectedPeriod); $date <= Carbon\Carbon::parse($selectedPeriod)->addDays(20); $date->addDay())
                                                        @php
                                                            $dateString = $date->format('Y-m-d');
                                                            $staffId = $midnightPhoneData[$dateString] ?? null;
                                                            $staffName = $staffMembers->where('id', $staffId)->first()->name ?? '';
                                                        @endphp
                                                        <td class="{{ in_array($date->format('Y-m-d'), $holidays->toArray()) ? '' : '' }}">
                                                            {{ $staffName }}
                                                        </td>
                                                    @endfor
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
            </div>
        </div>
    </div>
@endsection

<script>
document.addEventListener('DOMContentLoaded', function() {
    const periodSelect = document.getElementById('period');
    const supervisorSelect = document.getElementById('supervisor');
    const tableContainer = document.querySelector('.table-responsive');

    let isDragging = false;
    let startX, startY, scrollLeft, scrollTop;

    const startDragging = (e) => {
        isDragging = true;
        tableContainer.style.cursor = 'grabbing';
        
        // Get the current scroll position and mouse position
        startX = e.pageX - tableContainer.offsetLeft;
        startY = e.pageY - tableContainer.offsetTop;
        scrollLeft = tableContainer.scrollLeft;
        scrollTop = tableContainer.scrollTop;
    };

    const stopDragging = () => {
        isDragging = false;
        tableContainer.style.cursor = 'grab';
    };

    const drag = (e) => {
        if (!isDragging) return;
        e.preventDefault();

        // Calculate the new scroll position
        const x = e.pageX - tableContainer.offsetLeft;
        const y = e.pageY - tableContainer.offsetTop;
        const moveX = x - startX;
        const moveY = y - startY;

        // Apply the scroll
        tableContainer.scrollLeft = scrollLeft - moveX;
        tableContainer.scrollTop = scrollTop - moveY;
    };

    // Add event listeners
    tableContainer.addEventListener('mousedown', startDragging);
    tableContainer.addEventListener('mousemove', drag);
    tableContainer.addEventListener('mouseup', stopDragging);
    tableContainer.addEventListener('mouseleave', stopDragging);

    // Existing event listeners
    function updatePage() {
        let url = new URL(window.location.href);
        url.searchParams.set('period', periodSelect.value);
        url.searchParams.set('supervisor', supervisorSelect.value);
        window.location.href = url.toString();
    }

    periodSelect.addEventListener('change', updatePage);
    supervisorSelect.addEventListener('change', updatePage);
});
</script>

<style>
    .content-page {
        display: flex;
        flex-direction: column;
        min-height: 100vh;
    }
    .content {
        flex: 1 0 auto;
        overflow-x: auto;
    }
    .table-responsive-container {
        max-height: calc(100vh - 350px);
        overflow: auto;
        margin-bottom: 20px;
        cursor: grab;
        user-select: none;
        width: 100%;
    }
    .table-responsive-container:active {
        cursor: grabbing;
    }
    .table-responsive {
        max-height: none;
        overflow: auto;
        min-width: 100%;
        cursor: grab;
        user-select: none;
        position: relative;
    }
    .table-responsive:active {
        cursor: grabbing;
    }
    #timePlanTable {
        min-width: max-content;
        font-size: 0.75rem;
        width: auto;
        table-layout: fixed;
    }
    #timePlanTable th,
    #timePlanTable td {
        min-width: 100px;
        white-space: nowrap;
    }
    #timePlanTable th:first-child,
    #timePlanTable td:first-child {
        min-width: 150px;
    }
    #timePlanTable thead th {
        position: sticky;
        top: 0;
        z-index: 1;
    }
    #timePlanTable tbody td:first-child,
    #timePlanTable thead th:first-child {
        position: sticky;
        left: 0;
        z-index: 2;
        background: #424c5a !important;
    }
    
    #timePlanTable tbody td div{
        width:80px;
        text-align:center;
    }
    #timePlanTable thead th:first-child {
        z-index: 3;
    }
    body.enlarged .content-page {
        margin-left: 0;
    }
    body.enlarged .content {
        overflow-x: auto;
    }
    @media (min-width: 768px) {
        body:not(.enlarged) .content-page {
            margin-left: 240px;
        }
    }
    #timePlanTable input,
    #timePlanTable .btn {
        font-size: 0.75rem;
        padding: 0.2rem 0.5rem;
    }
    .holiday-column {
        background-color: #dc3545 !important;
        color: #856404;
    }
    #timePlanTable tbody td.holiday-column {
        background-color: #dc3545 !important;
    }
    #timePlanTable thead th.holiday-column {
        background-color: #dc3545 !important;
    }
</style>
