<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Penyesuaian Stok - Custom Pare</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;600&display=swap" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        body {
            font-family: 'Raleway', sans-serif;
        }
    </style>
</head>

<body class="bg-gray-100">
    <div class="flex">
        <x-navbar-kepala-toko></x-navbar-kepala-toko>
        <div class="flex-1">
            <x-navbar-top-kepala-toko></x-navbar-top-kepala-toko>
            <div class="p-4 lg:p-8">
                <!-- Content Start -->
                <div class="px-6 py-8">
                    <div class="mb-8">
                        <a href="{{ route('kepala-toko.inventory.stock-adjustments.index') }}"
                            class="text-blue-600 hover:text-blue-800 font-medium inline-flex items-center gap-2 mb-2">
                            <i class="bi bi-arrow-left"></i> Kembali ke Daftar
                        </a>
                        <div class="flex items-start justify-between">
                            <div>
                                <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-3">
                                    Detail Penyesuaian #{{ $adjustment->adjustment_number }}
                                    @if($adjustment->type === 'in')
                                        <span
                                            class="px-2.5 py-1 rounded-full text-xs font-bold bg-green-100 text-green-700 uppercase">Input
                                            Stock (Masuk)</span>
                                    @else
                                        <span
                                            class="px-2.5 py-1 rounded-full text-xs font-bold bg-red-100 text-red-700 uppercase">Adjustment
                                            Out (Keluar)</span>
                                    @endif
                                </h1>
                                <p class="text-sm text-gray-500 mt-1">Dibuat pada
                                    {{ \Carbon\Carbon::parse($adjustment->created_at)->format('d F Y, H:i') }} oleh
                                    <strong>{{ $adjustment->user->name ?? 'Unknown' }}</strong></p>
                            </div>
                            <div>
                                <button onclick="window.print()"
                                    class="px-4 py-2 border border-gray-300 rounded-lg text-gray-600 hover:bg-gray-50 flex items-center gap-2">
                                    <i class="bi bi-printer"></i> Cetak
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Info Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                        <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm">
                            <h6 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Tanggal Adjustment
                            </h6>
                            <p class="text-lg font-semibold text-gray-800">
                                {{ \Carbon\Carbon::parse($adjustment->date)->format('d M Y') }}</p>
                        </div>
                        <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm">
                            <h6 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Alasan</h6>
                            <p class="text-lg font-semibold text-gray-800">{{ $adjustment->reason }}</p>
                        </div>
                        <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm">
                            <h6 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Total Item</h6>
                            <p class="text-lg font-semibold text-gray-800">{{ $adjustment->items->count() }} Produk</p>
                        </div>
                    </div>

                    @if($adjustment->notes)
                        <div class="bg-blue-50 border border-blue-100 rounded-xl p-5 mb-8">
                            <h6 class="text-xs font-bold text-blue-500 uppercase tracking-wider mb-1">Catatan Tambahan</h6>
                            <p class="text-blue-800">{{ $adjustment->notes }}</p>
                        </div>
                    @endif

                    <!-- Items Table -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                            <h3 class="font-bold text-gray-900">Rincian Barang</h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="text-xs text-gray-500 uppercase border-b border-gray-100">
                                        <th class="px-6 py-3 font-semibold">Produk</th>
                                        <th class="px-6 py-3 font-semibold text-center w-32">SKU</th>
                                        <th class="px-6 py-3 font-semibold text-center w-32">Kuantitas</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50">
                                    @foreach($adjustment->items as $item)
                                        <tr>
                                            <td class="px-6 py-4">
                                                <p class="font-medium text-gray-900">{{ $item->product_name }}</p>
                                                @if($item->product)
                                                    <a href="{{ route('kepala-toko.product.edit', $item->product_id) }}"
                                                        class="text-xs text-blue-500 hover:underline">Lihat Produk <i
                                                            class="bi bi-box-arrow-up-right text-[10px]"></i></a>
                                                @else
                                                    <span class="text-xs text-red-400 italic">(Produk Terhapus)</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 text-center font-mono text-gray-600">
                                                {{ $item->sku }}
                                            </td>
                                            <td class="px-6 py-4 text-center">
                                                @if($adjustment->type === 'in')
                                                    <span class="font-bold text-green-600">+{{ $item->qty }}</span>
                                                @else
                                                    <span class="font-bold text-red-600">-{{ $item->qty }}</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <!-- Content End -->
            </div>
        </div>
    </div>
</body>

</html>