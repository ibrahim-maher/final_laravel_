<!-- Sidebar -->
<div id="adminSidebar" class="fixed top-0 left-0 w-72 h-screen bg-gradient-to-b from-primary via-slate-800 to-slate-900 text-white transform transition-all duration-300 ease-in-out z-50 shadow-2xl lg:translate-x-0 -translate-x-full flex flex-col">

    <!-- Brand Section -->
    <div class="flex items-center justify-center p-6 border-b border-white/10 flex-shrink-0">
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
                    class="sidebar-nav-link flex items-center px-4 py-3 text-white/80 hover:text-white hover:bg-white/10 rounded-lg transition-all duration-200 group {{ request()->routeIs('admin.dashboard*') ? 'bg-white/20 text-white' : '' }}">
                    <i class="fas fa-tachometer-alt w-5 mr-3 group-hover:scale-110 transition-transform duration-200"></i>
                    <span>Dashboard</span>
                    @if(request()->routeIs('admin.dashboard*'))
                    <div class="ml-auto w-2 h-2 bg-secondary rounded-full animate-pulse"></div>
                    @endif
                </a>
            </div>
        </div>

        <!-- User Management Section -->
        <div class="mb-6">
            <h3 class="text-xs font-semibold text-blue-200 uppercase tracking-wider mb-3 px-3">
                <i class="fas fa-users mr-2"></i>User Management
            </h3>
            <div class="space-y-1">
                <a href="{{ route('user.index') }}"
                    class="sidebar-nav-link flex items-center px-4 py-3 text-white/80 hover:text-white hover:bg-white/10 rounded-lg transition-all duration-200 group {{ request()->routeIs('user.index') ? 'bg-white/20 text-white' : '' }}">
                    <i class="fas fa-users w-5 mr-3 group-hover:scale-110 transition-transform duration-200"></i>
                    <span>All Users</span>
                </a>

                <a href="{{ route('user.create') }}"
                    class="sidebar-nav-link flex items-center px-4 py-3 text-white/80 hover:text-white hover:bg-white/10 rounded-lg transition-all duration-200 group {{ request()->routeIs('user.create') ? 'bg-white/20 text-white' : '' }}">
                    <i class="fas fa-user-plus w-5 mr-3 group-hover:scale-110 transition-transform duration-200"></i>
                    <span>Add New User</span>
                </a>

                <a href="{{ route('user.statistics') }}"
                    class="sidebar-nav-link flex items-center px-4 py-3 text-white/80 hover:text-white hover:bg-white/10 rounded-lg transition-all duration-200 group {{ request()->routeIs('user.statistics') ? 'bg-white/20 text-white' : '' }}">
                    <i class="fas fa-chart-bar w-5 mr-3 group-hover:scale-110 transition-transform duration-200"></i>
                    <span>User Statistics</span>
                </a>
            </div>
        </div>

        <!-- Driver Management Section -->
        <div class="mb-6">
            <h3 class="text-xs font-semibold text-blue-200 uppercase tracking-wider mb-3 px-3">
                <i class="fas fa-car mr-2"></i>Driver Management
            </h3>
            <div class="space-y-1">
                <a href="{{ route('admin.drivers.index') }}"
                    class="sidebar-nav-link flex items-center px-4 py-3 text-white/80 hover:text-white hover:bg-white/10 rounded-lg transition-all duration-200 group {{ request()->routeIs('admin.drivers.index') ? 'bg-white/20 text-white' : '' }}">
                    <i class="fas fa-users w-5 mr-3 group-hover:scale-110 transition-transform duration-200"></i>
                    <span>All Drivers</span>
                </a>

                <a href="{{ route('admin.drivers.create') }}"
                    class="sidebar-nav-link flex items-center px-4 py-3 text-white/80 hover:text-white hover:bg-white/10 rounded-lg transition-all duration-200 group {{ request()->routeIs('admin.drivers.create') ? 'bg-white/20 text-white' : '' }}">
                    <i class="fas fa-user-plus w-5 mr-3 group-hover:scale-110 transition-transform duration-200"></i>
                    <span>Add New Driver</span>
                </a>

                <a href="{{ route('admin.drivers.statistics') }}"
                    class="sidebar-nav-link flex items-center px-4 py-3 text-white/80 hover:text-white hover:bg-white/10 rounded-lg transition-all duration-200 group {{ request()->routeIs('admin.drivers.statistics') ? 'bg-white/20 text-white' : '' }}">
                    <i class="fas fa-chart-bar w-5 mr-3 group-hover:scale-110 transition-transform duration-200"></i>
                    <span>Driver Statistics</span>
                </a>
            </div>
        </div>

        <!-- Vehicle Management Section -->
        <!-- Change this section in the sidebar: -->
        <div class="mb-6">
            <h3 class="text-xs font-semibold text-blue-200 uppercase tracking-wider mb-3 px-3">
                <i class="fas fa-car-side mr-2"></i>Vehicle Management
            </h3>
            <div class="space-y-1">
                <!-- Fixed route names to match your actual registered routes -->
                <a href="{{ route('admin.vehicles.index') }}"
                    class="sidebar-nav-link flex items-center px-4 py-3 text-white/80 hover:text-white hover:bg-white/10 rounded-lg transition-all duration-200 group {{ request()->routeIs('admin.admin.vehicles.*') ? 'bg-white/20 text-white' : '' }}">
                    <i class="fas fa-car w-5 mr-3 group-hover:scale-110 transition-transform duration-200"></i>
                    <span>All Vehicles</span>
                </a>

                <a href="{{ route('admin.vehicles.create') }}"
                    class="sidebar-nav-link flex items-center px-4 py-3 text-white/80 hover:text-white hover:bg-white/10 rounded-lg transition-all duration-200 group {{ request()->routeIs('admin.admin.vehicles.create') ? 'bg-white/20 text-white' : '' }}">
                    <i class="fas fa-plus w-5 mr-3 group-hover:scale-110 transition-transform duration-200"></i>
                    <span>Add New Vehicle</span>
                </a>Activity Management

                <a href="{{ route('admin.vehicles.statistics') }}"
                    class="sidebar-nav-link flex items-center px-4 py-3 text-white/80 hover:text-white hover:bg-white/10 rounded-lg transition-all duration-200 group {{ request()->routeIs('admin.admin.vehicles.statistics') ? 'bg-white/20 text-white' : '' }}">
                    <i class="fas fa-chart-line w-5 mr-3 group-hover:scale-110 transition-transform duration-200"></i>
                    <span>Vehicle Statistics</span>
                </a>
            </div>
        </div>
        <!-- Document Management Section -->
        <div class="mb-6">
            <h3 class="text-xs font-semibold text-blue-200 uppercase tracking-wider mb-3 px-3">
                <i class="fas fa-file-alt mr-2"></i>Document Management
            </h3>
            <div class="space-y-1">
                <a href="{{ route('admin.documents.index') }}"
                    class="sidebar-nav-link flex items-center px-4 py-3 text-white/80 hover:text-white hover:bg-white/10 rounded-lg transition-all duration-200 group {{ request()->routeIs('admin.documents.index') ? 'bg-white/20 text-white' : '' }}">
                    <i class="fas fa-folder w-5 mr-3 group-hover:scale-110 transition-transform duration-200"></i>
                    <span>All Documents</span>
                </a>



                <a href="{{ route('admin.documents.verification-queue') }}"
                    class="sidebar-nav-link flex items-center px-4 py-3 text-white/80 hover:text-white hover:bg-white/10 rounded-lg transition-all duration-200 group {{ request()->routeIs('admin.documents.verification-queue') ? 'bg-white/20 text-white' : '' }}">
                    <i class="fas fa-shield-check w-5 mr-3 group-hover:scale-110 transition-transform duration-200"></i>
                    <span>Verification Queue</span>
                </a>


            </div>
        </div>

        <!-- Ride Management Section -->
        <div class="mb-6">
            <h3 class="text-xs font-semibold text-blue-200 uppercase tracking-wider mb-3 px-3">
                <i class="fas fa-route mr-2"></i>Ride Management
            </h3>
            <div class="space-y-1">
                <a href="{{ route('admin.rides.index') }}"
                    class="sidebar-nav-link flex items-center px-4 py-3 text-white/80 hover:text-white hover:bg-white/10 rounded-lg transition-all duration-200 group {{ request()->routeIs('admin.rides.index') ? 'bg-white/20 text-white' : '' }}">
                    <i class="fas fa-map-marked-alt w-5 mr-3 group-hover:scale-110 transition-transform duration-200"></i>
                    <span>All Rides</span>
                </a>



               
            </div>
        </div>


        <!-- Complaints Section -->
        <div class="mb-6">
            <h3 class="text-xs font-semibold text-blue-200 uppercase tracking-wider mb-3 px-3">
                <i class="fas fa-exclamation-triangle mr-2"></i>Complaints
            </h3>
            <div class="space-y-1">
                <a href="{{ route('complaints.index') }}"
                    class="sidebar-nav-link flex items-center px-4 py-3 text-white/80 hover:text-white hover:bg-white/10 rounded-lg transition-all duration-200 group {{ request()->routeIs('complaints.index') ? 'bg-white/20 text-white' : '' }}">
                    <i class="fas fa-list-alt w-5 mr-3 group-hover:scale-110 transition-transform duration-200"></i>
                    <span>All Complaints</span>
                </a>


            </div>
        </div>

        <!-- Coupon Management Section -->
        <div class="mb-6">
            <h3 class="text-xs font-semibold text-blue-200 uppercase tracking-wider mb-3 px-3">
                <i class="fas fa-ticket-alt mr-2"></i>Coupon Management
            </h3>
            <div class="space-y-1">
                <a href="{{ route('coupons.index') }}"
                    class="sidebar-nav-link flex items-center px-4 py-3 text-white/80 hover:text-white hover:bg-white/10 rounded-lg transition-all duration-200 group {{ request()->routeIs('coupons.index') ? 'bg-white/20 text-white' : '' }}">
                    <i class="fas fa-tags w-5 mr-3 group-hover:scale-110 transition-transform duration-200"></i>
                    <span>All Coupons</span>
                </a>

                <a href="{{ route('coupons.create') }}"
                    class="sidebar-nav-link flex items-center px-4 py-3 text-white/80 hover:text-white hover:bg-white/10 rounded-lg transition-all duration-200 group {{ request()->routeIs('coupons.create') ? 'bg-white/20 text-white' : '' }}">
                    <i class="fas fa-plus w-5 mr-3 group-hover:scale-110 transition-transform duration-200"></i>
                    <span>Create Coupon</span>
                </a>


            </div>
        </div>
        <!-- FOQ Management Section -->
        <div class="mb-6">
            <h3 class="text-xs font-semibold text-blue-200 uppercase tracking-wider mb-3 px-3">
                <i class="fas fa-question-circle mr-2"></i>FOQ Management
            </h3>
            <div class="space-y-1">
                <a href="{{ route('foqs.index') }}"
                    class="sidebar-nav-link flex items-center px-4 py-3 text-white/80 hover:text-white hover:bg-white/10 rounded-lg transition-all duration-200 group {{ request()->routeIs('foqs.index') ? 'bg-white/20 text-white' : '' }}">
                    <i class="fas fa-list w-5 mr-3 group-hover:scale-110 transition-transform duration-200"></i>
                    <span>All FOQs</span>
                </a>


            </div>
            <!-- Commission Management Section -->
            <div class="mb-6">
                <h3 class="text-xs font-semibold text-blue-200 uppercase tracking-wider mb-3 px-3">
                    <i class="fas fa-percent mr-2"></i>Commission Management
                </h3>
                <div class="space-y-1">
                    <a href="{{ route('commissions.index') }}"
                        class="sidebar-nav-link flex items-center px-4 py-3 text-white/80 hover:text-white hover:bg-white/10 rounded-lg transition-all duration-200 group {{ request()->routeIs('commissions.*') ? 'bg-white/20 text-white' : '' }}">
                        <i class="fas fa-list w-5 mr-3 group-hover:scale-110 transition-transform duration-200"></i>
                        <span>All Commissions</span>
                    </a>
                </div>
            </div>

            <!-- Tax Settings Section -->

            <!-- Page Management Section -->
            <div class="mb-6">
                <h3 class="text-xs font-semibold text-blue-200 uppercase tracking-wider mb-3 px-3">
                    <i class="fas fa-file-alt mr-2"></i>Page Management
                </h3>
                <div class="space-y-1">
                    <a href="{{ route('pages.index') }}"
                        class="sidebar-nav-link flex items-center px-4 py-3 text-white/80 hover:text-white hover:bg-white/10 rounded-lg transition-all duration-200 group {{ request()->routeIs('pages.index') ? 'bg-white/20 text-white' : '' }}">
                        <i class="fas fa-file-alt w-5 mr-3 group-hover:scale-110 transition-transform duration-200"></i>
                        <span>All Pages</span>
                    </a>

                    <a href="{{ route('pages.create') }}"
                        class="sidebar-nav-link flex items-center px-4 py-3 text-white/80 hover:text-white hover:bg-white/10 rounded-lg transition-all duration-200 group {{ request()->routeIs('pages.create') ? 'bg-white/20 text-white' : '' }}">
                        <i class="fas fa-plus w-5 mr-3 group-hover:scale-110 transition-transform duration-200"></i>
                        <span>Add New Page</span>
                    </a>


                </div>
            </div>

         
    </nav>

    <!-- Sidebar Footer -->
    <div class="p-4 border-t border-white/10 flex-shrink-0">
        <div class="flex items-center space-x-3 p-3 bg-white/5 rounded-xl hover:bg-white/10 transition-all duration-200 cursor-pointer group">
            <img src="https://ui-avatars.com/api/?name={{ urlencode(session('firebase_user.email', 'Admin')) }}&size=40&background=FFA500&color=ffffff"
                alt="User" class="w-10 h-10 rounded-full border-2 border-secondary/50 group-hover:border-secondary transition-colors duration-200">
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-white truncate">{{ session('firebase_user.email', 'Admin User') }}</p>
                <p class="text-xs text-blue-200">Administrator</p>
            </div>
            <form method="POST" action="{{ route('logout') }}" class="inline">
                @csrf
                <button type="submit" class="text-white/60 hover:text-white transition-colors duration-200" title="Logout">
                    <i class="fas fa-sign-out-alt"></i>
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    async function clearCache(event) {
        event.preventDefault();

        if (!confirm('Are you sure you want to clear the cache?')) {
            return;
        }

        try {
            const response = await fetch('{{ route("admin.utilities.clear-cache") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            });

            const result = await response.json();

            if (response.ok && result.success) {
                alert('Cache cleared successfully!');
            } else {
                alert('Failed to clear cache: ' + (result.message || 'Unknown error'));
            }
        } catch (error) {
            console.error('Clear cache error:', error);
            alert('Error clearing cache: Connection failed');
        }
    }

    async function toggleMaintenance(event) {
        event.preventDefault();

        if (!confirm('Are you sure you want to toggle maintenance mode?')) {
            return;
        }

        try {
            const response = await fetch('{{ route("admin.utilities.maintenance-mode") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            });

            const result = await response.json();

            if (response.ok && result.success) {
                alert(result.message);
            } else {
                alert('Failed to toggle maintenance mode: ' + (result.message || 'Unknown error'));
            }
        } catch (error) {
            console.error('Maintenance mode error:', error);
            alert('Error toggling maintenance mode: Connection failed');
        }
    }
</script>