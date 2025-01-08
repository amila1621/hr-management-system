<?php $__env->startSection('content'); ?>
    <div class="content-page">
        <div class="content">
            <div class="container-fluid">
                <div class="page-title-box">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <div class="page-title-box">
                                <?php if(Auth::user()->role == "admin"): ?>
                                    <h4 class="page-title">Guide Reports</h4>
                                <?php else: ?>
                                    <h4 class="page-title">Driver Report</h4>
                                <?php endif; ?>
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item">
                                        <a href="javascript:void(0);">Home</a>
                                    </li>
                                    <?php if(Auth::user()->role == "admin"): ?>
                                        <li class="breadcrumb-item active">Guide Reports</li>
                                    <?php else: ?>
                                        <li class="breadcrumb-item active">Driver Report</li>
                                    <?php endif; ?>
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

                <div class="row">
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-body">
                                <?php if(Auth::user()->role == "admin"): ?>
                                    <h4 class="mt-0 header-title">Select a Guide and Date Range</h4>
                                <?php else: ?>
                                    <h4 class="mt-0 header-title">Select a Driver and Date Range</h4>
                                <?php endif; ?>
                                <form id="reportForm" action="<?php echo e(route('reports.getGuideWiseReport')); ?>" method="GET">
                                    <?php echo csrf_field(); ?>
                                    <div class="form-group">
                                        <?php if(Auth::user()->role == "admin"): ?>
                                            <label for="guide_id">Select Guide</label>
                                        <?php else: ?>
                                            <label for="guide_id">Select Driver</label>
                                        <?php endif; ?>
                                        <select name="guide_id" class="form-control" required>
                                            <?php $__currentLoopData = $tourGuides; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tourGuide): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <option value="<?php echo e($tourGuide->id); ?>"><?php echo e($tourGuide->name); ?></option>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label for="start_date">Start Date</label>
                                        <input type="date" name="start_date" class="form-control" required>
                                    </div>

                                    <div class="form-group">
                                        <label for="end_date">End Date</label>
                                        <input type="date" name="end_date" class="form-control" required>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary waves-effect waves-light mr-2">Fetch</button>
                                    <button type="button" id="fetchCurrentMonth" class="btn btn-secondary waves-effect waves-light">Fetch Current Month</button>
                                </form>

                                <script>
                                    document.getElementById('fetchCurrentMonth').addEventListener('click', function() {
                                        const now = new Date();
                                        const startDate = new Date(now.getFullYear(), now.getMonth(), 1);
                                        const endDate = new Date(now.getFullYear(), now.getMonth() + 1, 0);

                                        document.querySelector('input[name="start_date"]').value = startDate.toISOString().split('T')[0];
                                        document.querySelector('input[name="end_date"]').value = endDate.toISOString().split('T')[0];

                                        document.querySelector('#reportForm').submit();
                                    });

                                    document.addEventListener('DOMContentLoaded', function() {
                                        document.getElementById('fetchCurrentMonth').click();
                                    });
                                </script>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('partials.main', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/nordpzbm/hr.nordictravels.tech/resources/views/reports/guide-wise-report-create.blade.php ENDPATH**/ ?>