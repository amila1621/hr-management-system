@extends('partials.main')
@section('content')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/dark.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<style>
    .table-warning {
        color: black !important;
        background-color: #fa974b !important;
    }

    .table-danger {
        color: black !important;
        background-color: #f03252 !important;
    }

    .sortable {
        cursor: pointer;
    }

    .sortable::after {
        content: '\25B2\25BC';
        font-size: 0.7em;
        margin-left: 5px;
        opacity: 0.5;
    }

    .sortable.asc::after {
        content: '\25B2';
        opacity: 1;
    }

    .sortable.desc::after {
        content: '\25BC';
        opacity: 1;
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
@php
use Carbon\Carbon;
$startDate = Carbon::createFromFormat('Y-m-d', $currentweek)->startOfWeek();
@endphp
<div class="content-page">
    <div class="content">
        <div class="container-fluid">
            <div class="page-title-box">
                <form action="/updatedateForBusDrivers" method="post">
                    @csrf
                    <div style="margin-bottom: 10px;" class="row">
                        <div class="col-6">
                            <div class="col-12">
                                <h4 class="page-title">Bus Drivers - Ranking Hours </h4>
                                <p class="mb-0">Update until {{ \Carbon\Carbon::parse($updatedate->bus_driver_updated_date)->format('d/m/Y') }}</p>
                            </div>
                        </div>
                        @if(Auth::user()->role == 'team-lead')
                            
                        <div class="col-4">
                            <input type="text" value="" class="form-control flatpickr" name="bus_driver_updated_date" placeholder="Select Date...">
                        </div>
                        <div class="col-2">
                            <button class="btn btn-primary btn-sm" type="submit">Update</button>
                        </div>


                        @endauth

                    </div>
                </form>


                <div class="mb-3">
                    <input type="text" id="searchInput" class="form-control" placeholder="Search by guide name...">
                </div>
                <form action="/reports/ranking-for-hours-bus-drivers" method="post">
                    @csrf
                    <div style="margin-bottom: 10px;" class="row">
                        <div class="col-4">
                            <select name="start_date" class="form-control">
                                <option value="2024-10-14">14/10/2024 to 16/02/2025 - (Previous Segment)</option>
                                <option selected value="2025-02-17">17/02/2025 to 22/06/2025 - (Current Segment)</option>
                            </select>
                        </div>
                        <div class="col-2">
                            <button class="btn btn-primary btn-sm" type="submit">Filter</button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered" id="guideTable">
                    <thead>
                        <tr>
                            <th class="sortable" data-sort="string">Guide Name</th>

                            <th class="sortable" data-sort="number">1st 3 Week <br>
                                {{ $startDate->copy()->format('d/m') }} to
                                {{ $startDate->copy()->addWeeks(3)->subDay()->format('d/m') }}
                            </th>
                            <th class="sortable" data-sort="number">2nd 3 Week <br>
                                {{ $startDate->copy()->addWeeks(3)->format('d/m') }} to
                                {{ $startDate->copy()->addWeeks(6)->subDay()->format('d/m') }}
                            </th>
                            <th class="sortable" data-sort="number">3rd 3 Week <br>
                                {{ $startDate->copy()->addWeeks(6)->format('d/m') }} to
                                {{ $startDate->copy()->addWeeks(9)->subDay()->format('d/m') }}
                            </th>
                            <th class="sortable" data-sort="number">4th 3 Week <br>
                                {{ $startDate->copy()->addWeeks(9)->format('d/m') }} to
                                {{ $startDate->copy()->addWeeks(12)->subDay()->format('d/m') }}
                            </th>
                            <th class="sortable" data-sort="number">5th 3 Week <br>
                                {{ $startDate->copy()->addWeeks(12)->format('d/m') }} to
                                {{ $startDate->copy()->addWeeks(15)->subDay()->format('d/m') }}
                            </th>
                            <th class="sortable" data-sort="number">6th 3 Week <br>
                                {{ $startDate->copy()->addWeeks(15)->format('d/m') }} to
                                {{ $startDate->copy()->addWeeks(18)->subDay()->format('d/m') }}
                            </th>

                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($guides as $guide)
                        <tr>
                            <td>{{ $guide->name }}</td>

                            <td
                                class="{{ ($guide->working_hours['period1_hours'] ?? 0) > 144 ? 'table-warning' : (($guide->working_hours['period1_hours'] ?? 0) > 120 ? '' : '') }}">
                                {{ formatTime($guide->working_hours['period1_hours'] ?? 0) }}
                            </td>

                            <td
                                class="{{ ($guide->working_hours['period2_hours'] ?? 0) > 144 ? 'table-warning' : (($guide->working_hours['period2_hours'] ?? 0) > 120 ? '' : '') }}">
                                {{ formatTime($guide->working_hours['period2_hours'] ?? 0) }}
                            </td>

                            <td
                                class="{{ ($guide->working_hours['period3_hours'] ?? 0) > 144 ? 'table-warning' : (($guide->working_hours['period3_hours'] ?? 0) > 120 ? '' : '') }}">
                                {{ formatTime($guide->working_hours['period3_hours'] ?? 0) }}
                            </td>

                            <td
                                class="{{ ($guide->working_hours['period4_hours'] ?? 0) > 144 ? 'table-warning' : (($guide->working_hours['period4_hours'] ?? 0) > 120 ? '' : '') }}">
                                {{ formatTime($guide->working_hours['period4_hours'] ?? 0) }}
                            </td>

                            <td
                                class="{{ ($guide->working_hours['period5_hours'] ?? 0) > 144 ? 'table-warning' : (($guide->working_hours['period5_hours'] ?? 0) > 120 ? '' : '') }}">
                                {{ formatTime($guide->working_hours['period5_hours'] ?? 0) }}
                            </td>
                            <td
                                class="{{ ($guide->working_hours['period6_hours'] ?? 0) > 144 ? 'table-warning' : (($guide->working_hours['period6_hours'] ?? 0) > 120 ? '' : '') }}">
                                {{ formatTime($guide->working_hours['period6_hours'] ?? 0) }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('searchInput');
        const table = document.getElementById('guideTable');
        const rows = table.getElementsByTagName('tr');
        const headers = table.querySelectorAll('th.sortable');

        // Search functionality
        searchInput.addEventListener('keyup', function() {
            const searchTerm = searchInput.value.toLowerCase();
            for (let i = 1; i < rows.length; i++) {
                const guideName = rows[i].getElementsByTagName('td')[0].textContent.toLowerCase();
                rows[i].style.display = guideName.includes(searchTerm) ? '' : 'none';
            }
        });

        // Sorting functionality
        headers.forEach(header => {
            header.addEventListener('click', function() {
                const column = this.cellIndex;
                const type = this.dataset.sort;
                const tbody = table.querySelector('tbody');
                const rowsArray = Array.from(tbody.querySelectorAll('tr'));

                const isAscending = !this.classList.contains('asc');

                // Remove sorting classes from all headers
                headers.forEach(h => h.classList.remove('asc', 'desc'));

                // Add appropriate class to clicked header
                this.classList.add(isAscending ? 'asc' : 'desc');

                rowsArray.sort((a, b) => {
                    const aValue = a.cells[column].textContent.trim();
                    const bValue = b.cells[column].textContent.trim();

                    if (type === 'number') {
                        // Convert time format to minutes for sorting
                        const getMinutes = (time) => {
                            const parts = time.split(':');
                            return parts.length > 1 ? parseInt(parts[0]) * 60 +
                                parseInt(parts[1]) : parseInt(parts[0]) * 60;
                        };
                        return isAscending ? getMinutes(aValue) - getMinutes(bValue) :
                            getMinutes(bValue) - getMinutes(aValue);
                    } else {
                        return isAscending ? aValue.localeCompare(bValue) : bValue
                            .localeCompare(aValue);
                    }
                });

                // Reorder the rows in the table body
                rowsArray.forEach(row => tbody.appendChild(row));
            });
        });
    });

    flatpickr(".flatpickr", {
        dateFormat: "Y-m-d",
        theme: "dark",
        allowInput: true,
        altInput: true,
        altFormat: "d/m/Y",
    });
</script>
@endsection