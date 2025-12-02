<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Raleway', sans-serif;
        }
        
        .search-transition {
            transition: all 0.3s ease;
        }
        
        .notification-pulse {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        .profile-dropdown {
            transition: all 0.3s ease;
        }
    </style>
</head>
<body>
    <!-- Top Navigation -->
    <div class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-30">
        <div class="flex items-center justify-between px-4 lg:px-6 py-3">
            <!-- Left Section: Menu Button & Breadcrumb -->
            <div class="flex items-center space-x-4">
                <!-- Mobile Menu Button -->
                <button
                    onclick="toggleSidebar()"
                    class="lg:hidden p-2 rounded-lg hover:bg-gray-100 text-gray-600 transition-colors"
                >
                    <i class="bi bi-list text-xl"></i>
                </button>

                <!-- Breadcrumb -->
                <div class="hidden sm:flex items-center space-x-2 text-sm">
                    <a href="{{ route('admin.dashboard') }}" class="text-gray-500 hover:text-blue-600 transition-colors">
                        <i class="bi bi-house"></i>
                    </a>
                    <i class="bi bi-chevron-right text-gray-300 text-xs"></i>
                    <span class="text-gray-900 font-medium capitalize">
                        @php
                            $currentRoute = request()->route()->getName();
                            $routeParts = explode('.', $currentRoute);
                            $pageName = end($routeParts);
                            echo str_replace(['-', '_'], ' ', $pageName);
                        @endphp
                    </span>
                </div>
            </div>

            <!-- Center Section: Search (Desktop) -->
            <div class="hidden lg:block flex-1 max-w-md mx-8">
                <div class="relative">
                    <input
                        type="text"
                        placeholder="Cari sesuatu..."
                        class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-gray-50 search-transition"
                    >
                    <i class="bi bi-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                </div>
            </div>

            <!-- Right Section: Actions & Profile -->
            <div class="flex items-center space-x-3">
            <div class="hidden lg:flex items-center space-x-2 text-sm text-gray-600">
        <i class="bi bi-clock"></i>
        <span id="current-time"></span>
    </div>
                <!-- Mobile Search Button -->
                <button
                    onclick="toggleMobileSearch()"
                    class="lg:hidden p-2 rounded-lg hover:bg-gray-100 text-gray-600 transition-colors"
                >
                    <i class="bi bi-search text-lg"></i>
                </button>

                <!-- Notifications -->
                <div class="relative">
                    <button
                        id="notification-button"
                        class="p-2 rounded-lg hover:bg-gray-100 text-gray-600 transition-colors relative"
                    >
                        <i class="bi bi-bell text-lg"></i>
                        <span id="notification-badge" class="absolute -top-1 -right-1 w-3 h-3 bg-red-500 rounded-full border-2 border-white notification-pulse" style="display: none;"></span>
                    </button>
                    
                    <!-- Notification Dropdown -->
                    <div id="notification-dropdown" class="absolute right-0 mt-2 w-80 bg-white rounded-xl shadow-lg border border-gray-200 py-2 hidden profile-dropdown">
                        <div class="px-4 py-2 border-b border-gray-200">
                            <h3 class="font-semibold text-gray-900">Notifikasi</h3>
                        </div>
                        <div class="max-h-64 overflow-y-auto">
                            <!-- Notification Items -->
                            <div class="px-4 py-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100">
                                <div class="flex items-start space-x-3">
                                    <div class="w-2 h-2 bg-blue-500 rounded-full mt-2"></div>
                                    <div class="flex-1">
                                        <p class="text-sm text-gray-900">Order baru #SO-1234 diterima</p>
                                        <p class="text-xs text-gray-500 mt-1">2 menit yang lalu</p>
                                    </div>
                                </div>
                            </div>
                            <div class="px-4 py-3 hover:bg-gray-50 cursor-pointer">
                                <div class="flex items-start space-x-3">
                                    <div class="w-2 h-2 bg-green-500 rounded-full mt-2"></div>
                                    <div class="flex-1">
                                        <p class="text-sm text-gray-900">Pembayaran lunas untuk #SO-1233</p>
                                        <p class="text-xs text-gray-500 mt-1">1 jam yang lalu</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="px-4 py-2 border-t border-gray-200">
                            <a href="#" class="text-sm text-blue-600 hover:text-blue-700 font-medium">
                                Lihat semua notifikasi
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Profile Dropdown -->
                <div class="relative">
                    <button
                        id="profile-button"
                        class="flex items-center space-x-3 p-2 rounded-lg hover:bg-gray-100 transition-colors"
                    >
                        <div class="text-right hidden sm:block">
                            <p class="text-sm font-medium text-gray-900">{{ auth()->user()->name }}</p>
                            <p class="text-xs text-gray-500 capitalize">{{ auth()->user()->usertype }}</p>
                        </div>
                        @php
                            $avatarPath = auth()->user()->avatar 
                                ? asset('storage/avatars/' . auth()->user()->avatar) 
                                : 'https://placehold.co/32x32/6B7280/FFFFFF?text=' . strtoupper(substr(auth()->user()->name, 0, 1));
                        @endphp
                        <img 
                            src="{{ $avatarPath }}" 
                            alt="{{ auth()->user()->name }}" 
                            class="w-8 h-8 rounded-full border-2 border-gray-200 object-cover"
                        >
                        <i class="bi bi-chevron-down text-xs text-gray-400"></i>
                    </button>

                    <!-- Profile Dropdown Menu -->
                    <div id="profile-dropdown" class="absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-lg border border-gray-200 py-2 hidden profile-dropdown">
                        <a href="{{ route('profile.edit') }}" class="flex items-center space-x-3 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                            <i class="bi bi-person text-gray-400"></i>
                            <span>Profile Saya</span>
                        </a>
                        <a href="#" class="flex items-center space-x-3 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                            <i class="bi bi-gear text-gray-400"></i>
                            <span>Pengaturan</span>
                        </a>
                        <div class="border-t border-gray-200 my-1"></div>
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button 
                                type="submit" 
                                class="flex items-center space-x-3 w-full px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors"
                            >
                                <i class="bi bi-box-arrow-right"></i>
                                <span>Logout</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mobile Search Bar -->
        <div id="mobile-search" class="lg:hidden px-4 pb-3 hidden search-transition">
            <div class="relative">
                <input
                    type="text"
                    placeholder="Cari sesuatu..."
                    class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-gray-50"
                >
                <i class="bi bi-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
            </div>
        </div>
    </div>

    <script>
        // Toggle sidebar (shared function with sidebar component)
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebar-overlay');
            
            if (sidebar) {
                sidebar.classList.toggle('-translate-x-full');
            }
            if (overlay) {
                overlay.classList.toggle('hidden');
            }
            
            document.body.classList.toggle('overflow-hidden', !sidebar?.classList.contains('-translate-x-full'));
        }

        function updateClock() {
              const now = new Date();
              document.getElementById('current-time').textContent = 
                now.toLocaleTimeString('id-ID', { 
                  hour: '2-digit', 
                  minute: '2-digit',
                  second: '2-digit'
                });
            }
            setInterval(updateClock, 1000);
            updateClock();

        // Toggle mobile search
        function toggleMobileSearch() {
            const mobileSearch = document.getElementById('mobile-search');
            mobileSearch.classList.toggle('hidden');
        }

        // Toggle notification dropdown
        document.getElementById('notification-button')?.addEventListener('click', function(e) {
            e.stopPropagation();
            const dropdown = document.getElementById('notification-dropdown');
            dropdown.classList.toggle('hidden');
            
            // Close other dropdowns
            document.getElementById('profile-dropdown')?.classList.add('hidden');
        });

        // Toggle profile dropdown
        document.getElementById('profile-button')?.addEventListener('click', function(e) {
            e.stopPropagation();
            const dropdown = document.getElementById('profile-dropdown');
            dropdown.classList.toggle('hidden');
            
            // Close other dropdowns
            document.getElementById('notification-dropdown')?.classList.add('hidden');
        });

        // Close dropdowns when clicking outside
        document.addEventListener('click', function() {
            document.getElementById('notification-dropdown')?.classList.add('hidden');
            document.getElementById('profile-dropdown')?.classList.add('hidden');
        });

        // Prevent dropdowns from closing when clicking inside them
        document.querySelectorAll('#notification-dropdown, #profile-dropdown').forEach(dropdown => {
            dropdown.addEventListener('click', function(e) {
                e.stopPropagation();
            });
        });

        // Simulate notification count (you can replace this with real data)
        document.addEventListener('DOMContentLoaded', function() {
            // Show notification badge (simulate 2 notifications)
            const notificationBadge = document.getElementById('notification-badge');
            if (notificationBadge) {
                notificationBadge.style.display = 'block';
            }
        });
    </script>
</body>
</html>