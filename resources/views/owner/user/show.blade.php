<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pengguna - Pare Custom</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Raleway', sans-serif;
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
    </style>
</head>
<body class="bg-gray-100">
    <div class="flex">
        <x-navbar-owner></x-navbar-owner>
        <div class="flex-1 lg:w-5/6">
            <x-navbar-top-owner></x-navbar-top-owner>
            <div class="p-4 lg:p-8">
                <div class="p-6 bg-gray-100 min-h-screen">
                    <div class="max-w-2xl mx-auto">
                        <div class="bg-white rounded-lg shadow-lg p-6">
                            <div class="mb-6">
                                <h1 class="text-2xl font-semibold text-gray-900">Detail Pengguna</h1>
                            </div>
                            <div class="grid grid-cols-2 gap-4 mb-6">
                                <div>
                                    <h3 class="text-sm font-medium text-gray-500">ID</h3>
                                    <p class="mt-1 text-lg font-bold text-gray-900">#{{ $user->id }}</p>
                                </div>
                                <div>
                                    <h3 class="text-sm font-medium text-gray-500">Nama Lengkap</h3>
                                    <p class="mt-1 text-lg font-semibold text-gray-900">{{ $user->name }}</p>
                                </div>
                                <div>
                                    <h3 class="text-sm font-medium text-gray-500">Email</h3>
                                    <p class="mt-1 text-base text-gray-900">{{ $user->email }}</p>
                                </div>
                                <div>
                                    <h3 class="text-sm font-medium text-gray-500">Tipe Pengguna</h3>
                                    <p class="mt-1 text-base font-medium text-indigo-600 uppercase">{{ str_replace('_', ' ', $user->usertype) }}</p>
                                </div>
                                <div>
                                    <h3 class="text-sm font-medium text-gray-500">Status Akun</h3>
                                    <div class="mt-1">
                                        @if($user->is_active ?? true)
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800 border border-green-200">
                                                <span class="w-2 h-2 mr-1.5 bg-green-500 rounded-full"></span>
                                                Aktif (Memiliki Akses Sistem)
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800 border border-red-200">
                                                <span class="w-2 h-2 mr-1.5 bg-red-500 rounded-full"></span>
                                                Nonaktif (Akses Dicabut)
                                            </span>
                                        @endif
                                    </div>
                                </div>
                                <div>
                                    <h3 class="text-sm font-medium text-gray-500">Status Proteksi Data</h3>
                                    <div class="mt-1">
                                        @if($user->has_history)
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800 border border-blue-200">
                                                <i class="bi bi-shield-lock-fill mr-1.5 text-blue-600"></i>
                                                Terproteksi (Ada Riwayat Data)
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-700">
                                                <i class="bi bi-info-circle mr-1.5 text-gray-500"></i>
                                                Belum Ada Riwayat
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <!-- Historical Records Breakdown -->
                            <div class="bg-gray-50 rounded-xl p-4 mb-6 border border-gray-200">
                                <h4 class="text-xs font-bold uppercase tracking-wider text-gray-500 mb-3 flex items-center">
                                    <i class="bi bi-clock-history mr-1.5 text-indigo-500"></i>
                                    Ringkasan Riwayat Kerja & Operasional Pengguna
                                </h4>
                                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                                    <div class="bg-white p-3 rounded-lg border border-gray-200">
                                        <div class="text-xs text-gray-500">Shift Kasir</div>
                                        <div class="text-lg font-bold text-gray-800">{{ $user->historical_counts['shifts'] ?? 0 }}</div>
                                    </div>
                                    <div class="bg-white p-3 rounded-lg border border-gray-200">
                                        <div class="text-xs text-gray-500">Transaksi Penjualan</div>
                                        <div class="text-lg font-bold text-gray-800">{{ $user->historical_counts['sales_orders'] ?? 0 }}</div>
                                    </div>
                                    <div class="bg-white p-3 rounded-lg border border-gray-200">
                                        <div class="text-xs text-gray-500">Pembayaran</div>
                                        <div class="text-lg font-bold text-gray-800">{{ $user->historical_counts['payments'] ?? 0 }}</div>
                                    </div>
                                    <div class="bg-white p-3 rounded-lg border border-gray-200">
                                        <div class="text-xs text-gray-500">Purchase Order</div>
                                        <div class="text-lg font-bold text-gray-800">{{ $user->historical_counts['purchase_orders'] ?? 0 }}</div>
                                    </div>
                                    <div class="bg-white p-3 rounded-lg border border-gray-200">
                                        <div class="text-xs text-gray-500">Stock Opname</div>
                                        <div class="text-lg font-bold text-gray-800">{{ $user->historical_counts['stock_opnames'] ?? 0 }}</div>
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-wrap justify-end gap-2 pt-4 border-t border-gray-200">
                                <a href="{{ route('owner.user.index') }}" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 text-sm font-medium transition">
                                    <i class="bi bi-arrow-left mr-1"></i>
                                    Kembali
                                </a>

                                <a href="{{ route('owner.user.edit', $user) }}" class="bg-amber-500 text-white px-4 py-2 rounded-lg hover:bg-amber-600 text-sm font-medium transition">
                                    <i class="bi bi-pencil mr-1"></i>
                                    Edit
                                </a>

                                @if($user->id !== auth()->id())
                                    <form action="{{ route('owner.user.toggle-status', $user) }}" method="POST" class="inline"
                                          onsubmit="return confirm('{{ ($user->is_active ?? true) ? 'Nonaktifkan akun ' . $user->name . '?' : 'Aktifkan kembali akun ' . $user->name . '?' }}')">
                                        @csrf
                                        @method('PATCH')
                                        @if($user->is_active ?? true)
                                            <button type="submit" class="bg-orange-500 text-white px-4 py-2 rounded-lg hover:bg-orange-600 text-sm font-medium transition">
                                                <i class="bi bi-slash-circle mr-1"></i>
                                                Nonaktifkan Akun
                                            </button>
                                        @else
                                            <button type="submit" class="bg-emerald-600 text-white px-4 py-2 rounded-lg hover:bg-emerald-700 text-sm font-medium transition">
                                                <i class="bi bi-check-circle mr-1"></i>
                                                Aktifkan Akun
                                            </button>
                                        @endif
                                    </form>

                                    @if($user->has_history)
                                        <button type="button" 
                                                class="bg-gray-200 text-gray-400 px-4 py-2 rounded-lg text-sm font-medium cursor-not-allowed"
                                                onclick="alert('Pengguna ini memiliki riwayat operasional toko dan dilindungi dari penghapusan permanen agar data tidak rusak. Silakan gunakan opsi Nonaktifkan Akun!')">
                                            <i class="bi bi-lock-fill mr-1"></i>
                                            Hapus Terkunci
                                        </button>
                                    @else
                                        <form action="{{ route('owner.user.destroy', $user) }}" method="POST" class="inline"
                                              onsubmit="return confirm('Pengguna ini belum memiliki riwayat kerja. Yakin ingin menghapus permanen?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600 text-sm font-medium transition">
                                                <i class="bi bi-trash mr-1"></i>
                                                Hapus
                                            </button>
                                        </form>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
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