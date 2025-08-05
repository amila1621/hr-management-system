@extends('partials.main')

@section('content')
<div class="content-page">
    <div class="content">
        <div class="container-fluid">
            <div class="page-title-box">
                <div class="row align-items-center">
                    <div class="col-sm-6">
                        <h4 class="page-title">Receipt Records</h4>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="javascript:void(0);">Home</a></li>
                            <li class="breadcrumb-item active">Receipt Records</li>
                        </ol>
                    </div>
                </div>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="datatable-buttons" class="table table-striped table-bordered dt-responsive nowrap">
                                    <thead>
                                        <tr>
                                        
                                            <th>Receipt Date</th>
                                            <th>Employee</th>
                                            <th>Amount</th>
                                            <th>Reason</th>
                                            <th>Receipt</th>

                                            <th>Status</th>
                                            <th>Submitted By</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($receipts as $receipt)
                                            <tr>
                                            
                                            <td>{{ $receipt->created_at->format('d.m.Y H:i') }}</td>
                                            <td>{{ $receipt->user->name}}</td>

                                               
                                            <td>{{ $receipt->amount }}</td>
                                                <td>{{ $receipt->note }}</td>
                                                <td>
                                                    <a href="javascript:void(0);" onclick="showReceiptModal('{{ Storage::url($receipt->receipt) }}')">
                                                        <img src="{{ Storage::url($receipt->receipt) }}" 
                                                             alt="Receipt" 
                                                             class="img-thumbnail" 
                                                             style="max-width: 50px;">
                                                    </a>
                                                </td>
                                                <td>
                                                    @if($receipt->status == 0)
                                                        <span class="badge badge-warning">Pending</span>
                                                    @elseif($receipt->status == 1)
                                                        <span class="badge badge-success">Approved</span>
                                                    @else
                                                        <span class="badge badge-danger">Rejected</span>
                                                        @if($receipt->rejection_reason)
                                                        <i class="fas fa-info-circle" data-toggle="tooltip" title="{{ $receipt->rejection_reason }}"></i>
                                                        @endif
                                                    @endif
                                                </td>
                                                <td>{{ $receipt->creator->name }}</td>
                                                
                                                <td>
                                                    <div class="btn-group">
                                                        @if(auth()->user()->role === 'supervisor' && $receipt->status == 0)
                                                            <form method="POST" action="{{ route('receipts.update-status', $receipt->id) }}" class="d-inline" id="approve-form-{{ $receipt->id }}">
                                                                @csrf
                                                                @method('PATCH')
                                                                <input type="hidden" name="status" value="1">
                                                                <input type="hidden" name="applied_month" value="{{ $receipt->applied_month ?? now()->format('Y-m') }}">
                                                                <button type="button" 
                                                                        class="btn btn-success btn-sm approve-btn" 
                                                                        data-id="{{ $receipt->id }}"
                                                                        title="Approve">
                                                                    <i class="fas fa-check"></i> Approve
                                                                </button>
                                                            </form>
                                                            &nbsp;
                                                            <form method="POST" action="{{ route('receipts.update-status', $receipt->id) }}" class="d-inline" id="reject-form-{{ $receipt->id }}">
                                                                @csrf
                                                                @method('PATCH')
                                                                <input type="hidden" name="status" value="3">
                                                                <button type="button" 
                                                                        class="btn btn-danger btn-sm reject-btn" 
                                                                        data-id="{{ $receipt->id }}"
                                                                        title="Reject">
                                                                    <i class="fas fa-times"></i> Reject
                                                                </button>
                                                            </form>
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="9" class="text-center">No receipts found</td>
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

<!-- Status Update Form -->
<form id="statusUpdateForm" action="" method="POST" style="display: none;">
    @csrf
    @method('PATCH')
    <input type="hidden" name="status" id="statusInput">
    <input type="hidden" name="rejection_reason" id="rejectionReasonInput">
    <input type="hidden" name="create_accounting_record" id="createAccountingRecord" value="0">
    <input type="hidden" name="accounting_category_id" id="accountingCategoryId">
    <input type="hidden" name="accounting_description" id="accountingDescription">
    <input type="hidden" name="accounting_amount" id="accountingAmount">
    <input type="hidden" name="applied_month" id="accountingAppliedMonth">
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle approve button clicks
    document.querySelectorAll('.approve-btn').forEach(button => {
        button.addEventListener('click', function() {
            const receiptId = this.getAttribute('data-id');
            if (confirm('Are you sure you want to approve this receipt?')) {
                document.getElementById('approve-form-' + receiptId).submit();
            }
        });
    });

    // Handle reject button clicks
    document.querySelectorAll('.reject-btn').forEach(button => {
        button.addEventListener('click', function() {
            const receiptId = this.getAttribute('data-id');
            const reason = prompt('Please provide a reason for rejection:', '');
            
            if (reason !== null) {
                const form = document.getElementById('reject-form-' + receiptId);
                
                // Add rejection reason to the form
                const reasonInput = document.createElement('input');
                reasonInput.type = 'hidden';
                reasonInput.name = 'rejection_reason';
                reasonInput.value = reason;
                form.appendChild(reasonInput);
                
                // Submit the form
                form.submit();
            }
        });
    });
});
</script>
@endsection


