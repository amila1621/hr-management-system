@extends('partials.main')

@section('content')
    <div class="content-page">
        <div class="content">
            <div class="container-fluid">
                <div class="page-title-box">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <div class="page-title-box">
                                @if(Auth::user()->role == "admin")
                                    <h4 class="page-title">Guide Reports</h4>
                                @else
                                    <h4 class="page-title">Driver Report</h4>
                                @endif
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item">
                                        <a href="javascript:void(0);">Home</a>
                                    </li>
                                    @if(Auth::user()->role == "admin")
                                        <li class="breadcrumb-item active">Guide Reports</li>
                                    @else
                                        <li class="breadcrumb-item active">Driver Report</li>
                                    @endif
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
                                @if(Auth::user()->role == "admin")
                                    <h4 class="mt-0 header-title">Select a Guide and Date Range</h4>
                                @else
                                    <h4 class="mt-0 header-title">Select a Driver and Date Range</h4>
                                @endif
                                <form id="reportForm" action="{{ route('reports.getGuideWiseReport') }}" method="GET">
                                    @csrf
                                    <div class="form-group">
                                        @if(Auth::user()->role == "admin")
                                            <label for="guide_id">Select Guide</label>
                                        @else
                                            <label for="guide_id">Select Driver</label>
                                        @endif
                                        <select name="guide_id" class="form-control" required>
                                            @foreach($tourGuides as $tourGuide)
                                                <option value="{{ $tourGuide->id }}">{{ $tourGuide->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label for="start_date">Start Date</label>
                                        <input type="date" name="start_date" class="form-control" required>
                                    </div>

                                    <div class="form-group">
                                        <label for="end_date">End Date</label>
                                        <input type="date" name="end_date" class="form-control" required>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary waves-effect waves-light mr-2">Fetch</button>
                                    <button type="button" id="fetchCurrentMonth" class="btn btn-secondary waves-effect waves-light">Fetch Current Month</button>
                                </form>

                                <script>
                                    document.getElementById('fetchCurrentMonth').addEventListener('click', function() {
                                        const now = new Date();
                                        const startDate = new Date(now.getFullYear(), now.getMonth(), 1);
                                        const endDate = new Date(now.getFullYear(), now.getMonth() + 1, 0);

                                        document.querySelector('input[name="start_date"]').value = startDate.toISOString().split('T')[0];
                                        document.querySelector('input[name="end_date"]').value = endDate.toISOString().split('T')[0];

                                        document.querySelector('#reportForm').submit();
                                    });

                                    document.addEventListener('DOMContentLoaded', function() {
                                        document.getElementById('fetchCurrentMonth').click();
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
