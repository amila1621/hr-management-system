@extends('partials.main')

@section('content')
<div class="content-page">
    <div class="content">
        <div class="container-fluid">
            <div class="page-title-box">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <div class="page-title-box">
                            <h4 class="page-title">Combined Accountant Report (NUT Staff + Guides)</h4>
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item">
                                    <a href="javascript:void(0);">Home</a>
                                </li>
                                <li class="breadcrumb-item active">Combined Accountant Reports</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title">Generate Combined Accountant Report</h4>
                            <p class="card-subtitle">Generate a monthly report combining both NUT Staff and Tour Guides data</p>

                            <form method="POST" action="{{ route('combined-reports.combined-accountant.monthly') }}">
                                @csrf
                                <div class="form-group">
                                    <label for="month">Select Month</label>
                                    <input type="month" class="form-control" id="month" name="month" 
                                           value="{{ now()->format('Y-m') }}" required>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">Generate Report</button>
                                <a href="{{ url()->previous() }}" class="btn btn-secondary">Back</a>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection