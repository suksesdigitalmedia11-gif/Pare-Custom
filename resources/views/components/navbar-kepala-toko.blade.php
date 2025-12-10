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
        
        .sidebar-transition {
            transition: all 0.3s ease-in-out;
        }
        
        .nav-item {
            position: relative;
            transition: all 0.2s ease;
        }
        
        .nav-item.active {
            background: linear-gradient(135deg, #3B82F6 0%, #1D4ED8 100%);
            color: white;
        }
        
        .nav-item.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 4px;
            height: 60%;
            background: white;
            border-radius: 0 4px 4px 0;
        }
        
        .dropdown-transition {
            transition: max-height 0.3s ease-in-out;
        }
        
        /* Custom scrollbar for sidebar */
        .sidebar-scroll::-webkit-scrollbar {
            width: 4px;
        }
        
        .sidebar-scroll::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        
        .sidebar-scroll::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 10px;
        }
        
        .sidebar-scroll::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }
    </style>
</head>
<body>
    <!-- Mobile Overlay -->
    <div id="sidebar-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-30 lg:hidden hidden" onclick="toggleSidebar()"></div>

    <!-- Sidebar -->
    <div
        id="sidebar"
        class="fixed lg:sticky top-0 left-0 w-80 lg:w-72 bg-white h-screen flex flex-col sidebar-transition transform -translate-x-full lg:translate-x-0 shadow-xl z-40 border-r border-gray-200"
    >
        <!-- Sidebar Header -->
        <div class="flex items-center justify-between p-6 border-b border-gray-200 bg-white">
            <div class="flex items-center space-x-3">
                <img 
                    src="{{ asset('https://parecustom.com/public/assets/logo.png') }}" 
                    alt="Pare Custom" 
                    class="w-10 h-10 object-contain"
                    onerror="this.src='https://parecustom.com/public/assets/logo.png"
                >
                <div>
                    <h1 class="text-xl font-bold text-gray-900">PareCustom</h1>
                    <p class="text-xs text-gray-500">Kepala Toko Panel</p>
                </div>
            </div>
            
            <!-- Close Button (Mobile) -->
            <button
                class="lg:hidden p-2 rounded-lg hover:bg-gray-100 text-gray-400 hover:text-gray-600 transition-colors"
                onclick="toggleSidebar()"

                <i class="bi bi-x-lg text-lg"></i>
            </button>
        </div>

        <!-- User Info -->
        <div class="p-4 border-b border-gray-200 bg-gray-50">
            <div class="flex items-center space-x-3">
                @php
                    $avatarPath = auth()->user()->avatar 
                        ? asset('storage/avatars/' . auth()->user()->avatar) 
                        : 'https://placehold.co/40x40/6B7280/FFFFFF?text=' . strtoupper(substr(auth()->user()->name, 0, 1));
                @endphp
                <img 
                    src="{{ $avatarPath }}" 
                    alt="{{ auth()->user()->name }}" 
                    class="w-10 h-10 rounded-full border-2 border-white shadow-sm object-cover"
                >
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-gray-900 truncate">{{ auth()->user()->name }}</p>
                    <p class="text-xs text-gray-500 capitalize">{{ auth()->user()->usertype }}</p>
                </div>
            </div>
        </div>

        <!-- Navigation Menu -->
        <div class="flex-1 overflow-y-auto sidebar-scroll py-4">
            <nav class="space-y-1 px-4">
                <!-- Dashboard -->
                <a 
                    href="{{ route('kepala-toko.dashboard') }}" 
                    class="nav-item flex items-center space-x-3 px-4 py-3 rounded-xl text-gray-700 hover:bg-blue-50 hover:text-blue-700 group transition-all"
                >
                    <i class="bi bi-house-door text-lg text-gray-400 group-hover:text-blue-600"></i>
                    <span class="font-medium">Dashboard</span>
                </a>

                <!-- Katalog Dropdown -->
                <div class="nav-item">
                    <button
                        onclick="toggleDropdown(this)"
                        class="flex items-center justify-between w-full px-4 py-3 rounded-xl text-gray-700 hover:bg-blue-50 hover:text-blue-700 group transition-all"
                    >
                        <div class="flex items-center space-x-3">
                            <i class="bi bi-grid text-lg text-gray-400 group-hover:text-blue-600"></i>
                            <span class="font-medium">Katalog</span>
                        </div>
                        <i class="bi bi-chevron-down text-xs text-gray-400 transition-transform"></i>
                    </button>
                    <div class="dropdown-transition overflow-hidden max-h-0">
                        <div class="ml-11 space-y-1 py-2">
                            <a 
                                href="{{ route('kepala-toko.product.index') }}" 
                                class="block px-3 py-2 rounded-lg text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-700 transition-colors"
                            >
                                Produk
                            </a>
                            <a 
                                href="{{ route('kepala-toko.category.index') }}" 
                                class="block px-3 py-2 rounded-lg text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-700 transition-colors"
                            >
                                Kategori
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Contacts -->
                <a 
                    href="{{ route('kepala-toko.contacts.index') }}" 
                    class="nav-item flex items-center space-x-3 px-4 py-3 rounded-xl text-gray-700 hover:bg-blue-50 hover:text-blue-700 group transition-all"
                >
                    <i class="bi bi-people text-lg text-gray-400 group-hover:text-blue-600"></i>
                    <span class="font-medium">Customer & Supplier</span>
                </a>

                <!-- Inventory -->
                <a 
                    href="{{ route('kepala-toko.inventory.index') }}" 
                    class="nav-item flex items-center space-x-3 px-4 py-3 rounded-xl text-gray-700 hover:bg-blue-50 hover:text-blue-700 group transition-all"
                >
                    <i class="bi bi-clipboard-data text-lg text-gray-400 group-hover:text-blue-600"></i>
                    <span class="font-medium">Inventory</span>
                </a>

                <!-- Penjualan Dropdown -->
                <div class="nav-item">
                    <button
                        onclick="toggleDropdown(this)"
                        class="flex items-center justify-between w-full px-4 py-3 rounded-xl text-gray-700 hover:bg-blue-50 hover:text-blue-700 group transition-all"
                    >
                        <div class="flex items-center space-x-3">
                            <i class="bi bi-cash-coin text-lg text-gray-400 group-hover:text-blue-600"></i>
                            <span class="font-medium">Penjualan</span>
                        </div>
                        <i class="bi bi-chevron-down text-xs text-gray-400 transition-transform"></i>
                    </button>
                    <div class="dropdown-transition overflow-hidden max-h-0">
                        <div class="ml-11 space-y-1 py-2">
                            <a 
                                href="{{ route('kepala-toko.sales.index') }}" 
                                class="block px-3 py-2 rounded-lg text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-700 transition-colors"
                            >
                                Transaksi
                            </a>
                            <a 
                                href="{{ route('kepala-toko.shift.dashboard') }}" 
                                class="block px-3 py-2 rounded-lg text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-700 transition-colors"
                            >
                                Shift
                            </a>
                        </div>
                    </div>
                </div>

                     <!-- Iklan -->
                <a 
                    href="{{ route('kepala-toko.advertisement.index') }}" 
                    class="nav-item flex items-center space-x-3 px-4 py-3 rounded-xl text-gray-700 hover:bg-blue-50 hover:text-blue-700 group transition-all"
                >
                    <i class="bi bi-bar-chart-line text-lg text-gray-400 group-hover:text-blue-600"></i>
                    <span class="font-medium">Iklan</span>
                </a>

                <!-- Pembelian Dropdown -->
                <div class="nav-item">
                    <button
                        onclick="toggleDropdown(this)"
                        class="flex items-center justify-between w-full px-4 py-3 rounded-xl text-gray-700 hover:bg-blue-50 hover:text-blue-700 group transition-all"
                    >
                        <div class="flex items-center space-x-3">
                            <i class="bi bi-cart-check text-lg text-gray-400 group-hover:text-blue-600"></i>
                            <span class="font-medium">Pembelian</span>
                        </div>
                        <i class="bi bi-chevron-down text-xs text-gray-400 transition-transform"></i>
                    </button>
                    <div class="dropdown-transition overflow-hidden max-h-0">
                        <div class="ml-11 space-y-1 py-2">
                            <a 
                                href="{{ route('kepala-toko.purchases.index') }}" 
                                class="block px-3 py-2 rounded-lg text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-700 transition-colors"
                            >
                                Purchase List
                            </a>
                        </div>
                    </div>
                </div>
            </nav>
        </div>

        <!-- Logout Section -->
        <div class="p-4 border-t border-gray-200 bg-gray-50">
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button 
                    type="submit" 
                    class="flex items-center space-x-3 w-full px-4 py-3 rounded-xl text-gray-700 hover:bg-red-50 hover:text-red-700 group transition-all"
                >
                    <i class="bi bi-box-arrow-right text-lg text-gray-400 group-hover:text-red-600"></i>
                    <span class="font-medium">Logout</span>
                </button>
            </form>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebar-overlay');
            
            sidebar.classList.toggle('-translate-x-full');
            overlay.classList.toggle('hidden');
            
            // Prevent body scroll when sidebar is open on mobile
            document.body.classList.toggle('overflow-hidden', !sidebar.classList.contains('-translate-x-full'));
        }

        function toggleDropdown(button) {
            const dropdownMenu = button.nextElementSibling;
            const chevronIcon = button.querySelector('.bi-chevron-down');
            
            const isOpen = dropdownMenu.classList.contains('max-h-0');
            
            // Close all other dropdowns
            document.querySelectorAll('.dropdown-transition').forEach(menu => {
                if (menu !== dropdownMenu) {
                    menu.classList.add('max-h-0');
                    menu.previousElementSibling?.querySelector('.bi-chevron-down')?.classList.remove('rotate-180');
                }
            });
            
            // Toggle current dropdown
            if (isOpen) {
                dropdownMenu.classList.remove('max-h-0');
                dropdownMenu.style.maxHeight = dropdownMenu.scrollHeight + 'px';
                chevronIcon.classList.add('rotate-180');
            } else {
                dropdownMenu.classList.add('max-h-0');
                dropdownMenu.style.maxHeight = '0px';
                chevronIcon.classList.remove('rotate-180');
            }
        }

        // Close sidebar when clicking on nav items on mobile
        document.addEventListener('DOMContentLoaded', function() {
            const navItems = document.querySelectorAll('.nav-item a');
            navItems.forEach(item => {
                item.addEventListener('click', function() {
                    if (window.innerWidth < 1024) {
                        toggleSidebar();
                    }
                });
            });

            // Handle window resize
            window.addEventListener('resize', function() {
                if (window.innerWidth >= 1024) {
                    document.getElementById('sidebar-overlay').classList.add('hidden');
                    document.body.classList.remove('overflow-hidden');
                }
            });
        });
    </script>
</body>
</html>