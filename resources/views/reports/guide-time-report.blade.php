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
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php
                                                $totalNormalHours = 0;
                                                $totalNormalNightHours = 0;
                                                $totalHolidayHours = 0;
                                                $totalHolidayNightHours = 0;
                                            @endphp

                                            @foreach ($groupedEventSalaries as $date => $data)
                                                <tr>
                                                    <td>{{ \Carbon\Carbon::parse($data['date'])->format('d.m.Y') }}</td>
                                                    <td>{{ $data['normal_hours'] }}</td>
                                                    <td>{{ $data['holiday_hours'] }}</td>
                                                    <td>{{ $data['normal_night_hours'] }}</td>
                                                    <td>{{ $data['holiday_night_hours'] }}</td>
                                                </tr>

                                                @php
                                                    $totalNormalHours += \App\Helpers\TimeHelper::timeToDecimal($data['normal_hours']);
                                                    $totalNormalNightHours += \App\Helpers\TimeHelper::timeToDecimal($data['normal_night_hours']);
                                                    $totalHolidayHours += \App\Helpers\TimeHelper::timeToDecimal($data['holiday_hours']);
                                                    $totalHolidayNightHours += \App\Helpers\TimeHelper::timeToDecimal($data['holiday_night_hours']);
                                                @endphp
                                            @endforeach
                                        </tbody>
                                        {{-- <tfoot>
                                            <tr>
                                                <th>Total</th>
                                                <th>{{ \App\Helpers\TimeHelper::decimalToTime($totalNormalHours) }}</th>
                                                <th>{{ \App\Helpers\TimeHelper::decimalToTime($totalHolidayHours) }}</th>
                                                <th>{{ \App\Helpers\TimeHelper::decimalToTime($totalNormalNightHours) }}</th>
                                                <th>{{ \App\Helpers\TimeHelper::decimalToTime($totalHolidayNightHours) }}</th>
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
    <!-- Include your DataTables and other scripts here -->
@endsection
