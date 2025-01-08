<?php $__env->startSection('content'); ?>

    <!-- Add Flatpickr CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

    <div class="content-page">
        <!-- Start content -->
        <div class="content">

            <div class="container-fluid">
                <div class="row">
                    <div class="col-12">
                        <div class="page-title-box">
                            <h4 class="page-title">Report Hours for Work</h4>
                            <ol class="breadcrumb p-0 m-0">
                                <li class="breadcrumb-item"><a href="#">Home</a></li>
                                <li class="breadcrumb-item"><a href="#">Tours</a></li>
                                <li class="breadcrumb-item active">Report Hours for Work</li>
                            </ol>
                            <div class="clearfix"></div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="card-title">Report Hours for Work</h4>
                                
                                <?php if($errors->any()): ?>
                                    <div class="alert alert-danger">
                                        <ul>
                                            <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <li><?php echo e($error); ?></li>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>

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

                                <form id="reportHoursForm" action="<?php echo e(route('guide.report-hours-store')); ?>" method="POST">
                                    <?php echo csrf_field(); ?>
                                    <div class="form-group">
                                        <label for="tourDate">Date</label>
                                        <input type="text" name="tourDate" id="tourDate" class="form-control flatpickr-date" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="tourName">Works/ Chores</label>
                                        <input type="text" name="tourName" id="tourName" class="form-control" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="startTime">Start Time</label>
                                        <input type="text" name="startTime" id="startTime" class="form-control flatpickr-datetime" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="endTime">End Time</label>
                                        <input type="text" name="endTime" id="endTime" class="form-control flatpickr-datetime" required>
                                    </div>
                                    <button type="submit" class="btn btn-primary">Submit Hours</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- container-fluid -->

        </div>
        <!-- content -->

    <!-- Add Flatpickr JS -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            initFlatpickr();
        });

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
    </script>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('partials.main', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/nordpzbm/hr.nordictravels.tech/resources/views/guides/report-hours.blade.php ENDPATH**/ ?>