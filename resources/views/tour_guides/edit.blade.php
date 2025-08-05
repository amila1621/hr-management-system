@extends('partials.main')

@section('content')
    <div class="content-page">
        <div class="content">
            <div class="container-fluid">
                <div class="page-title-box">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <div class="page-title-box">
                                <h4 class="page-title">Edit Tour Guide</h4>
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item">
                                        <a href="javascript:void(0);">Home</a>
                                    </li>
                                    <li class="breadcrumb-item active">Edit Tour Guide</li>
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
                                <h4 class="mt-0 header-title">Edit Tour Guide</h4>
                                <form action="{{ route('tour-guides.update', $tourGuide->id) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <div class="form-group">
                                        <label for="name">Name</label>
                                        <input type="text" name="name" class="form-control"
                                            value="{{ old('name', $tourGuide->name) }}" required>
                                        @error('name')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>

                                    <div class="form-group">
                                        <label for="name">Full Name</label>
                                        <input type="text" name="full_name" class="form-control"
                                            value="{{ old('full_name', $tourGuide->full_name) }}" required>
                                        @error('full_name')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>
                                    <div class="form-group">
                                        <label for="name">Supervisor</label>
                                        <select name="supervisor" class="form-control">
                                    
                                                <option value="" disabled {{ !$tourGuide->supervisor ? 'selected' : '' }}>Select Supervisor</option>
                                                @foreach ($supervisors as $supervisor)
                                                    <option value="{{ $supervisor->id }}"
                                                        {{ $tourGuide->supervisor == $supervisor->id ? 'selected' : '' }}>
                                                        {{ $supervisor->name }}
                                                    </option>
                                                @endforeach
                                          
                                            
                                        </select>
                                    </div>


                                    <div class="form-group">
                                        <label for="email">Email</label>
                                        <input type="email" name="email" class="form-control"
                                            value="{{ old('email', $tourGuide->email) }}" required>
                                        @error('email')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>

                                    <div class="form-group">
                                        <label for="phone_number">Phone Number</label>
                                        <input type="text" name="phone_number" class="form-control"
                                            value="{{ old('phone_number', $tourGuide->phone_number) }}">
                                        @error('phone_number')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>

                                    <div class="form-group">
                                        <label for="rate">Note</label>
                                        <input type="text" name="rate" value="{{ old('rate', $tourGuide->rate) }}"
                                            class="form-control">
                                        @error('rate')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>

                                    <div class="form-group">
                                        <label for="allow_report_hours">Allow Report Hours</label>
                                        <select name="allow_report_hours" class="form-control" required>
                                            <option value="1"
                                                {{ old('allow_report_hours', $tourGuide->allow_report_hours) == 1 ? 'selected' : '' }}>
                                                Yes</option>
                                            <option value="0"
                                                {{ old('allow_report_hours', $tourGuide->allow_report_hours) == 0 ? 'selected' : '' }}>
                                                No</option>
                                        </select>
                                        @error('allow_report_hours')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>

                                    <div class="form-group">
                                        <label for="is_intern">Intern Status</label>
                                        <select name="is_intern" class="form-control">
                                            <option value="0" {{ old('is_intern', $tourGuide->user->is_intern ?? 0) == 0 ? 'selected' : '' }}>No</option>
                                            <option value="1" {{ old('is_intern', $tourGuide->user->is_intern ?? 0) == 1 ? 'selected' : '' }}>Yes</option>
                                            <option value="2" {{ old('is_intern', $tourGuide->user->is_intern ?? 0) == 2 ? 'selected' : '' }}>Yes with Housing Compensation</option>
                                        </select>
                                        @error('is_intern')
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
@endsection
