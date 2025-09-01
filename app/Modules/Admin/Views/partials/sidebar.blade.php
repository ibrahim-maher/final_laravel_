<!-- Sidebar -->
<div id="adminSidebar" class="fixed top-0 left-0 w-72 h-screen bg-gradient-to-b from-primary via-slate-800 to-slate-900 text-white transform transition-all duration-300 ease-in-out z-50 shadow-2xl lg:translate-x-0 -translate-x-full">
    
    <!-- Brand Section -->
    <div class="flex items-center justify-center p-6 border-b border-white/10">
        <div class="flex items-center space-x-3">
            <div class="w-12 h-12 bg-gradient-to-r from-secondary to-accent rounded-xl flex items-center justify-center shadow-lg animate-bounce-soft">
                <i class="fas fa-fire text-white text-xl"></i>
            </div>
            <div class="sidebar-brand-text transition-all duration-300">
                <h1 class="text-xl font-bold bg-gradient-to-r from-white to-gray-200 bg-clip-text text-transparent">
                    Firebase Admin
                </h1>
                <p class="text-xs text-blue-200">Management Panel</p>
            </div>
        </div>
    </div>
    
    <!-- Navigation -->
    <nav class="flex-1 py-6 px-4 space-y-2 overflow-y-auto">
        <!-- Main Section -->
        <div class="mb-6">
            <h3 class="text-xs font-semibold text-blue-200 uppercase tracking-wider mb-3 px-3">
                <i class="fas fa-home mr-2"></i>Main
            </h3>
            <div class="space-y-1">
                <a href="{{ route('admin.dashboard') }}" 
                   class="sidebar-nav-link flex items-center px-4 py-3 text-white/80 hover:text-white hover:bg-white/10 rounded-lg transition-all duration-200 group {{ request()->routeIs('admin.dashboard') ? 'bg-gradient-to-r from-primary to-secondary text-white shadow-lg' : '' }}">
                    <i class="fas fa-tachometer-alt w-5 mr-3 group-hover:scale-110 transition-transform duration-200"></i>
                    <span>Dashboard</span>
                    @if(request()->routeIs('admin.dashboard'))
                        <div class="ml-auto w-2 h-2 bg-secondary rounded-full animate-pulse"></div>
                    @endif
                </a>
                
                <a href="#" 
                   class="sidebar-nav-link flex items-center px-4 py-3 text-white/80 hover:text-white hover:bg-white/10 rounded-lg transition-all duration-200 group">
                    <i class="fas fa-chart-line w-5 mr-3 group-hover:scale-110 transition-transform duration-200"></i>
                    <span>Analytics</span>
                </a>
            </div>
        </div>
        
        <!-- User Management Section -->
        <div class="mb-6">
            <h3 class="text-xs font-semibold text-blue-200 uppercase tracking-wider mb-3 px-3">
                <i class="fas fa-users mr-2"></i>User Management
            </h3>
            <div class="space-y-1">
                <a href="{{ route('admin.users') }}" 
                   class="sidebar-nav-link flex items-center px-4 py-3 text-white/80 hover:text-white hover:bg-white/10 rounded-lg transition-all duration-200 group {{ request()->routeIs('admin.users') ? 'bg-gradient-to-r from-primary to-secondary text-white shadow-lg' : '' }}">
                    <i class="fas fa-users w-5 mr-3 group-hover:scale-110 transition-transform duration-200"></i>
                    <span>All Users</span>
                </a>
                
                <a href="#" 
                   class="sidebar-nav-link flex items-center px-4 py-3 text-white/80 hover:text-white hover:bg-white/10 rounded-lg transition-all duration-200 group">
                    <i class="fas fa-user-plus w-5 mr-3 group-hover:scale-110 transition-transform duration-200"></i>
                    <span>Add New User</span>
                </a>
            </div>
        </div>
        
        <!-- System Section -->
        <div class="mb-6">
            <h3 class="text-xs font-semibold text-blue-200 uppercase tracking-wider mb-3 px-3">
                <i class="fas fa-server mr-2"></i>System
            </h3>
            <div class="space-y-1">
                <a href="{{ route('admin.settings') }}" 
                   class="sidebar-nav-link flex items-center px-4 py-3 text-white/80 hover:text-white hover:bg-white/10 rounded-lg transition-all duration-200 group {{ request()->routeIs('admin.settings') ? 'bg-gradient-to-r from-primary to-secondary text-white shadow-lg' : '' }}">
                    <i class="fas fa-cog w-5 mr-3 group-hover:scale-110 transition-transform duration-200"></i>
                    <span>Settings</span>
                </a>
                
                <a href="#" 
                   class="sidebar-nav-link flex items-center px-4 py-3 text-white/80 hover:text-white hover:bg-white/10 rounded-lg transition-all duration-200 group">
                    <i class="fas fa-database w-5 mr-3 group-hover:scale-110 transition-transform duration-200"></i>
                    <span>Firebase Console</span>
                </a>
            </div>
        </div>
    </nav>
    
    <!-- Sidebar Footer -->
    <div class="p-4 border-t border-white/10">
        <div class="flex items-center space-x-3 p-3 bg-white/5 rounded-xl hover:bg-white/10 transition-all duration-200 cursor-pointer group">
            <img src="https://ui-avatars.com/api/?name={{ urlencode(session('firebase_user.email', 'Admin')) }}&size=40&background=FFA500&color=ffffff" 
                 alt="User" class="w-10 h-10 rounded-full border-2 border-secondary/50 group-hover:border-secondary transition-colors duration-200">
            <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-white truncate">{{ session('firebase_user.email', 'Admin User') }}</p>
                <p class="text-xs text-blue-200 truncate">Administrator</p>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-white/50 hover:text-red-400 transition-colors duration-200">
                    <i class="fas fa-sign-out-alt"></i>
                </button>
            </form>
        </div>
    </div>
</div>