@extends('layouts.mobile.app')

@section('title', 'Staff Working Hours - Mobile')

@section('header-title', 'Working Hours')

@section('header-content')
    <!-- Week Navigation -->
    <div class="d-flex justify-content-between align-items-center mt-2">
        <a href="{{ route(Route::currentRouteName(), ['week' => Carbon\Carbon::parse($selectedDate)->subWeek()->format('Y-m-d')]) }}" 
           class="btn btn-light btn-sm">
            <i class="fas fa-chevron-left"></i>
        </a>
        
        <div class="text-center">
            <small>Week:</small><br>
            <strong>{{ Carbon\Carbon::parse($selectedDate)->startOfWeek()->format('M d') }} - 
            {{ Carbon\Carbon::parse($selectedDate)->endOfWeek()->format('M d, Y') }}</strong>
        </div>
        
        <a href="{{ route(Route::currentRouteName(), ['week' => Carbon\Carbon::parse($selectedDate)->addWeek()->format('Y-m-d')]) }}" 
           class="btn btn-light btn-sm">
            <i class="fas fa-chevron-right"></i>
        </a>
    </div>
@endsection

@section('content')
<!-- Department Tabs -->
<div class="mobile-tabs">
    @foreach($staffByDepartment as $department => $departmentStaff)
        <button class="mobile-tab {{ $loop->first ? 'active' : '' }}" 
                data-department="{{ Str::slug($department) }}"
                onclick="switchDepartment('{{ Str::slug($department) }}')">
            {{ $department }}
            <span class="badge bg-light text-dark ms-1">{{ $departmentStaff->count() }}</span>
        </button>
    @endforeach
</div>

<!-- Department Content -->
@foreach($staffByDepartment as $department => $departmentStaff)
<div class="department-content {{ $loop->first ? 'active' : 'd-none' }}" 
     id="dept-{{ Str::slug($department) }}">
    
    <!-- Department Form -->
    <form action="{{ route('staff.working-hours.store') }}" method="POST" class="mobile-department-form" id="form-{{ Str::slug($department) }}">
        @csrf
        <input type="hidden" name="week" value="{{ $selectedDate }}">
        <input type="hidden" name="department" value="{{ $department }}">
        
        <!-- Reception row (only in first department) -->
        @if($loop->first)
            @foreach($dates as $date)
                @php $dateString = $date->format('Y-m-d'); @endphp
                <input type="hidden" name="reception[{{ $dateString }}]" value="{{ $receptionData[$dateString] ?? '' }}" class="reception-input" data-date="{{ $dateString }}">
            @endforeach
            
            @foreach($dates as $date)
                @php $dateString = $date->format('Y-m-d'); @endphp
                <input type="hidden" name="midnight_phone[{{ $dateString }}]" value="{{ $midnightPhoneData[$dateString] ?? '' }}" class="midnight-phone-input" data-date="{{ $dateString }}">
            @endforeach
        @endif
    
    <!-- Staff List -->
    @foreach($departmentStaff as $staffIndex => $staff)
    <div class="mobile-card staff-card {{ $staffIndex === 0 ? 'active' : 'd-none' }}" 
         data-staff-id="{{ $staff->id }}"
         data-staff-email="{{ $staff->email }}">
        
        <!-- Staff Header -->
        <div class="card-header d-flex justify-content-between align-items-center" style="background: #2c3749; color: #dee2e6;">
            <h5 class="mb-0 staff-name" data-staff-name="{{ $staff->name }}">{{ $staff->name }}</h5>
            <div class="staff-navigation">
                <button class="btn btn-light btn-sm me-1" id="prev-staff-btn" style="display: none;">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <span class="badge bg-light text-dark staff-counter">1/{{ $departmentStaff->count() }}</span>
                <button class="btn btn-light btn-sm ms-1" id="next-staff-btn" @if($departmentStaff->count() <= 1) style="display: none;" @endif>
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        </div>
        
        <!-- Day Selection -->
        <div class="card-body p-0">
            <div class="day-selector p-3 border-bottom">
                <div class="row">
                    @foreach($dates as $dayIndex => $date)
                        @php
                            $dateString = $date->format('Y-m-d');
                            $isHoliday = $holidays->contains($dateString);
                            $dayName = $date->format('D');
                            $dayNumber = $date->format('d');
                        @endphp
                        <div class="col">
                            <button class="btn btn-sm w-100 day-btn {{ $dayIndex === 0 ? 'active' : '' }} {{ $isHoliday ? 'holiday' : '' }}" 
                    style="border: 1px solid #23cbe0; color: #dee2e6; background: #323e53;" 
                                    data-date="{{ $dateString }}"
                                    data-day-index="{{ $dayIndex }}"
                                    type="button"
                                    onclick="selectDay('{{ $dateString }}', {{ $dayIndex }})"
                                    title="Click to select {{ $dayName }} {{ $dayNumber }}">
                                <small>{{ $dayName }}</small>
                                <strong>{{ $dayNumber }}</strong>
                                @if($isHoliday)
                                    <i class="fas fa-star"></i>
                                @endif
                            </button>
                        </div>
                    @endforeach
                </div>
            </div>
            
            <!-- Time Entry for Selected Day -->
            @foreach($dates as $dayIndex => $date)
                @php
                    $dateString = $date->format('Y-m-d');
                    $hoursData = $staffHours[$staff->id][$dateString]['hours_data'] ?? [];
                    $isApproved = $staffHours[$staff->id][$dateString]['is_approved'] ?? 1;
                    $staffDateKey = $staff->id . '_' . $dateString;
                    $sickLeaveInfo = $sickLeaveStatuses[$staffDateKey] ?? null;
                @endphp
                
                <div class="day-content {{ $dayIndex === 0 ? 'active' : 'd-none' }}" 
                     data-date="{{ $dateString }}">
                    
                    <!-- Sick Leave Alert -->
                    @if($sickLeaveInfo)
                        @php
                            $statusText = '';
                            $statusClass = '';
                            switch($sickLeaveInfo->status) {
                                case '0': $statusText = 'Sick Leave - Pending from supervisor'; $statusClass = 'warning'; break;
                                case '1': $statusText = 'Sick Leave - Pending from HR'; $statusClass = 'warning'; break;
                                case '2': $statusText = 'Sick Leave - Approved'; $statusClass = 'success'; break;
                                case '3': $statusText = 'Sick Leave - Rejected'; $statusClass = 'danger'; break;
                                case '4': $statusText = 'Sick Leave - Cancelled'; $statusClass = 'secondary'; break;
                                default: $statusText = 'Sick Leave - Unknown status'; $statusClass = 'secondary';
                            }
                        @endphp
                        <div class="alert alert-{{ $statusClass }} m-3">
                            <strong>{{ $statusText }}</strong>
                            @if($sickLeaveInfo->description)
                                <br><small><strong>Description:</strong> {{ $sickLeaveInfo->description }}</small>
                            @endif
                        </div>
                    @endif
                    
                    <!-- Time Entries -->
                    <div class="p-3">
                        <h6 class="mb-3">{{ $date->format('l, M d, Y') }}</h6>
                        
                        <div class="day-form" data-staff-id="{{ $staff->id }}" data-date="{{ $dateString }}">
                            
                            {{-- Debug hours data --}}
                            @if(config('app.debug'))
                                <!-- DEBUG: Hours data for {{ $dateString }}: {{ json_encode($hoursData) }} -->
                            @endif
                            
                            @forelse($hoursData as $index => $timeRange)
                                <div class="time-entry mb-3 {{ $isApproved == 0 ? 'unapproved' : '' }}">
                                    @include('staffs.mobile.partials.time-entry', [
                                        'timeRange' => $timeRange,
                                        'index' => $index,
                                        'staff' => $staff,
                                        'dateString' => $dateString,
                                        'isApproved' => $isApproved
                                    ])
                                </div>
                            @empty
                                <div class="time-entry mb-3">
                                    @include('staffs.mobile.partials.time-entry', [
                                        'timeRange' => null,
                                        'index' => 0,
                                        'staff' => $staff,
                                        'dateString' => $dateString,
                                        'isApproved' => 1
                                    ])
                                </div>
                            @endforelse
                            
                            <!-- Add Time Slot Button -->
                            <button type="button" class="btn btn-mobile w-100 mb-3" style="background: #28a745; color: white; border: none;" 
                                    onclick="addTimeSlot('{{ $staff->id }}', '{{ $dateString }}')">
                                <i class="fas fa-plus-circle me-2"></i>Add Time Slot
                            </button>
                            
                            <!-- Save Day Button -->
                            <button type="button" class="btn btn-mobile w-100" style="background: #23cbe0; color: white; border: none;" 
                                    onclick="saveDay('{{ $staff->id }}', '{{ $dateString }}')">
                                <i class="fas fa-save me-2"></i>Save {{ $date->format('D') }}
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    @endforeach
    </form>
