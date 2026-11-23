<nav class="bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16 items-center">
            <div class="flex items-center">
                <a href="{{ url('/') }}" class="font-semibold text-lg text-gray-800 dark:text-gray-200">
                    {{ config('app.name', 'Pare Custom POS') }}
                </a>
            </div>
            <div class="flex items-center gap-4">
                @auth
                    <span class="text-sm text-gray-600 dark:text-gray-400">{{ auth()->user()->name }}</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-sm font-medium text-gray-700 dark:text-gray-300 underline hover:no-underline">
                            Keluar
                        </button>
                    </form>
                @endauth
            </div>
        </div>
    </div>
</nav>
