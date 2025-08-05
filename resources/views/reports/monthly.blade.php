@extends('partials.main')

@section('content')
    <div class="content-page">
        <div class="content">
            <div class="container-fluid">
                <h4 class="page-title">Monthly Report for {{ \Carbon\Carbon::parse($monthYear)->format('F Y') }}</h4>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">

                                <div class="table">
                                    <table id="monthly-report-datatable" class="table table-striped table-bordered"
                                        style="border-collapse: collapse; border-spacing: 0; width: 100%">
                                        <thead>
                                            <tr>
                                                <th>Guide Name</th>
                                                <th>Work Name</th>
                                                <th>Work Hours</th>
                                                <th>Work hours ONLY with holiday extras</th>
                                                <th>Night supplement hours(20-6)</th>
                                                <th>Night supplement ONLY with holiday extras</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($eventSalaries as $salary)
                                            @if(isset($salary['tourGuide']))
                                                @if (Auth::user()->role == 'admin' || Auth::user()->role == 'hr-assistant')
                                                    <tr>
                                                        <td>{{ $salary['tourGuide']?->full_name ?? 'N/A' }}</td>
                                                        <td>{{ $salary['tourGuide']?->name ?? 'N/A' }}</td>
                                                        <td>{{ $salary['totalNormalHours'] }}</td>
                                                        <td>{{ $salary['totalHolidayHours'] }}</td>
                                                        <td>{{ $salary['totalNormalNightHours'] }}</td>
                                                        <td>{{ $salary['totalHolidayNightHours'] }}</td>
                                                    </tr>
                                                @elseif (Auth::user()->role == 'supervisor' || Auth::user()->role == 'operation')
                                                    @if ($salary['tourGuide']->supervisor == Auth::id())
                                                        <tr>
                                                        <td>{{ $salary['tourGuide']?->full_name ?? 'N/A' }}</td>
                                                        <td>{{ $salary['tourGuide']?->name ?? 'N/A' }}</td>
                                                            <td>{{ $salary['totalNormalHours'] }}</td>
                                                            <td>{{ $salary['totalHolidayHours'] }}</td>
                                                            <td>{{ $salary['totalNormalNightHours'] }}</td>
                                                            <td>{{ $salary['totalHolidayNightHours'] }}</td>
                                                        </tr>
                                                    @endif
                                                @endif
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
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
            $(document).ready(function() {
        // Define custom sorting after DataTables is loaded
        $.fn.dataTable.ext.type.order['time-pre'] = function(data) {
            if (!data) return 0;
            
            var parts = data.split(':');
            return parseInt(parts[0]) * 60 + parseInt(parts[1]);
        };

       
        
        
        $('#monthly-report-datatable').DataTable({
        dom: 'Bfrtip',
        buttons: [
            'copy', 'csv', 'excel', 'pdf', 'print'
        ],
        columnDefs: [
            { type: 'time', targets: [2, 3, 4, 5] }
        ],
        order: [[0, 'asc']],
        searching: true,
        ordering: true,
        paging: false,     // Disable pagination
        info: false        // Remove "Showing X of Y entries" text
    });
    });
    </script>
@endsection