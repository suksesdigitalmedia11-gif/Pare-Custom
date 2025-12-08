<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Pelanggan & Pemasok - Pare Custom</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Bootstrap Icons CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css" />
    <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;600&display=swap" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        body { font-family: 'Raleway', sans-serif; }
        .nav-text { position: relative; display: inline-block; }
        .nav-text::after {
            content: ''; position: absolute; width: 0; height: 2px; bottom: -2px; left: 0;
            background-color: #e17f12; transition: width 0.2s ease-in-out;
        }
        .hover-link:hover .nav-text::after { width: 100%; }
        .pagination-button { @apply px-3 py-1 border rounded-lg transition-all duration-200; }
        .pagination-button.active { @apply bg-orange-500 text-white border-orange-500; }
        .pagination-button:not(.active) { @apply hover:bg-orange-50 text-gray-700; }
        tbody tr { animation: fadeIn 0.3s ease-in-out; }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .tab-button { @apply px-6 py-3 font-semibold transition-all duration-200 border-b-2 border-transparent; }
        .tab-button.active { @apply border-orange-500 text-orange-600; }
        .tab-button:not(.active) { @apply text-gray-500 hover:text-gray-700; }
    </style>
</head>
<body class="bg-gray-100">
    <div class="flex">

        <!-- Sidebar -->
        <x-navbar-owner></x-navbar-owner>

        <!-- Main Content -->
        <div class="flex-1 lg:w-5/6">
            <x-navbar-top-owner></x-navbar-top-owner>

            <div class="p-4 lg:p-8">
                <div class="p-6 bg-gray-100 min-h-screen">
                    <div class="max-w-7xl mx-auto">
                        <div class="flex justify-between items-center mb-6">
                            <h1 class="text-3xl font-bold text-gray-800">Pelanggan & Pemasok</h1>
                        </div>

                        @if(session('success'))
                            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-6" role="alert">
                                <span class="block sm:inline">{{ session('success') }}</span>
                            </div>
                        @endif
                        @if(session('error'))
                            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert">
                                <span class="block sm:inline">{{ session('error') }}</span>
                            </div>
                        @endif

                        <div class="bg-white rounded-lg shadow overflow-hidden" x-data="{ tab: 'customers' }">
                            <!-- Tabs -->
                            <div class="flex border-b space-x-6 py-2">
                                <button @click="tab='customers'" :class="tab==='customers' ? 'tab-button active' : 'tab-button'" class="flex items-center gap-2 px-6 py-3">
                                    <i class="bi bi-people-fill"></i> Pelanggan
                                </button>
                                <button @click="tab='suppliers'" :class="tab==='suppliers' ? 'tab-button active' : 'tab-button'" class="flex items-center gap-2 px-6 py-3">
                                    <i class="bi bi-truck"></i> Pemasok
                                </button>
                            </div>

                            <!-- Customers Tab -->
                            <div x-show="tab==='customers'" class="p-6">
                                <!-- Import Customers Form -->
                                <div class="bg-gray-50 p-6 rounded-lg mb-6">
                                    <h3 class="text-lg font-semibold text-gray-800 mb-4">
                                        <i class="bi bi-upload mr-2"></i>Impor Pelanggan
                                    </h3>
                                    <div class="flex items-center mb-4">
                                        <a href="{{ route('owner.contacts.customers.template') }}"
                                           class="bg-green-500 hover:bg-green-600 text-white px-4 py-2.5 rounded-lg font-medium transition-all duration-200">
                                            <i class="bi bi-download mr-2"></i>Unduh Template Pelanggan
                                        </a>
                                    </div>
                                    <form method="POST" action="{{ route('owner.contacts.customers.import') }}" enctype="multipart/form-data" class="space-y-4">
                                        @csrf
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-2">Unggah File Excel/CSV *</label>
                                                <input type="file" name="file" accept=".xlsx,.xls,.csv" required
                                                       class="w-full px-4 py-2.5 border rounded-lg focus:outline-none focus:border-orange-500 focus:ring-2 focus:ring-orange-200 transition-all duration-200">
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-2">&nbsp;</label>
                                                <button type="submit"
                                                        class="w-full bg-blue-500 hover:bg-blue-600 text-white px-4 py-2.5 rounded-lg font-medium transition-all duration-200">
                                                    <i class="bi bi-upload mr-2"></i>Impor Pelanggan
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>

                                <!-- Add Customer Form -->
                                <div class="bg-gray-50 p-6 rounded-lg mb-6">
                                    <h3 class="text-lg font-semibold text-gray-800 mb-4">
                                        <i class="bi bi-person-plus mr-2"></i>Tambah Pelanggan Baru
                                    </h3>
                                    <form method="POST" action="{{ route('owner.contacts.customers.store') }}" class="space-y-4">
                                        @csrf
                                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-2">Nama *</label>
                                                <input name="name" required
                                                       class="w-full px-4 py-2.5 border rounded-lg focus:outline-none focus:border-orange-500 focus:ring-2 focus:ring-orange-200 transition-all duration-200"
                                                       placeholder="Nama pelanggan">
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-2">Telepon</label>
                                                <input name="phone"
                                                       class="w-full px-4 py-2.5 border rounded-lg focus:outline-none focus:border-orange-500 focus:ring-2 focus:ring-orange-200 transition-all duration-200"
                                                       placeholder="Nomor telepon">
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                                                <input name="email" type="email"
                                                       class="w-full px-4 py-2.5 border rounded-lg focus:outline-none focus:border-orange-500 focus:ring-2 focus:ring-orange-200 transition-all duration-200"
                                                       placeholder="Alamat email">
                                            </div>
                                            <div class="md:col-span-2 lg:col-span-1">
                                                <label class="block text-sm font-medium text-gray-700 mb-2">&nbsp;</label>
                                                <button type="submit"
                                                        class="w-full bg-orange-500 hover:bg-orange-600 text-white px-4 py-2.5 rounded-lg font-medium transition-all duration-200">
                                                    <i class="bi bi-plus-circle mr-2"></i>Tambah Pelanggan
                                                </button>
                                            </div>
                                        </div>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div class="md:col-span-2">
                                                <label class="block text-sm font-medium text-gray-700 mb-2">Alamat</label>
                                                <input name="address"
                                                       class="w-full px-4 py-2.5 border rounded-lg focus:outline-none focus:border-orange-500 focus:ring-2 focus:ring-orange-200 transition-all duration-200"
                                                       placeholder="Alamat lengkap">
                                            </div>
                                            <div class="md:col-span-2">
                                                <label class="block text-sm font-medium text-gray-700 mb-2">Catatan</label>
                                                <input name="notes"
                                                       class="w-full px-4 py-2.5 border rounded-lg focus:outline-none focus:border-orange-500 focus:ring-2 focus:ring-orange-200 transition-all duration-200"
                                                       placeholder="Catatan tambahan">
                                            </div>
                                        </div>
                                    </form>
                                </div>

                                <!-- Customers Table -->
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Telepon</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Alamat</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Catatan</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            @foreach($customers as $customer)
                                                <tr class="hover:bg-gray-50 transition-colors duration-200">
                                                    <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900">{{ $customer->name }}</td>
                                                    <td class="px-6 py-4 whitespace-nowrap">{{ $customer->phone ?? '-' }}</td>
                                                    <td class="px-6 py-4 whitespace-nowrap">{{ $customer->email ?? '-' }}</td>
                                                    <td class="px-6 py-4 whitespace-nowrap">{{ $customer->address ?? '-' }}</td>
                                                    <td class="px-6 py-4 whitespace-nowrap">{{ $customer->notes ?? '-' }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                                <div class="mt-6 px-6 py-4 border-t border-gray-200">
                                    <div class="flex justify-between items-center">
                                        <div class="text-sm text-gray-700">
                                            Menampilkan {{ $customers->firstItem() }} sampai {{ $customers->lastItem() }} dari {{ $customers->total() }} pelanggan
                                        </div>
                                        <div>
                                            {{ $customers->withQueryString()->onEachSide(1)->links() }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Suppliers Tab -->
                            <div x-show="tab='suppliers'" class="p-6">
                                <!-- Import Suppliers Form -->
                                <div class="bg-gray-50 p-6 rounded-lg mb-6">
                                    <h3 class="text-lg font-semibold text-gray-800 mb-4">
                                        <i class="bi bi-upload mr-2"></i>Impor Pemasok
                                    </h3>
                                    <div class="flex items-center mb-4">
                                        <a href="{{ route('owner.contacts.suppliers.template') }}"
                                           class="bg-green-500 hover:bg-green-600 text-white px-4 py-2.5 rounded-lg font-medium transition-all duration-200">
                                            <i class="bi bi-download mr-2"></i>Unduh Template Pemasok
                                        </a>
                                    </div>
                                    <form method="POST" action="{{ route('owner.contacts.suppliers.import') }}" enctype="multipart/form-data" class="space-y-4">
                                        @csrf
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-2">Unggah File Excel/CSV *</label>
                                                <input type="file" name="file" accept=".xlsx,.xls,.csv" required
                                                       class="w-full px-4 py-2.5 border rounded-lg focus:outline-none focus:border-orange-500 focus:ring-2 focus:ring-orange-200 transition-all duration-200">
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-2">&nbsp;</label>
                                                <button type="submit"
                                                        class="w-full bg-blue-500 hover:bg-blue-600 text-white px-4 py-2.5 rounded-lg font-medium transition-all duration-200">
                                                    <i class="bi bi-upload mr-2"></i>Impor Pemasok
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>

                                <!-- Add Supplier Form -->
                                <div class="bg-gray-50 p-6 rounded-lg mb-6">
                                    <h3 class="text-lg font-semibold text-gray-800 mb-4">
                                        <i class="bi bi-building-add mr-2"></i>Tambah Pemasok Baru
                                    </h3>
                                    <form method="POST" action="{{ route('owner.contacts.suppliers.store') }}" class="space-y-4">
                                        @csrf
                                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-2">Nama Pemasok *</label>
                                                <input name="name" required
                                                       class="w-full px-4 py-2.5 border rounded-lg focus:outline-none focus:border-orange-500 focus:ring-2 focus:ring-orange-200 transition-all duration-200"
                                                       placeholder="Nama pemasok">
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-2">Nama Kontak</label>
                                                <input name="contact_name"
                                                       class="w-full px-4 py-2.5 border rounded-lg focus:outline-none focus:border-orange-500 focus:ring-2 focus:ring-orange-200 transition-all duration-200"
                                                       placeholder="Nama kontak person">
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-2">Telepon</label>
                                                <input name="phone"
                                                       class="w-full px-4 py-2.5 border rounded-lg focus:outline-none focus:border-orange-500 focus:ring-2 focus:ring-orange-200 transition-all duration-200"
                                                       placeholder="Nomor telepon">
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                                                <input name="email" type="email"
                                                       class="w-full px-4 py-2.5 border rounded-lg focus:outline-none focus:border-orange-500 focus:ring-2 focus:ring-orange-200 transition-all duration-200"
                                                       placeholder="Alamat email">
                                            </div>
                                            <div class="md:col-span-2">
                                                <label class="block text-sm font-medium text-gray-700 mb-2">Alamat</label>
                                                <input name="address"
                                                       class="w-full px-4 py-2.5 border rounded-lg focus:outline-none focus:border-orange-500 focus:ring-2 focus:ring-orange-200 transition-all duration-200"
                                                       placeholder="Alamat lengkap">
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-2">&nbsp;</label>
                                                <button type="submit"
                                                        class="w-full bg-orange-500 hover:bg-orange-600 text-white px-4 py-2.5 rounded-lg font-medium transition-all duration-200">
                                                    <i class="bi bi-plus-circle mr-2"></i>Tambah Pemasok
                                                </button>
                                            </div>
                                        </div>
                                    </form17>
                                </div>

                                <!-- Suppliers Table -->
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Pemasok</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kontak</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Telepon</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Alamat</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            @foreach($suppliers as $supplier)
                                                <tr class="hover:bg-gray-50 transition-colors duration-200">
                                                    <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900">{{ $supplier->name }}</td>
                                                    <td class="px-6 py-4 whitespace-nowrap">{{ $supplier->contact_name ?? '-' }}</td>
                                                    <td class="px-6 py-4 whitespace-nowrap">{{ $supplier->phone ?? '-' }}</td>
                                                    <td class="px-6 py-4 whitespace-nowrap">{{ $supplier->email ?? '-' }}</td>
                                                    <td class="px-6 py-4 whitespace-nowrap">{{ $supplier->address ?? '-' }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                                <div class="mt-6 px-6 py-4 border-t border-gray-200">
                                    <div class="flex justify-between items-center">
                                        <div class="text-sm text-gray-700">
                                            Menampilkan {{ $suppliers->firstItem() }} sampai {{ $suppliers->lastItem() }} dari {{ $suppliers->total() }} pemasok
                                        </div>
                                        <div>
                                            {{ $suppliers->withQueryString()->onEachSide(1)->links() }}
                                        </div>
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
        function toggleSidebar() {
            const sidebar = document.querySelector('#sidebar');
            sidebar.classList.toggle('-translate-x-full');
        }

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