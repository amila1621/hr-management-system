@extends('partials.main')

<?php
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

if (!function_exists('addTime')) {
    function addTime($total, $time)
    {
        $hours = floor($time);
        $minutes = round(($time - $hours) * 100);

        $totalHours = floor($total);
        $totalMinutes = round(($total - $totalHours) * 100);

        $newTotalMinutes = $totalMinutes + $minutes;
        $additionalHours = floor($newTotalMinutes / 60);
        $newTotalMinutes %= 60;

        return $totalHours + $hours + $additionalHours + $newTotalMinutes / 100;
    }
}

$totalNormalHours = 0;
$totalNormalNightHours = 0;
$totalHolidayHours = 0;
$totalHolidayNightHours = 0;
?>

@section('content')
    <div class="content-page">
        <div class="content">
            <div class="container-fluid">
                @if (session()->has('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                @endif
                
                
                <!-- Add year and month selection form -->
                <div class="row mb-3" style="z-index: 1000; position: fixed; top: -6px; margin: 0px 40px 10px 20px;">
                    <h4 class="page-title" style="margin: 25px 40px 0px 20px;">
                       @if (auth()->user()->role === 'team-lead')
                            Bus Driver Report - {{ $tourGuide->name }}

                        @else
                            {{ $tourGuide->name }} - Guide Report
                        @endif
                    </h4>

                    <div class="col-md-6" style="margin-top: 23px;">
                        <form action="{{ route('guide.report-by-month', ['guideId' => ':guideId']) }}" method="GET"
                            class="form-inline" style="flex-flow: initial;" id="guideReportForm">
                            <div class="form-group mr-2">
                                <label for="year" class="mr-2">Guide:</label>
                                <select name="guide_id" id="guide_id" class="form-control" onchange="this.form.submit()">
                                    @foreach ($tourGuides as $guide)
                                        <option value="{{ $guide->id }}"
                                            {{ $guide->id == $tourGuide->id ? 'selected' : '' }}>{{ $guide->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group mr-2">
                                <label for="year" class="mr-2">Year:</label>
                                <select name="year" id="year" class="form-control" onchange="this.form.submit()">
                                    @for ($i = date('Y'); $i >= date('Y') - 5; $i--)
                                        <option value="{{ $i }}"
                                            {{ request('year', date('Y')) == $i ? 'selected' : '' }}>{{ $i }}
                                        </option>
                                    @endfor
                                </select>
                            </div>
                            <div class="form-group mr-2">
                                <label for="month" class="mr-2">Month:</label>
                                <select name="month" id="month" class="form-control" onchange="this.form.submit()">
                                    @foreach (range(1, 12) as $month)
                                        <option value="{{ $month }}"
                                            {{ request('month', date('n')) == $month ? 'selected' : '' }}>
                                            {{ date('F', mktime(0, 0, 0, $month, 1)) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                
                                <div style="max-height: 600px; overflow-y: auto;">
                                    <div class="table" style="margin: 10px 0px 10px 0px;">
                                        <style>
                                            th, tr {
                                                text-align: center;
                                                align-content: center;
                                            }

                                            #tour-name-column {
                                                width: 22%;
                                            }
                                        </style>
  
                                        <table id="datatable-buttons" class="table table-striped table-bordered"
                                            style="display: none; border-collapse: collapse; border-spacing: 0; width: 100%">
                                            <thead>
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Start Time</th>
                                                    <th>End Time</th>
                                                    <th id="tour-name-column">Tour Name</th>
                                                    <th>Work Hours</th>
                                                    <th>Work hours <br> ONLY with <br> holiday extras</th>
                                                    <th>Night supplement <br> hours(20-6)</th>
                                                    <th>Night supplement <br> ONLY with <br> holiday extras</th>
                                                    @if (auth()->user()->role === 'admin' || auth()->user()->role === 'hr-assistant')
                                                        <th>Action</th>
                                                    @endif
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($eventSalaries as $date => $dailySalaries)
                                                    {{-- You can add a date header here if needed --}}
                                                    {{-- <h3>{{ \Carbon\Carbon::parse($date)->format('d.m.Y') }}</h3> --}}
                                                    
                                                    @foreach($dailySalaries as $salary)
                                                    <tr data-event-id="{{ $salary->event->id }}" data-salary-id="{{ $salary->id }}">
                                                        <td>
                                                            @if ($salary->guide_start_time)
                                                                {{ \Carbon\Carbon::parse($salary->guide_start_time)->format('d') }}
                                                                <!-- <br> -->
                                                                {{ \Carbon\Carbon::parse($salary->guide_start_time)->format('D.') }}
                                                            @elseif($salary->event->start_time)
                                                                {{ \Carbon\Carbon::parse($salary->event->start_time)->format('d') }}
                                                                <!-- <br> -->
                                                                {{ \Carbon\Carbon::parse($salary->event->start_time)->format('D.') }}
                                                            @else
                                                                N/A
                                                            @endif
                                                        </td>
                                                        <td>
                                                            @if (!empty($salary->guide_start_time))
                                                                {{ $salary->guide_start_time->format('d.m.Y H:i') }}
                                                            @else
                                                                N/A
                                                            @endif
                                                        </td>
                                                        <td>
                                                            @if (!empty($salary->guide_end_time))
                                                                {{ $salary->guide_end_time->format('d.m.Y H:i') }}
                                                            @else
                                                                N/A
                                                            @endif
                                                        </td>
                                                        <td @if(Str::startsWith($salary->event->event_id, 'manual')) style="color: #da76de;" @endif>{{ $salary->event->name }}</td>
                                                        <td>{{ formatTime($salary->normal_hours) }}</td>
                                                        <td>{{ formatTime($salary->holiday_hours) }}</td>
                                                        <td>{{ formatTime($salary->normal_night_hours) }}</td>
                                                        <td>{{ formatTime($salary->holiday_night_hours) }}</td>
                                                        @if (auth()->user()->role === 'admin' || auth()->user()->role === 'hr-assistant')
                                                            <td>
                                                                <button class="fa fa-pen btn btn-primary btn-bg" title="Update Hours"
                                                                    onclick="openManualEntryModal('{{ $salary->event->id }}', '{{ $salary->guide_start_time }}', '{{ $salary->guide_end_time }}')">
                                                                </button>
                                                                <button class="fa fa-plus btn btn-success btn-bg" title="Add Extra Hours"
                                                                    onclick="openExtraHoursModal('{{ $salary->id }}')"></button>
                                                                
                                                                <a href="{{ route('work-hours.delete', $salary->id) }}"
                                                                    class="fa fa-trash btn btn-danger btn-bg"
                                                                    onclick="return confirm('Are you sure you want to delete this entry?')"></a>
                                                            </td>
                                                        @endif
                                                    </tr>

                                                    @php
                                                        $totalNormalHours = addTime(
                                                            $totalNormalHours,
                                                            $salary->normal_hours,
                                                        );
                                                        $totalNormalNightHours = addTime(
                                                            $totalNormalNightHours,
                                                            $salary->normal_night_hours,
                                                        );
                                                        $totalHolidayHours = addTime(
                                                            $totalHolidayHours,
                                                            $salary->holiday_hours,
                                                        );
                                                        $totalHolidayNightHours = addTime(
                                                            $totalHolidayNightHours,
                                                            $salary->holiday_night_hours,
                                                        );
                                                    @endphp
                                                    @endforeach
                                                @endforeach
                                            </tbody>
                                            @if (auth()->user()->role === 'admin')
                                                <tfoot>
                                                    <tr>
                                                        <th colspan="2">Total</th>
                                                        <th></th>
                                                        <th></th>
                                                        <th>{{ formatTime($totalNormalHours) }}</th>
                                                        <th>{{ formatTime($totalHolidayHours) }}</th>
                                                        <th>{{ formatTime($totalNormalNightHours) }}</th>
                                                        <th>{{ formatTime($totalHolidayNightHours) }}</th>
                                                        <th></th>
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
    </div>

    <!-- Manual Entry Modal -->
    <div class="modal fade" id="manualEntryModal" tabindex="-1" role="dialog" aria-labelledby="manualEntryModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="manualEntryModalLabel">Update Tour Guide Hours</h5>
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
                        <button type="button" class="btn btn-secondary" onclick="addGuideEntry()">Add Another
                            Guide</button>
                        <button type="submit" class="btn btn-primary">Submit</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Extra Hours Modal -->
    <div class="modal fade" id="extraHoursModal" tabindex="-1" role="dialog" aria-labelledby="extraHoursModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="extraHoursModalLabel">Add Extra Hours</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="extraHoursForm" action="{{ route('salary.add-extra-hours') }}" method="POST">
                        @csrf
                        <input type="hidden" name="salary_id" id="extraHoursSalaryId">
                        <div class="form-group">
                            <label for="extra_time">Extra Time</label>
                            <input value="00:00" type="time" class="form-control" id="extra_time" name="extra_time"
                                step="60" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Add Hours</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        var tourGuides = @json($tourGuides);

        function addGuideEntry(startTime = null, endTime = null, guideId = null) {
            var guideFieldsContainer = document.getElementById('guideFieldsContainer');
            var guideCount = guideFieldsContainer.getElementsByClassName('guide-entry').length;

            // Create the container div first
            var entryDiv = document.createElement('div');
            entryDiv.className = 'card mb-3 guide-entry';

            // Create the HTML content
            entryDiv.innerHTML = `
                <div class="card-body">
                    <div class="form-group">
                        <label for="guideName">Select Guide</label>
                        <select name="guides[${guideCount}][name]" class="form-control" required>
                            <option value="" disabled>Select Guide</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="startTime">Start Time</label>
                        <input type="text" name="guides[${guideCount}][startTime]" class="form-control flatpickr-datetime" value="${startTime || ''}" required>
                    </div>
                    <div class="form-group">
                        <label for="endTime">End Time</label>
                        <input type="text" name="guides[${guideCount}][endTime]" class="form-control flatpickr-datetime" value="${endTime || ''}" required>
                    </div>
                </div>
            `;

            // Get the select element
            var selectElement = entryDiv.querySelector('select');

            // Add options to select
            tourGuides.forEach(function(guide) {
                var option = document.createElement('option');
                option.value = guide.id;
                option.textContent = guide.name;

                if (parseInt(guide.id) === parseInt(guideId)) {
                    option.selected = true;
                    console.log('Setting selected guide:', guide.name, 'ID:', guide.id);
                }
                selectElement.appendChild(option);
            });

            // Append the entire div to the container
            guideFieldsContainer.appendChild(entryDiv);

            // Initialize Flatpickr for the new datetime inputs
            var newDateInputs = entryDiv.getElementsByClassName('flatpickr-datetime');
            Array.from(newDateInputs).forEach(input => {
                flatpickr(input, {
                    enableTime: true,
                    dateFormat: "Y-m-d H:i",
                    time_24hr: true,
                    minuteIncrement: 1,
                    defaultDate: input.value || new Date()
                });
            });
        }

        function openManualEntryModal(eventId, startTime, endTime) {
            document.getElementById('manualEntryEventId').value = eventId;
            document.getElementById('guideFieldsContainer').innerHTML = ''; // Clear existing entries

            const urlParams = new URLSearchParams(window.location.search);
            const currentGuideId = urlParams.get('guide_id');
            console.log('URL Guide ID:', currentGuideId);

            addGuideEntry(startTime, endTime, currentGuideId);
            $('#manualEntryModal').modal('show');
        }

        function openExtraHoursModal(salaryId) {
            document.getElementById('extraHoursSalaryId').value = salaryId;
            $('#extraHoursModal').modal('show');
        }

        function updateFormAction(guideId) {
            const form = document.getElementById('guideReportForm');
            const newAction = form.action.replace(':guideId', guideId);
            form.action = newAction;
        }

        // Initialize the form action with the current guide ID
        document.addEventListener('DOMContentLoaded', function() {
            updateFormAction(document.getElementById('guide_id').value);
        });

        // Add these new AJAX handlers
        document.getElementById('manualEntryForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            fetch("{{ route('salary.manual-calculation-ajax') }}", {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
                body: new FormData(this)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    $('#manualEntryModal').modal('hide');
                    
                    // Find the row(s) for this event and update them
                    const eventId = document.getElementById('manualEntryEventId').value;
                    const rows = document.querySelectorAll(`tr[data-event-id="${eventId}"]`);
                    
                    // Update each row with new data
                    rows.forEach((row, index) => {
                        const updatedData = data.data[index];
                        if (updatedData) {
                            // Update the cells with new values
                            row.querySelector('td:nth-child(3)').textContent = updatedData.start_time;
                            row.querySelector('td:nth-child(4)').textContent = updatedData.end_time;
                            row.querySelector('td:nth-child(5)').textContent = updatedData.normal_hours;
                            row.querySelector('td:nth-child(6)').textContent = updatedData.holiday_hours;
                            row.querySelector('td:nth-child(7)').textContent = updatedData.normal_night_hours;
                            row.querySelector('td:nth-child(8)').textContent = updatedData.holiday_night_hours;
                        }
                        
                        // Add highlight class
                        row.classList.add('highlight-update');
                    });
                } else {
                    alert('Error updating hours: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while updating hours');
            });
        });

        document.getElementById('extraHoursForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            fetch("{{ route('salary.add-extra-hours-ajax') }}", {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
                body: new FormData(this)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    $('#extraHoursModal').modal('hide');
                    
                    // Find the row(s) for this event and update them
                    const salaryId = document.getElementById('extraHoursSalaryId').value;
                    const row = document.querySelector(`tr[data-salary-id="${salaryId}"]`);
                    
                    if (row && data.data[0]) {
                        const updatedData = data.data[0];
                        // Update the cells with new values
                        row.querySelector('td:nth-child(3)').textContent = updatedData.start_time;
                        row.querySelector('td:nth-child(4)').textContent = updatedData.end_time;
                        row.querySelector('td:nth-child(5)').textContent = updatedData.normal_hours;
                        row.querySelector('td:nth-child(6)').textContent = updatedData.holiday_hours;
                        row.querySelector('td:nth-child(7)').textContent = updatedData.normal_night_hours;
                        row.querySelector('td:nth-child(8)').textContent = updatedData.holiday_night_hours;
                        
                        // Add highlight class
                        row.classList.add('highlight-update');
                    }
                } else {
                    alert('Error adding extra hours: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while adding extra hours');
            });
        });

        document.addEventListener("DOMContentLoaded", function () {
            const table = document.getElementById("datatable-buttons");
            table.style.display = "table";
            const rows = table.querySelectorAll("tbody tr");

            for (let i = 0; i < rows.length; i++) {
                const currentRow = rows[i];
                const nextRow = rows[i + 1];

                // Check if the next row exists and has the same "Category"
                if ( nextRow &&
                    currentRow.cells[0].textContent === nextRow.cells[0].textContent
                ) {
                    // Merge rows by using rowspan
                    let rowspan = 1;
                    for (let j = i + 1; j < rows.length; j++) {
                        if (currentRow.cells[0].textContent === rows[j].cells[0].textContent) {
                            rowspan++;
                            rows[j].cells[0].style.display = "none"; // Keep cell empty
                        } else {
                            break;
                        }
                    }
                    currentRow.cells[0].rowSpan = rowspan;
                }
            }

         });

    </script>
