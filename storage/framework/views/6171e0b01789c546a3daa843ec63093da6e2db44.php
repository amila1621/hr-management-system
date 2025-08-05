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

                                <!-- Department Filter Buttons -->
                                <div class="mb-4">
                                    <div class="btn-group department-filters">
                                        <button type="button" class="btn btn-primary active" data-department="all">All</button>
                                        <?php
                                        $departments = App\Models\Departments::orderBy('department')->pluck('department')->toArray();
                
                                        ?>
                                        
                                        <?php $__currentLoopData = $departments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $dept): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <button type="button" class="btn btn-secondary" data-department="<?php echo e($dept); ?>">
                                                <?php echo e($dept); ?>

                                            </button>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </div>
                                </div>

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
                                                <th>Color</th>
                                                <th>Department</th>
                                                <th>Intern Status</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $__currentLoopData = $staffUsers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $staffUser): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <tr>
                                                    <td><?php echo e($staffUser->name); ?></td>
                                                    <td><?php echo e($staffUser->full_name); ?></td>
                                                    <td><?php echo e($staffUser->email); ?></td>
                                                    <td><?php echo e($staffUser->phone_number); ?></td>
                                                    <td><?php echo e($staffUser->rate); ?></td>
                                                    <td><?php echo e($staffUser->allow_report_hours ? 'Yes' : 'No'); ?></td>
                                                    
                                                    <td>
                                                        <div style="display: flex; align-items: center;">
                                                            <div style="width: 20px; height: 20px; background-color: <?php echo e($staffUser->color); ?>; margin-right: 10px; border: 1px solid #ccc;"></div>
                                                            <?php echo e($staffUser->color); ?>

                                                        </div>
                                                    </td>
                                                    <td><?php echo e($staffUser->department); ?></td>
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
        
        // Department filtering functionality
        $(document).ready(function() {
            $('.department-filters button').on('click', function() {
                // Update active button
                $('.department-filters button').removeClass('active btn-primary').addClass('btn-secondary');
                $(this).removeClass('btn-secondary').addClass('active btn-primary');
                
                var department = $(this).data('department');
                
                // Filter the table rows
                if (department === 'all') {
                    $('#datatable-buttons tbody tr').show();
                } else {
                    $('#datatable-buttons tbody tr').hide();
                    $('#datatable-buttons tbody tr').each(function() {
                        if ($(this).find('td:nth-child(8)').text().trim() === department) {
                            $(this).show();
                        }
                    });
                }
            });
        });
    </script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('partials.main', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/nordpzbm/hr.nordictravels.tech/resources/views/tour_guides/staff-index.blade.php ENDPATH**/ ?>