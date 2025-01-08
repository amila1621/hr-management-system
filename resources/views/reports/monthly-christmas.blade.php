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
                                    <table id="datatable-buttons" class="table table-striped table-bordered"
                                        style="border-collapse: collapse; border-spacing: 0; width: 100%">
                                        <thead>
                                            <tr>
                                                <th>Full Name</th>
                                                <th>Guide Name</th>
                                                <th>Work Period</th>
                                                <th>Work Hours</th>
                                                <th>Work hours ONLY with holiday extras</th>
                                                <th>Night supplement hours(20-6)</th>
                                                <th>Night supplement ONLY with holiday extras</th>
                                                <th>Special Holiday Bonus - Day</th>
                                                <th>Special Holiday Bonus with holiday extras - Day</th>
                                                <th>Special Holiday Bonus - Night</th>
                                                <th>Special Holiday Bonus with holiday extras - Night</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($results as $result)
                                                <tr>
                                                    <td>{{ $result['tourGuide']->full_name }}</td>
                                                    <td>{{ $result['tourGuide']->name }}</td>
                                                    <td>{{ $result['period'] }}</td>
                                                    <td>{{ number_format($result['totalNormalHours'], 2) }}</td>
                                                    <td>{{ number_format($result['totalHolidayHours'], 2) }}</td>
                                                    <td>{{ number_format($result['totalNormalNightHours'], 2) }}</td>
                                                    <td>{{ number_format($result['totalHolidayNightHours'], 2) }}</td>
                                                    <td>{{ number_format($result['bonus3Hours'], 2) }}</td>
                                                    <td>{{ number_format($result['specialHolidayDay'], 2) }}</td>
                                                    <td>{{ number_format($result['bonus5Hours'], 2) }}</td>
                                                    <td>{{ number_format($result['specialHolidayNight'], 2) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                        {{-- <tfoot>
                                            <tr>
                                                <th colspan="2" style="text-align: right">Total Hours:</th>
                                                <th>{{ number_format($eventSalaries->sum('totalNormalHours'), 2) }}</th>
                                                <th>{{ number_format($eventSalaries->sum('totalHolidayHours'), 2) }}</th>
                                                <th>{{ number_format($eventSalaries->sum('totalNormalNightHours'), 2) }}</th>
                                                <th>{{ number_format($eventSalaries->sum('totalHolidayNightHours'), 2) }}</th>
                                                <th>{{ number_format($eventSalaries->sum('bonus3Hours'), 2) }}</th>
                                                <th>{{ number_format($eventSalaries->sum('bonus5Hours'), 2) }}</th>
                                                <th>{{ number_format($eventSalaries->sum('specialHolidayDay'), 2) }}</th>
                                                <th>{{ number_format($eventSalaries->sum('specialHolidayNight'), 2) }}</th>
                                            </tr>
                                        </tfoot> --}}
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
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
                footerCallback: function(row, data, start, end, display) {
                    var api = this.api();
                    
                    // Function to sum HH.MM format
                    function sumTime(times) {
                        var totalMinutes = 0;
                        times.forEach(function(time) {
                            if (time) {
                                var parts = time.toString().split('.');
                                var hours = parseInt(parts[0]);
                                var minutes = parts[1] ? parseInt(parts[1]) : 0;
                                totalMinutes += (hours * 60) + minutes;
                            }
                        });
                        
                        var hours = Math.floor(totalMinutes / 60);
                        var minutes = totalMinutes % 60;
                        return hours + '.' + (minutes < 10 ? '0' : '') + minutes;
                    }

                    // Update footer totals for all numeric columns (3 through 10)
                    for(var i = 3; i <= 10; i++) {
                        var columnData = api.column(i).data();
                        var total = sumTime(columnData);
                        $(api.column(i).footer()).html(total);
                    }
                }
            });
        });
    </script>
@endsection
