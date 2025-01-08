<?php $__env->startSection('content'); ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/dark.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
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
                                <h4 class="page-title">Salary Updates</h4>
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item">
                                        <a href="javascript:void(0);">Home</a>
                                    </li>
                                    <li class="breadcrumb-item">
                                        <a href="javascript:void(0);">Payroll</a>
                                    </li>
                                    <li class="breadcrumb-item active">Salary Updates</li>
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
                                <h4 class="mt-0 header-title">Salary Update List</h4>

                                <div class="table">
                                    <table id="datatable-buttons"
                                        class="table table-striped table-bordered"
                                        style="border-collapse: collapse; border-spacing: 0; width: 100%">
                                        <thead>
                                            <tr>
                                                <th>Employee</th>
                                                <th>Effective Date</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $__currentLoopData = $salaryUpdated; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $update): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <tr>
                                                    <td><?php echo e($update->guide_name); ?></td>
                                                    <td><?php echo e(date('d-m-Y', strtotime($update->effective_date))); ?></td>
                                                    <td>
                                                        <button type="button" 
                                                                class="btn btn-warning btn-sm edit-btn" 
                                                                data-id="<?php echo e($update->id); ?>"
                                                                data-guide="<?php echo e($update->guide_id); ?>"
                                                                data-date="<?php echo e($update->effective_date); ?>">
                                                            Edit
                                                        </button>
                                                        <form action="<?php echo e(route('salary-updates.destroy', $update->id)); ?>" method="POST" style="display:inline-block;">
                                                            <?php echo csrf_field(); ?>
                                                            <?php echo method_field('DELETE'); ?>
                                                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this salary update?');">Delete</button>
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

                                <?php if($errors->any()): ?>
                                    <div class="alert alert-danger">
                                        <ul class="mb-0">
                                            <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <li><?php echo e($error); ?></li>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>

                                <h4 class="mt-0 header-title" id="form-title">Add New Salary Update</h4>
                                <form method="POST" action="<?php echo e(route('salary-updates.store')); ?>" id="salary-form">
                                    <?php echo csrf_field(); ?>
                                    <div id="method-div"></div>
                                    
                                    <div class="form-group">
                                        <label for="guide_id">Employee</label>
                                        <select name="guide_id" id="guide_id" 
                                            class="form-control <?php $__errorArgs = ['guide_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" required>
                                            <option value="">Select Employee</option>
                                            <?php $__currentLoopData = $guides; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $guide): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <option value="<?php echo e($guide->id); ?>" <?php echo e(old('guide_id') == $guide->id ? 'selected' : ''); ?>>
                                                    <?php echo e($guide->name); ?>

                                                </option>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </select>
                                        <?php $__errorArgs = ['guide_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                            <span class="invalid-feedback" role="alert">
                                                <strong><?php echo e($message); ?></strong>
                                            </span>
                                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                    </div>


                                    <div class="form-group">
                                        <label for="update_date">Effective Date</label>
                                        <input type="text" name="effective_date" id="update_date" 
                                            class="form-control flatpickr <?php $__errorArgs = ['update_date'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                            value="<?php echo e(old('update_date')); ?>"
                                            placeholder="Select Date..."
                                            required>
                                        <?php $__errorArgs = ['update_date'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                            <span class="invalid-feedback" role="alert">
                                                <strong><?php echo e($message); ?></strong>
                                            </span>
                                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                    </div>


                                    <button type="submit" class="btn btn-primary">Add Salary Update</button>
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

    <script>
        $(document).ready(function() {
            $('.edit-btn').on('click', function() {
                const id = $(this).data('id');
                const guideId = $(this).data('guide');
                const effectiveDate = $(this).data('date');
                
                // Update form title
                $('#form-title').text('Edit Salary Update');
                
                // Update form action and method
                $('#salary-form').attr('action', `/salary-updates/${id}`);
                $('#method-div').html('<?php echo method_field("PUT"); ?>');
                
                // Populate form fields
                $('#guide_id').val(guideId);
                $('#update_date').val(effectiveDate);
                
                // Scroll to form
                $('html, body').animate({
                    scrollTop: $("#salary-form").offset().top
                }, 500);
            });
        });
    </script>

    <script>
        flatpickr("#update_date", {
            dateFormat: "Y-m-d",
            theme: "dark",
            allowInput: true,
            altInput: true,
            altFormat: "d/m/Y",
        });
    </script>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('partials.main', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/nordpzbm/hr.nordictravels.tech/resources/views/salary_updated/index.blade.php ENDPATH**/ ?>