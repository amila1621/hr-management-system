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
                                                @if (Auth::user()->role == 'admin' || Auth::user()->role == 'hr-assistant')
                                                    <tr>
                                                        <td>{{ $salary['tourGuide']->full_name }}</td>
                                                        <td>{{ $salary['tourGuide']->name }}</td>
                                                        <td>{{ $salary['totalNormalHours'] }}</td>
                                                        <td>{{ $salary['totalHolidayHours'] }}</td>
                                                        <td>{{ $salary['totalNormalNightHours'] }}</td>
                                                        <td>{{ $salary['totalHolidayNightHours'] }}</td>
                                                    </tr>
                                                @elseif (Auth::user()->role == 'supervisor' || Auth::user()->role == 'operation')
                                                    @if ($salary['tourGuide']->supervisor == Auth::id())
                                                        <tr>
                                                            <td>{{ $salary['tourGuide']->full_name }}</td>
                                                            <td>{{ $salary['tourGuide']->name }}</td>
                                                            <td>{{ $salary['totalNormalHours'] }}</td>
                                                            <td>{{ $salary['totalHolidayHours'] }}</td>
                                                            <td>{{ $salary['totalNormalNightHours'] }}</td>
                                                            <td>{{ $salary['totalHolidayNightHours'] }}</td>
                                                        </tr>
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
                ]
            });
        });
    </script>
@endsection
