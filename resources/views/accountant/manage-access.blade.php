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
                                <h4 class="page-title">Manage Access</h4>
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item">
                                        <a href="javascript:void(0);">Home</a>
                                    </li>
                                    <li class="breadcrumb-item">
                                        <a href="javascript:void(0);">Accountant Report</a>
                                    </li>
                                    <li class="breadcrumb-item active">Manage Access</li>
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
                                    </div>
                                @endif
                                <style>
                                    .form-check-input {
                                        width: 15px;
                                        height: 15px;
                                        cursor: pointer;
                                    }
                                    .table td.text-center {
                                        vertical-align: middle;
                                    }
                                    .dataTables_filter {
                                        margin-bottom: 15px;
                                    }
                                    .dataTables_filter input {
                                        border-radius: 4px;
                                        padding: 5px 10px;
                                    }
                                    .checkbox-cell {
                                        padding: 0 !important;
                                        height: 45px;
                                    }
                                    .checkbox-label {
                                        display: flex;
                                        align-items: center;
                                        justify-content: center;
                                        width: 100%;
                                        height: 100%;
                                        margin: 0;
                                        cursor: pointer;
                                        padding: 10px;
                                    }
                                    .checkbox-label:hover {
                                        background-color: rgba(0,0,0,0.05);
                                    }
                                </style>
                                <form action="{{ route('accountant.update.access') }}" method="POST">
                                    @csrf
                                    <!-- Add top save button -->
                                    <div class="text-end mb-3">
                                        <button type="submit" class="btn btn-primary">Save Changes</button>
                                    </div>

                                    <div class="table-responsive">
                                        <table class="table table-bordered" id="accessTable">
                                            <thead>
                                                <tr>
                                                    <th>Employee Name</th>
                                                    @foreach($types as $type)
                                                        <th>{{ $type->name }} ({{ $type->unit }})</th>
                                                    @endforeach
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($users as $employee)
                                                <tr>
                                                    <td>{{ $employee->name }}</td>
                                                    @foreach($types as $type)
                                                        @php
                                                            $accessTypeKey = Str::snake($type->name);
                                                        @endphp
                                                        <td class="checkbox-cell">
                                                            <label class="checkbox-label">
                                                                <input type="hidden" name="access[{{ $employee->id }}][{{ $type->name }}]" value="0">
                                                                <input type="checkbox" name="access[{{ $employee->id }}][{{ $type->name }}]" 
                                                                       value="1"
                                                                       {{ $employee->hasAccess($accessTypeKey) ? 'checked' : '' }}
                                                                       class="form-check-input">
                                                            </label>
                                                        </td>
                                                    @endforeach
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
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

@section('scripts')
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script>
    $(document).ready(function() {
        $('#accessTable').DataTable({
            "scrollX": true,
            "pageLength": 25,
            "columnDefs": [
                { "orderable": false, "targets": "_all" }
            ]
        });
    });
</script>
@endsection
