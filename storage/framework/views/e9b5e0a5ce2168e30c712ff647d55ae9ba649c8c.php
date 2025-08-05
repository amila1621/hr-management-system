<!-- ========== Left Sidebar Start ========== -->
<div class="left side-menu">
    <div class="slimscroll-menu" id="remove-scroll">

        <!--- Sidemenu -->
        <div id="sidebar-menu">
            <!-- Left Menu Start -->
            <ul class="metismenu" id="side-menu">
                <li class="menu-title">Menu</li>

                <?php if(auth()->user()->hasAnyAccountingAccess()): ?>
                    <li>
                        <a href="<?php echo e(route('accountant.records.store')); ?>">
                            <i class="fas fa-money-bill-alt"></i>  Payments/Deductions
                        </a>
                    </li>
                <?php endif; ?>

                <?php if(Auth::user()->role == 'admin'): ?>
                    <?php echo $__env->make('partials.sidebar-admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                <?php elseif(Auth::user()->role == 'hr-assistant'): ?>
                    <?php echo $__env->make('partials.sidebar-hr-assistant', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                <?php elseif(Auth::user()->role == 'guide'): ?>
                    <?php echo $__env->make('partials.sidebar-guide', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                <?php elseif(Auth::user()->role == 'guide/staff'): ?>
                    <?php echo $__env->make('partials.sidebar-guide-staff', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                <?php elseif(Auth::user()->role == 'team-lead'): ?>
                    <?php echo $__env->make('partials.sidebar-team-lead', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                <?php elseif(Auth::user()->role == 'supervisor'): ?>
                    <?php echo $__env->make('partials.sidebar-supervisor', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                <?php elseif(Auth::user()->role == 'staff'): ?>
                    <?php echo $__env->make('partials.sidebar-staff', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                <?php elseif(Auth::user()->role == 'hr'): ?>
                    <?php echo $__env->make('partials.sidebar-hr', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                <?php elseif(Auth::user()->role == 'am-supervisor'): ?>
                    <?php echo $__env->make('partials.sidebar-am-supervisor', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                <?php endif; ?>
            </ul>
        </div>
        <!-- Sidebar -->
        <div class="clearfix"></div>
    </div>
    <!-- Sidebar -left -->
</div>
<!-- Left Sidebar End -->
<?php /**PATH /home/nordpzbm/hr.nordictravels.tech/resources/views/partials/sidebar.blade.php ENDPATH**/ ?>