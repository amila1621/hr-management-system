@extends('partials.main')

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
            <div class="container-fluid">
                <h4 class="page-title">{{ $tourGuide->name }} - Guide Report ({{ $startDate->format('d.m.Y') }} -
                    {{ $endDate->format('d.m.Y') }})</h4>

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
                                                <th>Guide Times</th>
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
                                                    <td>
                                                        @if($salary->guide_start_time)
                                                            {{ \Carbon\Carbon::parse($salary->guide_start_time)->format('d.m.Y') }}
                                                        @elseif($salary->event->start_time)
                                                            {{ \Carbon\Carbon::parse($salary->event->start_time)->format('d.m.Y') }}
                                                        @else
                                                            N/A
                                                        @endif
                                                    </td>
                                                    <td>{{ $salary->event->name }}</td>
                                                    <td>
                                                        @if($salary->guide_times)
                                                            @foreach(json_decode($salary->guide_times, true) ?? [] as $time)
                                                                {{ \Carbon\Carbon::parse($time['start'])->format('H:i') }} - 
                                                                {{ \Carbon\Carbon::parse($time['end'])->format('H:i') }}<br>
                                                            @endforeach
                                                        @else
                                                            @if($salary->guide_start_time)
                                                                {{ \Carbon\Carbon::parse($salary->guide_start_time)->format('H:i') }} - 
                                                                {{ \Carbon\Carbon::parse($salary->guide_end_time)->format('H:i') }}
                                                            @else
                                                                N/A
                                                            @endif
                                                        @endif
                                                    </td>
                                                    
                                                    @if ($salary->approval_status != 4)
                                                        
                                                    <td>{{ formatTime($salary->normal_hours) }}</td>
                                                    <td>{{ formatTime($salary->holiday_hours) }}</td>
                                                    <td>{{ formatTime($salary->normal_night_hours) }}</td>
                                                    <td>{{ formatTime($salary->holiday_night_hours) }}</td>
                                                    @else
                                                        <td>N/A</td>
                                                        <td>N/A</td>
                                                        <td>N/A</td>
                                                        <td>N/A</td>
                                                    @endif
                                                    
                                                   
                                                    
                                                    
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
                        <div id="time-entries">
                            <div class="time-entry">
                                <div class="form-group">
                                    <label>Time Entry 1</label>
                                    <div class="row">
                                        <div class="col-md-5">
                                            <input type="text" class="form-control guide-start-time" name="guide_times[0][start]" placeholder="Start Time">
                                        </div>
                                        <div class="col-md-5">
                                            <input type="text" class="form-control guide-end-time" name="guide_times[0][end]" placeholder="End Time">
                                        </div>
                                        <div class="col-md-2">
                                            <button type="button" class="btn btn-danger btn-sm remove-time">×</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="btn btn-secondary btn-sm mt-2" id="add-time">Add Another Time</button>
                        
                        <div class="form-group mt-3">
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

            // Initialize Flatpickr for the first time entry
            initializeFlatpickr();

            // Add new time entry
            $('#add-time').click(function() {
                const timeEntryCount = $('.time-entry').length;
                const newEntry = `
                    <div class="time-entry">
                        <div class="form-group">
                            <label>Time Entry ${timeEntryCount + 1}</label>
                            <div class="row">
                                <div class="col-md-5">
                                    <input type="text" class="form-control guide-start-time" name="guide_times[${timeEntryCount}][start]" placeholder="Start Time">
                                </div>
                                <div class="col-md-5">
                                    <input type="text" class="form-control guide-end-time" name="guide_times[${timeEntryCount}][end]" placeholder="End Time">
                                </div>
                                <div class="col-md-2">
                                    <button type="button" class="btn btn-danger btn-sm remove-time">×</button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                $('#time-entries').append(newEntry);
                initializeFlatpickr();
            });

            // Remove time entry
            $(document).on('click', '.remove-time', function() {
                if ($('.time-entry').length > 1) {
                    $(this).closest('.time-entry').remove();
                }
            });

            function initializeFlatpickr() {
                flatpickr(".guide-start-time, .guide-end-time", {
                    enableTime: true,
                    dateFormat: "d.m.Y H:i",
                    time_24hr: true,
                    allowInput: true
                });
            }

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
                
                // Clear existing time entries except the first one
                $('.time-entry:not(:first)').remove();
                
                // If there are existing times, populate them
                if (startTime && endTime) {
                    $('.guide-start-time').first()[0]._flatpickr.setDate(startTime);
                    $('.guide-end-time').first()[0]._flatpickr.setDate(endTime);
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
                    url: `/event-salary/${eventSalaryId}/update-hours-by-guides`,
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

    @if($displayAnnouncement && $latestAnnouncement)
        <!-- Announcement Modal -->
        <div class="modal fade" id="announcementModal" tabindex="-1" role="dialog" aria-labelledby="announcementModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div style="text-align: center" class="modal-header text-center">
                        <h5 class="modal-title " id="announcementModalLabel">Important Announcement</h5>
                    </div>
                    <div class="modal-body">
                        
                        <div  class="announcement-content text-center">
                            <h4>{{ $latestAnnouncement->title }}</h4>
                            {!! nl2br(e($latestAnnouncement->description)) !!}
                        </div>
                        <div class="mt-3  text-muted small">
                            Posted on {{ $latestAnnouncement->created_at->format('M d, Y') }}
                        </div>
                    </div>
                    <div class="modal-footer">
                        <form action="{{ route('announcements.acknowledge', $latestAnnouncement->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-primary">I Acknowledge</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <script>
            $(document).ready(function() {
                $('#announcementModal').modal('show');
            });
        </script>
    @endif
@endsection
