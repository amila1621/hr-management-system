@extends('partials.main')
  <!-- Add Flatpickr CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

@section('content')
    <div class="content-page">
        <div class="content">
            <div class="container-fluid">
                <div class="page-title-box">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <div class="page-title-box">
                                <h4 class="page-title">Requested Sick Leaves</h4>
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item">
                                        <a href="javascript:void(0);">Home</a>
                                    </li>
                                    <li class="breadcrumb-item active">Manage Sick Leave Requests</li>
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
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table id="datatable" class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Staff Name</th>
                                                <th>Start Date</th>
                                                <th>End Date</th>
                                                <th>Description</th>
                                                <th>Medical Certificate</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($sickLeaves as $sickLeave)
                                            <tr>
                                                <td>{{ $sickLeave->staff->name ?? 'N/A' }}</td>
                                                <td>{{ \Carbon\Carbon::parse($sickLeave->start_date)->format('d.m.Y') }}</td>
                                                <td>{{ $sickLeave->end_date ? \Carbon\Carbon::parse($sickLeave->end_date)->format('d.m.Y') : 'N/A' }}</td>
                                                <td>{{ $sickLeave->description ?? 'N/A' }}</td>
                                                <td>
                                                    @if($sickLeave->image)
                                                    <a href="{{ Storage::url($sickLeave->image) }}" target="_blank" class="btn btn-sm btn-info">
                                                        <i class="fas fa-file-medical"></i> View
                                                    </a>
                                                    @else
                                                    <span class="badge badge-warning">No Document</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($sickLeave->status == 0)
                                                        <span class="badge badge-warning">Pending Supervisor Approval</span>
                                                    @elseif($sickLeave->status == 1)
                                                        <span class="badge badge-info">Pending HR Approval</span>
                                                        @if($sickLeave->supervisor_remark)
                                                            <div class="remark-container mt-1">
                                                                <div class="remark-info">
                                                                    <strong>Supervisor comment:</strong>
                                                                    <p class="mb-0">{{ $sickLeave->supervisor_remark }}</p>
                                                                </div>
                                                            </div>
                                                        @endif
                                                    @elseif($sickLeave->status == 2)
                                                        <span class="badge badge-success">Approved</span>
                                                        @if($sickLeave->supervisor_remark || $sickLeave->admin_remark)
                                                            <div class="remark-container mt-1">
                                                                @if($sickLeave->supervisor_remark)
                                                                    <div class="remark-info mb-1">
                                                                        <strong>Supervisor comment:</strong>
                                                                        <p class="mb-0">{{ $sickLeave->supervisor_remark }}</p>
                                                                    </div>
                                                                @endif
                                                                @if($sickLeave->admin_remark)
                                                                    <div class="remark-info">
                                                                        <strong>HR comment:</strong>
                                                                        <p class="mb-0">{{ $sickLeave->admin_remark }}</p>
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        @endif
                                                    @elseif($sickLeave->status == 3)
                                                        <span class="badge badge-danger">Rejected</span>
                                                        @if($sickLeave->supervisor_remark || $sickLeave->admin_remark)
                                                            <div class="remark-container mt-1">
                                                                @if($sickLeave->supervisor_remark)
                                                                    <div class="remark-info mb-1">
                                                                        <strong>Supervisor reason:</strong>
                                                                        <p class="mb-0">{{ $sickLeave->supervisor_remark }}</p>
                                                                    </div>
                                                                @endif
                                                                @if($sickLeave->admin_remark)
                                                                    <div class="remark-info">
                                                                        <strong>HR reason:</strong>
                                                                        <p class="mb-0">{{ $sickLeave->admin_remark }}</p>
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        @endif
                                                    @elseif($sickLeave->status == 4)
                                                        <span class="badge badge-secondary">Cancelled</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <div class="btn-group">
                                                        @if($sickLeave->status == 0 && (auth()->user()->role == 'admin' || auth()->id() == $sickLeave->staff_id))
                                                            <form action="{{ route('supervisor-sick-leaves.destroy', $sickLeave->id) }}" 
                                                                method="POST" 
                                                                class="d-inline delete-form">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="btn btn-sm btn-danger delete-btn">
                                                                    <i class="fas fa-trash"></i> Delete
                                                                </button>
                                                            </form>
                                                        @endif
                                                        
                                                        @if(auth()->user()->role == 'supervisor' && $sickLeave->status == 0)
                                                            <button class="btn btn-sm btn-success approve-btn ml-1" 
                                                                    data-id="{{ $sickLeave->id }}">
                                                                <i class="fas fa-check"></i> Approve
                                                            </button>
                                                            <button class="btn btn-sm btn-danger reject-btn ml-1" 
                                                                    data-id="{{ $sickLeave->id }}">
                                                                <i class="fas fa-times"></i> Reject
                                                            </button>
                                                        @endif
                                                        
                                                        @if(auth()->user()->role == 'admin' && $sickLeave->status == 1)
                                                            <button class="btn btn-sm btn-success admin-approve-btn ml-1" 
                                                                    data-id="{{ $sickLeave->id }}">
                                                                <i class="fas fa-check"></i> HR Approve
                                                            </button>
                                                            <button class="btn btn-sm btn-danger admin-reject-btn ml-1" 
                                                                    data-id="{{ $sickLeave->id }}">
                                                                <i class="fas fa-times"></i> HR Reject
                                                            </button>
                                                        @endif
                                                    </div>
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
@endsection

