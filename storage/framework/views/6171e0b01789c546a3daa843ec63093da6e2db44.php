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
                                <h4 class="page-title">Manage Office Worker</h4>
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item">
                                        <a href="javascript:void(0);">Home</a>
                                    </li>
                                    <li class="breadcrumb-item">
                                        <a href="javascript:void(0);">Office Workers</a>
                                    </li>
                                    <li class="breadcrumb-item active">View Office Workers</li>
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
                                <h4 class="mt-0 header-title">Latest Office Workers</h4>

                                <div class="table-responsive">
                                    <table id="datatable-buttons"
                                        class="table table-striped table-bordered dt-responsive"
                                        style="border-collapse: collapse; border-spacing: 0; width: 100%">
                                        <thead>
                                            <tr>
                                                <th>Name</th>
                                                <th>Email</th>
                                                <th>Phone Number</th>
                                                <th>Note</th>
                                                <th>Allow Report Hours</th>
                                                <th>Supervisor</th>
                                                <th>Color</th>
                                                <th>Intern Status</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $__currentLoopData = $staffUsers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $staffUser): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <tr>
                                                    <td><?php echo e($staffUser->name); ?></td>
                                                    <td><?php echo e($staffUser->email); ?></td>
                                                    <td><?php echo e($staffUser->phone_number); ?></td>
                                                    <td><?php echo e($staffUser->rate); ?></td>
                                                    <td><?php echo e($staffUser->allow_report_hours ? 'Yes' : 'No'); ?></td>
                                                    <td>
                                                        <?php if($staffUser->supervisor != null): ?>
                                                            <?php echo e(\App\Models\User::find($staffUser->supervisor)->name ?? 'Unknown Supervisor'); ?>

                                                        <?php else: ?>
                                                            <form action="/add-supervisor" method="POST">
                                                                <?php echo csrf_field(); ?>
                                                                <select name="supervisor_id" required class="form-control" required>
                                                                    <?php
                                                                        $supervisors = \App\Models\User::where(
                                                                            'role',
                                                                            'supervisor',
                                                                        )->get();
                                                                    ?>
                                                                    <option value="">Select Supervisor</option>
                                                                    <?php $__currentLoopData = $supervisors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $supervisor): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                                        <option value="<?php echo e($supervisor->id); ?>">
                                                                            <?php echo e($supervisor->name); ?></option>
                                                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                                </select>
                                                                <input type="hidden" name="staff_user_id"
                                                                    value="<?php echo e($staffUser->id); ?>">
                                                                <button type="submit" class="btn btn-primary mt-2">Assign
                                                                    Supervisor</button>
                                                            </form>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <div style="display: flex; align-items: center;">
                                                            <div style="width: 20px; height: 20px; background-color: <?php echo e($staffUser->color); ?>; margin-right: 10px; border: 1px solid #ccc;"></div>
                                                            <?php echo e($staffUser->color); ?>

                                                        </div>
                                                    </td>
                                                    <td><?php echo e($staffUser->user->is_intern ? 'Yes' : 'No'); ?></td>
                                                    <td>
                                                        <a href="<?php echo e(route('staff.edit', $staffUser->id)); ?>"
                                                            class="btn btn-sm btn-primary">Edit</a>

                                                        <form action="<?php echo e(route('staff.destroy', $staffUser->id)); ?>"
                                                            method="POST" style="display:inline-block;">
                                                            <?php echo csrf_field(); ?>
                                                            <?php echo method_field('GET'); ?>
                                                            <button type="submit" class="btn btn-sm btn-danger"
                                                                onclick="return confirm('Are you sure you want to delete this staff?')">Delete</button>
                                                        </form>

                                                        <!-- Change Password Button -->
                                                        <button type="button" class="btn btn-sm btn-warning"
                                                            data-toggle="modal"
                                                            data-target="#changePasswordModal<?php echo e($staffUser->id); ?>">
                                                            Change Password
                                                        </button>
                                                    </td>
                                                </tr>

                                                <!-- Change Password Modal -->
                                                <div class="modal fade" id="changePasswordModal<?php echo e($staffUser->id); ?>"
                                                    tabindex="-1" role="dialog"
                                                    aria-labelledby="changePasswordModalLabel<?php echo e($staffUser->id); ?>"
                                                    aria-hidden="true">
                                                    <div class="modal-dialog" role="document">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title"
                                                                    id="changePasswordModalLabel<?php echo e($staffUser->id); ?>">
                                                                    Change Password for <?php echo e($staffUser->name); ?></h5>
                                                                <button type="button" class="close" data-dismiss="modal"
                                                                    aria-label="Close">
                                                                    <span aria-hidden="true">&times;</span>
                                                                </button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <form
                                                                    action="<?php echo e(route('staff.change-password', $staffUser->id)); ?>"
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

<?php echo $__env->make('partials.main', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/nordpzbm/hr.nordictravels.tech/resources/views/tour_guides/staff-index.blade.php ENDPATH**/ ?>