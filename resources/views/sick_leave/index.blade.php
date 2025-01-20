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
                                <h4 class="page-title">Missing Hours</h4>
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item">
                                        <a href="javascript:void(0);">Home</a>
                                    </li>
                                    <li class="breadcrumb-item active">Manage Missing Hours</li>
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
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table id="datatable" class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Guide</th>
                                                <th>Tour Name</th>
                                                <th>Date</th>
                                                <th>Start Time</th>
                                                <th>End Time</th>
                                                <th>Normal Hours</th>
                                                <th>Night Hours</th>
                                                <th>Holiday Hours</th>
                                                <th>Holiday Night Hours</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($sickLeaves as $sickLeave)
                                            <tr data-guide-id="{{ $sickLeave->guide_id }}">
                                                <td>{{ $sickLeave->id }}</td>
                                                <td>{{ $sickLeave->guide_name }}</td>
                                                <td>{{ $sickLeave->tour_name }}</td>
                                                <td>{{ $sickLeave->date->format('d/m/Y') }}</td>
                                                <td>{{ $sickLeave->start_time }}</td>
                                                <td>{{ $sickLeave->end_time }}</td>
                                                <td>{{ $sickLeave->normal_hours }}</td>
                                                <td>{{ $sickLeave->normal_night_hours }}</td>
                                                <td>{{ $sickLeave->holiday_hours }}</td>
                                                <td>{{ $sickLeave->holiday_night_hours }}</td>
                                                <td>
                                                    <button class="btn btn-sm btn-primary edit-btn" 
                                                            data-id="{{ $sickLeave->id }}"
                                                            data-guide-id="{{ $sickLeave->guide_id }}"
                                                            data-guide-name="{{ $sickLeave->guide_name }}"
                                                            data-tour-name="{{ $sickLeave->tour_name }}"
                                                            data-start-time="{{ $sickLeave->start_time }}"
                                                            data-end-time="{{ $sickLeave->end_time }}"
                                                            data-toggle="modal" 
                                                            data-target="#editModal">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <form action="{{ route('sick-leaves.destroy', $sickLeave->id) }}" 
                                                          method="POST" 
                                                          class="d-inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" 
                                                                class="btn btn-sm btn-danger delete-btn">
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
                </div>
            </div>
        </div>
    </div>
@endsection

<script type="text/javascript" src="https://cdn.jsdelivr.net/jquery/latest/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Add Flatpickr JS -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        initFlatpickr();
        const deleteForms = document.querySelectorAll('.delete-form');
        

        function initFlatpickr() {
            flatpickr(".flatpickr-date", {
                dateFormat: "Y-m-d",
            });

            flatpickr(".flatpickr-datetime", {
                enableTime: true,
                dateFormat: "Y-m-d H:i",
                time_24hr: true
            });
        }
        
        deleteForms.forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                Swal.fire({
                    title: 'Are you sure?',
                    text: "These missing hours will be permanently deleted!",
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
    });

    function openEditModal(entry) {
        // Set the form action URL
        const form = document.getElementById('editMissingHoursForm');
        form.action = `/missing-hours/${entry.id}`;

        // Populate the form fields
        document.getElementById('edit_tour_name').value = entry.tour_name;
        document.getElementById('edit_guide_id').value = entry.guide_id;
        document.getElementById('edit_applied_at').value = entry.applied_at.substring(0, 7); // YYYY-MM format

        // Initialize Flatpickr for datetime fields
        const startTimePicker = flatpickr("#edit_start_time", {
            enableTime: true,
            dateFormat: "Y-m-d H:i",
            time_24hr: true,
            defaultDate: entry.start_time
        });

        const endTimePicker = flatpickr("#edit_end_time", {
            enableTime: true,
            dateFormat: "Y-m-d H:i",
            time_24hr: true,
            defaultDate: entry.end_time
        });

        // Show the modal
        $('#editMissingHoursModal').modal('show');
    }

    // Initialize Flatpickr for all datetime inputs when document loads
    document.addEventListener('DOMContentLoaded', function() {
        flatpickr(".flatpickr-datetime", {
            enableTime: true,
            dateFormat: "Y-m-d H:i",
            time_24hr: true
        });
    });

    // Add this to your script section
