<?php
    $pendingSickLeaveCount = \App\Models\SupervisorSickLeaves::where('admin_id', null)->where('status','!=',4)->count();
?>

<li>
    <a href="{{ route('supervisor.manage-sick-leaves') }}" class="waves-effect">
        <i class="fas fa-medkit"></i><span> Pending Sick Leaves </span>
        <span class="badge badge-danger">{{ $pendingSickLeaveCount }}</span>
    </a>
</li>

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
        <li><a href="{{ route('tour-guides.hr-assistants-index') }}"> Manage AM Supervisor </a></li>
    </ul>
</li>

<li>
    <a href="{{ route('staff.schedule') }}" class="waves-effect">
        <i class="fas fa-calendar-alt"></i><span> Monthly Roster </span>
    </a>
</li>

<li>
    <a href="{{ route('staff.hours-report') }}" class="waves-effect">
        <i class="fas fa-hourglass"></i><span> Hours Report </span>
    </a>
</li>
