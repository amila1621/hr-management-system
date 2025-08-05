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
                            <li class="breadcrumb-item active">Receipts Records</li>
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
                                                        <span class="badge badge-info">Approved By Supervisor</span>
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
                                                        @if(auth()->user()->role === 'admin')
                                                            <button type="button" 
                                                                    class="btn btn-success btn-sm" 
                                                                    onclick="updateStatus({{ $receipt->id }}, 2, '{{ $receipt->applied_month ?? '' }}')"
                                                                    title="Approve"> Approve
                                                                <i class="fas fa-check"></i>
                                                            </button>
                                                            &nbsp;
                                                            <button type="button" 
                                                                    class="btn btn-danger btn-sm" 
                                                                    onclick="showRejectModal({{ $receipt->id }})"
                                                                    title="Reject"> Reject
                                                                <i class="fas fa-times"></i>
                                                            </button>
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
    <input type="hidden" name="approval_description" id="approvalDescriptionInput">
    <input type="hidden" name="create_accounting_record" id="createAccountingRecord" value="0">
    <input type="hidden" name="accounting_category_id" id="accountingCategoryId">
    <input type="hidden" name="accounting_description" id="accountingDescription">
    <input type="hidden" name="accounting_amount" id="accountingAmount">
    <input type="hidden" name="applied_month" id="accountingAppliedMonth">
</form>

<!-- Receipt Modal -->
<div class="modal fade" id="receiptModal" tabindex="-1" role="dialog" aria-labelledby="receiptModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="receiptModalLabel">Receipt Image</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body text-center">
                <img id="receiptImage" src="" class="img-fluid" alt="Receipt">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <a id="downloadReceipt" href="" class="btn btn-primary" download>Download</a>
            </div>
        </div>
    </div>
</div>

<!-- Rejection Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1" role="dialog" aria-labelledby="rejectModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="rejectModalLabel">Reject Receipt</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="rejectForm">
                    <div class="form-group">
                        <label for="rejectionReason">Reason for Rejection</label>
                        <textarea class="form-control" id="rejectionReason" rows="3" required></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmReject">Confirm Rejection</button>
            </div>
        </div>
    </div>
</div>

<!-- Approval Modal -->
<div class="modal fade" id="approvalModal" tabindex="-1" role="dialog" aria-labelledby="approvalModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="approvalModalLabel">Approve Receipt</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="approvalForm">
                    <div class="form-group">
                        <div class="custom-control custom-checkbox d-none">
                            <input type="checkbox" class="custom-control-input" id="createAccounting" checked>
                            <label class="custom-control-label" for="createAccounting">Create accounting record</label>
                        </div>
                    </div>
                    <div id="accountingDetails">
                        <div class="form-group">
                            <label for="appliedMonth">Applied Month</label>
                            <input type="month" class="form-control" id="appliedMonth" required>
                            <small class="form-text text-muted">Month to which this receipt applies</small>
                        </div>
                        <div class="form-group">
                            <label for="accountingCategory">Accounting Category</label>
                            <select class="form-control" id="accountingCategory" required>
                                <option value="">Select a category</option>
                                @foreach($accountingCategories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea class="form-control" id="description" rows="2"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="amount">Amount</label>
                            <input type="number" class="form-control" id="amount" step="0.01">
                            <small class="form-text text-muted">If left empty, receipt amount will be used</small>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="confirmApproval">Confirm Approval</button>
            </div>
        </div>
    </div>
</div>

<script>
    function updateStatus(receiptId, status, appliedMonth = '') {
        if (status === 2) { // Changed from 1 to 2
            // For approval, show the accounting modal
            currentReceiptId = receiptId;
            
            // Set the current applied month if available
            if (appliedMonth) {
                document.getElementById('appliedMonth').value = appliedMonth;
            } else {
                // Default to current month
                const now = new Date();
                const year = now.getFullYear();
                const month = (now.getMonth() + 1).toString().padStart(2, '0');
                document.getElementById('appliedMonth').value = `${year}-${month}`;
            }
            
            $('#approvalModal').modal('show');
        } else {
            // For rejection (shouldn't happen here but kept for safety)
            if (confirm('Are you sure you want to approve this receipt?')) {
                submitStatusForm(receiptId, status);
            }
        }
    }
    
    function submitStatusForm(receiptId, status, accountingData = null) {
        const form = document.getElementById('statusUpdateForm');
        form.action = `/receipts/${receiptId}/status`;
        document.getElementById('statusInput').value = status;
        
        if (accountingData) {
            document.getElementById('createAccountingRecord').value = accountingData.create ? 1 : 0;
            document.getElementById('accountingCategoryId').value = accountingData.categoryId || '';
            document.getElementById('accountingDescription').value = accountingData.description || '';
            document.getElementById('approvalDescriptionInput').value = accountingData.description || '';
            document.getElementById('accountingAmount').value = accountingData.amount || '';
            document.getElementById('accountingAppliedMonth').value = accountingData.appliedMonth || '';
        }
        
        form.submit();
    }
    
    function showReceiptModal(imageUrl) {
        document.getElementById('receiptImage').src = imageUrl;
        document.getElementById('downloadReceipt').href = imageUrl;
        $('#receiptModal').modal('show');
    }
    
    let currentReceiptId = null;
    
    function showRejectModal(receiptId) {
        currentReceiptId = receiptId;
        $('#rejectModal').modal('show');
    }
    
    // Toggle accounting details visibility based on checkbox
    document.getElementById('createAccounting').addEventListener('change', function() {
        const accountingDetails = document.getElementById('accountingDetails');
        accountingDetails.style.display = this.checked ? 'block' : 'none';
    });
    
    document.getElementById('confirmApproval').addEventListener('click', function() {
        const createAccounting = document.getElementById('createAccounting').checked;
        
        // Get the month value and ensure it has a day component
        let appliedMonth = document.getElementById('appliedMonth').value;
        if (appliedMonth && !appliedMonth.includes('-01')) {
            appliedMonth = appliedMonth + '-01';
        }
        
        const accountingData = {
            create: createAccounting,
            categoryId: document.getElementById('accountingCategory').value,
            description: document.getElementById('description').value,
            amount: document.getElementById('amount').value,
            appliedMonth: appliedMonth
        };
        
        if (createAccounting && !accountingData.categoryId) {
            alert('Please select an accounting category');
            return;
        }
        
        if (!accountingData.appliedMonth) {
            alert('Please select the applied month');
            return;
        }
        
        submitStatusForm(currentReceiptId, 2, accountingData);
    });
    
    document.getElementById('confirmReject').addEventListener('click', function() {
        const reason = document.getElementById('rejectionReason').value;
        if (!reason.trim()) {
            alert('Please provide a reason for rejection');
            return;
        }
        
        const form = document.getElementById('statusUpdateForm');
        form.action = `/receipts/${currentReceiptId}/status`;
        document.getElementById('statusInput').value = 3; // Changed from 2 to 3 for rejection status
        document.getElementById('rejectionReasonInput').value = reason;
        form.submit();
    });
    
    $(function () {
        $('[data-toggle="tooltip"]').tooltip();
    });
</script>
@endsection


