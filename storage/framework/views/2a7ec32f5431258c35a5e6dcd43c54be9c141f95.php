
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
                    <div class="col-8">
                        <div class="card">
                            <div class="card-body">
                                <?php if(session('error')): ?>
                                    <div class="alert alert-danger">
                                        <?php echo e(session('error')); ?>

                                    </div>
                                <?php endif; ?>

                                <?php if(session()->has('failed')): ?>
                                    <div class="alert alert-danger">
                                        <?php echo e(session()->get('failed')); ?>

                                    </div>
                                <?php endif; ?>
            
                                <?php if(session()->has('success')): ?>
                                    <div class="alert alert-success">
                                        <?php echo e(session()->get('success')); ?>

                                    </div>
                                <?php endif; ?>
                                <h4 class="mt-0 header-title">Latest Tour Durations</h4>

                                <div class="table">
                                    <table id="datatable-buttons"
                                        class="table table-striped table-bordered"
                                        style="border-collapse: collapse; border-spacing: 0; width: 100%">
                                        <thead>
                                            <tr>
                                                <th>Tour</th>
                                                <th>Duration (in Hours)</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $__currentLoopData = $tourDurations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tourDuration): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <tr>
                                                    <td><?php echo e($tourDuration->tour); ?></td>
                                                    <td>
                                                        <?php
                                                            $hours = floor($tourDuration->duration / 60);
                                                            $minutes = $tourDuration->duration % 60;
                                                            echo $hours . '.' . str_pad($minutes, 2, '0', STR_PAD_LEFT);
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <a href="<?php echo e(route('tour-durations.edit', $tourDuration->id)); ?>" class="btn btn-warning btn-sm">Edit</a>
                                                        <form action="<?php echo e(route('tour-durations.destroy', $tourDuration->id)); ?>" method="POST" style="display:inline-block;">
                                                            <?php echo csrf_field(); ?>
                                                            <?php echo method_field('DELETE'); ?>
                                                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this tour duration?');">Delete</button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </tbody>
                                        
                                        
                                    </table>
                                </div>


                            </div>

                        </div>
                    </div>

                    <div class="col-4">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="mt-0 header-title">Add New Tour Duration</h4>
                                <form method="POST" action="<?php echo e(route('tour-durations.store')); ?>">
                                    <?php echo csrf_field(); ?>
                                    <div class="form-group">
                                        <label for="tour">Tour Name</label>
                                        <input type="text" name="tour" id="tour" class="form-control" placeholder="Enter tour name" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="duration">Duration (HH.MM)</label>
                                        <input type="text" name="duration" id="duration" class="form-control" placeholder="Enter duration (e.g., 5.30 for 5 hours 30 minutes)" required pattern="\d+\.\d{2}">
                                    </div>
                                    <button type="submit" class="btn btn-primary">Add Tour Duration</button>
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

<?php echo $__env->make('partials.main', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/nordpzbm/hr.nordictravels.tech/resources/views/tour_duration/index.blade.php ENDPATH**/ ?>