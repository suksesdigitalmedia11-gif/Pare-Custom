<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Sales Order - Pare Custom</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css" />
    <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Raleway', sans-serif;
        }

        /* Custom scrollbar */
        .custom-scrollbar::-webkit-scrollbar {
            height: 6px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 10px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }

        /* Smooth transitions */
        .smooth-transition {
            transition: all 0.3s ease;
        }

        /* Card hover effects */
        .card-hover {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .card-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        /* Status badges */
        .status-badge {
            font-size: 0.7rem;
            padding: 0.25rem 0.5rem;
            border-radius: 0.375rem;
            font-weight: 500;
            white-space: nowrap;
        }

        /* Mobile responsive table */
        @media (max-width: 768px) {
            .mobile-table-row {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 0.5rem;
                padding: 1rem;
                border-bottom: 1px solid #e5e7eb;
            }

            .mobile-table-row:hover {
                background-color: #f9fafb;
            }
        }
    </style>
</head>

<body class="bg-gray-50">
    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <x-navbar-kepala-toko />

        <!-- Main Content -->
        <div class="flex-1 lg:ml-3">
            <x-navbar-top-kepala-toko />

            <!-- Content Wrapper -->
            <div class="p-4 lg:p-8">
                <!-- Page Title -->
                <div class="bg-white p-6 rounded-xl shadow-lg mb-6">
                    <h1 class="text-2xl font-semibold text-gray-800">Daftar Sales Order</h1>
                    <p class="text-sm text-gray-500 mt-1">Pantau dan kelola data sales order.</p>
                </div>

                <!-- Filter + Button -->
                <div class="bg-white p-4 rounded-xl shadow-lg mb-6">
                    <form method="GET" action="{{ route('kepala-toko.sales.index') }}"
                        class="flex flex-col md:flex-row md:items-center md:space-x-4 space-y-4 md:space-y-0">
                        <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari SO atau customer"
                            class="border rounded px-3 py-2 w-full max-w-xs focus:outline-none focus:ring-2 focus:ring-blue-500" />

                        <select name="status"
                            class="border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Semua Status</option>
                            @foreach (['pending', 'request_kain', 'payment', 'proses_jahit', 'printing', 'diterima_toko', 'selesai'] as $s)
                                <option value="{{ $s }}" @if(request('status') === $s) selected @endif>{{ ucfirst(str_replace('_', ' ', $s)) }}</option>
                            @endforeach
                        </select>

                        <select name="payment_status"
                            class="border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Semua Status Pembayaran</option>
                            @foreach (['dp', 'lunas'] as $s)
                                <option value="{{ $s }}" @if(request('payment_status') === $s) selected @endif>{{ ucfirst($s) }}</option>
                            @endforeach
                        </select>

                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded shadow">
                            <i class="bi bi-funnel-fill mr-1"></i> Filter
                        </button>

                        @php
                            $activeShift = \App\Models\Shift::where('user_id', \Illuminate\Support\Facades\Auth::id())->whereNull('end_time')->first();
                        @endphp
                        @if($activeShift)
                            <a href="{{ route('kepala-toko.sales.create') }}"
                                class="inline-flex items-center bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 text-white px-4 lg:px-6 py-3 rounded-lg shadow-lg smooth-transition font-medium">
                                <i class="bi bi-plus-circle mr-2"></i>
                                <span class="hidden sm:inline">Buat SO Baru</span>
                                <span class="sm:hidden">Baru</span>
                            </a>
                        @else
                            <span class="inline-flex items-center bg-gray-400 text-white px-4 lg:px-6 py-3 rounded-lg shadow cursor-not-allowed font-medium">
                                <i class="bi bi-plus-circle mr-2"></i>
                                Shift Closed
                            </span>
                        @endif
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 card-hover">
                        <div class="flex items-center">
                            <div class="p-2 rounded-lg bg-blue-50 text-blue-600 mr-3">
                                <i class="bi bi-receipt text-lg"></i>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-600">Total SO</p>
                                <p class="text-xl font-bold text-gray-900">{{ $salesOrders->total() }}</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 card-hover">
                        <div class="flex items-center">
                            <div class="p-2 rounded-lg bg-green-50 text-green-600 mr-3">
                                <i class="bi bi-check-circle text-lg"></i>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-600">Selesai</p>
                                <p class="text-xl font-bold text-gray-900">
                                    {{ \App\Models\SalesOrder::where('status', 'selesai')->count() }}
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 card-hover">
                        <div class="flex items-center">
                            <div class="p-2 rounded-lg bg-yellow-50 text-yellow-600 mr-3">
                                <i class="bi bi-clock text-lg"></i>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-600">Pending</p>
                                <p class="text-xl font-bold text-gray-900">
                                    {{ \App\Models\SalesOrder::where('status', 'pending')->count() }}
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 card-hover">
                        <div class="flex items-center">
                            <div class="p-2 rounded-lg bg-purple-50 text-purple-600 mr-3">
                                <i class="bi bi-currency-dollar text-lg"></i>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-600">Lunas</p>
                                <p class="text-xl font-bold text-gray-900">
                                    {{ \App\Models\SalesOrder::where('payment_status', 'lunas')->count() }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filter Section -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 lg:p-6 mb-6 card-hover">
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                        <!-- Search and Filters -->
                        <form method="GET" action="{{ route('kepala-toko.sales.index') }}" class="flex-1">
                            <div class="grid grid-cols-1 lg:grid-cols-5 gap-4">

                                <!-- Date Range -->
                                <div class="lg:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Order</label>
                                    <div class="flex space-x-2">
                                        <input type="date" name="start_date" value="{{ request('start_date') }}" 
                                            class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                        <span class="self-center text-gray-500 text-sm">s/d</span>
                                        <input type="date" name="end_date" value="{{ request('end_date') }}" 
                                            class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    </div>
                                </div>

                                <!-- Status Filters -->
                                <div class="lg:col-span-1">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                    <select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                        <option value="">Semua Status</option>
                                        <option value="draft" @if(request('status') === 'draft') selected @endif>Draft</option>
                                        @foreach (['pending', 'request_kain', 'proses_jahit', 'jadi', 'diterima_toko', 'di proses', 'selesai'] as $s)
                                            <option value="{{ $s }}" @if(request('status') === $s) selected @endif>
                                                {{ ucfirst(str_replace('_', ' ', $s)) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- Payment Status -->
                                <div class="lg:col-span-1">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Status Bayar</label>
                                    <select name="payment_status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                        <option value="">Semua</option>
                                        @foreach (['dp', 'lunas'] as $s)
                                            <option value="{{ $s }}" @if(request('payment_status') === $s) selected @endif>
                                                {{ ucfirst($s) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex flex-wrap gap-2 mt-4">
                                <button type="submit" 
                                    class="inline-flex items-center bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium smooth-transition">
                                    <i class="bi bi-funnel-fill mr-2"></i> Terapkan Filter
                                </button>

                                <a href="{{ route('kepala-toko.sales.index') }}" 
                                    class="inline-flex items-center bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg text-sm font-medium smooth-transition">
                                    <i class="bi bi-arrow-clockwise mr-2"></i> Reset
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Table Section -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden card-hover">
                    <!-- Desktop Table -->
                    <div class="hidden md:block">
                        <div class="overflow-x-auto custom-scrollbar">
                            <table class="w-full">
                                <thead class="bg-gray-50 border-b border-gray-200">
                                    <tr>
                                        <th class="px-4 lg:px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">SO Number</th>
                                        <th class="px-4 lg:px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                            <div class="flex items-center">
                                                Tanggal Order
                                                <i class="bi bi-arrow-down ml-1 text-blue-500 text-xs"></i>
                                            </div>
                                        </th>
                                        <th class="px-4 lg:px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Customer</th>
                                        <th class="px-4 lg:px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                                        <th class="px-4 lg:px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Total</th>
                                        <th class="px-4 lg:px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Dibayar</th>
                                        <th class="px-4 lg:px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Sisa</th>
                                        <th class="px-4 lg:px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Status Bayar</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    @forelse ($salesOrders as $so)
                                        <tr class="hover:bg-gray-50 smooth-transition cursor-pointer group"
                                            onclick="window.location='{{ route('kepala-toko.sales.show', array_merge(['salesOrder' => $so->id], request()->query())) }}'">
                                            <td class="px-4 lg:px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900 group-hover:text-blue-600">{{ $so->so_number }}</div>
                                            </td>
                                            <td class="px-4 lg:px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900">{{ \Carbon\Carbon::parse($so->order_date)->format('d/m/Y') }}</div>
                                                <div class="text-xs text-gray-500">{{ $so->created_at->format('H:i') }}</div>
                                            </td>
                                            <td class="px-4 lg:px-6 py-4">
                                                <div class="text-sm text-gray-900">{{ $so->customer ? $so->customer->name : 'Umum' }}</div>
                                            <div class="text-xs text-gray-500">{{ $so->order_type === 'jahit_sendiri' ? 'Jahit' : 'Beli Jadi' }}</div>
                                            </td>
                                            <td class="px-4 lg:px-6 py-4 whitespace-nowrap">
                                                <span class="status-badge 
                                                    @if($so->status === 'selesai') bg-green-100 text-green-800
                                                    @elseif($so->status === 'di proses') bg-yellow-100 text-yellow-800
                                                    @elseif($so->status === 'pending') bg-blue-100 text-blue-800
                                                    @elseif($so->status === 'draft') bg-gray-100 text-gray-800
                                                    @else bg-orange-100 text-orange-800 @endif">
                                                    {{ ucfirst(str_replace('_', ' ', $so->status)) }}
                                                </span>
                                            </td>
                                            <td class="px-4 lg:px-6 py-4 whitespace-nowrap text-right">
                                                <div class="text-sm font-medium text-gray-900">Rp {{ number_format($so->grand_total, 0, ',', '.') }}</div>
                                            </td>
                                            <td class="px-4 lg:px-6 py-4 whitespace-nowrap text-right">
                                                <div class="text-sm font-medium text-green-600">Rp {{ number_format($so->paid_total, 0, ',', '.') }}</div>
                                            </td>
                                            <td class="px-4 lg:px-6 py-4 whitespace-nowrap text-right">
                                                <div class="text-sm font-medium @if($so->remaining_amount > 0) text-red-600 @else text-green-600 @endif">
                                                    Rp {{ number_format($so->remaining_amount, 0, ',', '.') }}
                                                </div>
                                            </td>
                                            <td class="px-4 lg:px-6 py-4 whitespace-nowrap text-right">
                                                <span class="status-badge 
                                                    @if($so->payment_status === 'lunas') bg-green-100 text-green-800
                                                    @else bg-yellow-100 text-yellow-800 @endif">
                                                    {{ ucfirst($so->payment_status) }}
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="px-4 lg:px-6 py-8 text-center">
                                                <div class="text-gray-500">
                                                    <i class="bi bi-inbox text-4xl mb-2"></i>
                                                    <p class="text-lg font-medium">Tidak ada data</p>
                                                    <p class="text-sm">Tidak ada sales order yang ditemukan</p>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Mobile Cards -->
                    <div class="md:hidden">
                        @forelse ($salesOrders as $so)
                            <div class="border-b border-gray-200 p-4 hover:bg-gray-50 smooth-transition cursor-pointer"
                                onclick="window.location='{{ route('kepala-toko.sales.show', array_merge(['salesOrder' => $so->id], request()->query())) }}'">
                                <div class="flex justify-between items-start mb-2">
                                    <div>
                                        <div class="font-medium text-gray-900">{{ $so->so_number }}</div>
                                        <div class="text-sm text-gray-500">{{ \Carbon\Carbon::parse($so->order_date)->format('d/m/Y H:i') }}</div>
                                    </div>
                                    <div class="text-right">
                                        <span class="status-badge 
                                            @if($so->status === 'selesai') bg-green-100 text-green-800
                                            @elseif($so->status === 'di proses') bg-yellow-100 text-yellow-800
                                            @elseif($so->status === 'pending') bg-blue-100 text-blue-800
                                            @elseif($so->status === 'draft') bg-gray-100 text-gray-800
                                            @else bg-orange-100 text-orange-800 @endif">
                                            {{ ucfirst(str_replace('_', ' ', $so->status)) }}
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="text-sm text-gray-600 mb-2">{{ $so->customer ? $so->customer->name : 'Umum' }}</div>
                                
                                <div class="grid grid-cols-3 gap-2 text-sm">
                                    <div>
                                        <div class="text-gray-500">Total</div>
                                        <div class="font-medium">Rp {{ number_format($so->grand_total, 0, ',', '.') }}</div>
                                    </div>
                                    <div>
                                        <div class="text-gray-500">Dibayar</div>
                                        <div class="font-medium text-green-600">Rp {{ number_format($so->paid_total, 0, ',', '.') }}</div>
                                    </div>
                                    <div>
                                        <div class="text-gray-500">Sisa</div>
                                        <div class="font-medium @if($so->remaining_amount > 0) text-red-600 @else text-green-600 @endif">
                                            Rp {{ number_format($so->remaining_amount, 0, ',', '.') }}
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mt-2 flex justify-between items-center">
                                    <span class="text-xs text-gray-500">{{ $so->order_type === 'jahit_sendiri' ? 'Jahit Sendiri' : 'Beli Jadi' }}</span>
                                    <span class="status-badge 
                                        @if($so->payment_status === 'lunas') bg-green-100 text-green-800
                                        @else bg-yellow-100 text-yellow-800 @endif">
                                        {{ ucfirst($so->payment_status) }}
                                    </span>
                                </div>
                            </div>
                        @empty
                            <div class="p-8 text-center text-gray-500">
                                <i class="bi bi-inbox text-4xl mb-2"></i>
                                <p class="text-lg font-medium">Tidak ada data</p>
                                <p class="text-sm">Tidak ada sales order yang ditemukan</p>
                            </div>
                        @endforelse
                    </div>

                    <!-- Pagination -->
                    @if($salesOrders->hasPages())
                        <div class="bg-gray-50 px-4 lg:px-6 py-3 border-t border-gray-200">
                            <div class="flex items-center justify-between">
                                <div class="text-sm text-gray-700">
                                    Menampilkan {{ $salesOrders->firstItem() ?? 0 }} - {{ $salesOrders->lastItem() ?? 0 }} dari {{ $salesOrders->total() }} hasil
                                </div>
                                <div class="flex space-x-2">
                                    {{ $salesOrders->withQueryString()->links() }}
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <script>
        // Enhanced mobile experience
        document.addEventListener('DOMContentLoaded', function() {
            // Add loading states
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                form.addEventListener('submit', function() {
                    const submitBtn = this.querySelector('button[type="submit"]');
                    if (submitBtn) {
                        submitBtn.innerHTML = '<i class="bi bi-hourglass-split mr-2"></i>Memproses...';
                        submitBtn.disabled = true;
                    }
                });
            });

            // Enhanced touch experience for mobile
            if ('ontouchstart' in window) {
                document.querySelectorAll('.cursor-pointer').forEach(element => {
                    element.style.cursor = 'pointer';
                });
            }
        });
    </script>
</body>
</html>
