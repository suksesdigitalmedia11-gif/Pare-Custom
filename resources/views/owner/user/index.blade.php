<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Pengguna - Pare Custom</title>
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
        .search-input:focus {
            box-shadow: 0 0 0 2px rgba(225, 127, 18, 0.2);
        }
        .table-loading {
            position: relative;
        }
        .table-loading::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.8);
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 1.2em;
            color: #666;
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
                    <div class="max-w-7xl mx-auto">
                        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                            <!-- Header Section -->
                            <div class="p-6 border-b border-gray-200">
                                <div class="flex flex-col md:flex-row justify-between items-center space-y-4 md:space-y-0">
                                    <h1 class="text-3xl font-bold text-gray-800">Daftar Pengguna</h1>
                                    <div class="flex items-center space-x-4">
                                        <!-- Search Bar -->
                                        <div class="relative">
                                            <input type="text" 
                                                id="searchInput"
                                                class="search-input w-full md:w-64 pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none"
                                                placeholder="Cari pengguna..."
                                                onkeyup="filterTable()">
                                            <span class="absolute left-3 top-2.5 text-gray-400">
                                                <i class="bi bi-search"></i>
                                            </span>
                                        </div>
                                        <a href="{{ route('owner.user.create') }}" 
                                           class="inline-flex items-center px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg transition duration-200">
                                            <i class="bi bi-plus-lg mr-2"></i>
                                            Tambah Pengguna
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Alert Messages -->
                            @if(session('success'))
                                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 m-6" role="alert">
                                    <p class="font-medium flex items-center"><i class="bi bi-check-circle-fill mr-2"></i>Sukses!</p>
                                    <p class="mt-1">{{ session('success') }}</p>
                                </div>
                            @endif

                            @if(session('error'))
                                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 m-6" role="alert">
                                    <p class="font-medium flex items-center"><i class="bi bi-exclamation-triangle-fill mr-2"></i>Perhatian Proteksi Sistem!</p>
                                    <p class="mt-1">{{ session('error') }}</p>
                                </div>
                            @endif

                            <!-- Table Container -->
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200" id="userTable">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th onclick="sortTable(0)" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition duration-150">
                                                <div class="flex items-center space-x-1">
                                                    <span>ID</span>
                                                    <i class="bi bi-chevron-expand"></i>
                                                </div>
                                            </th>
                                            <th onclick="sortTable(1)" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition duration-150">
                                                <div class="flex items-center space-x-1">
                                                    <span>Nama</span>
                                                    <i class="bi bi-chevron-expand"></i>
                                                </div>
                                            </th>
                                            <th onclick="sortTable(2)" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition duration-150">
                                                <div class="flex items-center space-x-1">
                                                    <span>Email</span>
                                                    <i class="bi bi-chevron-expand"></i>
                                                </div>
                                            </th>
                                            <th onclick="sortTable(3)" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition duration-150">
                                                <div class="flex items-center space-x-1">
                                                    <span>Tipe Pengguna</span>
                                                    <i class="bi bi-chevron-expand"></i>
                                                </div>
                                            </th>
                                            <th onclick="sortTable(4)" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition duration-150">
                                                <div class="flex items-center space-x-1">
                                                    <span>Status</span>
                                                    <i class="bi bi-chevron-expand"></i>
                                                </div>
                                            </th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200" id="tableBody">
                                        @foreach($users as $user)
                                            <tr class="hover:bg-gray-50 transition duration-150 table-row {{ !($user->is_active ?? true) ? 'bg-gray-50/60 opacity-80' : '' }}">
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-700">#{{ $user->id }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="font-medium text-gray-900">{{ $user->name }}</div>
                                                    @if($user->has_history)
                                                        <div class="text-[11px] text-gray-400">
                                                            {{ $user->historical_counts['shifts'] > 0 ? $user->historical_counts['shifts'] . ' shift' : '' }}
                                                            {{ $user->historical_counts['sales_orders'] > 0 ? ' • ' . $user->historical_counts['sales_orders'] . ' penjualan' : '' }}
                                                        </div>
                                                    @endif
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $user->email }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <span class="px-2.5 py-1 text-xs font-medium rounded-full
                                                        {{ $user->usertype === 'admin' ? 'bg-purple-100 text-purple-800 border border-purple-200' : 
                                                           ($user->usertype === 'owner' ? 'bg-blue-100 text-blue-800 border border-blue-200' : 
                                                           ($user->usertype === 'finance' ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' :
                                                           ($user->usertype === 'kepala_toko' ? 'bg-amber-100 text-amber-800 border border-amber-200' : 'bg-gray-100 text-gray-800 border border-gray-200'))) }}">
                                                        {{ strtoupper(str_replace('_', ' ', $user->usertype)) }}
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    @if($user->is_active ?? true)
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800 border border-green-200">
                                                            <span class="w-1.5 h-1.5 mr-1.5 bg-green-500 rounded-full"></span>
                                                            Aktif
                                                        </span>
                                                    @else
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-800 border border-red-200">
                                                            <span class="w-1.5 h-1.5 mr-1.5 bg-red-500 rounded-full"></span>
                                                            Nonaktif
                                                        </span>
                                                    @endif
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="flex items-center space-x-1.5">
                                                        <a href="{{ route('owner.user.show', $user) }}" 
                                                           class="inline-flex items-center px-2.5 py-1.5 bg-blue-500 hover:bg-blue-600 text-white text-xs font-medium rounded-md shadow-sm transition duration-150"
                                                           title="Lihat Detail">
                                                            <i class="bi bi-eye mr-1"></i>
                                                            Lihat
                                                        </a>
                                                        <a href="{{ route('owner.user.edit', $user) }}" 
                                                           class="inline-flex items-center px-2.5 py-1.5 bg-amber-500 hover:bg-amber-600 text-white text-xs font-medium rounded-md shadow-sm transition duration-150"
                                                           title="Edit Data">
                                                            <i class="bi bi-pencil mr-1"></i>
                                                            Edit
                                                        </a>

                                                        @if($user->id !== auth()->id())
                                                            <!-- Toggle Status Button -->
                                                            <form action="{{ route('owner.user.toggle-status', $user) }}" method="POST" class="inline-block"
                                                                  onsubmit="return confirm('{{ ($user->is_active ?? true) ? 'Apakah Anda yakin ingin MENONAKTIFKAN akun ' . $user->name . '? Karyawan ini tidak akan bisa login lagi ke sistem.' : 'Aktifkan kembali akun ' . $user->name . '?' }}')">
                                                                @csrf
                                                                @method('PATCH')
                                                                @if($user->is_active ?? true)
                                                                    <button type="submit" 
                                                                            class="inline-flex items-center px-2.5 py-1.5 bg-orange-500 hover:bg-orange-600 text-white text-xs font-medium rounded-md shadow-sm transition duration-150"
                                                                            title="Nonaktifkan Akses Karyawan Keluar">
                                                                        <i class="bi bi-slash-circle mr-1"></i>
                                                                        Nonaktifkan
                                                                    </button>
                                                                @else
                                                                    <button type="submit" 
                                                                            class="inline-flex items-center px-2.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-medium rounded-md shadow-sm transition duration-150"
                                                                            title="Aktifkan Kembali Akses">
                                                                        <i class="bi bi-check-circle mr-1"></i>
                                                                        Aktifkan
                                                                    </button>
                                                                @endif
                                                            </form>

                                                            <!-- Delete Button with Safety Lock -->
                                                            @if($user->has_history)
                                                                <button type="button" 
                                                                        class="inline-flex items-center px-2.5 py-1.5 bg-gray-200 hover:bg-gray-300 text-gray-500 text-xs font-medium rounded-md transition duration-150 cursor-pointer"
                                                                        title="Data Terkunci: Pengguna ini memiliki riwayat transaksi/shift kerja. Klik untuk info."
                                                                        onclick="alert('Pengguna \'{{ $user->name }}\' memiliki riwayat transaksi/shift kerja yang dilindungi sistem agar pembukuan tidak rusak. Untuk mencabut akses karyawan ini, silakan gunakan tombol \'Nonaktifkan\'!')">
                                                                    <i class="bi bi-lock-fill mr-1 text-gray-500"></i>
                                                                    Terkunci
                                                                </button>
                                                            @else
                                                                <form action="{{ route('owner.user.destroy', $user) }}" 
                                                                      method="POST" 
                                                                      class="inline-block"
                                                                      onsubmit="return confirm('Pengguna ini belum memiliki riwayat kerja apa pun. Apakah Anda yakin ingin menghapusnya secara permanen?')">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <button type="submit" 
                                                                            class="inline-flex items-center px-2.5 py-1.5 bg-red-500 hover:bg-red-600 text-white text-xs font-medium rounded-md shadow-sm transition duration-150"
                                                                            title="Hapus Akun Kosong">
                                                                        <i class="bi bi-trash mr-1"></i>
                                                                        Hapus
                                                                    </button>
                                                                </form>
                                                            @endif
                                                        @else
                                                            <span class="text-xs text-gray-400 italic px-1">Akun Utama</span>
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>

                                <!-- Empty State -->
                                <div id="emptyState" class="hidden p-8 text-center">
                                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 mb-4">
                                        <i class="bi bi-search text-2xl text-gray-400"></i>
                                    </div>
                                    <h3 class="text-lg font-medium text-gray-900 mb-2">Tidak ada data ditemukan</h3>
                                    <p class="text-gray-500">Coba ubah kata kunci pencarian Anda</p>
                                </div>
                            </div>

                            <!-- Pagination -->
                            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
                                <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
                                    <div class="flex items-center space-x-2">
                                        <label for="itemsPerPage" class="text-sm text-gray-600">Tampilkan:</label>
                                        <select id="itemsPerPage" class="border rounded px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            <option value="5">5</option>
                                            <option value="10" selected>10</option>
                                            <option value="25">25</option>
                                            <option value="50">50</option>
                                        </select>
                                        <span class="text-sm text-gray-600">entri</span>
                                    </div>
                                    <div class="flex items-center">
                                        <span class="text-sm text-gray-600 mr-4" id="pageInfo"></span>
                                        <div class="flex space-x-1" id="pagination"></div>
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
        // Variabel untuk pagination dan sorting
        let currentPage = 1;
        let rowsPerPage = 10;
        let rows = [];
        let sortColumn = 0;
        let sortDirection = 'asc';
        
        // Inisialisasi saat dokumen dimuat
        document.addEventListener('DOMContentLoaded', function() {
            // Ambil semua baris tabel
            rows = Array.from(document.querySelectorAll('#tableBody tr'));
            
            // Setup event listeners
            document.getElementById('itemsPerPage').addEventListener('change', function(e) {
                rowsPerPage = parseInt(e.target.value);
                currentPage = 1;
                displayTableRows();
            });

            // Tampilkan baris pertama kali
            displayTableRows();
        });

        // Filter tabel
        function filterTable() {
            const input = document.getElementById('searchInput');
            const filter = input.value.toLowerCase();
            const emptyState = document.getElementById('emptyState');
            let hasVisibleRows = false;
            
            // Reset pagination ke halaman pertama saat mencari
            currentPage = 1;
            
            // Filter baris
            rows.forEach(row => {
                const text = Array.from(row.getElementsByTagName('td'))
                    .slice(0, -1) // Abaikan kolom terakhir (aksi)
                    .map(td => td.textContent || td.innerText)
                    .join(' ')
                    .toLowerCase();
                
                const isVisible = text.includes(filter);
                row.classList.toggle('table-row', isVisible);
                row.classList.toggle('hidden', !isVisible);
                
                if (isVisible) hasVisibleRows = true;
            });

            // Toggle empty state
            emptyState.classList.toggle('hidden', hasVisibleRows);
            document.getElementById('userTable').classList.toggle('hidden', !hasVisibleRows);

            // Update tampilan
            displayTableRows();
        }

        // Fungsi untuk menampilkan baris dan pagination
        function displayTableRows() {
            const filteredRows = rows.filter(row => !row.classList.contains('hidden'));
            const totalRows = filteredRows.length;
            const totalPages = Math.ceil(totalRows / rowsPerPage);
            
            // Pastikan halaman saat ini valid
            if (currentPage > totalPages) {
                currentPage = totalPages || 1;
            }

            // Sembunyikan semua baris terlebih dahulu
            rows.forEach(row => row.style.display = 'none');

            // Tampilkan baris untuk halaman saat ini
            const start = (currentPage - 1) * rowsPerPage;
            const end = start + rowsPerPage;
            filteredRows.slice(start, end).forEach(row => row.style.display = '');

            // Update informasi halaman
            const pageInfo = document.getElementById('pageInfo');
            if (totalRows > 0) {
                pageInfo.textContent = `Menampilkan ${start + 1} sampai ${Math.min(end, totalRows)} dari ${totalRows} entri`;
            } else {
                pageInfo.textContent = 'Tidak ada data yang ditampilkan';
            }

            // Render pagination
            renderPagination(totalPages);
        }

        // Fungsi untuk merender tombol pagination
        function renderPagination(totalPages) {
            const pagination = document.getElementById('pagination');
            pagination.innerHTML = '';

            if (totalPages <= 1) return;

            // Tombol Previous
            const prevButton = createPaginationButton('«', () => {
                if (currentPage > 1) {
                    currentPage--;
                    displayTableRows();
                }
            }, currentPage === 1);
            pagination.appendChild(prevButton);

            // Tombol halaman
            for (let i = 1; i <= totalPages; i++) {
                if (
                    i === 1 || // Selalu tampilkan halaman pertama
                    i === totalPages || // Selalu tampilkan halaman terakhir
                    (i >= currentPage - 1 && i <= currentPage + 1) // Tampilkan halaman sekitar halaman saat ini
                ) {
                    const pageButton = createPaginationButton(i.toString(), () => {
                        currentPage = i;
                        displayTableRows();
                    }, currentPage === i);
                    pagination.appendChild(pageButton);
                } else if (
                    i === currentPage - 2 ||
                    i === currentPage + 2
                ) {
                    // Tambahkan ellipsis
                    const ellipsis = document.createElement('span');
                    ellipsis.textContent = '...';
                    ellipsis.className = 'px-3 py-1 text-gray-500';
                    pagination.appendChild(ellipsis);
                }
            }

            // Tombol Next
            const nextButton = createPaginationButton('»', () => {
                if (currentPage < totalPages) {
                    currentPage++;
                    displayTableRows();
                }
            }, currentPage === totalPages);
            pagination.appendChild(nextButton);
        }

        // Fungsi untuk membuat tombol pagination
        function createPaginationButton(text, onClick, isActive = false, isDisabled = false) {
            const button = document.createElement('button');
            button.textContent = text;
            button.className = `px-3 py-1 rounded-md transition duration-150 ${
                isDisabled 
                    ? 'bg-gray-100 text-gray-400 cursor-not-allowed' 
                    : isActive
                        ? 'bg-blue-500 text-white'
                        : 'bg-white hover:bg-gray-50'
            }`;
            if (!isDisabled) {
                button.addEventListener('click', onClick);
            }
            button.disabled = isDisabled;
            return button;
        }

        // Fungsi sort
        function sortTable(n) {
            sortColumn = n;
            sortDirection = sortColumn === n ? (sortDirection === 'asc' ? 'desc' : 'asc') : 'asc';

            rows.sort((a, b) => {
                const aValue = a.getElementsByTagName('td')[n].textContent.trim();
                const bValue = b.getElementsByTagName('td')[n].textContent.trim();

                if (n === 0) { // Untuk kolom ID, sort sebagai angka
                    return sortDirection === 'asc' 
                        ? parseInt(aValue) - parseInt(bValue)
                        : parseInt(bValue) - parseInt(aValue);
                } else {
                    return sortDirection === 'asc'
                        ? aValue.localeCompare(bValue)
                        : bValue.localeCompare(aValue);
                }
            });

            // Reattach sorted rows
            const tbody = document.getElementById('tableBody');
            rows.forEach(row => tbody.appendChild(row));

            // Update tampilan dengan urutan baru
            displayTableRows();

            // Update tampilan icon sort
            updateSortIcons(n);
        }

        // Update ikon sort
        function updateSortIcons(column) {
            const headers = document.querySelectorAll('th');
            headers.forEach((header, index) => {
                const icon = header.querySelector('i');
                if (icon) {
                    if (index === column) {
                        icon.className = `bi ${sortDirection === 'asc' ? 'bi-chevron-up' : 'bi-chevron-down'}`;
                    } else {
                        icon.className = 'bi bi-chevron-expand';
                    }
                }
            });
        }
    </script>
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