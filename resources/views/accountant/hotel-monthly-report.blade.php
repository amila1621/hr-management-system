@extends('partials.main')

@section('content')
<div class="content-page">
    <div class="content">
        <div class="container-fluid">
            <div class="page-title-box">
                <div class="row align-items-center">
                    <div class="col-sm-6">
                        <h4 class="page-title">Hotel Accountant Report</h4>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="javascript:void(0);">Reports</a></li>
                            <li class="breadcrumb-item active">Hotel Accountant Report</li>
                        </ol>
                    </div>
                    <div class="col-sm-6">
                        <div class="float-right d-none d-md-block">
                            <a href="{{ route('reports.hotel-report-create') }}" class="btn btn-primary">
                                <i class="mdi mdi-calendar mr-2"></i> Select Different Month
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="mt-0 header-title">Hotel Staff Report - {{ \Carbon\Carbon::parse($monthYear)->format('F Y') }}</h4>
                            
                            <div class="table-responsive">
                                <table id="datatable-buttons" class="table table-bordered table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Full Name</th>
                                            <!-- <th>Department</th> -->
                                            <!-- Hours columns -->
                                            <th>Work Hours</th>
                                            <th>Holiday Hours</th>
                                            <th>Evening Hours <br> (18:00-00:00)</th>
                                            <th>Evening Holiday Hours</th>
                                            <th>Night Hours <br> (00:00-06:00)</th>
                                            <th>Night Holiday Hours</th>
                                            <!-- Accounting columns -->
                                            @foreach($accountingTypes as $type)
                                                <th>{{ $type->name }} ({{ $type->unit }})</th>
                                            @endforeach
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if(count($staffHours))
                                            @foreach($staffUsers as $staff)
                                                <tr>
                                                    <td>{{ $staff->name }}</td>
                                                    <td>{{ $staff->full_name }}</td>
                                                    <!-- <td>{{ $staff->department }}</td> -->
                                                    <!-- Hours data -->
                                                    @php
                                                        $staffHourData = $staffHours[$staff->user_id] ?? null;
                                                    @endphp
                                                    <td>{{ $staffHourData ? $staffHourData['totalWorkHours'] : '00:00' }}</td>
                                                    <td>{{ $staffHourData ? $staffHourData['totalHolidayHours'] : '00:00' }}</td>
                                                    <td>{{ $staffHourData ? $staffHourData['totalEveningHours'] : '00:00' }}</td>
                                                    <td>{{ $staffHourData ? $staffHourData['totalEveningHolidayHours'] : '00:00' }}</td>
                                                    <td>{{ $staffHourData ? $staffHourData['totalNightHours'] : '00:00' }}</td>
                                                    <td>{{ $staffHourData ? $staffHourData['totalNightHolidayHours'] : '00:00' }}</td>
                                                    <!-- Accounting data -->
                                                    @foreach($accountingTypes as $type)
                                                        <td>
                                                            @if($staff)
                                                                @if(isset($accountingRecords[$staff->user_id][$type->name]))
                                                                    @foreach($accountingRecords[$staff->user_id][$type->name] as $index => $record)
                                                                        <div class="mb-1 p-1 rounded {{ $record['expense_type'] == 'payback' ? 'bg-success-light' : 'bg-danger-light' }}">
                                                                            <span>{{ $record['expense_type'] == 'payback' ? '+' : '-' }} {{ $record['amount'] }}</span>
                                                                        </div>
                                                                    @endforeach
                                                                @endif
                                                            @endif
                                                        </td>
                                                    @endforeach
                                                </tr>
                                            @endforeach
                                        @else
                                            <tr>
                                                <td colspan="{{ 8 + count($accountingTypes) }}" class="text-center">No staff records found</td>
                                            </tr>
                                        @endif
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
