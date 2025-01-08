<?php $__env->startSection('content'); ?>
    <div class="content-page">
        <div class="content">
            <div class="container-fluid">
                <div class="page-title-box">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="mt-0 header-title">Working Hours for <?php echo e($selectedMonth); ?></h4>
                                <div class="col-md-3 mb-3">
                                    <div class="form-group">
                                        <label for="month">Select Month</label>
                                        <input type="month" name="month" id="month" class="form-control" value="<?php echo e($selectedMonth); ?>" required>
                                    </div>
                                </div>

                                <div class="table-responsive">
                                    <table class="table table-bordered" id="workingHoursTable">
                                        <thead>
                                            <tr>
                                                <th>Office Workers Name</th>
                                                <?php for($day = 1; $day <= $daysInMonth; $day++): ?>
                                                    <?php
                                                        $date = Carbon\Carbon::parse($selectedMonth)->day($day);
                                                        $dayOfWeek = $date->format('D');
                                                        $dateString = $date->format('Y-m-d');
                                                    ?>
                                                    <th class="<?php echo e(in_array($dateString, $holidays->toArray()) ? 'holiday-column' : ''); ?>">
                                                        <?php echo e($day); ?> (<?php echo e($dayOfWeek); ?>)
                                                    </th>
                                                <?php endfor; ?>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <!-- Reception row -->
                                            <tr>
                                                <td>Reception</td>
                                                <?php for($day = 1; $day <= $daysInMonth; $day++): ?>
                                                    <?php
                                                        $dateString = $selectedMonth . '-' . str_pad($day, 2, '0', STR_PAD_LEFT);
                                                    ?>
                                                    <td class="<?php echo e(in_array($dateString, $holidays->toArray()) ? '' : ''); ?>">
                                                        <?php echo e($receptionData[$dateString] ?? ''); ?>

                                                    </td>
                                                <?php endfor; ?>
                                            </tr>
                                            <!-- Midnight Phone row -->
                                            <?php if($displayMidnightPhone == 1): ?>
                                            <tr>
                                                <td>Midnight Phone</td>
                                                <?php for($day = 1; $day <= $daysInMonth; $day++): ?>
                                                    <?php
                                                        $dateString = $selectedMonth . '-' . str_pad($day, 2, '0', STR_PAD_LEFT);
                                                        $staffId = $midnightPhoneData[$dateString] ?? null;
                                                        $staffName = $staffMembers->where('id', $staffId)->first()->name ?? '';
                                                    ?>
                                                    <td class="<?php echo e(in_array($dateString, $holidays->toArray()) ? '' : ''); ?>">
                                                        <?php echo e($staffName); ?>

                                                    </td>
                                                <?php endfor; ?>
                                            </tr>
                                            <?php endif; ?>
                                            <!-- Staff hours rows -->
                                            <?php $__currentLoopData = $staffMembers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $staffMember): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <tr>
                                                    <td><?php echo e($staffMember->name); ?></td>
                                                    <?php for($day = 1; $day <= $daysInMonth; $day++): ?>
                                                        <?php
                                                            $dateString = $selectedMonth . '-' . str_pad($day, 2, '0', STR_PAD_LEFT);
                                                            $timeValues = $staffHours[$staffMember->id][$dateString] ?? [];
                                                        ?>
                                                        <td class="<?php echo e(in_array($dateString, $holidays->toArray()) ? '' : ''); ?>">
                                                            <?php $__currentLoopData = $timeValues; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $timeValue): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                                <div>
                                                                <?php if(is_array($timeValue)): ?>
                                                                    <?php if(isset($timeValue['type'])): ?>
                                                                        <?php echo e($timeValue['type']); ?>

                                                                    <?php elseif(isset($timeValue['start_time']) && isset($timeValue['end_time'])): ?>
                                                                        <?php echo e($timeValue['start_time']); ?> - <?php echo e($timeValue['end_time']); ?>

                                                                    <?php endif; ?>
                                                                <?php else: ?>
                                                                    <?php echo e($timeValue); ?>

                                                                <?php endif; ?>
                                                                </div>
                                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                        </td>
                                                    <?php endfor; ?>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const monthInput = document.getElementById('month');

    monthInput.addEventListener('change', function() {
        let url = new URL(window.location.href);
        url.searchParams.set('month', this.value);
        window.location.href = url.toString();
    });
});
</script>

<style>
    .content-page {
        display: flex;
        flex-direction: column;
        min-height: 100vh;
    }
    .content {
        flex: 1 0 auto;
        overflow-x: auto; /* Add horizontal scrolling if needed */
    }
    .table-responsive-container {
        max-height: calc(100vh - 350px); /* Adjust this value as needed */
        overflow: auto;
        margin-bottom: 20px; /* Add some space below the table */
    }
    .table-responsive {
        max-height: none;
        overflow: visible;
    }
    #workingHoursTable {
        min-width: 100%;
        font-size: 0.75rem;
    }
    #workingHoursTable thead th {
        position: sticky;
        top: 0;
        z-index: 1;
    }
    #workingHoursTable tbody td:first-child,
    #workingHoursTable thead th:first-child {
        position: sticky;
        left: 0;
        z-index: 2;
    }
    #workingHoursTable thead th:first-child {
        z-index: 3;
    }
    /* Add these new styles */
    body.enlarged .content-page {
        margin-left: 0;
    }
    body.enlarged .content {
        overflow-x: auto;
    }
    @media (min-width: 768px) {
        body:not(.enlarged) .content-page {
            margin-left: 240px; /* Adjust this value to match your sidebar width */
        }
    }
    #workingHoursTable input,
    #workingHoursTable .btn {
        font-size: 0.75rem;
        padding: 0.2rem 0.5rem;
    }
    .holiday-column {
        background-color: #dc3545 !important;
        color: #856404;
    }
</style> 
<?php echo $__env->make('partials.main', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/nordpzbm/hr.nordictravels.tech/resources/views/staffs/dashboard.blade.php ENDPATH**/ ?>