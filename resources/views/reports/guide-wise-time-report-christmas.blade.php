@extends('partials.main')

@section('content')
    <div class="content-page">
        <div class="content">
            <div class="container-fluid">
                <h4 class="page-title">{{ $tourGuide->name }} - Time Sheet ({{ $startDate->format('d M Y') }} - {{ $endDate->format('d M Y') }})</h4>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="mt-0 header-title">Hours Worked</h4>

                                <div class="table-responsive">
                                    <table id="datatable-buttons" class="table table-striped table-bordered" style="border-collapse: collapse; border-spacing: 0; width: 100%">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
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
                                            @foreach ($groupedEventSalaries as $date => $data)
                                                <tr>
                                                    <td>{{ \Carbon\Carbon::parse($data['date'])->format('d.m.Y') }}</td>
                                                    <td>{{ $data['normal_hours'] }}</td>
                                                    <td>{{ $data['holiday_hours'] }}</td>
                                                    <td>{{ $data['normal_night_hours'] }}</td>
                                                    <td>{{ $data['holiday_night_hours'] }}</td>
                                                    <td>{{ $data['regularBonus3Hours'] }}</td>
                                                    <td>{{ $data['extrasBonus3Hours'] }}</td>
                                                    <td>{{ $data['regularBonus5Hours'] }}</td>
                                                    <td>{{ $data['extrasBonus5Hours'] }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <th>Total</th>
                                                <th>{{ $sumTimeFormat($groupedEventSalaries->pluck('normal_hours')->toArray()) }}</th>
                                                <th>{{ $sumTimeFormat($groupedEventSalaries->pluck('holiday_hours')->toArray()) }}</th>
                                                <th>{{ $sumTimeFormat($groupedEventSalaries->pluck('normal_night_hours')->toArray()) }}</th>
                                                <th>{{ $sumTimeFormat($groupedEventSalaries->pluck('holiday_night_hours')->toArray()) }}</th>
                                                <th>{{ $sumTimeFormat($groupedEventSalaries->pluck('regularBonus3Hours')->toArray()) }}</th>
                                                <th>{{ $sumTimeFormat($groupedEventSalaries->pluck('extrasBonus3Hours')->toArray()) }}</th>
                                                <th>{{ $sumTimeFormat($groupedEventSalaries->pluck('regularBonus5Hours')->toArray()) }}</th>
                                                <th>{{ $sumTimeFormat($groupedEventSalaries->pluck('extrasBonus5Hours')->toArray()) }}</th>
                                            </tr>
                                        </tfoot>
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
