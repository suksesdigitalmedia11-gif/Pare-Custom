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
        <x-navbar-finance></x-navbar-finance>

        <!-- Main Content -->
        <div class="flex-1 lg:w-5/6">
            <x-navbar-top-finance></x-navbar-top-finance>

            <!-- Content Wrapper -->
            <div class="p-4 lg:p-8">
                <div class="p-6 bg-gray-100 min-h-screen">
                    <div class="max-w-7xl mx-auto">
                        <div class="flex justify-between items-center mb-6">
                            <h1 class="text-3xl font-bold text-gray-800">Daftar Produk</h1>
                            <div class="flex space-x-2">
                                <div class="btn-group">
                                    <button type="button" class="btn btn-info dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="bi bi-download me-2"></i>Export
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li>
                                            <a class="dropdown-item" href="{{ route('finance.product.export', array_merge(request()->query(), ['format' => 'xlsx'])) }}">
                                                <i class="bi bi-file-earmark-spreadsheet text-success me-2"></i>Export Excel (XLSX)
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="{{ route('finance.product.export', array_merge(request()->query(), ['format' => 'csv'])) }}">
                                                <i class="bi bi-filetype-csv text-primary me-2"></i>Export CSV
                                            </a>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <a class="dropdown-item" href="{{ route('finance.product.export', ['format' => 'xlsx']) }}">
                                                <i class="bi bi-download me-2"></i>Export Semua Produk (Excel)
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#importModal">
                                    <i class="bi bi-upload me-2"></i>Import
                                </button>
                                <a href="{{ route('finance.product.create') }}" class="btn btn-primary">
                                    <i class="bi bi-plus-circle me-2"></i>Tambah Produk
                                </a>
                            </div>
                        </div>

                        @if(session('success'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                {{ session('success') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        @if(session('import_errors'))
                            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                                <strong>Peringatan:</strong>
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
                                <form method="GET" action="{{ route('finance.product.index') }}" class="row g-3 mb-4">
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
                                            <a href="{{ route('finance.product.index') }}" class="btn btn-outline-secondary">
                                                <i class="bi bi-arrow-clockwise"></i>
                                            </a>
                                        </div>
                                    </div>
                                </form>

                                <div class="table-responsive">
                                    <table class="table table-hover align-middle">
                                        <thead class="table-light">
                                            <tr>
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
                                                            <a href="{{ route('finance.product.show', $product) }}" 
                                                               class="btn btn-outline-info" title="Analisis">
                                                                <i class="bi bi-eye"></i>
                                                            </a>
                                                            <a href="{{ route('finance.product.edit', $product) }}" 
                                                               class="btn btn-outline-warning" title="Edit">
                                                                <i class="bi bi-pencil"></i>
                                                            </a>
                                                            <button type="button" class="btn btn-outline-danger" 
                                                                    title="Hapus"
                                                                    onclick="confirmDelete('{{ route('finance.product.destroy', $product) }}', '{{ $product->name }}')">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="10" class="text-center py-4 text-muted">
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

    <!-- Import Modal -->
    <div class="modal fade" id="importModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-cloud-upload text-primary me-2"></i>
                        Import Data Produk
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <form action="{{ route('finance.product.import') }}" method="POST" enctype="multipart/form-data" id="importForm">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-4">
                            <label class="form-label fw-semibold">
                                <i class="bi bi-file-earmark-spreadsheet text-success me-2"></i>
                                Pilih File
                            </label>
                            <input type="file" name="file" class="form-control" 
                                   accept=".csv,.txt,.xlsx,.xls" required>
                            <div class="form-text">
                                Format yang didukung: CSV, TXT, XLSX, XLS (Max: 10MB)
                            </div>
                        </div>

                        <div class="alert alert-info">
                            <div class="d-flex">
                                <i class="bi bi-lightbulb flex-shrink-0 me-2"></i>
                                <div>
                                    <strong>Belum punya template?</strong>
                                    <p class="mb-2">Download template Excel untuk memudahkan proses import.</p>
                                    <a href="{{ route('finance.product.download-template') }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-download me-1"></i>Download Template
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="border rounded p-3 bg-light">
                            <h6 class="fw-bold mb-3">
                                <i class="bi bi-list-check text-success me-2"></i>
                                Format File yang Dibutuhkan:
                            </h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <ul class="list-unstyled small">
                                        <li><code class="bg-white px-2 py-1 rounded">sku</code> - Kode produk</li>
                                        <li><code class="bg-white px-2 py-1 rounded">name</code> - Nama produk</li>
                                        <li><code class="bg-white px-2 py-1 rounded">category_name</code> - Nama kategori</li>
                                        <li><code class="bg-white px-2 py-1 rounded">cost_price</code> - Harga modal</li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <ul class="list-unstyled small">
                                        <li><code class="bg-white px-2 py-1 rounded">price</code> - Harga jual</li>
                                        <li><code class="bg-white px-2 py-1 rounded">stock_qty</code> - Jumlah stok</li>
                                        <li><code class="bg-white px-2 py-1 rounded">is_active</code> - Status (0/1)</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary" id="importBtn">
                            <i class="bi bi-upload me-2"></i>Import Data
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

        // Import form handling
        document.getElementById('importForm').addEventListener('submit', function() {
            const btn = document.getElementById('importBtn');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Mengimport...';
        });

        // Reset import button when modal is closed
        document.getElementById('importModal').addEventListener('hidden.bs.modal', function() {
            const btn = document.getElementById('importBtn');
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-upload me-2"></i>Import Data';
        });
    </script>
</body>
</html>