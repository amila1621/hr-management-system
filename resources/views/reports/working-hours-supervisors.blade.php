@extends('partials.main')
@section('content')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/dark.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <style>
        .table-warning { color: black !important; background-color: #fa974b !important; }
        .table-danger { color: black !important; background-color: #f03252 !important; }
        .sortable { cursor: pointer; }
        .sortable::after {
            content: '\25B2\25BC';
            font-size: 0.7em;
            margin-left: 5px;
            opacity: 0.5;
        }
        .sortable.asc::after { content: '\25B2'; opacity: 1; }
        .sortable.desc::after { content: '\25BC'; opacity: 1; }
    </style>

    @php
        if (!function_exists('formatTime')) {
            function formatTime($hours, $minutes = 0, $isSickLeave = false) {
                if ($hours === 0 && $minutes === 0) {
                    return $isSickLeave ? '0:00 - Sick Leave' : '0:00';
                }
                
                $totalMinutes = ($hours * 60) + $minutes;
                $finalHours = floor($totalMinutes / 60);
                $finalMinutes = $totalMinutes % 60;
                
                $timeString = sprintf('%d:%02d', $finalHours, $finalMinutes);
                return $isSickLeave ? $timeString . ' - Sick Leave' : $timeString;
            }
        }
    @endphp

    <div class="content-page">
        <div class="content">
            <div class="container-fluid">
                <div class="page-title-box">
                    <div class="row align-items-center mb-3">
                        <div class="col-12">
                            <h4 class="page-title">Hours Report</h4>
                        </div>
                    </div>

                    <div class="mb-3">
                        <input type="text" id="searchInput" class="form-control" placeholder="Search by staff name...">
                    </div>

                    <form action="/reports/supervisor-working-hours" method="post">
                        @csrf
                        <div class="row mb-3">
                            <div class="col-4">
                                <select name="start_date" class="form-control">
                                    <option value="2024-10-14">14/10/2024 to 16/02/2025 - (Previous Segment)</option>
                                    <option selected value="2025-02-17">17/02/2025 to 22/06/2025 - (Previous Segment)</option>
                                    <option selected value="2025-06-23">23/06/2025 to 26/10/2025 - (Current Segment)</option>
                                </select>
                            </div>
                            <div class="col-2">
                                <button class="btn btn-primary btn-sm" type="submit">Filter</button>
                            </div>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-bordered" id="staffTable">
                            <thead>
                                <tr>
                                    <th class="sortable" data-sort="string">Staff Name</th>
                                    @for ($i = 1; $i <= 6; $i++)
                                        <th class="sortable" data-sort="number">
                                            {{ $i }}{{ substr(date('jS', mktime(0, 0, 0, 1, $i, 2000)), -2) }} 3 Weeks<br>
                                            {{ $startDate->copy()->addWeeks(($i-1)*3)->format('d/m') }} to
                                            {{ $startDate->copy()->addWeeks($i*3)->subDay()->format('d/m') }}
                                        </th>
                                    @endfor
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($assignedEmployees as $employee)
                                    <tr>
                                        <td>{{ $employee->name }}</td>
                                        @for ($i = 1; $i <= 6; $i++)
                                            @php
                                                $periodHours = $employee->working_hours["period{$i}_hours"] ?? 0;
                                                $periodMinutes = $employee->working_hours["period{$i}_minutes"] ?? 0;
                                                $isSickLeave = $employee->working_hours["period{$i}_sick_leave"] ?? false;
                                                
                                                // If it's sick leave, don't count these hours in the total
                                                $displayHours = $isSickLeave ? 0 : $periodHours;
                                                $displayMinutes = $isSickLeave ? 0 : $periodMinutes;
                                                
                                                // For cell background color, calculate total minutes
                                                $totalMinutes = ($displayHours * 60) + $displayMinutes;
                                            @endphp
                                            <td class="{{ $totalMinutes > (144 * 60) ? 'table-warning' : ($totalMinutes > (120 * 60) ? '' : '') }}">
                                                {{ formatTime($displayHours, $displayMinutes, $isSickLeave) }}
                                            </td>
                                        @endfor
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Search functionality
            const searchInput = document.getElementById('searchInput');
            const table = document.getElementById('staffTable');
            
            searchInput.addEventListener('keyup', function() {
                const searchTerm = searchInput.value.toLowerCase();
                const rows = table.getElementsByTagName('tr');
                
                for (let i = 1; i < rows.length; i++) {
                    const staffName = rows[i].getElementsByTagName('td')[0].textContent.toLowerCase();
                    rows[i].style.display = staffName.includes(searchTerm) ? '' : 'none';
                }
            });

            // Sorting functionality
            const headers = table.querySelectorAll('th.sortable');
            headers.forEach(header => {
                header.addEventListener('click', function() {
                    const column = this.cellIndex;
                    const type = this.dataset.sort;
                    const tbody = table.querySelector('tbody');
                    const rows = Array.from(tbody.querySelectorAll('tr'));
                    const isAscending = !this.classList.contains('asc');

                    headers.forEach(h => h.classList.remove('asc', 'desc'));
                    this.classList.add(isAscending ? 'asc' : 'desc');

                    rows.sort((a, b) => {
                        const aValue = a.cells[column].textContent.trim();
                        const bValue = b.cells[column].textContent.trim();

                        if (type === 'number') {
                            const getMinutes = (time) => {
                                // Remove " - Sick Leave" if present and get just the time part
                                const timePart = time.split(' - ')[0];
                                const parts = timePart.split(':');
                                return parts.length > 1 ? parseInt(parts[0]) * 60 + parseInt(parts[1]) : parseInt(parts[0]) * 60;
                            };
                            return isAscending ? getMinutes(aValue) - getMinutes(bValue) : getMinutes(bValue) - getMinutes(aValue);
                        } else {
                            return isAscending ? aValue.localeCompare(bValue) : bValue.localeCompare(aValue);
                        }
                    });

                    rows.forEach(row => tbody.appendChild(row));
                });
            });

            // Initialize flatpickr
            flatpickr(".flatpickr", {
                dateFormat: "Y-m-d",
                theme: "dark",
                allowInput: true,
                altInput: true,
                altFormat: "d/m/Y",
            });
        });
    </script>
@endsection