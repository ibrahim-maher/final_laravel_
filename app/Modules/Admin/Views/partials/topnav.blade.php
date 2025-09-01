<!-- Top Navigation -->
<header class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-30">
    <div class="flex items-center justify-between px-6 py-4">
        <!-- Mobile Menu Button -->
        <button id="mobileMenuBtn" class="lg:hidden text-gray-600 hover:text-primary">
            <i class="fas fa-bars text-xl"></i>
        </button>
        
        <!-- Page Title -->
        <h1 class="text-xl font-semibold text-gray-800">
            @yield('page-title', 'Dashboard')
        </h1>
        
        <!-- Right Section -->
        <div class="flex items-center space-x-4">
            <!-- Notifications -->
            <button class="relative text-gray-600 hover:text-primary transition-colors duration-200">
                <i class="fas fa-bell text-lg"></i>
                <span class="absolute -top-1 -right-1 w-3 h-3 bg-red-500 rounded-full"></span>
            </button>
            
            <!-- User Info -->
            <div class="flex items-center space-x-3">
                <span class="text-sm text-gray-600 hidden md:block">{{ session('firebase_user.email') }}</span>
                <div class="w-8 h-8 bg-primary rounded-full flex items-center justify-center">
                    <i class="fas fa-user text-white text-xs"></i>
                </div>
            </div>
        </div>
    </div>
</header>   