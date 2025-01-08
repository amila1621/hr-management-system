  <!-- Add Flatpickr CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

<?php $__env->startSection('content'); ?>
    <div class="content-page">
        <div class="content">
            <div class="container-fluid">
                <div class="page-title-box">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <div class="page-title-box">
                                <h4 class="page-title">Missing Hours</h4>
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item">
                                        <a href="javascript:void(0);">Home</a>
                                    </li>
                                    <li class="breadcrumb-item active">Manage Missing Hours</li>
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
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="mt-0 header-title">Latest Missing Hours</h4>
                                <div class="table-responsive">
                                    <table class="table table-centered table-hover mb-0 table-striped table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Guide Name</th>
                                                <th>Date</th>
                                                <th>Normal Hours</th>
                                                <th>Normal Night Hours</th>
                                                <th>Holiday Hours</th>
                                                <th>Holiday Night Hours</th>
                                                <th>Tour Name/Reason</th>
                                                <th>Applied Month</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $__empty_1 = true; $__currentLoopData = $missingHours; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $entry): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                                <tr>
                                                    <td><a target="_blank" href="/get-guide-wise-reports?guide_id=<?php echo e($entry->guide_id); ?>&start_date=<?php echo e(\Carbon\Carbon::parse($entry->date)->format('Y-m-01')); ?>&end_date=<?php echo e(\Carbon\Carbon::parse($entry->date)->endOfMonth()->format('Y-m-d')); ?>"><?php echo e($entry->guide_name); ?></a></td>
                                                    <td><?php echo e(\Carbon\Carbon::parse($entry->date)->format('d M Y')); ?></td>
                                                    <td><?php echo e($entry->normal_hours); ?></td>
                                                    <td><?php echo e($entry->normal_night_hours); ?></td>
                                                    <td><?php echo e($entry->holiday_hours); ?></td>
                                                    <td><?php echo e($entry->holiday_night_hours); ?></td>
                                                    <td><?php echo e($entry->tour_name); ?></td>
                                                    <td><?php echo e(\Carbon\Carbon::parse($entry->applied_at)->format('M Y')); ?></td>
                                                    <td>
                                                        <button type="button" 
                                                                class="btn btn-warning btn-sm mr-1" 
                                                                onclick="openEditModal(<?php echo e(json_encode($entry)); ?>)">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <form action="<?php echo e(route('missing-hours.destroy', $entry->id)); ?>" method="POST" class="delete-form d-inline">
                                                            <?php echo csrf_field(); ?>
                                                            <?php echo method_field('DELETE'); ?>
                                                            <button type="submit" class="btn btn-danger btn-sm">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                                <tr>
                                                    <td colspan="9" class="text-center">No missing hours entries found</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="mt-0 header-title">Create New Missing Hours</h4>
                                <form action="<?php echo e(route('missing-hours.store')); ?>" method="POST">
                                    <?php echo csrf_field(); ?>
                                    <div class="form-group">
                                        <label for="tour_name">Tour Name / Reason</label>
                                        <input type="text" name="tour_name" id="tour_name" class="form-control" required>
                                        <?php $__errorArgs = ['tour_name'];
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
                                        <label for="title">Guide</label>
                                      <select name="guide_id" id="guide_id" class="form-control">
                                        <option value="">Select Guide</option>
                                        <?php $__currentLoopData = $guides; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $guide): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <option value="<?php echo e($guide->id); ?>"><?php echo e($guide->name); ?></option>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                      </select>
                                    </div>

                                    
                                    <div class="form-group">
                                        <label for="start_time">Start Time</label>
                                        <input type="text" name="start_time" id="start_time" class="form-control flatpickr-datetime flatpickr-input" readonly="readonly">
                                        <?php $__errorArgs = ['start_time'];
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
                                        <label for="end_time">End Time</label>
                                        <input type="text" name="end_time" id="end_time" class="form-control flatpickr-datetime flatpickr-input" readonly="readonly">
                                        <?php $__errorArgs = ['end_time'];
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
                                        <label for="applied_at">Applied Month</label>
                                        <input type="month" 
                                               name="applied_at" 
                                               id="applied_at" 
                                               value="<?php echo e(date('Y-m')); ?>" 
                                               class="form-control" 
                                               required>
                                        <?php $__errorArgs = ['applied_at'];
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

                                   
                                    


                                    <button type="submit" class="btn btn-primary waves-effect waves-light">Create Missing Hours</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Add Flatpickr JS -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        initFlatpickr();
        const deleteForms = document.querySelectorAll('.delete-form');
        

        function initFlatpickr() {
            flatpickr(".flatpickr-date", {
                dateFormat: "Y-m-d",
            });

            flatpickr(".flatpickr-datetime", {
                enableTime: true,
                dateFormat: "Y-m-d H:i",
                time_24hr: true
            });
        }
        
        deleteForms.forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                Swal.fire({
                    title: 'Are you sure?',
                    text: "These missing hours will be permanently deleted!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        this.submit();
                    }
                });
            });
        });
    });

    function openEditModal(entry) {
        // Set the form action URL
        const form = document.getElementById('editMissingHoursForm');
        form.action = `/missing-hours/${entry.id}`;

        // Populate the form fields
        document.getElementById('edit_tour_name').value = entry.tour_name;
        document.getElementById('edit_guide_id').value = entry.guide_id;
        document.getElementById('edit_applied_at').value = entry.applied_at.substring(0, 7); // YYYY-MM format

        // Initialize Flatpickr for datetime fields
        const startTimePicker = flatpickr("#edit_start_time", {
            enableTime: true,
            dateFormat: "Y-m-d H:i",
            time_24hr: true,
            defaultDate: entry.start_time
        });

        const endTimePicker = flatpickr("#edit_end_time", {
            enableTime: true,
            dateFormat: "Y-m-d H:i",
            time_24hr: true,
            defaultDate: entry.end_time
        });

        // Show the modal
        $('#editMissingHoursModal').modal('show');
    }

    // Initialize Flatpickr for all datetime inputs when document loads
    document.addEventListener('DOMContentLoaded', function() {
        flatpickr(".flatpickr-datetime", {
            enableTime: true,
            dateFormat: "Y-m-d H:i",
            time_24hr: true
        });
    });
