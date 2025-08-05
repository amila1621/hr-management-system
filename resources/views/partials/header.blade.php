<body>

    <!-- Begin page -->
    <div id="wrapper">

        <!-- Top Bar Start -->
        <div class="topbar">

            <!-- LOGO -->
            <div class="topbar-left">
                <a href="{{ route('dashboard') }}" class="logo">
                    <span class="logo-light">
                        <img src="{{ asset('assets/images/nutlogo.png') }}" alt="logo" height="40">
                    </span>
                </a>
            </div>

            <!-- Add this CSS in the head section or your stylesheet -->
            <style>
             

                .logo img {
                    vertical-align: middle;
                }

                .logo-light {
                    display: flex;
                    align-items: center;
                    padding: 10px;
                }

              
            </style>

            <nav class="navbar-custom">
                <ul class="navbar-right list-inline float-right mb-0">

                    <li class="dropdown notification-list list-inline-item">{{ Auth::user()->name }}</li>

                    <!-- full screen -->
                    <li class="dropdown notification-list list-inline-item d-none d-md-inline-block">
                        <a class="nav-link waves-effect" href="#" id="btn-fullscreen">
                            <i class="fas fa-expand noti-icon"></i>
                        </a>
                    </li>

                    <li class="dropdown notification-list list-inline-item" style="margin-top: 0px;">
                        <div class="dropdown notification-list nav-pro-img">
                            <a class="dropdown-toggle nav-link arrow-none waves-effect nav-user" data-toggle="dropdown"
                                href="#" role="button" aria-haspopup="false" aria-expanded="false">
                                <img src="{{ asset('assets/images/users/user-1.jpg') }}" alt="user"
                                    class="rounded-circle">
                            </a>
                            <div class="dropdown-menu dropdown-menu-right dropdown-menu-animated profile-dropdown">
                                <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                                    @csrf
                                </form>
                                <a class="dropdown-item text-danger" href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                    <i class="mdi mdi-power text-danger"></i> Logout
                                </a>
                            </div>
                            
                        </div>
                    </li>

                </ul>

                <ul class="list-inline menu-left mb-0">
                    <li class="float-left">
                        <button class="button-menu-mobile open-left waves-effect"  style="z-index: 1; margin: -14px -60px -60px -60px;">
                            <i class="mdi mdi-menu"></i>
                        </button>
                    </li>
                </ul>

            </nav>

        </div>
        <!-- Top Bar End -->

        <script>
            // Add event listeners for modal open/close
            $('#aiCalculationModal').on('show.bs.modal', function () {
                document.body.style.overflow = 'hidden';
            });

            $('#aiCalculationModal').on('hidden.bs.modal', function () {
                document.body.style.overflow = 'auto';
            });
        </script>
