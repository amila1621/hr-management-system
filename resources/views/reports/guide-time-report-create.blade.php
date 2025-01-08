@extends('partials.main')

@section('content')
    <div class="content-page">
        <div class="content">
            <div class="container-fluid">
                <div class="page-title-box">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <div class="page-title-box">
                                <h4 class="page-title">Guide Reports</h4>
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item">
                                        <a href="javascript:void(0);">Home</a>
                                    </li>
                                    <li class="breadcrumb-item active">Guide Time Reports</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>

                @if (session()->has('failed'))
                    <div class="alert alert-danger">
                        {{ session()->get('failed') }}
                    </div>
                @endif

                @if (session()->has('success'))
                    <div class="alert alert-success">
                        {{ session()->get('success') }}
                    </div>
                @endif

                <div class="row">
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="mt-0 header-title">Select a Guide and Date Range</h4>
                                <form id="reportForm" action="{{ route('reports.getGuideTimeReport') }}" method="GET">
                                    @csrf
                                    <div class="form-group">
                                        <label for="guide_id">Select Guide</label>
                                        <select name="guide_id" class="form-control" required>
                                            @foreach($tourGuides as $tourGuide)
                                                <option value="{{ $tourGuide->id }}">{{ $tourGuide->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Select Date Range</label>
                                        <input type="text" name="daterange" class="form-control" />
                                        <input type="hidden" name="start_date" />
                                        <input type="hidden" name="end_date" />
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary waves-effect waves-light mr-2">Fetch</button>
                                    <button type="button" id="fetchCurrentMonth" class="btn btn-secondary waves-effect waves-light">Fetch Current Month</button>
                                </form>

                                <script>
                                    $(function() {
                                        $('input[name="daterange"]').daterangepicker({
                                            autoUpdateInput: false,
                                            autoApply: true,
                                            locale: {
                                                cancelLabel: 'Clear',
                                                format: 'DD/MM/YYYY'
                                            }
                                        });

                                        $('input[name="daterange"]').on('apply.daterangepicker', function(ev, picker) {
                                            $(this).val(picker.startDate.format('DD/MM/YYYY') + ' - ' + picker.endDate.format('DD/MM/YYYY'));
                                            $('input[name="start_date"]').val(picker.startDate.format('YYYY-MM-DD'));
                                            $('input[name="end_date"]').val(picker.endDate.format('YYYY-MM-DD'));
                                        });

                                        $('input[name="daterange"]').on('cancel.daterangepicker', function(ev, picker) {
                                            $(this).val('');
                                            $('input[name="start_date"]').val('');
                                            $('input[name="end_date"]').val('');
                                        });

                                        document.getElementById('fetchCurrentMonth').addEventListener('click', function() {
                                            const startDate = moment().startOf('month');
                                            const endDate = moment().endOf('month');

                                            $('input[name="daterange"]').data('daterangepicker').setStartDate(startDate);
                                            $('input[name="daterange"]').data('daterangepicker').setEndDate(endDate);
                                            
                                            $('input[name="daterange"]').val(startDate.format('DD/MM/YYYY') + ' - ' + endDate.format('DD/MM/YYYY'));
                                            $('input[name="start_date"]').val(startDate.format('YYYY-MM-DD'));
                                            $('input[name="end_date"]').val(endDate.format('YYYY-MM-DD'));

                                            document.getElementById('reportForm').submit();
                                        });
                                    });
                                </script>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