<!-- Rejection Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1" role="dialog" aria-labelledby="rejectModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="rejectModalLabel">Reject Sick Leave Request</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="rejectForm" method="POST">
                    @csrf
                    @method('PATCH')
                    <div class="form-group">
                        <label for="rejection_reason">Reason for Rejection</label>
                        <textarea class="form-control" id="rejection_reason" name="rejection_reason" rows="3" required></textarea>
                    </div>
                    <input type="hidden" name="status" value="3">
                    <button type="submit" class="btn btn-danger">Confirm Rejection</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript" src="https://cdn.jsdelivr.net/jquery/latest/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function() {
    // Initialize DataTable
    $('#datatable').DataTable({
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        order: [[2, 'desc']] // Sort by start date by default
    });
    
    // Delete button handler
    $('.delete-btn').click(function(e) {
        e.preventDefault();
        const form = $(this).closest('form');
        
        Swal.fire({
            title: 'Are you sure?',
            text: "This sick leave request will be permanently deleted!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    });
    
    // Approve button handler (Supervisor)
    $('.approve-btn').click(function() {
        const id = $(this).data('id');
        
        Swal.fire({
            title: 'Approve sick leave?',
            text: "This will move the request to HR for final approval",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, approve it!'
        }).then((result) => {
            if (result.isConfirmed) {
                // Create and submit form
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = `/supervisor-sick-leaves/${id}/approve`;
                
                const csrfToken = document.createElement('input');
                csrfToken.type = 'hidden';
                csrfToken.name = '_token';
                csrfToken.value = '{{ csrf_token() }}';
                
                const methodField = document.createElement('input');
                methodField.type = 'hidden';
                methodField.name = '_method';
                methodField.value = 'PATCH';
                
                form.appendChild(csrfToken);
                form.appendChild(methodField);
                document.body.appendChild(form);
                form.submit();
            }
        });
    });
    
    // Reject button handler (Supervisor)
    $('.reject-btn').click(function() {
        const id = $(this).data('id');
        $('#rejectForm').attr('action', `/supervisor-sick-leaves/${id}/reject`);
        $('#rejectModal').modal('show');
    });
    
    // Admin Approve Button
    $('.admin-approve-btn').click(function() {
        const id = $(this).data('id');
        
        Swal.fire({
            title: 'Final approval',
            text: "Approve this sick leave request?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, approve it!'
        }).then((result) => {
            if (result.isConfirmed) {
                // Create and submit form
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = `/supervisor-sick-leaves/${id}/admin-approve`;
                
                const csrfToken = document.createElement('input');
                csrfToken.type = 'hidden';
                csrfToken.name = '_token';
                csrfToken.value = '{{ csrf_token() }}';
                
                const methodField = document.createElement('input');
                methodField.type = 'hidden';
                methodField.name = '_method';
                methodField.value = 'PATCH';
                
                form.appendChild(csrfToken);
                form.appendChild(methodField);
                document.body.appendChild(form);
                form.submit();
            }
        });
    });
    
    // Admin Reject Button
    $('.admin-reject-btn').click(function() {
        const id = $(this).data('id');
        $('#rejectForm').attr('action', `/supervisor-sick-leaves/${id}/admin-reject`);
        $('#rejectModal').modal('show');
    });
});
</script>

<style>
    .content-page {
        position: relative;
        height: 100vh;
        overflow: hidden;
    }

    .content {
        height: calc(100vh - 70px);
        overflow-y: auto;
        padding-bottom: 60px;
    }

    .content {
        scroll-behavior: smooth;
        -webkit-overflow-scrolling: touch;
    }

    .footer {
        position: fixed;
        bottom: 0;
        right: 0;
        left: 240px;
        z-index: 100;
        background: #fff;
    }

    .btn-group .btn {
        margin-right: 2px;
    }
    
    /* Status badge styling */
    .badge-warning {
        background-color: #ffc107;
    }
    
    .badge-info {
        background-color: #17a2b8;
    }
    
    .badge-success {
        background-color: #28a745;
    }
    
    .badge-danger {
        background-color: #dc3545;
    }
    
    .badge-secondary {
        background-color: #6c757d;
    }

    .remark-container {
        background-color: #f8f9fa;
        border-radius: 4px;
        padding: 6px 8px;
        font-size: 0.85rem;
        max-width: 300px;
        border-left: 3px solid #17a2b8;
    }

    .remark-info strong {
        font-size: 0.75rem;
        text-transform: uppercase;
        color: #495057;
        display: block;
    }

    .remark-info p {
        font-size: 0.85rem;
        color: #212529;
        word-break: break-word;
    }

    /* Change border color based on status */
    .badge-success + .remark-container {
        border-left-color: #28a745;
    }

    .badge-danger + .remark-container {
        border-left-color: #dc3545;
    }

    .badge-info + .remark-container {
        border-left-color: #17a2b8;
    }
</style>