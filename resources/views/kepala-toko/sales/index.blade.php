<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Sales Order - Pare Custom</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css" />
    <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Raleway', sans-serif;
        }

        .nav-text {
            position: relative;
            display: inline-block;
        }

        .nav-text::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: -2px;
            left: 0;
            background-color: #e17f12;
            transition: width 0.2s ease-in-out;
        }

        .hover-link:hover .nav-text::after {
            width: 100%;
        }
    </style>
</head>

<body class="bg-gray-100">
    <div class="flex">
        <!-- Sidebar -->
        <x-navbar-kepala-toko />

        <!-- Main Content -->
        <div class="flex-1 lg:w-5/6">
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
                                class="ml-auto bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded shadow">
                                <i class="bi bi-plus-circle mr-1"></i> Buat SO Baru
                            </a>
                        @else
                            <span class="ml-auto bg-gray-400 text-white px-4 py-2 rounded shadow cursor-not-allowed">
                                <i class="bi bi-plus-circle mr-1"></i> Shift Closed
                            </span>
                        @endif
                    </form>
                </div>

                <!-- Table -->
                <div class="bg-white p-6 rounded-xl shadow-lg">
                    <div class="overflow-x-auto">
                        <table class="w-full table-auto border-collapse">
                            <thead>
                                <tr class="bg-gray-50 text-left text-sm font-semibold text-gray-600 border-b">
                                    <th class="px-4 py-2">SO Number</th>
                                    <th class="px-4 py-2">Tanggal Order</th>
                                    <th class="px-4 py-2">Customer</th>
                                    <th class="px-4 py-2">Status</th>
                                    <th class="px-4 py-2 text-right">Total</th>
                                    <th class="px-4 py-2 text-right">Total Dibayar</th>
                                    <th class="px-4 py-2 text-right">Sisa</th>
                                    <th class="px-4 py-2 text-right">Status Pembayaran</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($salesOrders as $so)
                                    <tr class="border-b hover:bg-gray-50 cursor-pointer"
                                        onclick="window.location='{{ route('kepala-toko.sales.show', $so) }}'"
                                        data-id="{{ $so->id }}">
                                        <td class="px-4 py-2">{{ $so->so_number }}</td>
                                        <td class="px-4 py-2">
                                            {{ \Carbon\Carbon::parse($so->order_date)->format('d/m/Y') }}
                                        </td>
                                        <td class="px-4 py-2">{{ $so->customer?->name ?? 'Umum' }}</td>
                                        <td class="px-4 py-2">
                                            <span class="inline-block px-2 py-1 text-xs font-medium rounded-full
                                                @if($so->status === 'selesai') bg-green-100 text-green-600
                                                @elseif($so->status === 'payment') bg-yellow-100 text-yellow-600
                                                @elseif($so->status === 'pending') bg-blue-100 text-blue-600
                                                @else bg-gray-100 text-gray-600 @endif">
                                                {{ ucfirst(str_replace('_', ' ', $so->status)) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-2 text-right">Rp
                                            {{ number_format($so->grand_total, 0, ',', '.') }}</td>
                                        <td class="px-4 py-2 text-right">
                                            <span class="text-green-600 font-medium">
                                                Rp {{ number_format($so->paid_total, 0, ',', '.') }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-2 text-right">
                                            <span class="@if($so->remaining_amount > 0) text-red-600 @else text-green-600 @endif font-medium">
                                                Rp {{ number_format($so->remaining_amount, 0, ',', '.') }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-2 text-right">
                                            <span class="inline-block px-2 py-1 text-xs font-medium rounded-full
                                                @if($so->payment_status === 'lunas') bg-green-100 text-green-600
                                                @else bg-yellow-100 text-yellow-600 @endif">
                                                {{ ucfirst($so->payment_status) }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-gray-500 px-4 py-4">Tidak ada data</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="mt-6">
                        {{ $salesOrders->withQueryString()->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('-translate-x-full');
        }
        function toggleDropdown(button) {
            const dropdownMenu = button.nextElementSibling;
            const chevronIcon = button.querySelector('.bi-chevron-down');
            dropdownMenu.classList.toggle('max-h-0');
            dropdownMenu.classList.toggle('max-h-40');
            chevronIcon.classList.toggle('rotate-180');
        }

        // Handle row click
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('tr[data-id]').forEach(row => {
                row.addEventListener('click', function (e) {
                    // Prevent click on interactive elements (e.g., links, buttons) from triggering row redirect
                    if (e.target.tagName !== 'A' && e.target.tagName !== 'BUTTON' && !e.target.closest('a, button')) {
                        window.location = this.getAttribute('onclick').match(/'([^']+)'/)[1];
                    }
                });
            });
        });
    </script>
</body>

</html>