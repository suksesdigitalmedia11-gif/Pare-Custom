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
        <x-navbar-finance />

        <!-- Main Content -->
        <div class="flex-1 lg:ml-3">
            <x-navbar-top-finance />

            <!-- Content Wrapper -->
            <div class="p-4 lg:p-6">
                <!-- Header Section -->
                <div class="mb-6">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                        <div class="mb-4 sm:mb-0">
                            <h1 class="text-2xl lg:text-3xl font-bold text-gray-900">Sales Orders</h1>
                            <p class="text-gray-600 mt-1">Kelola dan pantau semua transaksi penjualan</p>
                        </div>
                        
                        @php
                            $activeShift = \App\Models\Shift::where('user_id', \Illuminate\Support\Facades\Auth::id())->whereNull('end_time')->first();
                        @endphp
                        @if($activeShift)
                            <a href="{{ route('finance.sales.create') }}"
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
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
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

                    <!-- ✅ CARD TOTAL HPP (hasil filter) -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 card-hover border-l-4 border-l-red-500">
                        <div class="flex items-center">
                            <div class="p-2 rounded-lg bg-red-50 text-red-600 mr-3">
                                <i class="bi bi-box-seam text-lg"></i>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-600">Total HPP (Filter)</p>
                                <p class="text-xl font-bold text-red-600">Rp {{ number_format($totalHpp ?? 0, 0, ',', '.') }}</p>
                                <p class="text-[10px] text-gray-400">Σ harga modal × qty</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filter Section -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 lg:p-6 mb-6 card-hover">
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                        <!-- Search and Filters -->
                        <form method="GET" action="{{ route('finance.sales.index') }}" class="flex-1">
                            <div class="grid grid-cols-1 lg:grid-cols-5 gap-4">
                                <!-- Search -->
                                <div class="lg:col-span-1">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Cari</label>
                                    <input type="text" name="q" value="{{ request('q') }}" 
                                        placeholder="SO Number / Customer"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                </div>

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

                                <a href="{{ route('finance.sales.index') }}" 
                                    class="inline-flex items-center bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg text-sm font-medium smooth-transition">
                                    <i class="bi bi-arrow-clockwise mr-2"></i> Reset
                                </a>

                                <!-- Export Button -->
                                <div class="flex gap-2 ml-auto">
                                    <a href="{{ route('finance.sales.export', request()->query()) }}" 
                                        class="inline-flex items-center bg-green-600 hover:bg-green-700 text-white px-3 py-2 rounded-lg text-sm font-medium smooth-transition">
                                        <i class="bi bi-download mr-2"></i> Export
                                    </a>
                                </div>
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
                                        <th class="px-4 lg:px-6 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Desain</th>
                                        <th class="px-4 lg:px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Total</th>
                                        <th class="px-4 lg:px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">HPP</th>
                                        <th class="px-4 lg:px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Dibayar</th>
                                        <th class="px-4 lg:px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Sisa</th>
                                        <th class="px-4 lg:px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Status Bayar</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    @forelse ($salesOrders as $so)
                                        <tr class="hover:bg-gray-50 smooth-transition cursor-pointer group"
                                            onclick="window.location='{{ route('finance.sales.show', array_merge(['salesOrder' => $so->id], request()->query())) }}'">
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
                                            <td class="px-4 lg:px-6 py-4 whitespace-nowrap text-center">
                                                @if($so->design_status)
                                                    <div class="flex flex-col items-center">
                                                        <span class="status-badge mb-1
                                                            @if($so->design_status === 'approved') bg-green-100 text-green-800
                                                            @elseif($so->design_status === 'rejected') bg-red-100 text-red-800
                                                            @elseif($so->design_status === 'pending') bg-gray-100 text-gray-800 border border-gray-300
                                                            @elseif($so->design_status === 'waiting_customer') bg-purple-100 text-purple-800
                                                            @else bg-blue-100 text-blue-800 @endif">
                                                            {{ $so->design_status === 'process' ? 'Proses' : 
                                                               ($so->design_status === 'approved' ? 'Acc Desain' : 
                                                               ($so->design_status === 'rejected' ? 'Revisi' : 
                                                               ($so->design_status === 'pending' ? 'Belum Disentuh' : 
                                                               ($so->design_status === 'waiting_customer' ? 'Tunggu Customer' : 'Dikerjakan')))) }}
                                                        </span>
                                                        @if($so->design_aging)
                                                            <span class="text-[10px] {{ $so->design_status === 'pending' ? 'text-red-500 font-semibold' : 'text-gray-400' }}">
                                                                {{ $so->design_aging }}
                                                            </span>
                                                        @endif
                                                    </div>
                                                @else
                                                    <span class="text-xs text-gray-400">-</span>
                                                @endif
                                            </td>
                                            <td class="px-4 lg:px-6 py-4 whitespace-nowrap text-right">
                                                <div class="text-sm font-medium text-gray-900">Rp {{ number_format($so->grand_total, 0, ',', '.') }}</div>
                                            </td>
                                            <td class="px-4 lg:px-6 py-4 whitespace-nowrap text-right">
                                                <div class="text-sm font-medium text-red-600">Rp {{ number_format($so->total_hpp, 0, ',', '.') }}</div>
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
                                            <td colspan="9" class="px-4 lg:px-6 py-8 text-center">
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
                                onclick="window.location='{{ route('finance.sales.show', array_merge(['salesOrder' => $so->id], request()->query())) }}'">
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
                                        <div class="text-gray-500">HPP</div>
                                        <div class="font-medium text-red-600">Rp {{ number_format($so->total_hpp, 0, ',', '.') }}</div>
                                    </div>
                                    <div>
                                        <div class="text-gray-500">Dibayar</div>
                                        <div class="font-medium text-green-600">Rp {{ number_format($so->paid_total, 0, ',', '.') }}</div>
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-2 text-sm mt-2">
                                    <div>
                                        <div class="text-gray-500">Sisa</div>
                                        <div class="font-medium @if($so->remaining_amount > 0) text-red-600 @else text-green-600 @endif">
                                            Rp {{ number_format($so->remaining_amount, 0, ',', '.') }}
                                        </div>
                                    </div>
                                    <div>
                                        <div class="text-gray-500">Est. Profit</div>
                                        <div class="font-medium {{ $so->est_profit >= 0 ? 'text-teal-600' : 'text-red-600' }}">
                                            Rp {{ number_format($so->est_profit, 0, ',', '.') }}
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mt-2 flex justify-between items-center">
                                    <div>
                                        <span class="text-xs text-gray-500 block">{{ $so->order_type === 'jahit_sendiri' ? 'Jahit Sendiri' : 'Beli Jadi' }}</span>
                                        @if($so->design_status)
                                            <div class="mt-1">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                                    @if($so->design_status === 'approved') bg-green-100 text-green-800
                                                    @elseif($so->design_status === 'rejected') bg-red-100 text-red-800
                                                    @elseif($so->design_status === 'pending') bg-gray-100 text-gray-800 border border-gray-300
                                                    @elseif($so->design_status === 'waiting_customer') bg-purple-100 text-purple-800
                                                    @else bg-blue-100 text-blue-800 @endif">
                                                    {{ $so->design_status === 'process' ? 'Proses' : 
                                                       ($so->design_status === 'approved' ? 'Acc' : 
                                                       ($so->design_status === 'rejected' ? 'Revisi' : 
                                                       ($so->design_status === 'pending' ? 'Pending' : 
                                                       ($so->design_status === 'waiting_customer' ? 'Tunggu Cust' : 'Dikerjakan')))) }}
                                                </span>
                                                @if($so->design_aging)
                                                    <span class="ml-1 text-[10px] {{ $so->design_status === 'pending' ? 'text-red-500 font-semibold' : 'text-gray-400' }}">
                                                        {{ $so->design_aging }}
                                                    </span>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
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
