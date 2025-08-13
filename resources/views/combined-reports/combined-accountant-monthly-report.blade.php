@extends('partials.main')

@section('content')
<div class="content-page">
    <div class="content">
        <div class="container-fluid">
            <div class="page-title-box">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <div class="page-title-box">
                            <h4 class="page-title">Combined Accountant Report (NUT Staff + Guides) - {{ \Carbon\Carbon::parse($monthYear)->format('F Y') }}</h4>
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item">
                                    <a href="javascript:void(0);">Home</a>
                                </li>
                                <li class="breadcrumb-item">
                                    <a href="{{ route('combined-reports.combined-accountant.create') }}">Combined Accountant Reports</a>
                                </li>
                                <li class="breadcrumb-item active">Monthly Report</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="datatable-buttons" class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Full Name</th>
                                            <th>Department</th>
                                            <th>Type</th>
                                            <!-- Hours columns -->
                                            <th>Work Hours</th>
                                            <th>Work Hours ONLY with Holiday Extras</th>
                                            <th>Night Supplement Hours</th>
                                            <th>Night Supplement ONLY with Holiday Extras</th>
                                            <th>Sick Leaves</th>
                                            <!-- Accounting columns -->
                                            @foreach($accountingTypes as $type)
                                                <th>{{ $type->name }} ({{ $type->unit }})</th>
                                            @endforeach
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($staffSalaries as $salary)
                                            <tr>
                                                <td>{{ $salary['name'] }}</td>
                                                <td>{{ $salary['full_name'] }}</td>
                                                <td>{{ $salary['department'] }}</td>
                                                <td>
                                                    <span class="badge badge-{{ $salary['type'] == 'Staff' ? 'primary' : ($salary['type'] == 'Guide' ? 'info' : 'success') }}">
                                                        {{ $salary['type'] }}
                                                    </span>
                                                </td>
                                                <!-- Hours columns -->
                                                <td>{{ $salary['totalNormalHours'] }}</td>
                                                <td>{{ $salary['totalHolidayHours'] }}</td>
                                                <td>{{ $salary['totalNormalNightHours'] }}</td>
                                                <td>{{ $salary['totalHolidayNightHours'] }}</td>
                                                <td>{{ $salary['totalSickLeaves'] ?? '0:00' }}</td>
                                                
                                                <!-- Accounting columns -->
                                                @foreach($accountingTypes as $type)
                                                    <td>
                                                        @php
                                                            // Get user_id for lookup - handle both staff and guide data structures
                                                            $userId = isset($salary['staff']) ? $salary['staff']->user_id : 
                                                                     (isset($salary['tourGuide']) ? $salary['tourGuide']->user_id : null);
                                                        @endphp
                                                        @if($userId && isset($accountingRecords[$userId][$type->name]))
                                                            @php
                                                                $total = 0;
                                                                foreach($accountingRecords[$userId][$type->name] as $record) {
                                                                    if($record['expense_type'] == 'deduct') {
                                                                        $total -= $record['amount'];
                                                                    } else {
                                                                        $total += $record['amount'];
                                                                    }
                                                                }
                                                            @endphp
                                                            @if($total < 0)
                                                                <span class="text-danger">{{ number_format($total, 2) }}</span>
                                                            @elseif($total > 0)
                                                                <span class="text-success">+{{ number_format($total, 2) }}</span>
                                                            @else
                                                                0.00
                                                            @endif
                                                        @else
                                                            0.00
                                                        @endif
                                                    </td>
                                                @endforeach
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

@push('scripts')
<script>
    // Helper function to convert HH:MM to minutes
    function timeToMinutes(timeStr) {
        if (!timeStr || timeStr === '0:00' || timeStr === '00:00') return 0;
        const parts = timeStr.split(':');
        return parseInt(parts[0]) * 60 + parseInt(parts[1]);
    }

    // Helper function to convert minutes to HH:MM
    function minutesToTime(minutes) {
        const hours = Math.floor(minutes / 60);
        const mins = minutes % 60;
        return hours.toString().padStart(2, '0') + ':' + mins.toString().padStart(2, '0');
    }

    // Wait for document ready and ensure DataTables is loaded
    $(document).ready(function() {
        // Destroy existing DataTable if it exists
        if ($.fn.DataTable.isDataTable('#datatable-buttons')) {
            $('#datatable-buttons').DataTable().destroy();
        }
        
        // Initialize DataTable with custom configuration
        $('#datatable-buttons').DataTable({
            lengthChange: true,
            searching: true,
            pageLength: 100,
            paging: false,
            ordering: true,
            info: false,
            buttons: [
                'copy', 'csv', 'excel', 'pdf', 'print'
            ],
            columnDefs: [
                {
                    targets: '_all', // All columns
                    orderable: false // Disable sorting for all columns by default
                },
                {
                    targets: 1, // Full Name column (0-indexed, so column 1)
                    orderable: true // Enable sorting only for Full Name column
                }
            ],
            order: [[1, 'asc']] // Default sort by Full Name column in ascending order
        });
    });
</script>
@endpush
@endsection