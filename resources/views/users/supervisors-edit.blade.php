@extends('partials.main')

@section('content')
    <div class="content-page">
        <div class="content">
            <div class="container-fluid">
                <div class="page-title-box">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <div class="page-title-box">
                                <h4 class="page-title">Edit Supervisor</h4>
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item">
                                        <a href="javascript:void(0);">Home</a>
                                    </li>
                                    <li class="breadcrumb-item active">Edit Supervisor</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>

                @if (session()->has('success'))
                    <div class="alert alert-success">
                        {{ session()->get('success') }}
                    </div>
                @endif

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
                                <h4 class="mt-0 header-title">Edit Supervisor</h4>
                                <form action="{{ route('supervisors.update', $supervisor->id) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <div class="form-group">
                                        <label for="name">Name</label>
                                        <input type="text" name="name" class="form-control"
                                            value="{{ old('name', $supervisor->user->name) }}" required>
                                        @error('name')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>
                                    <div class="form-group">
                                        <label for="full_name">Full Name</label>
                                        <input type="text" name="full_name" class="form-control"
                                            value="{{ old('full_name', $staffUsers->full_name) }}" required>
                                        @error('full_name')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>

                                    <div class="form-group">
                                        <label for="email">Email</label>
                                        <input type="email" name="email" class="form-control"
                                            value="{{ old('email', $supervisor->user->email) }}" required>
                                        @error('email')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>
                                    <div class="form-group">
                                        <label for="phone_number">Phone Number</label>
                                        <input type="text" name="phone_number" class="form-control"
                                            value="{{ old('phone_number', $supervisor->phone_number) }}" required>
                                        @error('phone_number')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>

                                    <div class="form-group">
                                        <label for="rate">Rate</label>
                                        <input type="text" name="rate" class="form-control"
                                            value="{{ old('rate', $supervisor->rate) }}" required>
                                        @error('rate')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>

                                    <div class="form-group">
                                        <label for="color">Color</label>
                                        <input type="text" name="color" class="form-control"
                                            value="{{ old('color', $supervisor->color) }}" required>
                                        @error('color')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>

                                    <div class="form-group">
                                        <label for="department">Department(s)</label>
                                        <div class="department-checkbox-container">
                                            @php
                                            $departments = App\Models\Departments::orderBy('department')->pluck('department')->toArray();
                
                                            
                                            // Convert existing department string to array by splitting on commas
                                            $currentDepartments = old('departments', explode(', ', $supervisor->department));
                                            @endphp
                                            
                                            @foreach($departments as $dept)
                                                <div class="custom-control custom-checkbox">
                                                    <input type="checkbox" class="custom-control-input" 
                                                        id="dept-{{ Str::slug($dept) }}" 
                                                        name="departments[]" 
                                                        value="{{ $dept }}" 
                                                        {{ in_array($dept, $currentDepartments) ? 'checked' : '' }}>
                                                    <label class="custom-control-label" for="dept-{{ Str::slug($dept) }}">{{ $dept }}</label>
                                                </div>
                                            @endforeach
                                        </div>
                                        @error('departments')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>

                                    <div class="form-group">
                                        <label for="display_midnight_phone">Display Midnight Phone</label>
                                        <select name="display_midnight_phone" class="form-control">
                                            <option value="0" {{ old('display_midnight_phone', $supervisor->display_midnight_phone) == '0' ? 'selected' : '' }}>No</option>
                                            <option value="1" {{ old('display_midnight_phone', $supervisor->display_midnight_phone) == '1' ? 'selected' : '' }}>Yes</option>
                                        </select>
                                        @error('display_midnight_phone')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>

                        

                                    <div class="form-group">
                                        <label for="is_intern">Intern Status</label>
                                        <select name="is_intern" class="form-control" required>
                                            <option value="1" {{ $supervisor->user->is_intern ? 'selected' : '' }}>Yes</option>
                                            <option value="0" {{ !$supervisor->user->is_intern ? 'selected' : '' }}>No</option>
                                           
                                        </select>
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
@endsection
