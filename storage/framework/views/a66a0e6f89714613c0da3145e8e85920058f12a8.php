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
                                <h4 class="mt-0 header-title">Time Plan for <?php echo e($selectedPeriod); ?></h4>
                                <div class="row mb-3">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="month">Select Period</label>
                                            <select name="period" id="period" class="form-control" required>
                                                <?php
                                                    $startDate = \Carbon\Carbon::parse('2025-04-21');
                                                    $currentDate = \Carbon\Carbon::now();
                                                    
                                                    // Get the selected period from the request, or use current period as default
                                                    $selectedPeriodDate = isset($selectedPeriod) 
                                                        ? \Carbon\Carbon::parse($selectedPeriod) 
                                                        : $currentDate;
                                                    
                                                    // Calculate which period is selected
                                                    $selectedPeriodNumber = floor($selectedPeriodDate->diffInDays($startDate) / 21);
                                                    
                                                    // Generate periods
                                                    for ($i = 0; $i < 10; $i++) {
                                                        $periodStart = $startDate->copy()->addDays($i * 21);
                                                        $periodEnd = $periodStart->copy()->addDays(20);
                                                        $periodLabel = $periodStart->format('M d') . ' - ' . $periodEnd->format('M d, Y');
                                                        $periodValue = $periodStart->format('Y-m-d');
                                                        $selected = ($i == $selectedPeriodNumber) ? 'selected' : '';
                                                        echo "<option value='{$periodValue}' {$selected}>{$periodLabel}</option>";
                                                    }
                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                    <?php
                                    // Get current user's department
                                    $currentUserDepartment = '';
                                    if(Auth::user()->role == 'supervisor') {
                                        $supervisor = App\Models\Supervisors::where('user_id', Auth::user()->id)->first();
                                        $currentUserDepartment = $supervisor ? $supervisor->department : '';
                                    } else {
                                        $staffUser = App\Models\StaffUser::where('user_id', Auth::user()->id)->first();
                                        $currentUserDepartment = $staffUser ? $staffUser->department : '';
                                    }
                                    
                                    // Check if user belongs to HR department
                                    $isHRUser = str_contains(strtolower($currentUserDepartment), 'hr');
                                ?>

                                <?php if($isHRUser): ?>
                                <div class="col-md-3 offset-md-6">
                                    <div class="form-group">
                                        <label for="supervisor">Select Team</label>
                                        <select name="supervisor" id="supervisor" class="form-control">
                                            <option value="" selected disabled>Select Team</option>
                                            <?php
                                            $departments = App\Models\Departments::orderBy('department')->pluck('department')->toArray();
                                            ?>
                                            
                                            <?php $__currentLoopData = $departments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $dept): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <option value="<?php echo e($dept); ?>" 
                                                    <?php echo e(request('supervisor') == $dept ? 'selected' : ''); ?>>
                                                    <?php echo e($dept); ?>

                                                </option>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </select>
                                    </div>
                                </div>
                                <?php endif; ?>
                                </div>

                                <div class="table-responsive-container">
                                    <div class="table-responsive">
                                        <table class="table table-bordered" id="timePlanTable">
                                            <thead>
                                                <tr>
                                                    <th>Office Worker</th>
                                                    <?php
                                                        $startDate = isset($selectedPeriod) 
                                                            ? \Carbon\Carbon::parse($selectedPeriod) 
                                                            : \Carbon\Carbon::now()->subDays($currentDate->dayOfWeek)->addDays($currentPeriod * 21);
                                                        $endDate = $startDate->copy()->addDays(20);
                                                    ?>
                                                    <?php for($date = $startDate; $date <= $endDate; $date->addDay()): ?>
                                                        <th class="<?php echo e(in_array($date->format('Y-m-d'), $holidays->toArray()) ? 'holiday-column' : ''); ?>">
                                                            <?php echo e($date->format('d M')); ?> (<?php echo e($date->format('D')); ?>)
                                                        </th>
                                                    <?php endfor; ?>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <!-- Reception row -->
                                                <tr>
                                                    <td>Reception</td>
                                                    <?php for($date = Carbon\Carbon::parse($selectedPeriod); $date <= Carbon\Carbon::parse($selectedPeriod)->addDays(20); $date->addDay()): ?>
                                                        <td class="<?php echo e(in_array($date->format('Y-m-d'), $holidays->toArray()) ? '' : ''); ?>">
                                                            <?php echo e($receptionData[$date->format('Y-m-d')] ?? ''); ?>

                                                        </td>
                                                    <?php endfor; ?>
                                                </tr>
                                                
                                                <!-- Staff hours rows -->
                                                <?php $__currentLoopData = $staffMembers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $staffMember): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                    <tr>
                                                        <td><?php echo e($staffMember->name); ?></td>
                                                        <?php for($date = Carbon\Carbon::parse($selectedPeriod); $date <= Carbon\Carbon::parse($selectedPeriod)->addDays(20); $date->addDay()): ?>
                                                            <?php
                                                                $dateString = $date->format('Y-m-d');
                                                                $timeValues = $staffHours[$staffMember->id][$dateString] ?? [];
                                                            ?>
                                                            <td class="<?php echo e(in_array($date->format('Y-m-d'), $holidays->toArray()) ? '' : ''); ?>">
                                                                <?php $__currentLoopData = $timeValues; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $timeValue): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                                    <?php if(is_array($timeValue)): ?>
                                                                        <?php if(isset($timeValue['type']) && in_array($timeValue['type'], ['V', 'X', 'H'])): ?>
                                                                            <div><?php echo e($timeValue['type']); ?></div>
                                                                        <?php elseif(isset($timeValue['start_time']) && isset($timeValue['end_time'])): ?>
                                                                            <?php if(isset($timeValue['original_start_time']) && 
                                                                                isset($timeValue['original_end_time']) && 
                                                                                ($timeValue['original_start_time'] != $timeValue['start_time'] || 
                                                                                $timeValue['original_end_time'] != $timeValue['end_time'])): ?>
                                                                                <div class="original-time">
                                                                                    <span class="strikethrough"><?php echo e($timeValue['original_start_time']); ?> - <?php echo e($timeValue['original_end_time']); ?></span>
                                                                                </div>
                                                                            <?php endif; ?>
                                                                            <div>
                                                                                
                                                                                <?php echo e(isset($timeValue['display_prefix']) ? $timeValue['display_prefix'] : ''); ?><?php echo e($timeValue['start_time']); ?> - <?php echo e($timeValue['end_time']); ?>

                                                                            </div>
                                                                        <?php endif; ?>
                                                                    <?php elseif(is_string($timeValue)): ?>
                                                                        <?php if(in_array($timeValue, ['V', 'X', 'H'])): ?>
                                                                            <div><?php echo e($timeValue); ?></div>
                                                                        <?php elseif(strpos($timeValue, '-') !== false): ?>
                                                                            <?php
                                                                                list($start, $end) = explode('-', $timeValue);
                                                                            ?>
                                                                            <div><?php echo e($start); ?> - <?php echo e($end); ?></div>
                                                                        <?php else: ?>
                                                                            <div><?php echo e($timeValue); ?></div>
                                                                        <?php endif; ?>
                                                                    <?php endif; ?>
                                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                            </td>
                                                        <?php endfor; ?>
                                                    </tr>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                
                                                <!-- Midnight Phone row -->
                                                <?php if($displayMidnightPhone == 1): ?>
                                                <tr>
                                                    <td>Midnight Phone</td>
                                                    <?php for($date = Carbon\Carbon::parse($selectedPeriod); $date <= Carbon\Carbon::parse($selectedPeriod)->addDays(20); $date->addDay()): ?>
                                                        <?php
                                                            $dateString = $date->format('Y-m-d');
                                                            $staffId = $midnightPhoneData[$dateString] ?? null;
                                                            $staffName = $staffMembers->where('id', $staffId)->first()->name ?? '';
                                                        ?>
                                                        <td class="<?php echo e(in_array($date->format('Y-m-d'), $holidays->toArray()) ? '' : ''); ?>">
                                                            <?php echo e($staffName); ?>

                                                        </td>
                                                    <?php endfor; ?>
                                                </tr>
                                                <?php endif; ?>
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
    </div>
