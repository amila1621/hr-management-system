@extends('partials.main')

@php
if (!function_exists('formatTime')) {
    function formatTime($hours) {
        $wholeHours = floor($hours);
        $fractionalHours = $hours - $wholeHours;
        $minutes = round($fractionalHours * 100);
        
        $totalMinutes = $wholeHours * 60 + $minutes;
        $finalHours = floor($totalMinutes / 60);
        $finalMinutes = $totalMinutes % 60;
        
        if ($finalMinutes == 0) {
            return $finalHours;
        } else {
            return sprintf("%d:%02d", $finalHours, $finalMinutes);
        }
    }
}
@endphp

@section('content')
    <div class="content-page">
        <div class="content">
            <div class="container-fluid">
                <h4 class="page-title">{{ $tourGuide->name }} - Guide Report</h4>

                <!-- Add year and month selection form -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <form action="{{ route('guide.report-by-month', $tourGuide->id) }}" method="GET" class="form-inline">
                            <div class="form-group mr-2">
                                <label for="year" class="mr-2">Year:</label>
                                <select name="year" id="year" class="form-control">
                                    @for ($i = date('Y'); $i >= date('Y') - 5; $i--)
                                        <option value="{{ $i }}" {{ request('year', date('Y')) == $i ? 'selected' : '' }}>{{ $i }}</option>
                                    @endfor
                                </select>
                            </div>
                            <div class="form-group mr-2">
                                <label for="month" class="mr-2">Month:</label>
                                <select name="month" id="month" class="form-control">
                                    @foreach (range(1, 12) as $month)
                                        <option value="{{ $month }}" {{ request('month', date('n')) == $month ? 'selected' : '' }}>
                                            {{ date('F', mktime(0, 0, 0, $month, 1)) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary">Filter</button>
                        </form>
                    </div>
                </div>

                <h5>Report for: {{ date('F Y', mktime(0, 0, 0, request('month', date('n')), 1, request('year', date('Y')))) }}</h5>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="mt-0 header-title">Tours and Hours Worked</h4>

                                <div class="table">
                                    <table id="datatable-buttons"
                                        class="table table-striped table-bordered"
                                        style="border-collapse: collapse; border-spacing: 0; width: 100%">
                                        <thead>
                                            <tr>
                                                <th>Tour Date</th>
                                                <th>Tour Name</th>
                                                <th>Start Time</th>
                                                <th>End Time</th>
                                                <th>Work Hours</th>
                                                <th>Work hours ONLY with holiday extras</th>
                                                <th>Night supplement hours(20-6)</th>
                                                <th>Night supplement ONLY with holiday extras</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php
                                                $totalNormalHours = 0;
                                                $totalNormalNightHours = 0;
                                                $totalHolidayHours = 0;
                                                $totalHolidayNightHours = 0;
                                            @endphp

                                            @foreach ($eventSalaries as $salary)
                                                <tr>
                                                    <td>{{ \Carbon\Carbon::parse($salary->event->start_time)->format('d.m.Y') }}</td>
                                                    <td>{{ $salary->event->name }}</td>
                                                    <td>
                                                        @if(!empty($salary->guide_start_time))
                                                        {{ $salary->guide_start_time->format('d.m.Y H:i') }}
                                                    @else
                                                        N/A
                                                    @endif</td>
                                                    <td>@if(!empty($salary->guide_end_time))
                                                        {{ $salary->guide_end_time->format('d.m.Y H:i') }}
                                                    @else
                                                        N/A
                                                    @endif</td>
                                                    <td>{{ number_format($salary->normal_hours, 2, '.', '') }}</td>
                                                    <td>{{ number_format($salary->holiday_hours, 2, '.', '') }}</td>
                                                    <td>{{ number_format($salary->normal_night_hours, 2, '.', '') }}</td>
                                                    <td>{{ number_format($salary->holiday_night_hours, 2, '.', '') }}</td>
                                                    <td>
                                                        <button class="btn btn-primary btn-sm" onclick="openManualEntryModal('{{ $salary->event->id }}')">Update Hours</button>
                                                    </td>
                                                </tr>

                                                @php
                                                    $totalNormalHours += $salary->normal_hours;
                                                    $totalNormalNightHours += $salary->normal_night_hours;
                                                    $totalHolidayHours += $salary->holiday_hours;
                                                    $totalHolidayNightHours += $salary->holiday_night_hours;
                                                @endphp
                                            @endforeach
                                        </tbody>
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
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Manual Entry Modal -->
    <div class="modal fade" id="manualEntryModal" tabindex="-1" role="dialog" aria-labelledby="manualEntryModalLabel" aria-hidden="true">
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
                        <button type="button" class="btn btn-secondary" onclick="addGuideEntry()">Add Another Guide</button>
                        <button type="submit" class="btn btn-primary">Submit</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        var tourGuides = @json($tourGuides);

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
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="startTime">Start Time</label>
                            <input type="datetime-local" name="guides[${guideCount}][startTime]" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="endTime">End Time</label>
                            <input type="datetime-local" name="guides[${guideCount}][endTime]" class="form-control" required>
                        </div>
                    </div>
                </div>
            `;
            var tempDiv = document.createElement('div');
            tempDiv.innerHTML = newGuideEntry;
            var selectElement = tempDiv.querySelector('select');
            
            tourGuides.forEach(function(guide) {
                var option = document.createElement('option');
                option.value = guide.id;
                option.textContent = guide.name;
                selectElement.appendChild(option);
            });
            
            guideFieldsContainer.insertAdjacentHTML('beforeend', tempDiv.innerHTML);
        }
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
                info: false
            });
        });
    </script>
@endsection

