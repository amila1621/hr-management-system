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
                                <h4 class="page-title">Manage Guides</h4>
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item">
                                        <a href="javascript:void(0);">Home</a>
                                    </li>
                                    <li class="breadcrumb-item">
                                        <a href="javascript:void(0);">Guides</a>
                                    </li>
                                    <li class="breadcrumb-item active">View Guides</li>
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
                                <h4 class="mt-0 header-title">Latest Guides</h4>

                                <div class="table-responsive">
                                    <table id="datatable-buttons"
                                        class="table table-striped table-bordered dt-responsive"
                                        style="border-collapse: collapse; border-spacing: 0; width: 100%">
                                        <thead>
                                            <tr>
                                                <th>Name</th>
                                                <th>Full Name</th>
                                                <th>Email</th>
                                                <th>Phone Number</th>
                                                <th>Note</th>
                                                <th>Allow Report Hours</th>
                                                <th>Supervisor</th>
                                                <th>Intern Status</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($tourGuides as $tourGuide)
                                                <tr>
                                                    <td>{{ $tourGuide->name }}</td>
                                                    <td>{{ $tourGuide->full_name }}</td>
                                                    <td>{{ $tourGuide->email }}</td>
                                                    <td>{{ $tourGuide->phone_number }}</td>
                                                    <td>{{ $tourGuide->rate }}</td>
                                                    <td>{{ $tourGuide->allow_report_hours ? 'Yes' : 'No' }}</td>
                                                    <td> @if ($tourGuide->supervisor != null)
                                                            {{ \App\Models\User::find($tourGuide->supervisor)->name ?? 'Unknown Supervisor' }}
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
                                                                <input type="hidden" name="tour_guide_id"
                                                                    value="{{ $tourGuide->id }}">
                                                                <button type="submit" class="btn btn-primary mt-2">Assign
                                                                    Supervisor</button>
                                                            </form>
                                                        @endif
                                                    </td>
                                                    <td>{{ $tourGuide->user->is_intern ? 'Yes' : 'No' }}</td>

                                                    <td>
                                                        <a href="{{ route('tour-guides.edit', $tourGuide->id) }}"
                                                            class="btn btn-sm btn-primary">Edit</a>

                                                        <form action="{{ route('tour-guides.destroy', $tourGuide->id) }}"
                                                            method="POST" style="display:inline-block;">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-sm btn-danger"
                                                                onclick="return confirm('Are you sure you want to delete this tour guide?')">Delete</button>
                                                        </form>


                                                        <!-- Change Password Button -->
                                                        <button type="button" class="btn btn-sm btn-warning"
                                                            data-toggle="modal"
                                                            data-target="#changePasswordModal{{ $tourGuide->id }}">
                                                            Change Password
                                                        </button>

                                                        @if(!$tourGuide->is_hidden)
                                                        <form action="{{ route('tour-guides.hide', $tourGuide->id) }}"
                                                            method="POST" style="display:inline-block;">
                                                            @csrf
                                                            @method('PUT')
                                                            <button type="submit" class="btn btn-sm btn-secondary"
                                                                onclick="return confirm('Are you sure you want to hide this tour guide?')">Hide</button>
                                                        </form>
                                                        @else
                                                        <form action="{{ route('tour-guides.unhide', $tourGuide->id) }}"
                                                            method="POST" style="display:inline-block;">
                                                            @csrf
                                                            @method('PUT')
                                                            <button type="submit" class="btn btn-sm btn-secondary"
                                                                onclick="return confirm('Are you sure you want to unhide this tour guide?')">Unhide</button>
                                                        </form>
                                                        @endif

                                                        
                                                        <form action="{{ route('tour-guides.terminate', $tourGuide->id) }}"
                                                            method="POST" style="display:inline-block;">
                                                            @csrf
                                                            @method('POST')
                                                            <button type="submit" class="btn btn-sm btn-danger"
                                                                onclick="return confirm('Are you sure you want to Terminate this tour guide?')">Terminate</button>
                                                        </form>

                                                        <!-- Make a Guide/Staff Button -->
                                                        @php
                                                            $isAlreadyStaff = \App\Models\StaffUser::where('user_id', $tourGuide->user_id)->exists();
                                                        @endphp
                                                        @if(!$isAlreadyStaff)
                                                        <button type="button" class="btn btn-sm btn-success"
                                                            data-toggle="modal"
                                                            data-target="#makeGuideStaffModal{{ $tourGuide->id }}">
                                                            Make a Guide/Staff
                                                        </button>
                                                        @endif
                                                    </td>
                                                </tr>

                                                <!-- Change Password Modal -->
                                                <div class="modal fade" id="changePasswordModal{{ $tourGuide->id }}"
                                                    tabindex="-1" role="dialog"
                                                    aria-labelledby="changePasswordModalLabel{{ $tourGuide->id }}"
                                                    aria-hidden="true">
                                                    <div class="modal-dialog" role="document">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title"
                                                                    id="changePasswordModalLabel{{ $tourGuide->id }}">
                                                                    Change Password for {{ $tourGuide->name }}</h5>
                                                                <button type="button" class="close" data-dismiss="modal"
                                                                    aria-label="Close">
                                                                    <span aria-hidden="true">&times;</span>
                                                                </button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <form
                                                                    action="{{ route('tour-guides.change-password', $tourGuide->id) }}"
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
                                                <!-- End of Change Password Modal -->

                                                <!-- Make a Guide/Staff Modal -->
                                                @if(!$isAlreadyStaff)
                                                <div class="modal fade" id="makeGuideStaffModal{{ $tourGuide->id }}"
                                                    tabindex="-1" role="dialog"
                                                    aria-labelledby="makeGuideStaffModalLabel{{ $tourGuide->id }}"
                                                    aria-hidden="true">
                                                    <div class="modal-dialog" role="document">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title"
                                                                    id="makeGuideStaffModalLabel{{ $tourGuide->id }}">
                                                                    Make {{ $tourGuide->name }} a Guide/Staff</h5>
                                                                <button type="button" class="close" data-dismiss="modal"
                                                                    aria-label="Close">
                                                                    <span aria-hidden="true">&times;</span>
                                                                </button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <form action="{{ route('tour-guides.make-guide-staff', $tourGuide->id) }}" method="POST">
                                                                    @csrf
                                                                    @method('PUT')
                                                                    <div class="form-group">
                                                                        <label for="department_id">Department</label>
                                                                        <select name="department_id" class="form-control" required>
                                                                            @php
                                                                                $departments = \App\Models\Departments::all();
                                                                            @endphp
                                                                            <option value="">Select Department</option>
                                                                            @foreach ($departments as $department)
                                                                                <option value="{{ $department->id }}">
                                                                                    {{ $department->department }}
                                                                                </option>
                                                                            @endforeach
                                                                        </select>
                                                                    </div>
                                                                    <input type="hidden" name="tour_guide_id" value="{{ $tourGuide->id }}">
                                                                    <button type="submit" class="btn btn-success">
                                                                        Make Guide/Staff
                                                                    </button>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                @endif
                                                <!-- End of Make a Guide/Staff Modal -->
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
