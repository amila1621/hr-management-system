@extends('partials.main')

@section('content')
    <div class="content-page">
        <div class="content">
            <div class="container-fluid">
                <h4 class="page-title">Office Worker Accountant Report for {{ \Carbon\Carbon::parse($monthYear)->format('F Y') }}</h4>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">

                                <div class="table">
                                    <table id="datatable-buttons" class="table table-striped table-bordered"
                                        style="border-collapse: collapse; border-spacing: 0; width: 100%">
                                        <thead>
                                            <tr>
                                                <th>Name</th>
                                                <th>Full Name</th>
                                                <th>Department</th>
                                                <th>Work Hours</th>
                                                <th>Work hours ONLY with holiday extras</th>
                                                <th>Night supplement hours(20-6)</th>
                                                <th>Night supplement ONLY with holiday extras</th>
                                                <!-- Generate accounting type columns dynamically -->
                                                @foreach($accountingTypes as $type)
                                                    <th>{{ $type->name }} ({{ $type->unit }})</th>
                                                @endforeach
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($staffHours as $staffId => $data)
                                                @if($data['staff'] !== null)
                                                    <tr>
                                                        <td>{{ $data['staff']->name ?? 'N/A' }}</td>
                                                        <td>{{ $data['staff']->full_name ?? 'N/A' }}</td>
                                                        <td>{{ $data['staff']->department ?? 'N/A' }}</td>
                                                        <td>{{ $data['totalNormalHours'] }}</td>
                                                        <td>{{ $data['totalHolidayHours'] }}</td>
                                                        <td>{{ $data['totalNormalNightHours'] ?? '0:00' }}</td>  <!-- Change from totalNightHours -->
                                                        <td>{{ $data['totalHolidayNightHours'] ?? '0:00' }}</td>  <!-- Change from totalNightHolidayHours -->
                                                        <!-- Generate accounting type columns data dynamically -->
                                                        @foreach($accountingTypes as $type)
                                                            <td>
                                                                @if($data['staff'])
                                                                    @if(isset($accountingRecords[$data['staff']->user_id][$type->name]))
                                                                        @foreach($accountingRecords[$data['staff']->user_id][$type->name] as $index => $record)
                                                                            <div class="mb-1 p-1 rounded {{ $record['expense_type'] == 'payback' ? 'bg-success-light' : 'bg-danger-light' }}">
                                                                                <span>{{ $record['expense_type'] == 'payback' ? '+' : '-' }} {{ $record['amount'] }}</span>
                                                                            </div>
                                                                        @endforeach
                                                                    @endif
                                                                @endif
                                                            </td>
                                                        @endforeach
                                                    </tr>
                                                @endif
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
        // ...existing code...
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