$(document).ready(function() {
    // Initialize daterangepicker for edit form
    $('#edit_datetime_range').daterangepicker({
        timePicker: true,
        timePicker24Hour: true,
        autoUpdateInput: false,
        locale: {
            format: 'YYYY-MM-DD HH:mm'
        }
    });

    // Handle daterangepicker events
    $('#edit_datetime_range').on('apply.daterangepicker', function(ev, picker) {
        $(this).val(picker.startDate.format('YYYY-MM-DD HH:mm') + ' - ' + picker.endDate.format('YYYY-MM-DD HH:mm'));
        $('#edit_start_time').val(picker.startDate.format('YYYY-MM-DD HH:mm'));
        $('#edit_end_time').val(picker.endDate.format('YYYY-MM-DD HH:mm'));
    });

    // Handle edit button click
    $('.edit-btn').click(function() {
        var id = $(this).data('id');
        var row = $(this).closest('tr');
        
        // Populate form fields
        $('#editSickLeaveForm').attr('action', '/sick-leaves/' + id);
        $('#edit_tour_name').val(row.find('td:eq(2)').text());
        $('#edit_guide_id').val(row.data('guide-id'));
        
        // Set daterangepicker values
        var startTime = moment(row.find('td:eq(4)').text(), 'YYYY-MM-DD HH:mm');
        var endTime = moment(row.find('td:eq(5)').text(), 'YYYY-MM-DD HH:mm');
        
        $('#edit_datetime_range').data('daterangepicker').setStartDate(startTime);
        $('#edit_datetime_range').data('daterangepicker').setEndDate(endTime);
        $('#edit_datetime_range').val(startTime.format('YYYY-MM-DD HH:mm') + ' - ' + endTime.format('YYYY-MM-DD HH:mm'));
        
        $('#edit_start_time').val(startTime.format('YYYY-MM-DD HH:mm'));
        $('#edit_end_time').val(endTime.format('YYYY-MM-DD HH:mm'));
    });
});
</script>

<script>
$(document).ready(function() {
    $('#editModal').on('shown.bs.modal', function () {
        $('#edit_tour_name').trigger('focus');
    });
    
    $('.edit-btn').click(function() {
        var button = $(this);
        var id = button.data('id');
        var guideId = button.data('guide-id');
        var tourName = button.data('tour-name');
        var startTime = moment(button.data('start-time'));
        var endTime = moment(button.data('end-time'));

        // Populate form fields
        $('#editSickLeaveForm').attr('action', '/sick-leaves/' + id);
        $('#edit_tour_name').val(tourName);
        $('#edit_guide_id').val(guideId);
        
        // Set daterangepicker values
        $('#edit_datetime_range').data('daterangepicker').setStartDate(startTime);
        $('#edit_datetime_range').data('daterangepicker').setEndDate(endTime);
        $('#edit_datetime_range').val(startTime.format('YYYY-MM-DD HH:mm') + ' - ' + endTime.format('YYYY-MM-DD HH:mm'));
        
        $('#edit_start_time').val(startTime.format('YYYY-MM-DD HH:mm'));
        $('#edit_end_time').val(endTime.format('YYYY-MM-DD HH:mm'));
    });
});
</script>

<script>
$(document).ready(function() {

        // Add event listener to delete buttons
    $('.delete-btn').click(function(e) {
        e.preventDefault();
        const form = $(this).closest('form');
        
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    });
    $('.edit-btn').click(function() {
       
        // Updated form population
        var id = $(this).data('id');
        var guideId = $(this).data('guide-id');
        var guideName = $(this).data('guide-name');
        var tourName = $(this).data('tour-name');
        var startTime = moment($(this).data('start-time'));
        var endTime = moment($(this).data('end-time'));

        // Update form action
        $('#editSickLeaveForm').attr('action', '/sick-leaves/' + id);
        
        // Populate fields
        $('#sick_leave_id').val(id);
        $('#edit_tour_name').val(tourName);
        $('#edit_guide_id').val(guideId).trigger('change');
        
        // Initialize daterangepicker if not already initialized
        if(!$('#edit_datetime_range').data('daterangepicker')) {
            $('#edit_datetime_range').daterangepicker({
                timePicker: true,
                timePicker24Hour: true,
                locale: {
                    format: 'YYYY-MM-DD HH:mm'
                }
            });
        }

        // Set date range values
        $('#edit_datetime_range').data('daterangepicker').setStartDate(startTime);
        $('#edit_datetime_range').data('daterangepicker').setEndDate(endTime);
        $('#edit_datetime_range').val(startTime.format('YYYY-MM-DD HH:mm') + ' - ' + endTime.format('YYYY-MM-DD HH:mm'));
        
        // Set hidden inputs
        $('#edit_start_time').val(startTime.format('YYYY-MM-DD HH:mm'));
        $('#edit_end_time').val(endTime.format('YYYY-MM-DD HH:mm'));
    });
});
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

<div class="modal fade" 
     id="editModal" 
     tabindex="-1" 
     role="dialog" 
     aria-labelledby="editModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editModalLabel">Edit Sick Leave Tour</h5>
                <button type="button" 
                        class="close" 
                        data-dismiss="modal" 
                        aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="editSickLeaveForm" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="form-group">
                        <label for="edit_tour_name">Tour Name / Reason</label>
                        <input type="text" name="tour_name" id="edit_tour_name" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="edit_guide_id">Guide</label>
                        <select name="guide_id" id="edit_guide_id" class="form-control" required>
                            <option value="">Select Guide</option>
                            @foreach ($guides as $guide)
                                <option value="{{ $guide->id }}">{{ $guide->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Date & Time Range</label>
                        <input type="text" name="datetime_range" class="form-control" id="edit_datetime_range" required>
                        <input type="hidden" name="start_time" id="edit_start_time">
                        <input type="hidden" name="end_time" id="edit_end_time">
                    </div>

                    <input type="hidden" name="sick_leave_id" id="sick_leave_id">
                    <button type="submit" class="btn btn-primary">Update Sick Leave</button>
                </form>
            </div>
        </div>
    </div>
</div>