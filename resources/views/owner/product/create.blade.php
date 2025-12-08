<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Tambah Produk - Pare Custom</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css" />
    <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Raleway', sans-serif;
            background-color: #f8fafc;
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
        
        /* Enhanced Input Styles */
        .form-input {
            transition: all 0.3s ease;
            border: 2px solid #e2e8f0;
            border-radius: 0.5rem;
        }
        .form-input:focus {
            transform: translateY(-1px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            border-color: #3b82f6;
        }
        .input-group:hover label {
            color: #2563eb;
        }

        /* Enhanced Dropzone */
        .dropzone {
            border: 2px dashed #e2e8f0;
            border-radius: 0.5rem;
            padding: 2rem;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
            background-color: #f8fafc;
        }
        .dropzone:hover {
            border-color: #3b82f6;
            background-color: #f0f7ff;
        }
        .dropzone.dragover {
            border-color: #2563eb;
            background-color: #e8f2ff;
        }

        /* Floating Labels */
        .floating-label {
            transform: translateY(-50%) scale(0.85);
            background-color: white;
            padding: 0 0.25rem;
        }

        /* Enhanced Card Style */
        .card {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            border: 1px solid #f1f5f9;
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="flex">

        <x-navbar-owner></x-navbar-owner>

        <div class="flex-1 lg:w-5/6">
            <x-navbar-top-owner></x-navbar-top-owner>

            <div class="p-4 lg:p-8">
                <div class="max-w-2xl mx-auto">
                    <!-- Enhanced Card Design -->
                    <div class="card">
                        <div class="border-b border-gray-100 px-8 py-6">
                            <h1 class="text-2xl font-semibold text-gray-900">Tambah Produk Baru</h1>
                            <p class="mt-2 text-gray-600 text-sm">Lengkapi informasi produk yang akan ditambahkan</p>
                        </div>

                        <form action="{{ route('owner.product.store') }}" method="POST" enctype="multipart/form-data" class="p-8">
                            @csrf

                            <!-- Enhanced Input Groups -->
                            <div class="space-y-6">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="input-group relative">
                                        <label for="sku" class="absolute -top-2 left-2 z-10 px-1 text-xs font-medium text-gray-500 floating-label">
                                            SKU (Opsional)
                                        </label>
                                        <input type="text" 
                                               name="sku" 
                                               id="sku"
                                               class="form-input block w-full px-4 py-3 text-gray-700"
                                               value="{{ old('sku') }}">
                                        @error('sku')
                                            <p class="mt-2 text-sm text-red-600">
                                                <i class="bi bi-exclamation-circle mr-1"></i>
                                                {{ $message }}
                                            </p>
                                        @enderror
                                    </div>
                                    
                                    <div class="input-group relative">
                                        <label for="barcode" class="absolute -top-2 left-2 z-10 px-1 text-xs font-medium text-gray-500 floating-label">
                                            Barcode (Opsional)
                                        </label>
                                        <input type="text" 
                                               name="barcode" 
                                               id="barcode"
                                               class="form-input block w-full px-4 py-3 text-gray-700"
                                               value="{{ old('barcode') }}">
                                        @error('barcode')
                                            <p class="mt-2 text-sm text-red-600">
                                                <i class="bi bi-exclamation-circle mr-1"></i>
                                                {{ $message }}
                                            </p>
                                        @enderror
                                    </div>
                                    
                                    <div class="input-group relative">
                                        <label for="name" class="absolute -top-2 left-2 z-10 px-1 text-xs font-medium text-gray-500 floating-label">
                                            Nama Produk
                                        </label>
                                        <input type="text" 
                                               name="name" 
                                               id="name"
                                               class="form-input block w-full px-4 py-3 text-gray-700"
                                               value="{{ old('name') }}"
                                               required>
                                        @error('name')
                                            <p class="mt-2 text-sm text-red-600">
                                                <i class="bi bi-exclamation-circle mr-1"></i>
                                                {{ $message }}
                                            </p>
                                        @enderror
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div class="input-group relative md:col-span-1">
                                        <label for="category_id" class="absolute -top-2 left-2 z-10 px-1 text-xs font-medium text-gray-500 floating-label">
                                            Kategori
                                        </label>
                                        <select name="category_id" id="category_id" class="form-input block w-full px-4 py-3 text-gray-700">
                                            <option value="">-</option>
                                            @foreach($categories as $cat)
                                                <option value="{{ $cat->id }}" @selected(old('category_id')==$cat->id)>{{ $cat->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('category_id')
                                            <p class="mt-2 text-sm text-red-600">
                                                <i class="bi bi-exclamation-circle mr-1"></i>
                                                {{ $message }}
                                            </p>
                                        @enderror
                                    </div>

                                    <div class="input-group relative md:col-span-1">
                                        <label for="stock_qty" class="absolute -top-2 left-2 z-10 px-1 text-xs font-medium text-gray-500 floating-label">
                                            Qty Stok
                                        </label>
                                        <input type="number" name="stock_qty" id="stock_qty" class="form-input block w-full px-4 py-3 text-gray-700" value="{{ old('stock_qty', 0) }}" min="0">
                                        @error('stock_qty')
                                            <p class="mt-2 text-sm text-red-600">
                                                <i class="bi bi-exclamation-circle mr-1"></i>
                                                {{ $message }}
                                            </p>
                                        @enderror
                                    </div>
                                    
                                    <div class="flex items-center pt-6 md:col-span-1">
                                        <input type="checkbox" id="is_active" name="is_active" value="1" class="mr-2" checked>
                                        <label for="is_active" class="text-sm text-gray-700">Aktif</label>
                                    </div>
                                </div>

                                <div class="input-group relative">
                                    <label for="cost_price" class="absolute -top-2 left-2 z-10 px-1 text-xs font-medium text-gray-500 floating-label">
                                        Harga Modal
                                    </label>
                                    <input type="number" 
       name="cost_price" 
       id="cost_price"
       class="form-input block w-full px-4 py-3 text-gray-700"
       value="{{ old('cost_price') }}"
       step="0.01"
       required>
                                    @error('cost_price')
                                        <p class="mt-2 text-sm text-red-600">
                                            <i class="bi bi-exclamation-circle mr-1"></i>
                                            {{ $message }}
                                        </p>
                                    @enderror
                                </div>

                                <div class="input-group relative">
                                    <label for="price" class="absolute -top-2 left-2 z-10 px-1 text-xs font-medium text-gray-500 floating-label">
                                        Harga Jual
                                    </label>
                                    <input type="number" 
       name="price" 
       id="price"
       class="form-input block w-full px-4 py-3 text-gray-700"
       value="{{ old('price') }}"
       step="0.01"
       required>
                                    @error('price')
                                        <p class="mt-2 text-sm text-red-600">
                                            <i class="bi bi-exclamation-circle mr-1"></i>
                                            {{ $message }}
                                        </p>
                                    @enderror
                                </div>

                                <!-- Enhanced Dropzone -->
                                <div class="input-group">
                                    <label class="block text-sm font-medium text-gray-500 mb-2">Gambar Produk</label>
                                    <div class="dropzone" id="dropzone">
                                        <i class="bi bi-cloud-arrow-up text-gray-400 text-4xl mb-4"></i>
                                        <p class="text-gray-500">Drag & drop gambar di sini, atau klik untuk memilih gambar</p>
                                        <input type="file" name="image" id="image" class="hidden" accept="image/*">
                                        <img id="preview" class="mt-4 max-h-40 mx-auto hidden rounded-lg">
                                    </div>
                                    @error('image')
                                        <p class="mt-2 text-sm text-red-600">
                                            <i class="bi bi-exclamation-circle mr-1"></i>
                                            {{ $message }}
                                        </p>
                                    @enderror
                                </div>

                                <!-- Enhanced Buttons -->
                                <div class="flex justify-end space-x-4 pt-6 border-t">
                                    <a href="{{ route('owner.product.index') }}"
                                       class="px-6 py-2.5 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-all duration-200">
                                        <i class="bi bi-arrow-left mr-2"></i>
                                        Kembali
                                    </a>
                                    <button type="submit" 
                                            class="px-6 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-all duration-200">
                                        <i class="bi bi-check-lg mr-2"></i>
                                        Simpan
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Enhanced Dropzone Functionality
        const dropzone = document.getElementById('dropzone');
        const imageInput = document.getElementById('image');
        const previewImg = document.getElementById('preview');

        dropzone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropzone.classList.add('dragover');
        });

        dropzone.addEventListener('dragleave', () => {
            dropzone.classList.remove('dragover');
        });

        dropzone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropzone.classList.remove('dragover');
            const files = e.dataTransfer.files;
            handleFiles(files);
        });

        dropzone.addEventListener('click', () => {
            imageInput.click();
        });

        imageInput.addEventListener('change', (e) => {
            handleFiles(e.target.files);
        });

        function handleFiles(files) {
            if (files.length > 0) {
                const file = files[0];
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        previewImg.src = e.target.result;
                        previewImg.classList.remove('hidden');
                    };
                    reader.readAsDataURL(file);
                }
            }
        }

        // Existing toggle functions
        function toggleDropdown(button) {
          const dropdownMenus = document.querySelectorAll(".dropdown-menu");
          const dropdownArrows = document.querySelectorAll("i.bi-chevron-down");

          // Tutup semua dropdown kecuali yang dipilih
          dropdownMenus.forEach((menu) => {
            if (menu !== button.nextElementSibling) {
              menu.classList.add("max-h-0");
              menu.classList.remove("max-h-40");
            }
          });

          // Atur semua panah kecuali yang dipilih
          dropdownArrows.forEach((arrow) => {
            if (arrow !== button.querySelector("i.bi-chevron-down")) {
              arrow.classList.remove("rotate-180");
            }
          });

          // Toggle dropdown yang dipilih
          const dropdownMenu = button.nextElementSibling;
          const dropdownArrow = button.querySelector("i.bi-chevron-down");

          if (dropdownMenu.classList.contains("max-h-0")) {
            dropdownMenu.classList.remove("max-h-0");
            dropdownMenu.classList.add("max-h-40");
            dropdownArrow.classList.add("rotate-180");
          } else {
            dropdownMenu.classList.add("max-h-0");
            dropdownMenu.classList.remove("max-h-40");
            dropdownArrow.classList.remove("rotate-180");
          }
        }
    </script>
</body>
</html>