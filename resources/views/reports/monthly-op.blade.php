@extends('partials.main')

@section('content')
    <div class="content-page">
        <div class="content">
            <div class="container-fluid">
                <!-- Add form for month/year selection -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <form action="{{ route('reports.monthly-report-create-op') }}" method="GET" class="form-inline">
                            <select name="month" class="form-control mr-2">
                                @for ($m = 1; $m <= 12; $m++)
                                    <option value="{{ $m }}" {{ \Carbon\Carbon::parse($monthYear)->format('n') == $m ? 'selected' : '' }}>
                                        {{ \Carbon\Carbon::create()->month($m)->format('F') }}
                                    </option>
                                @endfor
                            </select>
                            
                            <select name="year" class="form-control mr-2">
                                @for ($y = date('Y') - 2; $y <= date('Y') + 1; $y++)
                                    <option value="{{ $y }}" {{ \Carbon\Carbon::parse($monthYear)->format('Y') == $y ? 'selected' : '' }}>
                                        {{ $y }}
                                    </option>
                                @endfor
                            </select>
                            
                            <button type="submit" class="btn btn-primary">Filter</button>
                        </form>
                    </div>
                </div>

                <h4 class="page-title">Monthly and Double Pay Hours Report for {{ \Carbon\Carbon::parse($monthYear)->format('F Y') }}</h4>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">

                                <div class="table">
                                    <table id="datatable-buttons2" class="table table-striped table-bordered"
                                        style="border-collapse: collapse; border-spacing: 0; width: 100%">
                                        <thead>
                                            <tr>
                                                <th>Work Name</th>
                                                <th>Work Hours</th>
                                                <th>Double Pay Hours</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($eventSalaries as $salary)
                                                @if (Auth::user()->role == 'supervisor' || Auth::user()->role == 'operation')
                                                    @if ($salary['tourGuide']->supervisor == Auth::id())
                                                        <tr>
                                                            <td>{{ $salary['tourGuide']->name }}</td>
                                                            <td>{{ sprintf("%02d:%02d", floor($salary['totalNormalHours']), fmod($salary['totalNormalHours'], 1) * 60) }}</td>
                                                             <td>{{ sprintf("%02d:%02d", floor($salary['totalHolidayHours']), fmod($salary['totalHolidayHours'], 1) * 60) }}</td>
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

    <!-- Include only the basic DataTables script -->
    <script src="https://cdn.datatables.net/1.10.25/js/jquery.dataTables.min.js"></script>

    <script>
        $(document).ready(function() {
            // Custom sorting function for time values (HH:MM format)
            jQuery.extend(jQuery.fn.dataTableExt.oSort, {
                "time-pre": function(a) {
                    if (!a) return 0;
                    let time = a.split(':');
                    return parseInt(time[0]) * 60 + parseInt(time[1]);
                },
                
                "time-asc": function(a, b) {
                    return a - b;
                },
                
                "time-desc": function(a, b) {
                    return b - a;
                }
            });

            $('#datatable-buttons2').DataTable({
                ordering: true,
                order: [[0, 'asc']],
                paging: false,
                info: false,
                columnDefs: [
                    { 
                        type: 'time',
                        targets: [1, 2]
                    }
                ]
            });
        });
    </script>
@endsection
