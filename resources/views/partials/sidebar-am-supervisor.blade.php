<li>
    <a href="{{ route('am-supervisor.enter-working-hours') }}" class="waves-effect">
        <i class="fas fa-tasks"></i><span> Manage Roster </span>
    </a>
</li>
<li>
    <a target="_blank" href="{{ route('supervisor.print-roster') }}" class="waves-effect">
        <i class="fas fa-calendar-alt"></i><span> Print Rosters </span>
    </a>
</li>
<li>
    <a href="{{ route('supervisors.working-hours') }}" class="waves-effect">
        <i class="fas fa-hourglass"></i><span> 3 Weeks Report </span>
    </a>
</li>

<li>
    <a href="{{ route('supervisors.missing-hours') }}" class="waves-effect">
        <i class="fas fa-exclamation-circle"></i><span> Missing/Extra Hours </span>
    </a>
</li>

<li>
    <a href="{{ route('staff.schedule') }}" class="waves-effect">
        <i class="fas fa-calendar-alt"></i><span> View Monthly Roster </span>
    </a>
</li>
<li>
    <a href="{{ route('supervisor.display-schedule') }}" class="waves-effect">
        <i class="fas fa-calendar-week"></i><span> Display Schedule </span>
    </a>
</li>

<li>
    <a href="{{ route('supervisor.view-time-plan') }}" class="waves-effect">
        <i class="fas fa-clock"></i><span> View Time Plan </span>
    </a>
</li>

@php

$supervisorDepartments = explode(', ', 'AM');
$pendingSickLeaveCount = \App\Models\SupervisorSickLeaves::where(function($query) use ($supervisorDepartments) {
    foreach ($supervisorDepartments as $department) {
        $query->orWhere('department', 'LIKE', $department)
              ->orWhere('department', 'LIKE', $department . ',%')
              ->orWhere('department', 'LIKE', '%, ' . $department)
              ->orWhere('department', 'LIKE', '%, ' . $department . ',%');
    }
})->where('status', 0)->count();

@endphp

<li>
    <a href="{{ route('supervisor.manage-sick-leaves') }}" class="waves-effect">
        <i class="fas fa-medkit"></i><span> Pending Sick Leaves </span>
        <span class="badge badge-danger">{{ $pendingSickLeaveCount }}</span>
    </a>
</li>

@php
// Get current supervisor's departments
$supervisorDepartments = explode(', ', 'AM');

// Get receipt counts for the supervisor's departments
$pendingReceiptsCount = \App\Models\Receipts::where(function($query) use ($supervisorDepartments) {
    foreach ($supervisorDepartments as $department) {
        $query->orWhere('department', 'LIKE', $department)
              ->orWhere('department', 'LIKE', $department . ',%')
              ->orWhere('department', 'LIKE', '%, ' . $department)
              ->orWhere('department', 'LIKE', '%, ' . $department . ',%');
    }
})->where('status', 0)->count();
@endphp

<li>
    <a href="{{ route('supervisor.manage-receipts') }}" class="waves-effect">
        <i class="fas fa-receipt"></i><span> Pending Receipts </span>
        <span class="badge badge-danger">{{ $pendingReceiptsCount }}</span>
    </a>
</li>

<li>
    <a href="{{ route('supervisor.manage-staff') }}" class="waves-effect">
        <i class="fas fa-palette"></i><span> Colour Management </span>
    </a>
</li>