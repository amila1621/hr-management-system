
<?php $__env->startSection('content'); ?>
<div class="content-page">
    <div class="content">
        <div class="container-fluid">
            
            <div class="page-title-box">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <div class="page-title-box">
                            <h4 class="page-title">Operations Check Sheet</h4>
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="javascript:void(0);">Home</a></li>
                                <li class="breadcrumb-item active">Check Sheet</li>
                            </ol>
                        </div>
                    </div>
                    <div class="col-md-4 text-right">
                        <div class="date-filter">
                            <h5><?php echo e(now()->format('l, F j, Y')); ?></h5>
                        </div>
                    </div>
                </div>
            </div>

            
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="operationsTable">
                                    
                                    <thead>
                                        <tr class="">
                                            <th width="8%">Duration</th>
                                            <th width="30%">Tour</th>
                                            <th width="10%">Car</th>
                                            <th width="8%">Pick Up Time</th>
                                            <th width="15%">Pick Up place</th>
                                            <th width="8%">Pax</th>
                                            <th width="10%">Guide</th>
                                            <th width="8%" class="bg-warning">Available time</th>
                                            <th>Remark</th>
                                        </tr>
                                    </thead>
                                    
                                    <tbody>
                                        <?php
                                            $currentEventId = null;
                                            $rowCount = 0;
                                            $groupedTours = $tours->groupBy('event_id');
                                        ?>

                                        <?php $__currentLoopData = $groupedTours; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $eventId => $tourGroup): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <?php $__currentLoopData = $tourGroup; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $tour): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <tr class="tour-start">
                                                    <?php if($index === 0): ?>
                                                        <td rowspan="<?php echo e(count($tourGroup)); ?>" class="tour-duration">
                                                            <?php
                                                                $formattedDuration = '#N/A';
                                                                if (is_numeric($tour->duration)) {
                                                                    $hours = floor($tour->duration / 60);
                                                                    $minutes = $tour->duration % 60;
                                                                    $formattedDuration = sprintf("%02d:%02d", $hours, $minutes);
                                                                }
                                                            ?>
                                                            <?php echo e($formattedDuration); ?>

                                                        </td>
                                                        <td rowspan="<?php echo e(count($tourGroup)); ?>" class="tour-name font-weight-bold">
                                                            <?php echo e($tour->tour_name); ?>

                                                        </td>
                                                    <?php endif; ?>
                                                    <td>
                                                        <select class="form-control" name="vehicle" id="vehicle_<?php echo e($tour->id); ?>">
                                                            <option value="0"><?php echo e($tour->vehicle); ?></option>
                                                            <?php if(isset($vehicles)): ?>
                                                                <?php $__currentLoopData = $vehicles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $vehicle): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                                    <option value="<?php echo e($vehicle->id); ?>"
                                                                        <?php echo e($tour->vehicle_id == $vehicle->id ? 'selected' : ''); ?>>
                                                                        <?php echo e($vehicle->name); ?>

                                                                    </option>
                                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                            <?php endif; ?>
                                                        </select>
                                                    </td>
                                                    <td><?php echo e($tour->pickup_time); ?></td>
                                                    <td><?php echo e($tour->pickup_location); ?></td>
                                                    <td class="text-center"><?php echo e($tour->pax); ?></td>
                                                    <td>
                                                        <select class="form-control" name="guide" id="guide_<?php echo e($tour->id); ?>">
                                                            <option value="0"><?php echo e($tour->guide); ?></option>
                                                            <?php if(isset($guides)): ?>
                                                                <?php $__currentLoopData = $guides; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $guide): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                                    <option value="<?php echo e($guide->id); ?>"
                                                                        <?php echo e($tour->guide_id == $guide->id ? 'selected' : ''); ?>>
                                                                        <?php echo e($guide->name); ?>

                                                                    </option>
                                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                            <?php endif; ?>
                                                        </select>
                                                    </td>
                                                    <td class="bg-warning"><?php echo e($tour->available ?: '#N/A'); ?></td>
                                                    <td><?php echo e($tour->remark); ?></td>
                                                </tr>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
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



<?php $__env->startPush('scripts'); ?>
<script>
$(document).ready(function() {
    // Initialize DataTable with custom settings
    var table = $('#operationsTable').DataTable({
        ordering: false,
        pageLength: 50,
        searching: true,
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
             '<"row"<"col-sm-12"tr>>' +
             '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        language: {
            search: "Search tours:",
            lengthMenu: "Show _MENU_ tours per page"
        },
        drawCallback: function(settings) {
            // Add border to last row of each tour group
            $('.tour-start').prev('tr').not('.tour-start').css('border-bottom', '2px solid #333');
            
            // Ensure proper alignment of rowspan cells
            $('.tour-duration, .tour-name').css('vertical-align', 'middle');
        }
    });
    
    // Additional styling for mobile responsiveness
    $(window).resize(function() {
        $('.table-responsive').css('max-height', (window.innerHeight - 300) + 'px');
    }).resize();
});
</script>
<?php $__env->stopPush(); ?>
<?php echo $__env->make('partials.main', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/nordpzbm/hr.nordictravels.tech/resources/views/operations/check-sheet.blade.php ENDPATH**/ ?>