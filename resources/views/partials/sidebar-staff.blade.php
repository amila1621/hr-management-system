<style>
    /* Sidebar Section Styling */
.side-section-divider {
    height: 1px;
    background-color: rgba(255, 255, 255, 0.1);
    margin: 15px 0 5px 0;
}

.section-title {
    padding: 5px 15px;
    font-size: 12px;
    font-weight: 600;
    letter-spacing: 0.05em;
    color: #adb5bd;
    margin-bottom: 10px;
    text-transform: uppercase;
}

.section-title i {
    margin-right: 5px;
    font-size: 14px;
}

</style>

@php
    $userId = Auth::user()->id;
    $department = \App\Models\StaffUser::where('user_id', $userId)->first()->department;
@endphp

@if($department == 'Operations')
<li>
    <div class="side-section-divider"></div>
    <h5 class="section-title"><i class="fas fa-briefcase mr-1"></i> OPERATIONS</h5>
</li>
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
@endif

<li>
    <div class="side-section-divider"></div>
    <h5 class="section-title"><i class="fas fa-user mr-1"></i> PERSONAL</h5>
</li>

@if (auth()->user()->staff && auth()->user()->staff->allow_report_hours)
    <li>
        <a href="{{ route('staff.report-hours') }}" class="waves-effect">
            <i class="fas fa-clock"></i><span> Report Work Hours </span>
        </a>
    </li>
@endif

<li>
    <a href="{{ route('staff.schedule') }}" class="waves-effect">
        <i class="fas fa-calendar-alt"></i><span> Monthly Roster </span>
    </a>
</li>
@php
    // Check if current user is also a guide (dual-role)
    $isStaffGuide = \App\Models\TourGuide::where('user_id', Auth::user()->id)->where('is_hidden', 0)->exists();
@endphp

@if($isStaffGuide)
<li>
    <a href="{{ route('staff-guide.hours-report') }}" class="waves-effect">
        <i class="fas fa-hourglass"></i><span> Staff+Guide Hours Report </span>
    </a>
</li>
@else
<li>
    <a href="{{ route('staff.hours-report') }}" class="waves-effect">
        <i class="fas fa-hourglass"></i><span> Hours Report </span>
    </a>
</li>
@endif

<li>
    <a href="javascript:void(0);" class="waves-effect">
        <i class="fas fa-receipt"></i>
        <span> Sick Leave <span class="float-right menu-arrow">
                <i class="mdi mdi-chevron-right"></i></span>
        </span>
    </a>
    <ul class="submenu">
        <li>
            <a href="{{ route('sick-leave.request-sick-leaves') }}">Request Sick Leaves</a>
        </li>
        <li>
            <a href="{{ route('sick-leave.manage-sick-leaves') }}">Sick Leave Records</a>
        </li>
    </ul>
</li>

<li>
    <a href="javascript:void(0);" class="waves-effect">
        <i class="fas fa-receipt"></i>
        <span> Receipts <span class="float-right menu-arrow">
                <i class="mdi mdi-chevron-right"></i></span>
        </span>
    </a>
    <ul class="submenu">
        <li>
            <a href="{{ route('receipts.create') }}">Submit Receipts</a>
        </li>
        <li>
            <a href="{{ route('receipts.manage') }}">Receipt Records</a>
        </li>
    </ul>
</li>