</div>
@endforeach

@endsection

@section('footer-content')
<div class="d-flex gap-2">
    <button type="button" class="btn btn-success btn-mobile flex-fill" onclick="saveAllChanges()">
        <i class="fas fa-save me-2"></i>Save All Changes
    </button>
    <button type="button" class="btn btn-outline-secondary btn-mobile" onclick="showWeekPicker()">
        <i class="fas fa-calendar me-2"></i>
    </button>
</div>
@endsection

@push('styles')
<style>
/* Compact Mobile Day Selector - Project Theme Colors */
.day-selector {
    padding: 8px 12px !important;
    background: #2c3749;
    border-radius: 12px;
    margin: 8px;
    border: 1px solid #38455c;
}

.day-btn {
    transition: all 0.2s ease;
    min-height: 48px !important;
    padding: 6px 4px !important;
    position: relative;
    border-radius: 8px !important;
    font-size: 0.85rem;
    border: 1.5px solid #38455c;
    background: #323e53;
    color: #dee2e6;
    margin: 2px;
}

.day-btn small {
    font-size: 0.7rem;
    font-weight: 500;
    line-height: 1;
    display: block;
    margin-bottom: 2px;
    color: #a8b5c8;
}

.day-btn strong {
    font-size: 1rem;
    font-weight: 600;
    line-height: 1;
    display: block;
    color: #dee2e6;
}

.day-btn.active {
    background-color: #23cbe0 !important;
    color: white !important;
    border-color: #23cbe0 !important;
    box-shadow: 0 2px 6px rgba(35, 203, 224, 0.4);
    transform: translateY(-1px);
}

.day-btn.active small,
.day-btn.active strong {
    color: white !important;
}

.day-btn.holiday {
    background-color: #3a4553;
    border-color: #ffc107;
    position: relative;
}

.day-btn.holiday.active {
    background-color: #ffc107 !important;
    color: #242d3e !important;
    box-shadow: 0 2px 6px rgba(255, 193, 7, 0.4);
    transform: translateY(-1px);
}

.day-btn.holiday.active small,
.day-btn.holiday.active strong {
    color: #242d3e !important;
}

.day-btn:hover:not(.active) {
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(35, 203, 224, 0.2);
    border-color: #23cbe0;
    background-color: #3a4757;
}

.day-btn:active {
    transform: translateY(0);
}

/* Holiday star indicator - more compact */
.day-btn .fas.fa-star {
    position: absolute;
    top: 4px;
    right: 4px;
    font-size: 0.6rem !important;
    color: #ffc107;
    text-shadow: 0 1px 1px rgba(0,0,0,0.2);
}

/* Row spacing adjustment for compact layout */
.day-selector .row {
    margin: 0 -2px;
}

.day-selector .col {
    padding: 0 2px;
}

/* Responsive adjustments for very small screens */
@media (max-width: 320px) {
    .day-btn {
        min-height: 44px !important;
        padding: 4px 2px !important;
        font-size: 0.8rem;
    }
    
    .day-btn small {
        font-size: 0.65rem;
    }
    
    .day-btn strong {
        font-size: 0.9rem;
    }
    
    .day-selector {
        padding: 6px 8px !important;
        margin: 6px;
    }
}

/* Larger screens - slightly more spacing */
@media (min-width: 400px) {
    .day-btn {
        min-height: 52px !important;
        padding: 8px 6px !important;
        margin: 3px;
    }
    
    .day-selector {
        padding: 10px 15px !important;
        margin: 10px;
    }
    
    .day-selector .col {
        padding: 0 3px;
    }
}

.time-entry.unapproved {
    background-color: rgba(255, 193, 7, 0.1);
    border: 1px solid rgba(255, 193, 7, 0.3);
    border-radius: 8px;
    padding: 12px;
    position: relative;
}

.time-entry.unapproved::before {
    content: "PENDING APPROVAL";
    position: absolute;
    top: -8px;
    right: 8px;
    background: #ffc107;
    color: #000;
    font-size: 0.7rem;
    font-weight: bold;
    padding: 2px 6px;
    border-radius: 4px;
    z-index: 5;
}

