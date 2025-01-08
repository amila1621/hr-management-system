<?php $__env->startSection('content'); ?>
    <div class="content-page">
        <div class="content">
            <div class="container-fluid">
                <h4 class="page-title"><?php echo e($tourGuide->name); ?> - Time Sheet (<?php echo e($startDate->format('d M Y')); ?> - <?php echo e($endDate->format('d M Y')); ?>)</h4>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="mt-0 header-title">Hours Worked</h4>

                                <div class="table-responsive">
                                    <table id="datatable-buttons" class="table table-striped table-bordered" style="border-collapse: collapse; border-spacing: 0; width: 100%">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Work Hours</th>
                                                <th>Work hours ONLY with holiday extras</th>
                                                <th>Night supplement hours(20-6)</th>
                                                <th>Night supplement ONLY with holiday extras</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                                $totalNormalHours = 0;
                                                $totalNormalNightHours = 0;
                                                $totalHolidayHours = 0;
                                                $totalHolidayNightHours = 0;
                                            ?>

                                            <?php $__currentLoopData = $groupedEventSalaries; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $date => $data): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <tr>
                                                    <td><?php echo e(\Carbon\Carbon::parse($data['date'])->format('d.m.Y')); ?></td>
                                                    <td><?php echo e($data['normal_hours']); ?></td>
                                                    <td><?php echo e($data['holiday_hours']); ?></td>
                                                    <td><?php echo e($data['normal_night_hours']); ?></td>
                                                    <td><?php echo e($data['holiday_night_hours']); ?></td>
                                                </tr>

                                                <?php
                                                    $totalNormalHours += \App\Helpers\TimeHelper::timeToDecimal($data['normal_hours']);
                                                    $totalNormalNightHours += \App\Helpers\TimeHelper::timeToDecimal($data['normal_night_hours']);
                                                    $totalHolidayHours += \App\Helpers\TimeHelper::timeToDecimal($data['holiday_hours']);
                                                    $totalHolidayNightHours += \App\Helpers\TimeHelper::timeToDecimal($data['holiday_night_hours']);
                                                ?>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </tbody>
                                        
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('scripts'); ?>
    <!-- Include your DataTables and other scripts here -->
<?php $__env->stopSection(); ?>

<?php echo $__env->make('partials.main', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/nordpzbm/hr.nordictravels.tech/resources/views/reports/guide-time-report.blade.php ENDPATH**/ ?>