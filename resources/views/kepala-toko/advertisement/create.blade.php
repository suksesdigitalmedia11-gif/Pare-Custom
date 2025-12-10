<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Data Iklan - Kepala Toko</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
</head>
<body class="bg-gray-100">
    <div class="flex">
        <x-navbar-kepala-toko></x-navbar-kepala-toko>
        
        <div class="flex-1 lg:w-5/6">
            <x-navbar-top-kepala-toko></x-navbar-top-kepala-toko>

            <div class="p-4 lg:p-8">
                <div class="max-w-2xl mx-auto">
                    <!-- Header -->
                    <div class="bg-white p-6 rounded-xl shadow-lg mb-6">
                        <div class="flex items-center gap-3 mb-4">
                            <a href="{{ route('kepala-toko.advertisement.index') }}" 
                               class="text-blue-600 hover:text-blue-800">
                                <i class="bi bi-arrow-left"></i>
                            </a>
                            <div>
                                <h1 class="text-2xl font-bold text-gray-800">Input Data Iklan</h1>
                                <p class="text-gray-600">Tanggal: {{ now()->format('d/m/Y') }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Form -->
                    <div class="bg-white p-6 rounded-xl shadow-lg">
                        <form action="{{ route('kepala-toko.advertisement.store') }}" method="POST">
                            @csrf
                            
                            <!-- Type Selection -->
                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 mb-3">Jenis Inputan</label>
                                <div class="grid grid-cols-3 gap-4">
                                    @foreach([
                                        ['value' => 'chat', 'label' => 'Chat Masuk', 'color' => 'blue', 'icon' => 'chat-left-text'],
                                        ['value' => 'followup', 'label' => 'Follow Up', 'color' => 'orange', 'icon' => 'telephone'],
                                        ['value' => 'closing', 'label' => 'Closing', 'color' => 'green', 'icon' => 'currency-dollar']
                                    ] as $type)
                                        <label class="relative">
                                            <input type="radio" name="type" value="{{ $type['value'] }}" 
                                                   class="hidden peer" 
                                                   onchange="updateDescriptions('{{ $type['value'] }}')">
                                            <div class="p-4 border-2 border-gray-200 rounded-lg text-center cursor-pointer
                                                       peer-checked:border-{{ $type['color'] }}-500 peer-checked:bg-{{ $type['color'] }}-50
                                                       hover:border-{{ $type['color'] }}-300 transition-colors">
                                                <i class="bi bi-{{ $type['icon'] }} text-{{ $type['color'] }}-600 text-xl mb-2"></i>
                                                <div class="font-medium text-gray-800">{{ $type['label'] }}</div>
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                                @error('type')
                                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Description -->
                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Keterangan</label>
                                <select name="description" id="description" 
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:border-blue-500 focus:ring-blue-500">
                                    <option value="">Pilih Keterangan</option>
                                </select>
                                @error('description')
                                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Amount (hanya untuk closing) -->
                            <div class="mb-6 hidden" id="amount-field">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Nominal Closing</label>
                                <input type="number" name="amount" id="amount" 
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:border-blue-500 focus:ring-blue-500"
                                       placeholder="Masukkan nominal transaksi">
                                @error('amount')
                                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Submit Button -->
                            <div class="flex gap-3">
                                <a href="{{ route('kepala-toko.advertisement.index') }}" 
                                   class="flex-1 bg-gray-500 text-white py-3 rounded-lg text-center hover:bg-gray-600">
                                    Batal
                                </a>
                                <button type="submit" 
                                        class="flex-1 bg-blue-600 text-white py-3 rounded-lg hover:bg-blue-700 font-medium">
                                    Simpan Data
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function updateDescriptions(type) {
            // Show/hide amount field
            const amountField = document.getElementById('amount-field');
            if (type === 'closing') {
                amountField.classList.remove('hidden');
                document.getElementById('amount').required = true;
            } else {
                amountField.classList.add('hidden');
                document.getElementById('amount').required = false;
            }

            // Fetch descriptions via AJAX
            fetch(`{{ route('kepala-toko.advertisement.descriptions') }}?type=${type}`)
                .then(response => response.json())
                .then(descriptions => {
                    const select = document.getElementById('description');
                    select.innerHTML = '<option value="">Pilih Keterangan</option>';
                    
                    descriptions.forEach(desc => {
                        const option = document.createElement('option');
                        option.value = desc;
                        option.textContent = desc;
                        select.appendChild(option);
                    });
                });
        }

        // Initialize form
        document.addEventListener('DOMContentLoaded', function() {
            const firstType = document.querySelector('input[name="type"]');
            if (firstType) {
                firstType.checked = true;
                updateDescriptions(firstType.value);
            }
        });
    </script>
</body>
</html>

