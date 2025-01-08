<?php $__env->startSection('content'); ?>
    <style>
        .col-mail-3 {
            position: absolute;
            top: 0;
            right: 20px;
            bottom: 0;
        }
    </style>
    <div class="content-page">
        <!-- Start content -->
        <div class="content">

            <div class="container-fluid">
                <div class="page-title-box">

                    <div class="row align-items-center ">
                        <div class="col-md-8">
                            <div class="page-title-box">
                                <h4 class="page-title">Manage Tour Durations</h4>
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item">
                                        <a href="javascript:void(0);">Home</a>
                                    </li>
                                    <li class="breadcrumb-item">
                                        <a href="javascript:void(0);">Tour Durations</a>
                                    </li>
                                    <li class="breadcrumb-item active">View Tour Durations</li>
                                </ol>
                            </div>
                        </div>

                    </div>
                </div>
                <!-- end page-title -->

                <div class="row">

                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="mt-0 header-title"><?php echo e(isset($tourDuration) ? 'Edit Tour Duration' : 'Add New Tour Duration'); ?></h4>
                                <form method="POST" action="<?php echo e(isset($tourDuration) ? route('tour-durations.update', $tourDuration->id) : route('tour-durations.store')); ?>">
                                    <?php echo csrf_field(); ?>
                                    <?php if(isset($tourDuration)): ?>
                                        <?php echo method_field('PUT'); ?>
                                    <?php endif; ?>
                                    <div class="form-group">
                                        <label for="tour">Tour Name</label>
                                        <input type="text" name="tour" id="tour" class="form-control" value="<?php echo e(isset($tourDuration) ? $tourDuration->tour : ''); ?>" placeholder="Enter tour name" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="duration">Duration (in Hours)</label>
                                        <input type="number" min="0" step="0.01" name="duration" id="duration" class="form-control" 
                                            value="<?php echo e(isset($tourDuration) ? sprintf('%.2f', floor($tourDuration->duration/60) + (($tourDuration->duration % 60)/100)) : ''); ?>" 
                                            placeholder="Enter duration in Hours" required>
                                    </div>
                                    <button type="submit" class="btn btn-primary"><?php echo e(isset($tourDuration) ? 'Update Tour Duration' : 'Add Tour Duration'); ?></button>
                                </form>
                            </div>
                        </div>
                    </div>
                    

                </div>
                <!-- end col -->
            </div>

        </div>
        <!-- container-fluid -->

    </div>
    <!-- content -->

    <script>
        $("ul:not(:has(li))").parent().parent().parent().css("display", "none");
    </script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('partials.main', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/nordpzbm/hr.nordictravels.tech/resources/views/tour_duration/edit.blade.php ENDPATH**/ ?>