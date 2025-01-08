<?php $__env->startSection('content'); ?>
<div class="content-page">
    <div class="content">
        <div class="container-fluid">
            <div class="page-title-box">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <div class="page-title-box">
                            <h4 class="page-title">Operations Check Sheet</h4>
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="javascript:void(0);">Home</a></li>
                                <li class="breadcrumb-item active">Check Sheet</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <?php if($errors->any()): ?>
                                <div class="alert alert-danger">
                                    <ul class="mb-0">
                                        <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <li><?php echo e($error); ?></li>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </ul>
                                </div>
                            <?php endif; ?>

                            <div class="table-responsive">
                                <table class="table table-bordered table-hover">
                                    <thead>
                                        <tr>
                                            <th>Tour Name</th>
                                            <th>Guide</th>
                                            <th>Vehicle</th>
                                            <th>Pickup Time</th>
                                            <th>Pickup Location</th>
                                            <th>PAX</th>
                                            <th>Available Seats</th>
                                            <th>Remarks</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                            $currentTour = '';
                                            $tourCount = 0;
                                        ?>
                                        <?php $__empty_1 = true; $__currentLoopData = $events; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $event): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                            <?php if($currentTour !== $event->tour_name): ?>
                                                <?php
                                                    $currentTour = $event->tour_name;
                                                    $tourCount = $events->where('tour_name', $currentTour)->count();
                                                ?>
                                                <tr>
                                                    <td rowspan="<?php echo e($tourCount); ?>"><?php echo e($event->tour_name); ?></td>
                                                    <td><?php echo e($event->guide); ?></td>
                                                    <td><?php echo e($event->vehicle); ?></td>
                                                    <td><?php echo e($event->pickup_time); ?></td>
                                                    <td><?php echo e($event->pickup_location); ?></td>
                                                    <td><?php echo e($event->pax); ?></td>
                                                    <td><?php echo e($event->available); ?></td>
                                                    <td><?php echo e($event->remark); ?></td>
                                                </tr>
                                            <?php else: ?>
                                                <tr>
                                                    <td><?php echo e($event->guide); ?></td>
                                                    <td><?php echo e($event->vehicle); ?></td>
                                                    <td><?php echo e($event->pickup_time); ?></td>
                                                    <td><?php echo e($event->pickup_location); ?></td>
                                                    <td><?php echo e($event->pax); ?></td>
                                                    <td><?php echo e($event->available); ?></td>
                                                    <td><?php echo e($event->remark); ?></td>
                                                </tr>
                                            <?php endif; ?>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                            <tr>
                                                <td colspan="8" class="text-center">No tours scheduled for today</td>
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

<script>
    $(document).ready(function() {
        // Initialize any datepickers or timepickers if needed
        $('input[name="pickup_time"]').datetimepicker({
            format: 'HH:mm'
        });
    });
</script>
<?php $__env->stopSection(); ?> 
<?php echo $__env->make('partials.main', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/nordpzbm/hr.nordictravels.tech/resources/views/operations/check-sheet.blade.php ENDPATH**/ ?>