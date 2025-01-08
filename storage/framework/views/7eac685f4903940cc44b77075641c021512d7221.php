
<?php $__env->startSection('content'); ?>
    <script src="https://rawgit.com/schmich/instascan-builds/master/instascan.min.js"></script>
    <script src="https://cdn.jsdelivr.net/gh/xcash/bootstrap-autocomplete@v2.3.7/dist/latest/bootstrap-autocomplete.min.js">
    </script>
 
    <div class="content-page">
        <!-- Start content -->
        <div class="content">

            <div class="container-fluid">
                <div class="page-title-box">

                    <div class="row align-items-center ">
                        <div class="col-md-8">
                            <div class="page-title-box">
                                <h4 class="page-title">Fetch Event</h4>
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item">
                                        <a href="javascript:void(0);">Home</a>
                                    </li>

                                    <li class="breadcrumb-item active">Fetch Event</li>
                                </ol>
                            </div>
                        </div>

                    </div>
                </div>
                <!-- end page-title -->
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
                    

                    <div class="col-lg-5">
                        <div class="card">
                            <div class="card-body">

                                <h1 class="mt-0 header-title">Fetch Events</h1>

                                <form action="<?php echo e(route('fetch.filter.events')); ?>" method="post">
                                    <?php echo csrf_field(); ?>
                                    <div class="form-group">
                                        <label>Select Date Range</label>
                                        <div>
                                            <div class="input-group">
                                                <input type="text" name="daterange" class="form-control" autocomplete="off" />
                                                <input type="hidden" name="start" />
                                                <input type="hidden" name="end" />
                                            </div>
                                        </div>
                                    </div>

                                    <button class="btn btn-primary waves-effect waves-light" type="submit">Proceed</button>


                                </form>
                            </div>
                        </div>


                    </div>

                    <div class="col-lg-5">
                        <div class="card">
                            <div class="card-body">

                                <h1 class="mt-0 header-title">Fetch Chores</h1>

                                <form autocomplete="off" action="<?php echo e(route('fetch.filter.chores')); ?>" method="post">
                                    <?php echo csrf_field(); ?>
                                    <div class="form-group">
                                        <label>Select Date Range</label>
                                        <div>
                                            <div class="input-group">
                                                <input type="text" name="daterange" class="form-control" autocomplete="off"/>
                                                <input type="hidden" name="start" />
                                                <input type="hidden" name="end" />
                                            </div>
                                        </div>
                                    </div>

                                    <button class="btn btn-primary waves-effect waves-light" type="submit">Proceed</button>


                                </form>
                            </div>
                        </div>

                    </div>

                    <!-- Add new table section -->
                    <div class="col-lg-12 mt-4">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="mt-0 header-title">Last Tours History</h4>
                                <div class="table-responsive">
                                    <table id="datatable-buttons" class="table table-striped mb-0">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Tour Name</th>
                                                <th>Guide</th>
                                                <th>Start Time</th>
                                                <th>End Time</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $__currentLoopData = $lastTours; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tour): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <tr>
                                                    <td><?php echo e(Carbon\Carbon::parse($tour->tour_date)->format('Y-m-d')); ?></td>
                                                    <td><?php echo e($tour->tour_name); ?></td>
                                                    <td><?php echo e($tour->guide); ?></td>
                                                    <td><?php echo e(Carbon\Carbon::parse($tour->start_time)); ?></td>
                                                    <td><?php echo e(Carbon\Carbon::parse($tour->end_time)); ?></td>
                                                </tr>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
            <!-- container-fluid -->

        </div>
        <!-- content -->

        <script>
            $(function() {
                $('input[name="daterange"]').daterangepicker({
                    autoUpdateInput: false,
                    autoApply: true,
                    locale: {
                        cancelLabel: 'Clear'
                    }
                });
    
                $('input[name="daterange"]').on('apply.daterangepicker', function(ev, picker) {
                    $(this).val(picker.startDate.format('DD/MM/YYYY') + ' - ' + picker.endDate.format('DD/MM/YYYY'));
                    $('input[name="start"]').val(picker.startDate.format('YYYY-MM-DD'));
                    $('input[name="end"]').val(picker.endDate.format('YYYY-MM-DD'));
                });
    
                $('input[name="daterange"]').on('cancel.daterangepicker', function(ev, picker) {
                    $(this).val('');
                    $('input[name="start"]').val('');
                    $('input[name="end"]').val('');
                });
            });
        </script>
        
    <?php $__env->stopSection(); ?>

   

<?php echo $__env->make('partials.main', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/nordpzbm/hr.nordictravels.tech/resources/views/fetch-events.blade.php ENDPATH**/ ?>