.staff-card {
    transition: transform 0.3s ease;
}

.department-content {
    transition: opacity 0.3s ease;
}

/* Touch feedback */
.btn:active {
    transform: scale(0.98);
}

/* Custom time picker styling - Dark Theme */
.time-picker-group {
    background: #2c3749;
    border-radius: 8px;
    padding: 12px;
    margin-bottom: 8px;
    border: 1px solid #38455c;
}

/* Day content visibility */
.day-content {
    display: block !important;
}

.day-content.d-none {
    display: none !important;
}

.day-content.active {
    display: block !important;
}

/* Debug styles for day content - removed */

.day-form {
    min-height: 200px;
    padding: 15px;
}

/* Ensure time entries are visible and properly spaced - Dark Theme */
.time-entry {
    display: block !important;
    margin-bottom: 15px;
    padding: 10px;
    background: #323e53;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.3);
    border: 1px solid #38455c;
    clear: both; /* Prevent floating issues */
    position: relative; /* Establish positioning context */
    color: #dee2e6;
}

/* Ensure time picker groups don't overlap */
.time-picker-group {
    margin-bottom: 12px;
    clear: both;
    overflow: hidden;
}

/* Fix mobile form layout */
.day-form {
    min-height: 200px;
    padding: 15px;
    overflow: hidden; /* Prevent layout issues */
}

/* Ensure buttons don't overlap */
.btn-mobile {
    margin-bottom: 10px;
    clear: both;
    width: 100%;
    position: relative;
    z-index: 1;
}

/* Dark theme form inputs */
.time-entry .form-control {
    background: #38455c;
    border: 1px solid #4a5568;
    color: #dee2e6;
}

.time-entry .form-control:focus {
    background: #38455c;
    border-color: #23cbe0;
    color: #dee2e6;
    box-shadow: 0 0 0 0.2rem rgba(35, 203, 224, 0.25);
}

.time-entry .form-label {
    color: #a8b5c8;
}

/* Dark theme for badges */
.time-entry .badge {
    background: #4a5568 !important;
    color: #dee2e6;
}

.time-entry .badge.bg-secondary {
    background: #4a5568 !important;
    color: #dee2e6;
}

/* Special type display styling */
.time-entry .special-type-display .badge {
    background: #4a5568 !important;
    color: #dee2e6;
    border: 1px solid #38455c;
}

/* Buttons in time entries */
.time-entry .btn-outline-danger {
    border-color: #dc3545;
    color: #dc3545;
}

.time-entry .btn-outline-danger:hover {
    background: #dc3545;
    color: #dee2e6;
}

.time-entry .btn-outline-primary {
    border-color: #23cbe0;
    color: #23cbe0;
}

.time-entry .btn-outline-primary:hover {
    background: #23cbe0;
    color: #2c3749;
}

/* Quick action buttons dark theme */
.time-entry .btn-group .btn {
    border-color: #4a5568;
    color: #a8b5c8;
    background: #38455c;
}

.time-entry .btn-group .btn:hover {
    background: #4a5568;
    border-color: #23cbe0;
    color: #23cbe0;
}

.time-entry .btn-outline-secondary {
    border-color: #4a5568;
    color: #a8b5c8;
    background: #38455c;
}

.time-entry .btn-outline-secondary:hover {
    background: #4a5568;
    border-color: #a8b5c8;
    color: #dee2e6;
}

.time-entry .btn-outline-success {
    border-color: #28a745;
    color: #28a745;
    background: #38455c;
}

.time-entry .btn-outline-success:hover {
    background: #28a745;
    color: #dee2e6;
}

.time-entry .btn-outline-info {
    border-color: #17a2b8;
    color: #17a2b8;
    background: #38455c;
}

.time-entry .btn-outline-info:hover {
    background: #17a2b8;
    color: #dee2e6;
}
</style>
@endpush

@push('scripts')
<script>
// Mobile-specific JavaScript for staff hours
console.log('Mobile JavaScript loading...');

@php
    $firstDepartment = array_keys($staffByDepartment)[0] ?? '';
    $currentUserDepartment = Auth::user()->staff ? Auth::user()->staff->department : '';
    $departmentsWithPhoneReception = ['Operations', 'HR', 'Booking'];
    $showPhoneReceptionButtons = false;
    if ($currentUserDepartment) {
        foreach ($departmentsWithPhoneReception as $allowedDept) {
            // Check for exact match or if department contains the allowed department name
            if ($currentUserDepartment === $allowedDept || 
                str_contains(strtolower($currentUserDepartment), strtolower($allowedDept))) {
                $showPhoneReceptionButtons = true;
                break;
            }
        }
    }
@endphp

let currentDepartment = @json($firstDepartment);
let currentStaffIndex = 0;
let currentDayIndex = 0;
let hasUnsavedChanges = false;

// Debug function to check navigation state
window.debugNavigationState = function() {
    console.log('🔍 Navigation State Debug:');
    console.log('Current department:', currentDepartment);
    console.log('Current staff index:', currentStaffIndex);
    console.log('Current day index:', currentDayIndex);
    
    const activeContent = document.querySelector('.department-content.active');
    if (activeContent) {
        const staffCards = activeContent.querySelectorAll('.staff-card');
        console.log('Staff cards in active department:', staffCards.length);
        
        staffCards.forEach((card, index) => {
            const isActive = card.classList.contains('active');
            const hasHiddenClass = card.classList.contains('d-none');
            // Get the staff name from the data attribute AND the staff ID for better debugging
            const staffNameAttr = card.querySelector('.staff-name')?.getAttribute('data-staff-name') || 'Unknown';
            const staffNameText = card.querySelector('.staff-name')?.textContent || 'Unknown';
            const staffId = card.getAttribute('data-staff-id') || 'No ID';
            const visibility = hasHiddenClass ? 'hidden' : (isActive ? 'ACTIVE' : 'visible');
            console.log(`  Staff ${index}: ${staffNameAttr} (ID: ${staffId}) [Text: ${staffNameText}] - ${visibility}`);
        });
    }
};

// Test navigation functions for debugging
window.testNavigation = function() {
    console.log('🧪 Testing navigation sequence...');
    console.log('Starting from staff 0');
    showStaffMember(0);
    
    setTimeout(() => {
        console.log('Moving to staff 1');
        showStaffMember(1);
        
        setTimeout(() => {
            console.log('Moving to staff 2'); 
            showStaffMember(2);
            
            setTimeout(() => {
                console.log('Back to staff 1');
                showStaffMember(1);
                setTimeout(() => debugNavigationState(), 100);
            }, 1000);
        }, 1000);
    }, 1000);
};

