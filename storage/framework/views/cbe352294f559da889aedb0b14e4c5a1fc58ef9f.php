<?php $__env->startSection('content'); ?>
<div class="content-page">
    <div class="content">
        <div class="container-fluid">
            <div class="page-title-box">
                <div class="row align-items-center">
                    <div class="col-sm-6">
                        <h4 class="page-title">Manage Receipts</h4>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="javascript:void(0);">Home</a></li>
                            <li class="breadcrumb-item active">Manage Receipts</li>
                        </ol>
                    </div>
                </div>
            </div>

            <?php if(session('success')): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo e(session('success')); ?>

                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="datatable-buttons" class="table table-striped table-bordered dt-responsive nowrap">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Receipt</th>
                                            <th>Note</th>
                                            <th>Status</th>
                                            <th>Submitted By</th>
                                            <th>Submitted Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $__empty_1 = true; $__currentLoopData = $receipts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $receipt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                            <tr>
                                                <td><?php echo e($receipt->id); ?></td>
                                                <td>
                                                    <a href="<?php echo e(Storage::url($receipt->receipt)); ?>" target="_blank">
                                                        <img src="<?php echo e(Storage::url($receipt->receipt)); ?>" 
                                                             alt="Receipt" 
                                                             class="img-thumbnail" 
                                                             style="max-width: 50px;">
                                                    </a>
                                                </td>
                                                <td><?php echo e($receipt->note); ?></td>
                                                <td>
                                                    <?php if($receipt->status == 0): ?>
                                                        <span class="badge badge-warning">Pending</span>
                                                    <?php elseif($receipt->status == 1): ?>
                                                        <span class="badge badge-success">Approved</span>
                                                    <?php else: ?>
                                                        <span class="badge badge-danger">Rejected</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo e($receipt->user->name); ?></td>
                                                <td><?php echo e($receipt->created_at->format('d M Y H:i')); ?></td>
                                                <td>
                                                    <div class="btn-group">

                                                        <?php if(auth()->user()->role === 'admin' && $receipt->status == 0): ?>
                                                            <button type="button" 
                                                                    class="btn btn-success btn-sm" 
                                                                    onclick="updateStatus(<?php echo e($receipt->id); ?>, 1)"
                                                                    title="Approve"> Approve
                                                                <i class="fas fa-check"></i>
                                                            </button>
                                                            &nbsp;
                                                            <button type="button" 
                                                                    class="btn btn-danger btn-sm" 
                                                                    onclick="updateStatus(<?php echo e($receipt->id); ?>, 2)"
                                                                    title="Reject"> Reject
                                                                <i class="fas fa-times"></i>
                                                            </button>
                                                        <?php endif; ?>

                                                   
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                            <tr>
                                                <td colspan="7" class="text-center">No receipts found</td>
                                            </tr>
                                        <?php endif; ?>
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
    <?php echo csrf_field(); ?>
    <?php echo method_field('PATCH'); ?>
    <input type="hidden" name="status" id="statusInput">
</form>


<script>
 
    function updateStatus(receiptId, status) {
        if (confirm('Are you sure you want to ' + (status === 1 ? 'approve' : 'reject') + ' this receipt?')) {
            const form = document.getElementById('statusUpdateForm');
            form.action = `/receipts/${receiptId}/status`;
            document.getElementById('statusInput').value = status;
            form.submit();
        }
    }
</script>
<?php $__env->stopSection(); ?>



<?php echo $__env->make('partials.main', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/nordpzbm/hr.nordictravels.tech/resources/views/receipts/approve.blade.php ENDPATH**/ ?>