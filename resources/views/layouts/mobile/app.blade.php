<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'HR System - Mobile')</title>
    
    <!-- Mobile-optimized CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#23cbe0">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="HR System">
    
    <style>
        /* Mobile-first CSS */
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
            -webkit-touch-callout: none;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
        }
        
        .mobile-header {
            background: linear-gradient(135deg, #2c3749, #323e53);
            color: #dee2e6;
            padding: 1rem;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .mobile-header h1 {
            font-size: 1.25rem;
            margin: 0;
            font-weight: 600;
        }
        
        .mobile-content {
            padding: 1rem;
            min-height: calc(100vh - 120px);
        }
        
        .mobile-footer {
            background: #2c3749;
            border-top: 1px solid #38455c;
            padding: 1rem;
            position: sticky;
            bottom: 0;
            z-index: 1000;
        }
        
        /* Touch-friendly buttons */
        .btn-mobile {
            min-height: 44px;
            padding: 12px 16px;
            font-size: 16px;
            border-radius: 8px;
            font-weight: 500;
        }
        
        /* Card styling */
        .mobile-card {
            background: #323e53;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.3);
            border: 1px solid #38455c;
            margin-bottom: 1rem;
            overflow: hidden;
            color: #dee2e6;
        }
        
        /* Form inputs */
        .form-control-mobile {
            min-height: 44px;
            font-size: 16px;
            border-radius: 8px;
            border: 2px solid #4a5568;
            background: #38455c;
            color: #dee2e6;
            padding: 12px 16px;
        }
        
        .form-control-mobile:focus {
            border-color: #23cbe0;
            box-shadow: 0 0 0 0.2rem rgba(35, 203, 224, 0.25);
        }
        
        /* Navigation tabs */
        .mobile-tabs {
            display: flex;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            margin-bottom: 1rem;
        }
        
        .mobile-tab {
            min-width: 120px;
            padding: 12px 16px;
            background: #38455c;
            border: 1px solid #4a5568;
            border-radius: 20px;
            margin-right: 8px;
            white-space: nowrap;
            font-weight: 500;
            color: #a8b5c8;
            transition: all 0.2s ease;
        }
        
        .mobile-tab.active {
            background: #23cbe0;
            color: #2c3749;
            border-color: #23cbe0;
        }
        
        /* Loading states */
        .loading {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        
        .spinner {
            width: 24px;
            height: 24px;
            border: 2px solid #f3f3f3;
            border-top: 2px solid #23cbe0;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Success/Error messages */
        .alert-mobile {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 1rem;
            font-weight: 500;
        }
        
        /* Swipe indicators */
        .swipe-indicator {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1.5rem;
            color: #23cbe0;
            opacity: 0.7;
        }
        
        .swipe-left { left: 10px; }
        .swipe-right { right: 10px; }
        
        /* Hide scrollbars but keep functionality */
        .mobile-tabs::-webkit-scrollbar {
            display: none;
        }
        
        .mobile-tabs {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
        
        /* Mobile Menu Styles */
        .mobile-menu-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1998;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        
        .mobile-menu-overlay.active {
            opacity: 1;
            visibility: visible;
        }
        
        .mobile-menu-sidebar {
            position: fixed;
            top: 0;
            left: -300px;
            width: 280px;
            height: 100%;
            background: linear-gradient(180deg, #2c3749 0%, #323e53 100%);
            z-index: 1999;
            transition: left 0.3s ease;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        
        .mobile-menu-sidebar.active {
            left: 0;
        }
        
        .mobile-menu-header {
            padding: 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            color: #dee2e6;
        }
        
        .mobile-menu-content {
            padding: 0;
            height: calc(100% - 80px);
            overflow-y: auto;
        }
        
        .mobile-menu-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .mobile-menu-section {
            padding: 1rem 1rem 0.5rem;
        }
        
        .mobile-menu-section .section-title {
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            color: #a8b5c8;
            letter-spacing: 0.05em;
            margin-bottom: 0.5rem;
        }
        
        .mobile-menu-link {
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            color: #dee2e6;
            text-decoration: none;
            transition: all 0.2s ease;
            border: none;
            background: none;
            width: 100%;
            font-size: 0.9rem;
        }
        
        .mobile-menu-link:hover,
        .mobile-menu-link:focus {
            background: rgba(35, 203, 224, 0.1);
            color: #23cbe0;
            text-decoration: none;
        }
        
        .mobile-menu-link.active {
            background: rgba(35, 203, 224, 0.15);
            color: #23cbe0;
            border-left: 3px solid #23cbe0;
        }
        
        .mobile-menu-item-collapsible .mobile-menu-link {
            position: relative;
        }
        
        .submenu-arrow {
            transition: transform 0.2s ease;
            font-size: 0.75rem;
        }
        
        .mobile-menu-item-collapsible.expanded .submenu-arrow {
            transform: rotate(90deg);
        }
        
        .mobile-submenu {
            list-style: none;
            padding: 0;
            margin: 0;
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
            background: rgba(0, 0, 0, 0.2);
        }
        
        .mobile-menu-item-collapsible.expanded .mobile-submenu {
            max-height: 300px;
        }
        
        .mobile-submenu-link {
            display: flex;
            align-items: center;
            padding: 0.5rem 1rem 0.5rem 2rem;
            color: #a8b5c8;
            text-decoration: none;
            transition: all 0.2s ease;
            font-size: 0.85rem;
        }
        
        .mobile-submenu-link:hover,
        .mobile-submenu-link:focus {
            background: rgba(35, 203, 224, 0.1);
            color: #23cbe0;
            text-decoration: none;
        }
        
        /* Prevent body scroll when menu is open */
        body.mobile-menu-open {
            overflow: hidden;
        }
    </style>
    
    @stack('styles')
</head>
<body>
    <!-- Mobile Header -->
    <div class="mobile-header">
        <div class="d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <button class="btn btn-link text-white p-0 me-3" onclick="toggleMobileMenu()" id="mobile-menu-toggle">
                    <i class="fas fa-bars fs-5"></i>
                </button>
                <h1 class="mb-0">@yield('header-title', 'HR System')</h1>
            </div>
            <div class="d-flex align-items-center">
                @yield('header-actions')
                <!-- User Info -->
                <div class="dropdown ms-2">
                    <button class="btn btn-link text-white p-1 dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle fs-5"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><span class="dropdown-item-text small">{{ Auth::user()->name }}</span></li>
                        <li><span class="dropdown-item-text small text-muted">{{ ucfirst(Auth::user()->role) }}</span></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                            <i class="fas fa-sign-out-alt me-2"></i>Logout
                        </a></li>
                    </ul>
                </div>
            </div>
        </div>
        @yield('header-content')
    </div>
    
    <!-- Mobile Menu Overlay -->
    <div class="mobile-menu-overlay" id="mobile-menu-overlay" onclick="closeMobileMenu()"></div>
    
    <!-- Mobile Menu Sidebar -->
    <div class="mobile-menu-sidebar" id="mobile-menu-sidebar">
        <div class="mobile-menu-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Menu</h5>
                <button class="btn btn-link text-white p-0" onclick="closeMobileMenu()">
                    <i class="fas fa-times fs-4"></i>
                </button>
            </div>
        </div>
        
        <div class="mobile-menu-content">
            <ul class="mobile-menu-list">
                <li class="mobile-menu-section">
                    <span class="section-title">
                        <i class="fas fa-home me-2"></i>MAIN
                    </span>
                </li>
                <li>
                    <a href="{{ route('dashboard') }}" class="mobile-menu-link">
                        <i class="fas fa-tachometer-alt me-3"></i>Dashboard
                    </a>
                </li>
                
                @if(Auth::user()->role == 'staff')
                    <li class="mobile-menu-section">
                        <span class="section-title">
                            <i class="fas fa-user me-2"></i>PERSONAL
                        </span>
                    </li>
                    
                    @if (auth()->user()->staff && auth()->user()->staff->allow_report_hours)
                        <li>
                            <a href="{{ route('staff.report-hours') }}" class="mobile-menu-link {{ request()->routeIs('staff.report-hours') ? 'active' : '' }}">
                                <i class="fas fa-clock me-3"></i>Report Work Hours
                            </a>
                        </li>
                    @endif
                    
                    <li>
                        <a href="{{ route('staff.schedule') }}" class="mobile-menu-link">
                            <i class="fas fa-calendar-alt me-3"></i>Monthly Roster
                        </a>
                    </li>
                    
                    <li>
                        <a href="{{ route('staff.hours-report') }}" class="mobile-menu-link">
                            <i class="fas fa-hourglass me-3"></i>Hours Report
                        </a>
                    </li>
                    
                    <li class="mobile-menu-item-collapsible">
                        <button class="mobile-menu-link w-100 text-start" onclick="toggleSubmenu(this)">
                            <i class="fas fa-receipt me-3"></i>Sick Leave
                            <i class="fas fa-chevron-right submenu-arrow ms-auto"></i>
                        </button>
                        <ul class="mobile-submenu">
                            <li>
                                <a href="{{ route('sick-leave.request-sick-leaves') }}" class="mobile-submenu-link">
                                    <i class="fas fa-plus me-3"></i>Request Sick Leaves
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('sick-leave.manage-sick-leaves') }}" class="mobile-submenu-link">
                                    <i class="fas fa-list me-3"></i>Sick Leave Records
                                </a>
                            </li>
                        </ul>
                    </li>
                    
                    <li class="mobile-menu-item-collapsible">
                        <button class="mobile-menu-link w-100 text-start" onclick="toggleSubmenu(this)">
                            <i class="fas fa-receipt me-3"></i>Receipts
                            <i class="fas fa-chevron-right submenu-arrow ms-auto"></i>
                        </button>
                        <ul class="mobile-submenu">
                            <li>
                                <a href="{{ route('receipts.create') }}" class="mobile-submenu-link">
                                    <i class="fas fa-plus me-3"></i>Submit Receipts
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('receipts.manage') }}" class="mobile-submenu-link">
                                    <i class="fas fa-list me-3"></i>Receipt Records
                                </a>
                            </li>
                        </ul>
                    </li>
                @endif
                
                <!-- Add more role-based menu items as needed -->
            </ul>
        </div>
    </div>
    
    <!-- Hidden logout form -->
    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
        @csrf
    </form>
    
    <!-- Success/Error Messages -->
    @if (session()->has('success'))
        <div class="alert alert-success alert-mobile">
            <i class="fas fa-check-circle me-2"></i>
            {{ session()->get('success') }}
        </div>
    @endif
    
    @if (session()->has('error'))
        <div class="alert alert-danger alert-mobile">
            <i class="fas fa-exclamation-circle me-2"></i>
            {{ session()->get('error') }}
        </div>
    @endif
    
    @if ($errors->any())
        <div class="alert alert-danger alert-mobile">
            <i class="fas fa-exclamation-triangle me-2"></i>
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif
    
    <!-- Main Content -->
    <div class="mobile-content">
        @yield('content')
    </div>
    
    <!-- Mobile Footer -->
    <div class="mobile-footer">
        @yield('footer-content')
    </div>
    
    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        // Mobile-specific JavaScript utilities
        window.MobileApp = {
            // Show loading spinner
            showLoading: function(message = 'Loading...') {
                Swal.fire({
                    title: message,
                    didOpen: () => {
                        Swal.showLoading();
                    },
                    allowOutsideClick: false,
                    showConfirmButton: false
                });
            },
            
            // Hide loading spinner
            hideLoading: function() {
                Swal.close();
            },
            
            // Show success message
            showSuccess: function(message) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: message,
                    timer: 2000,
                    showConfirmButton: false
                });
            },
            
            // Show error message
            showError: function(message) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: message,
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#dc3545'
                });
            },
            
            // Confirm action
            confirm: function(message, callback) {
                Swal.fire({
                    title: 'Are you sure?',
                    text: message,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#23cbe0',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        callback();
                    }
                });
            },
            
            // Vibrate (if supported)
            vibrate: function(pattern = [100]) {
                if (navigator.vibrate) {
                    navigator.vibrate(pattern);
                }
            }
        };
        
        // Mobile Menu Functions
        window.toggleMobileMenu = function() {
            const overlay = document.getElementById('mobile-menu-overlay');
            const sidebar = document.getElementById('mobile-menu-sidebar');
            const body = document.body;
            
            overlay.classList.add('active');
            sidebar.classList.add('active');
            body.classList.add('mobile-menu-open');
        };
        
        window.closeMobileMenu = function() {
            const overlay = document.getElementById('mobile-menu-overlay');
            const sidebar = document.getElementById('mobile-menu-sidebar');
            const body = document.body;
            
            overlay.classList.remove('active');
            sidebar.classList.remove('active');
            body.classList.remove('mobile-menu-open');
        };
        
        window.toggleSubmenu = function(button) {
            const listItem = button.closest('.mobile-menu-item-collapsible');
            const isExpanded = listItem.classList.contains('expanded');
            
            if (isExpanded) {
                listItem.classList.remove('expanded');
            } else {
                // Close other open submenus
                document.querySelectorAll('.mobile-menu-item-collapsible.expanded').forEach(item => {
                    item.classList.remove('expanded');
                });
                listItem.classList.add('expanded');
            }
            
            // Add haptic feedback
            if (navigator.vibrate) {
                navigator.vibrate(30);
            }
        };
        
        // Close menu when clicking links (not submenu toggles)
        document.addEventListener('DOMContentLoaded', function() {
            const menuLinks = document.querySelectorAll('.mobile-menu-link:not([onclick*="toggleSubmenu"]), .mobile-submenu-link');
            menuLinks.forEach(link => {
                link.addEventListener('click', function() {
                    // Only close menu if it's not a submenu toggle
                    if (!this.hasAttribute('onclick') || !this.getAttribute('onclick').includes('toggleSubmenu')) {
                        closeMobileMenu();
                    }
                });
            });
        });
        
        // Close menu on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeMobileMenu();
            }
        });
        
        // Auto-hide alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert-mobile');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.transition = 'opacity 0.5s ease';
                    alert.style.opacity = '0';
                    setTimeout(() => {
                        alert.remove();
                    }, 500);
                }, 5000);
            });
        });
    </script>
    
    @stack('scripts')
</body>
</html>