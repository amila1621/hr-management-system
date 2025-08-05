<?php
// Execute the query directly in the view
$pendingApprovalsCount = \App\Models\EventSalary::where('is_guide_updated', 1)->where('approval_status', 0)->count();
$pending16PlusApprovalsCount = \App\Models\EventSalary::where('approval_status', 5)->count();
$pendingReceiptsCount = \App\Models\Receipts::whereIn('status', [0, 1])->count();
$pendingSickLeaveCount = \App\Models\SupervisorSickLeaves::where('admin_id', null)->whereIn('status', [0,1])->count();
?>


<!-- Administration -->
<li>
    <a href="javascript:void(0);" class="waves-effect">
        <i class="fas fa-calculator"></i><span> Accountant Reports
            <span class="float-right menu-arrow"><i class="mdi mdi-chevron-right"></i></span>
        </span>
    </a>
    <ul class="submenu">
        <li><a href="{{ route('combined-reports.hotel.create') }}"> Hotel Accountant Report</a></li>
        <!-- <li><a href="{{ route('combined-reports.all-departments.create') }}"> NUT Accountant Report</a></li>
        <li><a href="{{ route('reports.accountant-report-create') }}"> Guide Accountant Report</a></li> -->
        <li><a href="{{ route('combined-reports.combined-accountant.create') }}"> Combined Accountant Report</a></li>
    </ul>
</li>

<li>
    <a href="javascript:void(0);" class="waves-effect">
        <i class="fas fa-calculator"></i><span> Accountant Types 
            <span class="float-right menu-arrow"><i class="mdi mdi-chevron-right"></i></span>
        </span>
    </a>
    <ul class="submenu">
        <!-- <li><a href="{{ route('reports.accountant-report-create') }}"> Guide Accountant Report</a></li> -->
        <!-- <li><a href="{{ route('reports.staff-report-create') }}"> Office Worker Accountant Report</a></li> -->
        <!-- <li><a href="{{ route('reports.operation-staff-report-create') }}"> OP Accountant Report</a></li> -->
        <!-- <li><a href="{{ route('reports.hotel-report-create') }}"> Hotel Accountant Report</a></li> -->
        <li><a href="{{ route('accountant.manage-access') }}"> Manage Access </a></li>
        <li><a href="{{ route('accountant.manage-income-expenses') }}"> Manage Income/Expenses Types </a></li>
    </ul>
</li>

<li>
    <a href="{{ route('guides.working-hours') }}" class="waves-effect">
        <i class="fas fa-calendar-week"></i><span> 3 Weeks Reports(Guides) </span>
    </a>
</li>
<li>
    <a href="{{ route('staff.working-hours') }}" class="waves-effect">
        <i class="fas fa-calendar-week"></i><span> 3 Weeks Reports(Staff) </span>
    </a>
</li>

<!-- Approvals & Hours Management -->
<li>
    <a href="{{ route('admin.pending-approvals') }}">
        <i class="fas fa-check-circle"></i> Pending Approvals
        <span class="badge badge-danger">{{ $pendingApprovalsCount }}</span>
    </a>
</li>

<li>
    <a href="{{ route('admin.manage-sick-leaves') }}" class="waves-effect">
        <i class="fas fa-medkit"></i><span> Pending Sick Leaves </span>
        <span class="badge badge-danger">{{ $pendingSickLeaveCount }}</span>
    </a>
</li>

<li>
    <a href="{{ route('admin.pending-16plus-approvals') }}">
        <i class="fas fa-hourglass-half"></i> 16+ Hours Approvals
        <span class="badge badge-danger">{{ $pending16PlusApprovalsCount }}</span>
    </a>
</li>

<li>
    <a href="{{ route('receipts.approve') }}" class="waves-effect">
        <i class="fas fa-receipt"></i><span> Pending Receipts
            <span class="badge badge-danger">{{ $pendingReceiptsCount }}</span>
        </span>
    </a>
</li>

<li>
    <a href="{{ route('supervisor.enter-working-hours') }}" class="waves-effect">
        <i class="fas fa-tasks"></i><span> Manage Roster </span>
    </a>
</li>
<li>
    <a target="_blank" href="{{ route('supervisor.print-roster') }}" class="waves-effect">
        <i class="fas fa-calendar-alt"></i><span> Print Rosters </span>
    </a>
</li>

<li>
    <a href="{{ route('admin.missing-hours') }}">
        <i class="fas fa-exclamation-circle"></i> Missing Hours
    </a>
</li>

<!-- Events & Tours Management -->
<li>
    <a href="{{ route('fetch.all.events') }}" class="waves-effect">
        <i class="fas fa-calendar-week"></i><span> All Event </span>
    </a>
</li>

<li>
    <a href="{{ route('tours.create-a-new-tour') }}" class="waves-effect">
        <i class="fas fa-plus-circle"></i><span> + New Tour </span>
    </a>
</li>

