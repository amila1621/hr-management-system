        <!-- ========== Left Sidebar Start ========== -->
        <div class="left side-menu">
            <div class="slimscroll-menu" id="remove-scroll">

                <!--- Sidemenu -->
                <div id="sidebar-menu">
                    <!-- Left Menu Start -->
                    <ul class="metismenu" id="side-menu">
                        <li class="menu-title">Menu</li>

                       
                        <?php if(auth()->user()->role == 'hr-assistant'): ?>  

                        <li>
                          <a href="<?php echo e(route('dashboard')); ?>" class="waves-effect">
                              <i class="dripicons-meter"></i><span> Dashboard </span>
                          </a>
                      </li>

                      <?php endif; ?>

                        <?php if(Auth::user()->role == 'admin'): ?>
                        <?php
                        // Execute the query directly in the view
                        $pendingApprovalsCount = \App\Models\EventSalary::where('is_guide_updated', 1)->where('approval_status', 0)->count();
                        $pending16PlusApprovalsCount = \App\Models\EventSalary::where('approval_status', 5)->count();
                        $pendingReceiptsCount = \App\Models\Receipts::where('status', 0)->count();
                        ?>
                            <li>
                                <a href="<?php echo e(route('dashboard')); ?>" class="waves-effect">
                                    <i class="dripicons-meter"></i><span> Dashboard </span>
                                </a>
                            </li>

                            <li>
                                <a href="<?php echo e(route('admin.pending-approvals')); ?>">
                                    <i class="fas fa-clock"></i> Pending Approvals
                                    <span class="badge badge-danger"><?php echo e($pendingApprovalsCount); ?></span>
                                    <!-- Display the count here -->
                                </a>
                            </li>

                            <li>
                                <a href="<?php echo e(route('admin.pending-16plus-approvals')); ?>">
                                    <i class="fas fa-clock"></i> 16+ Hours Approvals
                                    <span class="badge badge-danger"><?php echo e($pending16PlusApprovalsCount); ?></span>
                                    <!-- Display the count here -->
                                </a>
                            </li>

                            <li>
                                <a href="<?php echo e(route('admin.missing-hours')); ?>">
                                    <i class="fas fa-clock"></i> Missing Hours
                                </a>
                            </li>

                            <li>
                                <a href="<?php echo e(route('fetch.all.events')); ?>" class="waves-effect">
                                    <i class="dripicons-meter"></i><span> All Event </span>
                                </a>
                            </li>

                            <li>
                                <a href="<?php echo e(route('tours.create-a-new-tour')); ?>" class="waves-effect"><i
                                        class="dripicons-calendar"></i><span> Add a New Tour
                                    </span></a>
                            </li>


                            <li>
                                <a href="<?php echo e(route('errors.log')); ?>" class="waves-effect">
                                    <i class="dripicons-meter"></i><span> Error Log</span>
                                </a>
                            </li>

                            <li>
                                <a href="<?php echo e(route('announcements.manage')); ?>" class="waves-effect">
                                    <i class="dripicons-meter"></i><span> Announcements</span>
                                </a>
                            </li>
                          

                            <li>
                                <a href="javascript:void(0);" class="waves-effect"><i
                                        class="dripicons-user-group"></i><span> User Management <span
                                            class="float-right menu-arrow"><i class="mdi mdi-chevron-right"></i></span>
                                    </span></a>
                                <ul class="submenu">
                                    <li><a href="<?php echo e(route('new-user')); ?>"> Create New User </a></li>
                                    <li><a href="<?php echo e(route('tour-guides.index')); ?>"> Manage Guides </a></li>
                                    <li><a href="<?php echo e(route('tour-guides.staff-index')); ?>"> Manage Office Workers </a></li>
                                    <li><a href="<?php echo e(route('tour-guides.operations-index')); ?>"> Manage Operations </a></li>
                                    <li><a href="<?php echo e(route('tour-guides.supervisors-index')); ?>"> Manage Supervisors </a></li>
                                    <li><a href="<?php echo e(route('tour-guides.team-leads-index')); ?>"> Manage Team Leads </a></li>
                                    <li><a href="<?php echo e(route('tour-guides.hr-assistants-index')); ?>"> Manage HR Assistants </a></li>

                                </ul>
                            </li>

                            <li>
                                <a href="javascript:void(0);" class="waves-effect"><i
                                        class="dripicons-user-group"></i><span> Tour Durations <span
                                            class="float-right menu-arrow"><i class="mdi mdi-chevron-right"></i></span>
                                    </span></a>
                                <ul class="submenu">
                                    <li><a href="<?php echo e(route('tour-durations.index')); ?>"> Manage Tour Durations </a></li>
                                    <li><a href="<?php echo e(route('tour-durations-sauna.index')); ?>"> Manage Sauna Tour Durations </a></li>

                                </ul>
                            </li>
                            <li>
                                <a href="<?php echo e(route('admin.assign-guides-to-managers')); ?>" class="waves-effect"><i
                                        class="dripicons-calendar"></i><span> Assign Managers
                                    </span></a>
                            </li>

                            <li>
                                <a href="javascript:void(0);" class="waves-effect"><i
                                        class="dripicons-user-group"></i><span> Reports <span
                                            class="float-right menu-arrow"><i class="mdi mdi-chevron-right"></i></span>
                                    </span></a>
                                <ul class="submenu">
                                    <li><a href="<?php echo e(route('reports.guide-wise-report-create')); ?>"> Guide Report
                                    <li><a href="<?php echo e(route('reports.guide-time-report-create')); ?>"> Guide Time Report
                                    <li><a href="<?php echo e(route('reports.manually-added-entries-create')); ?>"> Manually Added
                                            Entries Report</a></li>
                                    <li><a href="<?php echo e(route('reports.manually-added-tours-create')); ?>"> Manually Added
                                            New Tours Report</a></li>

                                </ul>
                            </li>

                           
                            <li>
                                <a href="<?php echo e(route('reports.monthly-report-create')); ?>" class="waves-effect"><i
                                        class="dripicons-calendar"></i><span> Monthly Report
                                    </span></a>
                            </li>

                            <li>
                                <a href="<?php echo e(route('reports.monthly-report-christmas')); ?>" class="waves-effect"><i
                                        class="dripicons-calendar"></i><span> Monthly Report(Christmas)
                                    </span></a>
                            </li>
                            <li>
                                <a href="<?php echo e(route('reports.guide-time-report-christmas')); ?>" class="waves-effect"><i
                                        class="dripicons-calendar"></i><span> Guide Time Report (Christmas)
                                    </span></a>
                            </li>

                            <li>
                                <a href="<?php echo e(route('guides.working-hours')); ?>" class="waves-effect"><i
                                        class="dripicons-calendar"></i><span> 3 Weeks Reports
                                    </span></a>
                            </li>

                            <li>
                                <a href="<?php echo e(route('reports.rejected-hours')); ?>" class="waves-effect"><i
                                        class="dripicons-calendar"></i><span> Rejected Hours
                                    </span></a>
                            </li>
                          
                        <?php elseif(Auth::user()->role == 'guide' || Auth::user()->role == 'hr-assistant'): ?>
                            <li>
                                <a href="<?php echo e(route('guide.work-report')); ?>" class="waves-effect">
                                    <i class="dripicons-meter"></i><span> Work Report </span>
                                </a>
                            </li>
                            <?php if(auth()->user()->tourGuide && auth()->user()->tourGuide->allow_report_hours): ?>
                                <li>
                                    <a href="<?php echo e(route('guide.report-hours')); ?>" class="waves-effect">
                                        <i class="dripicons-meter"></i><span> Record Work Hours </span>
                                    </a>
                                </li>
                            <?php endif; ?>

                            
                            <?php if(auth()->user()->role == 'hr-assistant'): ?>  

                    
                            <li>
                                <a href="<?php echo e(route('fetch.all.events')); ?>" class="waves-effect">
                                    <i class="dripicons-meter"></i><span> All Event </span>
                                </a>
                            </li>

                            <li>
                                <a href="<?php echo e(route('admin.pending-approvals')); ?>">
                                    <i class="fas fa-clock"></i> Pending Approvals
                                </a>
                            </li>

                            <li>
                                <a href="<?php echo e(route('admin.missing-hours')); ?>">
                                    <i class="fas fa-clock"></i> Missing Hours
                                </a>
                            </li>

                            <li>
                                <a href="<?php echo e(route('tours.create-a-new-tour')); ?>" class="waves-effect"><i
                                        class="dripicons-calendar"></i><span> Add a New Tour
                                    </span></a>
                            </li>


                            <li>
                                <a href="<?php echo e(route('announcements.manage')); ?>" class="waves-effect">
                                    <i class="dripicons-meter"></i><span> Announcements</span>
                                </a>
                            </li>


                            <li>
                                <a href="javascript:void(0);" class="waves-effect"><i
                                        class="dripicons-user-group"></i><span> Tour Durations <span
                                            class="float-right menu-arrow"><i class="mdi mdi-chevron-right"></i></span>
                                    </span></a>
                                <ul class="submenu">
                                    <li><a href="<?php echo e(route('tour-durations.index')); ?>"> Manage Tour Durations </a></li>

                                </ul>
                            </li>


                            <li>
                                <a href="javascript:void(0);" class="waves-effect"><i
                                        class="dripicons-user-group"></i><span> Reports <span
                                            class="float-right menu-arrow"><i class="mdi mdi-chevron-right"></i></span>
                                    </span></a>
                                <ul class="submenu">
                                    <li><a href="<?php echo e(route('reports.guide-wise-report-create')); ?>"> Guide Report
                                    
                                    <li><a href="<?php echo e(route('reports.manually-added-entries-create')); ?>"> Manually Added
                                            Entries Report</a></li>
                                    <li><a href="<?php echo e(route('reports.manually-added-tours-create')); ?>"> Manually Added
                                            New Tours Report</a></li>

                                </ul>
                            </li>

                            <li>
                                <a href="<?php echo e(route('reports.monthly-report-christmas')); ?>" class="waves-effect"><i
                                        class="dripicons-calendar"></i><span> Monthly Report(Christmas)
                                    </span></a>
                            </li>
                            <li>
                                <a href="<?php echo e(route('reports.guide-time-report-christmas')); ?>" class="waves-effect"><i
                                        class="dripicons-calendar"></i><span> Guide Time Report (Christmas)
                                    </span></a>
                            </li>

                           
                            <li>
                                <a href="<?php echo e(route('reports.monthly-report-create')); ?>" class="waves-effect"><i
                                        class="dripicons-calendar"></i><span> Monthly Report
                                    </span></a>
                            </li>

                            <li>
                                <a href="<?php echo e(route('guides.working-hours')); ?>" class="waves-effect"><i
                                        class="dripicons-calendar"></i><span> 3 Weeks Reports
                                    </span></a>
                            </li>

                            <li>
                                <a href="<?php echo e(route('reports.rejected-hours')); ?>" class="waves-effect"><i
                                        class="dripicons-calendar"></i><span> Rejected Hours
                                    </span></a>
                            </li>




                            <?php endif; ?>

                        <?php elseif(Auth::user()->role == 'team-lead'): ?>
                           
                            <li>
                                <a href="<?php echo e(route('admin.pending-approvals')); ?>">
                                    <i class="fas fa-clock"></i> Pending Approvals
                                </a>
                            </li>
                            <li>
                                <a href="<?php echo e(route('manager.guide-report')); ?>" class="waves-effect">
                                    <i class="dripicons-meter"></i><span> Bus Driver Report </span>
                                </a>
                            </li>

                       
                           
                        <?php elseif(Auth::user()->role == 'supervisor'): ?>

                            <li>
                                <a href="<?php echo e(route('guides.working-hours')); ?>" class="waves-effect"><i
                                        class="dripicons-calendar"></i><span> Ranking for hours
                                    </span></a>
                            </li>

                            <li>
                                <a href="<?php echo e(route('supervisor.enter-working-hours')); ?>" class="waves-effect"><i
                                        class="dripicons-calendar"></i><span> Manage Monthly Plan  
                                    </span></a>
                            </li>

                            <li>
                                <a href="<?php echo e(route('supervisor.display-schedule')); ?>" class="waves-effect"><i
                                        class="dripicons-calendar"></i><span> Display Schedule  
                                    </span></a>
                            </li>

                            <li>
                                <a href="<?php echo e(route('supervisor.view-time-plan')); ?>" class="waves-effect"><i
                                        class="dripicons-calendar"></i><span> View Time Plan  
                                    </span></a>
                            </li>
                            
                            

                            <?php elseif(Auth::user()->role == 'operation'): ?>

                            <li>
                                <a href="<?php echo e(route('guides.working-hours')); ?>" class="waves-effect"><i
                                        class="dripicons-calendar"></i><span> Ranking for hours
                                    </span></a>
                            </li>
                            <li>
                                <a href="<?php echo e(route('reports.monthly-report-create-op')); ?>" class="waves-effect"><i
                                        class="dripicons-calendar"></i><span> Monthly Report
                                    </span></a>
                            </li>
                            <li>
                                <a href="<?php echo e(route('vehicles.index')); ?>" class="waves-effect"><i
                                        class="dripicons-calendar"></i><span> Manage Vehicles
                                    </span></a>
                            </li>
                            <li>
                                <a href="<?php echo e(route('operation.checkin-sheet')); ?>" class="waves-effect"><i
                                        class="dripicons-calendar"></i><span> Daily Sheet
                                    </span></a>
                            </li>


                        <?php endif; ?>

                        <?php if(Auth::user()->role == 'admin'): ?>
                        <li>
                            <a href="<?php echo e(route('receipts.approve')); ?>" class="waves-effect"><i
                                    class="dripicons-document"></i><span> Pending Receipts
                                        <span class="badge badge-danger"><?php echo e($pendingReceiptsCount); ?></span>
                                </span></a>
                        </li>

                        <li>
                            <a href="<?php echo e(route('salary-updates.index')); ?>" class="waves-effect"><i
                                    class="dripicons-document"></i><span> Salary Updates
                                </span></a>
                        </li>

                        <li>
                            <a href="<?php echo e(route('receipts.approve')); ?>" class="waves-effect"><i
                                    class="dripicons-document"></i><span> Pending Receipts
                                        <span class="badge badge-danger"><?php echo e($pendingReceiptsCount); ?></span>
                                </span></a>
                        </li>
                        <?php else: ?>
                        <li>
                            <a href="javascript:void(0);" class="waves-effect">
                                <i class="dripicons-document"></i>
                                <span> Receipts <span class="float-right menu-arrow">
                                    <i class="mdi mdi-chevron-right"></i></span>
                                </span>
                            </a>
                            <ul class="submenu">
                                <li>
                                    <a href="<?php echo e(route('receipts.create')); ?>">Submit Receipts</a>
                                </li>
                                <li>
                                    <a href="<?php echo e(route('receipts.manage')); ?>">Manage Receipts</a>
                                </li>
                            </ul>
                        </li>
                        <?php endif; ?>
                        



                    </ul>
                    </li>


                    </ul>

                </div>
                <!-- Sidebar -->
                <div class="clearfix"></div>

            </div>
            <!-- Sidebar -left -->

        </div>
        <!-- Left Sidebar End -->
<?php /**PATH C:\Users\OMISTAJA\Documents\Projects\-nuthr\resources\views/partials/sidebar.blade.php ENDPATH**/ ?>