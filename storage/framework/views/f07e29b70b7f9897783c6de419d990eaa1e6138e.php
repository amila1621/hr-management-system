

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
                                <h4 class="page-title">Manage Guides</h4>
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item">
                                        <a href="javascript:void(0);">Home</a>
                                    </li>
                                    <li class="breadcrumb-item">
                                        <a href="javascript:void(0);">Guides</a>
                                    </li>
                                    <li class="breadcrumb-item active">View Guides</li>
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
                                <h4 class="mt-0 header-title">Latest Guides</h4>

                                <div class="table-responsive">
                                    <table id="datatable-buttons"
                                        class="table table-striped table-bordered dt-responsive"
                                        style="border-collapse: collapse; border-spacing: 0; width: 100%">
                                        <thead>
                                            <tr>
                                                <th>Name</th>
                                                <th>Full Name</th>
                                                <th>Email</th>
                                                <th>Phone Number</th>
                                                <th>Note</th>
                                                <th>Allow Report Hours</th>
                                                <th>Supervisor</th>
                                                <th>Intern Status</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $__currentLoopData = $tourGuides; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tourGuide): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <tr>
                                                    <td><?php echo e($tourGuide->name); ?></td>
                                                    <td><?php echo e($tourGuide->full_name); ?></td>
                                                    <td><?php echo e($tourGuide->email); ?></td>
                                                    <td><?php echo e($tourGuide->phone_number); ?></td>
                                                    <td><?php echo e($tourGuide->rate); ?></td>
                                                    <td><?php echo e($tourGuide->allow_report_hours ? 'Yes' : 'No'); ?></td>
                                                    <td> <?php if($tourGuide->supervisor != null): ?>
                                                            <?php echo e(\App\Models\User::find($tourGuide->supervisor)->name ?? 'Unknown Supervisor'); ?>

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
                                                                <input type="hidden" name="tour_guide_id"
                                                                    value="<?php echo e($tourGuide->id); ?>">
                                                                <button type="submit" class="btn btn-primary mt-2">Assign
                                                                    Supervisor</button>
                                                            </form>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo e($tourGuide->user->is_intern ? 'Yes' : 'No'); ?></td>

                                                    <td>
                                                        <a href="<?php echo e(route('tour-guides.edit', $tourGuide->id)); ?>"
                                                            class="btn btn-sm btn-primary">Edit</a>

                                                        <form action="<?php echo e(route('tour-guides.destroy', $tourGuide->id)); ?>"
                                                            method="POST" style="display:inline-block;">
                                                            <?php echo csrf_field(); ?>
                                                            <?php echo method_field('DELETE'); ?>
                                                            <button type="submit" class="btn btn-sm btn-danger"
                                                                onclick="return confirm('Are you sure you want to delete this tour guide?')">Delete</button>
                                                        </form>


                                                        <!-- Change Password Button -->
                                                        <button type="button" class="btn btn-sm btn-warning"
                                                            data-toggle="modal"
                                                            data-target="#changePasswordModal<?php echo e($tourGuide->id); ?>">
                                                            Change Password
                                                        </button>

                                                        <?php if(!$tourGuide->is_hidden): ?>
                                                        <form action="<?php echo e(route('tour-guides.hide', $tourGuide->id)); ?>"
                                                            method="POST" style="display:inline-block;">
                                                            <?php echo csrf_field(); ?>
                                                            <?php echo method_field('PUT'); ?>
                                                            <button type="submit" class="btn btn-sm btn-secondary"
                                                                onclick="return confirm('Are you sure you want to hide this tour guide?')">Hide</button>
                                                        </form>
                                                        <?php else: ?>
                                                        <form action="<?php echo e(route('tour-guides.unhide', $tourGuide->id)); ?>"
                                                            method="POST" style="display:inline-block;">
                                                            <?php echo csrf_field(); ?>
                                                            <?php echo method_field('PUT'); ?>
                                                            <button type="submit" class="btn btn-sm btn-secondary"
                                                                onclick="return confirm('Are you sure you want to unhide this tour guide?')">Unhide</button>
                                                        </form>
                                                        <?php endif; ?>

                                                        
                                                        <form action="<?php echo e(route('tour-guides.terminate', $tourGuide->id)); ?>"
                                                            method="POST" style="display:inline-block;">
                                                            <?php echo csrf_field(); ?>
                                                            <?php echo method_field('POST'); ?>
                                                            <button type="submit" class="btn btn-sm btn-danger"
                                                                onclick="return confirm('Are you sure you want to Terminate this tour guide?')">Terminate</button>
                                                        </form>

                                                        <!-- Make a Guide/Staff Button -->
                                                        <?php
                                                            $isAlreadyStaff = \App\Models\StaffUser::where('user_id', $tourGuide->user_id)->exists();
                                                        ?>
                                                        <?php if(!$isAlreadyStaff): ?>
                                                        <button type="button" class="btn btn-sm btn-success"
                                                            data-toggle="modal"
                                                            data-target="#makeGuideStaffModal<?php echo e($tourGuide->id); ?>">
                                                            Make a Guide/Staff
                                                        </button>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>

                                                <!-- Change Password Modal -->
                                                <div class="modal fade" id="changePasswordModal<?php echo e($tourGuide->id); ?>"
                                                    tabindex="-1" role="dialog"
                                                    aria-labelledby="changePasswordModalLabel<?php echo e($tourGuide->id); ?>"
                                                    aria-hidden="true">
                                                    <div class="modal-dialog" role="document">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title"
                                                                    id="changePasswordModalLabel<?php echo e($tourGuide->id); ?>">
                                                                    Change Password for <?php echo e($tourGuide->name); ?></h5>
                                                                <button type="button" class="close" data-dismiss="modal"
                                                                    aria-label="Close">
                                                                    <span aria-hidden="true">&times;</span>
                                                                </button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <form
                                                                    action="<?php echo e(route('tour-guides.change-password', $tourGuide->id)); ?>"
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
                                                <!-- End of Change Password Modal -->

                                                <!-- Make a Guide/Staff Modal -->
                                                <?php if(!$isAlreadyStaff): ?>
                                                <div class="modal fade" id="makeGuideStaffModal<?php echo e($tourGuide->id); ?>"
                                                    tabindex="-1" role="dialog"
                                                    aria-labelledby="makeGuideStaffModalLabel<?php echo e($tourGuide->id); ?>"
                                                    aria-hidden="true">
                                                    <div class="modal-dialog" role="document">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title"
                                                                    id="makeGuideStaffModalLabel<?php echo e($tourGuide->id); ?>">
                                                                    Make <?php echo e($tourGuide->name); ?> a Guide/Staff</h5>
                                                                <button type="button" class="close" data-dismiss="modal"
                                                                    aria-label="Close">
                                                                    <span aria-hidden="true">&times;</span>
                                                                </button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <form action="<?php echo e(route('tour-guides.make-guide-staff', $tourGuide->id)); ?>" method="POST">
                                                                    <?php echo csrf_field(); ?>
                                                                    <?php echo method_field('PUT'); ?>
                                                                    <div class="form-group">
                                                                        <label for="department_id">Department</label>
                                                                        <select name="department_id" class="form-control" required>
                                                                            <?php
                                                                                $departments = \App\Models\Departments::all();
                                                                            ?>
                                                                            <option value="">Select Department</option>
                                                                            <?php $__currentLoopData = $departments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $department): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                                                <option value="<?php echo e($department->id); ?>">
                                                                                    <?php echo e($department->department); ?>

                                                                                </option>
                                                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                                        </select>
                                                                    </div>
                                                                    <input type="hidden" name="tour_guide_id" value="<?php echo e($tourGuide->id); ?>">
                                                                    <button type="submit" class="btn btn-success">
                                                                        Make Guide/Staff
                                                                    </button>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <?php endif; ?>
                                                <!-- End of Make a Guide/Staff Modal -->
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

<?php echo $__env->make('partials.main', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/nordpzbm/hr.nordictravels.tech/resources/views/tour_guides/index.blade.php ENDPATH**/ ?>