// Manual navigation helpers
window.goToStaff = function(index) {
    console.log(`🎯 Manual navigation to staff ${index}`);
    showStaffMember(index);
    setTimeout(() => debugNavigationState(), 100);
};
let userDepartment = @json($currentUserDepartment);
let showPhoneReceptionButtons = @json($showPhoneReceptionButtons);

console.log('Variables initialized:', {currentDepartment, currentStaffIndex, currentDayIndex});
console.log('User department:', userDepartment);
console.log('Show phone/reception buttons:', showPhoneReceptionButtons);
console.log('Departments with phone/reception access: Operations, HR, Booking (and variations like Package Booking)');

// Debug data availability
console.log('🔍 Debug data:');
console.log('Staff by department:', {!! json_encode(array_keys($staffByDepartment)) !!});
console.log('Selected date:', '{{ $selectedDate }}');
@php
    // Debug staff hours data
    $debugStaffHours = [];
    foreach($staffByDepartment as $dept => $staffList) {
        foreach($staffList as $staff) {
            $debugStaffHours[$staff->id] = [
                'name' => $staff->name,
                'has_hours' => isset($staffHours[$staff->id]) ? 'yes' : 'no',
                'hours_count' => isset($staffHours[$staff->id]) ? count($staffHours[$staff->id]) : 0,
                'hours_data' => $staffHours[$staff->id] ?? 'none'
            ];
        }
    }
@endphp
console.log('Staff hours debug:', {!! json_encode($debugStaffHours) !!});


// Debug function to check current state
window.debugCurrentState = function() {
    console.log('🐛 DEBUG CURRENT STATE:');
    const activeStaff = document.querySelector('.staff-card.active');
    if (activeStaff) {
        console.log('Active staff card:', activeStaff);
        console.log('Day buttons in active staff:', activeStaff.querySelectorAll('.day-btn').length);
        console.log('Day contents in active staff:', activeStaff.querySelectorAll('.day-content').length);
        
        const visibleDayContent = activeStaff.querySelector('.day-content:not(.d-none)');
        if (visibleDayContent) {
            console.log('Visible day content:', visibleDayContent);
            console.log('Visible day content date:', visibleDayContent.getAttribute('data-date'));
            console.log('Time entries in visible day:', visibleDayContent.querySelectorAll('.time-entry').length);
            console.log('Day content HTML preview:', visibleDayContent.innerHTML.substring(0, 300) + '...');
            
            // Check if there are any time input fields
            const timeInputs = visibleDayContent.querySelectorAll('input[type="time"]');
            console.log('Time input fields found:', timeInputs.length);
            
            // Check if there are any time picker groups
            const timePickerGroups = visibleDayContent.querySelectorAll('.time-picker-group');
            console.log('Time picker groups found:', timePickerGroups.length);
            
            // Show actual HTML content for debugging
            if (visibleDayContent.innerHTML.length < 500) {
                console.log('📄 Full day content HTML:', visibleDayContent.innerHTML);
            }
            
            // Check for specific elements that should be there
            const dayForm = visibleDayContent.querySelector('.day-form');
            console.log('Day form found:', !!dayForm);
            
            const addButton = visibleDayContent.querySelector('.btn-outline-success');
            console.log('Add Time Slot button found:', !!addButton);
            
            const saveButton = visibleDayContent.querySelector('.btn-primary');
            console.log('Save button found:', !!saveButton);
        } else {
            console.log('❌ No visible day content found');
        }
    } else {
        console.log('❌ No active staff card found');
    }
};

// Debug function to show all day contents (for testing)
window.showAllDayContents = function() {
    console.log('🔍 Showing ALL day contents for debugging...');
    const activeStaffCard = document.querySelector('.staff-card.active');
    if (activeStaffCard) {
        const dayContents = activeStaffCard.querySelectorAll('.day-content');
        dayContents.forEach((content, index) => {
            content.classList.remove('d-none');
            content.style.display = 'block';
            content.style.border = `3px solid ${index === 0 ? 'red' : 'blue'}`;
            content.style.margin = '10px 0';
            console.log(`📄 Day content ${index}:`, content.getAttribute('data-date'), content.innerHTML.length + ' chars');
        });
    }
};

// Reset function to fix duplicate content issue
window.resetDayVisibility = function() {
    console.log('🔧 Resetting day content visibility...');
    const activeStaffCard = document.querySelector('.staff-card.active');
    if (activeStaffCard) {
        const dayContents = activeStaffCard.querySelectorAll('.day-content');
        dayContents.forEach((content, index) => {
            // Hide all day contents first
            content.classList.add('d-none');
            content.classList.remove('active');
            content.style.display = '';
            content.style.border = '';
            content.style.margin = '';
        });
        
        // Show only the first day
        if (dayContents[0]) {
            dayContents[0].classList.remove('d-none');
            dayContents[0].classList.add('active');
            console.log('✅ Reset complete - showing first day only');
        }
    }
};

