<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Riwayat Log Perubahan Produk - Pare Custom</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Bootstrap CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css" />
    <!-- Font CDN -->
    <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Raleway', sans-serif; }
    </style>
</head>
<body class="bg-gray-100">
    <div class="flex">
        <!-- Sidebar -->
        <x-navbar-kepala-toko></x-navbar-kepala-toko>

        <!-- Main Content -->
        <div class="flex-1 lg:w-5/6">
            <x-navbar-top-kepala-toko></x-navbar-top-kepala-toko>

            <!-- Content Wrapper -->
            <div class="p-4 lg:p-8">
                <div class="p-6 bg-gray-100 min-h-screen">
                    <div class="max-w-7xl mx-auto">
                        
                        <!-- Header -->
                        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                            <div>
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('kepala-toko.product.index') }}" class="btn btn-outline-secondary btn-sm">
                                        <i class="bi bi-arrow-left"></i> Kembali ke Produk
                                    </a>
                                    <h1 class="text-2xl lg:text-3xl font-bold text-gray-800">Riwayat Log Perubahan Produk</h1>
                                </div>
                                <p class="text-sm text-gray-500 mt-1">
                                    Audit trail riwayat kapan produk diubah, siapa yang mengedit, serta rincian perubahan HPP, harga jual, dan SKU.
                                    @if($selectedProduct)
                                        <span class="badge bg-primary ms-1">Filter Produk: {{ $selectedProduct->name }}</span>
                                    @endif
                                </p>
                            </div>
                            <div>
                                <a href="{{ route('kepala-toko.product.export-price-update') }}" class="btn btn-warning text-dark font-medium shadow-sm">
                                    <i class="bi bi-download me-1"></i>Unduh Template Update Harga
                                </a>
                            </div>
                        </div>

                        <!-- Filter Card -->
                        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-200 mb-6">
                            <form method="GET" action="{{ route('kepala-toko.product.logs') }}" class="row g-3 items-end">
                                @if($productId)
                                    <input type="hidden" name="product_id" value="{{ $productId }}">
                                @endif
                                <div class="col-md-4">
                                    <label class="form-label text-xs font-semibold text-gray-600 uppercase">Cari Produk / SKU / Petugas</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                                        <input type="text" name="q" value="{{ $q }}" class="form-control" placeholder="Ketik nama produk, SKU, atau nama user...">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label text-xs font-semibold text-gray-600 uppercase">Sumber Perubahan</label>
                                    <select name="source" class="form-select form-select-sm">
                                        <option value="">Semua Sumber</option>
                                        <option value="import" {{ $source === 'import' ? 'selected' : '' }}>Import Excel</option>
                                        <option value="manual" {{ $source === 'manual' ? 'selected' : '' }}>Edit Manual (Web)</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label text-xs font-semibold text-gray-600 uppercase">Dari Tanggal</label>
                                    <input type="date" name="date_from" value="{{ $dateFrom }}" class="form-control form-control-sm">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label text-xs font-semibold text-gray-600 uppercase">Sampai Tanggal</label>
                                    <input type="date" name="date_to" value="{{ $dateTo }}" class="form-control form-control-sm">
                                </div>
                                <div class="col-md-2 d-flex gap-2">
                                    <button type="submit" class="btn btn-primary btn-sm flex-fill">
                                        <i class="bi bi-filter me-1"></i>Filter
                                    </button>
                                    <a href="{{ route('kepala-toko.product.logs') }}" class="btn btn-light border btn-sm" title="Reset Filter">
                                        <i class="bi bi-arrow-counterclockwise"></i>
                                    </a>
                                </div>
                            </form>
                        </div>

                        <!-- Table Card -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0 text-sm">
                                    <thead class="table-light text-secondary text-xs uppercase tracking-wider">
                                        <tr>
                                            <th class="py-3 px-4" style="width: 170px;">Waktu Perubahan</th>
                                            <th class="py-3 px-4">Produk & SKU</th>
                                            <th class="py-3 px-4" style="min-width: 170px;">Harga Modal (HPP)</th>
                                            <th class="py-3 px-4" style="min-width: 170px;">Harga Jual</th>
                                            <th class="py-3 px-4">Keterangan / Rincian</th>
                                            <th class="py-3 px-4">Diubah Oleh</th>
                                            <th class="py-3 px-4 text-center">Sumber</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        @forelse($logs as $log)
                                            <tr>
                                                <td class="py-3 px-4">
                                                    <div class="font-semibold text-gray-800">
                                                        {{ $log->changed_at ? $log->changed_at->translatedFormat('d M Y, H:i') : '-' }}
                                                    </div>
                                                    <div class="text-xs text-muted">
                                                        {{ $log->changed_at ? $log->changed_at->diffForHumans() : '' }}
                                                    </div>
                                                </td>
                                                <td class="py-3 px-4">
                                                    <div class="font-medium text-gray-900">
                                                        {{ $log->product->name ?? 'Produk Telah Dihapus' }}
                                                    </div>
                                                    <div class="text-xs text-gray-500 font-mono">
                                                        SKU: {{ $log->product->sku ?? '-' }}
                                                    </div>
                                                </td>
                                                <td class="py-3 px-4">
                                                    @if($log->old_cost_price !== null)
                                                        <div class="text-xs text-muted">Lama: Rp {{ number_format($log->old_cost_price, 0, ',', '.') }}</div>
                                                        <div class="font-semibold text-warning-emphasis">
                                                            Baru: Rp {{ number_format($log->new_cost_price, 0, ',', '.') }}
                                                        </div>
                                                        @php
                                                            $diffCost = (float)$log->new_cost_price - (float)$log->old_cost_price;
                                                        @endphp
                                                        @if(abs($diffCost) > 0.001)
                                                            <div class="text-xs {{ $diffCost > 0 ? 'text-danger' : 'text-success' }}">
                                                                <i class="bi {{ $diffCost > 0 ? 'bi-arrow-up-right' : 'bi-arrow-down-right' }}"></i>
                                                                {{ $diffCost > 0 ? '+' : '' }}Rp {{ number_format($diffCost, 0, ',', '.') }}
                                                            </div>
                                                        @endif
                                                    @else
                                                        <span class="badge bg-light text-dark border">Awal: Rp {{ number_format($log->new_cost_price, 0, ',', '.') }}</span>
                                                    @endif
                                                </td>
                                                <td class="py-3 px-4">
                                                    @if($log->new_price !== null)
                                                        @if($log->old_price !== null)
                                                            <div class="text-xs text-muted">Lama: Rp {{ number_format($log->old_price, 0, ',', '.') }}</div>
                                                            <div class="font-semibold text-success">
                                                                Baru: Rp {{ number_format($log->new_price, 0, ',', '.') }}
                                                            </div>
                                                            @php
                                                                $diffPrice = (float)$log->new_price - (float)$log->old_price;
                                                            @endphp
                                                            @if(abs($diffPrice) > 0.001)
                                                                <div class="text-xs {{ $diffPrice > 0 ? 'text-success' : 'text-danger' }}">
                                                                    <i class="bi {{ $diffPrice > 0 ? 'bi-arrow-up-right' : 'bi-arrow-down-right' }}"></i>
                                                                    {{ $diffPrice > 0 ? '+' : '' }}Rp {{ number_format($diffPrice, 0, ',', '.') }}
                                                                </div>
                                                            @endif
                                                        @else
                                                            <span class="badge bg-light text-dark border">Awal: Rp {{ number_format($log->new_price, 0, ',', '.') }}</span>
                                                        @endif
                                                    @else
                                                        <span class="text-muted text-xs">-</span>
                                                    @endif
                                                </td>
                                                <td class="py-3 px-4">
                                                    @if($log->notes)
                                                        <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle text-wrap text-start">
                                                            {{ $log->notes }}
                                                        </span>
                                                    @else
                                                        <span class="text-muted text-xs">Pembaruan harga modal</span>
                                                    @endif
                                                </td>
                                                <td class="py-3 px-4">
                                                    <div class="font-medium text-gray-800">
                                                        {{ $log->changer->name ?? 'Sistem / Otomatis' }}
                                                    </div>
                                                    @if($log->changer)
                                                        <div class="text-xs text-muted capitalize">
                                                            {{ $log->changer->usertype ?? 'User' }}
                                                        </div>
                                                    @endif
                                                </td>
                                                <td class="py-3 px-4 text-center">
                                                    @if($log->source === 'import')
                                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                                            <i class="bi bi-file-earmark-excel me-1"></i>Import Excel
                                                        </span>
                                                    @else
                                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">
                                                            <i class="bi bi-pencil-square me-1"></i>Manual Web
                                                        </span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="7" class="py-8 text-center text-muted">
                                                    <div class="flex flex-col items-center justify-center">
                                                        <i class="bi bi-clock-history text-4xl text-gray-300 mb-2"></i>
                                                        <p class="font-medium">Belum ada riwayat log perubahan yang cocok.</p>
                                                        <p class="text-xs text-gray-400">Setiap perubahan harga modal, harga jual, nama, atau SKU akan otomatis tercatat di sini.</p>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            @if($logs->hasPages())
                                <div class="p-4 border-t border-gray-100">
                                    {{ $logs->links() }}
                                </div>
                            @endif
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
