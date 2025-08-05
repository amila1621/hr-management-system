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
                                            @foreach ($combinedData as $record)
                                                <tr @if($record["type"] == "sick_leave") style="background-color:#806600;" @endif>
                                                    <td>
                                                        {{ \Carbon\Carbon::parse($record['date'])->format('d.m.Y') }}
                                                    </td>
                                                    <td>
                                                        {{ $record['tour_name'] }}
                                                        @if($record['type'] == 'sick_leave')
                                                            <span class="badge badge-info">Sick Leave</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if($record['type'] == 'event')
                                                        
                                                        @if($record['guide_start_time'])
                                                        {{ \Carbon\Carbon::parse($record['guide_start_time'])->format('H:i') }} - 
                                                        {{ \Carbon\Carbon::parse($record['guide_end_time'])->format('H:i') }}
                                                            @else
                                                                N/A
                                                            @endif
                                                        
                                                          
                                                        @else
                                                            {{ \Carbon\Carbon::parse($record['start_time'])->format('H:i') }} - 
                                                            {{ \Carbon\Carbon::parse($record['end_time'])->format('H:i') }}
                                                        @endif
                                                    </td>
                                                    
                                                    <td>{{ formatTime($record['normal_hours']) }}</td>
                                                    <td>{{ formatTime($record['holiday_hours']) }}</td>
                                                    <td>{{ formatTime($record['normal_night_hours']) }}</td>
                                                    <td>{{ formatTime($record['holiday_night_hours']) }}</td>
                                                    
                                                    <td>
                                                        @if($record['type'] == 'event')
                                                            @if ($record['approval_status'] == 1)
                                                                <span class="badge badge-success">Approved</span>
                                                            @elseif ($record['approval_status'] == 2)
                                                                <span class="badge badge-secondary">Adjusted</span>
                                                            @elseif ($record['approval_status'] == 3)
                                                                <span class="badge badge-warning">Needs More Info</span>
                                                            @elseif ($record['approval_status'] == 4)
                                                                <span class="badge badge-danger">Rejected</span>
                                                            @else
                                                                <span class="badge badge-warning">Pending</span>
                                                            @endif
                                                        @else
                                                            <span class="badge badge-success">Approved</span>
                                                        @endif
                                                    </td>
                                                    
                                                    <td>
                                                        @if($record['type'] == 'event')
                                                            {{ $record['approval_comment'] ?? 'No comment' }}
                                                        @else
                                                            N/A
                                                        @endif
                                                    </td>
                                                    
                                                    <td>
                                                        @if($record['type'] == 'event')
                                                            {{ $record['guide_comment'] ?? 'No comment' }}
                                                        @else
                                                            N/A
                                                        @endif
                                                    </td>
                                                    
                                                    <td>
                                                        @if($record['type'] == 'event' && 
                                                            ($record['is_chore'] == 1 && $record['is_guide_updated'] == 0 || 
                                                            $record['approval_status'] == 3))
                                                            <button class="btn btn-primary btn-sm edit-hours-btn"
                                                                data-event-salary-id="{{ $record['id'] }}"
                                                                data-guide-start-time="{{ $record['start_time'] }}"
                                                                data-guide-end-time="{{ $record['end_time'] }}"
                                                                data-guide-comment="{{ $record['guide_comment'] ?? '' }}"
                                                                data-tour-date="{{ $record['start_time'] }}">
                                                                Edit
                                                            </button>
                                                        @else
                                                            N/A
                                                        @endif
                                                    </td>
                                                </tr>

                                            @endforeach

                                            
                                        </tbody>

                                        @php
                                            $today = \Carbon\Carbon::now();
                                            $isLastDayOfMonth = $today->copy()->endOfMonth()->format('Y-m-d') === $today->format('Y-m-d');
                                        @endphp

                                        @if($isLastDayOfMonth)

                                        <tfoot>
                                            <tr class="font-weight-bold">
                                                <td colspan="3">Total</td>
                                                <td id="total-work-hours">0:00</td>
                                                <td id="total-holiday-hours">0:00</td>
                                                <td id="total-night-hours">0:00</td>
                                                <td id="total-holiday-night-hours">0:00</td>
                                                @if (auth()->user()->role === 'admin' || auth()->user()->role === 'hr-assistant')
                                                    <td></td>
                                                @endif
                                            </tr>
                                        </tfoot>

                                        @endif
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
                        <button type="submit" id="btnSubmitUpdateHours" class="btn btn-primary">Save changes</button>
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
                    // $('.guide-start-time').first()[0]._flatpickr.setDate(startTime);
                    // $('.guide-end-time').first()[0]._flatpickr.setDate(endTime);

                    $('.guide-start-time').first()[0]._flatpickr.setDate(new Date(startTime));
                    $('.guide-end-time').first()[0]._flatpickr.setDate(new Date(endTime));
                }
                
                $('#modal-guide-comment').val(guideComment);

                // Show the modal
                $('#editHoursModal').modal('show');
            });

            // Handle form submission via AJAX
            $('#updateHoursForm').on('submit', function(e) {
                e.preventDefault(); // Prevent default form submission

                $('#btnSubmitUpdateHours').prop('disabled', true);
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
                        $('#btnSubmitUpdateHours').prop('disabled', false);
                    },
                    error: function(xhr) {
                        const errors = xhr.responseJSON.errors;
                        let errorMessage = 'Failed to update hours:\n';
                        for (const field in errors) {
                            errorMessage += `${field}: ${errors[field].join(', ')}\n`;
                        }

                        $('#btnSubmitUpdateHours').prop('disabled', false);
                        alert(errorMessage);
                    }
                });
            });
        });
    </script>

<script>
function timeToMinutes(timeStr) {
    if (!timeStr) return 0;
    const [hours, minutes] = timeStr.split(':').map(Number);
    return (hours * 60) + (minutes || 0);
}

function minutesToTimeString(totalMinutes) {
    const hours = Math.floor(totalMinutes / 60);
    const minutes = totalMinutes % 60;
    return `${hours}:${minutes.toString().padStart(2, '0')}`;
}

function calculateTotals() {
    let totalWorkHours = 0;
    let totalHolidayHours = 0;
    let totalNightHours = 0;
    let totalHolidayNightHours = 0;

    // Sum only approved (1) and adjusted (2) rows
    document.querySelectorAll('tbody tr').forEach(row => {
        const cells = row.querySelectorAll('td');
        const statusBadge = row.querySelector('.badge');
        
        // Only include in totals if status is approved or adjusted
        if (statusBadge && 
            (statusBadge.classList.contains('badge-success') || // Approved
             statusBadge.classList.contains('badge-secondary'))) { // Adjusted
            
            totalWorkHours += timeToMinutes(cells[3].textContent);
            totalHolidayHours += timeToMinutes(cells[4].textContent);
            totalNightHours += timeToMinutes(cells[5].textContent);
            totalHolidayNightHours += timeToMinutes(cells[6].textContent);
        }
    });

    // Update totals row with calculated values
    document.getElementById('total-work-hours').textContent = minutesToTimeString(totalWorkHours);
    document.getElementById('total-holiday-hours').textContent = minutesToTimeString(totalHolidayHours);
    document.getElementById('total-night-hours').textContent = minutesToTimeString(totalNightHours);
    document.getElementById('total-holiday-night-hours').textContent = minutesToTimeString(totalHolidayNightHours);
}
// Calculate totals when page loads
document.addEventListener('DOMContentLoaded', calculateTotals);

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
