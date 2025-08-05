@extends('partials.main')

@section('content')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    /* Dark theme for Select2 */
    .select2-container--default .select2-selection--multiple,
    .select2-container--default .select2-selection--single {
        background-color: #242d3e !important; /* Red background */
        border: 1px solid #404e57;
        color: #fff;
        min-height: 38px;
    }

    .select2-container--default .select2-selection--multiple .select2-selection__choice {
        background-color: #242d3e;
        border: 1px solid #446c99;
        color: #fff;
    }

    .select2-container--classic .select2-selection--multiple .select2-selection__choice {
        background-color: #242d3e;
    }
    .select2-container--classic .select2-search--inline .select2-search__field {
        background:  #375a7f;
    }

    .select2-container--default .select2-selection__choice__remove {
        color: #fff;
    }

    .select2-container--classic .select2-selection--multiple {
        background-color: #3f4f69;
        border: 1px solid #3f4f69;
    }

    .select2-dropdown {
        background-color: #404e57 !important; /* Red background */
        border: 1px solid #404e57;
    }

    .select2-container--default .select2-search--dropdown .select2-search__field {
        background-color: #404e57 !important; /* Red background */
        border: 1px solid #404e57;
        color: #fff;
    }

    .select2-container--default .select2-results__option {
        color: #fff;
    }

    .select2-container--default .select2-results__option[aria-selected=true] {
        background-color: #3f4f69;
    }

    .select2-container--default .select2-results__option--highlighted[aria-selected] {
        background-color: #446c99;
        color: #fff;
    }

    /* Added styles for better visibility */
    .select2-container--default .select2-selection--multiple .select2-selection__rendered {
        background-color: #404e57 !important; /* Red background */
        color: #fff;
    }

    .select2-container--default .select2-selection--multiple .select2-selection__placeholder {
        color: #fff;
    }

    /* Fix for multiple select height */
    .select2-container--default .select2-selection--multiple .select2-selection__rendered {
        padding: 4px 8px;
    }
</style>

    <div class="content-page">
        <div class="content">
            <div class="container-fluid">
                <div class="page-title-box">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <div class="page-title-box">
                                <h4 class="page-title">Edit Office Workers</h4>
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item">
                                        <a href="javascript:void(0);">Home</a>
                                    </li>
                                    <li class="breadcrumb-item active">Edit Office Workers</li>
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
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="mt-0 header-title">Edit Staff</h4>
                                <form action="{{ route('staff.update', $staffUser->id) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <div class="form-group">
                                        <label for="name">Name</label>
                                        <input type="text" name="name" class="form-control"
                                            value="{{ old('name', $staffUser->name) }}" required>
                                        @error('name')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>

                                    <div class="form-group">
                                        <label for="name">Full Name</label>
                                        <input type="text" name="full_name" class="form-control"
                                            value="{{ old('full_name', $staffUser->full_name) }}" required>
                                        @error('full_name')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>
                                    <!-- <div class="form-group">
                                        <label for="name">Supervisor</label>
                                        <select name="supervisor" class="form-control">
                                    
                                                <option value="" disabled {{ !$staffUser->supervisor ? 'selected' : '' }}>Select Supervisor</option>
                                                @foreach ($supervisors as $supervisor)
                                                    <option value="{{ $supervisor->id }}"
                                                        {{ $staffUser->supervisor == $supervisor->id ? 'selected' : '' }}>
                                                        {{ $supervisor->name }}
                                                    </option>
                                                @endforeach
                                          
                                            
                                        </select>
                                    </div> -->

                                    <div class="form-group">
                                        <label for="department">Department</label>
                                        <select name="department" class="form-control">
                                            @php
                                            $departments = App\Models\Departments::orderBy('department')->pluck('department')->toArray();
                
                                            @endphp
                                            @foreach($departments as $dept)
                                                <option value="{{ $dept }}" 
                                                    {{ old('department', $staffUser->department) == $dept ? 'selected' : '' }}>
                                                    {{ $dept }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label for="email">Email</label>
                                        <input type="email" name="email" class="form-control"
                                            value="{{ old('email', $staffUser->email) }}" required>
                                        @error('email')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>

                                    <div class="form-group">
                                        <label for="phone_number">Phone Number</label>
                                        <input type="text" name="phone_number" class="form-control"
                                            value="{{ old('phone_number', $staffUser->phone_number) }}">
                                        @error('phone_number')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>

                                    <div class="form-group">
                                        <label for="rate">Note</label>
                                        <input type="text" name="rate" value="{{ old('rate', $staffUser->rate) }}"
                                            class="form-control">
                                        @error('rate')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>

                                    <div class="form-group">
                                        <label for="allow_report_hours">Allow Report Hours</label>
                                        <select name="allow_report_hours" class="form-control" required>
                                            <option value="1"
                                                {{ old('allow_report_hours', $staffUser->allow_report_hours) == 1 ? 'selected' : '' }}>
                                                Yes</option>
                                            <option value="0"
                                                {{ old('allow_report_hours', $staffUser->allow_report_hours) == 0 ? 'selected' : '' }}>
                                                No</option>
                                        </select>
                                        @error('allow_report_hours')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>

                                    <div class="form-group">
                                        <label for="is_intern">Intern Status</label>
                                        <select name="is_intern" class="form-control">
                                            <option value="0" {{ old('is_intern', $staffUser->user->is_intern ?? 0) == 0 ? 'selected' : '' }}>No</option>
                                            <option value="1" {{ old('is_intern', $staffUser->user->is_intern ?? 0) == 1 ? 'selected' : '' }}>Yes</option>
                                            <option value="2" {{ old('is_intern', $staffUser->user->is_intern ?? 0) == 2 ? 'selected' : '' }}>Yes with Housing Compensation</option>
                                       
                                        </select>
                                        @error('is_intern')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>

                                    <div class="form-group">
                                        <label for="color">Select Color</label>
                                        <input type="color" name="color" id="color-picker" class="form-control" value="{{ old('color', $staffUser->color ?? '#000000') }}">
                                        <input type="text" name="color_hex" id="color-hex" class="form-control mt-2" readonly value="{{ old('color_hex', $staffUser->color ?? '#000000') }}">
                                        @error('color')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>

                                    <button type="submit" class="btn btn-primary waves-effect waves-light">Update</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.select2').select2({
                placeholder: "Select Supervisors",
                allowClear: true,
                theme: "classic",
                width: '100%',
                dropdownAutoWidth: true,
                containerCssClass: 'select2-dark',
                dropdownCssClass: 'select2-dark',
                templateResult: formatOption,
                templateSelection: formatOption
            });
            
            function formatOption(option) {
                if (!option.id) return option.text;
                return $('<span style="color: #fff;">' + option.text + '</span>');
            }
        });
    </script>

@endsection



