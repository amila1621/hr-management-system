@if(in_array('Operations', explode(', ', Auth::user()->supervisor->department)))
<li>
    <a href="{{ route('guides.working-hours') }}" class="waves-effect">
        <i class="fas fa-sort-amount-down"></i><span> Ranking for Hours </span>
    </a>
</li>
<li>
    <a href="{{ route('guides.ranking-for-hours-bus-drivers') }}" class="waves-effect">
        <i class="fas fa-bus"></i><span> Ranking for Hours(Bus Drivers) </span>
    </a>
</li>
<li>
    <a href="{{ route('reports.monthly-report-create-op') }}" class="waves-effect">
        <i class="fas fa-calendar-alt"></i><span> Monthly Report </span>
    </a>
</li>

<!-- <li>
    <a href="{{ route('vehicles.index') }}" class="waves-effect">
        <i class="fas fa-car"></i><span> Manage Vehicles </span>
    </a>
</li> -->
<!-- <li>
    <a href="{{ route('operation.checkin-sheet') }}" class="waves-effect">
        <i class="fas fa-clipboard-check"></i><span> Daily Sheet </span>
    </a>
</li> -->
@endif



<li>
    <a href="{{ route('staff.hours-report') }}" class="waves-effect">
        <i class="fas fa-file"></i><span> Personal Hours Report </span>
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

$supervisor = \App\Models\Supervisors::where('user_id', Auth::user()->id)->first();
$supervisorDepartments = explode(', ', $supervisor->department);
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
$supervisor = \App\Models\Supervisors::where('user_id', auth()->id())->first();
$supervisorDepartments = explode(', ', $supervisor->department);

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
        <i class="fas fa-palette"></i><span> Organize Staff </span>
    </a>
</li>