// Select day - moved to top for global access
window.selectDay = function(dateString, dayIndex) {
    console.log(`🟢 selectDay called with dateString: ${dateString}, dayIndex: ${dayIndex}`);
    
    const activeStaffCard = document.querySelector('.staff-card.active');
    
    if (!activeStaffCard) {
        console.error('❌ No active staff card found');
        alert('No active staff card found'); // Temporary debug alert
        return false;
    }
    
    console.log('✅ Active staff card found:', activeStaffCard);
    
    try {
        // Update day buttons - remove active class from all day buttons in this staff card
        const dayButtons = activeStaffCard.querySelectorAll('.day-btn');
        console.log(`🔍 Found ${dayButtons.length} day buttons`);
        
        dayButtons.forEach((btn, index) => {
            btn.classList.remove('active');
            console.log(`➖ Removed active from button ${index}`);
        });
        
        // Add active class to the clicked day button
        const targetDayBtn = activeStaffCard.querySelector(`[data-day-index="${dayIndex}"]`);
        if (targetDayBtn) {
            targetDayBtn.classList.add('active');
            console.log(`✅ Added active to button with index ${dayIndex}`);
        } else {
            console.error(`❌ Day button with index ${dayIndex} not found`);
            return false;
        }
        
        // Update day content - hide all day content in this staff card
        const dayContents = activeStaffCard.querySelectorAll('.day-content');
        console.log(`🔍 Found ${dayContents.length} day contents`);
        
        dayContents.forEach((content, index) => {
            content.classList.add('d-none');
            content.classList.remove('active');
            console.log(`👻 Hidden day content ${index}`);
        });
        
        // Show the selected day content - specifically target day-content divs, not buttons
        const targetDayContent = activeStaffCard.querySelector(`.day-content[data-date="${dateString}"]`);
        if (targetDayContent) {
            targetDayContent.classList.remove('d-none');
            targetDayContent.classList.add('active');
            targetDayContent.style.display = 'block';
            console.log(`✅ Showed day content for ${dateString}`);
            console.log('Day content element:', targetDayContent);
            console.log('Day content innerHTML length:', targetDayContent.innerHTML.length);
            console.log('Day content visible:', !targetDayContent.classList.contains('d-none'));
            
            // Verify it's actually the visible one now
            setTimeout(() => {
                const nowVisible = document.querySelector('.day-content:not(.d-none)');
                if (nowVisible) {
                    console.log(`🔍 Currently visible day content date: ${nowVisible.getAttribute('data-date')}`);
                    if (nowVisible.getAttribute('data-date') === dateString) {
                        console.log('✅ Day switch successful!');
                    } else {
                        console.log('❌ Day switch failed - wrong day visible');
                    }
                } else {
                    console.log('❌ No day content visible after switch');
                }
            }, 50);
        } else {
            console.error(`❌ Day content with date ${dateString} not found`);
            // Let's see what day content elements exist
            const allDayContents = activeStaffCard.querySelectorAll('.day-content');
            console.log('🔍 All day content elements found:');
            allDayContents.forEach((content, index) => {
                console.log(`  ${index}: data-date="${content.getAttribute('data-date')}", classes: ${content.className}`);
            });
            return false;
        }
        
        currentDayIndex = dayIndex;
        
        // Success feedback
        if (window.MobileApp && window.MobileApp.vibrate) {
            window.MobileApp.vibrate([30]);
        }
        
        console.log('🎉 Day selection completed successfully');
        return true;
        
    } catch (error) {
        console.error('💥 Error in selectDay function:', error);
        alert('Error: ' + error.message); // Temporary debug alert
        return false;
    }
};

// Switch department
function switchDepartment(department) {
    // Check for unsaved changes before switching
    if (hasUnsavedChanges) {
        MobileApp.confirm('You have unsaved changes. Continue switching departments?', () => {
            performDepartmentSwitch(department);
        });
    } else {
        performDepartmentSwitch(department);
    }
}

function performDepartmentSwitch(department) {
    console.log(`🔄 Switching to department: ${department}`);
    
    // Update tabs
    document.querySelectorAll('.mobile-tab').forEach(tab => {
        tab.classList.remove('active');
    });
    document.querySelector(`[data-department="${department}"]`).classList.add('active');
    
    // Update content
    document.querySelectorAll('.department-content').forEach(content => {
        content.classList.add('d-none');
        content.classList.remove('active');
    });
    
    const targetContent = document.getElementById(`dept-${department}`);
    targetContent.classList.remove('d-none');
    targetContent.classList.add('active');
    
    // CRITICAL: Reset navigation state for new department
    currentDepartment = department;
    currentStaffIndex = 0;
    currentDayIndex = 0;
    
    console.log(`🔧 Reset navigation state: dept=${currentDepartment}, staffIndex=${currentStaffIndex}, dayIndex=${currentDayIndex}`);
    
    // Show first staff member in the new department
    setTimeout(() => {
        showStaffMember(0);
        console.log(`📋 Department switch complete: ${department}`);
    }, 50);
    
    // Reset unsaved changes flag for new department
    hasUnsavedChanges = false;
}

// Show specific staff member
function showStaffMember(index) {
    console.log(`🟢 showStaffMember called with index: ${index}`);
    
    const activeContent = document.querySelector('.department-content.active');
    if (!activeContent) {
        console.error('❌ No active department content found');
        return;
    }
    
    const staffCards = activeContent.querySelectorAll('.staff-card');
    console.log(`📋 Found ${staffCards.length} staff cards in active department`);
    
    if (index >= staffCards.length || index < 0) {
        console.warn(`❌ Staff index ${index} out of range (0-${staffCards.length-1})`);
        return;
    }
    
    // CRITICAL: Store the target staff info BEFORE we modify anything
    const targetCard = staffCards[index];
    const targetStaffName = targetCard.querySelector('.staff-name')?.getAttribute('data-staff-name') || 'Unknown Staff';
    console.log(`🎯 Target staff: ${targetStaffName} (index ${index})`);
    
    // Hide all staff cards and show the selected one
    staffCards.forEach((card, i) => {
        if (i === index) {
            card.classList.remove('d-none');
            card.classList.add('active');
            console.log(`✅ Showing staff card ${i} (${card.querySelector('.staff-name')?.getAttribute('data-staff-name')})`);
        } else {
            card.classList.add('d-none');
            card.classList.remove('active');
            console.log(`👻 Hiding staff card ${i} (${card.querySelector('.staff-name')?.getAttribute('data-staff-name')})`);
        }
    });
    
    // DON'T modify staff headers - each staff card keeps its own original name
    // The visible staff card already has the correct name from the server-side rendering
    
    // Update staff counter - but only for the ACTIVE staff card to avoid duplicates
    const totalStaff = staffCards.length;
    const currentPosition = index + 1;
    const activeStaffCounters = targetCard.querySelectorAll('.staff-counter');
    activeStaffCounters.forEach((counter, counterIndex) => {
        console.log(`🔢 Updating counter ${counterIndex} to: ${currentPosition}/${totalStaff}`);
        counter.textContent = `${currentPosition}/${totalStaff}`;
    });
    
    // Update navigation button visibility - only for the ACTIVE staff card
    const prevButtons = targetCard.querySelectorAll('#prev-staff-btn');
    const nextButtons = targetCard.querySelectorAll('#next-staff-btn');
    
    console.log(`🔍 Found ${prevButtons.length} prev buttons and ${nextButtons.length} next buttons in active card`);
    
    prevButtons.forEach((btn, btnIndex) => {
        if (index > 0) {
            btn.style.display = 'inline-block';
            btn.classList.remove('d-none');
            console.log(`👈 Enabling prev button ${btnIndex}`);
        } else {
            btn.style.display = 'none';
            btn.classList.add('d-none');
            console.log(`👈 Disabling prev button ${btnIndex}`);
        }
    });
    
    nextButtons.forEach((btn, btnIndex) => {
        if (index < totalStaff - 1) {
            btn.style.display = 'inline-block';
            btn.classList.remove('d-none');
            console.log(`👉 Enabling next button ${btnIndex}`);
        } else {
            btn.style.display = 'none';
            btn.classList.add('d-none');
            console.log(`👉 Disabling next button ${btnIndex}`);
        }
    });
    
    console.log(`📋 Staff navigation updated: ${targetStaffName} (${currentPosition}/${totalStaff})`);
    console.log(`🔍 Navigation state: prev=${index > 0 ? 'visible' : 'hidden'}, next=${index < totalStaff - 1 ? 'visible' : 'hidden'}`);
    
    // Ensure first day is visible for the newly active staff
    const firstDayButton = targetCard.querySelector('.day-btn[data-day-index="0"], .day-btn:first-of-type');
    if (firstDayButton) {
        // Reset all day buttons in the target card
        const allDayButtons = targetCard.querySelectorAll('.day-btn');
        allDayButtons.forEach(btn => btn.classList.remove('active'));
        
        // Activate first day button
        firstDayButton.classList.add('active');
        
        // Show first day content in the target card
        const allDayContents = targetCard.querySelectorAll('.day-content');
        allDayContents.forEach((content, i) => {
            if (i === 0) {
                content.classList.remove('d-none');
                content.classList.add('active');
            } else {
                content.classList.add('d-none');
                content.classList.remove('active');
            }
        });
        
        console.log(`📅 Reset to first day for ${targetStaffName}`);
    }
    
    // CRITICAL: Update the global index AFTER everything is complete
    currentStaffIndex = index;
    currentDayIndex = 0; // Reset to first day when switching staff
    console.log(`✅ Staff member ${index} (${targetStaffName}) is now active. Global currentStaffIndex = ${currentStaffIndex}`);
}

