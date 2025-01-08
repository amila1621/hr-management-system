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