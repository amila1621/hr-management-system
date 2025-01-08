

<?php $__env->startSection('content'); ?>
    <div class="content-page">
        <div class="content">
            <div class="container-fluid">
                <div class="page-title-box">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <div class="page-title-box">
                                <h4 class="page-title">Create User</h4>
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item">
                                        <a href="javascript:void(0);">Home</a>
                                    </li>
                                    <li class="breadcrumb-item active">Create User</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>

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

                <!-- Display Validation Errors -->
                <?php if($errors->any()): ?>
                    <div class="alert alert-danger">
                        <ul>
                            <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <li><?php echo e($error); ?></li>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="mt-0 header-title">Create New User</h4>
                                <form action="<?php echo e(route('new-users.store')); ?>" method="POST">
                                    <?php echo csrf_field(); ?>
                                    <div class="form-group">
                                        <label for="name">Name</label>
                                        <input type="text" name="name" class="form-control" required
                                            value="<?php echo e(old('name')); ?>">
                                        <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                            <small class="text-danger"><?php echo e($message); ?></small>
                                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                    </div>

                                    <div class="form-group">
                                        <label for="name">Full Name</label>
                                        <input type="text" name="full_name" class="form-control" required
                                            value="<?php echo e(old('full_name')); ?>">
                                        <?php $__errorArgs = ['full_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                            <small class="text-danger"><?php echo e($message); ?></small>
                                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                    </div>

                                    <div class="form-group">
                                        <label for="Role">Role</label>
                                        <select name="role" class="form-control" id="role-select" required>
                                            <option value="guide" <?php echo e(old('role') == 'guide' ? 'selected' : ''); ?>>Guide
                                            </option>
                                            <option value="staff" <?php echo e(old('role') == 'staff' ? 'selected' : ''); ?>>Office workers
                                            </option>
                                            <?php if(Auth::user()->role == 'admin'): ?>
                                                <option value="supervisor" <?php echo e(old('role') == 'supervisor' ? 'selected' : ''); ?>> Supervisor</option>
                                                <option value="operation" <?php echo e(old('role') == 'operation' ? 'selected' : ''); ?>>Operation</option>
                                                <option value="admin" <?php echo e(old('role') == 'admin' ? 'selected' : ''); ?>>Admin</option>
                                                <option value="team-lead" <?php echo e(old('role') == 'team-lead' ? 'selected' : ''); ?>>Team Lead</option>
                                                <option value="hr-assistant" <?php echo e(old('role') == 'hr-assistant' ? 'selected' : ''); ?>>HR Assistant</option>
                                            <?php endif; ?>

                                        </select>
                                        <?php $__errorArgs = ['role'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                            <small class="text-danger"><?php echo e($message); ?></small>
                                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                    </div>

                                    <!-- Fields specific to guide -->
                                    <div id="guide-fields" class="hr-assistant-fields" style="display: none;">
                                        <div class="form-group">
                                            <label for="phone_number">Phone Number</label>
                                            <input type="text" name="phone_number" class="form-control"
                                                value="<?php echo e(old('phone_number')); ?>">
                                            <?php $__errorArgs = ['phone_number'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                                <small class="text-danger"><?php echo e($message); ?></small>
                                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                        </div>
                                        <div class="form-group">
                                            <label for="rate">Rate</label>
                                            <input type="text" name="rate" class="form-control"
                                                value="<?php echo e(old('rate')); ?>">
                                            <?php $__errorArgs = ['rate'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                                <small class="text-danger"><?php echo e($message); ?></small>
                                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                        </div>
                                        <div class="form-group not-hr-assistant-fields">
                                            <label for="allow_report_hours">Allow Report Hours for Work</label>
                                            <select name="allow_report_hours" class="form-control">
                                                <option value="1"
                                                    <?php echo e(old('allow_report_hours') == '1' ? 'selected' : ''); ?>>Yes</option>
                                                <option value="0"
                                                    <?php echo e(old('allow_report_hours') == '0' ? 'selected' : ''); ?>>No</option>
                                            </select>
                                            <?php $__errorArgs = ['allow_report_hours'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                                <small class="text-danger"><?php echo e($message); ?></small>
                                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                        </div>
                                        <?php if(Auth::user()->role == 'admin'): ?>
                                            <div class="form-group not-hr-assistant-fields">
                                                <label for="supervisor">Supervisor</label>
                                                <select name="supervisor" class="form-control">
                                                    <?php $__currentLoopData = $supervisors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $supervisor): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                        <option value="<?php echo e($supervisor->id); ?>"
                                                            <?php echo e(old('supervisor') == $supervisor->id ? 'selected' : ''); ?>>
                                                            <?php echo e($supervisor->name); ?>

                                                        </option>
                                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                </select>
                                                <?php $__errorArgs = ['supervisor'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                                    <small class="text-danger"><?php echo e($message); ?></small>
                                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                            </div>
                                        <?php else: ?>
                                            <input type="hidden" name="supervisor" value="<?php echo e(Auth::id()); ?>">
                                        <?php endif; ?>

                                    </div>

                                    <div class="form-group">
                                        <label for="email">Email</label>
                                        <input type="email" name="email" class="form-control"
                                            value="<?php echo e(old('email')); ?>">
                                        <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                            <small class="text-danger"><?php echo e($message); ?></small>
                                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                    </div>

                                    <div class="form-group">
                                        <label for="password">Password</label>
                                        <input type="password" name="password" class="form-control" required>
                                        <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                            <small class="text-danger"><?php echo e($message); ?></small>
                                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                    </div>

                                    <div class="form-group">
                                        <label for="password_confirmation">Confirm Password</label>
                                        <input type="password" name="password_confirmation" class="form-control" required>
                                        <?php $__errorArgs = ['password_confirmation'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                            <small class="text-danger"><?php echo e($message); ?></small>
                                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                    </div>

                                    <div class="form-group">
                                        <label for="is_intern">Intern Status</label>
                                        <select name="is_intern" class="form-control">
                                            <option value="0" <?php echo e(old('is_intern') == '0' ? 'selected' : ''); ?>>No</option>
                                            <option value="1" <?php echo e(old('is_intern') == '1' ? 'selected' : ''); ?>>Yes</option>
                                        </select>
                                        <?php $__errorArgs = ['is_intern'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                            <small class="text-danger"><?php echo e($message); ?></small>
                                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                    </div>

                                    <div class="form-group">
                                        <label for="color">Select Color</label>
                                        <input type="color" name="color" id="color-picker" class="form-control" value="<?php echo e(old('color', '#000000')); ?>">
                                        <input type="text" name="color_hex" id="color-hex" class="form-control mt-2" readonly value="<?php echo e(old('color_hex', '#000000')); ?>">
                                        <?php $__errorArgs = ['color'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                            <small class="text-danger"><?php echo e($message); ?></small>
                                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                    </div>

                                    <div class="form-group supervisor-fields" style="display: none;">
                                        <label for="display_midnight_phone">Display Midnight Phone</label>
                                        <select name="display_midnight_phone" class="form-control">
                                            <option value="0" <?php echo e(old('display_midnight_phone') == '0' ? 'selected' : ''); ?>>No</option>
                                            <option value="1" <?php echo e(old('display_midnight_phone') == '1' ? 'selected' : ''); ?>>Yes</option>
                                        </select>
                                        <?php $__errorArgs = ['display_midnight_phone'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                            <small class="text-danger"><?php echo e($message); ?></small>
                                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                    </div>

                                    <button type="submit" class="btn btn-primary waves-effect waves-light">Create</button>
                                </form>



                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- jQuery for dynamic field display -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            function toggleGuideFields() {
                if ($('#role-select').val() === 'guide' || $('#role-select').val() === 'staff') {
                    $('#guide-fields').show();
                    $('.supervisor-fields').hide();
                } else if($('#role-select').val() === 'hr-assistant' || $('#role-select').val() === 'team-lead' || $('#role-select').val() === 'operation') {
                    $('.hr-assistant-fields').show();
                    $('.not-hr-assistant-fields').hide();
                    $('.supervisor-fields').hide();
                } else if($('#role-select').val() === 'supervisor') {
                    $('.hr-assistant-fields').show();
                    $('.supervisor-fields').show();
                    $('.not-hr-assistant-fields').hide();
                } else {
                    $('#guide-fields').hide();
                    $('.supervisor-fields').hide();
                }
            }

            // Run the function on page load to handle pre-selected values
            toggleGuideFields();

            // Listen for changes in the role select dropdown
            $('#role-select').on('change', function() {
                toggleGuideFields();
            });

            // Handle color picker changes
            $('#color-picker').on('input', function() {
                $('#color-hex').val($(this).val());
            });
        });
    </script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('partials.main', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/nordpzbm/hr.nordictravels.tech/resources/views/auth/new-user.blade.php ENDPATH**/ ?>