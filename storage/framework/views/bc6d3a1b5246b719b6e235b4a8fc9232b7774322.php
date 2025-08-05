

<?php $__env->startSection('content'); ?>
    <div class="content-page">
        <div class="content">
            <div class="container-fluid">
                <!-- Enhanced Header -->
                <div class="page-title-box mb-4">
                    <div class="row align-items-center">
                      
                        <div class="col-md-12">
                            <!-- Month Selector moved to header -->
                            <div>
                                <form id="monthSelectForm" action="<?php echo e(route('staff.schedule')); ?>" method="GET">
                                    <!-- Preserve any existing query parameters -->
                                    <?php $__currentLoopData = request()->except(['month']); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <input type="hidden" name="<?php echo e($key); ?>" value="<?php echo e($value); ?>">
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    <input type="month" name="month" id="month" class="form-control w-100 d-inline-block" 
                                        value="<?php echo e($selectedMonth); ?>" required>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Department Tabs - New Section -->
                <?php if(count($departments) > 1): ?>
                <div class="row mb-3">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <ul class="nav nav-pills nav-justified" role="tablist">
                                    <?php $__currentLoopData = $departments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $department): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <li class="nav-item waves-effect waves-light">
                                            <a class="nav-link <?php echo e($selectedDepartment === $department ? 'active' : ''); ?>" 
                                               href="<?php echo e(route('staff.schedule', array_merge(request()->query(), ['department' => $department]))); ?>"
                                               role="tab">
                                                <span class="d-block d-sm-none"><i class="fas fa-users"></i></span>
                                                <span class="d-none d-sm-block"><?php echo e($department); ?></span>
                                            </a>
                                        </li>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Main Table Card -->
                <div class="row">
                    <div class="col-12">
                        <div class="card shadow-sm">
                            <div class="card-header">
                                <div class="row align-items-center">
                                    <div class="col-md-6">
                                        <h5 class="card-title mb-0">
                                            <i class="fas fa-table mr-2"></i>
                                            Monthly Schedule Overview
                                        </h5>
                                    </div>
                                    <div class="col-md-6 text-right d-none d-md-block">
                                        <!-- Legend - Hidden on mobile -->
                                        <div class="legend-container">
                                            <span class="legend-item">
                                                <span class="legend-color holiday-legend"></span>
                                                <small>Holiday</small>
                                            </span>
                                            <span class="legend-item">
                                                <span class="legend-color day-off-legend"></span>
                                                <small>Day Off</small>
                                            </span>
                                            <span class="legend-item">
                                                <span class="legend-color work-hours-legend"></span>
                                                <small>Work Hours</small>
                                            </span>
                                            <span class="legend-item">
                                                <span class="legend-color reception-legend"></span>
                                                <small>Reception</small>
                                            </span>
                                            <span class="legend-item">
                                                <span class="legend-color on-call-legend"></span>
                                                <small>On Call</small>
                                            </span>
                                            <span class="legend-item">
                                                <span class="legend-color sick-leave-legend"></span>
                                                <small>Sick Leave</small>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <!-- Mobile Legend -->
                                <div class="row d-md-none mt-2">
                                    <div class="col-12">
                                        <div class="legend-container-mobile">
                                            <span class="legend-item">
                                                <span class="legend-color holiday-legend"></span>
                                                <small>Holiday</small>
                                            </span>
                                            <span class="legend-item">
                                                <span class="legend-color day-off-legend"></span>
                                                <small>Day Off</small>
                                            </span>
                                            <span class="legend-item">
                                                <span class="legend-color work-hours-legend"></span>
                                                <small>Work</small>
                                            </span>
                                            <span class="legend-item">
                                                <span class="legend-color reception-legend"></span>
                                                <small>Reception</small>
                                            </span>
                                            <span class="legend-item">
                                                <span class="legend-color on-call-legend"></span>
                                                <small>On Call</small>
                                            </span>
                                            <span class="legend-item">
                                                <span class="legend-color sick-leave-legend"></span>
                                                <small>Sick</small>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive-modern">
                                    <table class="table table-hover mb-0" id="workingHoursTable">
                                        <thead class="thead-dark">
                                            <tr>
                                                <th class="sticky-column staff-column">
                                                    <i class="fas fa-user mr-2 d-none d-md-inline"></i>
                                                    <span class="d-none d-md-inline">Office Workers</span>
                                                    <span class="d-md-none">Staff</span>
                                                </th>
                                                <?php for($day = 1; $day <= $daysInMonth; $day++): ?>
                                                    <?php
                                                        $date = Carbon\Carbon::parse($selectedMonth)->day($day);
                                                        $dayOfWeek = $date->format('D');
                                                        $dateString = $date->format('Y-m-d');
                                                        $isHoliday = in_array($dateString, $holidays->toArray());
                                                        $isWeekend = in_array($dayOfWeek, ['Sat', 'Sun']);
                                                    ?>
                                                    <th class="day-column <?php echo e($isHoliday ? 'holiday-column' : ($isWeekend ? 'weekend-column' : '')); ?>" 
                                                        data-date="<?php echo e($dateString); ?>">
                                                        <div class="day-header">
                                                            <div class="day-number"><?php echo e($day); ?></div>
                                                            <div class="day-name d-none d-sm-block"><?php echo e($dayOfWeek); ?></div>
                                                        </div>
                                                    </th>
                                                <?php endfor; ?>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <!-- Reception row -->
                                            <tr class="special-row reception-row">
                                                <td class="sticky-column staff-name">
                                                    <div class="staff-info">
                                                        <div class="staff-avatar bg-primary">
                                                            <i class="fas fa-phone"></i>
                                                        </div>
                                                        <div class="staff-details">
                                                            <div class="name">Reception</div>
                                                            <div class="role d-none d-md-block">Notes</div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <?php for($day = 1; $day <= $daysInMonth; $day++): ?>
                                                    <?php
                                                        $dateString = $selectedMonth . '-' . str_pad($day, 2, '0', STR_PAD_LEFT);
                                                        $isHoliday = in_array($dateString, $holidays->toArray());
                                                    ?>
                                                    <td class="time-cell <?php echo e($isHoliday ? 'holiday-cell' : ''); ?>">
                                                        <div class="time-content reception-note">
                                                            <?php echo e($receptionData[$dateString] ?? ''); ?>

                                                        </div>
                                                    </td>
                                                <?php endfor; ?>
                                            </tr>

                                            <!-- Midnight Phone row -->
                                            <?php if($displayMidnightPhone == 1): ?>
                                            <tr class="special-row midnight-row">
                                                <td class="sticky-column staff-name">
                                                    <div class="staff-info">
                                                        <div class="staff-avatar bg-info">
                                                            <i class="fas fa-moon"></i>
                                                        </div>
                                                        <div class="staff-details">
                                                            <div class="name">
                                                                <span class="d-none d-md-inline">Midnight Phone</span>
                                                                <span class="d-md-none">Night</span>
                                                            </div>
                                                            <div class="role d-none d-md-block">On-call</div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <?php for($day = 1; $day <= $daysInMonth; $day++): ?>
                                                    <?php
                                                        $dateString = $selectedMonth . '-' . str_pad($day, 2, '0', STR_PAD_LEFT);
                                                        $staffId = $midnightPhoneData[$dateString] ?? null;
                                                        $staffName = $staffMembers->where('id', $staffId)->first()->name ?? '';
                                                        $isHoliday = in_array($dateString, $holidays->toArray());
                                                    ?>
                                                    <td class="time-cell <?php echo e($isHoliday ? 'holiday-cell' : ''); ?>">
                                                        <div class="time-content midnight-assignment">
                                                            <?php if($staffName): ?>
                                                                <span class="assigned-staff">
                                                                    <span class="d-none d-md-inline"><?php echo e($staffName); ?></span>
                                                                    <span class="d-md-none"><?php echo e(substr($staffName, 0, 1)); ?></span>
                                                                </span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                <?php endfor; ?>
                                            </tr>
                                            <?php endif; ?>

                                            <!-- Staff hours rows -->
                                            <?php $__currentLoopData = $staffMembers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $staffMember): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <tr class="staff-row" data-staff-id="<?php echo e($staffMember->id); ?>">
                                                    <td class="sticky-column staff-name">
                                                        <div class="staff-info">
                                                            <div class="staff-avatar bg-secondary">
                                                                <?php echo e(substr($staffMember->name, 0, 2)); ?>

                                                            </div>
                                                            <div class="staff-details">
                                                                <div class="name">
                                                                    <span class="d-none d-md-inline"><?php echo e($staffMember->name); ?></span>
                                                                    <span class="d-md-none"><?php echo e(substr($staffMember->name, 0, 10)); ?><?php echo e(strlen($staffMember->name) > 10 ? '...' : ''); ?></span>
                                                                </div>
                                                                <div class="role d-none d-md-block"><?php echo e($staffMember->department ?? 'Staff'); ?></div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <?php for($day = 1; $day <= $daysInMonth; $day++): ?>
                                                        <?php
                                                            $dateString = $selectedMonth . '-' . str_pad($day, 2, '0', STR_PAD_LEFT);
                                                            $timeValues = $staffHours[$staffMember->id][$dateString] ?? [];
                                                            $isHoliday = in_array($dateString, $holidays->toArray());
                                                        ?>
                                                        <td class="time-cell <?php echo e($isHoliday ? 'holiday-cell' : ''); ?>" 
                                                            data-staff="<?php echo e($staffMember->id); ?>" 
                                                            data-date="<?php echo e($dateString); ?>">
                                                            <div class="time-content">
                                                                <?php $__empty_1 = true; $__currentLoopData = $timeValues; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $timeValue): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                                                    <?php if(is_array($timeValue)): ?>
                                                                        <?php if(isset($timeValue['type']) && in_array($timeValue['type'], ['V', 'X', 'H'])): ?>
                                                                            <div class="time-entry day-off-entry <?php echo e(strtolower($timeValue['type'])); ?>-type">
                                                                                <span class="day-off-badge"><?php echo e($timeValue['type']); ?></span>
                                                                            </div>
                                                                        <?php elseif(isset($timeValue['type']) && $timeValue['type'] === 'SL'): ?>
                                                                            <div class="time-entry sick-leave-entry">
                                                                                <i class="fas fa-user-injured mr-1 d-none d-md-inline"></i>
                                                                                <span class="time-text">SL</span>
                                                                                <?php if(isset($timeValue['start_time']) && isset($timeValue['end_time'])): ?>
                                                                                    <div class="time-details d-none d-md-block"><?php echo e($timeValue['start_time']); ?> - <?php echo e($timeValue['end_time']); ?></div>
                                                                                <?php endif; ?>
                                                                            </div>
                                                                        <?php elseif(isset($timeValue['type']) && $timeValue['type'] === 'reception'): ?>
                                                                            <div class="time-entry reception-entry">
                                                                                <i class="fas fa-desktop mr-1 d-none d-md-inline"></i>
                                                                                <span class="time-text">
                                                                                    <span class="d-none d-md-inline">Reception</span>
                                                                                    <span class="d-md-none">REC</span>
                                                                                </span>
                                                                                <?php if(isset($timeValue['start_time']) && isset($timeValue['end_time'])): ?>
                                                                                    <div class="time-details">
                                                                                        <span class="d-none d-md-inline"><?php echo e($timeValue['start_time']); ?> - <?php echo e($timeValue['end_time']); ?></span>
                                                                                        <span class="d-md-none">
                                                                                            <?php echo e(substr($timeValue['start_time'], 0, 5)); ?><br><?php echo e(substr($timeValue['end_time'], 0, 5)); ?>

                                                                                        </span>
                                                                                    </div>
                                                                                <?php endif; ?>
                                                                            </div>
                                                                        <?php elseif(isset($timeValue['type']) && $timeValue['type'] === 'on_call'): ?>
                                                                            <div class="time-entry on-call-entry">
                                                                                <i class="fas fa-phone mr-1 d-none d-md-inline"></i>
                                                                                <span class="time-text">
                                                                                    <span class="d-none d-md-inline">On Call</span>
                                                                                    <span class="d-md-none">OC</span>
                                                                                </span>
                                                                                <?php if(isset($timeValue['start_time']) && isset($timeValue['end_time'])): ?>
                                                                                    <div class="time-details">
                                                                                        <span class="d-none d-md-inline"><?php echo e($timeValue['start_time']); ?> - <?php echo e($timeValue['end_time']); ?></span>
                                                                                        <span class="d-md-none">
                                                                                            <?php echo e(substr($timeValue['start_time'], 0, 5)); ?><br><?php echo e(substr($timeValue['end_time'], 0, 5)); ?>

                                                                                        </span>
                                                                                    </div>
                                                                                <?php endif; ?>
                                                                            </div>
                                                                        <?php elseif(isset($timeValue['start_time']) && isset($timeValue['end_time'])): ?>
                                                                            <div class="time-entry work-entry">
                                                                                <?php if(isset($timeValue['original_start_time']) && 
                                                                                    isset($timeValue['original_end_time']) && 
                                                                                    ($timeValue['original_start_time'] != $timeValue['start_time'] || 
                                                                                    $timeValue['original_end_time'] != $timeValue['end_time'])): ?>
                                                                                    <div class="original-time d-none d-md-block">
                                                                                        <span class="strikethrough"><?php echo e($timeValue['original_start_time']); ?> - <?php echo e($timeValue['original_end_time']); ?></span>
                                                                                    </div>
                                                                                <?php endif; ?>
                                                                                <div class="current-time">
                                                                                    <i class="fas fa-clock mr-1 d-none d-md-inline"></i>
                                                                                    <span class="time-text">
                                                                                        <span class="d-none d-md-inline"><?php echo e($timeValue['start_time']); ?> - <?php echo e($timeValue['end_time']); ?></span>
                                                                                        <span class="d-md-none">
                                                                                            <?php
                                                                                                // Better mobile time formatting
                                                                                                $startHour = substr($timeValue['start_time'], 0, 5); // Get HH:MM instead of just HH
                                                                                                $endHour = substr($timeValue['end_time'], 0, 5);     // Get HH:MM instead of just HH
                                                                                            ?>
                                                                                            <?php echo e($startHour); ?><br><?php echo e($endHour); ?>

                                                                                        </span>
                                                                                    </span>
                                                                                </div>
                                                                            </div>
                                                                        <?php endif; ?>
                                                                    <?php elseif(is_string($timeValue)): ?>
                                                                        <?php if(in_array($timeValue, ['V', 'X', 'H'])): ?>
                                                                            <div class="time-entry day-off-entry <?php echo e(strtolower($timeValue)); ?>-type">
                                                                                <span class="day-off-badge"><?php echo e($timeValue); ?></span>
                                                                            </div>
                                                                        <?php elseif($timeValue === 'SL'): ?>
                                                                            <div class="time-entry sick-leave-entry">
                                                                                <i class="fas fa-user-injured mr-1 d-none d-md-inline"></i>
                                                                                <span class="time-text">SL</span>
                                                                            </div>
                                                                        <?php elseif(strpos($timeValue, '-') !== false): ?>
                                                                            <?php
                                                                                list($start, $end) = explode('-', $timeValue);
                                                                            ?>
                                                                            <div class="time-entry work-entry">
                                                                                <i class="fas fa-clock mr-1 d-none d-md-inline"></i>
                                                                                <span class="time-text">
                                                                                    <span class="d-none d-md-inline"><?php echo e(trim($start)); ?> - <?php echo e(trim($end)); ?></span>
                                                                                    <span class="d-md-none">
                                                                                        <?php
                                                                                            // Better mobile time formatting for string values
                                                                                            $startFormatted = substr(trim($start), 0, 5); // Get HH:MM
                                                                                            $endFormatted = substr(trim($end), 0, 5);     // Get HH:MM
                                                                                        ?>
                                                                                        <?php echo e($startFormatted); ?><br><?php echo e($endFormatted); ?>

                                                                                    </span>
                                                                                </span>
                                                                            </div>
                                                                        <?php else: ?>
                                                                            <div class="time-entry other-entry">
                                                                                <span class="time-text"><?php echo e($timeValue); ?></span>
                                                                            </div>
                                                                        <?php endif; ?>
                                                                    <?php endif; ?>
                                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                                                    <div class="empty-cell">
                                                                        <span class="no-data">-</span>
                                                                    </div>
                                                                <?php endif; ?>
                                                            </div>
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

    // Add smooth hover effects
    const timeCells = document.querySelectorAll('.time-cell');
    timeCells.forEach(cell => {
        cell.addEventListener('mouseenter', function() {
            const staffId = this.dataset.staff;
            const date = this.dataset.date;
            
            // Highlight row and column
            document.querySelectorAll(`[data-staff="${staffId}"]`).forEach(el => el.classList.add('row-highlight'));
            document.querySelectorAll(`[data-date="${date}"]`).forEach(el => el.classList.add('col-highlight'));
        });

        cell.addEventListener('mouseleave', function() {
            document.querySelectorAll('.row-highlight').forEach(el => el.classList.remove('row-highlight'));
            document.querySelectorAll('.col-highlight').forEach(el => el.classList.remove('col-highlight'));
        });
    });




    // Mouse drag for horizontal scrolling (desktop only)
    let isDown = false;
    let scrollLeft;
    const tableContainer = document.querySelector('.table-responsive-modern');

    // Only apply mouse handlers for non-touch devices
    if (window.matchMedia("(pointer: fine)").matches) {
        tableContainer.addEventListener('mousedown', (e) => {
            isDown = true;
            startX = e.pageX - tableContainer.offsetLeft;
            scrollLeft = tableContainer.scrollLeft;
        });

        tableContainer.addEventListener('mouseleave', () => {
            isDown = false;
        });

        tableContainer.addEventListener('mouseup', () => {
            isDown = false;
        });

        tableContainer.addEventListener('mousemove', (e) => {
            if (!isDown) return;
            e.preventDefault();
            const x = e.pageX - tableContainer.offsetLeft;
            const walk = (x - startX) * 2;
            tableContainer.scrollLeft = scrollLeft - walk;
        });
    }

    // REMOVE ALL CUSTOM TOUCH HANDLERS FOR iOS COMPATIBILITY
    // This allows iOS to use its native touch scrolling

    // iOS specific initialization to help with scrolling
    if (/iPhone|iPad|iPod/.test(navigator.userAgent)) {
        // Force iOS to recognize the container as scrollable
        tableContainer.style.webkitOverflowScrolling = 'touch';
        
        // Slight delay to ensure iOS has fully rendered the page
        setTimeout(() => {
            // Small "nudge" to help iOS recognize scrollable areas
            if (tableContainer.scrollTop === 0) {
                tableContainer.scrollTop = 1;
                setTimeout(() => tableContainer.scrollTop = 0, 100);
            }
        }, 300);
    }

    // Handle department tab changes
    const departmentTabs = document.querySelectorAll('.nav-link[role="tab"]');
    
    departmentTabs.forEach(tab => {
        tab.addEventListener('click', function(e) {
            e.preventDefault();
            const url = this.getAttribute('href');
            window.location.href = url;
        });
    });
    
    // Handle month form submission
    const monthForm = document.querySelector('form');
    if (monthForm) {
        monthForm.addEventListener('submit', function(e) {
            // Form will submit normally with the hidden department input
        });
    }
    
    // Add code for detecting touch direction and handling scrolling
    
    tableContainer.addEventListener('touchstart', function(e) {
        startY = e.touches[0].clientY;
        startX = e.touches[0].clientX;
    });
    
    tableContainer.addEventListener('touchmove', function(e) {
        if (!startY || !startX) return;
        
        const yDiff = startY - e.touches[0].clientY;
        const xDiff = startX - e.touches[0].clientX;
        
        // If vertical scrolling is dominant, don't prevent default
        if (Math.abs(yDiff) > Math.abs(xDiff)) {
            // Allow natural vertical scrolling
            return;
        } else {
            // For horizontal scrolling, prevent the default to avoid page scrolling
            e.preventDefault();
            tableContainer.scrollLeft += xDiff;
        }
        
        startY = e.touches[0].clientY;
        startX = e.touches[0].clientX;
    });
    
    // Initialize universal drag scrolling
    
    // Mouse drag for all devices
    let isDragging = false;
    let startX, startY, scrollLeftStart, scrollTopStart;
    
    // Mouse events (desktop)
    tableContainer.addEventListener('mousedown', function(e) {
        isDragging = true;
        tableContainer.classList.add('grabbing');
        startX = e.pageX - tableContainer.offsetLeft;
        startY = e.pageY - tableContainer.offsetTop;
        scrollLeftStart = tableContainer.scrollLeft;
        scrollTopStart = tableContainer.scrollTop;
    });
    
    document.addEventListener('mousemove', function(e) {
        if (!isDragging) return;
        e.preventDefault();
        const x = e.pageX - tableContainer.offsetLeft;
        const y = e.pageY - tableContainer.offsetTop;
        const walkX = (x - startX) * 1.5; // Adjust speed as needed
        const walkY = (y - startY) * 1.5;
        tableContainer.scrollLeft = scrollLeftStart - walkX;
        tableContainer.scrollTop = scrollTopStart - walkY;
    });
    
    document.addEventListener('mouseup', function() {
        isDragging = false;
        tableContainer.classList.remove('grabbing');
    });
    
    // Touch events (mobile)
    let lastX, lastY;
    let momentumId;
    
    tableContainer.addEventListener('touchstart', function(e) {
        if (momentumId) {
            cancelAnimationFrame(momentumId);
            momentumId = null;
        }
        
        isDragging = true;
        startX = e.touches[0].pageX - tableContainer.offsetLeft;
        startY = e.touches[0].pageY - tableContainer.offsetTop;
        lastX = startX;
        lastY = startY;
        scrollLeftStart = tableContainer.scrollLeft;
        scrollTopStart = tableContainer.scrollTop;
        
        // Prevent default to avoid iOS Safari overscroll behavior
        e.preventDefault();
    }, { passive: false });
    
    tableContainer.addEventListener('touchmove', function(e) {
        if (!isDragging) return;
        
        const x = e.touches[0].pageX - tableContainer.offsetLeft;
        const y = e.touches[0].pageY - tableContainer.offsetTop;
        
        const walkX = (x - lastX) * 1.5;
        const walkY = (y - lastY) * 1.5;
        
        tableContainer.scrollLeft -= walkX;
        tableContainer.scrollTop -= walkY;
        
        lastX = x;
        lastY = y;
        
        // Prevent default to avoid page scrolling
        e.preventDefault();
    }, { passive: false });
    
    tableContainer.addEventListener('touchend', function() {
        isDragging = false;
    });
});

