<?php $__env->startSection('content'); ?>

<style>
    .table-warning > td {
        color: black;
        background-color: #fa974b;
    }

    .table-danger > td {
        color: black;
        background-color: #f03252;
    }
</style>

<!-- Add Flatpickr CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

<div class="content-page">
    <div class="content">
        <div class="container-fluid">
            <div class="page-title-box">
                <h4 class="page-title">Rejected Hours</h4>
                <p>Below are the guide hours that have been rejected. Admins can review and update the status if needed.</p>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Guide Name</th>
                            <th>Tour Date</th>
                            <th>Tour Name</th>
                            <th>Guide Start Time</th>
                            <th>Guide End Time</th>
                            <th>Guide Comment</th>
                            <th>Admin Comment</th>
                            <th>Guide Image</th>
                            <th>Admin Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__currentLoopData = $rejectedEventSalaries; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $rejection): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr class="">
                                <td><?php echo e($rejection->tourGuide->name); ?></td>
                                <td>
                                    <?php echo e($rejection->event && $rejection->event->start_time ? \Carbon\Carbon::parse($rejection->event->start_time)->format('d.m.Y') : 'N/A'); ?>

                                </td>
                                <td><?php echo e($rejection->event->name); ?></td>
                                <td><?php echo e(\Carbon\Carbon::parse($rejection->guide_start_time)->format('d.m.Y H:i')); ?></td>
                                <td><?php echo e(\Carbon\Carbon::parse($rejection->guide_end_time)->format('d.m.Y H:i')); ?></td>
                                <td><?php echo e($rejection->guide_comment ?? 'No comment'); ?></td>
                                <td><?php echo e($rejection->admin_comment ?? 'No comment'); ?></td>
                                <td>
                                    <?php if($rejection->guide_image): ?>
                                        <a href="<?php echo e(asset('storage/' . $rejection->guide_image)); ?>" target="_blank">
                                            <img src="<?php echo e(asset('storage/' . $rejection->guide_image)); ?>" alt="Guide Image" class="img-thumbnail" style="max-width: 100px; max-height: 100px;">
                                        </a>
                                    <?php else: ?>
                                        No image
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <!-- Reject button -->
                                    <button class="btn btn-danger btn-sm" data-toggle="modal"
                                        data-target="#rejectModal<?php echo e($rejection->id); ?>">Modify Time</button>
                                </td>
                            </tr>

                            <!-- Reject Modal -->
                            <div class="modal fade" id="rejectModal<?php echo e($rejection->id); ?>" tabindex="-1" role="dialog"
                                aria-labelledby="rejectModalLabel<?php echo e($rejection->id); ?>" aria-hidden="true">
                                <div class="modal-dialog" role="document">
                                    <div class="modal-content">
                                        <form action="<?php echo e(route('admin.modify-time', $rejection->id)); ?>" method="POST">
                                            <?php echo csrf_field(); ?>
                                            <?php echo method_field('POST'); ?>
                                            <div class="modal-header">
                                                <h5 class="modal-title">Modify Hours</h5>
                                                <button type="button" class="close" data-dismiss="modal">
                                                    <span>&times;</span>
                                                </button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="form-group">
                                                    <label for="admin_comment">Modify Comment</label>
                                                    <textarea class="form-control" name="approval_comment"><?php echo e($rejection->admin_comment); ?></textarea>
                                                </div>

                                                <div class="form-group mt-3">
                                                    <label for="guide_start_time">Start Date & Time</label>
                                                    <input type="text" class="form-control flatpickr-datetime" name="guide_start_time" value="<?php echo e($rejection->guide_start_time); ?>">
                                                </div>

                                                <div class="form-group mt-3">
                                                    <label for="guide_end_time">End Date & Time</label>
                                                    <input type="text" class="form-control flatpickr-datetime" name="guide_end_time" value="<?php echo e($rejection->guide_end_time); ?>">
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                                <button type="submit" class="btn btn-danger">Confirm</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Flatpickr JS -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        initFlatpickr();
    });

    function initFlatpickr() {
        flatpickr(".flatpickr-datetime", {
            enableTime: true,
            dateFormat: "Y-m-d H:i",
            time_24hr: true
        });
    }
</script>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('partials.main', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/nordpzbm/hr.nordictravels.tech/resources/views/reports/rejected-hours.blade.php ENDPATH**/ ?>