</script>

<style>
    .content-page {
        position: relative;
        height: 100vh;
        overflow: hidden;
    }

    .content {
        height: calc(100vh - 70px); /* Adjust 70px based on your header height */
        overflow-y: auto;
        padding-bottom: 60px; /* Add padding to account for footer */
    }

    /* Optional: If you want a smoother scrolling experience */
    .content {
        scroll-behavior: smooth;
        -webkit-overflow-scrolling: touch;
    }

    /* Ensure footer stays at bottom */
    .footer {
        position: fixed;
        bottom: 0;
        right: 0;
        left: 240px; /* Adjust based on your sidebar width */
        z-index: 100;
        background: #fff;
    }
</style>

<div class="modal fade" id="editMissingHoursModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Missing Hours</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="editMissingHoursForm" method="POST">
                    <?php echo csrf_field(); ?>
                    <?php echo method_field('PUT'); ?>
                    <div class="form-group">
                        <label for="edit_tour_name">Tour Name / Reason</label>
                        <input type="text" name="tour_name" id="edit_tour_name" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="edit_guide_id">Guide</label>
                        <select name="guide_id" id="edit_guide_id" class="form-control">
                            <option value="">Select Guide</option>
                            <?php $__currentLoopData = $guides; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $guide): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($guide->id); ?>"><?php echo e($guide->name); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="edit_start_time">Start Time</label>
                        <input type="text" name="start_time" id="edit_start_time" class="form-control flatpickr-datetime" readonly="readonly">
                    </div>

                    <div class="form-group">
                        <label for="edit_end_time">End Time</label>
                        <input type="text" name="end_time" id="edit_end_time" class="form-control flatpickr-datetime" readonly="readonly">
                    </div>

                    <div class="form-group">
                        <label for="edit_applied_at">Applied Month</label>
                        <input type="month" name="applied_at" id="edit_applied_at" class="form-control" required>
                    </div>

                    <button type="submit" class="btn btn-primary">Update Missing Hours</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php echo $__env->make('partials.main', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/nordpzbm/hr.nordictravels.tech/resources/views/missing-hours/manage-missing-hours.blade.php ENDPATH**/ ?>