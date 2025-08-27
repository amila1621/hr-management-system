@extends('partials.main')
@section('content')
    <script src="https://rawgit.com/schmich/instascan-builds/master/instascan.min.js"></script>
    <script src="https://cdn.jsdelivr.net/gh/xcash/bootstrap-autocomplete@v2.3.7/dist/latest/bootstrap-autocomplete.min.js">
    </script>
 
    <div class="content-page">
        <!-- Start content -->
        <div class="content">

            <div class="container-fluid">
                <div class="page-title-box">

                    <div class="row align-items-center ">
                        <div class="col-md-8">
                            <div class="page-title-box">
                                <h4 class="page-title">Fetch Event</h4>
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item">
                                        <a href="javascript:void(0);">Home</a>
                                    </li>

                                    <li class="breadcrumb-item active">Fetch Event</li>
                                </ol>
                            </div>
                        </div>

                    </div>
                </div>
                <!-- end page-title -->
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
                    

                    {{-- <div class="col-lg-5">
                        <div class="card">
                            <div class="card-body">

                                <h1 class="mt-0 header-title">Fetch Events</h1>

                                <form action="{{ route('fetch.filter.events') }}" method="post">
                                    @csrf
                                    <div class="form-group">
                                        <label>Select Date Range</label>
                                        <div>
                                            <div class="input-group">
                                                <input type="text" name="daterange" class="form-control" autocomplete="off" />
                                                <input type="hidden" name="start" />
                                                <input type="hidden" name="end" />
                                            </div>
                                        </div>
                                    </div>

                                    <button class="btn btn-primary waves-effect waves-light" type="submit">Proceed</button>


                                </form>
                            </div>
                        </div>


                    </div> --}}

                    <div class="col-lg-5">
                        <div class="card">
                            <div class="card-body">

                                <h1 class="mt-0 header-title">Sync Tours from API</h1>

                                <form id="syncToursForm">
                                    @csrf
                                    <div class="form-group">
                                        <label>Select Date Range</label>
                                        <div>
                                            <div class="input-group">
                                                <input type="text" name="daterange" class="form-control" autocomplete="off" />
                                                <input type="hidden" name="start" />
                                                <input type="hidden" name="end" />
                                            </div>
                                        </div>
                                    </div>

                                    <button class="btn btn-success waves-effect waves-light" type="submit">
                                        <i class="fas fa-sync-alt"></i> Sync Tours
                                    </button>

                                    <div id="syncResult" class="mt-3" style="display: none;">
                                        <div id="syncAlert" class="alert"></div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-5">
                        <div class="card">
                            <div class="card-body">

                                <h1 class="mt-0 header-title">Fetch Chores</h1>

                                <form autocomplete="off" action="{{ route('fetch.filter.chores') }}" method="post">
                                    @csrf
                                    <div class="form-group">
                                        <label>Select Date Range</label>
                                        <div>
                                            <div class="input-group">
                                                <input type="text" name="daterange" class="form-control" autocomplete="off"/>
                                                <input type="hidden" name="start" />
                                                <input type="hidden" name="end" />
                                            </div>
                                        </div>
                                    </div>

                                    <button class="btn btn-primary waves-effect waves-light" type="submit">Proceed</button>


                                </form>
                            </div>
                        </div>

                    </div>

                    <!-- Add new table section -->
                    <div class="col-lg-12 mt-4">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="mt-0 header-title">Last Tours History</h4>
                                @php
                                    // Determine the date range - let's use the last 7 days
                                    $endDate = now();
                                    $startDate = now()->subDays(40); // 7 days including today
                                    $dateRange = [];
                                    
                                    // Create an array with all dates in the range
                                    for ($date = clone $startDate; $date <= $endDate; $date->addDay()) {
                                        $dateRange[$date->format('Y-m-d')] = [];
                                    }
                                    
                                    // Group tours by date
                                    foreach ($lastTours as $tour) {
                                        $tourDate = Carbon\Carbon::parse($tour->tour_date)->format('Y-m-d');
                                        if (isset($dateRange[$tourDate])) {
                                            $dateRange[$tourDate][] = $tour;
                                        }
                                    }
                                    
                                    // Sort by date in descending order (most recent first)
                                    krsort($dateRange);
                                @endphp

                                <div class="table-responsive">
                                    <table id="datatable-buttons" class="table table-striped mb-0">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Tour Name</th>
                                                <th>Guide</th>
                                                <th>End Time</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($dateRange as $date => $tours)
                                                @if(count($tours) > 0)
                                                    @foreach($tours as $tour)
                                                        <tr>
                                                            <td>{{ Carbon\Carbon::parse($tour->tour_date)->format('d/m/Y') }}</td>
                                                            <td>{{ $tour->tour_name }}</td>
                                                            <td>{{ $tour->guide }}</td>
                                                            <td>{{ Carbon\Carbon::parse($tour->end_time)->format('d/m/Y H:i:s') }}</td>
                                                        </tr>
                                                    @endforeach
                                                @else
                                                    <tr>
                                                        <td>{{ Carbon\Carbon::parse($date)->format('d/m/Y') }}</td>
                                                        <td colspan="3" class="text-center text-muted">No tours available</td>
                                                    </tr>
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
            <!-- container-fluid -->

        </div>
        <!-- content -->

        <script>
            $(function() {
                $('input[name="daterange"]').daterangepicker({
                    autoUpdateInput: false,
                    autoApply: true,
                    locale: {
                        cancelLabel: 'Clear'
                    }
                });
    
                $('input[name="daterange"]').on('apply.daterangepicker', function(ev, picker) {
                    $(this).val(picker.startDate.format('DD/MM/YYYY') + ' - ' + picker.endDate.format('DD/MM/YYYY'));
                    $('input[name="start"]').val(picker.startDate.format('YYYY-MM-DD'));
                    $('input[name="end"]').val(picker.endDate.format('YYYY-MM-DD'));
                });
    
                $('input[name="daterange"]').on('cancel.daterangepicker', function(ev, picker) {
                    $(this).val('');
                    $('input[name="start"]').val('');
                    $('input[name="end"]').val('');
                });

                // Handle sync tours form submission
                $('#syncToursForm').on('submit', function(e) {
                    e.preventDefault();
                    
                    var startDate = $('input[name="start"]').val();
                    var endDate = $('input[name="end"]').val();
                    
                    if (!startDate || !endDate) {
                        alert('Please select a date range');
                        return;
                    }
                    
                    var submitBtn = $(this).find('button[type="submit"]');
                    submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Syncing...');
                    
                    $.ajax({
                        url: '{{ route("api.sync-tours") }}',
                        method: 'POST',
                        data: {
                            start_date: startDate,
                            end_date: endDate,
                            _token: $('input[name="_token"]').val()
                        },
                        success: function(response) {
                            if (response.success) {
                                $('#syncAlert').removeClass('alert-danger').addClass('alert-success')
                                    .html('<i class="fas fa-check"></i> ' + response.message + 
                                          '<br><small>Synced: ' + response.synced + 
                                          ', Skipped: ' + response.skipped + 
                                          ', Total Fetched: ' + response.total_fetched + '</small>');
                                          
                                if (response.errors && response.errors.length > 0) {
                                    $('#syncAlert').append('<br><strong>Errors:</strong><ul>');
                                    response.errors.forEach(function(error) {
                                        $('#syncAlert').append('<li>' + error + '</li>');
                                    });
                                    $('#syncAlert').append('</ul>');
                                }
                            } else {
                                $('#syncAlert').removeClass('alert-success').addClass('alert-danger')
                                    .html('<i class="fas fa-times"></i> ' + response.message);
                            }
                            $('#syncResult').show();
                        },
                        error: function(xhr) {
                            var errorMsg = xhr.responseJSON ? xhr.responseJSON.message : 'An error occurred';
                            $('#syncAlert').removeClass('alert-success').addClass('alert-danger')
                                .html('<i class="fas fa-times"></i> Error: ' + errorMsg);
                            $('#syncResult').show();
                        },
                        complete: function() {
                            submitBtn.prop('disabled', false).html('<i class="fas fa-sync-alt"></i> Sync Tours');
                        }
                    });
                });
            });
        </script>
        
    @endsection