// Navigation functions
function showNextStaff() {
    console.log('🟢 showNextStaff called, current index:', currentStaffIndex);
    
    const activeContent = document.querySelector('.department-content.active');
    if (!activeContent) {
        console.error('❌ No active department content found');
        MobileApp.showError('No active department found');
        return;
    }
    
    const staffCards = activeContent.querySelectorAll('.staff-card');
    console.log(`📋 Found ${staffCards.length} staff cards, current index: ${currentStaffIndex}`);
    
    // Debug current state before navigation
    debugNavigationState();
    
    if (currentStaffIndex < staffCards.length - 1) {
        const nextIndex = currentStaffIndex + 1;
        console.log(`➡️ Moving to next staff (index ${nextIndex})`);
        showStaffMember(nextIndex);
        MobileApp.vibrate([50]);
        
        // Verify navigation worked
        setTimeout(() => {
            console.log('📊 Post-navigation state:');
            debugNavigationState();
        }, 100);
    } else {
        console.log('❌ Already at last staff member');
        MobileApp.showError('Already at the last staff member');
    }
}

function showPreviousStaff() {
    console.log('🟢 showPreviousStaff called, current index:', currentStaffIndex);
    
    // Debug current state before navigation
    debugNavigationState();
    
    if (currentStaffIndex > 0) {
        const prevIndex = currentStaffIndex - 1;
        console.log(`⬅️ Moving to previous staff (index ${prevIndex})`);
        showStaffMember(prevIndex);
        MobileApp.vibrate([50]);
        
        // Verify navigation worked
        setTimeout(() => {
            console.log('📊 Post-navigation state:');
            debugNavigationState();
        }, 100);
    } else {
        console.log('❌ Already at first staff member');
        MobileApp.showError('Already at the first staff member');
    }
}

// Day selection function moved to top for global access

