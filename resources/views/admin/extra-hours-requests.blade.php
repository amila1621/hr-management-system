@extends('partials.main')

@section('content')

<div class="content-page">
    <div class="content">
        <div class="container-fluid">
            <div class="page-title-box">
                <h4 class="page-title">Extra Hours Requests Management</h4>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Pending Requests</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                    <thead class="thead-dark">
                                        <tr>
                                            <th>Guide</th>
                                            <th>Tour Name</th>
                                            <th>Date</th>
                                            <th>Original End</th>
                                            <th>Requested End</th>
                                            <th>Extra Hours</th>
                                            <th>Explanation</th>
                                            <th>Status</th>
                                            <th>Submitted</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($requests as $request)
                                        <tr>
                                            <td>{{ $request->guide->full_name ?? $request->guide->name }}</td>
                                            <td>{{ $request->event->name }}</td>
                                            <td>{{ \Carbon\Carbon::parse($request->event->start_time)->format('d.m.Y') }}</td>
                                            <td>{{ \Carbon\Carbon::parse($request->original_end_time)->format('H:i') }}</td>
                                            <td>{{ \Carbon\Carbon::parse($request->requested_end_time)->format('H:i') }}</td>
                                            <td>
                                                @php
                                                    $hours = floor($request->extra_hours_minutes / 60);
                                                    $minutes = $request->extra_hours_minutes % 60;
                                                @endphp
                                                {{ $hours }}:{{ str_pad($minutes, 2, '0', STR_PAD_LEFT) }}
                                            </td>
                                            <td>
                                                <div style="max-width: 200px; overflow: hidden;">
                                                    <span class="explanation-short">{{ Str::limit($request->explanation, 50) }}</span>
                                                    @if(strlen($request->explanation) > 50)
                                                        <span class="explanation-full" style="display: none;">{{ $request->explanation }}</span>
                                                        <br><small><a href="#" class="toggle-explanation">Show more</a></small>
                                                    @endif
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge 
                                                    @if($request->status == 'pending') badge-warning
                                                    @elseif($request->status == 'approved') badge-success
                                                    @else badge-danger
                                                    @endif">
                                                    {{ ucfirst($request->status) }}
                                                </span>
                                            </td>
                                            <td>{{ $request->created_at->format('d.m.Y H:i') }}</td>
                                            <td>
                                                @if($request->status == 'pending')
                                                <div class="btn-group" role="group">
                                                    <button type="button" class="btn btn-success btn-sm" 
                                                            onclick="showApprovalModal({{ $request->id }}, 'approve')">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-danger btn-sm" 
                                                            onclick="showApprovalModal({{ $request->id }}, 'reject')">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </div>
                                                @else
                                                <span class="text-muted">
                                                    {{ $request->status == 'approved' ? 'Approved' : 'Rejected' }}
                                                    @if($request->approved_at)
                                                        <br><small>{{ $request->approved_at->format('d.m.Y H:i') }}</small>
                                                    @endif
                                                    @if($request->approvedBy)
                                                        <br><small>by {{ $request->approvedBy->name }}</small>
                                                    @endif
                                                </span>
                                                @endif
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="10" class="text-center">No extra hours requests found.</td>
                                        </tr>
                                        @endforelse
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

<!-- Approval Modal -->
<div class="modal fade" id="approvalModal" tabindex="-1" aria-labelledby="approvalModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="approvalForm" method="POST" action="">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="approvalModalLabel">Action Required</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="admin_comment">Comment (optional):</label>
                        <textarea class="form-control" id="admin_comment" name="admin_comment" rows="3" 
                                  placeholder="Add any comments about this decision..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-action" id="actionBtn">Confirm</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function showApprovalModal(requestId, action) {
        const modal = document.getElementById('approvalModal');
        const form = document.getElementById('approvalForm');
        const actionBtn = document.getElementById('actionBtn');
        const modalTitle = document.getElementById('approvalModalLabel');
        
        if (action === 'approve') {
            form.action = `/admin/extra-hours-requests/${requestId}/approve`;
            actionBtn.className = 'btn btn-success';
            actionBtn.textContent = 'Approve Request';
            modalTitle.textContent = 'Approve Extra Hours Request';
        } else {
            form.action = `/admin/extra-hours-requests/${requestId}/reject`;
            actionBtn.className = 'btn btn-danger';
            actionBtn.textContent = 'Reject Request';
            modalTitle.textContent = 'Reject Extra Hours Request';
        }
        
        const bootstrapModal = new bootstrap.Modal(modal);
        bootstrapModal.show();
    }

    // Toggle explanation text
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.toggle-explanation').forEach(function(link) {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const shortSpan = this.parentElement.parentElement.querySelector('.explanation-short');
                const fullSpan = this.parentElement.parentElement.querySelector('.explanation-full');
                
                if (shortSpan.style.display === 'none') {
                    shortSpan.style.display = 'inline';
                    fullSpan.style.display = 'none';
                    this.textContent = 'Show more';
                } else {
                    shortSpan.style.display = 'none';
                    fullSpan.style.display = 'inline';
                    this.textContent = 'Show less';
                }
            });
        });
    });
</script>

@endsection