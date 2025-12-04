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
                    <a href="{{ route('owner.dashboard') }}" class="text-gray-500 hover:text-blue-600 transition-colors">
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
                    <a href="{{ route('owner.notification.index') }}" 
                        id="notification-button"
                        class="p-2 rounded-lg hover:bg-gray-100 text-gray-600 transition-colors relative"
                    >
                        <i class="bi bi-bell text-lg"></i>
                        <span id="notification-badge" class="absolute -top-1 -right-1 w-3 h-3 bg-red-500 rounded-full border-2 border-white notification-pulse" style="display: none;"></span>
                    </a>
                </div>

                <!-- Profile Dropdown -->
                <div class="relative">
                    <a href="{{ route('owner.profile.index') }}"
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
                    </a>
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
              const timeElement = document.getElementById('current-time');
              if (timeElement) {
                timeElement.textContent = 
                  now.toLocaleTimeString('id-ID', { 
                    hour: '2-digit', 
                    minute: '2-digit',
                    second: '2-digit'
                  });
              }
            }
            setInterval(updateClock, 1000);
            updateClock();

        // Toggle mobile search
        function toggleMobileSearch() {
            const mobileSearch = document.getElementById('mobile-search');
            mobileSearch.classList.toggle('hidden');
        }

        // Update notification count
        document.addEventListener('DOMContentLoaded', function () {
            function updateNotificationCount() {
                fetch('/owner/notifications/unread-count')
                    .then(response => response.json())
                    .then(data => {
                        const notificationBadge = document.getElementById('notification-badge');
                        if (notificationBadge) {
                            if (data.count > 0) {
                                notificationBadge.textContent = data.count > 9 ? '9+' : data.count;
                                notificationBadge.style.display = 'block';
                            } else {
                                notificationBadge.style.display = 'none';
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching notification count:', error);
                    });
            }

            updateNotificationCount();
            setInterval(updateNotificationCount, 60000); // Update every minute
        });
    </script>
</body>
</html>
