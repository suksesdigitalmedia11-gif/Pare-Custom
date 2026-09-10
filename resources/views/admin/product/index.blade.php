<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Daftar Produk - Admin - Pare Custom</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css" />
    <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Raleway', sans-serif; }
        .nav-text { position: relative; display: inline-block; }
        .nav-text::after { content: ''; position: absolute; width: 0; height: 2px; bottom: -2px; left: 0; background-color: #e17f12; transition: width .2s; }
        .hover-link:hover .nav-text::after { width: 100%; }
        
        /* Custom Scrollbar for Table */
        .custom-scrollbar::-webkit-scrollbar {
            height: 8px;
            width: 8px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background-color: #f1f1f1;
            border-radius: 4px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background-color: #c1c1c1;
            border-radius: 4px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background-color: #a8a8a8;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="flex">

        <!-- Sidebar -->
        <x-navbar-admin></x-navbar-admin>

        <!-- Main Content -->
        <div class="flex-1 lg:w-5/6">
            <x-navbar-top-admin></x-navbar-top-admin>

            <!-- Content Wrapper -->
            <div class="p-4 lg:p-8">
                <div class="max-w-7xl mx-auto">
                    
                    <!-- Header -->
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">Daftar Produk</h1>
                            <p class="text-sm text-gray-500 mt-1">Kelola data produk, harga, dan stok inventaris.</p>
                        </div>
                        <div class="flex space-x-3">
                            <div class="relative inline-block text-left" id="exportDropdown">
                                <div>
                                    <button type="button" onclick="toggleExportMenu()" class="inline-flex items-center px-4 py-2 bg-sky-600 text-white rounded-xl hover:bg-sky-700 transition shadow-lg shadow-sky-200">
                                        <i class="bi bi-download mr-2"></i>
                                        Export
                                        <i class="bi bi-chevron-down ml-2 text-xs"></i>
                                    </button>
                                </div>
                                <div id="exportMenu" class="hidden absolute right-0 mt-2 w-56 rounded-xl shadow-lg bg-white ring-1 ring-black ring-opacity-5 focus:outline-none z-10 transition-all origin-top-right transform scale-95 opacity-0">
                                    <div class="py-1" role="menu" aria-orientation="vertical" aria-labelledby="options-menu">
                                        <a href="{{ route('admin.product.export', array_merge(request()->query(), ['format' => 'xlsx'])) }}" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900" role="menuitem">
                                            <i class="bi bi-file-earmark-spreadsheet text-green-600 mr-3"></i> Export Excel (.xlsx)
                                        </a>
                                        <a href="{{ route('admin.product.export', array_merge(request()->query(), ['format' => 'csv'])) }}" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900" role="menuitem">
                                            <i class="bi bi-filetype-csv text-blue-600 mr-3"></i> Export CSV (.csv)
                                        </a>
                                        <div class="border-t border-gray-100 my-1"></div>
                                        <a href="{{ route('admin.product.export-price-update', request()->query()) }}" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900" role="menuitem">
                                            <i class="bi bi-pencil-square text-orange-600 mr-3"></i> Template Update Harga
                                        </a>
                                        <a href="{{ route('admin.product.export', ['format' => 'xlsx']) }}" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900" role="menuitem">
                                            <i class="bi bi-collection text-purple-600 mr-3"></i> Export Semua Data
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <button type="button" onclick="openImportModal()" 
                                    class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-xl hover:bg-green-700 transition shadow-lg shadow-green-200">
                                <i class="bi bi-file-earmark-spreadsheet mr-2"></i>
                                Import Excel
                            </button>
                            <a href="{{ route('admin.product.create') }}" 
                               class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition shadow-lg shadow-blue-200">
                                <i class="bi bi-plus-lg mr-2"></i>
                                Produk Baru
                            </a>
                        </div>
                    </div>

                    <!-- Alerts -->
                    @if(session('success'))
                        <div class="mb-6 p-4 rounded-xl bg-green-50 border border-green-200 text-green-700 flex items-center gap-3">
                            <i class="bi bi-check-circle-fill text-xl"></i>
                            <p class="font-medium">{{ session('success') }}</p>
                        </div>
                    @endif

                    @if(session('import_updated_products') && count(session('import_updated_products')) > 0)
                        <div class="mb-6 p-4 rounded-xl bg-blue-50 border border-blue-200 text-blue-800">
                            <div class="flex justify-between items-center mb-2">
                                <div class="flex items-center gap-3">
                                    <i class="bi bi-info-circle-fill text-xl text-blue-600"></i>
                                    <h4 class="font-bold">Rincian Produk yang Berhasil Diperbarui ({{ count(session('import_updated_products')) }} Produk)</h4>
                                </div>
                            </div>
                            <div class="max-h-60 overflow-y-auto mt-2 border border-blue-100 rounded-lg bg-white p-2">
                                <table class="w-full text-xs text-left">
                                    <thead class="bg-blue-100 text-blue-800">
                                        <tr>
                                            <th class="p-1.5">#</th>
                                            <th class="p-1.5">SKU</th>
                                            <th class="p-1.5">Nama Produk</th>
                                            <th class="p-1.5">Perubahan</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-blue-50">
                                        @foreach(session('import_updated_products') as $idx => $p)
                                            <tr>
                                                <td class="p-1.5">{{ $idx + 1 }}</td>
                                                <td class="p-1.5 font-mono">{{ $p['sku'] ?? '-' }}</td>
                                                <td class="p-1.5 font-semibold">{{ $p['name'] }}</td>
                                                <td class="p-1.5"><span class="px-2 py-0.5 bg-green-100 text-green-800 rounded font-medium">{{ $p['change'] }}</span></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

                    @if(session('import_errors'))
                        <div class="mb-6 p-4 rounded-xl bg-yellow-50 border border-yellow-200 text-yellow-800">
                            <div class="flex items-center gap-3 mb-2">
                                <i class="bi bi-exclamation-triangle-fill text-xl"></i>
                                <h4 class="font-bold">Peringatan Import</h4>
                            </div>
                            <ul class="list-disc list-inside text-sm space-y-1 ml-9">
                                @foreach(session('import_errors') as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <!-- Card Container -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                        
                        <!-- Filter Section -->
                        <div class="p-6 border-b border-gray-100 bg-gray-50/50">
                            <form method="GET" action="{{ route('admin.product.index') }}">
                                <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                                    <div class="md:col-span-5">
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Cari Produk</label>
                                        <div class="relative">
                                            <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                            <input type="text" name="q" value="{{ request('q') }}" 
                                                   class="w-full pl-10 pr-4 py-2.5 rounded-xl border-gray-300 focus:ring-blue-500 focus:border-blue-500 transition shadow-sm"
                                                   placeholder="Nama produk, SKU, atau Barcode...">
                                        </div>
                                    </div>
                                    <div class="md:col-span-3">
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Kategori</label>
                                        <select name="category_id" class="w-full rounded-xl border-gray-300 focus:ring-blue-500 focus:border-blue-500 py-2.5 shadow-sm">
                                            <option value="">Semua Kategori</option>
                                            @foreach($categories as $category)
                                                <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                                    {{ $category->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="md:col-span-4 flex gap-2">
                                        <button type="submit" class="px-5 py-2.5 bg-gray-900 text-white rounded-xl hover:bg-gray-800 transition shadow-md">
                                            Filter
                                        </button>
                                        <a href="{{ route('admin.product.index') }}" class="px-5 py-2.5 bg-white border border-gray-300 text-gray-700 rounded-xl hover:bg-gray-50 transition shadow-sm">
                                            Reset
                                        </a>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <!-- Table Section -->
                        <div class="overflow-x-auto custom-scrollbar">
                            <table class="w-full text-left border-collapse">
                                <thead class="bg-gray-50 text-gray-600 font-semibold uppercase text-xs tracking-wider">
                                    <tr>
                                        <th class="px-6 py-4 border-b border-gray-100">
                                            <a href="{{ route('admin.product.index', array_merge(request()->query(), ['sort_by' => 'name', 'direction' => request('direction') == 'asc' ? 'desc' : 'asc'])) }}" class="group flex items-center gap-1 cursor-pointer hover:text-blue-600">
                                                Produk
                                                <span class="flex flex-col text-[10px] leading-none text-gray-400 group-hover:text-blue-500">
                                                    <i class="bi bi-caret-up-fill {{ request('sort_by') == 'name' && request('direction') == 'asc' ? 'text-blue-600' : '' }}"></i>
                                                    <i class="bi bi-caret-down-fill {{ request('sort_by') == 'name' && request('direction') == 'desc' ? 'text-blue-600' : '' }}"></i>
                                                </span>
                                            </a>
                                        </th>
                                        <th class="px-6 py-4 border-b border-gray-100">Kategori</th>
                                        <th class="px-6 py-4 border-b border-gray-100 text-right">Harga Modal</th>
                                        <th class="px-6 py-4 border-b border-gray-100 text-right">Harga Jual</th>
                                        <th class="px-6 py-4 border-b border-gray-100 text-center">Stok</th>
                                        <th class="px-6 py-4 border-b border-gray-100 text-center">Status</th>
                                        <th class="px-6 py-4 border-b border-gray-100 text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 bg-white text-sm">
                                    @forelse($products as $product)
                                        <tr class="hover:bg-gray-50/80 transition-colors group">
                                            <td class="px-6 py-4">
                                                <div class="flex items-center gap-4">
                                                    <div class="h-12 w-12 rounded-lg bg-gray-100 border border-gray-200 flex-shrink-0 overflow-hidden">
                                                        @if($product->image_path)
                                                            <img src="{{ Storage::url($product->image_path) }}" alt="{{ $product->name }}" class="h-full w-full object-cover">
                                                        @else
                                                            <div class="h-full w-full flex items-center justify-center text-gray-400">
                                                                <i class="bi bi-image"></i>
                                                            </div>
                                                        @endif
                                                    </div>
                                                    <div>
                                                        <p class="font-bold text-gray-900 group-hover:text-blue-600 transition-colors">{{ $product->name }}</p>
                                                        <div class="flex items-center gap-2 mt-0.5">
                                                            @if($product->sku) <span class="text-xs font-mono bg-gray-100 px-1.5 py-0.5 rounded text-gray-600">{{ $product->sku }}</span> @endif
                                                            @if($product->barcode) <span class="text-xs font-mono bg-gray-100 px-1.5 py-0.5 rounded text-gray-600"><i class="bi bi-upc"></i> {{ $product->barcode }}</span> @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 text-gray-600">
                                                {{ $product->category?->name ?: '-' }}
                                            </td>
                                            <td class="px-6 py-4 text-right font-mono text-gray-600">
                                                Rp {{ number_format($product->cost_price, 0, ',', '.') }}
                                            </td>
                                            <td class="px-6 py-4 text-right font-mono font-medium text-gray-900">
                                                Rp {{ number_format($product->price, 0, ',', '.') }}
                                            </td>
                                            <td class="px-6 py-4 text-center">
                                                @if($product->stock_qty <= 5)
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                        {{ $product->stock_qty }}
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                        {{ $product->stock_qty }}
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 text-center">
                                                @if($product->is_active)
                                                    <span class="inline-flex h-2 w-2 rounded-full bg-green-500" title="Aktif"></span>
                                                @else
                                                    <span class="inline-flex h-2 w-2 rounded-full bg-gray-400" title="Nonaktif"></span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 text-center">
                                                <div class="flex items-center justify-center gap-2">
                                                    <a href="{{ route('admin.product.show', $product) }}" class="p-2 rounded-lg text-gray-500 hover:bg-blue-50 hover:text-blue-600 transition-colors" title="Detail">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    <a href="{{ route('admin.product.edit', $product) }}" class="p-2 rounded-lg text-gray-500 hover:bg-yellow-50 hover:text-yellow-600 transition-colors" title="Edit">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <button type="button" onclick="confirmDelete('{{ route('admin.product.destroy', $product) }}', '{{ $product->name }}')" 
                                                            class="p-2 rounded-lg text-gray-500 hover:bg-red-50 hover:text-red-600 transition-colors" title="Hapus">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="px-6 py-12 text-center text-gray-500 text-sm">
                                                <div class="flex flex-col items-center">
                                                    <div class="h-16 w-16 bg-gray-100 rounded-full flex items-center justify-center mb-3">
                                                        <i class="bi bi-box-seam text-2xl text-gray-400"></i>
                                                    </div>
                                                    <p class="font-medium text-gray-900">Tidak ada produk ditemukan</p>
                                                    <p class="text-gray-500 mt-1">Coba sesuaikan filter pencarian atau tambah produk baru.</p>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        @if($products->hasPages())
                            <div class="px-6 py-4 border-t border-gray-100 bg-gray-50">
                                {{ $products->withQueryString()->links() }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Import Modal -->
    <div id="importModal" class="fixed inset-0 bg-gray-900/50 hidden z-50 flex items-center justify-center transition-opacity opacity-0 pointer-events-none" style="transition: opacity 0.3s ease;">
        <div class="bg-white w-full max-w-lg rounded-2xl shadow-2xl transform scale-95 transition-transform duration-300" style="transition: transform 0.3s ease;">
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
                <h3 class="text-xl font-bold text-gray-900">Import Data Produk</h3>
                <button onclick="closeImportModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="bi bi-x-lg text-lg"></i>
                </button>
            </div>
            
            <form action="{{ route('admin.product.import') }}" method="POST" enctype="multipart/form-data" id="importForm" class="p-6">
                @csrf
                <input type="hidden" name="import_token" id="importToken" value="">
                <div class="space-y-4" id="uploadSection">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Upload File (Excel/CSV)</label>
                        <div class="relative border-2 border-dashed border-gray-300 rounded-xl p-6 text-center hover:bg-gray-50 hover:border-blue-500 transition-colors cursor-pointer" onclick="document.getElementById('fileInput').click()">
                            <input type="file" name="file" id="fileInput" class="hidden" accept=".csv,.txt,.xlsx,.xls" required onchange="updateFileName(this)">
                            <i class="bi bi-cloud-arrow-up text-3xl text-gray-400 mb-2 block"></i>
                            <span id="fileName" class="text-gray-600 text-sm">Klik untuk memilih file</span>
                            <p class="text-xs text-gray-400 mt-1">Max: 10MB</p>
                        </div>
                    </div>

                    <div class="bg-blue-50 border border-blue-100 rounded-xl p-4 flex gap-3">
                        <i class="bi bi-info-circle-fill text-blue-600 mt-0.5"></i>
                        <div>
                            <h4 class="text-sm font-bold text-blue-800">Butuh Template?</h4>
                            <p class="text-xs text-blue-600 mt-1 mb-2">Gunakan template resmi agar import berhasil tanpa error.</p>
                            <a href="{{ route('admin.product.download-template') }}" class="text-xs font-bold text-blue-700 hover:underline">
                                <i class="bi bi-download"></i> Download Template
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Preview Section (hidden by default) -->
                <div id="previewSection" class="hidden space-y-4">
                    <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4">
                        <div class="flex items-center gap-3 mb-2">
                            <i class="bi bi-eye-fill text-yellow-600 text-lg"></i>
                            <h4 class="font-bold text-yellow-800">Pratinjau Perubahan</h4>
                        </div>
                        <p class="text-sm text-yellow-700 mb-3" id="previewSummary"></p>
                        <div class="max-h-64 overflow-y-auto custom-scrollbar">
                            <table class="w-full text-xs text-left">
                                <thead class="bg-yellow-100 text-yellow-800">
                                    <tr>
                                        <th class="px-2 py-1.5">#</th>
                                        <th class="px-2 py-1.5">SKU (Spreadsheet)</th>
                                        <th class="px-2 py-1.5">Produk</th>
                                        <th class="px-2 py-1.5">SKU Lama</th>
                                        <th class="px-2 py-1.5">SKU Baru</th>
                                        <th class="px-2 py-1.5">Harga Modal Lama</th>
                                        <th class="px-2 py-1.5">Harga Modal Baru</th>
                                        <th class="px-2 py-1.5">Harga Jual Lama</th>
                                        <th class="px-2 py-1.5">Harga Jual Baru</th>
                                        <th class="px-2 py-1.5">Status</th>
                                    </tr>
                                </thead>
                                <tbody id="previewTableBody" class="divide-y divide-yellow-100"></tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="mt-8 flex justify-end gap-3" id="importButtons">
                    <button type="button" onclick="closeImportModal()" class="px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-xl transition">Batal</button>
                    <button type="button" id="previewBtn" onclick="previewImport()" class="px-6 py-2 bg-yellow-600 text-white font-bold rounded-xl hover:bg-yellow-700 shadow-lg shadow-yellow-200 transition flex items-center gap-2">
                        <i class="bi bi-eye"></i>
                        <span>Pratinjau</span>
                    </button>
                    <button type="submit" id="importBtn" class="hidden px-6 py-2 bg-blue-600 text-white font-bold rounded-xl hover:bg-blue-700 shadow-lg shadow-blue-200 transition flex items-center gap-2">
                        <span>Konfirmasi Import</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Modal -->
    <div id="deleteModal" class="fixed inset-0 bg-gray-900/50 hidden z-50 flex items-center justify-center transition-opacity opacity-0 pointer-events-none">
        <div class="bg-white w-full max-w-md rounded-2xl shadow-xl p-6 transform scale-95 transition-transform duration-300">
            <div class="text-center mb-6">
                <div class="h-14 w-14 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="bi bi-trash text-2xl text-red-600"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-900">Hapus Produk?</h3>
                <p class="text-gray-500 text-sm mt-2">Anda akan menghapus <strong id="deleteProductName"></strong>. Data yang dihapus tidak dapat dikembalikan.</p>
            </div>
            <div class="flex justify-center gap-3">
                <button onclick="closeDeleteModal()" class="px-5 py-2.5 bg-gray-100 text-gray-700 font-medium rounded-xl hover:bg-gray-200 transition">Batal</button>
                <form id="deleteForm" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="px-5 py-2.5 bg-red-600 text-white font-bold rounded-xl hover:bg-red-700 shadow-lg shadow-red-200 transition">Ya, Hapus</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Export Dropdown Logic
        function toggleExportMenu() {
            const menu = document.getElementById('exportMenu');
            if (menu.classList.contains('hidden')) {
                menu.classList.remove('hidden', 'opacity-0', 'scale-95');
                menu.classList.add('opacity-100', 'scale-100');
            } else {
                menu.classList.remove('opacity-100', 'scale-100');
                menu.classList.add('opacity-0', 'scale-95');
                setTimeout(() => menu.classList.add('hidden'), 200);
            }
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const dropdown = document.getElementById('exportDropdown');
            const menu = document.getElementById('exportMenu');
            if (dropdown && !dropdown.contains(event.target)) {
                menu.classList.remove('opacity-100', 'scale-100');
                menu.classList.add('opacity-0', 'scale-95');
                setTimeout(() => menu.classList.add('hidden'), 200);
            }
        });

        function toggleSidebar() {
            const el = document.getElementById('sidebar');
            if(el) el.classList.toggle('-translate-x-full');
        }

        function toggleDropdown(btn) {
            const menu = btn.nextElementSibling;
            const arrow = btn.querySelector('.bi-chevron-down');
            if (menu.style.maxHeight && menu.style.maxHeight !== '0px') {
                menu.style.maxHeight = '0px';
                arrow.classList.remove('rotate-180');
            } else {
                menu.style.maxHeight = menu.scrollHeight + 'px';
                arrow.classList.add('rotate-180');
            }
        }

        // Modal Logic
        const importModal = document.getElementById('importModal');
        const deleteModal = document.getElementById('deleteModal');

        function openImportModal() {
            importModal.classList.remove('hidden', 'pointer-events-none', 'opacity-0');
            importModal.querySelector('div').classList.remove('scale-95');
            importModal.querySelector('div').classList.add('scale-100');
        }

        function closeImportModal() {
            importModal.classList.add('opacity-0', 'pointer-events-none');
            importModal.querySelector('div').classList.remove('scale-100');
            importModal.querySelector('div').classList.add('scale-95');
            setTimeout(() => importModal.classList.add('hidden'), 300);
        }

        function updateFileName(input) {
            if (input.files && input.files[0]) {
                document.getElementById('fileName').textContent = input.files[0].name;
                document.getElementById('fileName').classList.add('text-blue-600', 'font-medium');
            }
        }

        function confirmDelete(url, name) {
            deleteModal.classList.remove('hidden', 'pointer-events-none', 'opacity-0');
            deleteModal.querySelector('div').classList.remove('scale-95');
            deleteModal.querySelector('div').classList.add('scale-100');
            
            document.getElementById('deleteProductName').textContent = name;
            document.getElementById('deleteForm').action = url;
        }

        function closeDeleteModal() {
            deleteModal.classList.add('opacity-0', 'pointer-events-none');
            deleteModal.querySelector('div').classList.remove('scale-100');
            deleteModal.querySelector('div').classList.add('scale-95');
            setTimeout(() => deleteModal.classList.add('hidden'), 300);
        }

        // Preview Import Logic
        function previewImport() {
            const fileInput = document.getElementById('fileInput');
            if (!fileInput.files || !fileInput.files[0]) {
                alert('Pilih file terlebih dahulu.');
                return;
            }

            const btn = document.getElementById('previewBtn');
            const originalHtml = btn.innerHTML;
            btn.innerHTML = '<span class="animate-spin h-5 w-5 border-2 border-white border-t-transparent rounded-full mr-2"></span> Menganalisis...';
            btn.disabled = true;

            const formData = new FormData();
            formData.append('file', fileInput.files[0]);
            formData.append('_token', document.querySelector('input[name="_token"]').value);

            fetch('{{ route("admin.product.preview-import") }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                }
            })
            .then(res => res.json())
            .then(data => {
                btn.innerHTML = originalHtml;
                btn.disabled = false;

                if (!data.success) {
                    alert(data.message || 'Gagal membaca file.');
                    return;
                }

                // Show preview
                document.getElementById('uploadSection').classList.add('hidden');
                document.getElementById('previewSection').classList.remove('hidden');
                document.getElementById('previewBtn').classList.add('hidden');
                document.getElementById('importBtn').classList.remove('hidden');
                document.getElementById('importToken').value = data.import_token;

                // Summary
                let summary = `Total: ${data.preview.length} baris. `;
                if (data.total_updates > 0) summary += `Update: ${data.total_updates} produk. `;
                if (data.total_inserts > 0) summary += `Produk baru: ${data.total_inserts}. `;
                if (data.total_unchanged > 0) summary += `Tanpa perubahan: ${data.total_unchanged}.`;
                document.getElementById('previewSummary').textContent = summary;

                // Table body
                let tbody = '';
                data.preview.forEach(r => {
                    let actionBadge = '';
                    let actionType = r.action_type || r.action;
                    if (actionType === 'update') {
                        actionBadge = '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">✏️ ' + r.action + '</span>';
                    } else if (actionType === 'insert') {
                        actionBadge = '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">➕ ' + r.action + '</span>';
                    } else {
                        actionBadge = '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">✅ ' + r.action + '</span>';
                    }

                    // SKU display: highlight when new SKU is being added
                    let oldSku = (r.old_sku ?? '-') || '-';
                    let newSku = r.new_sku ? '<span class="font-bold text-emerald-700">' + r.new_sku + '</span>' : '-';

                    tbody += `<tr class="hover:bg-yellow-50">
                        <td class="px-2 py-1.5">${r.row}</td>
                        <td class="px-2 py-1.5 font-mono text-xs">${r.sku || '-'}</td>
                        <td class="px-2 py-1.5">${r.product_name}</td>
                        <td class="px-2 py-1.5 font-mono text-xs">${oldSku}</td>
                        <td class="px-2 py-1.5 font-mono text-xs">${newSku}</td>
                        <td class="px-2 py-1.5 text-right">${r.old_cost_price !== null ? 'Rp ' + r.old_cost_price.toLocaleString('id-ID') : '-'}</td>
                        <td class="px-2 py-1.5 text-right font-bold">${r.new_cost_price !== null ? 'Rp ' + r.new_cost_price.toLocaleString('id-ID') : '-'}</td>
                        <td class="px-2 py-1.5 text-right">${r.old_price !== null ? 'Rp ' + r.old_price.toLocaleString('id-ID') : '-'}</td>
                        <td class="px-2 py-1.5 text-right font-bold">${r.new_price !== null ? 'Rp ' + r.new_price.toLocaleString('id-ID') : '-'}</td>
                        <td class="px-2 py-1.5">${actionBadge}</td>
                    </tr>`;
                });
                document.getElementById('previewTableBody').innerHTML = tbody;
            })
            .catch(err => {
                btn.innerHTML = originalHtml;
                btn.disabled = false;
                alert('Gagal: ' + err.message);
            });
        }

        // Import Form Loading State
        document.getElementById('importForm').addEventListener('submit', function() {
            const btn = document.getElementById('importBtn');
            btn.innerHTML = '<span class="animate-spin h-5 w-5 border-2 border-white border-t-transparent rounded-full mr-2"></span> Mengimport...';
            btn.disabled = true;
            btn.classList.add('opacity-75', 'cursor-not-allowed');
        });
    </script>
</body>
</html>