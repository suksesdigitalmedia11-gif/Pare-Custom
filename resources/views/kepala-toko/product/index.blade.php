<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Tabel Produk - Pare Custom</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Bootstrap CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css" />
    <!-- Font CDN -->
    <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;600&display=swap" rel="stylesheet">
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
                        <div class="flex justify-between items-center mb-6">
                            <div>
                                <h1 class="text-3xl font-bold text-gray-800">Daftar Produk</h1>
                                <p class="text-sm text-gray-500 mt-1">Kelola data produk, stok, serta perbarui harga modal & harga jual.</p>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <div class="dropdown">
                                    <button class="btn btn-outline-success dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="bi bi-download me-1"></i>Download Template
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow">
                                        <li>
                                            <a class="dropdown-item py-2" href="{{ route('kepala-toko.product.export-price-update', request()->query()) }}">
                                                <i class="bi bi-pencil-square text-warning me-2"></i><strong>Template Update Harga</strong>
                                                <div class="small text-muted ps-4">Format 6 kolom khusus update modal & jual</div>
                                            </a>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <a class="dropdown-item py-2" href="{{ route('kepala-toko.product.download-template') }}">
                                                <i class="bi bi-file-earmark-excel text-success me-2"></i>Template Produk Baru
                                                <div class="small text-muted ps-4">Format lengkap untuk tambah produk baru</div>
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#importModal">
                                    <i class="bi bi-upload me-1"></i>Import Excel
                                </button>
                                <a href="{{ route('kepala-toko.product.logs') }}" class="btn btn-outline-primary">
                                    <i class="bi bi-clock-history me-1"></i>Riwayat Log Produk
                                </a>
                                <a href="{{ route('kepala-toko.product.create') }}" class="btn btn-primary">
                                    <i class="bi bi-plus-circle me-1"></i>Tambah Produk
                                </a>
                            </div>
                        </div>

                        @if(session('success'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-check-circle-fill text-success fs-4 me-2"></i>
                                    <div>{{ session('success') }}</div>
                                </div>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        @if(session('import_updated_products') && count(session('import_updated_products')) > 0)
                            <div class="alert alert-info alert-dismissible fade show" role="alert">
                                <div class="d-flex justify-content-between align-items-center">
                                    <strong><i class="bi bi-info-circle-fill me-1"></i> Rincian Produk yang Berhasil Diperbarui ({{ count(session('import_updated_products')) }} Produk):</strong>
                                    <button class="btn btn-sm btn-link text-decoration-none" type="button" data-bs-toggle="collapse" data-bs-target="#collapseUpdatedList">
                                        Buka/Tutup Rincian
                                    </button>
                                </div>
                                <div class="collapse show mt-2" id="collapseUpdatedList">
                                    <div class="bg-white p-3 rounded border" style="max-height: 220px; overflow-y: auto;">
                                        <table class="table table-sm table-hover mb-0" style="font-size: 0.85rem;">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>SKU</th>
                                                    <th>Nama Produk</th>
                                                    <th>Perubahan</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach(session('import_updated_products') as $idx => $p)
                                                    <tr>
                                                        <td>{{ $idx + 1 }}</td>
                                                        <td><code>{{ $p['sku'] ?? '-' }}</code></td>
                                                        <td><strong>{{ $p['name'] }}</strong></td>
                                                        <td><span class="badge bg-success text-white">{{ $p['change'] }}</span></td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        @if(session('import_errors'))
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <strong><i class="bi bi-exclamation-triangle-fill me-1"></i> Baris yang Gagal / Dilewati:</strong>
                                <ul class="mb-0 mt-2">
                                    @foreach(session('import_errors') as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        <div class="card shadow">
                            <div class="card-body">
                                <!-- Search and Filter Form -->
                                <form method="GET" action="{{ route('kepala-toko.product.index') }}" class="row g-3 mb-4">
                                    <div class="col-md-8">
                                        <label class="form-label fw-medium">
                                            <i class="bi bi-search me-1"></i>Cari Produk
                                        </label>
                                        <input type="text" name="q" class="form-control" 
                                               value="{{ request('q') }}" 
                                               placeholder="Cari berdasarkan nama produk, SKU, atau barcode...">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label fw-medium">
                                            <i class="bi bi-funnel me-1"></i>Kategori
                                        </label>
                                        <select name="category_id" class="form-select">
                                            <option value="">Semua Kategori</option>
                                            @foreach($categories as $category)
                                                <option value="{{ $category->id }}" 
                                                        {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                                    {{ $category->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">&nbsp;</label>
                                        <div class="d-flex gap-2">
                                            <button type="submit" class="btn btn-outline-primary">
                                                <i class="bi bi-search"></i>
                                            </button>
                                            <a href="{{ route('kepala-toko.product.index') }}" class="btn btn-outline-secondary">
                                                <i class="bi bi-arrow-clockwise"></i>
                                            </a>
                                        </div>
                                    </div>
                                </form>

                                <!-- Bulk Action Toolbar -->
                                <div id="bulkActionBar" class="alert alert-danger d-none align-items-center justify-content-between p-3 mb-3 shadow-sm rounded-3 border-danger">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="bi bi-check2-square fs-5 text-danger"></i>
                                        <span class="fw-bold text-dark" id="selectedCountText">0 produk dipilih</span>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-sm btn-outline-secondary" id="clearSelectionBtn">
                                            <i class="bi bi-x-lg me-1"></i>Batal Pilih
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger px-3 shadow-sm fw-bold" id="deleteSelectedBtn" onclick="confirmBulkDelete()">
                                            <i class="bi bi-trash-fill me-1"></i>Hapus Produk Terpilih
                                        </button>
                                    </div>
                                </div>

                                <!-- Hidden Bulk Delete Form -->
                                <form id="bulkDeleteForm" action="{{ route('kepala-toko.product.bulk-destroy') }}" method="POST" class="d-none">
                                    @csrf
                                    <div id="bulkDeleteInputs"></div>
                                </form>

                                <div class="table-responsive">
                                    <table class="table table-hover align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width: 40px;" class="text-center">
                                                    <input type="checkbox" id="selectAllProducts" class="form-check-input cursor-pointer" title="Pilih Semua Produk di Halaman Ini">
                                                </th>
                                                <th>Gambar</th>
                                                <th>Nama</th>
                                                <th>SKU</th>
                                                <th>Barcode</th>
                                                <th>Kategori</th>
                                                <th>Harga Modal</th>
                                                <th>Harga Jual</th>
                                                <th>Stok</th>
                                                <th>Status</th>
                                                <th>Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($products as $product)
                                                <tr>
                                                    <td class="text-center">
                                                        <input type="checkbox" class="form-check-input product-checkbox cursor-pointer" value="{{ $product->id }}">
                                                    </td>
                                                    <td>
                                                        @if($product->image_path)
                                                            <img src="{{ Storage::url($product->image_path) }}" 
                                                                 alt="{{ $product->name }}" 
                                                                 class="rounded" 
                                                                 style="width: 60px; height: 60px; object-fit: cover;">
                                                        @else
                                                            <div class="bg-light rounded d-flex align-items-center justify-content-center" 
                                                                 style="width: 60px; height: 60px;">
                                                                <i class="bi bi-image text-muted"></i>
                                                            </div>
                                                        @endif
                                                    </td>
                                                    <td>{{ $product->name }}</td>
                                                    <td>{{ $product->sku ?: '-' }}</td>
                                                    <td>{{ $product->barcode ?: '-' }}</td>
                                                    <td>{{ $product->category?->name ?: '-' }}</td>
                                                    <td>Rp {{ number_format($product->cost_price, 2, ',', '.') }}</td>
                                                    <td>Rp {{ number_format($product->price, 2, ',', '.') }}</td>
                                                    <td>{{ $product->stock_qty ?? 0 }}</td>
                                                    <td>
                                                        <span class="badge {{ $product->is_active ? 'bg-success' : 'bg-secondary' }}">
                                                            {{ $product->is_active ? 'Aktif' : 'Nonaktif' }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group btn-group-sm">
                                                            <a href="{{ route('kepala-toko.product.logs', ['product_id' => $product->id]) }}" 
                                                               class="btn btn-outline-secondary" title="Riwayat Log Perubahan">
                                                                <i class="bi bi-clock-history"></i>
                                                            </a>
                                                            <a href="{{ route('kepala-toko.product.show', $product) }}" 
                                                               class="btn btn-outline-info" title="Analisis">
                                                                <i class="bi bi-eye"></i>
                                                            </a>
                                                            <a href="{{ route('kepala-toko.product.edit', $product) }}" 
                                                               class="btn btn-outline-warning" title="Edit">
                                                                <i class="bi bi-pencil"></i>
                                                            </a>
                                                            <button type="button" class="btn btn-outline-danger" 
                                                                    title="Hapus"
                                                                    onclick="confirmDelete('{{ route('kepala-toko.product.destroy', $product) }}', '{{ $product->name }}')">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="11" class="text-center py-4 text-muted">
                                                        <i class="bi bi-inbox display-4 d-block mb-3"></i>
                                                        @if(request('q') || request('category_id'))
                                                            Tidak ada produk yang sesuai dengan pencarian.
                                                        @else
                                                            Belum ada produk. Silakan tambah produk baru.
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Pagination -->
                                @if($products->hasPages())
                                    <div class="d-flex justify-content-between align-items-center mt-4">
                                        <div class="text-muted small">
                                            Menampilkan {{ $products->firstItem() ?: 0 }} sampai {{ $products->lastItem() ?: 0 }} 
                                            dari {{ $products->total() }} entri
                                        </div>
                                        <nav>
                                            {{ $products->withQueryString()->links('pagination::bootstrap-4') }}
                                        </nav>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Import Modal with Interactive Preview -->
    <div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title fw-bold" id="importModalLabel">
                        <i class="bi bi-file-earmark-spreadsheet me-2"></i>
                        Import Data & Update Harga Produk
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <form action="{{ route('kepala-toko.product.import') }}" method="POST" enctype="multipart/form-data" id="importForm">
                    @csrf
                    <input type="hidden" name="import_token" id="importToken" value="">
                    
                    <div class="modal-body p-4">
                        <!-- File Upload Section -->
                        <div id="uploadSection" class="mb-4">
                            <label class="form-label fw-bold text-gray-700">
                                <i class="bi bi-cloud-arrow-up text-success me-1"></i>Pilih File Spreadsheet (Excel / CSV)
                            </label>
                            <input type="file" name="file" id="importFileInput" class="form-control form-control-lg" 
                                   accept=".csv,.txt,.xlsx,.xls" required onchange="handleFileSelected(this)">
                            <div class="form-text text-muted">
                                Format didukung: <code>.xlsx</code>, <code>.xls</code>, <code>.csv</code> (Maksimal 10MB).
                            </div>

                            <div class="row g-3 mt-2">
                                <div class="col-md-6">
                                    <div class="p-3 bg-light border rounded">
                                        <div class="d-flex align-items-center mb-1">
                                            <i class="bi bi-pencil-square text-warning fs-5 me-2"></i>
                                            <strong class="text-dark">Mau Update Harga Modal / Jual?</strong>
                                        </div>
                                        <p class="small text-muted mb-2">Download template khusus dengan data produk yang sudah ada untuk ubah harga modal & jual secara massal.</p>
                                        <a href="{{ route('kepala-toko.product.export-price-update') }}" class="btn btn-sm btn-outline-warning text-dark fw-semibold">
                                            <i class="bi bi-download me-1"></i>Download Template Update Harga (6 Kolom)
                                        </a>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="p-3 bg-light border rounded">
                                        <div class="d-flex align-items-center mb-1">
                                            <i class="bi bi-plus-circle text-success fs-5 me-2"></i>
                                            <strong class="text-dark">Mau Tambah Produk Baru?</strong>
                                        </div>
                                        <p class="small text-muted mb-2">Gunakan template master produk jika ingin memasukkan produk-produk baru ke inventaris.</p>
                                        <a href="{{ route('kepala-toko.product.download-template') }}" class="btn btn-sm btn-outline-success fw-semibold">
                                            <i class="bi bi-download me-1"></i>Download Template Produk Baru
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Loading State -->
                        <div id="previewLoading" class="text-center py-5 d-none">
                            <div class="spinner-border text-success" role="status" style="width: 3rem; height: 3rem;">
                                <span class="visually-hidden">Membaca file...</span>
                            </div>
                            <h5 class="mt-3 fw-bold text-gray-700">Menganalisa data spreadsheet...</h5>
                            <p class="text-muted small">Sistem sedang memeriksa kecocokan produk, format harga, dan validasi selisih keuntungan.</p>
                        </div>

                        <!-- Preview Section (Hidden initially) -->
                        <div id="previewSection" class="d-none">
                            <div class="alert alert-warning border-warning d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <i class="bi bi-eye-fill fs-5 me-2 text-warning"></i>
                                    <strong>Pratinjau Perubahan Data Sebelum Disimpan:</strong>
                                    <span id="previewSummaryText" class="d-block small text-muted mt-1"></span>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="resetToUpload()">
                                    <i class="bi bi-arrow-counterclockwise me-1"></i>Ganti File
                                </button>
                            </div>

                            <!-- Summary Badges -->
                            <div class="row g-2 mb-3 text-center" id="summaryBadges">
                                <div class="col-md-3">
                                    <div class="p-2 border rounded bg-light">
                                        <small class="text-muted d-block">Akan Diupdate</small>
                                        <span class="fs-5 fw-bold text-primary" id="badgeUpdates">0</span>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="p-2 border rounded bg-light">
                                        <small class="text-muted d-block">Produk Baru</small>
                                        <span class="fs-5 fw-bold text-success" id="badgeInserts">0</span>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="p-2 border rounded bg-light">
                                        <small class="text-muted d-block">Tidak Ada Perubahan</small>
                                        <span class="fs-5 fw-bold text-secondary" id="badgeUnchanged">0</span>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="p-2 border rounded bg-light">
                                        <small class="text-muted d-block">Perlu Perhatian / Error</small>
                                        <span class="fs-5 fw-bold text-danger" id="badgeErrors">0</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Preview Table -->
                            <div class="table-responsive border rounded" style="max-height: 350px; overflow-y: auto;">
                                <table class="table table-sm table-striped table-hover mb-0" style="font-size: 0.85rem;">
                                    <thead class="table-dark sticky-top">
                                        <tr>
                                            <th>#</th>
                                            <th>SKU</th>
                                            <th>Nama Produk</th>
                                            <th class="text-end">Modal Lama</th>
                                            <th class="text-end">Modal Baru</th>
                                            <th class="text-end">Jual Lama</th>
                                            <th class="text-end">Jual Baru</th>
                                            <th>Status Perubahan</th>
                                        </tr>
                                    </thead>
                                    <tbody id="previewTableBody">
                                        <!-- Dynamically generated via JS -->
                                    </tbody>
                                </table>
                            </div>
                            <div class="small text-muted mt-2">
                                <i class="bi bi-info-circle me-1"></i>Menampilkan sampel baris. Tekan <strong>Konfirmasi & Simpan Import</strong> untuk menyimpan perubahan ke database.
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="button" id="previewBtn" class="btn btn-warning text-dark fw-bold" onclick="runPreview()">
                            <i class="bi bi-eye me-1"></i>Pratinjau Data
                        </button>
                        <button type="submit" id="submitImportBtn" class="btn btn-success fw-bold d-none">
                            <i class="bi bi-check-circle me-1"></i>Konfirmasi & Simpan Import
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Konfirmasi Hapus</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Apakah Anda yakin ingin menghapus produk <strong id="productName"></strong>?</p>
                    <p class="text-muted">Tindakan ini tidak dapat dibatalkan.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <form method="POST" id="deleteForm" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">Hapus</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

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

        function confirmDelete(url, productName) {
            document.getElementById('productName').textContent = productName;
            document.getElementById('deleteForm').action = url;
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        }

        function handleFileSelected(input) {
            resetToUpload();
        }

        function resetToUpload() {
            document.getElementById('uploadSection').classList.remove('d-none');
            document.getElementById('previewSection').classList.add('d-none');
            document.getElementById('previewLoading').classList.add('d-none');
            document.getElementById('previewBtn').classList.remove('d-none');
            document.getElementById('submitImportBtn').classList.add('d-none');
            document.getElementById('importToken').value = '';
        }

        async function runPreview() {
            const fileInput = document.getElementById('importFileInput');
            if (!fileInput.files || fileInput.files.length === 0) {
                alert('Pilih file Excel/CSV terlebih dahulu!');
                fileInput.focus();
                return;
            }

            const formData = new FormData();
            formData.append('file', fileInput.files[0]);
            formData.append('_token', '{{ csrf_token() }}');

            document.getElementById('uploadSection').classList.add('d-none');
            document.getElementById('previewLoading').classList.remove('d-none');
            document.getElementById('previewBtn').disabled = true;

            try {
                const response = await fetch('{{ route("kepala-toko.product.preview-import") }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const data = await response.json();
                document.getElementById('previewLoading').classList.add('d-none');
                document.getElementById('previewBtn').disabled = false;

                if (!response.ok || !data.success) {
                    alert(data.message || 'Gagal menganalisa file. Pastikan format spreadsheet sesuai template.');
                    document.getElementById('uploadSection').classList.remove('d-none');
                    return;
                }

                // Render Preview Badges & Summary
                document.getElementById('importToken').value = data.import_token || '';
                document.getElementById('badgeUpdates').textContent = data.total_updates || 0;
                document.getElementById('badgeInserts').textContent = data.total_inserts || 0;
                document.getElementById('badgeUnchanged').textContent = data.total_unchanged || 0;
                document.getElementById('badgeErrors').textContent = data.total_errors || 0;

                const summary = data.summary || {};
                document.getElementById('previewSummaryText').textContent = 
                    `Total ${summary.total_rows || data.preview.length} baris dibaca: ` +
                    `${data.total_updates} produk akan diupdate, ${data.total_inserts} produk baru, ${data.total_unchanged} tidak ada perubahan harga.`;

                const tbody = document.getElementById('previewTableBody');
                tbody.innerHTML = '';

                const formatRupiah = (num) => {
                    if (num === null || num === undefined || isNaN(num)) return '-';
                    return 'Rp ' + Number(num).toLocaleString('id-ID');
                };

                (data.preview || []).forEach(row => {
                    const tr = document.createElement('tr');
                    
                    let badgeClass = 'bg-secondary';
                    if (row.action_type === 'update') badgeClass = 'bg-primary';
                    else if (row.action_type === 'insert') badgeClass = 'bg-success';
                    else if (row.action_type === 'error') badgeClass = 'bg-danger';

                    tr.innerHTML = `
                        <td>${row.row}</td>
                        <td><code>${row.new_sku || row.sku || '-'}</code></td>
                        <td><strong>${row.product_name}</strong></td>
                        <td class="text-end text-muted">${formatRupiah(row.old_cost_price)}</td>
                        <td class="text-end fw-bold text-dark">${row.new_cost_price !== null ? formatRupiah(row.new_cost_price) : '<span class="text-muted">-</span>'}</td>
                        <td class="text-end text-muted">${formatRupiah(row.old_price)}</td>
                        <td class="text-end fw-bold text-dark">${row.new_price !== null ? formatRupiah(row.new_price) : '<span class="text-muted">-</span>'}</td>
                        <td>
                            <span class="badge ${badgeClass}">${row.action}</span>
                            ${row.error_message ? `<div class="small text-danger mt-1">${row.error_message}</div>` : ''}
                        </td>
                    `;
                    tbody.appendChild(tr);
                });

                document.getElementById('previewSection').classList.remove('d-none');
                document.getElementById('previewBtn').classList.add('d-none');
                document.getElementById('submitImportBtn').classList.remove('d-none');

            } catch (err) {
                console.error(err);
                alert('Terjadi kesalahan koneksi saat membaca file: ' + err.message);
                document.getElementById('previewLoading').classList.add('d-none');
                document.getElementById('uploadSection').classList.remove('d-none');
                document.getElementById('previewBtn').disabled = false;
            }
        }

        // Import form submission feedback
        document.getElementById('importForm').addEventListener('submit', function() {
            const btn = document.getElementById('submitImportBtn');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Menyimpan Data...';
        });

        // Reset import modal state on close
        document.getElementById('importModal').addEventListener('hidden.bs.modal', function() {
            resetToUpload();
            const btn = document.getElementById('submitImportBtn');
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check-circle me-1"></i>Konfirmasi & Simpan Import';
        });

        // Bulk Delete Checkbox Logic
        const selectAllCheckbox = document.getElementById('selectAllProducts');
        const productCheckboxes = document.querySelectorAll('.product-checkbox');
        const bulkActionBar = document.getElementById('bulkActionBar');
        const selectedCountText = document.getElementById('selectedCountText');
        const clearSelectionBtn = document.getElementById('clearSelectionBtn');
        const bulkDeleteForm = document.getElementById('bulkDeleteForm');
        const bulkDeleteInputs = document.getElementById('bulkDeleteInputs');

        function updateBulkActionBar() {
            const selected = document.querySelectorAll('.product-checkbox:checked');
            const count = selected.length;
            if (count > 0) {
                bulkActionBar.classList.remove('d-none');
                bulkActionBar.classList.add('d-flex');
                selectedCountText.innerText = count + ' produk dipilih';
            } else {
                bulkActionBar.classList.add('d-none');
                bulkActionBar.classList.remove('d-flex');
            }
            if (selectAllCheckbox) {
                selectAllCheckbox.checked = (productCheckboxes.length > 0 && selected.length === productCheckboxes.length);
                selectAllCheckbox.indeterminate = (count > 0 && count < productCheckboxes.length);
            }
        }

        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', function() {
                productCheckboxes.forEach(cb => cb.checked = selectAllCheckbox.checked);
                updateBulkActionBar();
            });
        }

        productCheckboxes.forEach(cb => {
            cb.addEventListener('change', updateBulkActionBar);
        });

        if (clearSelectionBtn) {
            clearSelectionBtn.addEventListener('click', function() {
                productCheckboxes.forEach(cb => cb.checked = false);
                if (selectAllCheckbox) {
                    selectAllCheckbox.checked = false;
                    selectAllCheckbox.indeterminate = false;
                }
                updateBulkActionBar();
            });
        }

        function confirmBulkDelete() {
            const selected = document.querySelectorAll('.product-checkbox:checked');
            if (selected.length === 0) {
                alert('Silakan pilih minimal 1 produk untuk dihapus.');
                return;
            }

            if (confirm(`Apakah Anda yakin ingin menghapus ${selected.length} produk yang dipilih secara massal?\n\nProduk akan dihapus/diarsipkan dengan aman tanpa merusak riwayat transaksi penjualan yang sudah terjadi.`)) {
                bulkDeleteInputs.innerHTML = '';
                selected.forEach(cb => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'ids[]';
                    input.value = cb.value;
                    bulkDeleteInputs.appendChild(input);
                });
                bulkDeleteForm.submit();
            }
        }
    </script>
</body>
</html>