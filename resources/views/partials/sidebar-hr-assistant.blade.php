<?php
$pendingExtraHoursCount = \App\Models\ExtraHoursRequest::where('status', 'pending')->count();
?>

<li>
    <a href="{{ route('dashboard') }}" class="waves-effect">
        <i class="fas fa-tachometer-alt"></i><span> Dashboard </span>
    </a>
</li>

<li>
    <a href="{{ route('admin.extra-hours-requests') }}">
        <i class="fas fa-clock"></i> Extra Hours Requests
        @if($pendingExtraHoursCount > 0)
        <span class="badge badge-warning">{{ $pendingExtraHoursCount }}</span>
        @endif
    </a>
</li>


<li>
    <a href="{{ route('staff.report-hours') }}" class="waves-effect">
        <i class="fas fa-tasks"></i><span> Report Working Hours </span>
    </a>
</li>



<li>
    <a href="{{ route('staff.hours-report') }}" class="waves-effect">
        <i class="fas fa-hourglass"></i><span>Personal Hours Report </span>
    </a>
</li>

<li>
    <a href="{{ route('fetch.all.events') }}" class="waves-effect">
        <i class="fas fa-calendar-week"></i><span> All Event </span>
    </a>
</li>

<li>
    <a href="{{ route('admin.pending-approvals') }}">
        <i class="fas fa-check-circle"></i> Pending Approvals
    </a>
</li>

<li>
    <a href="{{ route('admin.missing-hours') }}">
        <i class="fas fa-exclamation-circle"></i> Missing Hours
    </a>
</li>

<li>
    <a href="{{ route('tours.create-a-new-tour') }}" class="waves-effect">
        <i class="fas fa-plus-circle"></i><span>  + New Tour </span>
    </a>
</li>

<li>
    <a href="javascript:void(0);" class="waves-effect">
        <i class="fas fa-procedures"></i><span> + Sick Leave Tour 
            <span class="float-right menu-arrow"><i class="mdi mdi-chevron-right"></i></span>
        </span>
    </a>
    <ul class="submenu">
        <li><a href="{{ route('tours.create-a-sick-leave-tour') }}"> + Sick Leave Tour </a></li>
        <li><a href="{{ route('tours.sick-leave-tours') }}"> Manage Sick Leave Tours </a></li>
    </ul>
</li>

<li>
    <a href="{{ route('announcements.manage') }}" class="waves-effect">
        <i class="fas fa-bullhorn"></i><span> Announcements</span>
    </a>
</li>

<li>
    <a href="javascript:void(0);" class="waves-effect">
        <i class="fas fa-clock"></i><span> Tour Durations 
            <span class="float-right menu-arrow"><i class="mdi mdi-chevron-right"></i></span>
        </span>
    </a>
    <ul class="submenu">
        <li><a href="{{ route('tour-durations.index') }}"> Manage Tour Durations </a></li>
    </ul>
</li>

<li>
    <a href="javascript:void(0);" class="waves-effect">
        <i class="fas fa-chart-bar"></i><span> Reports 
            <span class="float-right menu-arrow"><i class="mdi mdi-chevron-right"></i></span>
        </span>
    </a>
    <ul class="submenu">
        <li><a href="{{ route('reports.guide-wise-report-create') }}"> Guide Report
        <li><a href="{{ route('reports.guide-wise-report-custom-create') }}"> Guide Report(Date Range)</a></li>
        {{-- <li><a href="{{ route('reports.guide-time-report-create') }}"> Guide Time Report --}}
        <li><a href="{{ route('reports.manually-added-entries-create') }}"> Manually Added Entries Report</a></li>
        <li><a href="{{ route('reports.manually-added-tours-create') }}"> Manually Added New Tours Report</a></li>
        <li><a href="{{ route('reports.terminated-guide-wise-report-create') }}"> Terminated Guides Report</a></li>
    </ul>
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
    <a href="{{ route('reports.monthly-report-create') }}" class="waves-effect">
        <i class="fas fa-calendar-alt"></i><span> Monthly Report </span>
    </a>
</li>

<li>
    <a href="{{ route('guides.working-hours') }}" class="waves-effect">
        <i class="fas fa-calendar-week"></i><span> 3 Weeks Reports </span>
    </a>
</li>

<li>
    <a href="{{ route('reports.rejected-hours') }}" class="waves-effect">
        <i class="fas fa-ban"></i><span> Rejected Hours </span>
    </a>
</li>
