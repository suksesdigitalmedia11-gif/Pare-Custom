<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Produk - Bblara</title>
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
        .stat-card {
            transition: all 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
        }
        .action-button {
            transition: all 0.2s ease;
        }
        .action-button:hover {
            transform: translateY(-1px);
        }
        .product-badge {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        }
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(245, 158, 11, 0); }
            100% { box-shadow: 0 0 0 0 rgba(245, 158, 11, 0); }
        }
        .pulse-animation {
            animation: pulse 2s infinite;
        }
        .product-image-container {
            aspect-ratio: 1/1;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        .product-image-container:hover {
            transform: scale(1.02);
        }
    </style>
</head>
<body class="bg-gray-50">
  @php
  // Calculate margin and percentage
  $margin = $product->price - $product->cost_price;
  $marginPercentage = ($product->cost_price > 0) ? ($margin / $product->cost_price) * 100 : 0;

  // Normalize percentage for progress bar
  $normalizedPercentage = $marginPercentage;
  if ($marginPercentage >= 50) {
      $normalizedPercentage = 100;
  } elseif ($marginPercentage >= 25) {
      $normalizedPercentage = 66.67 + (($marginPercentage - 25) / 25) * 33.33;
  } elseif ($marginPercentage >= 10) {
      $normalizedPercentage = 33.33 + (($marginPercentage - 10) / 15) * 33.34;
  } else {
      $normalizedPercentage = ($marginPercentage / 10) * 33.33;
  }

  // Set status and colors based on margin percentage
  if ($marginPercentage >= 50) {
      $status = 'Sangat Baik';
      $statusColor = 'text-green-600 bg-green-100';
      $progressColor = 'bg-green-500';
  } elseif ($marginPercentage >= 25) {
      $status = 'Baik';
      $statusColor = 'text-blue-600 bg-blue-100';
      $progressColor = 'bg-blue-500';
  } elseif ($marginPercentage >= 10) {
      $status = 'Cukup';
      $statusColor = 'text-yellow-600 bg-yellow-100';
      $progressColor = 'bg-yellow-500';
  } else {
      $status = 'Buruk';
      $statusColor = 'text-red-600 bg-red-100';
      $progressColor = 'bg-red-500';
  }
  @endphp
    <div class="flex">
        
        <!-- Sidebar -->
        <x-navbar-owner></x-navbar-owner>
        
        <!-- Main Content -->
        <div class="flex-1 lg:w-5/6">
            <!-- Top Navigation -->
            <x-navbar-top-owner></x-navbar-top-owner>
            
            <!-- Content Area -->
            <div class="p-4 lg:p-8">
                <div class="max-w-6xl mx-auto">
                    <!-- Breadcrumb -->
                    <nav class="flex mb-8" aria-label="Breadcrumb">
                        <ol class="inline-flex items-center space-x-1 md:space-x-3">
                            <li class="inline-flex items-center">
                                <a href="{{ route('owner.product.index') }}" class="inline-flex items-center text-gray-500 hover:text-amber-600">
                                    <i class="bi bi-house-door mr-2"></i>
                                    Produk
                                </a>
                            </li>
                            <li>
                                <div class="flex items-center">
                                    <i class="bi bi-chevron-right text-gray-400 mx-2"></i>
                                    <span class="text-gray-700">Detail Produk</span>
                                </div>
                            </li>
                        </ol>
                    </nav>

                    <!-- Main Content Card -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                        <!-- Header -->
                        <div class="border-b border-gray-100 px-8 py-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h1 class="text-2xl font-semibold text-gray-800">Detail Produk</h1>
                                    <p class="mt-2 text-gray-600">Informasi lengkap mengenai produk</p>
                                </div>
                                <span class="product-badge px-4 py-2 rounded-full text-white text-sm font-medium">
                                    ID: #{{ $product->id }}
                                </span>
                            </div>
                        </div>

                        <!-- Product Content -->
                        <div class="p-8">
                            <!-- Product Grid -->
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                                <!-- Left Column - Image -->
                                <div class="product-image-container rounded-xl shadow-md overflow-hidden">
                                    <img src="{{ Storage::url($product->image_path) }}" 
                                         alt="{{ $product->name }}" 
                                         class="w-full h-full object-cover transition-transform hover:scale-105">
                                </div>

                                <!-- Right Column - Details -->
                                <div class="space-y-6">
                                    <!-- Stats Grid -->
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <!-- Name Card -->
                                        <div class="stat-card col-span-2 bg-white rounded-lg border border-gray-100 p-6">
                                            <div class="flex items-center">
                                                <div class="p-3 bg-amber-100 rounded-full">
                                                    <i class="bi bi-tag text-amber-600 text-xl"></i>
                                                </div>
                                                <div class="ml-4">
                                                    <p class="text-sm font-medium text-gray-500">Nama Produk</p>
                                                    <h3 class="text-lg font-semibold text-gray-800">{{ $product->name }}</h3>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Cost Price Card -->
                                        <div class="stat-card bg-white rounded-lg border border-gray-100 p-6">
                                            <div class="flex items-center">
                                                <div class="p-3 bg-blue-100 rounded-full">
                                                    <i class="bi bi-cash-stack text-blue-600 text-xl"></i>
                                                </div>
                                                <div class="ml-4">
                                                    <p class="text-sm font-medium text-gray-500">Harga Modal</p>
                                                    <h3 class="text-lg font-semibold text-gray-800">Rp {{ number_format($product->cost_price, 2, ',', '.') }}</h3>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Selling Price Card -->
                                        <div class="stat-card bg-white rounded-lg border border-gray-100 p-6">
                                            <div class="flex items-center">
                                                <div class="p-3 bg-green-100 rounded-full">
                                                    <i class="bi bi-currency-dollar text-green-600 text-xl"></i>
                                                </div>
                                                <div class="ml-4">
                                                    <p class="text-sm font-medium text-gray-500">Harga Jual</p>
                                                    <h3 class="text-lg font-semibold text-gray-800">Rp {{ number_format($product->price, 2, ',', '.') }}</h3>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Stock Qty Card -->
                                        <div class="stat-card bg-white rounded-lg border border-gray-100 p-6">
                                            <div class="flex items-center">
                                                <div class="p-3 bg-purple-100 rounded-full">
                                                    <i class="bi bi-box-seam text-purple-600 text-xl"></i>
                                                </div>
                                                <div class="ml-4">
                                                    <p class="text-sm font-medium text-gray-500">Qty Stok</p>
                                                    <h3 class="text-lg font-semibold text-gray-800">{{ $product->stock_qty }}</h3>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Margin Analysis Section -->
                                    <div class="bg-white rounded-lg border border-gray-100 p-6">
                                        <div class="space-y-4">
                                            <!-- Margin Header -->
                                            <div class="flex justify-between items-center">
                                                <h3 class="text-lg font-semibold text-gray-800">Analisis Margin</h3>
                                                <span class="px-3 py-1 rounded-full text-sm font-medium {{ $statusColor }}">
                                                    {{ $status }}
                                                </span>
                                            </div>

                                            <!-- Progress Section -->
                                            <div class="space-y-4">
                                                <div class="flex items-baseline space-x-2">
                                                    <span class="text-2xl font-bold {{ $marginPercentage >= 50 ? 'text-green-600' : ($marginPercentage >= 25 ? 'text-blue-600' : ($marginPercentage >= 10 ? 'text-yellow-600' : 'text-red-600')) }}">
                                                        {{ number_format($marginPercentage, 1) }}%
                                                    </span>
                                                    <span class="text-sm text-gray-500">margin profit</span>
                                                </div>

                                                <!-- Progress Bar -->
                                                <div class="relative pt-1">
                                                    <div class="overflow-hidden h-3 text-xs flex rounded bg-gray-200">
                                                        <div class="progress-bar shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center {{ $progressColor }}"
                                                            style="width: {{ $normalizedPercentage }}%">
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Progress Markers -->
                                                <div class="grid grid-cols-4 text-xs gap-2">
                                                    <div class="text-center">
                                                        <span class="block bg-red-100 px-2 py-1 rounded">0-10%</span>
                                                        <span class="block mt-1 text-gray-600">Buruk</span>
                                                    </div>
                                                    <div class="text-center">
                                                        <span class="block bg-yellow-100 px-2 py-1 rounded">10-24%</span>
                                                        <span class="block mt-1 text-gray-600">Cukup</span>
                                                    </div>
                                                    <div class="text-center">
                                                        <span class="block bg-blue-100 px-2 py-1 rounded">25-49%</span>
                                                        <span class="block mt-1 text-gray-600">Baik</span>
                                                    </div>
                                                    <div class="text-center">
                                                        <span class="block bg-green-100 px-2 py-1 rounded">≥50%</span>
                                                        <span class="block mt-1 text-gray-600">Sangat Baik</span>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Status Description -->
                                            <div class="mt-4 p-4 rounded-lg {{ $statusColor }} bg-opacity-50">
                                                <p class="text-sm">
                                                    @if($marginPercentage >= 50)
                                                        Margin profit sangat baik! Produk ini memberikan keuntungan yang optimal.
                                                    @elseif($marginPercentage >= 25)
                                                        Margin profit dalam kondisi baik. Terus pertahankan dan cari peluang untuk ditingkatkan.
                                                    @elseif($marginPercentage >= 10)
                                                        Margin profit cukup. Ada ruang untuk peningkatan melalui optimasi harga atau pengurangan biaya.
                                                    @else
                                                        Margin profit dalam kondisi buruk. Segera lakukan penyesuaian harga atau evaluasi biaya produksi.
                                                    @endif
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Action Buttons -->
                                    <div class="flex items-center justify-end space-x-4 pt-4">
                                        <a href="{{ route('owner.product.index') }}" 
                                           class="action-button inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500">
                                            <i class="bi bi-arrow-left mr-2"></i>
                                            Kembali
                                        </a>
                                        <a href="{{ route('owner.product.edit', $product) }}" 
                                           class="action-button inline-flex items-center px-4 py-2 border border-transparent rounded-lg text-white bg-amber-500 hover:bg-amber-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500">
                                            <i class="bi bi-pencil mr-2"></i>
                                            Edit
                                        </a>
                                        <form action="{{ route('owner.product.destroy', $product) }}" 
                                              method="POST" 
                                              class="inline" 
                                              onsubmit="return confirm('Apakah Anda yakin ingin menghapus produk ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" 
                                                    class="action-button inline-flex items-center px-4 py-2 border border-transparent rounded-lg text-white bg-red-500 hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                                <i class="bi bi-trash mr-2"></i>
                                                Hapus
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
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

        // Add hover animation to stat cards
        document.querySelectorAll('.stat-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.classList.add('pulse-animation');
            });
            card.addEventListener('mouseleave', function() {
                this.classList.remove('pulse-animation');
            });
        });

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