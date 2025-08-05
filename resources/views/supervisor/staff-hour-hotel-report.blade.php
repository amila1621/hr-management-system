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

// Helper function to add time in HH:MM format
if (!function_exists('sumTimes')) {
    function sumTimes($times) {
        $totalMinutes = 0;
        foreach ($times as $time) {
            if (empty($time) || $time == '00:00') continue;
            
            $parts = explode(':', $time);
            $hours = (int)$parts[0];
            $minutes = isset($parts[1]) ? (int)$parts[1] : 0;
            $totalMinutes += ($hours * 60) + $minutes;
        }
        
        $hours = floor($totalMinutes / 60);
        $minutes = $totalMinutes % 60;
        
        return sprintf('%02d:%02d', $hours, $minutes);
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
                    Hotel Staff Report - {{ $selectedStaff->name }}
                </h4>

                <div class="col-md-6" style="margin-top: 23px;">
                    @if(Auth::user()->role == 'admin')
                   
                <div class="col-md-6" style="margin-top: 23px;">
                    <form action="{{ route('reports.getOfficeStaffWiseReport') }}" method="GET" class="form-inline" style="flex-flow: initial;">
                        <div class="form-group mr-2">
                            <?php 
                            
                            $staffs = \App\Models\StaffUser::get();
                            $staff_id = request()->get('staff_id', null);
                            
                            ?>
                            <label for="staff_id" class="mr-2">Office Staff:</label>
                            <select name="staff_id" id="staff_id" class="form-control" onchange="this.form.submit()">
                                @foreach($staffs as $staff)
                                    <option value="{{ $staff->id }}" {{ (isset($staff_id) && $staff_id == $staff->id) ? 'selected' : '' }}>
                                        {{ $staff->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group mr-2">
                            <label for="year" class="mr-2">Year:</label>
                            <select name="year" id="year" class="form-control" onchange="this.form.submit()">
                                @for ($i = date('Y'); $i >= date('Y') - 1; $i--)
                                    <option value="{{ $i }}" {{ $year == $i ? 'selected' : '' }}>
                                        {{ $i }}
                                    </option>
                                @endfor
                            </select>
                        </div>
                        <div class="form-group mr-2">
                            <label for="month" class="mr-2">Month:</label>
                            <select name="month" id="month" class="form-control" onchange="this.form.submit()">
                                @foreach (range(1, 12) as $m)
                                    <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>
                                        {{ date('F', mktime(0, 0, 0, $m, 1)) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </form>
                </div>

                @else 

                     <div class="col-md-6" style="margin-top: 23px;">
                    <form action="{{ route('staff.hours-report') }}" method="GET" class="form-inline" style="flex-flow: initial;">
                        <div class="form-group mr-2">
                            <label for="year" class="mr-2">Year:</label>
                            <select name="year" id="year" class="form-control" onchange="this.form.submit()">
                                @for ($i = date('Y'); $i >= date('Y') - 1; $i--)
                                    <option value="{{ $i }}" {{ $year == $i ? 'selected' : '' }}>
                                        {{ $i }}
                                    </option>
                                @endfor
                            </select>
                        </div>
                        <div class="form-group mr-2">
                            <label for="month" class="mr-2">Month:</label>
                            <select name="month" id="month" class="form-control" onchange="this.form.submit()">
                                @foreach (range(1, 12) as $m)
                                    <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>
                                        {{ date('F', mktime(0, 0, 0, $m, 1)) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </form>
                </div>

                @endif
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">

                            <div style="max-height: 600px; overflow-y: auto;">
                                <div class="table" style="margin: 10px 0px 10px 0px;">
                                    <style>
                                        th,
                                        tr {
                                            text-align: center;
                                            align-content: center;
                                        }

                                        #tour-name-column {
                                            width: 22%;
                                        }
                                    </style>

                                    <table id="datatable-buttons" class="table table-striped table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Staff Name</th>
                                                <th>Date</th>
                                                <th>Start Time</th>
                                                <th>End Time</th>
                                                <th>Work Hours</th>
                                                <th>Hours with holiday extras</th>
                                                <th>Evening supplement hours(18-0)</th>
                                                <th>Evening supplement with holiday extras</th>
                                                <th>Night supplement hours(0-6)</th>
                                                <th>Night supplement with holiday extras</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($staffResults[$selectedStaff->id]['records'] as $record)
                                            <tr>
                                                <td>{{ $selectedStaff->name }}</td>
                                                <td>{{ Carbon\Carbon::parse($record['date'])->format('d/m/Y') }}</td>
                                                <td>{{ $record['start_time'] }}</td>
                                                <td>{{ $record['end_time'] }}</td>
                                                <td>{{ $record['work_hours'] }}</td>
                                                <td>{{ $record['holiday_hours'] }}</td>
                                                <td>{{ $record['evening_hours'] }}</td>
                                                <td>{{ $record['evening_holiday_hours'] }}</td>
                                                <td>{{ $record['night_hours'] }}</td>
                                                <td>{{ $record['night_holiday_hours'] }}</td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <th colspan="4">Total</th>
                                                <th>{{ sumTimes(collect($staffResults[$selectedStaff->id]['records'])->pluck('work_hours')->toArray()) }}</th>
                                                <th>{{ sumTimes(collect($staffResults[$selectedStaff->id]['records'])->pluck('holiday_hours')->toArray()) }}</th>
                                                <th>{{ sumTimes(collect($staffResults[$selectedStaff->id]['records'])->pluck('evening_hours')->toArray()) }}</th>
                                                <th>{{ sumTimes(collect($staffResults[$selectedStaff->id]['records'])->pluck('evening_holiday_hours')->toArray()) }}</th>
                                                <th>{{ sumTimes(collect($staffResults[$selectedStaff->id]['records'])->pluck('night_hours')->toArray()) }}</th>
                                                <th>{{ sumTimes(collect($staffResults[$selectedStaff->id]['records'])->pluck('night_holiday_hours')->toArray()) }}</th>
                                            </tr>
                                        </tfoot>
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



<script>
    var staffMembers = @json($staffs);

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

    function updateFormAction(staffId) {
        const form = document.getElementById('staffReportForm');
        const url = new URL(form.action);
        url.searchParams.set('staff_id', staffId);
        form.action = url.toString();
    }

    // Initialize the form action with the current staff ID
    document.addEventListener('DOMContentLoaded', function() {
        updateFormAction(document.getElementById('staff_id').value);
    });


    document.addEventListener("DOMContentLoaded", function() {
        const table = document.getElementById("datatable-buttons");
        table.style.display = "table";
        const rows = table.querySelectorAll("tbody tr");

        for (let i = 0; i < rows.length; i++) {
            const currentRow = rows[i];
            const nextRow = rows[i + 1];

            // Check if the next row exists and has the same "Category"
            if (nextRow &&
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
            ordering: true,
            info: false,
            columnDefs: [{
                    type: 'time',
                    targets: [2, 3]
                },
                {
                    type: 'num',
                    targets: [4, 5, 6, 7, 8, 9]
                }
            ]
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
        0% {
            background-color: #917006;
        }

        100% {
            background-color: transparent;
        }
    }

    .highlight-update {
        background-color: #325054 !important;
        /* Using Bootstrap's success color */
    }

    #datatable-buttons_filter label {
        color: white;
    }

    .navbar-right {
        margin-top: 15px;
    }
</style>