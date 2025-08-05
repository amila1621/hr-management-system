@extends('partials.main')
  <!-- Add Flatpickr CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

@section('content')
    <div class="content-page">
        <div class="content">
            <div class="container-fluid">
                <div class="page-title-box">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <div class="page-title-box">
                                <h4 class="page-title">Missing/Extra Hours</h4>
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item">
                                        <a href="javascript:void(0);">Home</a>
                                    </li>
                                    <li class="breadcrumb-item active">Manage Missing/Extra Hours</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>

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

                <!-- Display Validation Errors -->
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
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="mt-0 header-title">Latest Missing/Extra Hours</h4>
                                <div class="table-responsive">
                                    <table class="table table-centered table-hover mb-0 table-striped table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Staff Name</th>
                                                <th>Date</th>
                                                <th>Start Time</th>
                                                <th>End Time</th>
                                                <th>Tour Name/Reason</th>
                                                <th>Applied Month</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($missingHours as $entry)
                                                <tr>
                                                    <td>{{ $entry->staff_name }}</td>
                                                    <td>{{ \Carbon\Carbon::parse($entry->date)->format('d/m/Y') }}</td>
                                                    <td>{{ \Carbon\Carbon::parse($entry->start_time)->format('H:i') }}</td>
                                                    <td>{{ \Carbon\Carbon::parse($entry->end_time)->format('H:i') }}</td>
                                                    <td>{{ $entry->reason }}</td>
                                                    <td>{{ \Carbon\Carbon::parse($entry->applied_date)->format('F Y') }}</td>
                                                    <td>
                                                        <button type="button" 
                                                                class="btn btn-sm btn-info" 
                                                                onclick="openEditModal({{ $entry }})">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        
                                                        <form action="{{ route('missing-hours-supervisor.destroy', $entry->id) }}" 
                                                            method="POST" 
                                                            class="d-inline delete-form">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-sm btn-danger">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="mt-0 header-title">Create New Missing/Extra Hours</h4>
                                <form action="{{ route('missing-hours-supervisor.store') }}" method="POST">
                                    @csrf
                                    <div class="form-group">
                                        <label for="tour_name">Tour Name / Reason</label>
                                        <input type="text" name="tour_name" id="tour_name" class="form-control" required>
                                        @error('tour_name')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>

                                    <div class="form-group">
                                        <label for="title">Staff</label>
                                      <select name="guide_id" id="guide_id" class="form-control">
                                        <option value="">Select Staff</option>
                                        @foreach ($staffs as $staff)
                                            <option value="{{ $staff->id }}">{{ $staff->name }}</option>
                                        @endforeach
                                      </select>
                                    </div>

                                    
                                    <div class="form-group">
                                        <label for="start_time">Start Time</label>
                                        <input type="text" name="start_time" id="start_time" class="form-control flatpickr-datetime flatpickr-input" readonly="readonly">
                                        @error('start_time')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>

                                    
                                    <div class="form-group">
                                        <label for="end_time">End Time</label>
                                        <input type="text" name="end_time" id="end_time" class="form-control flatpickr-datetime flatpickr-input" readonly="readonly">
                                        @error('end_time')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>


                                    <div class="form-group">
                                        <label for="applied_at">Applied Month</label>
                                        <input type="month" 
                                               name="applied_at" 
                                               id="applied_at" 
                                               value="{{ date('Y-m') }}" 
                                               class="form-control" 
                                               required>
                                        @error('applied_at')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>

                                   
                                    {{-- <div class="form-group">
                                        <label for="normal_hours">Normal Hours</label>
                                        <input type="time" name="normal_hours" id="normal_hours" value="00:00" class="form-control" required>
                                        @error('normal_hours')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>
                                    <div class="form-group">
                                        <label for="normal_night_hours">Normal Night Hours</label>
                                        <input type="time" name="normal_night_hours" id="normal_night_hours" value="00:00" class="form-control" required>
                                        @error('normal_night_hours')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>
                                    <div class="form-group">
                                        <label for="holiday_hours">Holiday Hours</label>
                                        <input type="time" name="holiday_hours" id="holiday_hours" value="00:00" class="form-control" required>
                                        @error('holiday_hours')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>

                                    <div class="form-group">
                                        <label for="holiday_night_hours">Holiday Night Hours</label>
                                        <input type="time" name="holiday_night_hours" id="holiday_night_hours" value="00:00" class="form-control" required>
                                        @error('holiday_night_hours')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div> --}}


                                    <button type="submit" class="btn btn-primary waves-effect waves-light">Create Missing/Extra Hours</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Add Flatpickr JS -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        initFlatpickr();
        setupDateValidation();
        const deleteForms = document.querySelectorAll('.delete-form');
        
        deleteForms.forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                Swal.fire({
                    title: 'Are you sure?',
                    text: "These missing/extra hours will be permanently deleted!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        this.submit();
                    }
                });
            });
        });

        function initFlatpickr() {
            flatpickr(".flatpickr-date", {
                dateFormat: "Y-m-d",
            });

            // Create form flatpickr
            const startTimePicker = flatpickr("#start_time", {
                enableTime: true,
                dateFormat: "Y-m-d H:i",
                time_24hr: true,
                onChange: function(selectedDates, dateStr, instance) {
                    validateSameDate('start_time', 'end_time');
                }
            });

            const endTimePicker = flatpickr("#end_time", {
                enableTime: true,
                dateFormat: "Y-m-d H:i",
                time_24hr: true,
                onChange: function(selectedDates, dateStr, instance) {
                    validateSameDate('start_time', 'end_time');
                }
            });
        }

        function setupDateValidation() {
            // Add form submit validation
            const createForm = document.querySelector('form[action*="missing-hours-supervisor.store"]');
            if (createForm) {
                createForm.addEventListener('submit', function(e) {
                    if (!validateSameDate('start_time', 'end_time')) {
                        e.preventDefault();
                        Swal.fire({
                            title: 'Invalid Date Range',
                            text: 'Start time and end time must be on the same date.',
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    }
                });
            }
        }

        function validateSameDate(startId, endId) {
            const startTime = document.getElementById(startId).value;
            const endTime = document.getElementById(endId).value;
            
            if (startTime && endTime) {
                const startDate = new Date(startTime).toDateString();
                const endDate = new Date(endTime).toDateString();
                
                const startInput = document.getElementById(startId);
                const endInput = document.getElementById(endId);
                
                if (startDate !== endDate) {
                    // Add error styling
                    startInput.classList.add('is-invalid');
                    endInput.classList.add('is-invalid');
                    
                    // Show error message
                    showDateError(startId, endId);
                    return false;
                } else {
                    // Remove error styling
                    startInput.classList.remove('is-invalid');
                    endInput.classList.remove('is-invalid');
                    
                    // Remove error message
                    removeDateError(startId, endId);
                    return true;
                }
            }
            return true;
        }

        function showDateError(startId, endId) {
            const startInput = document.getElementById(startId);
            const endInput = document.getElementById(endId);
            
            // Remove existing error messages
            removeDateError(startId, endId);
            
            // Add error message
            const errorDiv = document.createElement('div');
            errorDiv.className = 'invalid-feedback date-validation-error';
            errorDiv.textContent = 'Start time and end time must be on the same date.';
            
            endInput.parentNode.appendChild(errorDiv);
        }

        function removeDateError(startId, endId) {
            const existingErrors = document.querySelectorAll('.date-validation-error');
            existingErrors.forEach(error => error.remove());
        }
    });

    function openEditModal(entry) {
        // Set the form action URL
        const form = document.getElementById('editMissingHoursForm');
        form.action = `/missing-hours-supervisor/${entry.id}`;

        // Populate the form fields
        document.getElementById('edit_tour_name').value = entry.reason;
        document.getElementById('edit_guide_id').value = entry.staff_id;
        document.getElementById('edit_applied_at').value = moment(entry.applied_date).format('YYYY-MM');

        // Initialize Flatpickr for datetime fields with validation
        flatpickr("#edit_start_time", {
            enableTime: true,
            dateFormat: "Y-m-d H:i",
            time_24hr: true,
            defaultDate: entry.start_time,
            onChange: function(selectedDates, dateStr, instance) {
                validateSameDateEdit('edit_start_time', 'edit_end_time');
            }
        });

        flatpickr("#edit_end_time", {
            enableTime: true,
            dateFormat: "Y-m-d H:i",
            time_24hr: true,
            defaultDate: entry.end_time,
            onChange: function(selectedDates, dateStr, instance) {
                validateSameDateEdit('edit_start_time', 'edit_end_time');
            }
        });

        // Add form submit validation for edit modal
        form.addEventListener('submit', function(e) {
            if (!validateSameDateEdit('edit_start_time', 'edit_end_time')) {
                e.preventDefault();
                Swal.fire({
                    title: 'Invalid Date Range',
                    text: 'Start time and end time must be on the same date.',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            }
        });

        // Show the modal
        $('#editMissingHoursModal').modal('show');
    }

    function validateSameDateEdit(startId, endId) {
        const startTime = document.getElementById(startId).value;
        const endTime = document.getElementById(endId).value;
        
        if (startTime && endTime) {
            const startDate = new Date(startTime).toDateString();
            const endDate = new Date(endTime).toDateString();
            
            const startInput = document.getElementById(startId);
            const endInput = document.getElementById(endId);
            
            if (startDate !== endDate) {
                // Add error styling
                startInput.classList.add('is-invalid');
                endInput.classList.add('is-invalid');
                
                // Show error message
                showDateErrorEdit(startId, endId);
                return false;
            } else {
                // Remove error styling
                startInput.classList.remove('is-invalid');
                endInput.classList.remove('is-invalid');
                
                // Remove error message
                removeDateErrorEdit(startId, endId);
                return true;
            }
        }
        return true;
    }

    function showDateErrorEdit(startId, endId) {
        const endInput = document.getElementById(endId);
        
        // Remove existing error messages
        removeDateErrorEdit(startId, endId);
        
        // Add error message
        const errorDiv = document.createElement('div');
        errorDiv.className = 'invalid-feedback date-validation-error-edit';
        errorDiv.textContent = 'Start time and end time must be on the same date.';
        
        endInput.parentNode.appendChild(errorDiv);
    }

    function removeDateErrorEdit(startId, endId) {
        const existingErrors = document.querySelectorAll('.date-validation-error-edit');
        existingErrors.forEach(error => error.remove());
    }
</script>

<style>
    .content-page {
        position: relative;
        height: 100vh;
        overflow: hidden;
    }

    .content {
        height: calc(100vh - 70px); /* Adjust 70px based on your header height */
        overflow-y: auto;
        padding-bottom: 60px; /* Add padding to account for footer */
    }

    /* Optional: If you want a smoother scrolling experience */
    .content {
        scroll-behavior: smooth;
        -webkit-overflow-scrolling: touch;
    }

    /* Ensure footer stays at bottom */
    .footer {
        position: fixed;
        bottom: 0;
        right: 0;
        left: 240px; /* Adjust based on your sidebar width */
        z-index: 100;
        background: #fff;
    }

    .navbar-right {
        margin-top: 15px;
    }
</style>

<div class="modal fade" id="editMissingHoursModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Missing/Extra Hours</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="editMissingHoursForm" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="form-group">
                        <label for="edit_tour_name">Tour Name / Reason</label>
                        <input type="text" name="tour_name" id="edit_tour_name" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="edit_guide_id">Staff</label>
                        <select name="guide_id" id="edit_guide_id" class="form-control">
                            <option value="">Select Staff</option>
                            @foreach ($staffs as $staff)
                                <option value="{{ $staff->id }}">{{ $staff->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="edit_start_time">Start Time</label>
                        <input type="text" name="start_time" id="edit_start_time" class="form-control flatpickr-datetime" readonly="readonly">
                    </div>

                    <div class="form-group">
                        <label for="edit_end_time">End Time</label>
                        <input type="text" name="end_time" id="edit_end_time" class="form-control flatpickr-datetime" readonly="readonly">
                    </div>

                    <div class="form-group">
                        <label for="edit_applied_at">Applied Month</label>
                        <input type="month" name="applied_at" id="edit_applied_at" class="form-control" required>
                    </div>

                    <button type="submit" class="btn btn-primary">Update Missing/Extra Hours</button>
                </form>
            </div>
        </div>
    </div>
</div>
