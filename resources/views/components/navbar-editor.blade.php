@php
    $menuItems = [
        [
            'label' => 'Dashboard',
            'icon' => 'bi-speedometer2',
            'route' => route('editor.dashboard'),
            'active' => request()->routeIs('editor.dashboard') && !request()->has('status'),
        ],
        [
            'label' => 'Board Desain',
            'icon' => 'bi-kanban',
            'route' => route('editor.dashboard') . '#board',
            'active' => request()->routeIs('editor.dashboard') && request()->has('status') && request('status') !== 'approved',
        ],
        [
            'label' => 'Daftar Sales',
            'icon' => 'bi-list-check',
            'route' => route('editor.sales.index'),
            'active' => request()->routeIs('editor.sales.index'),
        ],
        [
            'label' => 'Aktivitas',
            'icon' => 'bi-clock-history',
            'route' => route('editor.dashboard') . '#activity',
            'active' => request()->routeIs('editor.dashboard') && request()->get('section') === 'activity',
        ],
    ];
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Raleway', sans-serif; }
        .sidebar-transition { transition: all 0.3s ease-in-out; }
        .nav-item { position: relative; transition: all 0.2s ease; }
        .nav-item.active { background: linear-gradient(135deg, #3B82F6 0%, #1D4ED8 100%); color: white; }
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
        .sidebar-scroll::-webkit-scrollbar { width: 4px; }
        .sidebar-scroll::-webkit-scrollbar-thumb { background: #c1c1c1; border-radius: 10px; }
        .sidebar-scroll::-webkit-scrollbar-thumb:hover { background: #a8a8a8; }
    </style>
</head>
<body>
    <div id="sidebar-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-30 lg:hidden hidden" onclick="toggleSidebar()"></div>

    <div
        id="sidebar"
        class="fixed lg:sticky top-0 left-0 w-80 lg:w-72 bg-white h-screen flex flex-col sidebar-transition transform -translate-x-full lg:translate-x-0 shadow-xl z-40 border-r border-gray-200"
    >
        <div class="flex items-center justify-between p-6 border-b border-gray-200 bg-white">
            <div class="flex items-center space-x-3">
                <img src="{{ asset('https://parecustom.com/public/assets/logo.png') }}" alt="Pare Custom" class="w-10 h-10 object-contain">
                <div>
                    <h1 class="text-xl font-bold text-gray-900">PareCustom</h1>
                    <p class="text-xs text-gray-500">Editor Panel</p>
                </div>
            </div>
            <button class="lg:hidden p-2 rounded-lg hover:bg-gray-100 text-gray-400 hover:text-gray-600 transition-colors" onclick="toggleSidebar()">
                <i class="bi bi-x-lg text-lg"></i>
            </button>
        </div>

        <div class="p-4 border-b border-gray-200 bg-gray-50">
            <div class="flex items-center space-x-3">
                @php
                    $avatarPath = auth()->user()->avatar 
                        ? asset('storage/avatars/' . auth()->user()->avatar) 
                        : 'https://placehold.co/40x40/6B7280/FFFFFF?text=' . strtoupper(substr(auth()->user()->name, 0, 1));
                @endphp
                <img src="{{ $avatarPath }}" alt="{{ auth()->user()->name }}" class="w-10 h-10 rounded-full border-2 border-white shadow-sm object-cover">
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-gray-900 truncate">{{ auth()->user()->name }}</p>
                    <p class="text-xs text-gray-500 capitalize">{{ auth()->user()->usertype }}</p>
                </div>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto sidebar-scroll py-4">
            <nav class="space-y-1 px-4">
                @foreach($menuItems as $item)
                    <a href="{{ $item['route'] }}" class="nav-item flex items-center px-4 py-3 rounded-lg {{ $item['active'] ? 'active' : 'text-gray-700 hover:bg-gray-100' }}">
                        <i class="bi {{ $item['icon'] }} text-lg"></i>
                        <span class="ml-3 font-medium">{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </nav>
        </div>

        <form action="{{ route('logout') }}" method="POST" class="p-4 border-t border-gray-200 bg-white">
            @csrf
            <button type="submit" class="w-full flex items-center justify-center gap-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg py-2">
                <i class="bi bi-box-arrow-right"></i>
                Logout
            </button>
        </form>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebar-overlay');
            const isHidden = sidebar.classList.contains('-translate-x-full');
            sidebar.classList.toggle('-translate-x-full');
            overlay.classList.toggle('hidden', !isHidden);
        }
    </script>
</body>
</html>