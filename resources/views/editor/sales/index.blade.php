<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Daftar Sales - Editor</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" />
</head>
<body class="bg-gray-100">
<div class="flex">
    <x-navbar-editor />

    <div class="flex-1 lg:w-5/6">
        <x-navbar-top-editor />

        <div class="p-4 lg:p-8 space-y-6">
            <div class="bg-white p-6 rounded-xl shadow-lg">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Filter pesanan yang membutuhkan desain (DTF & Jersey)</p>
                        <h1 class="text-2xl font-semibold text-gray-800">Daftar Sales</h1>
                    </div>
                    <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <input type="text" name="search" value="{{ $search }}" placeholder="Cari SO / customer" class="border rounded-lg px-3 py-2 text-sm border-gray-200" />
                        <select name="status" class="border rounded-lg px-3 py-2 text-sm border-gray-200">
                            @foreach($statusOptions as $value => $label)
                                <option value="{{ $value }}" @selected($statusFilter === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <div class="flex gap-2">
                            <button class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700">Terapkan</button>
                            <a href="{{ route('editor.sales.index') }}" class="px-4 py-2 bg-gray-100 text-gray-600 rounded-lg text-sm hover:bg-gray-200">Reset</a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-lg">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm divide-y divide-gray-200">
                        <thead class="bg-gray-50 text-gray-600">
                            <tr>
                                <th class="px-4 py-2 text-left">SO</th>
                                <th class="px-4 py-2 text-left">Customer</th>
                                <th class="px-4 py-2 text-center">Tgl Order</th>
                                <th class="px-4 py-2 text-center">Deadline</th>
                                <th class="px-4 py-2 text-center">Status Order</th>
                                <th class="px-4 py-2 text-center">Status Desain</th>
                                <th class="px-4 py-2 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($orders as $order)
                                @php
                                    $designItems = $order->items;
                                    $statusCounts = $designItems->groupBy('design_status')->map->count();
                                    $dominantStatus = $statusCounts->sortDesc()->keys()->first();
                                    $statusColors = [
                                        'pending' => 'bg-amber-100 text-amber-800',
                                        'in_progress' => 'bg-blue-100 text-blue-800',
                                        'waiting_customer' => 'bg-purple-100 text-purple-800',
                                        'approved' => 'bg-emerald-100 text-emerald-800',
                                        'rejected' => 'bg-red-100 text-red-800',
                                    ];
                                    $statusLabels = \App\Models\SalesOrderItem::designStatusOptions();
                                @endphp
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-2 font-semibold text-gray-800">
                                        {{ $order->so_number }}
                                    </td>
                                    <td class="px-4 py-2">
                                        <div class="font-medium text-gray-800">{{ $order->customer->name ?? 'Customer Umum' }}</div>
                                        <div class="text-xs text-gray-500">Total item desain: {{ $designItems->count() }}</div>
                                    </td>
                                    <td class="px-4 py-2 text-center text-gray-700">
                                        {{ \Carbon\Carbon::parse($order->order_date)->format('d M Y') }}
                                    </td>
                                    <td class="px-4 py-2 text-center text-gray-700">
                                        {{ optional($order->deadline)->format('d M Y') ?? '-' }}
                                    </td>
                                    <td class="px-4 py-2 text-center">
                                        <span class="px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-700 capitalize">
                                            {{ str_replace('_', ' ', $order->status) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2 text-center">
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $statusColors[$dominantStatus] ?? 'bg-gray-100 text-gray-600' }}">
                                            {{ $statusLabels[$dominantStatus] ?? $dominantStatus ?? 'pending' }}
                                        </span>
                                        <div class="text-[11px] text-gray-500 mt-1">
                                            pending: {{ $statusCounts['pending'] ?? 0 }},
                                            progress: {{ $statusCounts['in_progress'] ?? 0 }},
                                            waiting: {{ $statusCounts['waiting_customer'] ?? 0 }},
                                            approved: {{ $statusCounts['approved'] ?? 0 }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-2 text-right">
                                        <a href="{{ route('editor.sales.show', $order) }}" class="text-blue-600 hover:underline text-sm inline-flex items-center gap-1">
                                            <i class="bi bi-box-arrow-up-right"></i> Detail
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-6 text-center text-gray-400">Tidak ada sales yang membutuhkan desain.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">
                    {{ $orders->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>

