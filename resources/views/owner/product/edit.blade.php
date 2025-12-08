<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Produk - Pare Custom</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
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
        a:hover .nav-text::after {
            width: 100%;
        }
        .form-input {
            transition: all 0.3s ease;
            border: 2px solid #e2e8f0;
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            width: 100%;
            background-color: #fff;
        }
        .form-input:focus {
            border-color: #f59e0b;
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.1);
            outline: none;
        }
        .form-input:hover {
            border-color: #f59e0b;
        }
        .dropzone {
            border: 2px dashed #e2e8f0;
            border-radius: 1rem;
            padding: 2rem;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
            background-color: #fff;
        }
        .dropzone:hover {
            border-color: #f59e0b;
            background-color: #fffbeb;
        }
        .dropzone.dragover {
            border-color: #f59e0b;
            background-color: #fffbeb;
            transform: scale(1.02);
        }
        .action-button {
            transition: all 0.3s ease;
        }
        .action-button:hover {
            transform: translateY(-1px);
        }
        .preview-image {
            transition: all 0.3s ease;
        }
        .preview-image:hover {
            transform: scale(1.05);
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="flex">
        
        <!-- Sidebar -->
        <x-navbar-owner></x-navbar-owner>
        
        <!-- Main Content -->
        <div class="flex-1 lg:w-5/6">
            <!-- Top Navigation -->
            <x-navbar-top-owner></x-navbar-top-owner>
            
            <!-- Content Area -->
            <div class="p-4 lg:p-8">
                <div class="max-w-4xl mx-auto">
                    <!-- Breadcrumb -->
                    <nav class="flex mb-8" aria-label="Breadcrumb">
                      <ol class="inline-flex items-center space-x-1 md:space-x-3">
                          <li class="inline-flex items-center">
                              <a href="{{ route('owner.product.index') }}" class="hover-link">
                                  <span class="nav-text inline-flex items-center text-gray-500">
                                      <i class="bi bi-house-door mr-2"></i>
                                      Produk
                                  </span>
                              </a>
                          </li>
                          <li>
                              <div class="flex items-center">
                                  <i class="bi bi-chevron-right text-gray-400 mx-2"></i>
                                  <span class="text-gray-700">Edit Produk</span>
                              </div>
                          </li>
                      </ol>
                    </nav>

                    <!-- Main Card -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                        <!-- Header -->
                        <div class="border-b border-gray-100 px-8 py-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h1 class="text-2xl font-semibold text-gray-800">Edit Produk</h1>
                                    <p class="mt-2 text-gray-600">Perbarui informasi produk Anda</p>
                                </div>
                                <span class="px-4 py-2 bg-amber-100 text-amber-700 rounded-full text-sm font-medium">
                                    ID: #{{ $product->id }}
                                </span>
                            </div>
                        </div>

                        <!-- Form Content -->
                        <form action="{{ route('owner.product.update', $product) }}" method="POST" enctype="multipart/form-data" class="p-8">
                            @csrf
                            @method('PUT')

                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                                <!-- Left Column - Product Details -->
                                <div class="space-y-6">
                                <div>
                                            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                                                <i class="bi bi-tag-fill mr-2 text-amber-500"></i>Nama Produk
                                            </label>
                                            <input type="text" name="name" id="name"
                                                   class="form-input"
                                                   value="{{ old('name', $product->name) }}" 
                                                   placeholder="Masukkan nama produk"
                                                   required>
                                            @error('name')
                                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                             <label for="sku" class="block text-sm font-medium text-gray-700 mb-2">
                                                 <i class="bi bi-upc-scan mr-2 text-amber-500"></i>SKU (Opsional)
                                             </label>
                                             <input type="text" name="sku" id="sku"
                                                    class="form-input"
                                                    value="{{ old('sku', $product->sku) }}"
                                                    placeholder="SKU">
                                             @error('sku')
                                                 <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                             @enderror
                                         </div>
                                         <div>
                                             <label for="barcode" class="block text-sm font-medium text-gray-700 mb-2">
                                                 <i class="bi bi-upc mr-2 text-amber-500"></i>Barcode (Opsional)
                                             </label>
                                             <input type="text" name="barcode" id="barcode"
                                                    class="form-input"
                                                    value="{{ old('barcode', $product->barcode) }}"
                                                    placeholder="Barcode">
                                             @error('barcode')
                                                 <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                             @enderror
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label for="stock_qty" class="block text-sm font-medium text-gray-700 mb-2">
                                                <i class="bi bi-box-seam mr-2 text-purple-500"></i>Qty Stok
                                            </label>
                                            <input type="number" name="stock_qty" id="stock_qty" class="form-input" value="{{ old('stock_qty', $product->stock_qty) }}" min="0">
                                            @error('stock_qty')
                                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                            @enderror
                                        </div>
                                        <div>
                                        <label for="cost_price" class="block text-sm font-medium text-gray-700 mb-2">
                                            <i class="bi bi-cash mr-2 text-blue-500"></i>Harga Modal
                                        </label>
                                        <div class="relative">
                                            <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500">Rp</span>
                                            <input type="number" step="0.01" name="cost_price" id="cost_price"
                                                   class="form-input pl-10"
                                                   value="{{ old('cost_price', preg_replace('/\.00$/','', $product->cost_price)) }}"
                                                   placeholder="0"
                                                   required>
                                        </div>
                                        @error('cost_price')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label for="category_id" class="block text-sm font-medium text-gray-700 mb-2">
                                                <i class="bi bi-folder mr-2 text-blue-500"></i>Kategori
                                            </label>
                                            <select name="category_id" id="category_id" class="form-input">
                                                <option value="">-</option>
                                                @foreach($categories as $cat)
                                                    <option value="{{ $cat->id }}" @selected(old('category_id', $product->category_id)==$cat->id)>{{ $cat->name }}</option>
                                                @endforeach
                                            </select>
                                            @error('category_id')
                                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                            @enderror
                                        </div>
                                        <div>
                                            <label for="is_active" class="block text-sm font-medium text-gray-700 mb-2">
                                                <i class="bi bi-toggle-on mr-2 text-green-500"></i>Status
                                            </label>
                                            <div class="flex items-center h-full pt-2">
                                                <input type="checkbox" name="is_active" id="is_active" value="1" class="mr-2" @checked(old('is_active', $product->is_active))>
                                                <span>Aktif</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div>
                                        <label for="price" class="block text-sm font-medium text-gray-700 mb-2">
                                            <i class="bi bi-currency-dollar mr-2 text-green-500"></i>Harga Jual
                                        </label>
                                        <div class="relative">
                                            <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500">Rp</span>
                                            <input type="number" step="0.01" name="price" id="price"
                                                   class="form-input pl-10"
                                                   value="{{ old('price', preg_replace('/\.00$/','', $product->price)) }}"
                                                   placeholder="0"
                                                   required>
                                        </div>
                                        @error('price')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Right Column - Image Upload -->
                                <div class="space-y-6">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="bi bi-image mr-2 text-purple-500"></i>Gambar Produk
                                    </label>
                                    
                                    @if($product->image_path)
                                        <div class="mb-4">
                                            <p class="text-sm text-gray-500 mb-2">Gambar Saat Ini:</p>
                                            <div class="relative group">
                                                <img src="{{ Storage::url($product->image_path) }}" 
                                                     alt="{{ $product->name }}"
                                                     class="preview-image w-full h-48 object-cover rounded-lg shadow-md">
                                                <div class="absolute inset-0 bg-black bg-opacity-40 opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-lg flex items-center justify-center">
                                                    <span class="text-white text-sm">Gambar saat ini</span>
                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                    <div class="dropzone" id="dropzone">
                                        <input type="file" name="image" id="image" class="hidden" accept="image/*">
                                        <div class="space-y-4">
                                            <i class="bi bi-cloud-arrow-up text-amber-500 text-4xl"></i>
                                            <div class="space-y-2">
                                                <p class="text-gray-700 font-medium">Unggah Gambar Baru</p>
                                                <p class="text-sm text-gray-500">Drag & drop gambar di sini, atau klik untuk memilih</p>
                                                <p class="text-xs text-gray-400">Format yang didukung: JPG, PNG, GIF (Max. 2MB)</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div id="image-preview" class="hidden mt-4">
                                        <p class="text-sm text-gray-500 mb-2">Preview:</p>
                                        <img src="" alt="Preview" class="w-full h-48 object-cover rounded-lg shadow-md">
                                    </div>
                                    @error('image')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex justify-end space-x-4 mt-8 pt-6 border-t">
                                <a href="{{ route('owner.product.index') }}" 
                                   class="action-button inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500">
                                    <i class="bi bi-x-lg mr-2"></i>
                                    Batal
                                </a>
                                <button type="submit" 
                                        class="action-button inline-flex items-center px-4 py-2 border border-transparent rounded-lg text-white bg-amber-500 hover:bg-amber-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500">
                                    <i class="bi bi-check-lg mr-2"></i>
                                    Simpan Perubahan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Sidebar Toggle Function
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            sidebar.classList.toggle('-translate-x-full');
        }

        // Drag and Drop functionality with preview
        const dropzone = document.getElementById('dropzone');
        const imageInput = document.getElementById('image');
        const imagePreview = document.getElementById('image-preview');
        const previewImage = imagePreview.querySelector('img');

        function handleFiles(files) {
            if (files.length > 0) {
                const file = files[0];
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        previewImage.src = e.target.result;
                        imagePreview.classList.remove('hidden');
                        dropzone.querySelector('p').innerText = `File terpilih: ${file.name}`;
                    };
                    reader.readAsDataURL(file);
                }
            }
        }

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
            handleFiles(e.dataTransfer.files);
        });

        dropzone.addEventListener('click', () => {
            imageInput.click();
        });

        imageInput.addEventListener('change', () => {
            handleFiles(imageInput.files);
        });

        // // Price formatting
        // const formatPrice = (input) => {
        //     let value = input.value.replace(/\D/g, '');
        //     input.value = new Intl.NumberFormat('id-ID').format(value);
        // };

        // document.querySelectorAll('input[type="number"]').forEach(input => {
        //     input.addEventListener('input', () => formatPrice(input));
        // });

        // Dropdown functionality
        function toggleDropdown(button) {
            const dropdownMenus = document.querySelectorAll(".dropdown-menu");
            const dropdownArrows = document.querySelectorAll("i.bi-chevron-down");

            dropdownMenus.forEach((menu) => {
                if (menu !== button.nextElementSibling) {
                    menu.classList.add("max-h-0");
                    menu.classList.remove("max-h-40");
                }
            });

            dropdownArrows.forEach((arrow) => {
                if (arrow !== button.querySelector("i.bi-chevron-down")) {
                    arrow.classList.remove("rotate-180");
                }
            });

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