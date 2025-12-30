<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Import Purchase Order - Pare Custom</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css" />
</head>

<body class="bg-gray-100">
    <div class="flex">
        <x-navbar-owner />
        <div class="flex-1 lg:w-5/6">
            <x-navbar-top-owner />
            <div class="p-4 lg:p-8">
                <div class="bg-white p-6 rounded-xl shadow-lg mb-6">
                    <h1 class="text-2xl font-semibold text-gray-800 mb-4">Import Purchase Order</h1>

                    <div class="grid md:grid-cols-2 gap-6 mb-6">
                        <!-- Info Panel -->
                        <div class="bg-blue-50 p-4 rounded-lg">
                            <h3 class="font-semibold text-blue-800 mb-2">📋 Petunjuk Import Purchase</h3>
                            <ul class="text-sm text-blue-700 space-y-1">
                                <li>• Gunakan <strong>template resmi</strong> dari sistem ini.</li>
                                <li>• <strong>PO_NUMBER DIHAPUS</strong> dari template. Sistem akan membuatkan Nomor PO
                                    Otomatis (Format: <code>P250101...</code>).</li>
                                <li>• <strong>Sistem Otomatis Menggabungkan Item (Grouping)</strong>:
                                    <br><span class="text-xs ml-3 text-gray-600">Jika beberapa baris Excel memiliki
                                        <strong>SUPPLIER, TANGGAL ORDER, & TIPE</strong> yang sama persis, mereka akan
                                        digabung jadi 1 Invoice/PO.</span>
                                </li>
                                <li>• <strong>SUPPLIER_NAME wajib diisi</strong>.</li>
                                <li>• Semua hasil import akan masuk sebagai <strong>status Draft</strong> (tidak
                                    mengubah stok otomatis).</li>
                                <li>• Format tanggal: <strong>YYYY-MM-DD</strong> (misal: 2025-01-15).</li>
                            </ul>
                        </div>

                        <!-- Template Download -->
                        <div class="bg-green-50 p-4 rounded-lg">
                            <h3 class="font-semibold text-green-800 mb-2">📥 Template</h3>
                            <p class="text-sm text-green-700 mb-3">
                                Download template untuk memastikan format kolom sesuai dengan sistem.
                            </p>
                            <a href="{{ route('owner.purchases.download-template') }}"
                                class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded shadow inline-flex items-center">
                                <i class="bi bi-download mr-2"></i> Download Template
                            </a>
                        </div>
                    </div>

                    <!-- Visual Guide for Grouping -->
                    <div class="bg-white border rounded-lg p-5 mb-6 shadow-sm">
                        <h3 class="font-bold text-gray-800 text-sm mb-3 flex items-center">
                            <i class="bi bi-info-circle text-blue-600 mr-2"></i> Contoh Cara Mengisi Excel Agar Menjadi
                            1 Faktur (Grouping)
                        </h3>
                        <p class="text-sm text-gray-600 mb-4">
                            Sistem akan otomatis menggabungkan baris-baris barang menjadi <strong>Satu PO /
                                Faktur</strong> jika
                            <span class="bg-yellow-100 text-yellow-800 px-1 rounded font-bold">TANGGAL, SUPPLIER, &
                                TIPE</span>-nya sama persis.
                        </p>

                        <div class="overflow-x-auto border rounded-lg">
                            <table class="w-full text-xs text-left">
                                <thead class="bg-gray-100 uppercase tracking-wider font-semibold text-gray-600">
                                    <tr>
                                        <th class="px-4 py-2 border-b text-red-600">ORDER_DATE *</th>
                                        <th class="px-4 py-2 border-b text-red-600">SUPPLIER_NAME *</th>
                                        <th class="px-4 py-2 border-b text-red-600">PURCHASE_TYPE *</th>
                                        <th class="px-4 py-2 border-b text-blue-600">PRODUCT_NAME</th>
                                        <th class="px-4 py-2 border-b text-blue-600 w-16 text-center">QTY</th>
                                        <th class="px-4 py-2 border-b bg-gray-50 text-gray-800">HASIL DI SISTEM</th>
                                    </tr>
                                </thead>
                                <tbody class="text-gray-700 bg-white">
                                    <!-- Group 1 -->
                                    <tr class="hover:bg-blue-50 transition-colors">
                                        <td class="px-4 py-2 border-b font-medium text-gray-900 border-r border-dashed">
                                            2025-01-10</td>
                                        <td class="px-4 py-2 border-b font-medium text-gray-900 border-r border-dashed">
                                            Toko Kain A</td>
                                        <td class="px-4 py-2 border-b font-medium text-gray-900 border-r border-dashed">
                                            kain</td>
                                        <td class="px-4 py-2 border-b">Kain Drill</td>
                                        <td class="px-4 py-2 border-b text-center font-bold">10</td>
                                        <td class="px-4 py-2 border-b border-l-2 border-blue-500 bg-blue-50/30 text-blue-700 font-bold align-middle"
                                            rowspan="2">
                                            Akan digabung jadi<br>1 Nomor PO Otomatis
                                        </td>
                                    </tr>
                                    <tr class="hover:bg-blue-50 transition-colors">
                                        <td class="px-4 py-2 border-b font-medium text-gray-900 border-r border-dashed">
                                            2025-01-10</td>
                                        <td class="px-4 py-2 border-b font-medium text-gray-900 border-r border-dashed">
                                            Toko Kain A</td>
                                        <td class="px-4 py-2 border-b font-medium text-gray-900 border-r border-dashed">
                                            kain</td>
                                        <td class="px-4 py-2 border-b">Benang Jahit</td>
                                        <td class="px-4 py-2 border-b text-center font-bold">50</td>
                                    </tr>
                                    <!-- Group 2 -->
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td class="px-4 py-2 border-b text-gray-500">2025-01-15</td>
                                        <td class="px-4 py-2 border-b text-gray-500">Konveksi B</td>
                                        <td class="px-4 py-2 border-b text-gray-500">produk_jadi</td>
                                        <td class="px-4 py-2 border-b text-gray-500">Kemeja Polos</td>
                                        <td class="px-4 py-2 border-b text-center text-gray-500">100</td>
                                        <td
                                            class="px-4 py-2 border-b border-l-2 border-gray-300 bg-gray-50 text-gray-400">
                                            Akan jadi PO Baru (Terpisah)
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">* Kolom berwarna Merah adalah kunci pengelompokan (harus
                            sama persis agar tergabung).</p>


                        <!-- Import Form -->
                        <form action="{{ route('owner.purchases.import') }}" method="POST" enctype="multipart/form-data"
                            class="bg-gray-50 p-6 rounded-lg">
                            @csrf

                            <div class="mb-4">
                                <label for="file" class="block font-medium mb-2">File Excel</label>
                                <input type="file" name="file" id="file" accept=".xlsx,.xls"
                                    class="border rounded px-3 py-2 w-full focus:ring focus:ring-blue-300" required>
                                <p class="text-sm text-gray-600 mt-1">Format: .xlsx, .xls (Max: 2MB)</p>
                                @error('file')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Mode Import Selection -->
                            <div class="mb-6 bg-yellow-50 p-4 rounded border border-yellow-200">
                                <label class="block font-bold text-gray-800 mb-2">Mode Import</label>

                                <div class="flex flex-col space-y-3">
                                    <label class="flex items-start">
                                        <input type="radio" name="import_mode" value="migration"
                                            class="mt-1 form-radio text-purple-600" checked>
                                        <div class="ml-2">
                                            <span class="block font-semibold text-gray-800">1. Migrasi Data Lama
                                                (Keuangan
                                                Saja)</span>
                                            <span class="block text-sm text-gray-600">
                                                Hanya mencatat riwayat pembelian & pengeluaran uang. <br>
                                                <span class="text-red-600 font-bold">TIDAK MENAMBAH STOK PRODUK</span>.
                                                Gunakan ini untuk input data masa lalu.
                                            </span>
                                        </div>
                                    </label>

                                    <label class="flex items-start">
                                        <input type="radio" name="import_mode" value="full"
                                            class="mt-1 form-radio text-purple-600">
                                        <div class="ml-2">
                                            <span class="block font-semibold text-gray-800">2. Import Pembelian Baru
                                                (Integrasi Penuh)</span>
                                            <span class="block text-sm text-gray-600">
                                                Mencatat pembelian, <span class="text-green-600 font-bold">MENAMBAH STOK
                                                    PRODUK</span> secara otomatis, dan masuk laporan pergerakan stok.
                                                <br>Gunakan ini untuk data pembelian yang baru saja terjadi.
                                            </span>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <!-- Error Display -->
                            @if($errors->has('import_errors'))
                                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                                    <h4 class="font-bold">Error Import:</h4>
                                    <ul class="list-disc list-inside text-sm">
                                        @foreach($errors->get('import_errors') as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            @error('error')
                                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4 text-sm">
                                    {{ $message }}
                                </div>
                            @enderror

                            <div class="flex space-x-3">
                                <button type="submit"
                                    class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 rounded shadow flex items-center">
                                    <i class="bi bi-upload mr-2"></i> Import Data
                                </button>
                                <a href="{{ route('owner.purchases.index') }}"
                                    class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded shadow flex items-center">
                                    <i class="bi bi-arrow-left mr-2"></i> Kembali
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
</body>

</html>