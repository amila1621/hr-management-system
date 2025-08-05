@extends('partials.main')
@section('content')
<link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">

<div class="content-page">
    <div class="content">
        <div class="container-fluid">
            <div class="page-title-box">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <div class="page-title-box">
                            <h4 class="page-title">Manage Income/Expense Types</h4>
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item">
                                    <a href="javascript:void(0);">Home</a>
                                </li>
                                <li class="breadcrumb-item">
                                    <a href="javascript:void(0);">Accountant Report</a>
                                </li>
                                <li class="breadcrumb-item active">Manage Income/Expense Types</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            @if(session('success'))
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    {{ session('success') }}
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                            @endif

                            <div class="text-end mb-3">
                                <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addTypeModal">
                                    Add New Type
                                </button>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-bordered" id="typesTable">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Type</th>
                                            <th>Unit</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($types as $type)
                                        <tr>
                                            <td>{{ $type->name }}</td>
                                            <td>{{ ucfirst($type->type) }}</td>
                                            <td>{{ $type->unit }}</td>
                                            <td>
                                                <span class="badge {{ $type->active ? 'badge-success' : 'badge-danger' }}">
                                                    {{ $type->active ? 'Active' : 'Inactive' }}
                                                </span>
                                            </td>
                                            <td>
                                              
                                                <form action="{{ route('accountant.delete-income-expense', $type->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger delete-btn" onclick="return confirm('Are you sure you want to delete this item?')">Delete</button>
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
            </div>
        </div>
    </div>
</div>

<!-- Add Type Modal -->
<div class="modal fade" id="addTypeModal" tabindex="-1" role="dialog" aria-labelledby="addTypeModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addTypeModalLabel">Add New Income/Expense Type</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('accountant.store-income-expense') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-group mb-3">
                        <label for="name">Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="form-group mb-3">
                        <label for="type">Type</label>
                        <select class="form-control" id="type" name="type" required>
                            <option value="income">Income</option>
                            <option value="expense">Expense</option>
                        </select>
                    </div>
                    <div class="form-group mb-3">
                        <label for="unit">Unit</label>
                        <input type="text" class="form-control" id="unit" name="unit" value="EUR" required>
                        <small class="form-text text-muted">Examples: EUR, USD, km, hours, etc.</small>
                    </div>
                    <div class="form-check mb-3">
                        <input type="checkbox" class="form-check-input" id="active" name="active" checked>
                        <label class="form-check-label" for="active">Active</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Type Modal -->
<div class="modal fade" id="editTypeModal" tabindex="-1" role="dialog" aria-labelledby="editTypeModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editTypeModalLabel">Edit Income/Expense Type</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="editTypeForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="form-group mb-3">
                        <label for="edit_name">Name</label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>
                    <div class="form-group mb-3">
                        <label for="edit_type">Type</label>
                        <select class="form-control" id="edit_type" name="type" required>
                            <option value="income">Income</option>
                            <option value="expense">Expense</option>
                        </select>
                    </div>
                    <div class="form-group mb-3">
                        <label for="edit_unit">Unit</label>
                        <input type="text" class="form-control" id="edit_unit" name="unit" required>
                        <small class="form-text text-muted">Examples: EUR, USD, km, hours, etc.</small>
                    </div>
                    <div class="form-check mb-3">
                        <input type="checkbox" class="form-check-input" id="edit_active" name="active">
                        <label class="form-check-label" for="edit_active">Active</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

@section('scripts')
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script>
    $(document).ready(function() {
        $('#typesTable').DataTable({
            "order": [[0, "asc"]],
            "pageLength": 25
        });
        
        // Edit modal data population
        $('.edit-btn').on('click', function() {
            const id = $(this).data('id');
            const name = $(this).data('name');
            const type = $(this).data('type');
            const unit = $(this).data('unit');
            const active = $(this).data('active');
            
            $('#edit_name').val(name);
            $('#edit_type').val(type);
            $('#edit_unit').val(unit);
            $('#edit_active').prop('checked', active == 1);
            
            $('#editTypeForm').attr('action', "{{ route('accountant.update-income-expense', '') }}/" + id);
        });
    });
</script>
@endsection

@endsection
