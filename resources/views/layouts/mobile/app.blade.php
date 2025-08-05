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
    <meta name="theme-color" content="#007bff">
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
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
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
            background: #fff;
            border-top: 1px solid #e9ecef;
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
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 1rem;
            overflow: hidden;
        }
        
        /* Form inputs */
        .form-control-mobile {
            min-height: 44px;
            font-size: 16px;
            border-radius: 8px;
            border: 2px solid #e9ecef;
            padding: 12px 16px;
        }
        
        .form-control-mobile:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
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
            background: white;
            border: none;
            border-radius: 20px;
            margin-right: 8px;
            white-space: nowrap;
            font-weight: 500;
            color: #6c757d;
            transition: all 0.2s ease;
        }
        
        .mobile-tab.active {
            background: #007bff;
            color: white;
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
            border-top: 2px solid #007bff;
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
            color: #007bff;
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
    </style>
    
    @stack('styles')
</head>
<body>
    <!-- Mobile Header -->
    <div class="mobile-header">
        <div class="d-flex justify-content-between align-items-center">
            <h1>@yield('header-title', 'HR System')</h1>
            <div>
                @yield('header-actions')
            </div>
        </div>
        @yield('header-content')
    </div>
    
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
                    confirmButtonColor: '#007bff',
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