<li>
    <a href="javascript:void(0);" class="waves-effect">
        <i class="fas fa-ambulance  "></i><span> + Sick Leave Tour 
            <span class="float-right menu-arrow"><i class="mdi mdi-chevron-right"></i></span>
        </span>
    </a>
    <ul class="submenu">
        <li><a href="{{ route('tours.create-a-sick-leave-tour') }}"> + Sick Leave Tour </a></li>
        <li><a href="{{ route('tours.sick-leave-tours') }}"> Manage Sick Leave Tours </a></li>
    </ul>
</li>



<li>
    <a href="{{ route('errors.log') }}" class="waves-effect">
        <i class="fas fa-exclamation-triangle"></i><span> Error Log</span>
    </a>
</li>

<li>
    <a href="{{ route('announcements.manage') }}" class="waves-effect">
        <i class="fas fa-bullhorn"></i><span> Announcements</span>
    </a>
</li>

<!-- User Management -->
<li>
    <a href="javascript:void(0);" class="waves-effect">
        <i class="fas fa-users-cog"></i><span> User Management 
            <span class="float-right menu-arrow"><i class="mdi mdi-chevron-right"></i></span>
        </span>
    </a>
    <ul class="submenu">
        <li><a href="{{ route('new-user') }}"> Create New User </a></li>
        <li><a href="{{ route('tour-guides.index') }}"> Manage Guides </a></li>
        <li><a href="{{ route('tour-guides.staff-index') }}"> Manage Office Workers </a></li>
        <li><a href="{{ route('tour-guides.supervisors-index') }}"> Manage Supervisors </a></li>
        <li><a href="{{ route('tour-guides.team-leads-index') }}"> Manage Bus Driver Supervisors </a></li>
        <li><a href="{{ route('tour-guides.hr-assistants-index') }}"> Manage Guide Supervisors </a></li>
        <li><a href="{{ route('tour-guides.hr-assistants-index') }}"> Manage HR </a></li>
        <li><a href="{{ route('tour-guides.am-supervisors-index') }}"> Manage AM Supervisor </a></li>
    </ul>
</li>

<!-- Tour Management -->
<li>
    <a href="javascript:void(0);" class="waves-effect">
        <i class="fas fa-clock"></i><span> Tour Durations 
            <span class="float-right menu-arrow"><i class="mdi mdi-chevron-right"></i></span>
        </span>
    </a>
    <ul class="submenu">
        <li><a href="{{ route('tour-durations.index') }}"> Manage Tour Durations </a></li>
        <li><a href="{{ route('tour-durations-sauna.index') }}"> Manage Sauna Tour Durations </a></li>
    </ul>
</li>

<!-- <li>
    <a href="{{ route('admin.assign-guides-to-managers') }}" class="waves-effect">
        <i class="fas fa-user-tie"></i><span> Assign Managers </span>
    </a>
</li> -->

<!-- Reports -->
<li>
    <a href="javascript:void(0);" class="waves-effect">
        <i class="fas fa-chart-bar"></i><span> Reports 
            <span class="float-right menu-arrow"><i class="mdi mdi-chevron-right"></i></span>
        </span>
    </a>
    <ul class="submenu">
        <li><a href="{{ route('reports.guide-wise-report-create') }}"> Guide Report
        <li><a href="{{ route('reports.getOfficeStaffWiseReport') }}"> Office Staff Report
        <li><a href="{{ route('reports.guide-time-report-create') }}"> Guide Time Report
        <li><a href="{{ route('reports.manually-added-entries-create') }}"> Manually Added Entries Report</a></li>
        <li><a href="{{ route('reports.manually-added-tours-create') }}"> Manually Added New Tours Report</a></li>
        <li><a href="{{ route('reports.terminated-guide-wise-report-create') }}"> Terminated Guides Report</a></li>
        <!-- <li><a href="{{ route('supervisor.hotel-staff-report') }}"> Hotel Staff Report</a></li> -->
   
    </ul>
</li>

<li>
    <a href="{{ route('reports.monthly-report-create') }}" class="waves-effect">
        <i class="fas fa-calendar-alt"></i><span> Monthly Report </span>
    </a>
</li>

<li>
    <a href="{{ route('reports.monthly-report-christmas') }}" class="waves-effect">
        <i class="fas fa-gift"></i><span> Monthly Report(Christmas) </span>
    </a>
</li>

<li>
    <a href="{{ route('reports.guide-time-report-christmas') }}" class="waves-effect">
        <i class="fas fa-snowflake"></i><span> Guide Time Report (Christmas) </span>
    </a>
</li>



<li>
    <a href="{{ route('reports.rejected-hours') }}" class="waves-effect">
        <i class="fas fa-ban"></i><span> Rejected Hours </span>
    </a>
</li>

<li>
    <a href="{{ route('salary-updates.index') }}" class="waves-effect">
        <i class="fas fa-money-bill-wave"></i><span> Salary Updates </span>
    </a>
</li>
