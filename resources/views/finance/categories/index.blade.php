<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Kategori - Pare Custom</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Bootstrap Icons CDN -->
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css"
    />
    {{-- Font Cdn --}}
    <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;600&display=swap" rel="stylesheet">
    <style>
      body{
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

      /* Tambahkan di dalam tag style yang sudah ada */
      .pagination-button {
          @apply px-3 py-1 border rounded-lg transition-all duration-200;
      }

      .pagination-button.active {
          @apply bg-orange-500 text-white border-orange-500;
      }

      .pagination-button:not(.active) {
          @apply hover:bg-orange-50 text-gray-700;
      }

      /* Animation untuk row tabel */
      tbody tr {
          animation: fadeIn 0.3s ease-in-out;
      }

      @keyframes fadeIn {
          from {
              opacity: 0;
              transform: translateY(-10px);
          }
          to {
              opacity: 1;
              transform: translateY(0);
          }
      }
    </style>
  </head>
  <body class="bg-gray-100">
    <div class="flex">

      <!-- Sidebar -->
      <x-navbar-finance></x-navbar-finance>

      <!-- Main Content -->
      <div class="flex-1 lg:w-5/6">
        {{-- Navbar Top --}}
        <x-navbar-top-finance></x-navbar-top-finance>

        <!-- Content Wrapper -->
        <div class="p-4 lg:p-8">
          <div class="p-6 bg-gray-100 min-h-screen">
              <div class="max-w-7xl mx-auto">
              <div class="flex justify-between items-center mb-6">
    <h1 class="text-3xl font-bold text-gray-800">Daftar Kategori</h1>
    <div class="flex space-x-2">
        <a href="{{ route('finance.category.import') }}" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg">
            <i class="bi bi-upload mr-1"></i>Import
        </a>
        <a href="{{ route('finance.category.create') }}" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg">
            <i class="bi bi-plus-circle mr-1"></i>Tambah Kategori
        </a>
    </div>
</div>
          
                  @if(session('success'))
                      <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                          <span class="block sm:inline">{{ session('success') }}</span>
                      </div>
                  @endif
          
                  <div class="bg-white rounded-lg shadow overflow-hidden">
                      <div class="p-6 border-b border-gray-100">
                        <div class="flex flex-col md:flex-row gap-4">
                            <!-- Search Box -->
                            <div class="flex-grow">
                                <label for="searchInput" class="text-sm font-medium text-gray-600 mb-2 block">
                                    <i class="bi bi-search mr-1"></i>Cari Kategori
                                </label>
                                <div class="relative">
                                    <input
                                        type="text"
                                        id="searchInput"
                                        class="w-full pl-10 pr-4 py-2.5 border rounded-lg focus:outline-none focus:border-orange-500 focus:ring-2 focus:ring-orange-200 transition-all duration-200"
                                        placeholder="Cari berdasarkan nama kategori..."
                                    >
                                    <div class="absolute left-3 top-3 text-gray-400">
                                        <i class="bi bi-search"></i>
                                    </div>
                                </div>
                            </div>
                    
                            <!-- Entries Per Page -->
                            <div class="md:w-48">
                                <label for="entriesPerPage" class="text-sm font-medium text-gray-600 mb-2 block">
                                    <i class="bi bi-table mr-1"></i>Tampilkan Entri
                                </label>
                                <select id="entriesPerPage" 
                                        class="w-full px-3 py-2.5 border rounded-lg focus:outline-none focus:border-orange-500 focus:ring-2 focus:ring-orange-200 transition-all duration-200 bg-white">
                                    <option value="5">5 entri</option>
                                    <option value="10" selected>10 entri</option>
                                    <option value="25">25 entri</option>
                                    <option value="50">50 entri</option>
                                </select>
                            </div>
                        </div>
                      </div>
                      <div class="overflow-x-auto">
                          <table class="min-w-full divide-y divide-gray-200">
                          <thead class="bg-gray-50">
                                  <tr>
                                       <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama</th>
                                       <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                       <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jumlah Produk</th>
                                       <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                                  </tr>
                              </thead>
                              <tbody class="bg-white divide-y divide-gray-200">
                                  @foreach($categories as $category)
                                      <tr>
                                          <td class="px-6 py-4 whitespace-nowrap font-semibold">{{ $category->name }}</td>
                                          <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 py-1 text-xs rounded-full {{ $category->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                {{ $category->is_active ? 'Aktif' : 'Nonaktif' }}
                                            </span>
                                          </td>
                                          <td class="px-6 py-4 whitespace-nowrap">{{ $category->products_count ?? 0 }} produk</td>
                                          <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex space-x-2">
                                                {{-- Button Edit --}}
                                                <a href="{{ route('finance.category.edit', $category) }}" 
                                                   class="inline-flex items-center px-3 py-1.5 bg-yellow-500 text-white text-sm font-medium rounded-md hover:bg-yellow-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500">
                                                    <i class="bi bi-pencil mr-1"></i>
                                                    Edit
                                                </a>
                                        
                                                {{-- Button Hapus --}}
                                                <form action="{{ route('finance.category.destroy', $category) }}" method="POST" class="inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" 
                                                            onclick="return confirm('Apakah Anda yakin ingin menghapus kategori ini?')"
                                                            class="inline-flex items-center px-3 py-1.5 bg-red-500 text-white text-sm font-medium rounded-md hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                                        <i class="bi bi-trash mr-1"></i>
                                                        Hapus
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                      </tr>
                                  @endforeach
                              </tbody>
                          </table>

                          <div class="px-6 py-4 border-t border-gray-200">
                            <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                                <div class="text-sm text-gray-700">
                                    Menampilkan 
                                    <span class="font-semibold text-orange-500" id="startEntry">1</span> 
                                    sampai 
                                    <span class="font-semibold text-orange-500" id="endEntry">10</span> 
                                    dari 
                                    <span class="font-semibold text-orange-500" id="totalEntries">{{ $categories->total() }}</span> 
                                    kategori
                                </div>
                                <div class="flex items-center space-x-2">
                                    {{ $categories->withQueryString()->links() }}
                                </div>
                            </div>
                        </div>

                      </div>
                  </div>
              </div>
          </div>
        </div>
      </div>

      <!-- Scripts -->
      <script>
        // Add toggleSidebar function
        function toggleSidebar() {
          const sidebar = document.querySelector('#sidebar');
          sidebar.classList.toggle('-translate-x-full');
        }
        
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

        // Fungsi untuk search client-side
        function handleSearch(e) {
            const searchTerm = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const name = row.querySelector('td:first-child').textContent.toLowerCase();
                if (name.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Event listener untuk search
            document.getElementById('searchInput').addEventListener('input', handleSearch);
            
            // Event listener untuk entries per page (redirect dengan parameter)
            document.getElementById('entriesPerPage').addEventListener('change', function(e) {
                const url = new URL(window.location.href);
                url.searchParams.set('per_page', e.target.value);
                window.location.href = url.toString();
            });
        });
      </script>
    </body>
</html>