@extends('partials.main')
<style>
    .logout-button {

        top: 20px;
        left: 20px;
        background-color: #ff4c4c;
        color: white;
        padding: 10px 20px;
        border: none;

        cursor: pointer;
        border-radius: 5px;
    }

    .logout-button:hover {
        background-color: #e60000;
    }
</style>
@php
    if (!function_exists('formatTime')) {
        function formatTime($hours)
        {
            $wholeHours = floor($hours);
            $fractionalHours = $hours - $wholeHours;
            $minutes = round($fractionalHours * 100);

            $totalMinutes = $wholeHours * 60 + $minutes;
            $finalHours = floor($totalMinutes / 60);
            $finalMinutes = $totalMinutes % 60;

            if ($finalMinutes == 0) {
                return $finalHours;
            } else {
                return sprintf('%d:%02d', $finalHours, $finalMinutes);
            }
        }
    }
@endphp


@section('content')
    <div class="content-page">

        <div class="content">
            <div class="row">
                <div class="col-6 col-md-2">
                    <a href="/" class="btn btn-warning btn-block"> <i class="fa fa-backward"></i> Back to Home</a>
                </div>
                <div style="text-align: right;" class="col-6 col-md-10">
                    <form action="/logout" method="POST">
                        <!-- CSRF Token for security (required in Laravel) -->
                        <input type="hidden" name="_token" value="{{ csrf_token() }}">

                        <button class="logout-button" type="submit">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </button>
                    </form>

                </div>
            </div>
            <div class="container-fluid">
                <h4 class="page-title">{{ $tourGuide->name }} - Guide Report ({{ $startDate->format('d.m.Y') }} -
                    {{ $endDate->format('d.m.Y') }})</h4>

                <!-- Date Filter Form -->
                <form action="{{ route('guide.work-report') }}" method="GET" class="mb-4">
                    <div class="row">
                        <div class="col-md-4">
                            <label for="start_date">Start Date</label>
                            <input type="text" name="start_date" id="start_date" class="form-control datepicker"
                                value="{{ $startDate->format('d.m.Y') }}">
                        </div>
                        <div class="col-md-4">
                            <label for="end_date">End Date</label>
                            <input type="text" name="end_date" id="end_date" class="form-control datepicker"
                                value="{{ $endDate->format('d.m.Y') }}">
                        </div>
                        <div class="col-md-4">
                            <label>&nbsp;</label>
                            <button type="submit" class="btn btn-primary btn-block">Filter</button>
                        </div>
                    </div>
                </form>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="mt-0 header-title">Tours and Hours Worked</h4>

                                <div class="table-responsive">
                                    <table id="datatable-buttons" class="table table-striped table-bordered"
                                        style="border-collapse: collapse; border-spacing: 0; width: 100%">
                                        <thead>
                                            <tr>
                                                <th>Tour Date</th>
                                                <th>Tour Name</th>
                                                <th>Guide Start Time</th>
                                                <th>Guide End Time</th>
                                                <th>Work Hours</th>
                                                <th>Work Hours with holiday extras</th>
                                                <th>Night supplement hours(20-6)</th>
                                                <th>Night supplement ONLY with holiday extras</th>
                                                <th>Approval Status</th>
                                                <th>Admin Comment</th>
                                                <th>Guide Comment</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($eventSalaries as $salary)
                                                <tr>
                                                    <td>{{ \Carbon\Carbon::parse($salary->event->start_time)->format('d.m.Y') }}</td>
                                                    <td>{{ $salary->event->name }}</td>
                                                    <td>
                                                        @if($salary->guide_start_time)
                                                            {{ \Carbon\Carbon::parse($salary->guide_start_time)->format('H:i') }}
                                                        @else
                                                            N/A
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if($salary->guide_end_time)
                                                            {{ \Carbon\Carbon::parse($salary->guide_end_time)->format('H:i') }}
                                                        @else
                                                            N/A
                                                        @endif
                                                    </td>
                                                    
                                                    <td>{{ formatTime($salary->normal_hours) }}</td>
                                                    <td>{{ formatTime($salary->holiday_hours) }}</td>
                                                    <td>{{ formatTime($salary->normal_night_hours) }}</td>
                                                    <td>{{ formatTime($salary->holiday_night_hours) }}</td>
                                                    
                                                   
                                                    
                                                    
                                                    <td>
                                                        @if ($salary->is_chore == 0  && $salary->approval_status == 1)
                                                            <span class="badge badge-success">Approved</span>
                                                        @elseif ($salary->is_chore == 0 && $salary->approval_status == 0)
                                                            <span class="badge badge-warning">Pending</span>
                                                        @elseif ($salary->approval_status == 1)
                                                            <span class="badge badge-success">Approved</span>
                                                        @elseif ($salary->approval_status == 2)
                                                            <span class="badge badge-secondary">Adjusted</span>
                                                        @elseif ($salary->approval_status == 3)
                                                            <span class="badge badge-warning">Needs More Info</span>
                                                        @elseif ($salary->approval_status == 4)
                                                            <span class="badge badge-danger">Rejected</span>
                                                        @else
                                                            <span class="badge badge-warning">Pending</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if ($salary->approval_comment)
                                                            {{ $salary->approval_comment }}
                                                        @else
                                                            No comment
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if ($salary->guide_comment)
                                                            {{ $salary->guide_comment }}
                                                        @else
                                                            No comment
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if ($salary->is_chore == 1 && $salary->is_guide_updated == 0 || $salary->approval_status == 3)
                                                            <button class="btn btn-primary btn-sm edit-hours-btn"
                                                                data-event-salary-id="{{ $salary->id }}"
                                                                data-guide-start-time="{{ $salary->guide_start_time ? \Carbon\Carbon::parse($salary->guide_start_time)->format('d.m.Y H:i') : '' }}"
                                                                data-guide-end-time="{{ $salary->guide_end_time ? \Carbon\Carbon::parse($salary->guide_end_time)->format('d.m.Y H:i') : '' }}"
                                                                data-guide-comment="{{ $salary->guide_comment }}"
                                                                data-tour-date="{{ \Carbon\Carbon::parse($salary->event->start_time)->format('d.m.Y') }}">
                                                                Edit
                                                            </button>
                                                        @else
                                                            N/A
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if($salary->guide_image)
                                                            <img src="{{ asset('storage/' . $salary->guide_image) }}" alt="Guide Image" class="img-thumbnail" style="max-width: 100px;">
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
            </div>
        </div>
    </div>

    <!-- Modal for Editing Hours -->
    <div class="modal fade" id="editHoursModal" tabindex="-1" aria-labelledby="editHoursModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="updateHoursForm" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="event_salary_id" id="modal-event-salary-id">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editHoursModalLabel">Edit Start & End Time</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="guide_start_time">Start Date & Time</label>
                            <input type="text" class="form-control" id="modal-guide-start-time" name="guide_start_time">
                        </div>
                        <div class="form-group">
                            <label for="guide_end_time">End Date & Time</label>
                            <input type="text" class="form-control" id="modal-guide-end-time" name="guide_end_time">
                        </div>
                        <div class="form-group">
                            <label for="guide_comment">Guide Comment</label>
                            <textarea class="form-control" id="modal-guide-comment" name="guide_comment" rows="3"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="guide_image">Upload Image (optional)</label>
                            <input type="file" class="form-control-file" id="modal-guide-image" name="guide_image" accept="image/*">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            // Initialize Flatpickr for date inputs
            flatpickr(".datepicker", {
                dateFormat: "d.m.Y",
                allowInput: true
            });

            // Initialize Flatpickr for modal date-time inputs
            flatpickr("#modal-guide-start-time, #modal-guide-end-time", {
                enableTime: true,
                dateFormat: "d.m.Y H:i",
                time_24hr: true,
                allowInput: true
            });

            // Handle opening the modal and populating the form with the current values
            $('.edit-hours-btn').on('click', function() {
                const eventSalaryId = $(this).data('event-salary-id');
                const startTime = $(this).data('guide-start-time');
                const endTime = $(this).data('guide-end-time');
                const guideComment = $(this).data('guide-comment');
                const tourDate = $(this).data('tour-date');

                console.log('Tour Date:', tourDate); // For debugging

                // Populate modal fields
                $('#modal-event-salary-id').val(eventSalaryId);
                
                // Set start time
                if (startTime) {
                    $('#modal-guide-start-time')[0]._flatpickr.setDate(startTime);
                } else if (tourDate) {
                    $('#modal-guide-start-time')[0]._flatpickr.setDate(tourDate + ' 00:00');
                }
                
                // Set end time
                if (endTime) {
                    $('#modal-guide-end-time')[0]._flatpickr.setDate(endTime);
                } else if (tourDate) {
                    $('#modal-guide-end-time')[0]._flatpickr.setDate(tourDate + ' 23:59');
                }
                
                $('#modal-guide-comment').val(guideComment);

                // Show the modal
                $('#editHoursModal').modal('show');
            });

            // Handle form submission via AJAX
            $('#updateHoursForm').on('submit', function(e) {
                e.preventDefault(); // Prevent default form submission

                const formData = new FormData(this);
                const eventSalaryId = $('#modal-event-salary-id').val();

                $.ajax({
                    url: `/event-salary/${eventSalaryId}/update-hours`,
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        $('#editHoursModal').modal('hide');
                        location.reload();
                    },
                    error: function(xhr) {
                        const errors = xhr.responseJSON.errors;
                        let errorMessage = 'Failed to update hours:\n';
                        for (const field in errors) {
                            errorMessage += `${field}: ${errors[field].join(', ')}\n`;
                        }
                        alert(errorMessage);
                    }
                });
            });
        });
    </script>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
@endsection
