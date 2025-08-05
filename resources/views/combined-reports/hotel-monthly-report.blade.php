{{-- filepath: c:\Users\it\Desktop\Projects\-nuthr\resources\views\combined-reports\hotel-monthly-report.blade.php --}}
@extends('partials.main')

@section('content')
<div class="content-page">
    <div class="content">
        <div class="container-fluid">
            <div class="page-title-box">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <div class="page-title-box">
                            <h4 class="page-title">Hotel Staff Monthly Report - {{ \Carbon\Carbon::parse($monthYear)->format('F Y') }}</h4>
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item">
                                    <a href="javascript:void(0);">Home</a>
                                </li>
                                <li class="breadcrumb-item">
                                    <a href="{{ route('combined-reports.hotel.create') }}">Hotel Reports</a>
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
                                            <!-- Hours columns -->
                                            <th>Work Hours</th>
                                            <th>Work Hours ONLY with Holiday Extras</th>
                                            <th>Evening Hours <br> (18:00-00:00)</th>
                                            <th>Evening Holiday Hours</th>
                                            <th>Night Supplement Hours <br> (00:00-06:00)</th>
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
                                                <td>{{ $salary['staff']->name }}</td>
                                                <td>{{ $salary['staff']->full_name ?? $salary['staff']->name }}</td>
                                                <td>{{ $salary['staff']->department }}</td>
                                                <!-- Hours columns -->
                                                <td>{{ $salary['totalNormalHours'] }}</td>
                                                <td>{{ $salary['totalHolidayHours'] }}</td>
                                                <td>{{ $salary['totalEveningHours'] ?? '00:00' }}</td>
                                                <td>{{ $salary['totalEveningHolidayHours'] ?? '00:00' }}</td>
                                                <td>{{ $salary['totalNormalNightHours'] }}</td>
                                                <td>{{ $salary['totalHolidayNightHours'] }}</td>
                                                <td>{{ $salary['totalSickLeaves'] }}</td>
                                                
                                                <!-- Accounting columns -->
                                                @foreach($accountingTypes as $type)
                                                    <td>
                                                        @if(isset($accountingRecords[$salary['staff']->user_id][$type->name]))
                                                            @php
                                                                $total = 0;
                                                                foreach($accountingRecords[$salary['staff']->user_id][$type->name] as $record) {
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
    <script>
    $('#datatable-buttons').DataTable({
        // ...existing options...
        buttons: [
            {
                extend: 'csv',
                text: 'Export CSV',
                customize: function(csv) {
                    // Replace multiple spaces with single line break
                    return csv.replace(/\s{2,}/g, '\n');
                }
            }
        ]
    });
    </script>
@endsection
