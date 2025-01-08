@extends('partials.main')
@section('content')
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
                                <h4 class="page-title">Manage Vehicles</h4>
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item">
                                        <a href="javascript:void(0);">Home</a>
                                    </li>
                                    <li class="breadcrumb-item">
                                        <a href="javascript:void(0);">Vehicles</a>
                                    </li>
                                    <li class="breadcrumb-item active">View Vehicles</li>
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
                                <h4 class="mt-0 header-title">Vehicle List</h4>

                                <div class="table">
                                    <table id="datatable-buttons"
                                        class="table table-striped table-bordered"
                                        style="border-collapse: collapse; border-spacing: 0; width: 100%">
                                        <thead>
                                            <tr>
                                                <th>Name</th>
                                                <th>Type</th>
                                                <th>Number</th>
                                                <th>Seats</th>
                                                <th>Baby Seats</th>
                                                <th>Status</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($vehicles as $vehicle)
                                                <tr>
                                                    <td>{{ $vehicle->name }}</td>
                                                    <td>{{ $vehicle->type }}</td>
                                                    <td>{{ $vehicle->number }}</td>
                                                    <td>{{ $vehicle->number_of_seats }}</td>
                                                    <td>{{ $vehicle->number_of_baby_seats }}</td>
                                                    <td>{{ $vehicle->status }}</td>
                                                    <td>
                                                        <a href="{{ route('vehicles.edit', $vehicle->id) }}" class="btn btn-warning btn-sm">Edit</a>
                                                        <form action="{{ route('vehicles.destroy', $vehicle->id) }}" method="POST" style="display:inline-block;">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this vehicle?');">Delete</button>
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

                                <h4 class="mt-0 header-title">Add New Vehicle</h4>
                                <form method="POST" action="{{ route('vehicles.store') }}">
                                    @csrf
                                    <div class="form-group">
                                        <label for="name">Vehicle Name</label>
                                        <input type="text" name="name" id="name" 
                                            class="form-control @error('name') is-invalid @enderror" 
                                            value="{{ old('name') }}"
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
                                            <option value="Sedan" {{ old('type') == 'Sedan' ? 'selected' : '' }}>Sedan</option>
                                            <option value="SUV" {{ old('type') == 'SUV' ? 'selected' : '' }}>SUV</option>
                                            <option value="Van" {{ old('type') == 'Van' ? 'selected' : '' }}>Van</option>
                                            <option value="Bus" {{ old('type') == 'Bus' ? 'selected' : '' }}>Bus</option>
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
                                            value="{{ old('number') }}"
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
                                            value="{{ old('number_of_seats') }}"
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
                                            value="{{ old('number_of_baby_seats') }}"
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
                                            <option value="1" {{ old('status') == '1' ? 'selected' : '' }}>Active</option>
                                            <option value="2" {{ old('status') == '2' ? 'selected' : '' }}>Maintenance</option>
                                            <option value="0" {{ old('status') == '0' ? 'selected' : '' }}>Inactive</option>
                                        </select>
                                        @error('status')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>

                                    <button type="submit" class="btn btn-primary">Add Vehicle</button>
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


@endsection
