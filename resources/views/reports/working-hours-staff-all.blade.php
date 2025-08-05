@extends('partials.main')
@section('content')
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
                            <h4 class="page-title">Staff Hours Report - All Departments</h4>
                        </div>
                    </div>

                    <div class="mb-3">
                        <input type="text" id="searchInput" class="form-control" placeholder="Search by staff name...">
                    </div>

                    <form action="/staff/working-hours" method="post">
                        @csrf
                        <div class="row mb-3">
                            <div class="col-4">
                                <select name="start_date" class="form-control">
                                    <option value="2024-10-14">14/10/2024 to 16/02/2025 - (Previous Segment)</option>
                                    <option value="2025-02-17">17/02/2025 to 22/06/2025 - (Previous Segment)</option>
                                    <option selected value="2025-06-23">23/06/2025 to 26/10/2025 - (Current Segment)</option>
                                </select>
                            </div>
                            <div class="col-2">
                                <button class="btn btn-primary btn-sm" type="submit">Filter</button>
                            </div>
                        </div>
                    </form>

                    @foreach($staffByDepartment as $department => $staffMembers)
                        <div class="mb-5">
                            <h5 class="mb-3 text-primary">{{ $department }} Department</h5>
                            
                            <div class="table-responsive">
                                <table class="table table-bordered department-table" data-department="{{ $department }}">
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
                                            <th class="sortable" data-sort="number" style=" font-weight: bold;">Total Hours</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($staffMembers as $staff)
                                            @php
                                                // Calculate total hours for all 6 periods
                                                $totalHours = ($staff->working_hours['period1_hours'] ?? 0) + 
                                                             ($staff->working_hours['period2_hours'] ?? 0) + 
                                                             ($staff->working_hours['period3_hours'] ?? 0) + 
                                                             ($staff->working_hours['period4_hours'] ?? 0) + 
                                                             ($staff->working_hours['period5_hours'] ?? 0) + 
                                                             ($staff->working_hours['period6_hours'] ?? 0);
                                            @endphp
                                            <tr>
                                                <td>{{ $staff->name }}</td>
                                                @for ($i = 1; $i <= 6; $i++)
                                                    @php
                                                        $periodHours = $staff->working_hours["period{$i}_hours"] ?? 0;
                                                        $totalMinutes = $periodHours * 60;
                                                    @endphp
                                                    <td class="{{ $totalMinutes > (144 * 60) ? 'table-warning' : ($totalMinutes > (120 * 60) ? '' : '') }}">
                                                        {{ formatTime(floor($periodHours), ($periodHours - floor($periodHours)) * 60) }}
                                                    </td>
                                                @endfor
                                                <td style="font-weight: bold; {{ $totalHours > 864 ? 'color: #dc3545;' : ($totalHours > 720 ? 'color: #fd7e14;' : '') }}">
                                                    {{ formatTime(floor($totalHours), ($totalHours - floor($totalHours)) * 60) }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

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
        .department-table {
            margin-bottom: 2rem;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Search functionality
            $('#searchInput').on('keyup', function() {
                const searchValue = this.value.toLowerCase();
                
                $('.department-table tbody tr').each(function() {
                    const staffName = $(this).find('td:first').text().toLowerCase();
                    if (staffName.includes(searchValue)) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            });

            // Sorting functionality for each table
            $('.sortable').on('click', function() {
                const table = $(this).closest('table');
                const columnIndex = $(this).index();
                const isAscending = !$(this).hasClass('asc');
                
                // Remove sorting classes from all headers in this table
                table.find('.sortable').removeClass('asc desc');
                
                // Add appropriate class to clicked header
                $(this).addClass(isAscending ? 'asc' : 'desc');
                
                // Sort the table rows
                const rows = table.find('tbody tr').get();
                
                rows.sort(function(a, b) {
                    const aValue = $(a).find('td').eq(columnIndex).text().trim();
                    const bValue = $(b).find('td').eq(columnIndex).text().trim();
                    
                    if (columnIndex === 0) { // Name column
                        return isAscending ? 
                            aValue.localeCompare(bValue) : 
                            bValue.localeCompare(aValue);
                    } else { // Time columns
                        const getMinutes = (time) => {
                            const parts = time.split(':');
                            return parts.length > 1 ? 
                                parseInt(parts[0]) * 60 + parseInt(parts[1]) : 
                                parseInt(parts[0]) * 60;
                        };
                        
                        const aMinutes = getMinutes(aValue);
                        const bMinutes = getMinutes(bValue);
                        
                        return isAscending ? aMinutes - bMinutes : bMinutes - aMinutes;
                    }
                });
                
                // Append sorted rows back to table
                table.find('tbody').append(rows);
            });
        });
    </script>
@endsection