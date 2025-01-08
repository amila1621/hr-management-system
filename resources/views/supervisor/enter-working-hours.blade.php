@extends('partials.main')


    
<!-- Add in the head section -->
<link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@section('content')
    <div class="content-page">
        <div class="content">
            <div class="container-fluid">
                <div class="page-title-box">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h4 class="page-title">Enter Working Hours</h4>
                        </div>
                    </div>
                </div>

                @if (session()->has('error'))
                    <div class="alert alert-danger">
                        {{ session()->get('error') }}
                    </div>
                @endif

                @if (session()->has('success'))
                    <div class="alert alert-success">
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

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <form action="{{ route('supervisor.working-hours.store') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="week" value="{{ $selectedDate }}">
                                    <div class="col-md-12 mb-3">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div class="btn-group">
                                                <a href="{{ route('supervisor.enter-working-hours', ['week' => Carbon\Carbon::parse($selectedDate)->subWeek()->format('Y-m-d')]) }}" 
                                                   class="btn btn-outline-primary">
                                                    <i class="fas fa-chevron-left"></i> Previous Week
                                                </a>
                                                <a href="{{ route('supervisor.enter-working-hours', ['week' => Carbon\Carbon::parse($selectedDate)->addWeek()->format('Y-m-d')]) }}" 
                                                   class="btn btn-outline-primary">
                                                    Next Week <i class="fas fa-chevron-right"></i>
                                                </a>
                                            </div>
                                            <div id="weekRange" class="text-center">
                                                <strong>Week: {{ Carbon\Carbon::parse($selectedDate)->startOfWeek()->format('d M Y') }} - 
                                                          {{ Carbon\Carbon::parse($selectedDate)->endOfWeek()->format('d M Y') }}</strong>
                                            </div>
                                            <input type="date" name="week" id="week" class="form-control" 
                                                   style="width: 200px;" 
                                                   value="{{ $selectedDate }}" 
                                                   onchange="window.location.href='{{ route('supervisor.enter-working-hours') }}?week=' + this.value">
                                        </div>
                                    </div>
                                    
                                    <div class="table-responsive-container" style="-webkit-overflow-scrolling: touch;">
                                        <div class="table-responsive">
                                            <table id="workingHoursTable" class="table table-striped table-bordered" style="border-collapse: collapse; border-spacing: 0; width: 100%">
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
                                                    <!-- Reception row -->
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
                                                   
                                                    <!-- Existing staff rows -->
                                                    @foreach($staffMembers as $staff)
                                                        <tr data-staff-id="{{ $staff->id }}">
                                                            <td>{{ $staff->name }}</td>
                                                            @foreach($dates as $date)
                                                                @php
                                                                    $dateString = $date->format('Y-m-d');
                                                                    $hoursData = $staffHours[$staff->id][$dateString] ?? [];
                                                                @endphp
                                                                <td class="{{ in_array($dateString, $holidays->toArray()) ? '' : '' }}">
                                                                    <div class="time-slots">
                                                                        @foreach($hoursData as $index => $timeRange)
                                                                            <div class="time-slot mb-1">
                                                                                <input type="text" 
                                                                                       name="hours[{{ $staff->id }}][{{ $dateString }}][]" 
                                                                                       class="form-control form-control-sm time-range" 
                                                                                       value="{{ isset($timeRange['type']) ? $timeRange['type'] : $timeRange['start_time'] . '-' . $timeRange['end_time'] }}"
                                                                                       placeholder="HH:MM-HH:MM"
                                                                                       pattern="([01]?[0-9]|2[0-3]):[0-5][0-9]-([01]?[0-9]|2[0-3]):[0-5][0-9]|^[VX]$|^SL$"
                                                                                       title="Please enter time in HH:MM-HH:MM format, or V, X, or SL"
                                                                                       style="width: 120px; display: inline-block;">
                                                                                <button type="button" class="btn btn-sm btn-secondary quick-fill" data-value="V">V</button>
                                                                                <button type="button" class="btn btn-sm btn-secondary quick-fill" data-value="X">X</button>
                                                                                <button type="button" class="btn btn-sm btn-warning quick-fill" data-value="SL">SL</button>
                                                                            </div>
                                                                        @endforeach
                                                                        @if(empty($hoursData))
                                                                            <div class="time-slot mb-1">
                                                                                <input type="text" 
                                                                                       name="hours[{{ $staff->id }}][{{ $dateString }}][]" 
                                                                                       class="form-control form-control-sm time-range" 
                                                                                       placeholder="HH:MM-HH:MM"
                                                                                       pattern="([01]?[0-9]|2[0-3]):[0-5][0-9]-([01]?[0-9]|2[0-3]):[0-5][0-9]|^[VX]$|^SL$"
                                                                                       title="Please enter time in HH:MM-HH:MM format, or V, X, or SL"
                                                                                       style="width: 120px; display: inline-block;">
                                                                                <button type="button" class="btn btn-sm btn-secondary quick-fill" data-value="V">V</button>
                                                                                <button type="button" class="btn btn-sm btn-secondary quick-fill" data-value="X">X</button>
                                                                                <button type="button" class="btn btn-sm btn-warning quick-fill" data-value="SL">SL</button>
                                                                            </div>
                                                                        @endif
                                                                    </div>
                                                                </td>
                                                            @endforeach
                                                        </tr>
                                                    @endforeach
                                                    
                                                     <!-- Midnight Phone row -->
                                                     @if($displayMidnightPhone == 1)
                                                    <tr>
                                                        <td>Midnight Phone</td>
                                                        @foreach($dates as $date)
                                                            @php $dateString = $date->format('Y-m-d'); @endphp
                                                            <td class="{{ in_array($dateString, $holidays->toArray()) ? '' : '' }}">
                                                                <select name="midnight_phone[{{ $dateString }}]" class="form-control form-control-sm">
                                                                    <option value="">Select Staff</option>
                                                                    @foreach($staffMembers as $staff)
                                                                        <option value="{{ $staff->id }}" 
                                                                            {{ ($midnightPhoneData[$dateString] ?? '') == $staff->id ? 'selected' : '' }}>
                                                                            {{ $staff->name }}
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
                                    </div>

                                    <div class="mt-3">
                                        <button type="submit" class="btn btn-primary waves-effect waves-light">Submit Working Hours</button>
                                    </div>
                                </form>
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
    const weekInput = document.getElementById('week');
    const workingHoursTable = document.getElementById('workingHoursTable');
    const container = document.querySelector('.table-responsive-container');
    let hasUnsavedChanges = false;

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
                hasUnsavedChanges = false; // Reset the flag before navigating
                window.location.href = url;
            }
        } else {
            window.location.href = url;
        }
    }

    // Update week input handler
    weekInput.addEventListener('change', function() {
        let url = new URL(window.location.href);
        url.searchParams.set('week', this.value);
        handleNavigation(url.toString());
    });

    // Update navigation buttons
    document.querySelectorAll('.btn-group a').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            handleNavigation(this.href);
        });
    });

    // Track changes in the form
    function markAsUnsaved() {
        hasUnsavedChanges = true;
    }

    // Add change listeners to all form inputs
    document.querySelectorAll('input, select').forEach(input => {
        input.addEventListener('change', markAsUnsaved);
        input.addEventListener('keyup', markAsUnsaved);
    });

    // Reset unsaved changes flag after successful form submission
    document.querySelector('form').addEventListener('submit', () => {
        hasUnsavedChanges = false;
    });

    function updateButtons(timeSlots) {
        timeSlots.forEach((slot, index) => {
            // Remove existing add/remove buttons
            slot.querySelectorAll('.add-time-slot, .remove-time-slot').forEach(btn => btn.remove());

            // Add remove button to all except the first
            if (index > 0) {
                const removeButton = document.createElement('button');
                removeButton.type = 'button';
                removeButton.className = 'btn btn-sm btn-danger remove-time-slot';
                removeButton.textContent = '-';
                slot.appendChild(removeButton);
            }

            // Add add button only to the last
            if (index === timeSlots.length - 1) {
                const addButton = document.createElement('button');
                addButton.type = 'button';
                addButton.className = 'btn btn-sm btn-primary add-time-slot';
                addButton.textContent = '+';
                slot.appendChild(addButton);
            }
        });
    }

    workingHoursTable.addEventListener('click', function(e) {
        if (e.target.classList.contains('add-time-slot')) {
            const timeSlotDiv = e.target.closest('.time-slot');
            const parentDiv = timeSlotDiv.parentNode;
            const newTimeSlotDiv = timeSlotDiv.cloneNode(true);
            const input = newTimeSlotDiv.querySelector('input');
            input.value = '';
            
            parentDiv.insertBefore(newTimeSlotDiv, timeSlotDiv.nextSibling);
            updateButtons(parentDiv.querySelectorAll('.time-slot'));
            markAsUnsaved();
        } else if (e.target.classList.contains('remove-time-slot')) {
            const timeSlotDiv = e.target.closest('.time-slot');
            const parentDiv = timeSlotDiv.parentNode;
            parentDiv.removeChild(timeSlotDiv);
            updateButtons(parentDiv.querySelectorAll('.time-slot'));
            markAsUnsaved();
        } else if (e.target.classList.contains('quick-fill')) {
            const input = e.target.closest('.time-slot').querySelector('input');
            input.value = e.target.dataset.value;
            markAsUnsaved();
        }
    });

    workingHoursTable.addEventListener('blur', function(e) {
        if (e.target.classList.contains('time-range')) {
            const value = e.target.value.trim();
            if (value && !/^([01]?[0-9]|2[0-3]):[0-5][0-9]-([01]?[0-9]|2[0-3]):[0-5][0-9]$|^[VX]$|^SL$/.test(value)) {
                alert('Please enter time in HH:MM-HH:MM format, or V, X, or SL');
                e.target.value = '';
            }
        }
    }, true);

    // Initialize buttons
    document.querySelectorAll('.time-slots').forEach(slots => {
        updateButtons(slots.querySelectorAll('.time-slot'));
    });

    // Force scrollbars visible functionality
    function forceScrollbarsVisible() {
        const currentScroll = container.scrollTop;
        container.scrollTop = currentScroll + 1;
        container.scrollTop = currentScroll;
    }

    // Run initially
    forceScrollbarsVisible();

    // Run periodically
    setInterval(forceScrollbarsVisible, 2000);

    // Run on hover
    container.addEventListener('mouseenter', forceScrollbarsVisible);

    // Run on scroll end
    let scrollTimeout;
    container.addEventListener('scroll', function() {
        clearTimeout(scrollTimeout);
        scrollTimeout = setTimeout(forceScrollbarsVisible, 150);
    });

    // Drag scrolling functionality
    let isDown = false;
    let startX;
    let startY;
    let scrollLeft;
    let scrollTop;

    container.addEventListener('mousedown', (e) => {
        isDown = true;
        container.style.cursor = 'grabbing';
        startX = e.pageX - container.offsetLeft;
        startY = e.pageY - container.offsetTop;
        scrollLeft = container.scrollLeft;
        scrollTop = container.scrollTop;
    });

    container.addEventListener('mouseleave', () => {
        isDown = false;
        container.style.cursor = 'grab';
    });

    container.addEventListener('mouseup', () => {
        isDown = false;
        container.style.cursor = 'grab';
    });

    container.addEventListener('mousemove', (e) => {
        if (!isDown) return;
        e.preventDefault();
        const x = e.pageX - container.offsetLeft;
        const y = e.pageY - container.offsetTop;
        const walkX = (x - startX) * 1.5; // Adjust scrolling speed
        const walkY = (y - startY) * 1.5; // Adjust scrolling speed
        container.scrollLeft = scrollLeft - walkX;
        container.scrollTop = scrollTop - walkY;
    });

    // Add initial grab cursor
    container.style.cursor = 'grab';
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
        overflow-x: auto; /* Add horizontal scrolling if needed */
    }
    .table-responsive-container {
        max-height: calc(100vh - 350px);
        overflow-y: scroll;
        overflow-x: scroll;
        margin-bottom: 20px;
        scrollbar-width: thin;
        -webkit-overflow-scrolling: touch;
        &::-webkit-scrollbar {
            -webkit-appearance: none;
            display: block;
        }
        user-select: none; /* Prevents text selection while dragging */
        cursor: grab;
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
    #workingHoursTable {
        min-width: 100%;
        font-size: 0.75rem;
    }
    #workingHoursTable thead th {
        position: sticky;
        top: 0;
        z-index: 1;
    }
    #workingHoursTable tbody td:first-child,
    #workingHoursTable thead th:first-child {
        position: sticky;
        left: 0;
        z-index: 2;
    }
    #workingHoursTable thead th:first-child {
        z-index: 3;
    }
    #workingHoursTable input,
    #workingHoursTable .btn {
        font-size: 0.75rem;
        padding: 0.2rem 0.5rem;
    }
    /* Navigation styles */
    .btn-group {
        margin: 1rem 0;
    }
    .btn-group .btn {
        padding: 0.5rem 1rem;
    }
    #weekRange {
        
        margin: 1rem 0;
        font-weight: 500;
    }
    /* Highlight styles */
    .bg-light {
        background-color: #f8f9fa !important;
    }
    .bg-primary {
        background-color: #007bff !important;
    }
    .text-white {
        color: #fff !important;
    }
    /* Time slot styles */
    .time-slot {
        display: flex;
        align-items: center;
        gap: 0.25rem;
    }
    .time-slot .form-control {
        flex: 1;
    }
    .time-slot .btn {
        flex-shrink: 0;
    }
    /* Responsive adjustments */
    @media (min-width: 768px) {
        body:not(.enlarged) .content-page {
            margin-left: 240px;
        }
    }
    body.enlarged .content-page {
        margin-left: 0;
    }
    body.enlarged .content {
        overflow-x: auto;
    }
    .holiday-column {
        background-color: #dc3545 !important;
        color: #856404;
    }

    #workingHoursTable tbody td.holiday-column {
        background-color: #dc3545 !important;
    }

    #workingHoursTable thead th.holiday-column {
        background-color: #dc3545 !important;
    }
</style>

<style>
.table-responsive-container::-webkit-scrollbar {
    height: 8px;
    width: 8px;
    display: block;
}

.table-responsive-container::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
    display: block;
}

.table-responsive-container::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 4px;
    display: block;
}

.table-responsive-container::-webkit-scrollbar-thumb:hover {
    background: #555;
}

/* For Firefox */
.table-responsive-container {
    scrollbar-width: thin !important;
    scrollbar-color: #888 #f1f1f1 !important;
}
</style>

<style>
    .quick-fill[data-value="SL"] {
        background-color: #ffc107;
        border-color: #ffc107;
        color: #000;
    }
    
    .quick-fill[data-value="SL"]:hover {
        background-color: #e0a800;
        border-color: #d39e00;
    }
</style>
