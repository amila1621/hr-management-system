@extends('partials.main')
@section('content')

<style>
    .thumbnail-img {
        width: 50px;
        height: 50px;
        object-fit: cover;
        border-radius: 4px;
        cursor: pointer;
        transition: transform 0.2s;
    }

    .thumbnail-img:hover {
        transform: scale(1.1);
    }

    /* Image Preview Modal */
    .modal-image {
        max-width: 100%;
        max-height: 80vh;
    }
    
    /* Status badge styling */
    .badge-warning {
        background-color: #ffc107;
        color: #212529;
    }
    
    .badge-success {
        background-color: #28a745;
        color: white;
    }
    
    .badge-danger {
        background-color: #dc3545;
        color: white;
    }
    
    .badge-info {
        background-color: #17a2b8;
        color: white;
    }
    
    .badge-secondary {
        background-color: #6c757d;
        color: white;
    }
    
    /* Date range display */
    .date-range {
        display: flex;
        flex-direction: column;
    }
    
    .date-label {
        font-weight: bold;
        font-size: 0.8rem;
        color: #6c757d;
    }
    
    .action-buttons .btn {
        margin-bottom: 5px;
    }
    
    /* Remark styling */
    .remark-container {
        background-color: #f8f9fa;
        border-radius: 4px;
        padding: 6px 8px;
        font-size: 0.85rem;
        max-width: 300px;
        border-left: 3px solid #17a2b8;
        margin-top: 5px;
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

<div class="content-page">
    <div class="content">
        <div class="container-fluid">
            <div class="page-title-box">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <div class="page-title-box">
                            <h4 class="page-title">HR Sick Leave Management</h4>
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item">
                                    <a href="javascript:void(0);">Home</a>
                                </li>
                                <li class="breadcrumb-item active">HR Sick Leave Management</li>
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
                            <!-- Filters -->
                            <div class="row mb-3">
                                <div class="col-md-3">
                                    <label for="start_date">Filter From</label>
                                    <input type="date" class="form-control" id="start_date" name="start_date" value="{{ request('start_date') }}">
                                </div>
                                <div class="col-md-3">
                                    <label for="end_date">Filter To</label>
                                    <input type="date" class="form-control" id="end_date" name="end_date" value="{{ request('end_date') }}">
                                </div>
                                <div class="col-md-3">
                                    <label for="status">Status</label>
                                    <select class="form-control" id="status" name="status">
                                        <option value="">All Status</option>
                                        <option value="0" {{ request('status') == '0' ? 'selected' : '' }}>Pending Supervisor</option>
                                        <option value="1" {{ request('status') == '1' ? 'selected' : '' }}>Pending HR Approval</option>
                                        <option value="2" {{ request('status') == '2' ? 'selected' : '' }}>Approved</option>
                                        <option value="3" {{ request('status') == '3' ? 'selected' : '' }}>Rejected</option>
                                        <option value="4" {{ request('status') == '4' ? 'selected' : '' }}>Cancelled</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label>&nbsp;</label>
                                    <button type="button" class="btn btn-primary btn-block" id="filter">Apply Filters</button>
                                </div>
                            </div>

                            <!-- Sick Leaves Table -->
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped" id="sick-leaves-table">
                                    <thead>
                                        <tr>
                                            <th>Staff Name</th>
                                            <th>Department</th>
                                            <th>Date Range</th>
                                            <th>Duration</th>
                                            <th>Status</th>
                                            <th>Created</th>
                                            <th>Medical Certificate</th>
                                            <th>Description</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($sickLeaves as $leave)
                                            <tr>
                                                <td>{{ $leave->staff->name ?? 'Unknown' }}</td>
                                                <td>{{ $leave->department ?? 'N/A' }}</td>
                                                <td>
                                                    <div class="date-range">
                                                        <div>
                                                            <span class="date-label">From:</span> 
                                                            @php
                                                                // Handle start date with more robust parsing
                                                                $startDate = null;
                                                                try {
                                                                    if ($leave->start_date instanceof \Carbon\Carbon) {
                                                                        $startDate = $leave->start_date;
                                                                    } elseif (is_string($leave->start_date)) {
                                                                        $startDate = \Carbon\Carbon::parse($leave->start_date);
                                                                    }
                                                                } catch (\Exception $e) {
                                                                    // Failed to parse
                                                                }
                                                            @endphp
                                                            {{ $startDate ? $startDate->format('d.m.Y') : 'N/A' }}
                                                        </div>
                                                        <div>
                                                            <span class="date-label">To:</span>
                                                            @php
                                                                // Handle end date with more robust parsing
                                                                $endDate = null;
                                                                try {
                                                                    if ($leave->end_date instanceof \Carbon\Carbon) {
                                                                        $endDate = $leave->end_date;
                                                                    } elseif (is_string($leave->end_date) && !empty($leave->end_date)) {
                                                                        $endDate = \Carbon\Carbon::parse($leave->end_date);
                                                                    }
                                                                } catch (\Exception $e) {
                                                                    // Failed to parse
                                                                }
                                                            @endphp
                                                            {{ $endDate ? $endDate->format('d.m.Y') : 'N/A' }}
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    @php
                                                        // More robust duration calculation
                                                        try {
                                                            $startDate = null;
                                                            $endDate = null;
                                                            
                                                            if ($leave->start_date instanceof \Carbon\Carbon) {
                                                                $startDate = $leave->start_date;
                                                            } elseif (is_string($leave->start_date)) {
                                                                $startDate = \Carbon\Carbon::parse($leave->start_date);
                                                            }
                                                            
                                                            if ($leave->end_date instanceof \Carbon\Carbon) {
                                                                $endDate = $leave->end_date;
                                                            } elseif (is_string($leave->end_date) && !empty($leave->end_date)) {
                                                                $endDate = \Carbon\Carbon::parse($leave->end_date);
                                                            } else {
                                                                $endDate = $startDate;
                                                            }
                                                            
                                                            $duration = $startDate && $endDate ? $startDate->diffInDays($endDate) + 1 : 'N/A';
                                                        } catch (\Exception $e) {
                                                            $duration = 'N/A';
                                                        }
                                                    @endphp
                                                    {{ is_numeric($duration) ? "$duration " . Str::plural('day', $duration) : $duration }}
                                                </td>
                                                <td>
                                                    @if($leave->status == 0)
                                                        <span class="badge badge-warning">Pending Supervisor</span>
                                                    @elseif($leave->status == 1)
                                                        <span class="badge badge-info">Pending HR Approval</span>
                                                        @if($leave->supervisor_remark)
                                                        <div class="remark-container">
                                                            <div class="remark-info">
                                                                <strong>Supervisor comment:</strong>
                                                                <p class="mb-0">{{ $leave->supervisor_remark }}</p>
                                                            </div>
                                                        </div>
                                                        @endif
                                                    @elseif($leave->status == 2)
                                                        <span class="badge badge-success">Approved</span>
                                                        @if($leave->supervisor_remark || $leave->admin_remark)
                                                        <div class="remark-container">
                                                            @if($leave->supervisor_remark)
                                                            <div class="remark-info mb-1">
                                                                <strong>Supervisor comment:</strong>
                                                                <p class="mb-0">{{ $leave->supervisor_remark }}</p>
                                                            </div>
                                                            @endif
                                                            @if($leave->admin_remark)
                                                            <div class="remark-info">
                                                                <strong>HR comment:</strong>
                                                                <p class="mb-0">{{ $leave->admin_remark }}</p>
                                                            </div>
                                                            @endif
                                                        </div>
                                                        @endif
                                                    @elseif($leave->status == 3)
                                                        <span class="badge badge-danger">Rejected</span>
                                                        @if($leave->supervisor_remark || $leave->admin_remark)
                                                        <div class="remark-container">
                                                            @if($leave->supervisor_remark)
                                                            <div class="remark-info mb-1">
                                                                <strong>Supervisor reason:</strong>
                                                                <p class="mb-0">{{ $leave->supervisor_remark }}</p>
                                                            </div>
                                                            @endif
                                                            @if($leave->admin_remark)
                                                            <div class="remark-info">
                                                                <strong>HR reason:</strong>
                                                                <p class="mb-0">{{ $leave->admin_remark }}</p>
                                                            </div>
                                                            @endif
                                                        </div>
                                                        @endif
                                                    @elseif($leave->status == 4)
                                                        <span class="badge badge-secondary">Cancelled</span>
                                                    @endif
                                                </td>
                                                <td>{{ $leave->created_at->format('d.m.Y H:i') }}</td>
                                                <td>
                                                    @if($leave->image)
                                                        <a href="{{ asset('storage/' . $leave->image) }}" 
                                                           class="image-preview" 
                                                           data-toggle="tooltip" 
                                                           title="Click to view">
                                                            <img src="{{ asset('storage/' . $leave->image) }}" 
                                                                 alt="Medical Certificate" 
                                                                 class="thumbnail-img">
                                                        </a>
                                                    @else
                                                        <span class="badge badge-secondary">No Image</span>
                                                    @endif
                                                </td>
                                                <td>{{ $leave->description ?? 'N/A' }}</td>
                                                <td>
                                                    <div class="action-buttons">
                                                            <button class="btn btn-sm btn-success admin-approve-btn" 
                                                                   data-id="{{ $leave->id }}">
                                                                <i class="fas fa-check"></i> HR Approve
                                                            </button>
                                                            
                                                            <button class="btn btn-sm btn-danger admin-reject-btn" 
                                                                   data-id="{{ $leave->id }}">
                                                                <i class="fas fa-times"></i> HR Reject
                                                            </button>
                                                    
                                                        @if($leave->status < 2)
                                                            <button class="btn btn-sm btn-warning cancel-leave" 
                                                                   data-id="{{ $leave->id }}">
                                                                <i class="fas fa-ban"></i> Cancel
                                                            </button>
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination -->
                            <div class="mt-3">
                                {{ $sickLeaves->links() }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Admin Approval Modal -->
<div class="modal fade" id="adminApprovalModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">HR Approval</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="adminApprovalForm">
                    <input type="hidden" id="adminApproveLeaveId" name="leave_id">
                    <div class="form-group">
                        <label for="adminApprovalRemark">HR Comments (Optional)</label>
                        <textarea class="form-control" id="adminApprovalRemark" name="admin_remark" rows="3" placeholder="Add any comments or notes about this HR approval"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="confirmAdminApprove">Confirm HR Approval</button>
            </div>
        </div>
    </div>
</div>

<!-- Admin Rejection Modal -->
<div class="modal fade" id="adminRejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">HR Rejection</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="adminRejectForm">
                    <input type="hidden" id="adminRejectLeaveId" name="leave_id">
                    <div class="form-group">
                        <label for="adminRejectionReason">HR Reason for Rejection</label>
                        <textarea class="form-control" id="adminRejectionReason" name="admin_remark" rows="3" required></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmAdminReject">Confirm HR Rejection</button>
            </div>
        </div>
    </div>
</div>

<!-- Image Preview Modal -->
<div class="modal fade" id="imagePreviewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Medical Certificate</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body text-center">
                <img src="" alt="Medical Certificate Preview" class="modal-image">
            </div>
            <div class="modal-footer">
                <a href="" class="btn btn-primary download-image" download>Download</a>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize DataTable
    $('#sick-leaves-table').DataTable({
        pageLength: 10,
        responsive: true,
        "order": [[5, "desc"]], // Sort by created date desc by default
        "columnDefs": [
            { "orderable": false, "targets": [6, 8] } // Disable sorting on image and actions columns
        ]
    });

    // Filter functionality
    const filterBtn = document.getElementById('filter');
    filterBtn.addEventListener('click', function() {
        const startDate = document.getElementById('start_date').value;
        const endDate = document.getElementById('end_date').value;
        const status = document.getElementById('status').value;
        
        window.location.href = `{{ route('admin.manage-sick-leaves') }}?start_date=${startDate}&end_date=${endDate}&status=${status}`;
    });

    // HR Approve functionality - show modal
    document.querySelectorAll('.admin-approve-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const leaveId = this.dataset.id;
            document.getElementById('adminApproveLeaveId').value = leaveId;
            // Reset the approval remark
            document.getElementById('adminApprovalRemark').value = '';
            $('#adminApprovalModal').modal('show');
        });
    });

    // Confirm HR approval
    document.getElementById('confirmAdminApprove').addEventListener('click', async function() {
        const leaveId = document.getElementById('adminApproveLeaveId').value;
        const approvalRemark = document.getElementById('adminApprovalRemark').value;
        
        try {
            // Use the correct route for admin approval
            const response = await fetch(`/supervisor-sick-leaves/${leaveId}/admin-approve`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    admin_remark: approvalRemark
                })
            });
            
            if(response.ok) {
                $('#adminApprovalModal').modal('hide');
                window.location.reload();
            } else {
                const data = await response.json();
                alert(data.message || 'Failed to approve');
            }
        } catch(error) {
            console.error('Error:', error);
            alert('An error occurred while processing your request');
        }
    });

    // HR Reject functionality - show modal
    document.querySelectorAll('.admin-reject-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const leaveId = this.dataset.id;
            document.getElementById('adminRejectLeaveId').value = leaveId;
            // Reset the rejection reason
            document.getElementById('adminRejectionReason').value = '';
            $('#adminRejectModal').modal('show');
        });
    });

    // Confirm HR rejection
    document.getElementById('confirmAdminReject').addEventListener('click', async function() {
        const leaveId = document.getElementById('adminRejectLeaveId').value;
        const rejectionReason = document.getElementById('adminRejectionReason').value;
        
        if (!rejectionReason) {
            alert('Please provide a reason for rejection');
            return;
        }
        
        try {
            // Use the correct route for admin rejection
            const response = await fetch(`/supervisor-sick-leaves/${leaveId}/admin-reject`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    admin_remark: rejectionReason
                })
            });
            
            if(response.ok) {
                $('#adminRejectModal').modal('hide');
                window.location.reload();
            } else {
                const data = await response.json();
                alert(data.message || 'Failed to reject');
            }
        } catch(error) {
            console.error('Error:', error);
            alert('An error occurred while processing your request');
        }
    });

    // Cancel leave functionality
    document.querySelectorAll('.cancel-leave').forEach(btn => {
        btn.addEventListener('click', async function() {
            const leaveId = this.dataset.id;
            if(confirm('Are you sure you want to cancel this sick leave request?')) {
                try {
                    // Use the correct route for cancellation
                    const response = await fetch(`/supervisor-sick-leaves/${leaveId}/cancel`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        }
                    });
                    
                    if(response.ok) {
                        window.location.reload();
                    } else {
                        const data = await response.json();
                        alert(data.message || 'Failed to cancel');
                    }
                } catch(error) {
                    console.error('Error:', error);
                    alert('An error occurred while cancelling the request');
                }
            }
        });
    });

    // Image preview functionality
    document.querySelectorAll('.image-preview').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const imageUrl = this.getAttribute('href');
            document.querySelector('#imagePreviewModal .modal-image').src = imageUrl;
            document.querySelector('#imagePreviewModal .download-image').href = imageUrl;
            $('#imagePreviewModal').modal('show');
        });
    });
});
</script>
@endsection
