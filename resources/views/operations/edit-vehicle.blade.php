@extends('partials.main')
@section('content')
    <div class="content-page">
        <div class="content">
            <div class="container-fluid">
                <div class="page-title-box">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <div class="page-title-box">
                                <h4 class="page-title">Edit Vehicle</h4>
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="javascript:void(0);">Home</a></li>
                                    <li class="breadcrumb-item"><a href="{{ route('vehicles.index') }}">Vehicles</a></li>
                                    <li class="breadcrumb-item active">Edit Vehicle</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                @if ($errors->any())
                                    <div class="alert alert-danger">
                                        <ul class="mb-0">
                                            @foreach ($errors->all() as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                <form action="{{ route('vehicles.update', $vehicle->id) }}" method="POST">
                                    @csrf
                                    @method('PUT')

                                    <div class="form-group">
                                        <label for="name">Vehicle Name</label>
                                        <input type="text" name="name" id="name" 
                                            class="form-control @error('name') is-invalid @enderror"
                                            value="{{ old('name', $vehicle->name) }}" 
                                            placeholder="Enter vehicle name" required>
                                        @error('name')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>

                                    <div class="form-group">
                                        <label for="type">Vehicle Type</label>
                                        <select name="type" id="type" 
                                            class="form-control @error('type') is-invalid @enderror" required>
                                            <option value="">Select Type</option>
                                            <option value="Sedan" {{ old('type', $vehicle->type) == 'Sedan' ? 'selected' : '' }}>Sedan</option>
                                            <option value="SUV" {{ old('type', $vehicle->type) == 'SUV' ? 'selected' : '' }}>SUV</option>
                                            <option value="Van" {{ old('type', $vehicle->type) == 'Van' ? 'selected' : '' }}>Van</option>
                                            <option value="Bus" {{ old('type', $vehicle->type) == 'Bus' ? 'selected' : '' }}>Bus</option>
                                        </select>
                                        @error('type')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>

                                    <div class="form-group">
                                        <label for="number">Vehicle Number</label>
                                        <input type="text" name="number" id="number" 
                                            class="form-control @error('number') is-invalid @enderror"
                                            value="{{ old('number', $vehicle->number) }}" 
                                            placeholder="Enter vehicle number" required>
                                        @error('number')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>

                                    <div class="form-group">
                                        <label for="number_of_seats">Number of Seats</label>
                                        <input type="number" name="number_of_seats" id="number_of_seats" 
                                            class="form-control @error('number_of_seats') is-invalid @enderror"
                                            value="{{ old('number_of_seats', $vehicle->number_of_seats) }}" 
                                            placeholder="Enter number of seats" required>
                                        @error('number_of_seats')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>

                                    <div class="form-group">
                                        <label for="number_of_baby_seats">Number of Baby Seats</label>
                                        <input type="number" name="number_of_baby_seats" id="number_of_baby_seats" 
                                            class="form-control @error('number_of_baby_seats') is-invalid @enderror"
                                            value="{{ old('number_of_baby_seats', $vehicle->number_of_baby_seats) }}" 
                                            placeholder="Enter number of baby seats" required>
                                        @error('number_of_baby_seats')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>

                                    <div class="form-group">
                                        <label for="status">Status</label>
                                        <select name="status" id="status" 
                                            class="form-control @error('status') is-invalid @enderror" required>
                                            <option value="1" {{ old('status', $vehicle->status) == '1' ? 'selected' : '' }}>Active</option>
                                            <option value="2" {{ old('status', $vehicle->status) == '2' ? 'selected' : '' }}>Maintenance</option>
                                            <option value="0" {{ old('status', $vehicle->status) == '0' ? 'selected' : '' }}>Inactive</option>
                                        </select>
                                        @error('status')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>

                                    <div class="form-group">
                                        <button type="submit" class="btn btn-primary">Update Vehicle</button>
                                        <a href="{{ route('vehicles.index') }}" class="btn btn-secondary">Cancel</a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection 