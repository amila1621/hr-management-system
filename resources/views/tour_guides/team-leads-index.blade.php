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
                                <h4 class="page-title">Manage Operations</h4>
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item">
                                        <a href="javascript:void(0);">Home</a>
                                    </li>
                                    <li class="breadcrumb-item">
                                        <a href="javascript:void(0);">Bus Driver Supervisors</a>
                                    </li>
                                    <li class="breadcrumb-item active">View Bus Driver Supervisors</li>
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
                                <h4 class="mt-0 header-title">Bus Driver Supervisors</h4>

                                <div class="table-responsive">
                                    <table id="datatable-buttons"
                                        class="table table-striped table-bordered dt-responsive nowrap"
                                        style="border-collapse: collapse; border-spacing: 0; width: 100%">
                                        <thead>
                                            <tr>
                                                <th>Name</th>
                                                <th>Email</th>
                                                <th>Phone Number</th>
                                                <th>Rate</th>
                                                <th>Color</th>
                                                <th>Intern Status</th>
                                                <th>Allow Report Hours</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($teamLeads as $teamLead)
                                                <tr>
                                                    <td>{{ $teamLead->name }}</td>
                                                    <td>{{ $teamLead->email }}</td>
                                                    <td>{{ $teamLead->teamLead->phone_number }}</td>
                                                    <td>{{ $teamLead->teamLead->rate }}</td>
                                                    <td>{{ $teamLead->teamLead->color }}</td>
                                                    <td>{{ $teamLead->is_intern ? 'Yes' : 'No' }}</td>
                                                    <td>{{ $teamLead->teamLead->allow_report_hours ? 'Yes' : 'No' }}</td>
                                                    <td>
                                                        <a href="{{ route('team-leads.edit', $teamLead->id) }}"
                                                            class="btn btn-sm btn-primary">Edit</a>


                                                            <form action="{{ route('team-leads.destroy', $teamLead->id) }}"
                                                                method="get" style="display:inline-block;">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="btn btn-sm btn-danger"
                                                                    onclick="return confirm('Are you sure you want to delete this supervisor?')">Delete</button>
                                                            </form>

                                                        <button type="button" class="btn btn-sm btn-warning"
                                                            data-toggle="modal"
                                                            data-target="#changePasswordModal{{ $teamLead->id }}">
                                                            Change Password
                                                        </button>
                                                    </td>
                                                </tr>

                                                <!-- Change Password Modal -->
                                                <div class="modal fade" id="changePasswordModal{{ $teamLead->id }}"
                                                    tabindex="-1" role="dialog"
                                                    aria-labelledby="changePasswordModalLabel{{ $teamLead->id }}"
                                                    aria-hidden="true">
                                                    <div class="modal-dialog" role="document">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title"
                                                                    id="changePasswordModalLabel{{ $teamLead->id }}">
                                                                    Change Password for {{ $teamLead->name }}</h5>
                                                                <button type="button" class="close" data-dismiss="modal"
                                                                    aria-label="Close">
                                                                    <span aria-hidden="true">&times;</span>
                                                                </button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <form
                                                                    action="{{ route('operations.change-password', $teamLead->id) }}"
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
