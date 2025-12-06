<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Detail Desain - {{ $salesOrder->so_number }}</title>
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
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm text-gray-500">SO Number</p>
                        <h1 class="text-2xl font-semibold text-gray-800">{{ $salesOrder->so_number }}</h1>
                        <p class="text-sm text-gray-500 mt-1">
                            Customer: {{ $salesOrder->customer->name ?? 'Customer Umum' }} •
                            Tgl Order: {{ \Carbon\Carbon::parse($salesOrder->order_date)->format('d M Y') }}
                            @if($salesOrder->deadline)
                                • Deadline: {{ \Carbon\Carbon::parse($salesOrder->deadline)->format('d M Y') }}
                            @endif
                        </p>
                    </div>
                    <span class="px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-700">
                        {{ ucfirst(str_replace('_', ' ', $salesOrder->status)) }}
                    </span>
                </div>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-lg">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Item & Status Desain</h2>
                @php
                    $statusColors = [
                        'pending' => 'bg-amber-100 text-amber-800',
                        'in_progress' => 'bg-blue-100 text-blue-800',
                        'waiting_customer' => 'bg-purple-100 text-purple-800',
                        'approved' => 'bg-emerald-100 text-emerald-800',
                        'rejected' => 'bg-red-100 text-red-800',
                    ];
                    $statusLabels = \App\Models\SalesOrderItem::designStatusOptions();
                @endphp
                <div class="overflow-x-auto">
                    <table class="w-full table-auto border-collapse text-sm">
                        <thead class="bg-gray-50 text-left text-gray-600">
                            <tr>
                                <th class="px-4 py-2 border">Produk</th>
                                <th class="px-4 py-2 border text-center">Qty</th>
                                <th class="px-4 py-2 border text-center">Status Desain</th>
                                <th class="px-4 py-2 border">Brief / Catatan</th>
                                <th class="px-4 py-2 border text-center">File</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($salesOrder->items as $item)
                                @php
                                    $isDesign = $item->requires_design || in_array($item->product_type, ['dtf', 'jersey']);
                                @endphp
                                <tr class="border-b">
                                    <td class="px-4 py-2 border">
                                        <div class="font-semibold text-gray-800">{{ $item->product_name }}</div>
                                        <div class="text-xs text-gray-500">SKU: {{ $item->sku ?? '-' }}</div>
                                        @if($isDesign)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] bg-purple-100 text-purple-700 mt-1">
                                                <i class="bi bi-brush mr-1"></i> {{ strtoupper($item->product_type ?? 'design') }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2 border text-center">{{ $item->qty }}</td>
                                    <td class="px-4 py-2 border text-center">
                                        @if($isDesign)
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $statusColors[$item->design_status] ?? 'bg-gray-100 text-gray-600' }}">
                                                {{ $statusLabels[$item->design_status] ?? $item->design_status ?? 'Belum Ditetapkan' }}
                                            </span>
                                            @if($item->design_confirmed_at)
                                                <div class="text-[11px] text-gray-500 mt-1">
                                                    Approved: {{ $item->design_confirmed_at->format('d/m H:i') }}
                                                </div>
                                            @endif
                                        @else
                                            <span class="text-xs text-gray-400">Tidak butuh desain</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2 border text-xs text-gray-700">
                                        @if($item->design_brief)
                                            <div><strong>Brief:</strong> {{ \Illuminate\Support\Str::limit($item->design_brief, 120) }}</div>
                                        @endif
                                        @if($item->design_notes)
                                            <div class="mt-1"><strong>Catatan:</strong> {{ \Illuminate\Support\Str::limit($item->design_notes, 120) }}</div>
                                        @endif
                                        @if($item->design_feedback)
                                            <div class="mt-1 text-amber-700 bg-amber-50 p-2 rounded"><strong>Feedback:</strong> {{ \Illuminate\Support\Str::limit($item->design_feedback, 120) }}</div>
                                        @endif
                                        @if(!$item->design_brief && !$item->design_notes && !$item->design_feedback)
                                            <span class="text-gray-400">Belum ada catatan</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2 border text-center text-xs">
                                        @if($item->design_reference_path)
                                            <a href="{{ Storage::url($item->design_reference_path) }}" target="_blank" class="text-blue-600 hover:underline inline-flex items-center gap-1">
                                                <i class="bi bi-cloud-arrow-down"></i> Brief
                                            </a><br>
                                        @endif
                                        @if($item->design_preview_path)
                                            <a href="{{ Storage::url($item->design_preview_path) }}" target="_blank" class="text-emerald-600 hover:underline inline-flex items-center gap-1 mt-1">
                                                <i class="bi bi-eye"></i> Preview
                                            </a>
                                        @endif
                                        @unless($item->design_reference_path || $item->design_preview_path)
                                            <span class="text-gray-400">Belum ada file</span>
                                        @endunless
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-lg">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Riwayat Aktivitas</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm text-left">
                        <thead class="bg-gray-50 text-gray-600">
                            <tr>
                                <th class="px-4 py-2 border">Waktu</th>
                                <th class="px-4 py-2 border">Aksi</th>
                                <th class="px-4 py-2 border">Deskripsi</th>
                                <th class="px-4 py-2 border">Oleh</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($salesOrder->logs as $log)
                                <tr class="border-b">
                                    <td class="px-4 py-2 border">{{ \Carbon\Carbon::parse($log->created_at)->format('d/m/Y H:i') }}</td>
                                    <td class="px-4 py-2 border">{{ ucfirst(str_replace('_', ' ', $log->action)) }}</td>
                                    <td class="px-4 py-2 border">{{ $log->description }}</td>
                                    <td class="px-4 py-2 border">{{ $log->user->name ?? 'System' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-gray-400 px-4 py-4">Belum ada aktivitas</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>