</script>

<style>
/* Enhanced modern styling - removed unwanted backgrounds */
.content-page {
    /* Removed background-color: #f8f9fa; */
    min-height: 100vh;
    padding-bottom: 60px; /* Add padding to prevent footer overlap */
}

.page-title-box {
    /* Removed gradient background */
    border-radius: 10px;
    padding: 20px;
    margin-bottom: 20px;
    border: 1px solid #e9ecef;
}

.page-title-box .page-title {
    margin-bottom: 0;
    color: white !important; /* Changed from #2c3e50 to white */
}

.page-title-box .text-muted {
    color: rgba(255,255,255,0.8) !important; /* Changed from #6c757d to semi-transparent white */
}

/* Compact Month selector in header */
.month-selector-header {
    text-align: right;
    max-width: 180px;
    float: right;
}

.month-selector-header .form-label {
    font-size: 0.7rem;
    font-weight: 500;
    margin-bottom: 2px;
    display: inline-block;
    color: white !important;
    margin-right: 5px;
}

.month-selector-header .form-control {
    height: 30px;
    padding: 0.2rem 0.5rem;
    font-size: 0.75rem;
    border: 1px solid rgba(255,255,255,0.3);
    color: #495057;
    background: rgba(255,255,255,0.9);
    display: inline-block;
    width: auto;
}

