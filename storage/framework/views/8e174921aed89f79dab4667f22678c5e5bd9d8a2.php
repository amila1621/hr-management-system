

<?php $__env->startSection('content'); ?>
    <div class="content-page">
        <div class="content">
            <div class="container-fluid">
                <div class="page-title-box">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <div class="page-title-box">
                                <h4 class="page-title">Monthly Reports</h4>
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item">
                                        <a href="javascript:void(0);">Home</a>
                                    </li>
                                    <li class="breadcrumb-item active">Monthly Reports</li>
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
                                <h4 class="mt-0 header-title">Select a Month</h4>
                                <form action="<?php echo e(route('reports.getMonthlyReport')); ?>" method="GET">
                                    <?php echo csrf_field(); ?>
                                    <div class="form-group">
                                        <label for="month">Month and Year</label>
                                        <input type="month" value="<?php echo e(date('Y-m')); ?>" name="month" class="form-control" required onclick="this.showPicker()">
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary waves-effect waves-light">Fetch</button>
                                    <button type="button" onclick="fetchLastMonth()" class="btn btn-secondary waves-effect waves-light ml-2">Fetch Last Month</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    function fetchLastMonth() {
        const monthInput = document.querySelector('input[type="month"]');
        const currentDate = new Date(monthInput.value);
        currentDate.setMonth(currentDate.getMonth() - 1);
        monthInput.value = currentDate.toISOString().slice(0, 7);
        monthInput.form.submit();
    }
    </script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('partials.main', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/nordpzbm/hr.nordictravels.tech/resources/views/reports/monthly-report-create.blade.php ENDPATH**/ ?>