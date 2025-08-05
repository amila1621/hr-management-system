@extends('partials.main')

<!-- Add DateRangePicker CSS -->
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
<!-- Add Flatpickr CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

@section('content')
    <div class="content-page">
        <div class="content">
            <div class="container-fluid">
                <div class="page-title-box">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <div class="page-title-box">
                                <h4 class="page-title">Sick Leave</h4>
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item">
                                        <a href="javascript:void(0);">Home</a>
                                    </li>
                                    <li class="breadcrumb-item active">Request Sick leaves </li>
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

                <!-- Display Validation Errors -->
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="row">
                    
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="mt-0 header-title">Submit Sick Leave Application</h4>
                                <form action="{{ route('sick-leave.store-sick-leaves') }}" method="POST" enctype="multipart/form-data">
                                    @csrf
                                    <div class="form-group">
                                        <label for="tour_name">Description</label>
                                        <input type="text" name="description" id="description" class="form-control" required>
                                        @error('description')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>

                                    <!-- Replace the current Date Range section with: -->
                                    <div class="form-group">
                                        <label for="start_date">When did your sick leave start?</label>
                                        <input type="date" 
                                               name="start_date" 
                                               id="start_date" 
                                               class="form-control" 
                                               required>
                                        <small class="form-text text-muted">Select the first day you were absent from work</small>
                                    </div>

                                    <div class="form-group">
                                        <label for="end_date">When did your sick leave end?</label>
                                        <input type="date" 
                                               name="end_date" 
                                               id="end_date" 
                                               class="form-control" 
                                               required>
                                        <small class="form-text text-muted">Select the last day you were absent (or today if still ongoing)</small>
                                    </div>

                                    <!-- Keep the hidden fields for backend compatibility -->
                                    <input type="hidden" name="start_time" id="start_time">
                                    <input type="hidden" name="end_time" id="end_time">
                                    <input type="hidden" name="datetime_range" id="datetime_range">

                                    <div class="form-group">
                                        <label for="image">Medical Certificate/Doctor's Note</label>
                                        <input type="file" name="image" id="image" class="form-control-file" required>
                                        <small class="form-text text-muted">Upload your doctor's note or medical certificate (JPG, PNG, PDF)</small>
                                        @error('image')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>

                                    <button type="submit" class="btn btn-primary waves-effect waves-light">Submit Sick Leave Request</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Add these script tags before your existing scripts -->
<script type="text/javascript" src="https://cdn.jsdelivr.net/jquery/latest/jquery.min.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
<!-- Add Flatpickr JS -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        $('#datetime_range').daterangepicker({
            startDate: moment(),
            endDate: moment().add(1, 'day'),
            locale: {
                format: 'YYYY-MM-DD'
            }
        }, function(start, end) {
            $('#start_time').val(start.format('YYYY-MM-DD'));
            $('#end_time').val(end.format('YYYY-MM-DD'));
        });
    });
</script>

<script>
$(function() {
    $('#datetime_range').daterangepicker({
        autoUpdateInput: false,
        startDate: moment(),
        endDate: moment(),
        locale: {
            format: 'YYYY-MM-DD',
            cancelLabel: 'Clear'
        }
    });

    $('#datetime_range').on('apply.daterangepicker', function(ev, picker) {
        $(this).val(picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD'));
        $('#start_time').val(picker.startDate.format('YYYY-MM-DD'));
        $('#end_time').val(picker.endDate.format('YYYY-MM-DD'));
    });

    $('#datetime_range').on('cancel.daterangepicker', function(ev, picker) {
        $(this).val('');
        $('#start_time').val('');
        $('#end_time').val('');
    });

    // Initialize with today's date
    $('#datetime_range').trigger('apply.daterangepicker', [$('#datetime_range').data('daterangepicker')]);
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const startDate = document.getElementById('start_date');
    const endDate = document.getElementById('end_date');
    
    // Remove minimum date restrictions - allow past dates
    // No date restrictions needed for retroactive sick leave applications
    
    // Update hidden fields when dates change
    function updateHiddenFields() {
        if (startDate.value && endDate.value) {
            document.getElementById('start_time').value = startDate.value;
            document.getElementById('end_time').value = endDate.value;
            document.getElementById('datetime_range').value = startDate.value + ' - ' + endDate.value;
        }
    }
    
    // Ensure end date is not before start date
    startDate.addEventListener('change', function() {
        if (endDate.value && endDate.value < this.value) {
            endDate.value = this.value;
        }
        updateHiddenFields();
    });
    
    endDate.addEventListener('change', function() {
        // Auto-adjust if end date is before start date
        if (startDate.value && this.value < startDate.value) {
            startDate.value = this.value;
        }
        updateHiddenFields();
    });
});
</script>

<style>
    .content-page {
        position: relative;
        height: 100vh;
        overflow: hidden;
    }

    .content {
        height: calc(100vh - 70px); /* Adjust 70px based on your header height */
        overflow-y: auto;
        padding-bottom: 60px; /* Add padding to account for footer */
    }

    /* Optional: If you want a smoother scrolling experience */
    .content {
        scroll-behavior: smooth;
        -webkit-overflow-scrolling: touch;
    }

    /* Ensure footer stays at bottom */
    .footer {
        position: fixed;
        bottom: 0;
        right: 0;
        left: 240px; /* Adjust based on your sidebar width */
        z-index: 100;
        background: #fff;
    }

    .navbar-right {
        margin-top: 15px;
    }
</style>