@endsection

@section('scripts')
    <!-- Include the DataTables and Buttons scripts -->
    <script src="https://cdn.datatables.net/1.10.25/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/1.7.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/1.7.1/js/buttons.flash.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/1.7.1/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/1.7.1/js/buttons.print.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>

    <script>
        $(document).ready(function() {
            $('#datatable-buttons').DataTable({
                dom: 'Bfrtip',
                buttons: [
                    'copy', 'csv', 'excel', 'pdf', 'print'
                ],
                paging: false,
                ordering: false,
                info: false,
                columnDefs: [{
                    orderable: false,
                    targets: '_all'
                }],
                order: []
            });

            // Remove sorting classes and click events from headers
            $('#datatable-buttons thead th').removeClass('sorting sorting_asc sorting_desc');
            $('#datatable-buttons thead th').off('click.dt');
        });
    </script>
@endsection

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<style>
    /* Remove fixed heights and overflow restrictions */
    body {
        overflow-y: auto !important;
        font-size: 14px;
        -webkit-overflow-scrolling: touch;
    }

    /* Content page adjustments */
    .content-page {
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        margin-bottom: 60px;
        overflow: visible !important;
    }

    .content {
        flex: 1;
        padding-bottom: 80px;
        overflow: visible !important;
    }

    /* Table adjustments - remove scrolling */
    .table-responsive {
        max-height: none !important;
        overflow: visible !important;
    }

    .table {
        height: auto !important;
        margin-bottom: 20px;
    }

    /* Card adjustments */
    .card {
        margin-bottom: 20px;
    }

    .card-body {
        padding-bottom: 30px;
        overflow: visible !important;
        max-height: none !important;
    }

    /* Remove any fixed positioning */
    #wrapper {
        height: auto !important;
        min-height: 100vh;
        position: relative;
    }

    /* DataTables specific adjustments */
    #datatable-buttons td,
    #datatable-buttons th {
        padding: 8px 6px;
        font-size: 12px;
    }

    /* Remove any overflow restrictions */
    .container-fluid {
        overflow: visible !important;
    }

    /* Remove any max-height restrictions */
    div[style*="max-height"] {
        max-height: none !important;
        overflow: visible !important;
    }

    @keyframes highlightFade {
        0% { background-color: #fff3cd; }
        100% { background-color: transparent; }
    }

    .highlight-update {
        background-color: #325054 !important; /* Using Bootstrap's success color */
    }

    #datatable-buttons_filter label {
        color: white;
    }
</style>