// Add time slot
function addTimeSlot(staffId, dateString) {
    console.log(`🟢 addTimeSlot called for staff ${staffId}, date ${dateString}`);
    
    // Find the active day content specifically
    const activeStaffCard = document.querySelector('.staff-card.active');
    if (!activeStaffCard) {
        console.error('❌ No active staff card found');
        MobileApp.showError('No active staff card found');
        return;
    }
    
    const dayContent = activeStaffCard.querySelector(`.day-content[data-date="${dateString}"].active`);
    if (!dayContent) {
        console.error(`❌ Day content not found for ${dateString}`);
        MobileApp.showError(`Day content not found for ${dateString}`);
        return;
    }
    
    console.log('✅ Found day content:', dayContent);
    
    const timeEntries = dayContent.querySelectorAll('.time-entry');
    console.log(`🔍 Found ${timeEntries.length} existing time entries`);
    
    if (timeEntries.length === 0) {
        console.error('❌ No time entries found to clone');
        MobileApp.showError('No time entries found to clone');
        return;
    }
    
    // Create a completely fresh regular time slot from scratch (no cloning)
    console.log('🏗️ Creating fresh regular time slot from scratch');
    
    const newEntry = document.createElement('div');
    newEntry.className = 'time-entry mb-3';
    
    // Generate unique timestamp for form names
    const timestamp = Date.now();
    
    // Create fresh HTML structure for regular time entry
    newEntry.innerHTML = `
        <div class="time-picker-group" data-time-entry="new-${timestamp}" data-staff-id="${staffId}" data-date="${dateString}">
            <!-- Hidden input for form submission -->
            <input type="hidden" name="hours[${staffId}][${dateString}][]" value="" class="time-range-input">
            
            <!-- Regular Time Entry -->
            <div class="row align-items-center">
                <div class="col">
                    <!-- Start Time -->
                    <label class="form-label small text-muted" style="color: #a8b5c8;">Start Time</label>
                    <input type="time" class="form-control form-control-mobile time-start" value="" onchange="updateTimeRange(this)">
                </div>
                <div class="col-auto px-2 mt-4">
                    <i class="fas fa-arrow-right text-muted" style="color: #a8b5c8;"></i>
                </div>
                <div class="col">
                    <!-- End Time -->
                    <label class="form-label small text-muted" style="color: #a8b5c8;">End Time</label>
                    <input type="time" class="form-control form-control-mobile time-end" value="" onchange="updateTimeRange(this)">
                </div>
                <div class="col-auto">
                    <label class="form-label small text-muted" style="color: #a8b5c8;">&nbsp;</label>
                    <div>
                        <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeTimeEntry(this)">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Quick Action Buttons -->
            <div class="mt-3">
                <div class="btn-group w-100" role="group">
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="applyQuickFill(this, 'V')" title="Vacation">V</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="applyQuickFill(this, 'X')" title="Day Off">X</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="applyQuickFill(this, 'H')" title="Holiday">H</button>
                    ${showPhoneReceptionButtons ? '<button type="button" class="btn btn-outline-success btn-sm" onclick="applyQuickFill(this, \'on_call\')" title="On Call">📞</button>' : ''}
                    ${showPhoneReceptionButtons ? '<button type="button" class="btn btn-outline-info btn-sm" onclick="applyQuickFill(this, \'reception\')" title="Reception">💻</button>' : ''}
                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="applyQuickFill(this, 'regular')" title="Regular Hours">⏰</button>
                </div>
            </div>
        </div>
    `;
    
    console.log('✅ Created fresh regular time slot with empty values and all quick action buttons');
    
    // Clean up the cloned element
    newEntry.classList.remove('unapproved'); // Remove any status classes
    newEntry.style.marginBottom = '15px'; // Ensure proper spacing
    
    // Clear values and reset to blank state (like desktop version)
    const inputs = newEntry.querySelectorAll('input, select');
    console.log(`🔧 Clearing ${inputs.length} input fields`);
    
    inputs.forEach((input, index) => {
        if (input.type === 'time') {
            input.value = '';
            input.id = ''; // Remove ID to avoid duplicates
        } else if (input.type === 'hidden') {
            // CRITICAL: Clear any inherited values (vacation, day-off, etc.)
            input.value = '';
            input.id = ''; // Remove ID to avoid duplicates
            // Remove special classes and attributes like desktop version
            input.classList.remove('sick-leave-entry');
            input.removeAttribute('data-sick-leave');
        }
        // Remove any other IDs or names that might cause conflicts
        if (input.name && input.name.includes('[')) {
            // Keep the name structure but ensure it's unique
            input.name = input.name;
        }
    });
    
    // Reset the time picker group to regular state (not special type)
    const timePickerGroup = newEntry.querySelector('.time-picker-group');
    if (timePickerGroup) {
        // Reset any special styling or content that might have been applied
        timePickerGroup.classList.remove('unapproved', 'vacation', 'day-off', 'holiday');
        
        // Ensure it shows regular time inputs, not special type display
        const regularTimeInputs = newEntry.querySelector('.row.align-items-center');
        const specialTypeDisplay = newEntry.querySelector('.special-type-display');
        
        if (regularTimeInputs) {
            regularTimeInputs.style.display = '';
        }
        if (specialTypeDisplay) {
            specialTypeDisplay.style.display = 'none';
        }
        
        // Reset button group to original state
        const buttonGroup = newEntry.querySelector('.btn-group');
        if (buttonGroup && buttonGroup.innerHTML.includes('badge')) {
            // If it has a badge (from quick action), restore original buttons
            buttonGroup.innerHTML = `
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="applyQuickFill(this, 'V')" title="Vacation">V</button>
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="applyQuickFill(this, 'X')" title="Day Off">X</button>
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="applyQuickFill(this, 'H')" title="Holiday">H</button>
                <button type="button" class="btn btn-outline-success btn-sm" onclick="applyQuickFill(this, 'on_call')" title="On Call">📞</button>
                <button type="button" class="btn btn-outline-info btn-sm" onclick="applyQuickFill(this, 'reception')" title="Reception">💻</button>
                <button type="button" class="btn btn-outline-primary btn-sm" onclick="applyQuickFill(this, 'regular')" title="Regular Hours">⏰</button>
            `;
        }
    }
    
    // Remove any IDs from other elements to avoid conflicts
    const allElements = newEntry.querySelectorAll('[id]');
    allElements.forEach(element => {
        element.id = '';
    });
    
    console.log('🔄 Reset cloned entry to blank state (like desktop version)');
    
    // Final verification - check what we actually have after cleaning
    const finalHiddenInput = newEntry.querySelector('.time-range-input');
    console.log('✅ Final hidden input value after cleaning:', finalHiddenInput ? finalHiddenInput.value : 'not found');
    console.log('✅ Final entry HTML after cleaning:', newEntry.outerHTML.substring(0, 500) + '...');
    
    // Find the correct insertion point
    const dayForm = dayContent.querySelector('.day-form');
    if (!dayForm) {
        console.error('❌ Day form not found');
        MobileApp.showError('Day form not found');
        return;
    }
    
    // Look for the add button by multiple selectors since styling may have changed
    let addButton = dayForm.querySelector('.btn-outline-success');
    if (!addButton) {
        // Try alternative selectors
        addButton = dayForm.querySelector('button[onclick*="addTimeSlot"]');
    }
    if (!addButton) {
        // Try by text content
        const buttons = dayForm.querySelectorAll('button');
        for (let btn of buttons) {
            if (btn.textContent.includes('Add Time Slot')) {
                addButton = btn;
                break;
            }
        }
    }
    
    if (!addButton) {
        console.error('❌ Add button not found after all attempts');
        console.log('Available buttons in day form:', Array.from(dayForm.querySelectorAll('button')).map(b => b.textContent.trim()));
        MobileApp.showError('Add Time Slot button not found');
        return;
    }
    
    console.log('➕ Inserting new time entry');
    console.log('Add button:', addButton);
    console.log('Add button parent:', addButton.parentNode);
    console.log('Day form container:', dayForm);
    console.log('Is add button child of day form?', dayForm.contains(addButton));
    
    // Wrap the new entry to ensure proper containment
    const wrapper = document.createElement('div');
    wrapper.className = 'time-entry-wrapper';
    wrapper.style.marginBottom = '15px';
    wrapper.style.clear = 'both';
    wrapper.appendChild(newEntry);
    
    // Insert before the Add button - use the actual parent of the add button
    const addButtonParent = addButton.parentNode;
    if (addButtonParent === dayForm) {
        // Direct child - insert directly
        dayForm.insertBefore(wrapper, addButton);
    } else {
        // Not direct child - insert before the add button's container
        const addButtonContainer = addButton.closest('.day-form > *') || addButton;
        dayForm.insertBefore(wrapper, addButtonContainer);
    }
    
    hasUnsavedChanges = true;
    MobileApp.vibrate([100]);
    
    console.log('🎉 Time slot added successfully');
}

