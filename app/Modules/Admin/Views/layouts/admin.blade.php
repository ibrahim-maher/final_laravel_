<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin Dashboard - Firebase Auth')</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#003366',
                        secondary: '#FFA500',
                        accent: '#ff6b35',
                        success: '#10b981',
                        warning: '#f59e0b',
                        danger: '#ef4444',
                        dark: '#1f2937',
                        light: '#f8fafc',
                    },
                    animation: {
                        'fade-in': 'fadeIn 0.5s ease-out',
                        'slide-in-left': 'slideInLeft 0.3s ease-out',
                        'slide-in-right': 'slideInRight 0.3s ease-out',
                        'bounce-soft': 'bounceSoft 0.6s ease-out',
                        'pulse-glow': 'pulseGlow 2s infinite',
                        'gradient-shift': 'gradientShift 3s ease-in-out infinite',
                    },
                    keyframes: {
                        fadeIn: {
                            '0%': { opacity: '0', transform: 'translateY(20px)' },
                            '100%': { opacity: '1', transform: 'translateY(0)' }
                        },
                        slideInLeft: {
                            '0%': { opacity: '0', transform: 'translateX(-30px)' },
                            '100%': { opacity: '1', transform: 'translateX(0)' }
                        },
                        slideInRight: {
                            '0%': { opacity: '0', transform: 'translateX(30px)' },
                            '100%': { opacity: '1', transform: 'translateX(0)' }
                        },
                        bounceSoft: {
                            '0%': { transform: 'scale(0.95)' },
                            '50%': { transform: 'scale(1.02)' },
                            '100%': { transform: 'scale(1)' }
                        },
                        pulseGlow: {
                            '0%, 100%': { boxShadow: '0 0 5px rgba(255, 165, 0, 0.5)' },
                            '50%': { boxShadow: '0 0 20px rgba(255, 165, 0, 0.8)' }
                        },
                        gradientShift: {
                            '0%, 100%': { backgroundPosition: '0% 50%' },
                            '50%': { backgroundPosition: '100% 50%' }
                        }
                    },
                    backdropBlur: {
                        xs: '2px',
                    }
                }
            }
        }
    </script>
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
        
        body {
            font-family: 'Inter', sans-serif;
        }
        
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #003366, #FFA500);
            border-radius: 10px;
        }
        
        .sidebar-nav-link {
            position: relative;
            overflow: hidden;
        }
        
        .sidebar-nav-link::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            transition: left 0.5s;
        }
        
        .sidebar-nav-link:hover::before {
            left: 100%;
        }
    </style>
    
    @stack('styles')
</head>
<body class="bg-white min-h-screen overflow-x-hidden">
    @if(config('app.debug'))
        <div class="fixed top-4 left-4 z-50 bg-red-500 text-white px-3 py-1 rounded-full text-xs font-bold uppercase">
            <i class="fas fa-bug mr-1"></i>DEBUG
        </div>
    @endif

    <!-- Include Sidebar -->
    @include('admin::partials.sidebar')

    <!-- Main Content Area -->
    <div id="adminContent" class="lg:ml-72 transition-all duration-300 min-h-screen">
        <!-- Include Top Navigation -->
        @include('admin::partials.topnav')
        
        <!-- Page Content -->
        <main class="p-6 animate-fade-in">
            @yield('content')
        </main>
    </div>
    
    @stack('scripts')
</body>
</html>