<?php $__env->stopSection(); ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const periodSelect = document.getElementById('period');
    const supervisorSelect = document.getElementById('supervisor');
    const tableContainer = document.querySelector('.table-responsive');

    let isDragging = false;
    let startX, startY, scrollLeft, scrollTop;

    const startDragging = (e) => {
        isDragging = true;
        tableContainer.style.cursor = 'grabbing';
        
        // Get the current scroll position and mouse position
        startX = e.pageX - tableContainer.offsetLeft;
        startY = e.pageY - tableContainer.offsetTop;
        scrollLeft = tableContainer.scrollLeft;
        scrollTop = tableContainer.scrollTop;
    };

    const stopDragging = () => {
        isDragging = false;
        tableContainer.style.cursor = 'grab';
    };

    const drag = (e) => {
        if (!isDragging) return;
        e.preventDefault();

        // Calculate the new scroll position
        const x = e.pageX - tableContainer.offsetLeft;
        const y = e.pageY - tableContainer.offsetTop;
        const moveX = x - startX;
        const moveY = y - startY;

        // Apply the scroll
        tableContainer.scrollLeft = scrollLeft - moveX;
        tableContainer.scrollTop = scrollTop - moveY;
    };

    // Add event listeners
    tableContainer.addEventListener('mousedown', startDragging);
    tableContainer.addEventListener('mousemove', drag);
    tableContainer.addEventListener('mouseup', stopDragging);
    tableContainer.addEventListener('mouseleave', stopDragging);

    // Existing event listeners
    function updatePage() {
        let url = new URL(window.location.href);
        url.searchParams.set('period', periodSelect.value);
        url.searchParams.set('supervisor', supervisorSelect.value);
        window.location.href = url.toString();
    }

    periodSelect.addEventListener('change', updatePage);
    supervisorSelect.addEventListener('change', updatePage);
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
        overflow-x: auto;
    }
    .table-responsive-container {
        max-height: calc(100vh - 350px);
        overflow: auto;
        margin-bottom: 20px;
        cursor: grab;
        user-select: none;
        width: 100%;
    }
    .table-responsive-container:active {
        cursor: grabbing;
    }
    .table-responsive {
        max-height: none;
        overflow: auto;
        min-width: 100%;
        cursor: grab;
        user-select: none;
        position: relative;
    }
    .table-responsive:active {
        cursor: grabbing;
    }
    #timePlanTable {
        min-width: max-content;
        font-size: 0.75rem;
        width: auto;
        table-layout: fixed;
    }
    #timePlanTable th,
    #timePlanTable td {
        min-width: 100px;
        white-space: nowrap;
    }
    #timePlanTable th:first-child,
    #timePlanTable td:first-child {
        min-width: 150px;
    }
    #timePlanTable thead th {
        position: sticky;
        top: 0;
        z-index: 1;
    }
    #timePlanTable tbody td:first-child,
    #timePlanTable thead th:first-child {
        position: sticky;
        left: 0;
        z-index: 2;
        background: #424c5a !important;
    }
    
    #timePlanTable tbody td div{
        width:80px;
        text-align:center;
    }
    #timePlanTable thead th:first-child {
        z-index: 3;
    }
    body.enlarged .content-page {
        margin-left: 0;
    }
    body.enlarged .content {
        overflow-x: auto;
    }
    @media (min-width: 768px) {
        body:not(.enlarged) .content-page {
            margin-left: 240px;
        }
    }
    #timePlanTable input,
    #timePlanTable .btn {
        font-size: 0.75rem;
        padding: 0.2rem 0.5rem;
    }
    .holiday-column {
        background-color: #dc3545 !important;
        color: #856404;
    }
    #timePlanTable tbody td.holiday-column {
        background-color: #dc3545 !important;
    }
    #timePlanTable thead th.holiday-column {
        background-color: #dc3545 !important;
    }
    #timePlanTable td span.sick-leave {
        color: #ffc107;
        font-weight: bold;
    }
    .original-time {
        font-size: 0.75rem;
        line-height: 1;
        margin-bottom: 2px;
        color: #dc3545;
    }
    .strikethrough {
        text-decoration: line-through;
        font-style: italic;
    }
</style>

<?php echo $__env->make('partials.main', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/nordpzbm/hr.nordictravels.tech/resources/views/supervisor/view-time-plan.blade.php ENDPATH**/ ?>