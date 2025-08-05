<!-- ========== Left Sidebar Start ========== -->
<div class="left side-menu">
    <div class="slimscroll-menu" id="remove-scroll">

        <!--- Sidemenu -->
        <div id="sidebar-menu">
            <!-- Left Menu Start -->
            <ul class="metismenu" id="side-menu">
                <li class="menu-title">Menu</li>

                @if(auth()->user()->hasAnyAccountingAccess())
                    <li>
                        <a href="{{ route('accountant.records.store') }}">
                            <i class="fas fa-money-bill-alt"></i>  Payments/Deductions
                        </a>
                    </li>
                @endif

                @if (Auth::user()->role == 'admin')
                    @include('partials.sidebar-admin')
                @elseif (Auth::user()->role == 'hr-assistant')
                    @include('partials.sidebar-hr-assistant')
                @elseif (Auth::user()->role == 'guide')
                    @include('partials.sidebar-guide')
                @elseif (Auth::user()->role == 'guide/staff')
                    @include('partials.sidebar-guide-staff')
                @elseif (Auth::user()->role == 'team-lead')
                    @include('partials.sidebar-team-lead')
                @elseif (Auth::user()->role == 'supervisor')
                    @include('partials.sidebar-supervisor')
                @elseif (Auth::user()->role == 'staff')
                    @include('partials.sidebar-staff')
                @elseif (Auth::user()->role == 'hr')
                    @include('partials.sidebar-hr')
                @elseif (Auth::user()->role == 'am-supervisor')
                    @include('partials.sidebar-am-supervisor')
                @endif
            </ul>
        </div>
        <!-- Sidebar -->
        <div class="clearfix"></div>
    </div>
    <!-- Sidebar -left -->
</div>
<!-- Left Sidebar End -->
