@extends('partials.main')

@section('content')
    <div class="content-page">
        <div class="content">
            <div class="container-fluid">
                <div class="page-title-box">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <div class="page-title-box">
                                <h4 class="page-title">Create User</h4>
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item">
                                        <a href="javascript:void(0);">Home</a>
                                    </li>
                                    <li class="breadcrumb-item active">Create User</li>
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
                                <h4 class="mt-0 header-title">Create New User</h4>
                                <form action="{{ route('new-users.store') }}" method="POST">
                                    @csrf
                                    <div class="form-group">
                                        <label for="name">Name</label>
                                        <input type="text" name="name" class="form-control" required
                                            value="{{ old('name') }}">
                                        @error('name')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>

                                    <div class="form-group">
                                        <label for="name">Full Name</label>
                                        <input type="text" name="full_name" class="form-control" required
                                            value="{{ old('full_name') }}">
                                        @error('full_name')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>

                                    <div class="form-group">
                                        <label for="Role">Role</label>
                                        <select name="role" class="form-control" id="role-select" required>
                                            <option value="guide" {{ old('role') == 'guide' ? 'selected' : '' }}>Guide
                                            </option>
                                            <option value="staff" {{ old('role') == 'staff' ? 'selected' : '' }}>Office workers
                                            </option>
                                            @if (Auth::user()->role == 'admin')
                                                <option value="supervisor" {{ old('role') == 'supervisor' ? 'selected' : '' }}> Supervisor</option>
                                                <option value="operation" {{ old('role') == 'operation' ? 'selected' : '' }}>Operation</option>
                                                <option value="admin" {{ old('role') == 'admin' ? 'selected' : '' }}>Admin</option>
                                                <option value="team-lead" {{ old('role') == 'team-lead' ? 'selected' : '' }}>Team Lead</option>
                                                <option value="hr-assistant" {{ old('role') == 'hr-assistant' ? 'selected' : '' }}>HR Assistant</option>
                                            @endif

                                        </select>
                                        @error('role')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>

                                    <!-- Fields specific to guide -->
                                    <div id="guide-fields" class="hr-assistant-fields" style="display: none;">
                                        <div class="form-group">
                                            <label for="phone_number">Phone Number</label>
                                            <input type="text" name="phone_number" class="form-control"
                                                value="{{ old('phone_number') }}">
                                            @error('phone_number')
                                                <small class="text-danger">{{ $message }}</small>
                                            @enderror
                                        </div>
                                        <div class="form-group">
                                            <label for="rate">Rate</label>
                                            <input type="text" name="rate" class="form-control"
                                                value="{{ old('rate') }}">
                                            @error('rate')
                                                <small class="text-danger">{{ $message }}</small>
                                            @enderror
                                        </div>
                                        <div class="form-group not-hr-assistant-fields">
                                            <label for="allow_report_hours">Allow Report Hours for Work</label>
                                            <select name="allow_report_hours" class="form-control">
                                                <option value="1"
                                                    {{ old('allow_report_hours') == '1' ? 'selected' : '' }}>Yes</option>
                                                <option value="0"
                                                    {{ old('allow_report_hours') == '0' ? 'selected' : '' }}>No</option>
                                            </select>
                                            @error('allow_report_hours')
                                                <small class="text-danger">{{ $message }}</small>
                                            @enderror
                                        </div>
                                        @if (Auth::user()->role == 'admin')
                                            <div class="form-group not-hr-assistant-fields">
                                                <label for="supervisor">Supervisor</label>
                                                <select name="supervisor" class="form-control">
                                                    @foreach ($supervisors as $supervisor)
                                                        <option value="{{ $supervisor->id }}"
                                                            {{ old('supervisor') == $supervisor->id ? 'selected' : '' }}>
                                                            {{ $supervisor->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @error('supervisor')
                                                    <small class="text-danger">{{ $message }}</small>
                                                @enderror
                                            </div>
                                        @else
                                            <input type="hidden" name="supervisor" value="{{ Auth::id() }}">
                                        @endif

                                    </div>

                                    <div class="form-group">
                                        <label for="email">Email</label>
                                        <input type="email" name="email" class="form-control"
                                            value="{{ old('email') }}">
                                        @error('email')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>

                                    <div class="form-group">
                                        <label for="password">Password</label>
                                        <input type="password" name="password" class="form-control" required>
                                        @error('password')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>

                                    <div class="form-group">
                                        <label for="password_confirmation">Confirm Password</label>
                                        <input type="password" name="password_confirmation" class="form-control" required>
                                        @error('password_confirmation')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>

                                    <div class="form-group">
                                        <label for="is_intern">Intern Status</label>
                                        <select name="is_intern" class="form-control">
                                            <option value="0" {{ old('is_intern') == '0' ? 'selected' : '' }}>No</option>
                                            <option value="1" {{ old('is_intern') == '1' ? 'selected' : '' }}>Yes</option>
                                        </select>
                                        @error('is_intern')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>

                                    <div class="form-group">
                                        <label for="color">Select Color</label>
                                        <input type="color" name="color" id="color-picker" class="form-control" value="{{ old('color', '#000000') }}">
                                        <input type="text" name="color_hex" id="color-hex" class="form-control mt-2" readonly value="{{ old('color_hex', '#000000') }}">
                                        @error('color')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>

                                    <div class="form-group supervisor-fields" style="display: none;">
                                        <label for="display_midnight_phone">Display Midnight Phone</label>
                                        <select name="display_midnight_phone" class="form-control">
                                            <option value="0" {{ old('display_midnight_phone') == '0' ? 'selected' : '' }}>No</option>
                                            <option value="1" {{ old('display_midnight_phone') == '1' ? 'selected' : '' }}>Yes</option>
                                        </select>
                                        @error('display_midnight_phone')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>

                                    <button type="submit" class="btn btn-primary waves-effect waves-light">Create</button>
                                </form>



                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- jQuery for dynamic field display -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            function toggleGuideFields() {
                if ($('#role-select').val() === 'guide' || $('#role-select').val() === 'staff') {
                    $('#guide-fields').show();
                    $('.supervisor-fields').hide();
                } else if($('#role-select').val() === 'hr-assistant' || $('#role-select').val() === 'team-lead' || $('#role-select').val() === 'operation') {
                    $('.hr-assistant-fields').show();
                    $('.not-hr-assistant-fields').hide();
                    $('.supervisor-fields').hide();
                } else if($('#role-select').val() === 'supervisor') {
                    $('.hr-assistant-fields').show();
                    $('.supervisor-fields').show();
                    $('.not-hr-assistant-fields').hide();
                } else {
                    $('#guide-fields').hide();
                    $('.supervisor-fields').hide();
                }
            }

            // Run the function on page load to handle pre-selected values
            toggleGuideFields();

            // Listen for changes in the role select dropdown
            $('#role-select').on('change', function() {
                toggleGuideFields();
            });

            // Handle color picker changes
            $('#color-picker').on('input', function() {
                $('#color-hex').val($(this).val());
            });
        });
    </script>
@endsection
