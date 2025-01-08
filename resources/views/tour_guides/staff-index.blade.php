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
                                <h4 class="page-title">Manage Office Worker</h4>
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item">
                                        <a href="javascript:void(0);">Home</a>
                                    </li>
                                    <li class="breadcrumb-item">
                                        <a href="javascript:void(0);">Office Workers</a>
                                    </li>
                                    <li class="breadcrumb-item active">View Office Workers</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- end page-title -->

                <div class="row">
                    @if (session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif

                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="mt-0 header-title">Latest Office Workers</h4>

                                <div class="table-responsive">
                                    <table id="datatable-buttons"
                                        class="table table-striped table-bordered dt-responsive"
                                        style="border-collapse: collapse; border-spacing: 0; width: 100%">
                                        <thead>
                                            <tr>
                                                <th>Name</th>
                                                <th>Email</th>
                                                <th>Phone Number</th>
                                                <th>Note</th>
                                                <th>Allow Report Hours</th>
                                                <th>Supervisor</th>
                                                <th>Color</th>
                                                <th>Intern Status</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($staffUsers as $staffUser)
                                                <tr>
                                                    <td>{{ $staffUser->name }}</td>
                                                    <td>{{ $staffUser->email }}</td>
                                                    <td>{{ $staffUser->phone_number }}</td>
                                                    <td>{{ $staffUser->rate }}</td>
                                                    <td>{{ $staffUser->allow_report_hours ? 'Yes' : 'No' }}</td>
                                                    <td>
                                                        @if ($staffUser->supervisor != null)
                                                            {{ \App\Models\User::find($staffUser->supervisor)->name ?? 'Unknown Supervisor' }}
                                                        @else
                                                            <form action="/add-supervisor" method="POST">
                                                                @csrf
                                                                <select name="supervisor_id" required class="form-control" required>
                                                                    @php
                                                                        $supervisors = \App\Models\User::where(
                                                                            'role',
                                                                            'supervisor',
                                                                        )->get();
                                                                    @endphp
                                                                    <option value="">Select Supervisor</option>
                                                                    @foreach ($supervisors as $supervisor)
                                                                        <option value="{{ $supervisor->id }}">
                                                                            {{ $supervisor->name }}</option>
                                                                    @endforeach
                                                                </select>
                                                                <input type="hidden" name="staff_user_id"
                                                                    value="{{ $staffUser->id }}">
                                                                <button type="submit" class="btn btn-primary mt-2">Assign
                                                                    Supervisor</button>
                                                            </form>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <div style="display: flex; align-items: center;">
                                                            <div style="width: 20px; height: 20px; background-color: {{ $staffUser->color }}; margin-right: 10px; border: 1px solid #ccc;"></div>
                                                            {{ $staffUser->color }}
                                                        </div>
                                                    </td>
                                                    <td>{{ $staffUser->user->is_intern ? 'Yes' : 'No' }}</td>
                                                    <td>
                                                        <a href="{{ route('staff.edit', $staffUser->id) }}"
                                                            class="btn btn-sm btn-primary">Edit</a>

                                                        <form action="{{ route('staff.destroy', $staffUser->id) }}"
                                                            method="POST" style="display:inline-block;">
                                                            @csrf
                                                            @method('GET')
                                                            <button type="submit" class="btn btn-sm btn-danger"
                                                                onclick="return confirm('Are you sure you want to delete this staff?')">Delete</button>
                                                        </form>

                                                        <!-- Change Password Button -->
                                                        <button type="button" class="btn btn-sm btn-warning"
                                                            data-toggle="modal"
                                                            data-target="#changePasswordModal{{ $staffUser->id }}">
                                                            Change Password
                                                        </button>
                                                    </td>
                                                </tr>

                                                <!-- Change Password Modal -->
                                                <div class="modal fade" id="changePasswordModal{{ $staffUser->id }}"
                                                    tabindex="-1" role="dialog"
                                                    aria-labelledby="changePasswordModalLabel{{ $staffUser->id }}"
                                                    aria-hidden="true">
                                                    <div class="modal-dialog" role="document">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title"
                                                                    id="changePasswordModalLabel{{ $staffUser->id }}">
                                                                    Change Password for {{ $staffUser->name }}</h5>
                                                                <button type="button" class="close" data-dismiss="modal"
                                                                    aria-label="Close">
                                                                    <span aria-hidden="true">&times;</span>
                                                                </button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <form
                                                                    action="{{ route('staff.change-password', $staffUser->id) }}"
                                                                    method="POST">
                                                                    @csrf
                                                                    @method('PUT')
                                                                    <div class="form-group">
                                                                        <label for="password">New Password</label>
                                                                        <input type="password" name="password"
                                                                            class="form-control" required>
                                                                    </div>
                                                                    <div class="form-group">
                                                                        <label for="password_confirmation">Confirm
                                                                            Password</label>
                                                                        <input type="password" name="password_confirmation"
                                                                            class="form-control" required>
                                                                    </div>
                                                                    <button type="submit" class="btn btn-primary">Change
                                                                        Password</button>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <!-- End of Modal -->
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
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
