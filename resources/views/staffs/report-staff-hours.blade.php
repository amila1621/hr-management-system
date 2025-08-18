@extends('partials.main')
@section('content')

<style>
     .unapproved-hours {
        background-color: rgba(255, 193, 7, 0.1);
        border: 1px solid rgba(255, 193, 7, 0.3);
        border-radius: 4px;
        padding: 2px;
    }

    .unapproved-entry {
        background-color: rgba(255, 193, 7, 0.15);
        border-radius: 3px;
        padding: 2px;
        border: 1px solid rgba(255, 193, 7, 0.4);
    }

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

    .unapproved-input {
        background-color: rgba(255, 193, 7, 0.1) !important;
        border-color: #ffc107 !important;
    }

    .unapproved-container .time-start,
    .unapproved-container .time-end {
        background-color: rgba(255, 255, 255, 0.9);
        border-color: #ffc107;
        box-shadow: 0 0 0 1px rgba(255, 193, 7, 0.3);
    }

    .unapproved-container:hover {
        background-color: rgba(255, 193, 7, 0.3);
        border-color: #e0a800;
        transition: all 0.2s ease;
    }

    /* Special display for day-off unapproved entries */
    .unapproved-container.day-off-container .special-display {
        border-color: #ffc107 !important;
        box-shadow: 0 0 0 2px rgba(255, 193, 7, 0.3) !important;
    }

    /* On-call unapproved styling */
    .unapproved-container.on-call-container {
        background: linear-gradient(135deg, rgba(255, 193, 7, 0.2), rgba(40, 167, 69, 0.1));
        border: 1px solid #ffc107;
    }

    /* Reception unapproved styling */
    .unapproved-container.reception-container {
        background: linear-gradient(135deg, rgba(255, 193, 7, 0.2), rgba(0, 123, 255, 0.1));
        border: 1px solid #ffc107;
    }

    /* Approved records styling */
    .approved-container {
        background-color: rgba(40, 167, 69, 0.1);
        border: 1px solid #28a745;
        border-radius: 4px;
        padding: 3px;
        position: relative;
    }

    .approved-input {
        background-color: rgba(40, 167, 69, 0.05) !important;
        border-color: #28a745 !important;
        cursor: not-allowed;
    }

    .approved-badge {
        position: absolute;
        top: -8px;
        right: -5px;
        background: #28a745;
        color: white;
        font-size: 0.6rem;
        font-weight: bold;
        padding: 1px 4px;
        border-radius: 3px;
        z-index: 5;
        border: 1px solid #1e7e34;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
    }

    /* Hover effect for approved containers */
    .approved-container:hover {
        background-color: rgba(40, 167, 69, 0.15);
        border-color: #1e7e34;
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

<link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.10.0/js/bootstrap-datepicker.min.js"></script>

<!-- Add Flatpickr library -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<div class="content-page">
    <div class="content">
        <div class="container-fluid">
            <div class="page-title-box">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h4 class="page-title">Manage Roster</h4>
                    </div>
                </div>
            </div>

            @if (session()->has('error'))
            <div class="alert alert-danger alert-dismissible">
                {{ session()->get('error') }}
            </div>
            @endif

            @if (session()->has('success'))
            <div class="alert alert-success alert-dismissible">
                {{ session()->get('success') }}
            </div>
            @endif

            @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            <!-- Week Navigation -->
            <div class="day-selector">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <a href="{{ route(Route::currentRouteName(), ['week' => Carbon\Carbon::parse($selectedDate)->subWeek()->format('Y-m-d')]) }}" class="btn btn-light btn-sm">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    </div>
                    <div class="col text-center">
                        <h5 class="mb-0">
                            {{ Carbon\Carbon::parse($selectedDate)->startOfWeek()->format('d M') }} - 
                            {{ Carbon\Carbon::parse($selectedDate)->endOfWeek()->format('d M, Y') }}
                        </h5>
                    </div>
                    <div class="col-auto">
                        <a href="{{ route(Route::currentRouteName(), ['week' => Carbon\Carbon::parse($selectedDate)->addWeek()->format('Y-m-d')]) }}" class="btn btn-light btn-sm">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Department Tabs -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <!-- Tab Navigation -->
                            <ul class="nav nav-tabs" id="departmentTabs" role="tablist">
                                @foreach($staffByDepartment as $department => $departmentStaff)
                                    <li class="nav-item" role="presentation">
                                        <a class="nav-link {{ $loop->first ? 'active' : '' }}" 
                                           id="tab-{{ Str::slug($department) }}" 
                                           data-toggle="tab" 
                                           href="#dept-{{ Str::slug($department) }}" 
                                           role="tab" 
                                           aria-controls="dept-{{ Str::slug($department) }}" 
                                           aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                                            {{ $department }} 
                                            <span class="badge badge-secondary ml-1">{{ $departmentStaff->count() }}</span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>

                            <!-- Tab Content -->
                            <div class="tab-content" id="departmentTabContent">
                                @foreach($staffByDepartment as $department => $departmentStaff)
                                    <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" 
                                         id="dept-{{ Str::slug($department) }}" 
                                         role="tabpanel" 
                                         aria-labelledby="tab-{{ Str::slug($department) }}">
                                        
                                        <div class="mt-3">
                                            <!-- Update each department form to include the department name -->
                                            <form action="{{ route('staff.working-hours.store') }}" method="POST" class="department-form">
                                                @csrf
                                                <input type="hidden" name="week" value="{{ $selectedDate }}">
                                                <!-- ADD THIS LINE: Pass the current department -->
                                                <input type="hidden" name="department" value="{{ $department }}">
                                                
                                                <div class="table-responsive-container" style="-webkit-overflow-scrolling: touch;">
                                                    <table class="table table-striped table-bordered working-hours-table" 
                                                           style="border-collapse: collapse; border-spacing: 0; width: 100%">
                                                        <thead>
                                                            <tr>
                                                                <th>Office Worker</th>
                                                                @foreach($dates as $date)
                                                                <th class="{{ in_array($date->format('Y-m-d'), $holidays->toArray()) ? 'holiday-column' : '' }}">
                                                                    {{ $date->format('d M (D)') }}
                                                                </th>
                                                                @endforeach
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <!-- Reception row (only in first tab) -->
                                                            @if($loop->first)
                                                            <tr>
                                                                <td>Note</td>
                                                                @foreach($dates as $date)
                                                                @php $dateString = $date->format('Y-m-d'); @endphp
                                                                <td class="{{ in_array($dateString, $holidays->toArray()) ? '' : '' }}">
                                                                    <input type="text" name="reception[{{ $dateString }}]"
                                                                        value="{{ $receptionData[$dateString] ?? '' }}"
                                                                        class="form-control form-control-sm">
                                                                </td>
                                                                @endforeach
                                                            </tr>
                                                            @endif

                                                            <!-- Department Staff rows -->
                                                            @foreach($departmentStaff as $staff)
                                                            <tr data-staff-id="{{ $staff->id }}" data-staff-email="{{ $staff->email }}">
                                                                <td>{{ $staff->name }}</td>
                                                                @foreach($dates as $date)
                                                                @php
                                                                $dateString = $date->format('Y-m-d');
                                                                $hoursData = $staffHours[$staff->id][$dateString]['hours_data'] ?? [];
                                                                // Only set approval status if record exists, otherwise null for empty slots
                                                                $isApproved = isset($staffHours[$staff->id][$dateString]) ? $staffHours[$staff->id][$dateString]['is_approved'] : null;
                                                                $staffDateKey = $staff->id . '_' . $dateString;
                                                                $sickLeaveInfo = $sickLeaveStatuses[$staffDateKey] ?? null;
                                                                @endphp
                                                                <td class="{{ in_array($dateString, $holidays->toArray()) ? '' : '' }}">
                                                                    <!-- Display sick leave status if exists -->
                                                                    @if($sickLeaveInfo)
                                                                        @php
                                                                        $statusText = '';
                                                                        $statusClass = '';
                                                                        switch($sickLeaveInfo->status) {
                                                                            case '0':
                                                                                $statusText = 'Sick Leave - Pending from supervisor';
                                                                                $statusClass = 'text-warning';
                                                                                break;
                                                                            case '1':
                                                                                $statusText = 'Sick Leave - Pending from HR';
                                                                                $statusClass = 'text-warning';
                                                                                break;
                                                                            case '2':
                                                                                $statusText = 'Sick Leave - Approved';
                                                                                $statusClass = 'text-success';
                                                                                break;
                                                                            case '3':
                                                                                $statusText = 'Sick Leave - Rejected';
                                                                                $statusClass = 'text-danger';
                                                                                break;
                                                                            case '4':
                                                                                $statusText = 'Sick Leave - Cancelled';
                                                                                $statusClass = 'text-muted';
                                                                                break;
                                                                            default:
                                                                                $statusText = 'Sick Leave - Unknown status';
                                                                                $statusClass = 'text-secondary';
                                                                        }
                                                                        @endphp
                                                                        <div class="alert alert-warning alert-sm mb-2 p-2" style="font-size: 12px; line-height: 1.3;">
                                                                            <strong class="{{ $statusClass }}">{{ $statusText }}</strong>
                                                                            @if($sickLeaveInfo->description)
                                                                                <br><small><strong>Description:</strong> {{ $sickLeaveInfo->description }}</small>
                                                                            @endif
                                                                            @if($sickLeaveInfo->supervisor_remark)
                                                                                <br><small><strong>Supervisor:</strong> {{ $sickLeaveInfo->supervisor_remark }}</small>
                                                                            @endif
                                                                            @if($sickLeaveInfo->admin_remark)
                                                                                <br><small><strong>Admin:</strong> {{ $sickLeaveInfo->admin_remark }}</small>
                                                                            @endif
                                                                            @if($sickLeaveInfo->start_date && $sickLeaveInfo->end_date)
                                                                                <br><small><strong>Period:</strong> {{ Carbon\Carbon::parse($sickLeaveInfo->start_date)->format('M d') }} - {{ Carbon\Carbon::parse($sickLeaveInfo->end_date)->format('M d, Y') }}</small>
                                                                            @endif
                                                                        </div>
                                                                    @endif
                                                                    
                                                                    <div class="time-slots">
                                                                        <!-- Debug: Show count of time slots -->
                                                                        @if(count($hoursData) > 0)
                                                                            <!-- Found {{ count($hoursData) }} time slots for {{ $staff->name }} on {{ $dateString }} -->
                                                                        @else
                                                                            <!-- No time slots found for {{ $staff->name }} on {{ $dateString }} -->
                                                                        @endif
                                                                        
                                                                        @forelse($hoursData as $index => $timeRange)
                                                                        <div class="time-slot mb-1">
                                                                            <div class="time-input-container">
                                                                                @php
                                                                                    // Check if current user should be restricted from editing this staff member's hours
                                                                                    $currentUserEmail = Auth::user()->email ?? '';
                                                                                    $isRestrictedUser = in_array($currentUserEmail, ['beatriz@nordictravels.eu', 'semi@nordictravels.eu']);
                                                                                    $isOwnRoster = $isRestrictedUser && $currentUserEmail === $staff->email;
                                                                                    
                                                                                    $isOnCall = false;
                                                                                    $isReception = false;
                                                                                    $isSpecialType = false;
                                                                                    $displayStartTime = '';
                                                                                    $displayEndTime = '';
                                                                                    $hiddenValue = '';
                                                                                    
                                                                                    // Check if it's an array (from database)
                                                                                    if (is_array($timeRange)) {
                                                                                        if (isset($timeRange['type']) && $timeRange['type'] === 'on_call') {
                                                                                            $isOnCall = true;
                                                                                            $displayStartTime = $timeRange['start_time'] ?? '';
                                                                                            $displayEndTime = $timeRange['end_time'] ?? '';
                                                                                            $hiddenValue = json_encode([
                                                                                                'start_time' => $displayStartTime,
                                                                                                'end_time' => $displayEndTime,
                                                                                                'type' => 'on_call'
                                                                                            ]);
                                                                                        } elseif (isset($timeRange['type']) && $timeRange['type'] === 'reception') {
                                                                                            $isReception = true;
                                                                                            $displayStartTime = $timeRange['start_time'] ?? '';
                                                                                            $displayEndTime = $timeRange['end_time'] ?? '';
                                                                                            $hiddenValue = json_encode([
                                                                                                'start_time' => $displayStartTime,
                                                                                                'end_time' => $displayEndTime,
                                                                                                'type' => 'reception'
                                                                                            ]);
                                                                                        } elseif (isset($timeRange['type']) && in_array($timeRange['type'], ['V', 'X', 'H', 'SL'])) {
                                                                                            $isSpecialType = true;
                                                                                            $hiddenValue = $timeRange['type'];
                                                                                        } elseif (isset($timeRange['start_time']) && isset($timeRange['end_time'])) {
                                                                                            $displayStartTime = $timeRange['start_time'];
                                                                                            $displayEndTime = $timeRange['end_time'];
                                                                                            // Preserve the full JSON object to maintain notes and other data
                                                                                            $hiddenValue = json_encode($timeRange);
                                                                                        }
                                                                                    }
                                                                                    // Handle string values
                                                                                    elseif (is_string($timeRange)) {
                                                                                        if (in_array($timeRange, ['V', 'X', 'H', 'SL'])) {
                                                                                            $isSpecialType = true;
                                                                                            $hiddenValue = $timeRange;
                                                                                        } elseif (strpos($timeRange, '-') !== false) {
                                                                                            list($displayStartTime, $displayEndTime) = explode('-', $timeRange);
                                                                                            $hiddenValue = $timeRange;
                                                                                        } else {
                                                                                            $hiddenValue = $timeRange;
                                                                                        }
                                                                                    }
                                                                                @endphp

                                                                                <input type="hidden"
                                                                                    name="hours[{{ $staff->id }}][{{ $dateString }}][]"
                                                                                    id="hidden-time-{{ $staff->id }}-{{ $dateString }}-{{ $index ?? 0 }}"
                                                                                    value="{{ $hiddenValue }}"
                                                                                    class="form-control form-control-sm time-range {{ isset($timeRange['type']) && $timeRange['type'] === 'SL' ? 'sick-leave-entry' : '' }} {{ $isOwnRoster ? 'own-roster-disabled' : '' }}"
                                                                                    data-sick-leave="{{ isset($timeRange['type']) && $timeRange['type'] === 'SL' ? 'true' : '' }}"
                                                                                    {{ $isOwnRoster ? 'disabled' : '' }}>

                                                                                @if($isSpecialType)
                                                                                    <div class="time-pickers special-type {{ $isOwnRoster ? 'roster-disabled' : '' }}" style="display: none;" data-day-off-type="{{ $hiddenValue }}">
                                                                                @elseif($isOnCall)
                                                                                    <div class="time-pickers on-call-type d-flex align-items-center {{ $isOwnRoster ? 'roster-disabled' : '' }}" style="flex: 1;">
                                                                                        <i class="fas fa-phone on-call-icon" style="color: #28a745; margin-right: 5px; font-size: 14px;"></i>
                                                                                @elseif($isReception)
                                                                                    <div class="time-pickers reception-type d-flex align-items-center {{ $isOwnRoster ? 'roster-disabled' : '' }}" style="flex: 1;">
                                                                                        <i class="fas fa-desktop reception-icon" style="color: #007bff; margin-right: 5px; font-size: 14px;"></i>
                                                                                @else
                                                                                    <div class="time-pickers d-flex align-items-center {{ $isOwnRoster ? 'roster-disabled' : '' }}" style="flex: 1;">
                                                                                @endif
                                                                                
                                                                                    @if(!$isSpecialType)
                                                                                        @if($isApproved === 0)
                                                                                            <div class="unapproved-container">
                                                                                                <div class="basic-time-row">
                                                                                                    <input type="time" 
                                                                                                        class="form-control form-control-sm time-start"
                                                                                                        value="{{ $displayStartTime }}"
                                                                                                        {{ $isSpecialType || $isOwnRoster ? 'disabled' : '' }}>
                                                                                                    
                                                                                                    <span class="time-separator mx-1">-</span>
                                                                                                    
                                                                                                    <input type="time" 
                                                                                                        class="form-control form-control-sm time-end"
                                                                                                        value="{{ $displayEndTime }}"
                                                                                                        {{ $isSpecialType || $isOwnRoster ? 'disabled' : '' }}>
                                                                                                </div>
                                                                                            </div>
                                                                                        @elseif($isApproved === 1)
                                                                                            <div class="basic-time-row approved-container">
                                                                                                <input type="time" 
                                                                                                    class="form-control form-control-sm time-start approved-input"
                                                                                                    value="{{ $displayStartTime }}"
                                                                                                    disabled
                                                                                                    title="This record has been approved and cannot be modified">
                                                                                                
                                                                                                <span class="time-separator mx-1">-</span>
                                                                                                
                                                                                                <input type="time" 
                                                                                                    class="form-control form-control-sm time-end approved-input"
                                                                                                    value="{{ $displayEndTime }}"
                                                                                                    disabled
                                                                                                    title="This record has been approved and cannot be modified">
                                                                                                
                                                                                                <span class="approved-badge">✓ APPROVED</span>
                                                                                            </div>
                                                                                        @else
                                                                                            <!-- This is for empty slots (isApproved is null) -->
                                                                                            <div class="basic-time-row">
                                                                                                <input type="time" 
                                                                                                    class="form-control form-control-sm time-start"
                                                                                                    value="{{ $displayStartTime }}"
                                                                                                    {{ $isSpecialType || $isOwnRoster ? 'disabled' : '' }}>
                                                                                                
                                                                                                <span class="time-separator mx-1">-</span>
                                                                                                
                                                                                                <input type="time" 
                                                                                                    class="form-control form-control-sm time-end"
                                                                                                    value="{{ $displayEndTime }}"
                                                                                                    {{ $isSpecialType || $isOwnRoster ? 'disabled' : '' }}>
                                                                                            </div>
                                                                                        @endif
                                                                                    @endif
                                                                                </div>

                                                                                @if($isSpecialType)
                                                                                    <div class="special-display d-flex align-items-center justify-content-center" style="flex: 1; padding: 5px; background-color: #f8f9fa; border-radius: 3px;">
                                                                                        <strong>{{ $hiddenValue }}</strong>
                                                                                    </div>
                                                                                @endif

                                                                                <!-- Add the on-call-container class if it's an on-call entry -->
                                                                                @if($isOnCall)
                                                                                    <script>
                                                                                        document.addEventListener('DOMContentLoaded', function() {
                                                                                            const container = document.getElementById('hidden-time-{{ $staff->id }}-{{ $dateString }}-{{ $index ?? 0 }}').closest('.time-input-container');
                                                                                            if (container) {
                                                                                                container.classList.add('on-call-container');
                                                                                            }
                                                                                        });
                                                                                    </script>
                                                                                @endif

                                                                                @if($isSpecialType)
                                                                                    <script>
                                                                                        document.addEventListener('DOMContentLoaded', function() {
                                                                                            const container = document.getElementById('hidden-time-{{ $staff->id }}-{{ $dateString }}-{{ $index ?? 0 }}').closest('.time-input-container');
                                                                                            if (container) {
                                                                                                container.classList.add('day-off-container');
                                                                                                container.setAttribute('data-day-off-type', '{{ $hiddenValue }}');
                                                                                            }
                                                                                        });
                                                                                    </script>
                                                                                @endif

                                                                                @if($isReception)
                                                                                    <script>
                                                                                        document.addEventListener('DOMContentLoaded', function() {
                                                                                            const container = document.getElementById('hidden-time-{{ $staff->id }}-{{ $dateString }}-{{ $index ?? 0 }}').closest('.time-input-container');
                                                                                            if (container) {
                                                                                                container.classList.add('reception-container');
                                                                                            }
                                                                                        });
                                                                                    </script>
                                                                                @endif

                                                                                <div class="dropdown d-inline-block">
                                                                                    <button class="btn btn-sm btn-secondary dropdown-toggle {{ $isOwnRoster || $isApproved === 1 ? 'disabled' : '' }}" 
                                                                                            type="button" 
                                                                                            data-toggle="dropdown"
                                                                                            {{ $isOwnRoster || $isApproved === 1 ? 'disabled' : '' }}
                                                                                            {{ $isApproved === 1 ? 'title=This record has been approved and cannot be modified' : '' }}>
                                                                                        <i class="fas fa-ellipsis-h"></i>
                                                                                    </button>
                                                                                    @if(!$isOwnRoster && $isApproved !== 1)
                                                                                    <div class="dropdown-menu">
                                                                                        <button type="button" class="dropdown-item quick-fill" data-value="V">Day Off (V)</button>
                                                                                        <button type="button" class="dropdown-item quick-fill" data-value="X">Day Off (X)</button>
                                                                                        <button type="button" class="dropdown-item quick-fill" data-value="H">Holiday (H)</button>
                                                                                        <button type="button" class="dropdown-item quick-fill" data-value="regular">Regular Hours</button>
                                                                                        @if($department === 'Operations')
                                                                                        <button type="button" class="dropdown-item quick-fill" data-value="on_call">On Call</button>
                                                                                        @endif
                                                                                        @if($department === 'Booking' || $department === 'Package Booking')
                                                                                        <button type="button" class="dropdown-item quick-fill" data-value="reception">Reception</button>
                                                                                        @endif
                                                                                        <div class="dropdown-divider"></div>
                                                                                        <button type="button" class="dropdown-item add-time-slot">
                                                                                            <i class="fas fa-plus-circle text-success"></i>
                                                                                            <span class="ml-2">Add New Time Slot</span>
                                                                                        </button>
                                                                                        <button type="button" class="dropdown-item remove-time-slot">
                                                                                            <i class="fas fa-minus-circle text-danger"></i>
                                                                                            <span class="ml-2">Remove This Slot</span>
                                                                                        </button>
                                                                                    </div>
                                                                                    @else
                                                                                    <div class="dropdown-menu">
                                                                                        <div class="dropdown-item-text text-muted">
                                                                                            <i class="fas fa-lock mr-2"></i>Cannot edit own roster
                                                                                        </div>
                                                                                    </div>
                                                                                    @endif
                                                                                </div>

                                                                                @if($isOwnRoster)
                                                                                <!-- Add visual indicator for disabled roster -->
                                                                                <div class="own-roster-overlay">
                                                                                    <i class="fas fa-lock text-muted"></i>
                                                                                </div>
                                                                                @endif
                                                                            </div>

                                                                            <!-- Notes Field -->
                                                                            @if(!$isOwnRoster && !$isSpecialType)
                                                                            <div class="notes-container mt-2 mb-2">
                                                                                <small class="text-muted d-block mb-1" style="font-size: 10px;">Notes:</small>
                                                                                <textarea class="form-control form-control-sm notes-input {{ $isApproved === 1 ? 'approved-input' : '' }}" 
                                                                                         rows="2" 
                                                                                         placeholder="{{ $isApproved === 1 && empty($timeRange['notes'] ?? '') ? 'Notes (Read-only - Record approved)' : 'Add notes for this time entry...' }}"
                                                                                         {{ $isApproved === 1 ? 'disabled readonly title="This record has been approved and cannot be modified"' : '' }}
                                                                                         style="font-size: 11px; resize: vertical; min-height: 35px; width: 100%;">{{ $timeRange['notes'] ?? '' }}</textarea>
                                                                            </div>
                                                                            @endif
                                                                        </div>
                                                                        @empty
                                                                        <!-- Empty time slot for new entries -->
                                                                        <div class="time-slot mb-1">
                                                                            <div class="time-input-container">
                                                                                @php
                                                                                    $currentUserEmail = Auth::user()->email ?? '';
                                                                                    $isRestrictedUser = in_array($currentUserEmail, ['beatriz@nordictravels.eu', 'semi@nordictravels.eu']);
                                                                                    $isOwnRoster = $isRestrictedUser && $currentUserEmail === $staff->email;
                                                                                @endphp

                                                                                <input type="hidden"
                                                                                    name="hours[{{ $staff->id }}][{{ $dateString }}][]"
                                                                                    id="hidden-time-{{ $staff->id }}-{{ $dateString }}-0"
                                                                                    class="form-control form-control-sm time-range {{ $isOwnRoster ? 'own-roster-disabled' : '' }}"
                                                                                    value=""
                                                                                    {{ $isOwnRoster ? 'disabled' : '' }}>

                                                                                <div class="time-pickers d-flex align-items-center {{ $isOwnRoster ? 'roster-disabled' : '' }}" style="flex: 1;">
                                                                                    <div class="basic-time-row">
                                                                                        <input type="time" 
                                                                                            class="form-control form-control-sm time-start"
                                                                                            {{ $isOwnRoster ? 'disabled' : '' }}>
                                                                                        
                                                                                        <span class="time-separator mx-1">-</span>
                                                                                        
                                                                                        <input type="time" 
                                                                                            class="form-control form-control-sm time-end"
                                                                                            {{ $isOwnRoster ? 'disabled' : '' }}>
                                                                                    </div>
                                                                                </div>

                                                                                <div class="dropdown d-inline-block">
                                                                                    <button class="btn btn-sm btn-secondary dropdown-toggle {{ $isOwnRoster ? 'disabled' : '' }}" 
                                                                                            type="button" 
                                                                                            data-toggle="dropdown"
                                                                                            {{ $isOwnRoster ? 'disabled' : '' }}>
                                                                                        <i class="fas fa-ellipsis-h"></i>
                                                                                    </button>
                                                                                    @if(!$isOwnRoster)
                                                                                    <div class="dropdown-menu">
                                                                                        <button type="button" class="dropdown-item quick-fill" data-value="V">Day Off (V)</button>
                                                                                        <button type="button" class="dropdown-item quick-fill" data-value="X">Day Off (X)</button>
                                                                                        <button type="button" class="dropdown-item quick-fill" data-value="SL">Sick Leave (SL)</button>
                                                                                        <button type="button" class="dropdown-item quick-fill" data-value="H">Holiday (H)</button>
                                                                                        <button type="button" class="dropdown-item quick-fill" data-value="regular">Regular Hours</button>
                                                                                        @if($department === 'Operations')
                                                                                        <button type="button" class="dropdown-item quick-fill" data-value="on_call">On Call</button>
                                                                                        @endif
                                                                                        @if($department === 'Booking' || $department === 'Package Booking')
                                                                                        <button type="button" class="dropdown-item quick-fill" data-value="reception">Reception</button>
                                                                                        @endif
                                                                                        <div class="dropdown-divider"></div>
                                                                                        <button type="button" class="dropdown-item add-time-slot">
                                                                                            <i class="fas fa-plus-circle text-success"></i>
                                                                                            <span class="ml-2">Add New Time Slot</span>
                                                                                        </button>
                                                                                        <button type="button" class="dropdown-item remove-time-slot">
                                                                                            <i class="fas fa-minus-circle text-danger"></i>
                                                                                            <span class="ml-2">Remove This Slot</span>
                                                                                        </button>
                                                                                    </div>
                                                                                    @else
                                                                                    <div class="dropdown-menu">
                                                                                        <div class="dropdown-item-text text-muted">
                                                                                            <i class="fas fa-lock mr-2"></i>Cannot edit own roster
                                                                                        </div>
                                                                                    </div>
                                                                                    @endif
                                                                                </div>

                                                                                @if($isOwnRoster)
                                                                                <div class="own-roster-overlay">
                                                                                    <i class="fas fa-lock text-muted"></i>
                                                                                </div>
                                                                                @endif
                                                                            </div>

                                                                            <!-- Notes Field for Empty Slot -->
                                                                            @if(!$isOwnRoster)
                                                                            <div class="notes-container mt-2 mb-2">
                                                                                <small class="text-muted d-block mb-1" style="font-size: 10px;">Notes:</small>
                                                                                <textarea class="form-control form-control-sm notes-input" 
                                                                                         rows="2" 
                                                                                         placeholder="Add notes for this time entry..."
                                                                                         style="font-size: 11px; resize: vertical; min-height: 35px; width: 100%;"></textarea>
                                                                            </div>
                                                                            @endif
                                                                        </div>
                                                                        @endforelse
                                                                    </div>
                                                                </td>
                                                                @endforeach
                                                            </tr>
                                                            @endforeach

                                                            <!-- Midnight Phone row (only in first tab) -->
                                                            @if($loop->first && $displayMidnightPhone == 1)
                                                                <tr>
                                                                    <td>Midnight Phone</td>
                                                                    @foreach($dates as $date)
                                                                    @php $dateString = $date->format('Y-m-d'); @endphp
                                                                    <td class="{{ in_array($dateString, $holidays->toArray()) ? '' : '' }}">
                                                                        <select name="midnight_phone[{{ $dateString }}]" class="form-control form-control-sm">
                                                                            <option value="">-- No Assignment --</option>
                                                                            @foreach($allStaffFlattened  as $allStaff)
                                                                            <option value="{{ $allStaff->id }}"
                                                                                {{ ($midnightPhoneData[$dateString] ?? '') == $allStaff->id ? 'selected' : '' }}>
                                                                                {{ $allStaff->name }}
                                                                            </option>
                                                                            @endforeach
                                                                        </select>
                                                                    </td>
                                                                    @endforeach
                                                                </tr>
                                                                @endif
                                                        </tbody>
                                                    </table>
                                                </div>

                                                <div class="mt-3 text-center">
                                                    <!-- Enhanced submit button with sticky container -->
                                                    <div class="submit-button-container">
                                                        <button type="submit" class="btn btn-primary waves-effect waves-light">
                                                            <i class="fas fa-save mr-2"></i>
                                                            Submit {{ $department }} Hours
                                                        </button>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Define functions in the global scope first
window.formatTime = function(timeString) {
    if (!timeString) return '';
    
    const [hour, minute] = timeString.split(':');
    const hourNum = parseInt(hour, 10);
    const hourFormatted = hourNum.toString().padStart(2, '0');
    
    return `${hourFormatted}:${minute}`;
};

window.markAsUnsaved = function() {
    window.hasUnsavedChanges = true;
};

window.updateHiddenInput = function(container) {
    const startInput = container.querySelector('.time-start');
    const endInput = container.querySelector('.time-end');
    const hiddenInput = container.querySelector('input[type="hidden"]');
    const timePickers = container.querySelector('.time-pickers');
    
    // Look for notes input in the parent time-slot since it's outside the time-input-container
    const timeSlot = container.closest('.time-slot');
    const notesInput = timeSlot ? timeSlot.querySelector('.notes-input') : null;

    if (!startInput || !endInput || !hiddenInput) return;

    if (startInput.disabled && ['V', 'X', 'H'].includes(hiddenInput.value)) {
        return;
    }

    // Store original value if not already stored AND it's not a special type entry
    const inputId = hiddenInput.id || hiddenInput.name;
    if (!window.originalTimeValues.has(inputId) && 
        hiddenInput.value && 
        hiddenInput.value.includes('-') && 
        !hiddenInput.value.includes('on_call') && 
        !hiddenInput.value.includes('reception')) { 
        window.originalTimeValues.set(inputId, hiddenInput.value);
    }

    if (startInput.value && endInput.value) {
        // Validate time range - ADD THIS VALIDATION
        const isValid = validateTimeRange(container);
        
        if (!isValid) {
            // Don't update hidden input if validation fails
            return;
        }
        
        // Check if this is an on-call entry
        if (timePickers && timePickers.classList.contains('on-call-type')) {
            const onCallData = {
                start_time: startInput.value,
                end_time: endInput.value,
                type: 'on_call'
            };
            // Add notes if available
            if (notesInput && notesInput.value.trim()) {
                onCallData.notes = notesInput.value.trim();
            }
            hiddenInput.value = JSON.stringify(onCallData);
        }
        // Check if this is a reception entry
        else if (timePickers && timePickers.classList.contains('reception-type')) {
            const receptionData = {
                start_time: startInput.value,
                end_time: endInput.value,
                type: 'reception'
            };
            // Add notes if available
            if (notesInput && notesInput.value.trim()) {
                receptionData.notes = notesInput.value.trim();
            }
            hiddenInput.value = JSON.stringify(receptionData);
        } else {
            // Always use JSON format for consistency
            const timeData = {
                start_time: startInput.value,
                end_time: endInput.value,
                type: 'normal'
            };
            // Add notes if available
            if (notesInput && notesInput.value.trim()) {
                timeData.notes = notesInput.value.trim();
            }
            hiddenInput.value = JSON.stringify(timeData);
        }
        window.markAsUnsaved();
    } else if (!startInput.value && !endInput.value) {
        hiddenInput.value = '';
        // Clear any validation errors when both fields are empty
        container.classList.remove('time-validation-error');
        startInput.classList.remove('is-invalid');
        endInput.classList.remove('is-invalid');
        const errorMsg = container.querySelector('.time-validation-message');
        if (errorMsg) {
            errorMsg.style.display = 'none';
        }
    }
};

// Add this validation function right after the existing updateHiddenInput function (around line 585)
window.validateTimeRange = function(container) {
    const startInput = container.querySelector('.time-start');
    const endInput = container.querySelector('.time-end');
    const timePickers = container.querySelector('.time-pickers');
    
    if (!startInput || !endInput || !timePickers) return true;
    
    // Skip validation for disabled inputs or special types
    if (startInput.disabled || endInput.disabled) return true;
    if (!startInput.value || !endInput.value) return true;
    
    const startTime = startInput.value;
    const endTime = endInput.value;
    
    // Convert times to minutes for comparison
    const [startHours, startMinutes] = startTime.split(':').map(Number);
    const [endHours, endMinutes] = endTime.split(':').map(Number);
    
    const startTotalMinutes = startHours * 60 + startMinutes;
    const endTotalMinutes = endHours * 60 + endMinutes;
    
    const isValid = startTotalMinutes < endTotalMinutes;
    
    // Visual feedback
    if (!isValid) {
        // Add error styling
        container.classList.add('time-validation-error');
        startInput.classList.add('is-invalid');
        endInput.classList.add('is-invalid');
        
        // Show error message
        let errorMsg = container.querySelector('.time-validation-message');
        if (!errorMsg) {
            errorMsg = document.createElement('div');
            errorMsg.className = 'time-validation-message';
            container.appendChild(errorMsg);
        }
        errorMsg.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Start time must be before end time';
        errorMsg.style.display = 'block';
        
        // Add shake animation
        container.style.animation = 'shake 0.5s';
        setTimeout(() => {
            container.style.animation = '';
        }, 500);
        
    } else {
        // Remove error styling
        container.classList.remove('time-validation-error');
        startInput.classList.remove('is-invalid');
        endInput.classList.remove('is-invalid');
        
        const errorMsg = container.querySelector('.time-validation-message');
        if (errorMsg) {
            errorMsg.style.display = 'none';
        }
    }
    
    return isValid;
};

// Initialize Flatpickr for time inputs in a container - MOVED TO GLOBAL SCOPE
function initFlatpickrForContainer(container) {
    const startInput = container.querySelector('.time-start');
    const endInput = container.querySelector('.time-end');
    const hiddenInput = container.querySelector('input[type="hidden"]');
    const timePickers = container.querySelector('.time-pickers');

    if (!startInput || !endInput || !hiddenInput) return;

    // Skip if already initialized with Flatpickr
    if (startInput.classList.contains('flatpickr-initialized')) return;

    // CRITICAL FIX: Check if we're in the middle of converting to regular hours
    const isConvertingToRegular = container.classList.contains('converting-to-regular');
    
    // Check if this input should be disabled (day off types) - BUT NOT during conversion
    if (hiddenInput.value && ['V', 'X', 'H'].includes(hiddenInput.value) && !isConvertingToRegular) {
        startInput.value = '';
        endInput.value = '';
        startInput.disabled = true;
        endInput.disabled = true;
        
        container.classList.add('day-off-container');
        container.dataset.dayOffType = hiddenInput.value;
        if (timePickers) timePickers.dataset.dayOffType = hiddenInput.value;
        return; // Don't initialize Flatpickr for disabled inputs
    }

    // IMPORTANT: Force enable inputs for all other cases (including empty values and conversions)
    startInput.disabled = false;
    endInput.disabled = false;

    // Store original value for change detection - but NOT for special type entries
    const inputId = hiddenInput.id || hiddenInput.name;
    if (hiddenInput.value && 
        hiddenInput.value.includes('-') && 
        !hiddenInput.value.includes('on_call') && 
        !hiddenInput.value.includes('reception')) {
        window.originalTimeValues.set(inputId, hiddenInput.value);
    }

    // Check if it's JSON format (on_call, reception, or other types)
    if (hiddenInput.value && hiddenInput.value.startsWith('{')) {
        try {
            const timeData = JSON.parse(hiddenInput.value);
            if (timeData.type === 'on_call') {
                startInput.value = timeData.start_time || '';
                endInput.value = timeData.end_time || '';
                
                // Add on-call styling and icon
                if (timePickers) {
                    timePickers.classList.add('on-call-type');
                    container.classList.add('on-call-container');
                    
                    // Add phone icon if not already present
                    if (!timePickers.querySelector('.on-call-icon')) {
                        const phoneIcon = document.createElement('i');
                        phoneIcon.className = 'fas fa-phone on-call-icon';
                        phoneIcon.style.cssText = 'color: #28a745; margin-right: 5px; font-size: 14px;';
                        timePickers.insertBefore(phoneIcon, timePickers.firstChild);
                    }
                }
            } else if (timeData.type === 'reception') {
                startInput.value = timeData.start_time || '';
                endInput.value = timeData.end_time || '';
                
                // Add reception styling and icon
                if (timePickers) {
                    timePickers.classList.add('reception-type');
                    container.classList.add('reception-container');
                    
                    // Add desktop icon if not already present
                    if (!timePickers.querySelector('.reception-icon')) {
                        const desktopIcon = document.createElement('i');
                        desktopIcon.className = 'fas fa-desktop reception-icon';
                        desktopIcon.style.cssText = 'color: #007bff; margin-right: 5px; font-size: 14px;';
                        timePickers.insertBefore(desktopIcon, timePickers.firstChild);
                    }
                }
            }
        } catch (e) {
            // Parsing error, handle as regular string
            console.error("Error parsing JSON time data:", e);
        }
    } else if (hiddenInput.value && hiddenInput.value.includes('-') && !isConvertingToRegular) {
        // Standard time range like "09:00-17:00" - but NOT during conversion
        const [startTime, endTime] = hiddenInput.value.split('-');
        startInput.value = startTime || '';
        endInput.value = endTime || '';
    }
    // IMPORTANT: If hiddenInput.value is empty OR we're converting, leave inputs empty and enabled

    // TRIPLE CHECK: Ensure inputs are enabled before initializing Flatpickr
    if (startInput.disabled || endInput.disabled) {
        console.log('Inputs are disabled, forcing enable...');
        startInput.disabled = false;
        endInput.disabled = false;
    }

    // Initialize Flatpickr for start time
    if (!startInput.disabled) {
        flatpickr(startInput, {
            enableTime: true,
            noCalendar: true,
            dateFormat: "H:i",
            time_24hr: true,
            minuteIncrement: 1,
            defaultHour: 9,
            defaultMinute: 0,
            allowInput: true,
            onClose: function(selectedDates, dateStr) {
                // Auto-suggest end time if start time is set but end time is empty
                if (dateStr && !endInput.value) {
                    const [hours, minutes] = dateStr.split(':').map(Number);
                    let endHour = hours + 8;
                    if (endHour >= 24) endHour = endHour - 24;
                    
                    const endTimeValue = `${endHour.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}`;
                    if (endInput._flatpickr && typeof endInput._flatpickr.setDate === 'function') {
                        try {
                            endInput._flatpickr.setDate(endTimeValue);
                        } catch (error) {
                            endInput.value = endTimeValue;
                        }
                    } else {
                        endInput.value = endTimeValue;
                    }
                }
                
                // Update the hidden input after time selection
                updateHiddenInput(container);
            }
        });
        
        // Mark as initialized
        startInput.classList.add('flatpickr-initialized');
    }
    
    // Initialize Flatpickr for end time
    if (!endInput.disabled) {
        flatpickr(endInput, {
            enableTime: true,
            noCalendar: true,
            dateFormat: "H:i",
            time_24hr: true,
            minuteIncrement: 1,
            defaultHour: 17,
            defaultMinute: 0,
            allowInput: true,
            onClose: function() {
                // Update the hidden input after time selection
                updateHiddenInput(container);
            }
        });
        
        // Mark as initialized
        endInput.classList.add('flatpickr-initialized');
    }
}

// Initialize all time fields
function initTimeFields() {
    // Find all time field containers
    document.querySelectorAll('.gcal-time-field').forEach(field => {
        if (field.dataset.disabled === 'true') return;
        
        const container = field.closest('.gcal-time-input');
        const dropdown = container.querySelector('.gcal-time-dropdown');
        const timeInput = container.querySelector('input[type="time"]');
        const isStartTime = field.classList.contains('start-time-field');
        
        // Populate the dropdown on first click
        let isDropdownPopulated = false;
        
        // Handle field click - show dropdown
        field.addEventListener('click', function() {
            if (field.dataset.disabled === 'true') return;
            
            // Close all other dropdowns first
            document.querySelectorAll('.gcal-time-dropdown.show').forEach(d => {
                if (d !== dropdown) d.classList.remove('show');
            });
            
            // Populate dropdown if needed
            if (!isDropdownPopulated) {
                populateTimeDropdown(dropdown);
                isDropdownPopulated = true;
            }
            
            // Toggle dropdown visibility
            dropdown.classList.toggle('show');
            
            // Highlight the current time if set
            if (timeInput.value) {
                const currentTimeItems = dropdown.querySelectorAll(`.gcal-time-item[data-value="${timeInput.value}"]`);
                dropdown.querySelectorAll('.gcal-time-item').forEach(item => item.classList.remove('active'));
                currentTimeItems.forEach(item => item.classList.add('active'));
                
                // Scroll to the active item
                if (currentTimeItems.length > 0) {
                    currentTimeItems[0].scrollIntoView({ block: 'center' });
                }
            }
        });
        
        // Handle selection from the dropdown
        dropdown.addEventListener('click', function(e) {
            e.stopPropagation();
            
            // Quick time button clicked
            if (e.target.classList.contains('gcal-quick-time')) {
                const selectedTime = e.target.dataset.time;
                if (selectedTime) {
                    timeInput.value = selectedTime;
                    field.textContent = formatTime(selectedTime);
                    
                    // Update active class for quick times
                    dropdown.querySelectorAll('.gcal-quick-time').forEach(qt => {
                        qt.classList.toggle('active', qt.dataset.time === selectedTime);
                    });
                    
                    // Update the hidden input
                    const container = field.closest('.time-pickers');
                    if (container) {
                        updateHiddenInput(container);
                    }
                    
                    // Auto-suggest end time if this is a start time selection
                    if (isStartTime) {
                        const timePickersContainer = field.closest('.time-pickers');
                        if (timePickersContainer) {
                            autoSuggestEndTime(timePickersContainer, selectedTime);
                        }
                    }
                    
                    dropdown.classList.remove('show');
                }
            }
            // Regular time item clicked
            else if (e.target.classList.contains('gcal-time-item')) {
                const selectedTime = e.target.dataset.value;
                if (selectedTime) {
                    timeInput.value = selectedTime;
                    field.textContent = e.target.textContent;
                    
                    // Update active class
                    dropdown.querySelectorAll('.gcal-time-item').forEach(item => {
                        item.classList.remove('active');
                    });
                    e.target.classList.add('active');
                    
                    // Update the hidden input
                    const container = field.closest('.time-pickers');
                    if (container) {
                        updateHiddenInput(container);
                    }
                    
                    // Auto-suggest end time if this is a start time selection
                    if (isStartTime) {
                        const timePickersContainer = field.closest('.time-pickers');
                        if (timePickersContainer) {
                            autoSuggestEndTime(timePickersContainer, selectedTime);
                        }
                    }
                    
                    dropdown.classList.remove('show');
                }
            }
        });
    });
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.gcal-time-input')) {
            document.querySelectorAll('.gcal-time-dropdown.show').forEach(dropdown => {
                dropdown.classList.remove('show');
            });
        }
    });
}

// Initialize all time inputs with Flatpickr - MOVED TO GLOBAL SCOPE
function initializeAllTimeInputs() {
    document.querySelectorAll('.time-input-container').forEach(container => {
        initFlatpickrForContainer(container);
    });
}

document.addEventListener('DOMContentLoaded', function() {
    const weekInput = document.getElementById('week');
    window.hasUnsavedChanges = false;

    // Store original values when page loads
    window.originalTimeValues = new Map();

    // Original function to apply quick fill changes
function applyQuickFill(container, value) {
    const hiddenInput = container.querySelector('input[type="hidden"]');
    let timePickers = container.querySelector('.time-pickers');

    if (!hiddenInput) return;

    // Clear all existing classes and attributes
    container.classList.remove('day-off-container', 'on-call-container', 'reception-container', 'time-validation-error');
    container.removeAttribute('data-day-off-type');
    
    // Remove existing icons and special displays
    const existingSpecialDisplay = container.querySelector('.special-display');
    if (existingSpecialDisplay) existingSpecialDisplay.remove();

    // Check if we're converting FROM a day-off type (time-pickers might be hidden or missing inputs)
    let needToRecreateInputs = false;
    
    if (timePickers) {
        // Show time pickers if they were hidden
        timePickers.style.display = '';
        timePickers.classList.remove('on-call-type', 'reception-type', 'special-type');
        timePickers.removeAttribute('data-day-off-type');
        
        // Remove existing icons
        const existingOnCallIcon = timePickers.querySelector('.on-call-icon');
        const existingReceptionIcon = timePickers.querySelector('.reception-icon');
        if (existingOnCallIcon) existingOnCallIcon.remove();
        if (existingReceptionIcon) existingReceptionIcon.remove();
        
        // Check if time inputs exist inside the time-pickers
        const existingStartInput = timePickers.querySelector('.time-start');
        const existingEndInput = timePickers.querySelector('.time-end');
        
        if (!existingStartInput || !existingEndInput) {
            needToRecreateInputs = true;
        }
    } else {
        needToRecreateInputs = true;
    }

    // If we need to recreate inputs (converting from day-off type)
    if (needToRecreateInputs) {
        console.log('Recreating time inputs for day-off conversion');
        
        // Remove the existing time-pickers if it exists but is incomplete
        if (timePickers) {
            timePickers.remove();
        }
        
        // Create new time pickers container
        timePickers = document.createElement('div');
        timePickers.className = 'time-pickers d-flex align-items-center';
        timePickers.style.cssText = 'flex: 1;';
        
        // Create the basic time row structure
        const basicTimeRow = document.createElement('div');
        basicTimeRow.className = 'basic-time-row';
        
        // Create start time input
        const startInput = document.createElement('input');
        startInput.type = 'time';
        startInput.className = 'form-control form-control-sm time-start';
        startInput.disabled = false;
        
        // Create separator
        const separator = document.createElement('span');
        separator.className = 'time-separator mx-1';
        separator.textContent = '-';
        
        // Create end time input
        const endInput = document.createElement('input');
        endInput.type = 'time';
        endInput.className = 'form-control form-control-sm time-end';
        endInput.disabled = false;
        
        // Assemble the structure
        basicTimeRow.appendChild(startInput);
        basicTimeRow.appendChild(separator);
        basicTimeRow.appendChild(endInput);
        timePickers.appendChild(basicTimeRow);
        
        // Insert the time pickers before the dropdown
        const dropdown = container.querySelector('.dropdown');
        if (dropdown) {
            container.insertBefore(timePickers, dropdown);
        } else {
            // Fallback: append to container
            container.appendChild(timePickers);
        }
    }

    // Now get the inputs (should exist after recreation if needed) - CHANGED TO LET
    let startInput = container.querySelector('.time-start');
    let endInput = container.querySelector('.time-end');
    
    if (!startInput || !endInput) {
        console.error('Could not find time inputs after recreation');
        return;
    }

    // Clear sick leave attributes
    hiddenInput.classList.remove('sick-leave-entry');
    hiddenInput.removeAttribute('data-sick-leave');

    // IMPORTANT: Always enable inputs first before doing anything else
    startInput.disabled = false;
    endInput.disabled = false;

    // Clear any existing values and Flatpickr instances
    startInput.value = '';
    endInput.value = '';
    if (startInput._flatpickr) {
        startInput._flatpickr.destroy();
    }
    if (endInput._flatpickr) {
        endInput._flatpickr.destroy();
    }
    startInput.classList.remove('flatpickr-initialized');
    endInput.classList.remove('flatpickr-initialized');

    switch(value) {
        case 'V':
        case 'X':
        case 'H':
            hiddenInput.value = value;
            
            // ONLY disable for day-off types
            startInput.value = '';
            endInput.value = '';
            startInput.disabled = true;
            endInput.disabled = true;
            
            // Hide the time pickers and add special styling
            timePickers.classList.add('special-type');
            timePickers.style.display = 'none';
            timePickers.dataset.dayOffType = value;
            
            container.classList.add('day-off-container');
            container.dataset.dayOffType = value;
            
            // Create the special display element (like on page load)
            const specialDisplay = document.createElement('div');
            specialDisplay.className = 'special-display d-flex align-items-center justify-content-center';
            specialDisplay.style.cssText = 'flex: 1; padding: 5px; background-color: #f8f9fa; border-radius: 3px;';
            specialDisplay.innerHTML = `<strong>${value}</strong>`;
            
            // Insert the special display before the dropdown
            const dropdown = container.querySelector('.dropdown');
            if (dropdown) {
                container.insertBefore(specialDisplay, dropdown);
            }
            break;
            
        case 'on_call':
            // ENSURE inputs are enabled (redundant but explicit)
            startInput.disabled = false;
            endInput.disabled = false;
            
            // Add on-call styling and icon
            timePickers.classList.add('on-call-type');
            container.classList.add('on-call-container');
            
            // Add phone icon at the beginning of time-pickers
            const phoneIcon = document.createElement('i');
            phoneIcon.className = 'fas fa-phone on-call-icon';
            phoneIcon.style.cssText = 'color: #28a745; margin-right: 5px; font-size: 14px;';
            timePickers.insertBefore(phoneIcon, timePickers.firstChild);
            
            // Initialize Flatpickr and set default times
            setTimeout(() => {
                initFlatpickrForContainer(container);
                
                setTimeout(() => {
                    // SAFE SET DEFAULT TIMES with error handling
                    if (startInput._flatpickr && typeof startInput._flatpickr.setDate === 'function') {
                        try {
                            startInput._flatpickr.setDate('09:00');
                        } catch (error) {
                            console.warn('Failed to set on-call start time via Flatpickr:', error);
                            startInput.value = '09:00';
                        }
                    } else {
                        startInput.value = '09:00';
                    }
                    
                    if (endInput._flatpickr && typeof endInput._flatpickr.setDate === 'function') {
                        try {
                            endInput._flatpickr.setDate('17:00');
                        } catch (error) {
                            console.warn('Failed to set on-call end time via Flatpickr:', error);
                            endInput.value = '17:00';
                        }
                    } else {
                        endInput.value = '17:00';
                    }
                    
                    const onCallData = {
                        start_time: startInput.value || '09:00',
                        end_time: endInput.value || '17:00',
                        type: 'on_call'
                    };
                    hiddenInput.value = JSON.stringify(onCallData);
                }, 100);
            }, 50);
            break;
            
        case 'reception':
            // ENSURE inputs are enabled (redundant but explicit)
            startInput.disabled = false;
            endInput.disabled = false;
            
            // Add reception styling and icon
            timePickers.classList.add('reception-type');
            container.classList.add('reception-container');
            
            // Add desktop icon at the beginning of time-pickers
            const desktopIcon = document.createElement('i');
            desktopIcon.className = 'fas fa-desktop reception-icon';
            desktopIcon.style.cssText = 'color: #007bff; margin-right: 5px; font-size: 14px;';
            timePickers.insertBefore(desktopIcon, timePickers.firstChild);
            
            // Reinitialize Flatpickr if needed
            if (!startInput.classList.contains('flatpickr-initialized') || startInput.disabled) {
                // Destroy existing instances first
                if (startFlatpickr) startFlatpickr.destroy();
                if (endFlatpickr) endFlatpickr.destroy();
                startInput.classList.remove('flatpickr-initialized');
                endInput.classList.remove('flatpickr-initialized');
                
                // Reinitialize
                initFlatpickrForContainer(container);
            }
            
            if (startInput.value && endInput.value) {
                const receptionData = {
                    start_time: startInput.value,
                    end_time: endInput.value,
                    type: 'reception'
                };
                hiddenInput.value = JSON.stringify(receptionData);
            } else {
                // Set default times if empty
                if (startInput._flatpickr && !startInput.value) startInput._flatpickr.setDate('09:00');
                if (endInput._flatpickr && !endInput.value) endInput._flatpickr.setDate('17:00');
                
                const receptionData = {
                    start_time: startInput.value || '09:00',
                    end_time: endInput.value || '17:00',
                    type: 'reception'
                };
                hiddenInput.value = JSON.stringify(receptionData);
            }
            break;
            
        case 'SL':
            // ENSURE inputs are enabled (redundant but explicit)
            startInput.disabled = false;
            endInput.disabled = false;
            hiddenInput.classList.add('sick-leave-entry');
            hiddenInput.dataset.sickLeave = 'true';
            
            // Reinitialize Flatpickr if needed
            if (!startInput.classList.contains('flatpickr-initialized') || startInput.disabled) {
                // Destroy existing instances first
                if (startFlatpickr) startFlatpickr.destroy();
                if (endFlatpickr) endFlatpickr.destroy();
                startInput.classList.remove('flatpickr-initialized');
                endInput.classList.remove('flatpickr-initialized');
                
                // Reinitialize
                initFlatpickrForContainer(container);
            }
            
            // Set default times if empty
            if (startInput._flatpickr && !startInput.value) startInput._flatpickr.setDate('10:00');
            if (endInput._flatpickr && !endInput.value) endInput._flatpickr.setDate('18:00');
            
            hiddenInput.value = `${startInput.value || '10:00'}-${endInput.value || '18:00'}`;
            break;
            
        case 'regular':
            // Add temporary class to indicate we're converting to regular
            container.classList.add('converting-to-regular');
            
            // FORCE enable inputs first - CRITICAL FIX
            startInput.disabled = false;
            endInput.disabled = false;
            
            // Clear any existing values first
            startInput.value = '';
            endInput.value = '';
            
            // Destroy any existing Flatpickr instances to ensure clean state
            if (startInput._flatpickr) {
                startInput._flatpickr.destroy();
            }
            if (endInput._flatpickr) {
                endInput._flatpickr.destroy();
            }
            startInput.classList.remove('flatpickr-initialized');
            endInput.classList.remove('flatpickr-initialized');
            
            // ENSURE inputs are enabled after destroying Flatpickr
            startInput.disabled = false;
            endInput.disabled = false;
            
            // Remove any disabled attributes that might be lingering
            startInput.removeAttribute('disabled');
            endInput.removeAttribute('disabled');
            
            // Initialize Flatpickr and set default times with proper delays
            setTimeout(() => {
                // TRIPLE CHECK: Ensure inputs are still enabled before initialization
                startInput.disabled = false;
                endInput.disabled = false;
                startInput.removeAttribute('disabled');
                endInput.removeAttribute('disabled');

                 container.classList.remove('converting-to-regular');
       
                
                console.log('Regular hours: About to initialize Flatpickr', {
                    startDisabled: startInput.disabled,
                    endDisabled: endInput.disabled,
                    startHasDisabledAttr: startInput.hasAttribute('disabled'),
                    endHasDisabledAttr: endInput.hasAttribute('disabled')
                });
                
                initFlatpickrForContainer(container);
                
                setTimeout(() => {
                    // FINAL CHECK: Ensure inputs are still enabled after initialization
                    startInput.disabled = false;
                    endInput.disabled = false;
                    startInput.removeAttribute('disabled');
                    endInput.removeAttribute('disabled');
                    
                    // SAFE SET DEFAULT TIMES: Check if Flatpickr is properly initialized before calling setDate
                    if (startInput._flatpickr && typeof startInput._flatpickr.setDate === 'function') {
                        try {
                            startInput._flatpickr.setDate('09:00');
                            console.log('Start time set via Flatpickr');
                        } catch (error) {
                            console.warn('Failed to set start time via Flatpickr:', error);
                            startInput.value = '09:00';
                        }
                    } else {
                        console.log('Setting start time directly');
                        startInput.value = '09:00';
                    }
                    
                    if (endInput._flatpickr && typeof endInput._flatpickr.setDate === 'function') {
                        try {
                            endInput._flatpickr.setDate('17:00');
                            console.log('End time set via Flatpickr');
                        } catch (error) {
                            console.warn('Failed to set end time via Flatpickr:', error);
                            endInput.value = '17:00';
                        }
                    } else {
                        console.log('Setting end time directly');
                        endInput.value = '17:00';
                    }
                    
                    hiddenInput.value = '09:00-17:00';
                    
                    // ABSOLUTE FINAL CHECK: Force inputs to be enabled
                    startInput.disabled = false;
                    endInput.disabled = false;
                    startInput.removeAttribute('disabled');
                    endInput.removeAttribute('disabled');
                    
                    // Remove the temporary class after initialization
                    container.classList.remove('converting-to-regular');
                    
                    console.log('Regular hours: Final state', {
                        startEnabled: !startInput.disabled,
                        endEnabled: !endInput.disabled,
                        startValue: startInput.value,
                        endValue: endInput.value,
                        hiddenValue: hiddenInput.value,
                        flatpickrInitialized: startInput.classList.contains('flatpickr-initialized'),
                        startFlatpickrExists: !!startInput._flatpickr,
                        endFlatpickrExists: !!endInput._flatpickr
                    });
                }, 300); // Increased timeout for proper initialization
            }, 150); // Increased timeout for DOM updates
            break;
            
        default:
            hiddenInput.value = value;
            break;
    }
    
    // Update the visible time fields for Google Calendar picker
    const startTimeField = container.querySelector('.start-time-field');
    const endTimeField = container.querySelector('.end-time-field');
    
    if (startInput && startTimeField && startInput.value) {
        startTimeField.textContent = formatTime(startInput.value);
    } else if (startTimeField) {
        startTimeField.textContent = 'Start';
    }
    if (endInput && endTimeField && endInput.value) {
        endTimeField.textContent = formatTime(endInput.value);
    } else if (endTimeField) {
        endTimeField.textContent = 'End';
    }
    
    markAsUnsaved();
}

    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('quick-fill')) {
            e.preventDefault();
            const container = e.target.closest('.time-input-container');
            const value = e.target.dataset.value;
            applyQuickFill(container, value);
        }
        
        if (e.target.classList.contains('add-time-slot') || e.target.closest('.add-time-slot')) {
            e.preventDefault();
            const container = e.target.closest('.time-input-container');
            addTimeSlot(container);
        }
        
        if (e.target.classList.contains('remove-time-slot') || e.target.closest('.remove-time-slot')) {
            e.preventDefault();
            const container = e.target.closest('.time-input-container');
            
            // CRITICAL: Check if this is an approved record
            if (container.querySelector('.approved-container')) {
                Swal.fire({
                    title: 'Cannot Remove Approved Record',
                    text: 'This record has been approved and cannot be removed. Only administrators and supervisors can modify approved records.',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
                return; // Exit early - don't allow removal
            }
            
            removeTimeSlot(container);
        }

        if (e.target.closest('.btn-group .btn-outline-primary')) {
            e.preventDefault();
            const url = e.target.closest('a').href;
            handleNavigation(url);
        }

        // if (e.target.closest('#copyPreviousWeekBtn') || e.target.closest('#clearCurrentWeekBtn')) {
        //     e.preventDefault();
        //     const url = e.target.closest('a').href;
        //     handleNavigation(url);
        // }
    });

    // Function to handle navigation with unsaved changes
    async function handleNavigation(url) {
        if (hasUnsavedChanges) {
            const result = await Swal.fire({
                title: 'Unsaved Changes',
                text: 'You have unsaved changes. Are you sure you want to leave?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, Leave',
                cancelButtonText: 'Stay',
                reverseButtons: true
            });

            if (result.isConfirmed) {
                hasUnsavedChanges = false;
                window.location.href = url;
            }
        } else {
            window.location.href = url;
        }
    }

    // Week input change handler
    if (weekInput) {
        weekInput.addEventListener('change', function() {
            const url = `{{ route('supervisor.enter-working-hours') }}?week=${this.value}`;
            handleNavigation(url);
        });
    }

    // Function to confirm time updates
    async function confirmTimeUpdate(startTime, endTime, originalStartTime, originalEndTime) {
        const result = await Swal.fire({
            title: 'Time Update Confirmation',
            html: `
                <div style="text-align: left;">
                    <p><strong>Original Time:</strong> ${originalStartTime} - ${originalEndTime}</p>
                    <p><strong>New Time:</strong> ${startTime} - ${endTime}</p>
                    <br>
                    <p>Do you want to keep track of the original times?</p>
                </div>
            `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, Keep Original Times',
            cancelButtonText: 'No, Just Store New Times',
            reverseButtons: true,
            allowOutsideClick: false
        });

        return result.isConfirmed;
    }

    // Form submission handlers for each department
    document.querySelectorAll('.department-form').forEach(form => {
    form.addEventListener('submit', async function(e) {
        e.preventDefault(); // Prevent default submission
        
        // Note: Approved records are disabled in the UI, so users cannot modify them
        // Backend validation will catch any programmatic attempts to modify approved records
        // This allows users to add new entries to empty slots even when approved records exist
        
        // Get the department from the hidden input
        const departmentInput = form.querySelector('input[name="department"]');
        const department = departmentInput ? departmentInput.value : 'Unknown';
        
        let timeUpdates = [];
        
        // Check for existing working hours entries and time updates
        const timeInputs = form.querySelectorAll('input[type="hidden"][name*="hours"]');
        
        timeInputs.forEach(input => {
            if (input.value && input.value.trim() !== '') {
                const inputId = input.id || input.name;
                const originalValue = window.originalTimeValues.get(inputId);
                const currentValue = input.value;
                
                // COMPLETELY SKIP special type entries from ANY time update processing
                if (currentValue.includes('on_call') || 
                    currentValue.includes('reception') || 
                    (originalValue && (originalValue.includes('on_call') || originalValue.includes('reception')))) {
                    return; // Skip this entry entirely
                }
                
                // Check if this is a time update (current value different from original and both contain time ranges)
                if (originalValue && 
                    currentValue !== originalValue && 
                    originalValue.includes('-') && 
                    currentValue.includes('-')) {
                    
                    const [originalStart, originalEnd] = originalValue.split('-');
                    const [currentStart, currentEnd] = currentValue.split('-');
                    
                    // Get staff info from the input name/container
                    const staffContainer = input.closest('[data-staff-id]');
                    const staffName = staffContainer ? staffContainer.querySelector('td:first-child')?.textContent?.trim() : 'Unknown Staff';
                    
                    // Get date info from the input name
                    const nameMatch = input.name.match(/hours\[\d+\]\[([^\]]+)\]/);
                    const date = nameMatch ? nameMatch[1] : 'Unknown Date';
                    
                    timeUpdates.push({
                        element: input,
                        staffName: staffName,
                        date: date,
                        newStart: currentStart,
                        newEnd: currentEnd,
                        originalStart: originalStart,
                        originalEnd: originalEnd,
                        inputName: input.name
                    });
                }
            }
        });
        
        // If there are time updates, show ONE confirmation dialog with ALL changes
        if (timeUpdates.length > 0) {
            // Build the changes summary HTML
            let changesHtml = '<div style="text-align: left; max-height: 400px; overflow-y: auto;">';
            changesHtml += `<h4 style="margin-bottom: 15px; color: #007bff;">Time Changes Summary (${timeUpdates.length} changes)</h4>`;
            
            timeUpdates.forEach((update, index) => {
                changesHtml += `<div style="margin-bottom: 15px; padding: 10px; background: #f8f9fa; border-radius: 5px; border-left: 3px solid #007bff;">`;
                changesHtml += `<strong style="color: #495057;">${update.staffName}</strong> - <span style="color: #6c757d;">${update.date}</span>`;
                changesHtml += `<div style="margin-top: 8px;">`;
                changesHtml += `<div><strong>Original Time:</strong> ${update.originalStart} - ${update.originalEnd}</div>`;
                changesHtml += `<div><strong>New Time:</strong> <span style="color: #28a745;">${update.newStart} - ${update.newEnd}</span></div>`;
                changesHtml += `</div>`;
                changesHtml += `</div>`;
            });
            
            changesHtml += `</div>`;
            
            // Enhanced explanation section
            changesHtml += `<div style="margin-top: 20px; padding: 20px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 8px;">`;
            changesHtml += `<h5 style="color: #856404; margin-bottom: 15px; display: flex; align-items: center;">`;
            changesHtml += `<i class="fas fa-question-circle" style="margin-right: 8px;"></i>`;
            changesHtml += `What does "Keep Original Times" mean?`;
            changesHtml += `</h5>`;
            
            changesHtml += `<div style="color: #856404; font-size: 14px; line-height: 1.6;">`;
            changesHtml += `<p style="margin-bottom: 12px;"><strong>Choose how to display the time changes:</strong></p>`;
            
            // Option 1 - Keep Original Times
            changesHtml += `<div style="margin-bottom: 15px; padding: 12px; background: #e8f5e8; border-radius: 6px; border-left: 4px solid #28a745;">`;
            changesHtml += `<div style="font-weight: 600; color: #28a745; margin-bottom: 8px;">`;
            changesHtml += `<i class="fas fa-check-circle" style="margin-right: 6px;"></i>Option 1: Keep Original Times`;
            changesHtml += `</div>`;
            changesHtml += `<div style="color: #495057;">Shows both old and new times for comparison:</div>`;
            changesHtml += `<div style="margin-top: 8px; padding: 8px; background: white; border-radius: 4px; font-family: monospace;">`;
            changesHtml += `<span style="text-decoration: line-through; color: #dc3545;">10:00 - 17:00</span> `;
            changesHtml += `<span style="color: #28a745; font-weight: 600;">→ 11:00 - 17:00</span>`;
            changesHtml += `</div>`;
            changesHtml += `<small style="color: #6c757d; font-style: italic;">Perfect for tracking changes and approvals</small>`;
            changesHtml += `</div>`;
            
            // Option 2 - New Times Only
            changesHtml += `<div style="margin-bottom: 15px; padding: 12px; background: #fff3e0; border-radius: 6px; border-left: 4px solid #ffc107;">`;
            changesHtml += `<div style="font-weight: 600; color: #f57c00; margin-bottom: 8px;">`;
            changesHtml += `<i class="fas fa-edit" style="margin-right: 6px;"></i>Option 2: New Times Only`;
            changesHtml += `</div>`;
            changesHtml += `<div style="color: #495057;">Shows only the updated times:</div>`;
            changesHtml += `<div style="margin-top: 8px; padding: 8px; background: white; border-radius: 4px; font-family: monospace;">`;
            changesHtml += `<span style="color: #495057;">11:00 - 17:00</span>`;
            changesHtml += `</div>`;
            changesHtml += `<small style="color: #6c757d; font-style: italic;">Cleaner display, no change history</small>`;
            changesHtml += `</div>`;
            
  
            
            changesHtml += `</div>`;
            changesHtml += `</div>`;
            
            // Show the single confirmation dialog for ALL changes
            const result = await Swal.fire({
                title: 'Time Update Confirmation',
                html: changesHtml,
                icon: 'question',
                showCancelButton: true,
                showDenyButton: true,
                confirmButtonText: '<i class="fas fa-history"></i> Keep Original Times',
                denyButtonText: '<i class="fas fa-edit"></i> New Times Only',
                cancelButtonText: '<i class="fas fa-times"></i> Cancel',
                confirmButtonColor: '#28a745',
                denyButtonColor: '#ffc107',
                cancelButtonColor: '#6c757d',
                reverseButtons: true,
                allowOutsideClick: false,
                allowEscapeKey: true,
                width: '700px',
                customClass: {
                    popup: 'time-changes-explanation-popup'
                }
            });
            
            if (result.isConfirmed) {
                // Keep original times - process ALL changes to include original times
                timeUpdates.forEach(update => {
                    const timeEntryWithOriginal = {
                        'start_time': update.newStart,
                        'end_time': update.newEnd,
                        'type': 'normal',
                        'original_start_time': update.originalStart,
                        'original_end_time': update.originalEnd
                    };
                    update.element.value = JSON.stringify(timeEntryWithOriginal);
                });
                
            } else if (result.isDenied) {
                // Just store new times - process ALL changes to regular format
                timeUpdates.forEach(update => {
                    update.element.value = `${update.newStart}-${update.newEnd}`;
                });
                
            } else {
                // User cancelled - do nothing
                return;
            }
        }
        
        // After handling time updates, proceed with form submission
        const submitResult = await Swal.fire({
            title: 'Submit Working Hours?',
            text: `Are you sure you want to submit the working hours for ${department} department?${timeUpdates.length > 0 ? ` (${timeUpdates.length} time updates processed)` : ''}`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, Submit',
            cancelButtonText: 'Cancel'
        });
        
        if (submitResult.isConfirmed) {
            window.hasUnsavedChanges = false; // Reset the flag
            form.submit(); // Actually submit the form
        }
    });
});

    // Track changes
    document.addEventListener('input', function(e) {
        if (e.target.matches('input[type="time"], input[type="text"], textarea.notes-input, input[name^="reception"], select[name^="midnight_phone"]')) {
            markAsUnsaved();
            
            // Update hidden input when notes change
            if (e.target.matches('textarea.notes-input')) {
                const container = e.target.closest('.time-slot').querySelector('.time-input-container');
                if (container) {
                    updateHiddenInput(container);
                }
            }
        }
    });

    document.addEventListener('change', function(e) {
        if (e.target.matches('select[name^="midnight_phone"], textarea.notes-input')) {
            markAsUnsaved();
            
            // Update hidden input when notes change
            if (e.target.matches('textarea.notes-input')) {
                const container = e.target.closest('.time-slot').querySelector('.time-input-container');
                if (container) {
                    updateHiddenInput(container);
                }
            }
        }
    });

    // Initialize all time inputs with Flatpickr
    initializeAllTimeInputs();
    hasUnsavedChanges = false;

    // MutationObserver to watch for dynamically added time inputs
    const observer = new MutationObserver(function(mutations) {
        let newTimeInputsAdded = false;
        
        mutations.forEach(function(mutation) {
            if (mutation.type === 'childList') {
                mutation.addedNodes.forEach(function(node) {
                    if (node.nodeType === 1) { // Element node
                        // Check if this node or any of its children are time input containers
                        if (node.classList && node.classList.contains('time-input-container')) {
                            newTimeInputsAdded = true;
                        } else if (node.querySelector && node.querySelector('.time-input-container')) {
                            newTimeInputsAdded = true;
                        }
                    }
                });
            }
        });
        
        if (newTimeInputsAdded) {
            // Small delay to ensure DOM is fully updated
            setTimeout(() => {
                // Initialize only the new containers that don't have Flatpickr yet
                document.querySelectorAll('.time-input-container').forEach(container => {
                    const startInput = container.querySelector('.time-start');
                    if (startInput && !startInput.classList.contains('flatpickr-initialized')) {
                        initFlatpickrForContainer(container);
                    }
                });
            }, 100);
        }
    });
    
    // Start observing the document
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });

    // Auto-dismiss form alerts only (not sick leave alerts)
    const formAlerts = document.querySelectorAll('.alert-dismissible');
    formAlerts.forEach(alert => {
        // Only auto-dismiss success/error messages, not sick leave alerts
        if (!alert.closest('td')) { // Sick leave alerts are inside table cells
            setTimeout(() => {
                if (alert.parentNode) {
                    alert.style.transition = 'opacity 0.5s';
                    alert.style.opacity = '0';
                    setTimeout(() => {
                        if (alert.parentNode) {
                            alert.remove();
                        }
                    }, 500);
                }
            }, 5000);
        }
    });

    // Add restricted user logic from the original code
    const currentUserEmail = '{{ Auth::user()->email ?? "" }}';
    const restrictedUsers = ['beatriz@nordictravels.eu', 'semi@nordictravels.eu'];
    const isRestrictedUser = restrictedUsers.includes(currentUserEmail);

    // Function to check if this is user's own roster
    function isOwnRoster(container) {
        if (!isRestrictedUser) return false;
        
        const staffRow = container.closest('tr[data-staff-id]');
        if (!staffRow) return false;
        
        const staffEmail = staffRow.getAttribute('data-staff-email');
        return staffEmail === currentUserEmail;
    }

    // Add CSS style for Flatpickr inputs
    const style = document.createElement('style');
    style.textContent = `

        .flatpickr-calendar {
            font-size: 14px;
        }
        .flatpickr-time input:hover,
        .flatpickr-time .flatpickr-am-pm:hover,
        .flatpickr-time input:focus,
        .flatpickr-time .flatpickr-am-pm:focus {
            background: #f0f0f0;
        }
    `;
    document.head.appendChild(style);

    // Clear Current Week confirmation
document.addEventListener('click', function(e) {
    // Handle Clear This Week Data button FIRST (most specific)
    if (e.target.closest('#clearCurrentWeekBtn')) {
        e.preventDefault();
        e.stopPropagation(); // Prevent other handlers from running
        const url = e.target.closest('a').href;
        
        // Show confirmation dialog
        Swal.fire({
            title: 'Clear This Week Data?',
            html: `
                <div style="text-align: left;">
                    <p><strong>This action will permanently delete:</strong></p>
                    <ul style="margin: 10px 0; padding-left: 20px;">
                        <li>All working hours for this week</li>
                        
                    </ul>
                    <p style="color: #dc3545; font-weight: 600; margin-top: 15px;">
                        ⚠️ This action cannot be undone!
                    </p>
                </div>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Clear All Data',
            cancelButtonText: 'Cancel',
            reverseButtons: true,
            allowOutsideClick: false,
            allowEscapeKey: true,
            focusCancel: true
        }).then((result) => {
            if (result.isConfirmed) {
                // Show loading state
                Swal.fire({
                    title: 'Clearing Data...',
                    text: 'Please wait while we clear the week data.',
                    icon: 'info',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                // Reset unsaved changes flag since we're clearing everything
                window.hasUnsavedChanges = false;
                
                // Navigate to the clear URL
                window.location.href = url;
            }
        });
        return; // Exit early to prevent other handlers
    }

    // Handle Copy Previous Week button (EXCLUDE clear button)
    if (e.target.closest('#copyPreviousWeekBtn')) {
        e.preventDefault();
        const url = e.target.closest('a').href;
        handleNavigation(url);
        return; // Exit early
    }

    // Handle week navigation buttons (EXCLUDE both copy and clear buttons)
    if (e.target.closest('.btn-group .btn-outline-primary') && 
        !e.target.closest('#copyPreviousWeekBtn') && 
        !e.target.closest('#clearCurrentWeekBtn')) {
        e.preventDefault();
        const url = e.target.closest('a').href;
        handleNavigation(url);
        return; // Exit early
    }

    // Handle quick fill options
    if (e.target.classList.contains('quick-fill')) {
        e.preventDefault();
        const container = e.target.closest('.time-input-container');
        
        // CRITICAL: Check if this is an approved record
        if (container.querySelector('.approved-container')) {
            Swal.fire({
                title: 'Cannot Modify Approved Record',
                text: 'This record has been approved and cannot be modified. Only administrators and supervisors can modify approved records.',
                icon: 'error',
                confirmButtonText: 'OK'
            });
            return; // Exit early - don't allow modification
        }
        
        const value = e.target.dataset.value;
        applyQuickFill(container, value);
        return; // Exit early
    }
    
    
    // Handle remove time slot
    if (e.target.classList.contains('remove-time-slot') || e.target.closest('.remove-time-slot')) {
        e.preventDefault();
        const container = e.target.closest('.time-input-container');
        
        // CRITICAL: Check if this is an approved record
        if (container.querySelector('.approved-container')) {
            Swal.fire({
                title: 'Cannot Remove Approved Record',
                text: 'This record has been approved and cannot be removed. Only administrators and supervisors can modify approved records.',
                icon: 'error',
                confirmButtonText: 'OK'
            });
            return; // Exit early - don't allow removal
        }
        
        removeTimeSlot(container);
        return; // Exit early
    }
});
});
</script>
@endsection

<!-- Keep all your existing CSS styles -->
<style>
    /* Basic Time Picker Styles */

    .time-changes-explanation-popup .swal2-popup {
    font-size: 14px;
}

.time-changes-explanation-popup h4 {
    border-bottom: 2px solid #007bff;
    padding-bottom: 8px;
}

.time-changes-explanation-popup h5 {
    margin-top: 0;
    margin-bottom: 15px;
}

/* Enhanced button styling for the explanation popup */
.time-changes-explanation-popup .swal2-confirm {
    background: linear-gradient(45deg, #28a745, #20c997) !important;
    border: none !important;
    font-weight: 600 !important;
    padding: 12px 20px !important;
    font-size: 14px !important;
}

.time-changes-explanation-popup .swal2-deny {
    background: linear-gradient(45deg, #ffc107, #fd7e14) !important;
    border: none !important;
    font-weight: 600 !important;
    color: #212529 !important;
    padding: 12px 20px !important;
    font-size: 14px !important;
}

.time-changes-explanation-popup .swal2-cancel {
    background: linear-gradient(45deg, #6c757d, #495057) !important;
    border: none !important;
    font-weight: 600 !important;
    padding: 12px 20px !important;
    font-size: 14px !important;
}

/* Hover effects */
.time-changes-explanation-popup .swal2-confirm:hover {
    transform: translateY(-1px) !important;
    box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3) !important;
}

.time-changes-explanation-popup .swal2-deny:hover {
    transform: translateY(-1px) !important;
    box-shadow: 0 4px 12px rgba(255, 193, 7, 0.3) !important;
}

.time-changes-explanation-popup .swal2-cancel:hover {
    transform: translateY(-1px) !important;
    box-shadow: 0 4px 12px rgba(108, 117, 125, 0.3) !important;
}

/* Visual example styling */
.time-changes-explanation-popup .swal2-html-container div[style*="font-family: monospace"] {
    font-size: 13px !important;
    border: 1px solid #dee2e6 !important;
}
    .basic-time-row {
        display: flex;
        align-items: center;
        width: 100%;
    }
    
    .time-start, .time-end {
        min-width: 85px;
        border-radius: 4px;
        border: 1px solid #ced4da;
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
        line-height: 1.5;
        transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    }
    
    .time-start:focus, .time-end:focus {
        border-color: #80bdff;
        outline: 0;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    }
    
    .time-separator {
        margin: 0 5px;
        font-weight: 500;
        color: #495057;
    }
    
    /* On-call styling */
    .on-call-container {
        background-color: #f8fff9;
        border: 1px solid #28a745;
        border-radius: 4px;
        padding: 2px;
    }
    
    .time-pickers.on-call-type {
        background-color: rgba(40, 167, 69, 0.1);
        border-radius: 4px;
        padding: 2px 5px;
    }
    
    .on-call-icon {
        font-size: 14px !important;
        color: #28a745 !important;
        margin-right: 5px !important;
        flex-shrink: 0;
    }
    
    .time-pickers.on-call-type input[type="time"] {
        border-color: #28a745;
    }

    .on-call-container:hover {
        background-color: #e8f5e8;
        transition: background-color 0.2s ease;
    }
    
    /* Reception styling */
    .reception-container {
        background-color: #f0f8ff;
        border: 1px solid #007bff;
        border-radius: 4px;
        padding: 2px;
    }
    
    .time-pickers.reception-type {
        background-color: rgba(0, 123, 255, 0.1);
        border-radius: 4px;
        padding: 2px 5px;
    }
    
    .reception-icon {
        font-size: 14px !important;
        color: #007bff !important;
        margin-right: 5px !important;
        flex-shrink: 0;
    }
    
    .time-pickers.reception-type input[type="time"] {
        border-color: #007bff;
    }

    .reception-container:hover {
        background-color: #e6f3ff;
        transition: background-color 0.2s ease;
    }
    
    /* Day off styling - CORRECTED VERSION */
    .day-off-container {
        position: relative;
        background: rgba(248, 249, 250, 0.3);
        border-radius: 4px;
        border: 1px solid rgba(206, 212, 218, 0.5);
    }

    .day-off-container .time-pickers {
        opacity: 0.3;
        position: relative;
        filter: blur(0.5px);
    }

    .day-off-container .time-pickers::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.1);
        border-radius: 4px;
        z-index: 1;
    }
.special-display {
    background-color: #ffffff !important;
    border: 2px solid #000000 !important;
    border-radius: 4px !important;
    padding: 4px 8px !important;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2) !important;
    min-height: 24px !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
}

.special-display strong {
    color: #000000 !important;
    font-weight: 700 !important;
    font-size: 0.8rem !important;
    text-shadow: none !important;
    letter-spacing: 0.5px !important;
    line-height: 1 !important;
}

/* Color-coded backgrounds for different day off types - SMALLER VERSION */
.day-off-container[data-day-off-type="V"] .special-display {
    background-color: #e8e3ff !important;
    border-color: #6f42c1 !important;
}

.day-off-container[data-day-off-type="X"] .special-display {
    background-color: #e8f5e8 !important;
    border-color: #28a745 !important;
}

.day-off-container[data-day-off-type="H"] .special-display {
    background-color: #fff3e0 !important;
    border-color: #fd7e14 !important;
}

/* Hover effects - SMALLER VERSION */
.day-off-container:hover .special-display {

    transform: scale(1.02) !important;
    transition: all 0.2s ease !important;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.3) !important;
}

/* Mobile responsive adjustments - SMALLER VERSION */
@media (max-width: 768px) {
    .special-display {
        padding: 3px 6px !important;
        border-width: 1px !important;
        min-height: 20px !important;
    }
    
    .special-display strong {
        font-size: 0.7rem !important;
        font-weight: 600 !important;
    }
}

@media (max-width: 480px) {
    .special-display {
        padding: 2px 5px !important;
        border-width: 1px !important;
        min-height: 18px !important;
        border-radius: 3px !important;
    }
    
    .special-display strong {
        font-size: 0.65rem !important;
        font-weight: 600 !important;
        letter-spacing: 0px !important;
    }
}

/* Update the day-off container to be more compact */
.day-off-container {
    position: relative;
    background: rgba(248, 249, 250, 0.2);
    border-radius: 3px;
    border: 1px solid rgba(206, 212, 218, 0.3);
    padding: 1px;
}

.day-off-container .time-pickers {
    opacity: 0.2;
    position: relative;
    filter: blur(0.3px);
}

.day-off-container .time-pickers::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.05);
    border-radius: 3px;
    z-index: 1;
}

/* Mobile responsive adjustments */
@media (max-width: 768px) {
    .special-display {
        padding: 6px 12px !important;
        border-width: 2px !important;
    }
    
    .special-display strong {
        font-size: 1rem !important;
    }
}

@media (max-width: 480px) {
    .special-display {
        padding: 4px 10px !important;
        border-width: 2px !important;
    }
    
    .special-display strong {
        font-size: 0.9rem !important;
    }
}

/* The rest of your existing CSS styles remain unchanged */
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
    overflow-y: auto;
    overflow-x: auto;
    margin-bottom: 20px;
    scrollbar-width: thin;
    -webkit-overflow-scrolling: touch;
    user-select: none;
    cursor: grab;
    position: relative; /* For sticky elements to work properly */
}

.table-responsive-container:active {
    cursor: grabbing;
}

.table-responsive {
    max-height: none;
    overflow: visible;
    min-width: max-content;
    padding-bottom: 15px;
}

.table-responsive-container::-webkit-scrollbar {
    height: 12px;
    width: 12px;
    display: block;
}

.table-responsive-container::-webkit-scrollbar-track {
    background: #f8f9fa;
    border-radius: 6px;
    display: block;
}

.table-responsive-container::-webkit-scrollbar-thumb {
    background: linear-gradient(45deg, #007bff, #0056b3);
    border-radius: 6px;
    display: block;
    border: 2px solid #f8f9fa;
}

.table-responsive-container::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(45deg, #0056b3, #004085);
}

.working-hours-table {
    min-width: 100%;
    font-size: 0.75rem;
    margin-bottom: 0; /* Remove default margin */
}

/* Make Office Worker column sticky - without changing widths */
.working-hours-table tbody td:first-child,
.working-hours-table thead th:first-child {
    position: sticky;
    left: 0;
    z-index: 2;
    background-color: #2c3749 !important; 
    border: 2px solid #424c5a  !important;
    box-shadow: 2px 0 5px rgba(0,0,0,0.1); /* Subtle shadow for depth */
}

.working-hours-table thead th:first-child {
    z-index: 3; /* Higher z-index for header */
    background-color: #424c5a !important; /* Keep the dark header color */
    color: white;
}

/* Ensure sticky column stays on top of other content */
.working-hours-table tbody td:first-child {
    font-weight: 500; /* Make staff names more prominent */
    /* border-right: 2px solid #dee2e6 !important; */
}

/* Fix the submit button visibility issue */
.tab-content {
    padding-bottom: 80px; /* Add bottom padding to ensure submit button is visible */
}

/* Make submit button container sticky at bottom */
.submit-button-container {
    position: sticky;
    bottom: 0;
    z-index: 10;
    /* background: linear-gradient(to bottom, rgba(248,249,250,0) 0%, rgba(248,249,250,0.9) 20%, rgba(248,249,250,1) 100%); Changed from white to light gray */
    padding: 20px 0 30px 0;
    margin-top: 20px;
    text-align: center;
    border-top: 1px solid rgba(0,0,0,0.1);
}

/* Enhance the submit button styling */
.submit-button-container .btn {
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    border: none;
    padding: 12px 30px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    transition: all 0.3s ease;
}

.submit-button-container .btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0,0,0,0.2);
}

/* Improve table container to work better with sticky elements */
.table-responsive-container {
    max-height: calc(100vh - 350px);
    overflow-y: auto;
    overflow-x: auto;
    margin-bottom: 20px;
    scrollbar-width: thin;
    -webkit-overflow-scrolling: touch;
    user-select: none;
    cursor: grab;
    position: relative; /* For sticky elements to work properly */
}

/* Enhanced scrollbar styling */
.table-responsive-container::-webkit-scrollbar {
    height: 12px;
    width: 12px;
    display: block;
}

.table-responsive-container::-webkit-scrollbar-track {
    background: #f8f9fa;
    border-radius: 6px;
    display: block;
}

.table-responsive-container::-webkit-scrollbar-thumb {
    background: linear-gradient(45deg, #007bff, #0056b3);
    border-radius: 6px;
    display: block;
    border: 2px solid #f8f9fa;
}

.table-responsive-container::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(45deg, #0056b3, #004085);
}

/* Ensure proper spacing and visibility */
.working-hours-table {
    min-width: 100%;
    font-size: 0.75rem;
    margin-bottom: 0; /* Remove default margin */
}

/* Mobile responsiveness for sticky column - removed width constraints */
@media (max-width: 768px) {
    .working-hours-table tbody td:first-child,
    .working-hours-table thead th:first-child {
        font-size: 0.7rem; /* Only change font size, not widths */
    }
    
    .submit-button-container {
        padding: 15px 0 25px 0;
    }
    
    .submit-button-container .btn {
        padding: 10px 25px;
        font-size: 0.9rem;
    }
}

@media (max-width: 480px) {
    .working-hours-table tbody td:first-child,
    .working-hours-table thead th:first-child {
        font-size: 0.65rem; /* Only change font size, not widths */
    }
}

.time-input-container {
    display: flex;
    align-items: center;
    gap: 5px;
    width: 100%;
    min-width: 0; /* Allow shrinking */
}

.time-pickers {
    flex: 1;
    min-width: 0; /* Allow shrinking */
    display: flex;
    align-items: center;
}

.time-pickers input[type="time"] {
    flex: 1;
    min-width: 80px; /* Minimum width for time inputs */
    max-width: 120px; /* Prevent inputs from getting too wide */
}

.time-pickers .mx-1 {
    flex-shrink: 0; /* Prevent the dash from shrinking */
    margin: 0 4px;
}

.dropdown {
    flex-shrink: 0; /* Prevent dropdown from shrinking */
}

.dropdown .btn {
    padding: 4px 8px; /* Smaller padding for the dropdown button */
    font-size: 0.75rem;
    border: 1px solid #ced4da;
}

/* Ensure the time slot containers don't break the layout */
.time-slot {
    margin-bottom: 4px;
}

.time-slots {
    width: 100%;
}

/* Make sure table cells accommodate the content properly */
.working-hours-table td {
    padding: 8px 6px;
    vertical-align: top;
    min-width: 200px; /* Ensure minimum cell width */
}

/* Holiday columns in header */
.working-hours-table thead th.holiday-column {
    background-color: #dc3545 !important;
    color: white;
}

/* Ensure proper layering for scrolling */
.table-responsive-container {
    position: relative;
    z-index: 1;
}

.working-hours-table tbody td:first-child {
    z-index: 6; /* Higher than regular header but lower than corner cell */
}

/* Department header row styling */
.department-header {
    background-color: #f8f9fa;
    font-weight: bold;
    padding: 10px;
}

.department-header .staff-name-cell {
    background-color: transparent !important;
    position: relative;
    z-index: 2;
}

/* Supervisor row styling */
.supervisor-row {
    background-color: #e3f2fd;
    font-weight: bold;
}

/* Ensure proper spacing in department rows */
.department-header + .staff-row {
    border-top: 1px solid #dee2e6;
}
/* Update the existing validation CSS styles */

    /* Time validation error styles */
    .time-validation-error {
        border: 2px solid #dc3545 !important;
        border-radius: 6px;
        background-color: rgba(220, 53, 69, 0.1);
        padding: 3px;
        position: relative; /* Add relative positioning */
    }

    .time-validation-error .time-pickers {
        background-color: rgba(220, 53, 69, 0.05);
    }

    .time-start.is-invalid,
    .time-end.is-invalid {
        border-color: #dc3545 !important;
        box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25) !important;
    }

    .time-validation-message {
        display: none;
        color: #dc3545;
        font-size: 0.75rem;
        font-weight: 500;
        margin-top: 4px;
        padding: 2px 6px;
        background-color: rgba(220, 53, 69, 0.1);
        border-radius: 3px;
        border-left: 3px solid #dc3545;
        position: absolute; /* Position absolutely */
        bottom: -25px; /* Position below the container */
        left: 0;
        right: 0;
        z-index: 1000; /* High z-index to appear above other elements */
        white-space: nowrap; /* Prevent text wrapping */
        box-shadow: 0 2px 4px rgba(0,0,0, 0.1); /* Add subtle shadow */
    }

    .time-validation-message i {
               margin-right: 4px;
    }

    /* Shake animation for validation errors */
    @keyframes shake {
        0%, 20%, 40%, 60%, 80% {
            transform: translateX(0);
        }
        10%, 30%, 50%, 70% {
            transform: translateX(-5px);
        }
        15%, 35%, 55%, 75% {
            transform: translateX(5px);
        }
    }

    /* Enhanced visual feedback */
    .time-validation-error:hover {
        border-color: #c82333;
        background-color: rgba(220, 53, 69, 0.15);
    }

    /* Add padding to the time slot to make room for error message */
    .time-slot {
        margin-bottom: 8px; /* Increased margin to accommodate error message */
        position: relative; /* Ensure proper positioning context */
        padding-bottom: 20px; /* Add bottom padding for error message space */
    }

    /* Mobile responsiveness for validation messages */
    @media (max-width: 768px) {
        .time-validation-message {
            font-size: 0.7rem;
            padding: 1px 4px;
            bottom: -22px; /* Adjust for smaller screens */
        }
        
        .time-slot {
            padding-bottom: 18px;
        }
    }

    @media (max-width: 480px) {
        .time-validation-message {
            font-size: 0.65rem;
            padding: 1px 3px;
            bottom: -20px; /* Adjust for smallest screens */
        }
        
        .time-slot {
            padding-bottom: 16px;
        }
    }

    /* Alternative: If you prefer the message to appear above the inputs */
    .time-validation-message.top {
        bottom: auto;
        top: -25px; /* Position above the container */
    }

    /* Alternative: If you prefer the message to appear to the right side */
    .time-validation-message.side {
        bottom: auto;
        top: 50%;
        left: 100%;
        right: auto;
        transform: translateY(-50%);
        margin-left: 10px;
        width: max-content;
        max-width: 200px;
    }

    
    
</style>

<!-- Google Calendar Time Picker JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Generate time options for the time picker dropdown
    function generateTimeOptions() {
        const timeList = [];
        // Generate times in 15-minute increments
        for (let hour = 0; hour < 24; hour++) {
            for (let minute = 0; minute < 60; minute += 15) {
                const hourFormatted = hour.toString().padStart(2, '0');
                const minuteFormatted = minute.toString().padStart(2, '0');
                const timeValue = `${hourFormatted}:${minuteFormatted}`;
                
                // Format for display (24-hour format)
                const displayTime = `${hourFormatted}:${minuteFormatted}`;
                
                timeList.push({ value: timeValue, display: displayTime });
            }
        }
        return timeList;
    }
    
    // Populate dropdown with time options
    function populateTimeDropdown(dropdown) {
        const timeList = dropdown.querySelector('.gcal-time-list');
        if (!timeList) return;
        
        const timeOptions = generateTimeOptions();
        timeList.innerHTML = '';
        
        timeOptions.forEach(time => {
            const li = document.createElement('li');
            li.className = 'gcal-time-item';
            li.dataset.value = time.value;
            li.textContent = time.display;
            timeList.appendChild(li);
        });
    }
    
    // Format time from 24-hour (HH:MM) to 24-hour (HH:MM)
    function formatTime(timeString) {
        if (!timeString) return '';
        
        const [hour, minute] = timeString.split(':');
        const hourNum = parseInt(hour, 10);
        const hourFormatted = hourNum.toString().padStart(2, '0');
        
        return `${hourFormatted}:${minute}`;
    }
    
    // Function to calculate end time (8 hours after start time)
    function calculateSuggestedEndTime(startTime) {
        if (!startTime) return '';
        
        const [hours, minutes] = startTime.split(':').map(Number);
        let endHour = hours + 8;
        let endMinute = minutes;
        
        // Handle overflow past 24 hours
        if (endHour >= 24) {
            endHour = endHour - 24;
        }
        
        return `${endHour.toString().padStart(2, '0')}:${endMinute.toString().padStart(2, '0')}`;
    }
    
    // Function to automatically suggest end time when start time is selected
    function autoSuggestEndTime(timePickersContainer, selectedStartTime) {
        const endTimeInput = timePickersContainer.querySelector('.time-end');
        const endTimeField = timePickersContainer.querySelector('.end-time-field');
        
        if (!endTimeInput || !endTimeField || endTimeInput.value) {
            // Don't override if end time is already set
            return;
        }
        
        const suggestedEndTime = calculateSuggestedEndTime(selectedStartTime);
        if (suggestedEndTime) {
            endTimeInput.value = suggestedEndTime;
            endTimeField.textContent = formatTime(suggestedEndTime);
            
            // Update the hidden input
            updateHiddenInput(timePickersContainer);
            
            // Add a subtle visual indication that this is auto-suggested
            endTimeField.style.backgroundColor = '#e8f5e8';
            endTimeField.style.transition = 'background-color 0.3s ease';
            
            // Remove the indication after 2 seconds
            setTimeout(() => {
                endTimeField.style.backgroundColor = '';
            }, 2000);
        }
    }

    // Initialize all time fields
    function initTimeFields() {
        // Find all time field containers
        document.querySelectorAll('.gcal-time-field').forEach(field => {
            if (field.dataset.disabled === 'true') return;
            
            const container = field.closest('.gcal-time-input');
            const dropdown = container.querySelector('.gcal-time-dropdown');
            const timeInput = container.querySelector('input[type="time"]');
            const isStartTime = field.classList.contains('start-time-field');
            
            // Populate the dropdown on first click
            let isDropdownPopulated = false;
            
            // Handle field click - show dropdown
            field.addEventListener('click', function() {
                if (field.dataset.disabled === 'true') return;
                
                // Close all other dropdowns first
                document.querySelectorAll('.gcal-time-dropdown.show').forEach(d => {
                    if (d !== dropdown) d.classList.remove('show');
                });
                
                // Populate dropdown if needed
                if (!isDropdownPopulated) {
                    populateTimeDropdown(dropdown);
                    isDropdownPopulated = true;
                }
                
                // Toggle dropdown visibility
                dropdown.classList.toggle('show');
                
                // Highlight the current time if set
                if (timeInput.value) {
                    const currentTimeItems = dropdown.querySelectorAll(`.gcal-time-item[data-value="${timeInput.value}"]`);
                    dropdown.querySelectorAll('.gcal-time-item').forEach(item => item.classList.remove('active'));
                    currentTimeItems.forEach(item => item.classList.add('active'));
                    
                    // Scroll to the active item
                    if (currentTimeItems.length > 0) {
                        currentTimeItems[0].scrollIntoView({ block: 'center' });
                    }
                }
            });
            
            // Handle selection from the dropdown
            dropdown.addEventListener('click', function(e) {
                e.stopPropagation();
                
                // Quick time button clicked
                if (e.target.classList.contains('gcal-quick-time')) {
                    const selectedTime = e.target.dataset.time;
                    if (selectedTime) {
                        timeInput.value = selectedTime;
                        field.textContent = formatTime(selectedTime);
                        
                        // Update active class for quick times
                        dropdown.querySelectorAll('.gcal-quick-time').forEach(qt => {
                            qt.classList.toggle('active', qt.dataset.time === selectedTime);
                        });
                        
                        // Update the hidden input
                        const container = field.closest('.time-pickers');
                        if (container) {
                            updateHiddenInput(container);
                        }
                        
                        // Auto-suggest end time if this is a start time selection
                        if (isStartTime) {
                            const timePickersContainer = field.closest('.time-pickers');
                            if (timePickersContainer) {
                                autoSuggestEndTime(timePickersContainer, selectedTime);
                            }
                        }
                        
                        dropdown.classList.remove('show');
                    }
                }
                // Regular time item clicked
                else if (e.target.classList.contains('gcal-time-item')) {
                    const selectedTime = e.target.dataset.value;
                    if (selectedTime) {
                        timeInput.value = selectedTime;
                        field.textContent = e.target.textContent;
                        
                        // Update active class
                        dropdown.querySelectorAll('.gcal-time-item').forEach(item => {
                            item.classList.remove('active');
                        });
                        e.target.classList.add('active');
                        
                        // Update the hidden input
                        const container = field.closest('.time-pickers');
                        if (container) {
                            updateHiddenInput(container);
                        }
                        
                        // Auto-suggest end time if this is a start time selection
                        if (isStartTime) {
                            const timePickersContainer = field.closest('.time-pickers');
                            if (timePickersContainer) {
                                autoSuggestEndTime(timePickersContainer, selectedTime);
                            }
                        }
                        
                        dropdown.classList.remove('show');
                    }
                }
            });
        });
        
        // Close dropdowns when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.gcal-time-input')) {
                document.querySelectorAll('.gcal-time-dropdown.show').forEach(dropdown => {
                    dropdown.classList.remove('show');
                });
            }
        });
    }
    
    // Ensure updateHiddenInput function is properly defined for Google Calendar time picker
    if (!window.updateHiddenInput) {
        window.updateHiddenInput = function(container) {
            const startInput = container.querySelector('.time-start');
            const endInput = container.querySelector('.time-end');
            const hiddenInput = container.parentElement.querySelector('input[type="hidden"]');
            
            // Look for notes input in the parent time-slot since it's outside the time-input-container
            const timeSlot = container.closest('.time-slot');
            const notesInput = timeSlot ? timeSlot.querySelector('.notes-input') : null;
            
            if (!startInput || !endInput || !hiddenInput) return;
            
            if (startInput.disabled && ['V', 'X', 'H'].includes(hiddenInput.value)) {
                return;
            }

            // Store original value if not already stored AND it's not a special type entry
            const inputId = hiddenInput.id || hiddenInput.name;
            if (!window.originalTimeValues.has(inputId) && 
                hiddenInput.value && 
                hiddenInput.value.includes('-') && 
                !hiddenInput.value.includes('on_call') && 
                !hiddenInput.value.includes('reception')) { 
                window.originalTimeValues.set(inputId, hiddenInput.value);
            }

            if (startInput.value && endInput.value) {
                // Validate time range - ADD THIS VALIDATION
                const isValid = validateTimeRange(container);
                
                if (!isValid) {
                    // Don't update hidden input if validation fails
                    return;
                }
                
                // Check if this is an on-call entry
                const timePickers = container.querySelector('.time-pickers');
                if (timePickers && timePickers.classList.contains('on-call-type')) {
                    const onCallData = {
                        start_time: startInput.value,
                        end_time: endInput.value,
                        type: 'on_call'
                    };
                    // Add notes if available
                    if (notesInput && notesInput.value.trim()) {
                        onCallData.notes = notesInput.value.trim();
                    }
                    hiddenInput.value = JSON.stringify(onCallData);
                }
                // Check if this is a reception entry
                else if (timePickers && timePickers.classList.contains('reception-type')) {
                    const receptionData = {
                        start_time: startInput.value,
                        end_time: endInput.value,
                        type: 'reception'
                    };
                    // Add notes if available
                    if (notesInput && notesInput.value.trim()) {
                        receptionData.notes = notesInput.value.trim();
                    }
                    hiddenInput.value = JSON.stringify(receptionData);
                } else {
                    // Always use JSON format for consistency
                    const timeData = {
                        start_time: startInput.value,
                        end_time: endInput.value,
                        type: 'normal'
                    };
                    // Add notes if available
                    if (notesInput && notesInput.value.trim()) {
                        timeData.notes = notesInput.value.trim();
                    }
                    hiddenInput.value = JSON.stringify(timeData);
                }
                window.markAsUnsaved();
            } else if (!startInput.value && !endInput.value) {
                hiddenInput.value = '';
                // Clear any validation errors when both fields are empty
                container.classList.remove('time-validation-error');
                startInput.classList.remove('is-invalid');
                endInput.classList.remove('is-invalid');
                const errorMsg = container.querySelector('.time-validation-message');
                if (errorMsg) {
                    errorMsg.style.display = 'none';
                }
            }
        };
    }
    
    // Ensure addTimeSlot function works with Google Calendar time picker
    if (!window.addTimeSlot) {
        window.addTimeSlot = function(targetContainer) {
    const timeSlots = targetContainer.closest('.time-slots');
    if (!timeSlots) return;
    
    // Clone the first time slot as template
    const firstTimeSlot = timeSlots.querySelector('.time-slot');
    if (!firstTimeSlot) return;
    
    const newTimeSlot = firstTimeSlot.cloneNode(true);
    
    // Reset values in the new slot
    const hiddenInput = newTimeSlot.querySelector('input[type="hidden"]');
    const startInput = newTimeSlot.querySelector('.time-start');
    const endInput = newTimeSlot.querySelector('.time-end');
    const timeInputContainer = newTimeSlot.querySelector('.time-input-container');
    const timePickers = newTimeSlot.querySelector('.time-pickers');
    
    if (hiddenInput) {
        hiddenInput.value = ''; // IMPORTANT: Clear any inherited day-off values
        // Generate new unique ID
        const originalId = hiddenInput.id;
        if (originalId) {
            const timestamp = Date.now();
            hiddenInput.id = originalId.replace(/(-\d+)$/, '') + '-' + timestamp;
        }
        // Clear all special classes and attributes
        hiddenInput.classList.remove('sick-leave-entry');
        hiddenInput.removeAttribute('data-sick-leave');
    }
    
    if (startInput) {
        startInput.value = '';
        // Remove any existing Flatpickr instance
        if (startInput._flatpickr) {
            startInput._flatpickr.destroy();
        }
        startInput.classList.remove('flatpickr-initialized');
        startInput.disabled = false; // IMPORTANT: Ensure inputs are enabled
    }
    
    if (endInput) {
        endInput.value = '';
        // Remove any existing Flatpickr instance
        if (endInput._flatpickr) {
            endInput._flatpickr.destroy();
        }
        endInput.classList.remove('flatpickr-initialized');
        endInput.disabled = false; // IMPORTANT: Ensure inputs are enabled
    }
    
    // IMPORTANT: Clear all container classes and reset to default state
    if (timeInputContainer) {
        timeInputContainer.classList.remove('day-off-container', 'on-call-container', 'reception-container', 'time-validation-error');
        timeInputContainer.removeAttribute('data-day-off-type');
    }
    
    if (timePickers) {
        timePickers.classList.remove('on-call-type', 'reception-type', 'special-type');
        timePickers.removeAttribute('data-day-off-type');
        timePickers.style.display = ''; // Ensure time pickers are visible
        
        // Remove existing icons
        const existingOnCallIcon = timePickers.querySelector('.on-call-icon');
        const existingReceptionIcon = timePickers.querySelector('.reception-icon');
        if (existingOnCallIcon) existingOnCallIcon.remove();
        if (existingReceptionIcon) existingReceptionIcon.remove();
        
        // Ensure basic-time-row is visible
        const basicTimeRow = timePickers.querySelector('.basic-time-row');
        if (basicTimeRow) {
            basicTimeRow.style.display = 'flex';
        }
    }
    
    // IMPORTANT: Remove any special display elements from cloned slot
    const existingSpecialDisplay = newTimeSlot.querySelector('.special-display');
    if (existingSpecialDisplay) {
        existingSpecialDisplay.remove();
    }
    
    // Clear any validation errors
    const validationMessage = newTimeSlot.querySelector('.time-validation-message');
    if (validationMessage) {
        validationMessage.remove();
    }
    
    // Remove invalid classes
    if (startInput) startInput.classList.remove('is-invalid');
    if (endInput) endInput.classList.remove('is-invalid');
    
    // Clear notes field
    const notesTextarea = newTimeSlot.querySelector('.notes-input');
    if (notesTextarea) {
        notesTextarea.value = '';
    }
    
    // Append the new time slot
    timeSlots.appendChild(newTimeSlot);
    
    // Initialize Flatpickr for the new time inputs immediately
    setTimeout(() => {
        initFlatpickrForContainer(timeInputContainer);
        
        // Add event listeners for notes field
        const notesTextarea = newTimeSlot.querySelector('.notes-input');
        if (notesTextarea) {
            notesTextarea.addEventListener('input', function() {
                const container = newTimeSlot.querySelector('.time-input-container');
                if (container) {
                    updateHiddenInput(container);
                }
            });
        }
        
        markAsUnsaved();
    }, 100);
};
    }
    
    // Ensure applyQuickFill function works with Google Calendar time picker
    if (!window.applyQuickFill) {
        window.applyQuickFill = function(container, value) {
            const hiddenInput = container.querySelector('input[type="hidden"]');
            const startInput = container.querySelector('.time-start');
            const endInput = container.querySelector('.time-end');
            const timePickers = container.querySelector('.time-pickers');
            const startField = container.querySelector('.start-time-field');
            const endField = container.querySelector('.end-time-field');

            if (!hiddenInput) return;

            // Clear all existing classes and attributes
            container.classList.remove('day-off-container', 'on-call-container', 'reception-container', 'time-validation-error');
            container.removeAttribute('data-day-off-type');
            
            // Remove existing icons and special displays
            const existingSpecialDisplay = container.querySelector('.special-display');
            if (existingSpecialDisplay) existingSpecialDisplay.remove();

            // Show time pickers if they were hidden
            if (timePickers) {
                timePickers.style.display = '';
                timePickers.classList.remove('on-call-type', 'reception-type', 'special-type');
                timePickers.removeAttribute('data-day-off-type');
                
                // Remove existing icons
                const existingOnCallIcon = timePickers.querySelector('.on-call-icon');
                const existingReceptionIcon = timePickers.querySelector('.reception-icon');
                if (existingOnCallIcon) existingOnCallIcon.remove();
                if (existingReceptionIcon) existingReceptionIcon.remove();
            }

            // Re-query inputs after showing time pickers (they might have been hidden)
            startInput = container.querySelector('.time-start');
            endInput = container.querySelector('.time-end');
            
            if (!startInput || !endInput || !timePickers) {
                console.error('Could not find time inputs after showing time pickers');
                return;
            }

            // Clear sick leave attributes
            hiddenInput.classList.remove('sick-leave-entry');
            hiddenInput.removeAttribute('data-sick-leave');

            switch(value) {
                case 'V':
                case 'X':
                case 'H':
                    hiddenInput.value = value;
                    
                    // Clear and disable time inputs
                    startInput.value = '';
                    endInput.value = '';
                    startInput.disabled = true;
                    endInput.disabled = true;
                    
                    // Hide the time pickers and add special styling
                    timePickers.classList.add('special-type');
                    timePickers.style.display = 'none';
                    timePickers.dataset.dayOffType = value;
                    
                    container.classList.add('day-off-container');
                    container.dataset.dayOffType = value;
                    
                    // Create the special display element (like on page load)
                    const specialDisplay = document.createElement('div');
                    specialDisplay.className = 'special-display d-flex align-items-center justify-content-center';
                    specialDisplay.style.cssText = 'flex: 1; padding: 5px; background-color: #f8f9fa; border-radius: 3px;';
                    specialDisplay.innerHTML = `<strong>${value}</strong>`;
                    
                    // Insert the special display before the dropdown
                    const dropdown = container.querySelector('.dropdown');
                    if (dropdown) {
                        container.insertBefore(specialDisplay, dropdown);
                    }
                    break;
                    
                case 'on_call':
                    // ENSURE inputs are enabled (redundant but explicit)
                    startInput.disabled = false;
                    endInput.disabled = false;
                    
                    // Add on-call styling and icon
                    timePickers.classList.add('on-call-type');
                    container.classList.add('on-call-container');
                    
                    // Add phone icon at the beginning of time-pickers
                    const phoneIcon = document.createElement('i');
                    phoneIcon.className = 'fas fa-phone on-call-icon';
                    phoneIcon.style.cssText = 'color: #28a745; margin-right: 5px; font-size: 14px;';
                    timePickers.insertBefore(phoneIcon, timePickers.firstChild);
                    
                    // Initialize Flatpickr and set default times
                    setTimeout(() => {
                        initFlatpickrForContainer(container);
                        
                        setTimeout(() => {
                            // SAFE SET DEFAULT TIMES with error handling
                            if (startInput._flatpickr && typeof startInput._flatpickr.setDate === 'function') {
                                try {
                                    startInput._flatpickr.setDate('09:00');
                                } catch (error) {
                                    console.warn('Failed to set on-call start time via Flatpickr:', error);
                                    startInput.value = '09:00';
                                }
                            } else {
                                startInput.value = '09:00';
                            }
                            
                            if (endInput._flatpickr && typeof endInput._flatpickr.setDate === 'function') {
                                try {
                                    endInput._flatpickr.setDate('17:00');
                                } catch (error) {
                                    console.warn('Failed to set on-call end time via Flatpickr:', error);
                                    endInput.value = '17:00';
                                }
                            } else {
                                endInput.value = '17:00';
                            }
                            
                            const onCallData = {
                                start_time: startInput.value || '09:00',
                                end_time: endInput.value || '17:00',
                                type: 'on_call'
                            };
                            hiddenInput.value = JSON.stringify(onCallData);
                        }, 100);
                    }, 50);
                    break;
                    
                case 'reception':
                    // ENSURE inputs are enabled (redundant but explicit)
                    startInput.disabled = false;
                    endInput.disabled = false;
                    
                    // Add reception styling and icon
                    timePickers.classList.add('reception-type');
                    container.classList.add('reception-container');
                    
                    // Add desktop icon at the beginning of time-pickers
                    const desktopIcon = document.createElement('i');
                    desktopIcon.className = 'fas fa-desktop reception-icon';
                    desktopIcon.style.cssText = 'color: #007bff; margin-right: 5px; font-size: 14px;';
                    timePickers.insertBefore(desktopIcon, timePickers.firstChild);
                    
                    // Reinitialize Flatpickr if needed
                    if (!startInput.classList.contains('flatpickr-initialized') || startInput.disabled) {
                        // Destroy existing instances first
                        if (startFlatpickr) startFlatpickr.destroy();
                        if (endFlatpickr) endFlatpickr.destroy();
                        startInput.classList.remove('flatpickr-initialized');
                        endInput.classList.remove('flatpickr-initialized');
                        
                        // Reinitialize
                        initFlatpickrForContainer(container);
                    }
                    
                    if (startInput.value && endInput.value) {
                        const receptionData = {
                            start_time: startInput.value,
                            end_time: endInput.value,
                            type: 'reception'
                        };
                        hiddenInput.value = JSON.stringify(receptionData);
                    } else {
                        // Set default times if empty
                        if (startInput._flatpickr && !startInput.value) startInput._flatpickr.setDate('09:00');
                        if (endInput._flatpickr && !endInput.value) endInput._flatpickr.setDate('17:00');
                        
                        const receptionData = {
                            start_time: startInput.value || '09:00',
                            end_time: endInput.value || '17:00',
                            type: 'reception'
                        };
                        hiddenInput.value = JSON.stringify(receptionData);
                    }
                    break;
                    
                case 'SL':
                    // ENSURE inputs are enabled (redundant but explicit)
                    startInput.disabled = false;
                    endInput.disabled = false;
                    hiddenInput.classList.add('sick-leave-entry');
                    hiddenInput.dataset.sickLeave = 'true';
                    
                    // Reinitialize Flatpickr if needed
                    if (!startInput.classList.contains('flatpickr-initialized') || startInput.disabled) {
                        // Destroy existing instances first
                        if (startFlatpickr) startFlatpickr.destroy();
                        if (endFlatpickr) endFlatpickr.destroy();
                        startInput.classList.remove('flatpickr-initialized');
                        endInput.classList.remove('flatpickr-initialized');
                        
                        // Reinitialize
                        initFlatpickrForContainer(container);
                    }
                    
                    // Set default times if empty
                    if (startInput._flatpickr && !startInput.value) startInput._flatpickr.setDate('10:00');
                    if (endInput._flatpickr && !endInput.value) endInput._flatpickr.setDate('18:00');
                    
                    hiddenInput.value = `${startInput.value || '10:00'}-${endInput.value || '18:00'}`;
                    break;
                    
                case 'regular':
                    // Add temporary class to indicate we're converting to regular
                    container.classList.add('converting-to-regular');
                    
                    // FORCE enable inputs first - CRITICAL FIX
                    startInput.disabled = false;
                    endInput.disabled = false;
                    
                    // Clear any existing values first
                    startInput.value = '';
                    endInput.value = '';
                    
                    // Destroy any existing Flatpickr instances to ensure clean state
                    if (startInput._flatpickr) {
                        startInput._flatpickr.destroy();
                    }
                    if (endInput._flatpickr) {
                        endInput._flatpickr.destroy();
                    }
                    startInput.classList.remove('flatpickr-initialized');
                    endInput.classList.remove('flatpickr-initialized');
                    
                    // ENSURE inputs are enabled after destroying Flatpickr
                    startInput.disabled = false;
                    endInput.disabled = false;
                    
                    // Remove any disabled attributes that might be lingering
                    startInput.removeAttribute('disabled');
                    endInput.removeAttribute('disabled');
                    
                    // Initialize Flatpickr and set default times with proper delays
                    setTimeout(() => {
                        // TRIPLE CHECK: Ensure inputs are still enabled before initialization
                        startInput.disabled = false;
                        endInput.disabled = false;
                        startInput.removeAttribute('disabled');
                        endInput.removeAttribute('disabled');
                        
                        console.log('Regular hours: About to initialize Flatpickr', {
                            startDisabled: startInput.disabled,
                            endDisabled: endInput.disabled,
                            startHasDisabledAttr: startInput.hasAttribute('disabled'),
                            endHasDisabledAttr: endInput.hasAttribute('disabled')
                        });
                        
                        initFlatpickrForContainer(container);
                        
                        setTimeout(() => {
                            // FINAL CHECK: Ensure inputs are still enabled after initialization
                            startInput.disabled = false;
                            endInput.disabled = false;
                            startInput.removeAttribute('disabled');
                            endInput.removeAttribute('disabled');
                            
                            // SAFE SET DEFAULT TIMES: Check if Flatpickr is properly initialized before calling setDate
                            if (startInput._flatpickr && typeof startInput._flatpickr.setDate === 'function') {
                                try {
                                    startInput._flatpickr.setDate('09:00');
                                    console.log('Start time set via Flatpickr');
                                } catch (error) {
                                    console.warn('Failed to set start time via Flatpickr:', error);
                                    startInput.value = '09:00';
                                }
                            } else {
                                console.log('Setting start time directly');
                                startInput.value = '09:00';
                            }
                            
                            if (endInput._flatpickr && typeof endInput._flatpickr.setDate === 'function') {
                                try {
                                    endInput._flatpickr.setDate('17:00');
                                    console.log('End time set via Flatpickr');
                                } catch (error) {
                                    console.warn('Failed to set end time via Flatpickr:', error);
                                    endInput.value = '17:00';
                                }
                            } else {
                                console.log('Setting end time directly');
                                endInput.value = '17:00';
                            }
                            
                            hiddenInput.value = '09:00-17:00';
                            
                            // ABSOLUTE FINAL CHECK: Force inputs to be enabled
                            startInput.disabled = false;
                            endInput.disabled = false;
                            startInput.removeAttribute('disabled');
                            endInput.removeAttribute('disabled');
                            
                            // Remove the temporary class after initialization
                            container.classList.remove('converting-to-regular');
                            
                            console.log('Regular hours: Final state', {
                                startEnabled: !startInput.disabled,
                                endEnabled: !endInput.disabled,
                                startValue: startInput.value,
                                endValue: endInput.value,
                                hiddenValue: hiddenInput.value,
                                flatpickrInitialized: startInput.classList.contains('flatpickr-initialized'),
                                startFlatpickrExists: !!startInput._flatpickr,
                                endFlatpickrExists: !!endInput._flatpickr
                            });
                        }, 300); // Increased timeout for proper initialization
                    }, 150); // Increased timeout for DOM updates
                    break;
                    
                default:
                    hiddenInput.value = value;
                    break;
            }
            
            // Update the visible time fields for Google Calendar picker
            const startTimeField = container.querySelector('.start-time-field');
            const endTimeField = container.querySelector('.end-time-field');
            
            if (startInput && startTimeField && startInput.value) {
                startTimeField.textContent = formatTime(startInput.value);
            } else if (startTimeField) {
                startTimeField.textContent = 'Start';
            }
            if (endInput && endTimeField && endInput.value) {
                endTimeField.textContent = formatTime(endInput.value);
            } else if (endTimeField) {
                endTimeField.textContent = 'End';
            }
            
            markAsUnsaved();
        };
    } else {
        // Override existing applyQuickFill to update GCal time fields
        const originalApplyQuickFill = window.applyQuickFill;
        window.applyQuickFill = function(container, value) {
            const result = originalApplyQuickFill(container, value);
            
            // Update the visible time fields
            setTimeout(() => {
                const startInput = container.querySelector('.time-start');
                const endInput = container.querySelector('.time-end');
                const startField = container.querySelector('.start-time-field');
                const endField = container.querySelector('.end-time-field');
                
                if (startInput && startField && startInput.value) {
                    startField.textContent = formatTime(startInput.value);
                }
                if (endInput && endField && endInput.value) {
                    endField.textContent = formatTime(endInput.value);
                }
            }, 50);
            
            return result;
        };
    }
    
    window.removeTimeSlot = function(targetContainer) {
    const timeSlot = targetContainer.closest('.time-slot');
    const timeSlots = targetContainer.closest('.time-slots');
    
    if (!timeSlot || !timeSlots) return;
    
    // Count how many time slots exist
    const allTimeSlots = timeSlots.querySelectorAll('.time-slot');
    
    // Don't allow removing if it's the only time slot
    if (allTimeSlots.length <= 1) {
        Swal.fire({
            title: 'Cannot Remove',
            text: 'At least one time slot must remain.',
            icon: 'warning',
            confirmButtonText: 'OK'
        });
        return;
    }
    
    // Show confirmation before removing
    Swal.fire({
        title: 'Remove Time Slot?',
        text: 'Are you sure you want to remove this time slot?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, Remove',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            // Remove the time slot
            timeSlot.remove();
            
            // Mark as unsaved
            markAsUnsaved();
        }
    });
};
    // Initialize all time fields on page load
    initTimeFields();
    
    // Also initialize when new content is added dynamically
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'childList') {
                mutation.addedNodes.forEach(function(node) {
                    if (node.nodeType === 1 && (node.classList.contains('gcal-time-field') || node.querySelector('.gcal-time-field'))) {
                        setTimeout(() => initTimeFields(), 50);
                    }
                });
            }
        });
    });
    
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
});
</script>