// Save individual day
function saveDay(staffId, dateString) {
    MobileApp.confirm(`Save changes for ${dateString}?`, () => {
        MobileApp.showLoading('Saving...');
        
        // Get the active department form and submit it
        const activeDepartment = document.querySelector('.department-content.active');
        const form = activeDepartment.querySelector('.mobile-department-form');
        
        if (form) {
            // Submit the form
            form.submit();
        } else {
            MobileApp.hideLoading();
            MobileApp.showError('No form found to submit');
        }
    });
}

// Save all changes
function saveAllChanges() {
    MobileApp.confirm('Save all changes for this week?', () => {
        MobileApp.showLoading('Saving all changes...');
        
        // Get the active department form and submit it
        const activeDepartment = document.querySelector('.department-content.active');
        const form = activeDepartment.querySelector('.mobile-department-form');
        
        if (form) {
            // Submit the form
            form.submit();
        } else {
            MobileApp.hideLoading();
            MobileApp.showError('No form found to submit');
        }
    });
}

// Show week picker
function showWeekPicker() {
    // Create a simple date picker modal
    const currentWeek = '{{ $selectedDate }}';
    
    Swal.fire({
        title: 'Select Week',
        html: `<input type="date" id="week-picker" class="form-control-mobile" value="${currentWeek}">`,
        showCancelButton: true,
        confirmButtonText: 'Go to Week',
        preConfirm: () => {
            const selectedDate = document.getElementById('week-picker').value;
            if (selectedDate) {
                return selectedDate;
            }
            Swal.showValidationMessage('Please select a date');
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const url = `{{ route(Route::currentRouteName()) }}?week=${result.value}`;
            if (hasUnsavedChanges) {
                MobileApp.confirm('You have unsaved changes. Continue anyway?', () => {
                    window.location.href = url;
                });
            } else {
                window.location.href = url;
            }
        }
    });
}

// Swipe gestures for navigation
let touchStartX = 0;
let touchEndX = 0;

document.addEventListener('touchstart', function(e) {
    touchStartX = e.changedTouches[0].screenX;
});

document.addEventListener('touchend', function(e) {
    touchEndX = e.changedTouches[0].screenX;
    handleSwipe();
});

function handleSwipe() {
    const swipeThreshold = 50;
    const swipeDistance = touchEndX - touchStartX;
    
    if (Math.abs(swipeDistance) > swipeThreshold) {
        if (swipeDistance > 0) {
            // Swipe right - previous staff/day
            if (currentStaffIndex > 0) {
                showPreviousStaff();
            }
        } else {
            // Swipe left - next staff/day  
            showNextStaff();
        }
    }
}

// Prevent back navigation with unsaved changes
window.addEventListener('beforeunload', function(e) {
    if (hasUnsavedChanges) {
        e.preventDefault();
        e.returnValue = '';
    }
});

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, initializing mobile staff navigation...');
    
    // Ensure proper initial state
    currentStaffIndex = 0;
    currentDayIndex = 0;
    
    console.log('🔧 Initial state set:', {
        currentDepartment,
        currentStaffIndex,
        currentDayIndex
    });
    
    // Initialize staff navigation - ensure first staff is shown
    setTimeout(() => {
        console.log('🚀 Initializing first staff member...');
        showStaffMember(0);
        
        // Verify initialization
        setTimeout(() => {
            console.log('✅ Initialization verification:');
            debugNavigationState();
        }, 200);
    }, 100);
    
    // Direct event listeners for day buttons
    const dayButtons = document.querySelectorAll('.day-btn');
    console.log(`Found ${dayButtons.length} day buttons to initialize`);
    
    dayButtons.forEach((button, index) => {
        console.log(`Adding click handler to button ${index}`);
        button.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const dateString = this.getAttribute('data-date');
            const dayIndex = parseInt(this.getAttribute('data-day-index'));
            
            console.log(`Day button ${index} clicked:`, dateString, dayIndex);
            selectDay(dateString, dayIndex);
        });
    });
    
    // Initialize staff navigation buttons
    const nextStaffButtons = document.querySelectorAll('#next-staff-btn');
    const prevStaffButtons = document.querySelectorAll('#prev-staff-btn');
    
    console.log(`🔘 Found ${nextStaffButtons.length} next buttons and ${prevStaffButtons.length} prev buttons to initialize`);
    
    nextStaffButtons.forEach((btn, index) => {
        console.log(`🔘 Adding event listener to next button ${index}`);
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log(`🟢 Next staff button ${index} clicked - currentStaffIndex before: ${currentStaffIndex}`);
            debugNavigationState();
            showNextStaff();
        });
    });
    
    prevStaffButtons.forEach((btn, index) => {
        console.log(`🔘 Adding event listener to prev button ${index}`);
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log(`🟢 Previous staff button ${index} clicked - currentStaffIndex before: ${currentStaffIndex}`);
            debugNavigationState();
            showPreviousStaff();
        });
    });
    
    console.log(`Initialized ${nextStaffButtons.length} next buttons and ${prevStaffButtons.length} prev buttons`);
    
    // Auto-dismiss alerts
    setTimeout(() => {
        document.querySelectorAll('.alert').forEach(alert => {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        });
    }, 3000);
    
    console.log('Day button initialization completed');
    
    // Debug initial state
    console.log('🔍 Checking initial day content visibility...');
    setTimeout(() => {
        debugCurrentState();
        
        // Force initialize first day if nothing is visible
        const visibleDayContent = document.querySelector('.day-content:not(.d-none)');
        if (!visibleDayContent) {
            console.log('🔧 No visible content found, forcing initialization of first day...');
            
            // Manually force the first day content to be visible
            const activeStaffCard = document.querySelector('.staff-card.active');
            if (activeStaffCard) {
                const firstDayContent = activeStaffCard.querySelector('.day-content[data-day-index="0"], .day-content:first-of-type');
                if (firstDayContent) {
                    console.log('🔧 Found first day content, making it visible...');
                    firstDayContent.classList.remove('d-none');
                    firstDayContent.classList.add('active');
                    firstDayContent.style.display = 'block';
                    
                    // Also activate the first day button
                    const firstDayButton = activeStaffCard.querySelector('.day-btn[data-day-index="0"], .day-btn:first-of-type');
                    if (firstDayButton) {
                        firstDayButton.classList.add('active');
                    }
                    
                    console.log('🔧 Manual initialization complete');
                } else {
                    console.error('🔧 Could not find first day content element');
                }
            } else {
                console.error('🔧 Could not find active staff card');
            }
        }
    }, 100);
});
</script>
@endpush