.month-selector-header .form-control:focus {
    background: white;
    border-color: #80bdff;
    box-shadow: 0 0 0 0.1rem rgba(0,123,255,0.2);
}

/* Card styling - transparent backgrounds */
.card {
    border: 1px solid #e9ecef;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
    background: transparent;
}

.card-header {
    /* Removed bg-light background */
    border-bottom: 1px solid #e9ecef;
    padding: 1rem;
}

/* Table container - add bottom margin to prevent footer overlap */
.table-responsive-modern {
    overflow: auto !important;
    cursor: grab;
    -webkit-overflow-scrolling: touch;
    touch-action: pan-x pan-y;
    max-height: none !important;
    height: auto !important;
    width: 100%;
    margin-bottom: 100px !important;
    position: relative;
    background-color: transparent;
    border: 1px solid #e9ecef;
    border-radius: 8px;
}

.table-responsive-modern.grabbing {
    cursor: grabbing;
}

/* Remove height constraints */
#workingHoursTable {
    width: max-content;
    min-width: 100%;
}

/* Ensure sticky headers work universally */
.thead-dark th {
    position: -webkit-sticky;
    position: sticky;
    top: 0;
    z-index: 10;
}

.sticky-column {
    position: -webkit-sticky;
    position: sticky;
    left: 0;
    z-index: 5;
}

