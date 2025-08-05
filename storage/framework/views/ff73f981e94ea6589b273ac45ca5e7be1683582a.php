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
                                <h4 class="page-title">Manage Supervisors</h4>
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item">
                                        <a href="javascript:void(0);">Home</a>
                                    </li>
                                    <li class="breadcrumb-item">
                                        <a href="javascript:void(0);">Supervisors</a>
                                    </li>
                                    <li class="breadcrumb-item active">View Supervisors</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- end page-title -->

                <div class="row">
                    <?php if(session('success')): ?>
                        <div class="alert alert-success">
                            <?php echo e(session('success')); ?>

                        </div>
                    <?php endif; ?>

                    <?php if(session('error')): ?>
                        <div class="alert alert-danger">
                            <?php echo e(session('error')); ?>

                        </div>
                    <?php endif; ?>

                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="mt-0 header-title">Latest Supervisors</h4>

                                <div class="table-responsive">
                                    <table id="datatable-buttons"
                                        class="table table-striped table-bordered dt-responsive nowrap"
                                        style="border-collapse: collapse; border-spacing: 0; width: 100%">
                                        <thead>
                                            <tr>
                                                <th>Name</th>
                                                <th>Email</th>
                                                <th>Phone Number</th>
                                                <th>Rate</th>
                                                <th>Color</th>
                                                <th>Department</th>
                                                <th>Display Midnight Phone</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $__currentLoopData = $supervisors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $supervisor): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <?php if($supervisor->department == 'AM'): ?>
                                                <?php continue; ?>
                                            <?php endif; ?>
                                                <tr>
                                                    <td><?php echo e($supervisor->name); ?></td>
                                                    <td><?php echo e($supervisor->email); ?></td>
                                                    <td><?php echo e($supervisor->supervisor->phone_number); ?></td>
                                                    <td><?php echo e($supervisor->supervisor->rate); ?></td>
                                                    <td><?php echo e($supervisor->supervisor->color); ?></td>
                                                    <td><?php echo e($supervisor->supervisor->department); ?></td>
                                                    <td><?php echo e($supervisor->supervisor->display_midnight_phone ? 'Yes' : 'No'); ?></td>
                                                   
                                                    <td>
                                                        <a href="<?php echo e(route('supervisors.edit', $supervisor->id)); ?>"
                                                            class="btn btn-sm btn-primary">Edit</a>

                                                        <form action="<?php echo e(route('supervisors.destroy', $supervisor->id)); ?>"
                                                            method="get" style="display:inline-block;">
                                                            <?php echo csrf_field(); ?>
                                                            <?php echo method_field('DELETE'); ?>
                                                            <button type="submit" class="btn btn-sm btn-danger"
                                                                onclick="return confirm('Are you sure you want to delete this supervisor?')">Delete</button>
                                                        </form>
                                                        
                                                        <button type="button" class="btn btn-sm btn-warning"
                                                            data-toggle="modal"
                                                            data-target="#changePasswordModal<?php echo e($supervisor->id); ?>">
                                                            Change Password
                                                        </button>
                                                    </td>
                                                </tr>

                                                <!-- Change Password Modal -->
                                                <div class="modal fade" id="changePasswordModal<?php echo e($supervisor->id); ?>"
                                                    tabindex="-1" role="dialog"
                                                    aria-labelledby="changePasswordModalLabel<?php echo e($supervisor->id); ?>"
                                                    aria-hidden="true">
                                                    <div class="modal-dialog" role="document">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title"
                                                                    id="changePasswordModalLabel<?php echo e($supervisor->id); ?>">
                                                                    Change Password for <?php echo e($supervisor->name); ?></h5>
                                                                <button type="button" class="close" data-dismiss="modal"
                                                                    aria-label="Close">
                                                                    <span aria-hidden="true">&times;</span>
                                                                </button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <form
                                                                    action="<?php echo e(route('operations.change-password', $supervisor->id)); ?>"
                                                                    method="POST">
                                                                    <?php echo csrf_field(); ?>
                                                                    <?php echo method_field('PUT'); ?>
                                                                    <div class="form-group">
                                                                        <label for="password">New Password</label>
                                                                        <input type="password" name="password"
                                                                            class="form-control" required>
                                                                    </div>
                                                                    <div class="form-group">
                                                                        <label for="password_confirmation">Confirm
                                                                            Password</label>
                                                                        <input type="password" name="password_confirmation"
                                                                            class="form-control" required>
                                                                    </div>
                                                                    <button type="submit" class="btn btn-primary">Change
                                                                        Password</button>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <!-- End of Modal -->
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </tbody>
                                    </table>
                                </div>
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

<?php echo $__env->make('partials.main', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/nordpzbm/hr.nordictravels.tech/resources/views/tour_guides/supervisors-index.blade.php ENDPATH**/ ?>