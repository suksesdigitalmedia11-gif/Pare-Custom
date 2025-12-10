<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Shift Auto-Close Approval - Pare Custom</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css" />
    <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Raleway', sans-serif; }
    </style>
</head>
<body class="bg-gray-50">
<div class="flex">
    <x-navbar-finance />
    <div class="flex-1 lg:w-5/6">
        <x-navbar-top-finance />
        
        <div class="p-4 lg:p-8">
            <!-- Header -->
            <div class="bg-gradient-to-r from-orange-500 to-orange-600 text-white p-6 rounded-xl shadow-lg mb-6">
                <div class="flex justify-between items-center">
                    <div>
                        <h1 class="text-2xl font-bold mb-2">⚠️ Shift Auto-Close Approval</h1>
                        <p class="opacity-90">Kelola approval untuk shift yang di-auto-close</p>
                    </div>
                    <a href="{{ route('finance.dashboard') }}" class="bg-white text-orange-600 px-4 py-2 rounded-lg hover:bg-gray-100 transition">
                        <i class="bi bi-arrow-left mr-2"></i> Kembali ke Dashboard
                    </a>
                </div>
            </div>

            <!-- Alert Messages -->
            @if(session('success'))
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded-lg">
                    <div class="flex items-center">
                        <i class="bi bi-check-circle-fill mr-2"></i>
                        <p>{{ session('success') }}</p>
                    </div>
                </div>
            @endif

            @if(session('error'))
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded-lg">
                    <div class="flex items-center">
                        <i class="bi bi-x-circle-fill mr-2"></i>
                        <p>{{ session('error') }}</p>
                    </div>
                </div>
            @endif

            <!-- Info Box -->
            <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6 rounded-lg">
                <div class="flex items-start">
                    <i class="bi bi-info-circle-fill text-blue-600 text-xl mr-3 mt-1"></i>
                    <div>
                        <h3 class="font-semibold text-blue-800 mb-2">Informasi</h3>
                        <p class="text-sm text-blue-700">
                            Shift yang tidak ditutup pada hari sebelumnya akan otomatis ditutup oleh sistem pada pukul 00:01.
                            Setelah itu, semua akun Admin dan Kepala Toko akan diblokir login hingga Anda memberikan approval.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full table-auto">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Shift ID</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">User</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Tanggal Auto-Close</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Status</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Approved By</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Approved At</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($autoCloses as $autoClose)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-sm">
                                        <span class="text-gray-800 font-medium">
                                            #{{ $autoClose->shift_id }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        {{ $autoClose->shift->user->name ?? 'N/A' }}
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        {{ \Carbon\Carbon::parse($autoClose->auto_closed_date)->format('d/m/Y') }}
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        @if($autoClose->is_blocked)
                                            <span class="px-3 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                <i class="bi bi-lock-fill mr-1"></i> Blocked
                                            </span>
                                        @else
                                            <span class="px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                <i class="bi bi-check-circle-fill mr-1"></i> Approved
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        {{ $autoClose->approver->name ?? '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        @if($autoClose->approved_at)
                                            {{ \Carbon\Carbon::parse($autoClose->approved_at)->format('d/m/Y H:i') }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        @if($autoClose->is_blocked)
                                            <form action="{{ route('finance.shift-auto-closes.approve', $autoClose->id) }}" 
                                                  method="POST" 
                                                  class="inline"
                                                  onsubmit="return confirm('Apakah Anda yakin ingin approve shift ini? Login admin akan diaktifkan kembali.');">
                                                @csrf
                                                <button type="submit" 
                                                        class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium inline-flex items-center">
                                                    <i class="bi bi-check-circle-fill mr-2"></i>
                                                    Approve
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-gray-400 text-sm">Sudah di-approve</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                                        <i class="bi bi-inbox text-4xl mb-2 block"></i>
                                        <p>Tidak ada shift yang di-auto-close</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if($autoCloses->hasPages())
                    <div class="px-4 py-3 border-t border-gray-200">
                        {{ $autoCloses->links() }}
                    </div>
                @endif
            </div>

            <!-- Statistics -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">
                <div class="bg-white p-4 rounded-lg shadow">
                    <div class="flex items-center">
                        <div class="bg-red-100 p-3 rounded-lg mr-3">
                            <i class="bi bi-lock-fill text-red-600 text-xl"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Blocked</p>
                            <p class="text-2xl font-bold text-gray-800">
                                {{ $autoCloses->where('is_blocked', true)->count() }}
                            </p>
                        </div>
                    </div>
                </div>
                <div class="bg-white p-4 rounded-lg shadow">
                    <div class="flex items-center">
                        <div class="bg-green-100 p-3 rounded-lg mr-3">
                            <i class="bi bi-check-circle-fill text-green-600 text-xl"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Approved</p>
                            <p class="text-2xl font-bold text-gray-800">
                                {{ $autoCloses->where('is_blocked', false)->count() }}
                            </p>
                        </div>
                    </div>
                </div>
                <div class="bg-white p-4 rounded-lg shadow">
                    <div class="flex items-center">
                        <div class="bg-blue-100 p-3 rounded-lg mr-3">
                            <i class="bi bi-list-ul text-blue-600 text-xl"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Total</p>
                            <p class="text-2xl font-bold text-gray-800">
                                {{ $autoCloses->total() }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>

