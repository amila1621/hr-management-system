
<?php
if (!function_exists('formatTime')) {
    function formatTime($hours) {
        $wholeHours = floor($hours);
        $fractionalHours = $hours - $wholeHours;
        $minutes = round($fractionalHours * 100);
        
        $totalMinutes = $wholeHours * 60 + $minutes;
        $finalHours = floor($totalMinutes / 60);
        $finalMinutes = $totalMinutes % 60;
        
        if ($finalMinutes == 0) {
            return $finalHours;
        } else {
            return sprintf("%d:%02d", $finalHours, $finalMinutes);
        }
    }
}
?>
<?php $__env->startSection('content'); ?>
    <div class="content-page">
        <div class="content">
            <div class="container-fluid">
                <h4 class="page-title">Manually Added Entries</h4>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="mt-0 header-title">Hours Worked</h4>

                                <div class="table-responsive">
                                    <table id="datatable-buttons" class="table table-striped table-bordered" style="border-collapse: collapse; border-spacing: 0; width: 100%">
                                        <thead>
                                            <tr>
                                                <th>Tour Name</th>
                                                <th>Guide</th>
                                                <th>Start Time</th>
                                                <th>End Time</th>
                                                <th>Work Hours</th>
                                                <th>Work hours ONLY with holiday extras</th>
                                                <th>Night supplement hours(20-6)</th>
                                                <th>Night supplement ONLY with holiday extras</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        

                                            <?php $__currentLoopData = $entries; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $entry): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <tr>
                                                    <td><?php echo e($entry->event->name); ?></td>
                                                    <td><?php echo e($entry->tourGuide->name); ?></td>
                                                    <td><?php echo e($entry->guide_start_time ? $entry->guide_start_time->format('d.m.Y H:i') : 'N/A'); ?></td>
                                                    <td><?php echo e($entry->guide_end_time ? $entry->guide_end_time->format('d.m.Y H:i') : 'N/A'); ?></td>
                                                    <td><?php echo e(formatTime($entry->normal_hours)); ?></td>
                                                    <td><?php echo e(formatTime($entry->holiday_hours)); ?></td>
                                                    <td><?php echo e(formatTime($entry->normal_night_hours)); ?></td>
                                                    <td><?php echo e(formatTime($entry->holiday_night_hours)); ?></td>
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
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('scripts'); ?>
    <!-- Include your DataTables and other scripts here -->
<?php $__env->stopSection(); ?>

<?php echo $__env->make('partials.main', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/nordpzbm/hr.nordictravels.tech/resources/views/reports/manually-added-entries.blade.php ENDPATH**/ ?>