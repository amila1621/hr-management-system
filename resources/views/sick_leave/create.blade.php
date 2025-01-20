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
                                <h4 class="page-title">Sick Leave Tours</h4>
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item">
                                        <a href="javascript:void(0);">Home</a>
                                    </li>
                                    <li class="breadcrumb-item active">Manage Sick Leave Tours</li>
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
                                <h4 class="mt-0 header-title">Create Sick Leave Tours</h4>
                                <form action="{{ route('tours.sick-leave-store-manual') }}" method="POST">
                                    @csrf
                                    <div class="form-group">
                                        <label for="tour_name">Tour Name / Reason</label>
                                        <input type="text" name="tour_name" id="tour_name" class="form-control" required>
                                        @error('tour_name')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>

                                    <div class="form-group">
                                        <label for="title">Guide</label>
                                      <select required name="guide_id" id="guide_id" class="form-control">
                                        <option value="">Select Guide</option>
                                        @foreach ($guides as $guide)
                                            <option value="{{ $guide->id }}">{{ $guide->name }}</option>
                                        @endforeach
                                      </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Date & Time Range</label>
                                        <input type="text" 
                                               name="datetime_range" 
                                               class="form-control" 
                                               id="datetime_range" 
                                               placeholder="Select date and time range"
                                               required>
                                        <input type="hidden" name="start_time" id="start_time">
                                        <input type="hidden" name="end_time" id="end_time">
                                    </div>

                                    <button type="submit" class="btn btn-primary waves-effect waves-light">Create Missing Hours</button>
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
        initFlatpickr();
    
   

        $('#datetime_range').daterangepicker({
            timePicker: true,
            timePicker24Hour: true,
            startDate: moment().startOf('hour'),
            endDate: moment().startOf('hour').add(1, 'hour'),
            locale: {
                format: 'YYYY-MM-DD HH:mm'
            }
        }, function(start, end) {
            $('#start_time').val(start.format('YYYY-MM-DD HH:mm'));
            $('#end_time').val(end.format('YYYY-MM-DD HH:mm'));
        });
    });

 
</script>

<script>
$(function() {
    $('#datetime_range').daterangepicker({
        timePicker: true,
        timePicker24Hour: true,
        autoUpdateInput: false,
        locale: {
            format: 'YYYY-MM-DD HH:mm',
            cancelLabel: 'Clear'
        }
    });

    $('#datetime_range').on('apply.daterangepicker', function(ev, picker) {
        $(this).val(picker.startDate.format('YYYY-MM-DD HH:mm') + ' - ' + picker.endDate.format('YYYY-MM-DD HH:mm'));
        $('#start_time').val(picker.startDate.format('YYYY-MM-DD HH:mm'));
        $('#end_time').val(picker.endDate.format('YYYY-MM-DD HH:mm'));
    });

    $('#datetime_range').on('cancel.daterangepicker', function(ev, picker) {
        $(this).val('');
        $('#start_time').val('');
        $('#end_time').val('');
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

<div class="modal fade" id="editMissingHoursModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Missing Hours</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="editMissingHoursForm" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="form-group">
                        <label for="edit_tour_name">Tour Name / Reason</label>
                        <input type="text" name="tour_name" id="edit_tour_name" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="edit_guide_id">Guide</label>
                        <select name="guide_id" id="edit_guide_id" class="form-control">
                            <option value="">Select Guide</option>
                            @foreach ($guides as $guide)
                                <option value="{{ $guide->id }}">{{ $guide->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="edit_start_time">Start Time</label>
                        <input type="text" name="start_time" id="edit_start_time" class="form-control flatpickr-datetime" readonly="readonly">
                    </div>

                    <div class="form-group">
                        <label for="edit_end_time">End Time</label>
                        <input type="text" name="end_time" id="edit_end_time" class="form-control flatpickr-datetime" readonly="readonly">
                    </div>

                    <div class="form-group">
                        <label for="edit_applied_at">Applied Month</label>
                        <input type="month" name="applied_at" id="edit_applied_at" class="form-control" required>
                    </div>

                    <button type="submit" class="btn btn-primary">Update Missing Hours</button>
                </form>
            </div>
        </div>
    </div>
</div>
