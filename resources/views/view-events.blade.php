@extends('partials.main')
<!-- SweetAlert2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">

<!-- SweetAlert2 JS -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@section('content')
    <style>
        /* Remove existing style that might be causing issues */
        :root {
            --scrollbar-always-visible: false;
        }
        
        /* Base styles for scrolling and fonts */
        body {
            overflow-y: auto !important;
            font-size: 14px;
            -webkit-overflow-scrolling: touch;
        }

        /* Table styles */
        .table {
            font-size: 13px;
        }

        .table td, .table th {
            padding: 0.5rem;
        }

        /* Button adjustments */
        .btn {
            font-size: 12px;
            padding: 0.3rem 0.6rem;
        }

        .btn-group .btn {
            padding: 0.3rem 0.5rem;
        }

        /* Content area fixes */
        .content-page {
            overflow-y: auto !important;
            height: auto !important;
            min-height: calc(100vh - 60px);
            margin-left: 240px;
            padding-bottom: 60px;
        }

        /* Sidebar adjustments */
        .left.side-menu {
            overflow: visible !important;
        }

        .slimscroll-menu {
            overflow-y: auto !important;
            height: calc(100vh - 60px) !important;
            position: fixed;
        }

        /* Card and modal adjustments */
        .card-body {
            font-size: 13px;
            padding: 1rem;
        }

        .modal-body {
            font-size: 13px;
        }

        /* Form control sizes */
        .form-control {
            font-size: 13px;
            padding: 0.375rem 0.75rem;
        }

        /* Header size adjustments */
        h4.page-title {
            font-size: 1.1rem;
        }

        h4.header-title {
            font-size: 1rem;
        }

        h5 {
            font-size: 0.9rem;
        }

        /* Breadcrumb adjustments */
        .breadcrumb {
            font-size: 12px;
            padding: 0.5rem 1rem;
        }

        /* Table button group spacing */
        .btn-group {
            gap: 2px;
        }

        /* Modal adjustments */
        .modal-header {
            padding: 0.75rem 1rem;
        }

        .modal-title {
            font-size: 1rem;
        }

        /* Remove any fixed positioning that might affect scrolling */
        #wrapper {
            height: auto !important;
            min-height: 100vh;
            position: relative;
        }

        .content {
            overflow: visible !important;
        }

        #eventDescription b {
            font-weight: bold !important;
        }
        
        #eventDescription {
            font-family: Arial, sans-serif;
            line-height: 1.5;
        }
        
        #eventDescription a {
            color: #007bff;
            text-decoration: underline;
        }
        
        #eventDescription br {
            display: block;
            margin: 5px 0;
        }

        
        #aiCalculationModal .form-control[readonly] {
            font-weight: bold;
        }
        
        #aiCalculationModal .btn {
            padding: 0.5rem 1rem;
        }
        
        #aiCalculationModal .modal-title {
            font-weight: 600;
        }

        #aiCalculationModal {
            max-height: 90vh;
            overflow-y: auto;
        }

        #eventDescription {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            height: 100%;
        }

        .card.h-100 {
            height: 100% !important;
            display: flex;
            flex-direction: column;
        }

        .card.h-100 .card-body {
            flex: 1;
            min-height: 400px; /* Minimum height fallback */
        }

        /* Custom scrollbar styles */
        .modal-body::-webkit-scrollbar {
            width: 8px;
        }

        .modal-body::-webkit-scrollbar-track {
            background: #4a4a4a;
        }

        .modal-body::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }

        .modal-body::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        /* For Firefox */
        .modal-body {
            scrollbar-width: thin;
            scrollbar-color: #888 #4a4a4a;
        }
    </style>
    <div class="content-page">
        <!-- Start content -->
        <div class="content">

            <div class="container-fluid">
                <div class="page-title-box">

                    <div class="row align-items-center ">
                        <div class="col-md-8">
                            <div class="page-title-box">
                                <h4 class="page-title">Manage Events</h4>
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item">
                                        <a href="javascript:void(0);">Home</a>
                                    </li>
                                    <li class="breadcrumb-item">
                                        <a href="javascript:void(0);">Events</a>
                                    </li>
                                    <li class="breadcrumb-item active">View Events</li>
                                </ol>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <a class="btn btn-primary w-100" href="{{route('calculate-all')}}">Calculate All</a>
                        </div>

                    </div>
                </div>
                <!-- end page-title -->

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                @if (session('error'))
                                    <div class="alert alert-danger">
                                        {{ session('error') }}
                                    </div>
                                @endif

                                @if (session()->has('failed'))
                                <div class="alert alert-danger">
                                    {{ session()->get('failed') }}
                                </div>
                            @endif
            
                            @if (session()->has('success'))
                                <div class="alert alert-success">
                                    {{ session()->get('success') }}
                                </div>
                            @endif
                                <h4 class="mt-0 header-title">Latest Events</h4>

                                <div class="table-responsive">
                                    <table id="datatable-buttons"
                                        class="table table-striped table-bordered"
                                        style="border-collapse: collapse; border-spacing: 0; width: 100%">
                                        <thead>
                                            <tr>
                                                <th>Title</th>
                                                <th>Start Date</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($eventes as $evente)
                                                <tr>
                                                    <td>{{ $evente->name }}</td>
                                                    <td>{{ \Carbon\Carbon::parse($evente->start_time)->format('d.m.Y') }}</td>
                                                    <td> 
                                                        @if($evente->status != 1)
                                                            <div class="btn-group" role="group">
                                                                <a class="btn btn-secondary" style="color:aquamarine" href="/event-salary/{{ $evente->id }}">Calculate Hours</a>
                                                                <button class="btn btn-info" 
                                                                        data-event-id="{{ $evente->id }}" 
                                                                        onclick="calculateWithAI(this)">Calculate with AI</button>
                                                                <button class="btn btn-warning btn-sm" onclick="ignoreEvent('{{ $evente->id }}')">Ignore</button>
                                                                <button class="btn btn-primary btn-sm" onclick="openManualEntryModal('{{ $evente->id }}')">Add Manually</button>
                                                            </div>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                            </div>

                        </div>
                    </div>
                </div>
                <!-- end col -->
            </div>

        </div>
        <!-- container-fluid -->

    </div>
    <!-- content -->

    <!-- Manual Entry Modal -->
    <div class="modal fade" id="manualEntryModal" tabindex="-1" role="dialog" aria-labelledby="manualEntryModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="manualEntryModalLabel">Add Guides Manually</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="manualEntryForm" action="{{ route('salary.manual-calculation') }}" method="POST">
                        @csrf
                        <input type="hidden" name="eventId" id="manualEntryEventId">
                        <div id="guideFieldsContainer">
                            <!-- Guide entries will be added here dynamically -->
                        </div>
                        <button type="button" class="btn btn-secondary" onclick="addGuideEntry()">Add Another Guide</button>
                        <button type="submit" class="btn btn-primary">Submit</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Flatpickr CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

    <!-- Add Flatpickr JS -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

    <script>
        function ignoreEvent(eventId) {
            if (confirm('Are you sure you want to ignore this event?')) {
                // Send an AJAX request to the backend controller
                $.ajax({
                    url: '/events/ignore',  // The URL for the request
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}', // Include CSRF token for security
                        eventId: eventId
                    },
                    success: function(response) {
                        location.reload();
                    },
                    error: function(xhr, status, error) {
                        alert('An error occurred while ignoring the event.');
                        console.error(xhr.responseText);
                    }
                });
            }
        }

        function openManualEntryModal(eventId) {
            document.getElementById('manualEntryEventId').value = eventId;
            document.getElementById('guideFieldsContainer').innerHTML = ''; // Clear existing entries
            addGuideEntry(); // Add the first guide entry
            $('#manualEntryModal').modal('show');
        }

        function addGuideEntry() {
            var guideFieldsContainer = document.getElementById('guideFieldsContainer');
            var guideCount = guideFieldsContainer.getElementsByClassName('guide-entry').length;
            var newGuideEntry = `
                <div class="card mb-3 guide-entry">
                    <div class="card-body">
                        <div class="form-group">
                            <label for="guideName">Select Guide</label>
                            <select name="guides[${guideCount}][name]" class="form-control" required>
                                <option value="" disabled selected>Select Guide</option>
                                @foreach($guides as $guide)
                                    <option value="{{ $guide->id }}">{{ $guide->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="startTime">Start Time</label>
                            <input type="text" name="guides[${guideCount}][startTime]" class="form-control flatpickr-datetime" required>
                        </div>
                        <div class="form-group">
                            <label for="endTime">End Time</label>
                            <input type="text" name="guides[${guideCount}][endTime]" class="form-control flatpickr-datetime" required>
                        </div>
                    </div>
                </div>
            `;
            guideFieldsContainer.insertAdjacentHTML('beforeend', newGuideEntry);
            initFlatpickr();
        }

        function initFlatpickr() {
            flatpickr(".flatpickr-datetime", {
                enableTime: true,
                dateFormat: "Y-m-d H:i",
                time_24hr: true
            });
        }

        // Initialize Flatpickr for existing inputs when the page loads
        document.addEventListener('DOMContentLoaded', function() {
            initFlatpickr();
        });

        function calculateWithAI(element) {
            const eventId = $(element).data('event-id');
            // Get the event name from the table row
            const eventName = $(element).closest('tr').find('td:first').text();
            
            Swal.fire({
                title: 'Processing',
                text: 'Analyzing event details...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            $.ajax({
                url: '{{ route("ai.analyze-event") }}',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    event_id: eventId,
                },
                success: function(response) {
                    Swal.close();
                    response.data.event_info = response.data.event_info || {};
                    // Add the event name to the response data
                    response.data.event_info.name = eventName;
                    
                    // First populate the modal
                    populateAIModal(response.data, eventId);
                    
                    // Show the modal and ensure content is set after modal is shown
                    $('#aiCalculationModal').modal('show').on('shown.bs.modal', function () {
                        $('#eventDescription').html(response.data.original_description || 'No description available');
                    });
                },
                error: function(xhr) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Failed to analyze event details'
                    });
                }
            });
        }

        function confirmAICalculation() {
            // Disable the button and show loading state
            const confirmButton = $('.modal-footer .btn-primary');
            confirmButton.prop('disabled', true);
            confirmButton.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...');
            
            // Get the stored eventId
            const eventId = $('#aiCalculationModal').data('eventId');
            
            // Debug log
            console.log('Event ID:', eventId);
            
            let formData = new FormData();
            formData.append('event_id', eventId);
            formData.append('office_time', $('#officeTime').val());

            // Process guides
            $('.guide-entry').each(function() {
                const name = $(this).find('.guide-name').val();
                const pickupTime = $(this).find('input[name$="[pickup_time]"]').val();
                const pickupLocation = $(this).find('input[name$="[pickup_location]"]').val();
                
                formData.append(`guides[${name}][pickup_time]`, pickupTime);
                formData.append(`guides[${name}][pickup_location]`, pickupLocation);
            });

            // Process helpers
            $('.helper-entry input[name="helpers[]"]').each(function(index) {
                const helperName = $(this).val().trim();
                if (helperName) {
                    formData.append(`helpers[${index}]`, helperName);
                }
            });

            $.ajax({
                url: '{{ route("salary.calculate-from-ai") }}',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                success: function(response) {
                    $('#aiCalculationModal').modal('hide');
                    
                    // Reset button state (although modal will be hidden)
                    confirmButton.prop('disabled', false);
                    confirmButton.html('Confirm and Calculate');
                    
                    // Check if there are any errors in the response
                    if (response.errors || response.error) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Partial Success',
                            text: response.errors || response.error || 'Some calculations could not be completed',
                            confirmButtonText: 'OK'
                        });
                    } else {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: 'Calculation completed successfully',
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            window.location.reload();
                        });
                    }
                },
                error: function(xhr) {
                    // Reset button state
                    confirmButton.prop('disabled', false);
                    confirmButton.html('Confirm and Calculate');
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Failed to process calculation'
                    });
                }
            });
        }
    </script>

    <!-- Add this modal before the end of content section -->
    <div class="modal fade" id="aiCalculationModal" tabindex="-1" role="dialog" aria-labelledby="aiCalculationModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="aiCalculationModalLabel">AI Analysis Results</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <!-- Top action buttons (existing) -->
                    <div class="row mb-4">
                        <div class="col-12 text-right">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary" onclick="confirmAICalculation()">
                                <i class="fas fa-check mr-1"></i> Confirm and Calculate
                            </button>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Left side - existing form -->
                        <div class="col-md-6" style="max-height: 60vh; overflow-y: auto; overflow-x: hidden; padding-right: 15px;">
                            <form id="aiCalculationForm">
                                <!-- Event Info Section -->
                                <div class="card mb-3">
                                    <div class="card-header">
                                        <h6 class="mb-0">Event Information</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label><strong>Event Name</strong></label>
                                                    <input type="text" class="form-control" id="eventName" readonly>
                                                </div>
                                                <div class="form-group">
                                                    <label>Date</label>
                                                    <input type="text" class="form-control" name="event_info[date]" id="eventDate">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label>Office Time</label>
                                            <input type="text" class="form-control" name="office_time" id="officeTime">
                                        </div>
                                    </div>
                                </div>

                                <!-- Guides Section -->
                                <div class="card mb-3">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0">Guides</h6>
                                        <button type="button" class="btn btn-sm btn-primary" onclick="addGuideEntry()">Add Guide</button>
                                    </div>
                                    <div class="card-body">
                                        <div id="guidesContainer">
                                            <!-- Guide entries will be added here dynamically -->
                                        </div>
                                    </div>
                                </div>

                                <!-- Helpers Section -->
                                <div class="card mb-3">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0">Helpers</h6>
                                        <button type="button" class="btn btn-sm btn-primary" onclick="addHelperEntry()">Add Helper</button>
                                    </div>
                                    <div class="card-body">
                                        <div id="helpersContainer">
                                            <!-- Helper entries will be added here dynamically -->
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Right side - event description -->
                        <div class="col-md-6" style="height: 60vh; overflow-y: auto;">
                            <div class="card h-100">
                                <div class="card-header">
                                    <h6 class="mb-0">Event Description</h6>
                                </div>
                                <div class="card-body">
                                    <div id="eventDescription" style="white-space: pre-wrap;">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bottom action buttons (new) -->
                    <div class="row mt-4">
                        <div class="col-12 text-right">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary" onclick="confirmAICalculation()">
                                <i class="fas fa-check mr-1"></i> Confirm and Calculate
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add these JavaScript functions -->
    <script>
    let currentEventId;

    function populateAIModal(data, eventId) {
        currentEventId = eventId;
        
        // Populate event name
        $('#eventName').val(data.event_info.name || 'N/A');
        
        // Populate event info
        $('#eventDate').val(data.event_info.date);
        $('#officeTime').val(data.office_time)
                       .trigger('change');
        
        // Populate guides
        $('#guidesContainer').empty();
        if (data.guides) {
            Object.entries(data.guides).forEach(([name, details]) => {
                addGuideEntry(name, details);
                // Trigger pickup duration calculation for each guide's location
                if (details.pickup_location) {
                    const lastGuideEntry = $('#guidesContainer .guide-entry:last');
                    const locationInput = lastGuideEntry.find('.pickup-location');
                    calculatePickupDuration(locationInput);
                }
            });
        }

        // Populate helpers
        $('#helpersContainer').empty();
        if (data.helpers && data.helpers.length > 0) {
            data.helpers.forEach(helper => {
                const helperName = typeof helper === 'string' ? helper : (helper.name || '');
                addHelperEntry(helperName);
            });
        }
    }

    function addGuideEntry(name = '', details = {}) {
        const guideHtml = `
            <div class="guide-entry border rounded p-3 mb-3">
                <div class="d-flex justify-content-between mb-2">
                    <h6>Guide Details</h6>
                    <button type="button" class="btn btn-sm btn-danger" onclick="$(this).closest('.guide-entry').remove()">Remove</button>
                </div>
                <input type="hidden" class="guide-data" name="guides[${name}]" value="">
                <div class="form-group">
                    <label>Name</label>
                    <input type="text" class="form-control guide-name" value="${name}" onchange="updateGuideName(this)">
                </div>
                <div class="form-group">
                    <label>Pickup Time</label>
                    <input type="text" class="form-control" name="guides[${name}][pickup_time]" 
                           value="${details.pickup_time || ''}" onchange="calculatePickupDuration(this)">
                    <div class="calculated-time mt-1" style="display: none;">
                        <small class="text-muted">
                            Actual pickup time: <span class="pickup-time-value"></span><br>
                            Calculated pickup duration: <span class="duration-value"></span> minutes
                        </small>
                    </div>
                </div>
                <div class="form-group">
                    <label>Pickup Location</label>
                    <input type="text" class="form-control pickup-location" name="guides[${name}][pickup_location]" 
                           value="${details.pickup_location || ''}" onchange="calculatePickupDuration(this)">
                </div>
            </div>
        `;
        $('#guidesContainer').append(guideHtml);
    }

    function updateGuideName(input) {
        const guideEntry = $(input).closest('.guide-entry');
        const newName = $(input).val();
        const oldName = guideEntry.find('.guide-data').attr('name').match(/guides\[(.*?)\]/)[1];
        
        // Update all input names in this guide entry
        guideEntry.find('input[name^="guides["]').each(function() {
            const currentName = $(this).attr('name');
            $(this).attr('name', currentName.replace(`guides[${oldName}]`, `guides[${newName}]`));
        });
    }

    function addHelperEntry(helper = '') {
        const helperHtml = `
            <div class="helper-entry border rounded p-3 mb-3">
                <div class="d-flex justify-content-between mb-2">
                    <h6>Helper Details</h6>
                    <button type="button" class="btn btn-sm btn-danger" onclick="$(this).closest('.helper-entry').remove()">Remove</button>
                </div>
                <div class="form-group">
                    <label>Name</label>
                    <input type="text" class="form-control" name="helpers[]" value="${helper}" required>
                </div>
            </div>
        `;
        $('#helpersContainer').append(helperHtml);
    }

    function calculatePickupDuration(input) {
        const guideEntry = $(input).closest('.guide-entry');
        const location = guideEntry.find('.pickup-location').val();
        const calculatedTimeDiv = guideEntry.find('.calculated-time');
        const officeTime = $('#officeTime').val();
        const pickupTime = guideEntry.find('input[name$="[pickup_time]"]').val();
        const isOfficePickup = location.toLowerCase().includes('office');
        
        if (!location || !officeTime || !pickupTime) {
            calculatedTimeDiv.hide();
            return;
        }

        $.ajax({
            url: '{{ route("calculate.pickup.duration") }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                location: location
            },
            success: function(response) {
                const officeMoment = moment(officeTime, 'HH:mm');
                const pickupMoment = moment(pickupTime, 'HH:mm');
                
                if (isOfficePickup) {
                    calculatedTimeDiv.find('.pickup-time-value').text(pickupTime);
                    calculatedTimeDiv.find('.duration-value').text(response.duration);
                    calculatedTimeDiv.show();
                    return;
                }

                // Calculate duration to office
                const durationToOffice = Math.abs(pickupMoment.diff(officeMoment, 'minutes'));

                // Apply 30-minute deduction rule
                const adjustedDuration = durationToOffice > 30 ? durationToOffice - 30 : 0;

                // Calculate final pickup time by subtracting adjusted duration from original pickup time
                const finalPickupTime = moment(pickupTime, 'HH:mm').subtract(adjustedDuration, 'minutes').format('HH:mm');
                
                calculatedTimeDiv.find('.pickup-time-value').text(finalPickupTime);
                calculatedTimeDiv.find('.duration-value').text(response.duration);
                calculatedTimeDiv.hide();
            },
            error: function() {
                calculatedTimeDiv.hide();
            }
        });
    }

    function confirmAICalculation() {
        // Disable the button and show loading state
        const confirmButton = $('.modal-footer .btn-primary');
        confirmButton.prop('disabled', true);
        confirmButton.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...');
        
        // Create the form data object
        let formData = {
            event_id: currentEventId,
            office_time: $('#officeTime').val(),
            guides: {},
            helpers: []
        };

        // Process guides
        $('.guide-entry').each(function() {
            const name = $(this).find('.guide-name').val();
            formData.guides[name] = {
                pickup_time: $(this).find('input[name$="[pickup_time]"]').val(),
                pickup_location: $(this).find('input[name$="[pickup_location]"]').val()
            };
        });

        // Process helpers
        $('.helper-entry input[name="helpers[]"]').each(function(index) {
            const helperName = $(this).val().trim();
            if (helperName) {
                formData.helpers.push(helperName);
            }
        });

        $.ajax({
            url: '{{ route("salary.calculate-from-ai") }}',
            method: 'POST',
            data: formData,
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            success: function(response) {
                $('#aiCalculationModal').modal('hide');
                
                // Reset button state (although modal will be hidden)
                confirmButton.prop('disabled', false);
                confirmButton.html('Confirm and Calculate');
                
                if (response.errors || response.error) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Partial Success',
                        text: response.errors || response.error || 'Some calculations could not be completed',
                        confirmButtonText: 'OK'
                    });
                } else {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: 'Calculation completed successfully',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.reload();
                    });
                }
            },
            error: function(xhr) {
                // Reset button state
                confirmButton.prop('disabled', false);
                confirmButton.html('Confirm and Calculate');
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to process calculation'
                });
            }
        });
    }

    // Add this to handle office time changes
    $('#officeTime').on('change', function() {
        // Recalculate for all guide entries
        $('.guide-entry').each(function() {
            calculatePickupDuration($(this).find('.pickup-location'));
        });
    });
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
@endsection
