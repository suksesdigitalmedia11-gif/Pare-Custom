<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Import Sales Order - Pare Custom</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css" />
</head>
<body class="bg-gray-100">
    <div class="flex">
        <x-navbar-admin />
        <div class="flex-1 lg:w-5/6">
            <x-navbar-top-admin />
            <div class="p-4 lg:p-8">
                <div class="bg-white p-6 rounded-xl shadow-lg mb-6">
                    <h1 class="text-2xl font-semibold text-gray-800 mb-4">Import Sales Order</h1>
                    
                    <div class="grid md:grid-cols-2 gap-6 mb-6">
                        <!-- Info Panel -->
                        <div class="bg-blue-50 p-4 rounded-lg">
    <h3 class="font-semibold text-blue-800 mb-2">📋 Petunjuk Import TERBARU</h3>
    <ul class="text-sm text-blue-700 space-y-1">
        <li>• <strong>Split Payment:</strong> Isi CASH_AMOUNT_TOTAL dan TRANSFER_AMOUNT_TOTAL</li>
        <li>• <strong>Cash Only:</strong> Isi CASH_AMOUNT_TOTAL saja</li>
        <li>• <strong>Transfer Only:</strong> Isi TRANSFER_AMOUNT_TOTAL saja</li>
        <li>• <strong>Historical Data:</strong> Auto set status selesai & lunas</li>
        <li>• Format date: YYYY-MM-DD (2024-01-15)</li>
    </ul>
</div>

                        <!-- Template Download -->
                        <div class="bg-green-50 p-4 rounded-lg">
                            <h3 class="font-semibold text-green-800 mb-2">📥 Template</h3>
                            <p class="text-sm text-green-700 mb-3">Download template untuk memastikan format sesuai</p>
                            <a href="{{ route('admin.sales.download-template') }}" 
                               class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded shadow inline-flex items-center">
                                <i class="bi bi-download mr-2"></i> Download Template
                            </a>
                        </div>
                    </div>

                    <!-- Import Form -->
                    <form action="{{ route('admin.sales.import') }}" method="POST" enctype="multipart/form-data" class="bg-gray-50 p-6 rounded-lg">
                        @csrf
                        
                        <div class="mb-4">
                            <label class="block font-medium mb-2">Tipe Import</label>
                            <div class="flex space-x-4">
                                <label class="flex items-center">
                                    <input type="radio" name="import_type" value="current" checked class="mr-2">
                                    <span>Data Baru (Perlu validasi shift)</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="radio" name="import_type" value="historical" class="mr-2">
                                    <span>Data Historical (Skip validasi)</span>
                                </label>
                            </div>
                            <p class="text-sm text-gray-600 mt-1">
                                <strong>Data Historical:</strong> Untuk import data lama, skip validasi shift & auto set status selesai
                            </p>
                        </div>

                        <div class="mb-4">
                            <label for="file" class="block font-medium mb-2">File Excel</label>
                            <input type="file" name="file" id="file" 
                                   accept=".xlsx,.xls" 
                                   class="border rounded px-3 py-2 w-full focus:ring focus:ring-blue-300"
                                   required>
                            <p class="text-sm text-gray-600 mt-1">Format: .xlsx, .xls (Max: 2MB)</p>
                            @error('file')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
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

                        <div class="flex space-x-3">
                            <button type="submit" 
                                    class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 rounded shadow flex items-center">
                                <i class="bi bi-upload mr-2"></i> Import Data
                            </button>
                            <a href="{{ route('admin.sales.index') }}" 
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