.thead-dark .sticky-column {
    z-index: 15;
    background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
}

/* Remove problematic iOS-specific overrides */
@supports (-webkit-touch-callout: none) {
    .table-responsive-modern {
        -webkit-overflow-scrolling: touch;
        overflow: auto !important;
    }
}

/* Make the container taller on mobile */
@media (max-width: 768px) {
    .table-responsive-modern {
        height: 60vh !important;
        max-height: 60vh !important;
    }
}

/* Ensure content doesn't hide under other elements */
.content-page {
    padding-bottom: 80px;
    overflow: visible;
}

/* Table styling */
#workingHoursTable {
    margin-bottom: 0;
    font-size: 0.85rem;
    border-collapse: separate;
    border-spacing: 0;
    background: transparent;
}

.thead-dark th {
    position: sticky;
    top: 0;
    z-index: 10;
    background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
    color: white;
    border: none;
    padding: 12px 8px;
    font-weight: 600;
}

/* Sticky column */
.sticky-column {
    position: sticky;
    left: 0;
    z-index: 5;
    background: rgba(248,249,250,0.95);
    border-right: 2px solid #e9ecef;
    min-width: 200px;
    backdrop-filter: blur(5px);
}

.thead-dark .sticky-column {
    z-index: 15; /* Higher z-index for the header corner cell */
}

