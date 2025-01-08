@extends('partials.main')
@section('content')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/dark.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <style>
        .col-mail-3 {
            position: absolute;
            top: 0;
            right: 20px;
            bottom: 0;
        }
    </style>
    <div class="content-page">
        <!-- Start content -->
        <div class="content">

            <div class="container-fluid">
                <div class="page-title-box">

                    <div class="row align-items-center ">
                        <div class="col-md-8">
                            <div class="page-title-box">
                                <h4 class="page-title">Salary Updates</h4>
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item">
                                        <a href="javascript:void(0);">Home</a>
                                    </li>
                                    <li class="breadcrumb-item">
                                        <a href="javascript:void(0);">Payroll</a>
                                    </li>
                                    <li class="breadcrumb-item active">Salary Updates</li>
                                </ol>
                            </div>
                        </div>

                    </div>
                </div>
                <!-- end page-title -->

                <div class="row">
                    <div class="col-8">
                        <div class="card">
                            <div class="card-body">
                                @if (session('error'))
                                    <div class="alert alert-danger">
                                        {{ session('error') }}
                                    </div>
                                @endif

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
                                <h4 class="mt-0 header-title">Salary Update List</h4>

                                <div class="table">
                                    <table id="datatable-buttons"
                                        class="table table-striped table-bordered"
                                        style="border-collapse: collapse; border-spacing: 0; width: 100%">
                                        <thead>
                                            <tr>
                                                <th>Employee</th>
                                                <th>Effective Date</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($salaryUpdated as $update)
                                                <tr>
                                                    <td>{{ $update->guide_name }}</td>
                                                    <td>{{ date('d-m-Y', strtotime($update->effective_date)) }}</td>
                                                    <td>
                                                        <button type="button" 
                                                                class="btn btn-warning btn-sm edit-btn" 
                                                                data-id="{{ $update->id }}"
                                                                data-guide="{{ $update->guide_id }}"
                                                                data-date="{{ $update->effective_date }}">
                                                            Edit
                                                        </button>
                                                        <form action="{{ route('salary-updates.destroy', $update->id) }}" method="POST" style="display:inline-block;">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this salary update?');">Delete</button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                        
                                        
                                    </table>
                                </div>


                            </div>

                        </div>
                    </div>

                    <div class="col-4">
                        <div class="card">
                            <div class="card-body">
                                @if (session('error'))
                                    <div class="alert alert-danger">
                                        {{ session('error') }}
                                    </div>
                                @endif

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

                                @if ($errors->any())
                                    <div class="alert alert-danger">
                                        <ul class="mb-0">
                                            @foreach ($errors->all() as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                <h4 class="mt-0 header-title" id="form-title">Add New Salary Update</h4>
                                <form method="POST" action="{{ route('salary-updates.store') }}" id="salary-form">
                                    @csrf
                                    <div id="method-div"></div>
                                    
                                    <div class="form-group">
                                        <label for="guide_id">Employee</label>
                                        <select name="guide_id" id="guide_id" 
                                            class="form-control @error('guide_id') is-invalid @enderror" required>
                                            <option value="">Select Employee</option>
                                            @foreach($guides as $guide)
                                                <option value="{{ $guide->id }}" {{ old('guide_id') == $guide->id ? 'selected' : '' }}>
                                                    {{ $guide->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('guide_id')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>


                                    <div class="form-group">
                                        <label for="update_date">Effective Date</label>
                                        <input type="text" name="effective_date" id="update_date" 
                                            class="form-control flatpickr @error('update_date') is-invalid @enderror"
                                            value="{{ old('update_date') }}"
                                            placeholder="Select Date..."
                                            required>
                                        @error('update_date')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>


                                    <button type="submit" class="btn btn-primary">Add Salary Update</button>
                                </form>
                            </div>
                        </div>
                    </div>

                </div>
                <!-- end col -->
            </div>

        </div>
        <!-- container-fluid -->

    </div>
    <!-- content -->

    <script>
        $("ul:not(:has(li))").parent().parent().parent().css("display", "none");
    </script>

    <script>
        $(document).ready(function() {
            $('.edit-btn').on('click', function() {
                const id = $(this).data('id');
                const guideId = $(this).data('guide');
                const effectiveDate = $(this).data('date');
                
                // Update form title
                $('#form-title').text('Edit Salary Update');
                
                // Update form action and method
                $('#salary-form').attr('action', `/salary-updates/${id}`);
                $('#method-div').html('@method("PUT")');
                
                // Populate form fields
                $('#guide_id').val(guideId);
                $('#update_date').val(effectiveDate);
                
                // Scroll to form
                $('html, body').animate({
                    scrollTop: $("#salary-form").offset().top
                }, 500);
            });
        });
    </script>

    <script>
        flatpickr("#update_date", {
            dateFormat: "Y-m-d",
            theme: "dark",
            allowInput: true,
            altInput: true,
            altFormat: "d/m/Y",
        });
    </script>

@endsection