/* Day columns */
.day-column {
    min-width: 80px;
    text-align: center;
    padding: 8px 4px !important;
}

.day-header {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.day-number {
    font-size: 1.1rem;
    font-weight: bold;
    line-height: 1;
}

.day-name {
    font-size: 0.75rem;
    opacity: 0.8;
    text-transform: uppercase;
}

.holiday-indicator {
    display: none;
}

/* Update holiday styling to use pink color instead of red */
.holiday-column,
.holiday-cell {
    background: linear-gradient(135deg, #fce4ec 0%, #f8bbd9 100%) !important;
    color: #ad1457 !important;
}

/* Staff info */
.staff-info {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px;
}

.staff-avatar {
    width: 35px;
    height: 35px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    font-size: 0.8rem;
    flex-shrink: 0;
}

.staff-details .name {
    font-weight: 600;
    color: #2c3e50;
    font-size: 0.9rem;
    line-height: 1.2;
}

.staff-details .role {
    font-size: 0.75rem;
    color: #6c757d;
    line-height: 1;
}

/* Time cells */
.time-cell {
    padding: 6px 4px;
    vertical-align: middle;
    border-bottom: 1px solid #e9ecef;
    border-right: 1px solid #f1f3f4;
    transition: all 0.2s ease;
    position: relative;
    background: transparent;
}

.time-cell:hover {
    background-color: rgba(248,249,250,0.7);
}

.time-content {
    display: flex;
    flex-direction: column;
    gap: 2px;
    min-height: 30px;
    align-items: center;
    justify-content: center;
}

/* Time entries */
.time-entry {
    padding: 3px 6px;
    border-radius: 4px;
    font-size: 0.75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 2px;
    margin: 1px 0;
    transition: all 0.2s ease;
}

.work-entry {
    background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
    color: #1565c0;
    border: 1px solid #90caf9;
}

.work-entry:hover {
    transform: scale(1.02);
    box-shadow: 0 2px 5px rgba(21,101,192,0.2);
}

.sick-leave-entry {
    background: linear-gradient(135deg, #fff3e0 0%, #ffe0b2 100%);
    color: #e65100;
    border: 1px solid #ffcc02;
}

.day-off-entry {
    padding: 4px 8px;
    border-radius: 15px;
    font-weight: bold;
    text-align: center;
}

.v-type {
    background: linear-gradient(135deg, #f3e5f5 0%, #e1bee7 100%);
    color: #7b1fa2;
    border: 1px solid #ce93d8;
}

.x-type {
    background: linear-gradient(135deg, #e8f5e8 0%, #c8e6c9 100%);
    color: #388e3c;
    border: 1px solid #81c784;
}

.h-type {
    background: linear-gradient(135deg, #fff8e1 0%, #ffecb3 100%);
    color: #f57c00;
    border: 1px solid #ffb74d;
}

.day-off-badge {
    font-weight: bold;
    font-size: 0.8rem;
}

/* Special rows */
.special-row {
    background-color: rgba(248,249,250,0.5);
}

.reception-row .staff-avatar {
    background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
}

.midnight-row .staff-avatar {
    background: linear-gradient(135deg, #6f42c1 0%, #5a32a3 100%);
}

/* Holiday and weekend styling */
.holiday-column,
.holiday-cell {
    background: linear-gradient(135deg, #fce4ec 0%, #f8bbd9 100%) !important;
    color: #ad1457 !important;
}

.weekend-column {
    background: linear-gradient(135deg, #f3e5f5 0%, #e1bee7 100%);
}

/* Empty cells */
.empty-cell {
    color: #bdc3c7;
    font-style: italic;
    text-align: center;
}

.no-data {
    color: #95a5a6;
    font-size: 1.2rem;
}

/* Original time styling */
.original-time {
    font-size: 0.65rem;
    margin-bottom: 2px;
}

.strikethrough {
    text-decoration: line-through;
    color: #dc3545;
    opacity: 0.7;
}

.current-time {
    font-weight: 500;
}

/* Hover effects */
.row-highlight {
    background-color: rgba(0,123,255,0.1) !important;
}

.col-highlight {
    background-color: rgba(40,167,69,0.1) !important;
}

/* Legend */
.legend-container {
    display: flex;
    gap: 15px;
    align-items: center;
    flex-wrap: wrap;
}

.legend-container-mobile {
    display: flex;
    gap: 8px;
    align-items: center;
    justify-content: center;
    flex-wrap: wrap;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 5px;
    margin: 2px 0;
}

.legend-color {
    width: 14px;
    height: 14px;
    border-radius: 3px;
    display: inline-block;
    border: 1px solid rgba(0,0,0,0.1);
}

/* Specific legend colors to match the actual entry types */
.holiday-legend {
    background: linear-gradient(135deg, #fce4ec 0%, #f8bbd9 100%);
    border-color: #ad1457;
}

.day-off-legend {
    background: linear-gradient(135deg, #f3e5f5 0%, #e1bee7 100%);
    border-color: #7b1fa2;
}

.work-hours-legend {
    background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
    border-color: #1565c0;
}

.reception-legend {
    background: linear-gradient(135deg, #e8f5e8 0%, #c8e6c9 100%);
    border-color: #2e7d32;
}

.on-call-legend {
    background: linear-gradient(135deg, #f3e5f5 0%, #e1bee7 100%);
    border-color: #7b1fa2;
}

.sick-leave-legend {
    background: linear-gradient(135deg, #fff3e0 0%, #ffe0b2 100%);
    border-color: #e65100;
}

/* Enhanced legend text */
.legend-item small {
    font-size: 0.75rem;
    font-weight: 500;
    color: #fff;
    white-space: nowrap;
}

/* Mobile legend adjustments */
@media (max-width: 768px) {
    .legend-container-mobile {
        gap: 6px;
    }
    
    .legend-color {
        width: 12px;
        height: 12px;
    }
    
    .legend-item small {
        font-size: 0.7rem;
    }
}

@media (max-width: 480px) {
    .legend-container-mobile {
        gap: 4px;
    }
    
    .legend-color {
        width: 10px;
        height: 10px;
    }
    
    .legend-item small {
        font-size: 0.65rem;
    }
    
    .legend-item {
        gap: 3px;
    }
}
.page-title-box {
    padding: 10px 10px !important;
}
/* Enhanced Mobile Responsive Design */
@media (max-width: 768px) {
    .content-page {
        padding: 0;
    }
    
    .container-fluid {
        padding: 10px;
    }
    
    .page-title-box {
        padding: 15px;
        margin-bottom: 15px;
    }
    
    .page-title-box .page-title {
        font-size: 1.2rem;
    }
    
    .month-selector-header {
        text-align: left;
        margin-top: 10px;
    }
    
    .month-selector-header .form-label {
        color: white !important; /* Ensure white on mobile too */
    }
    
    .card-header {
        padding: 0.75rem;
    }
    
    .staff-info {
        flex-direction: column;
        text-align: center;
        gap: 5px;
        padding: 5px;
    }
    
    .staff-avatar {
        width: 30px;
        height: 30px;
        font-size: 0.7rem;
    }
    
    .staff-details .name {
        font-size: 0.8rem;
    }
    
    .day-column {
        min-width: 50px;
        padding: 5px 2px !important;
    }
    
    .day-number {
        font-size: 0.9rem;
    }
    
    .time-entry {
        font-size: 0.65rem;
        padding: 2px 4px;
        margin: 0;
        line-height: 1.2; /* Better line spacing for stacked times */
    }
    
    .time-cell {
        padding: 4px 2px;
        min-height: 50px; /* Increased to accommodate two-line time display */
    }
    
    .sticky-column {
        min-width: 120px;
        max-width: 120px;
    }
    
    .table-responsive-modern {
        border-radius: 5px;
    }
    
    /* Mobile time display improvements */
    .time-text .d-md-none {
        text-align: center;
        line-height: 1.1;
        font-size: 0.6rem;
    }
    
    /* Better mobile scrolling */
    .table-responsive-modern::-webkit-scrollbar {
        height: 8px;
        width: 8px;
    }
    
    .table-responsive-modern::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 4px;
    }
    
    .table-responsive-modern::-webkit-scrollbar-thumb {
        background: #c1c1c1;
        border-radius: 4px;
    }
    
    .table-responsive-modern::-webkit-scrollbar-thumb:hover {
        background: #a8a8a8;
    }
}

@media (max-width: 480px) {
    .container-fluid {
        padding: 5px;
    }
    
    .page-title-box {
        padding: 10px;
    }
    
    .page-title-box .page-title {
        font-size: 1.1rem;
    }
    
    .sticky-column {
        min-width: 100px;
        max-width: 100px;
    }
    
    .day-column {
        min-width: 45px; /* Slightly wider for better time display */
    }
    
    .time-entry {
        font-size: 0.55rem;
        padding: 1px 2px;
    }
    
    .time-cell {
        min-height: 45px; /* Accommodate two-line time display */
    }
    
    .staff-avatar {
        width: 25px;
        height: 25px;
        font-size: 0.6rem;
    }
    
    .staff-details .name {
        font-size: 0.75rem;
    }
    
    /* Extra small mobile time display */
    .time-text .d-md-none {
        font-size: 0.5rem;
        line-height: 1.0;
    }
}

/* Touch improvements */
@media (hover: none) and (pointer: coarse) {
    .time-cell:hover {
        background-color: transparent;
    }
    
    .work-entry:hover {
        transform: none;
        box-shadow: none;
    }
}

/* iOS-friendly scrolling styles */
html, body {
    height: 100%;
    width: 100%;
    overflow-x: hidden;
}

.content-page {
    min-height: 100vh;
    position: relative;
}

/* Table container with iOS compatibility */
.table-responsive-modern {
    overflow: auto;
    -webkit-overflow-scrolling: touch; /* Enable momentum scrolling on iOS */
    max-height: 70vh; /* Lower height on mobile to ensure it fits in the viewport */
    width: 100%;
    border-radius: 8px;
    border: 1px solid #e9ecef;
    position: relative;
    margin-bottom: 20px;
}

/* Add extra bottom padding for iOS scrolling */
#workingHoursTable {
    margin-bottom: 0;
    font-size: 0.85rem;
    border-collapse: separate;
    border-spacing: 0;
    background: transparent;
}

/* iOS-specific overrides */
@supports (-webkit-touch-callout: none) {
    .table-responsive-modern {
        max-height: none;  /* Remove height constraint */
        overflow-y: visible; /* Be part of page scroll */
    }
    
    /* Force hardware acceleration for smoother scrolling */
    .table-responsive-modern {
        -webkit-transform: translateZ(0);
        transform: translateZ(0);
    }
    
    /* Ensure sticky headers work on iOS */
    .thead-dark th {
        position: -webkit-sticky;
        position: sticky;
        top: 0;
        z-index: 10;
    }
    
    .sticky-column {
        position: -webkit-sticky;
        position: sticky;
        left: 0;
        z-index: 5;
    }
    
    .thead-dark .sticky-column {
        z-index: 15;
    }
}

/* Adjust height for different screen sizes */
@media (max-width: 768px) {
    .table-responsive-modern {
        max-height: none;
    }
}

@media (max-width: 480px) {
    .table-responsive-modern {
        max-height: none;
    }
}

/* Footer adjustments - prevent overlap */
.footer {
    position: relative !important; /* Change from fixed to relative */
    bottom: auto;
    height: auto;
    margin-top: 30px;
    z-index: 10;
}

/* Table container adjustments */
.table-responsive-modern {
    overflow-x: auto;
    overflow-y: auto;
    max-height: none !important; /* Remove max-height restriction */
    width: 100%;
    margin-bottom: 100px !important; /* Much larger margin to ensure content clears footer */
    -webkit-overflow-scrolling: touch;
}

/* Content page adjustments */
.content-page {
    padding-bottom: 80px !important; /* Increase padding at bottom of content */
}

/* Add extra spacing at bottom of content for iOS devices */
@supports (-webkit-touch-callout: none) {
    .content-page {
        padding-bottom: 120px !important;
    }
    
    .table-responsive-modern {
        margin-bottom: 120px !important;
    }
}

/* iOS 16+ specific adjustments for newer devices like iPhone 15 Pro Max */
@media (min-width: 390px) and (-webkit-device-pixel-ratio: 3) {
    .table-responsive-modern {
        margin-bottom: 150px !important;
    }
    
    .content-page {
        padding-bottom: 150px !important;
    }
}
</style>
<?php echo $__env->make('partials.main', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/nordpzbm/hr.nordictravels.tech/resources/views/staffs/dashboard.blade.php ENDPATH**/ ?>