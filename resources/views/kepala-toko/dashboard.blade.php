<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Kepala Toko Dashboard - Pare Custom</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Bootstrap Icons CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css" />
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
    </style>
  </head>
  <body class="bg-gray-100">
    <div class="flex">

      <!-- Sidebar -->
      <x-navbar-kepala-toko></x-navbar-kepala-toko>

      <!-- Main Content -->
      <div class="flex-1 lg:w-5/6">
        {{-- Navbar Top --}}
        <x-navbar-top-kepala-toko></x-navbar-top-kepala-toko>

        <!-- Content Wrapper -->
        <div class="p-4 lg:p-8">
          <!-- Welcome Message -->
          <div class="bg-white p-6 rounded-xl shadow-lg mb-6 relative overflow-hidden">
            <!-- Background Pattern -->
            <div class="absolute right-0 top-0 w-32 h-32 bg-[#005281]/5 rounded-bl-full"></div>
            
            <div class="flex items-center gap-6 relative">
                <div class="flex items-center justify-center w-16 h-16 bg-[#005281]/10 rounded-full">
                    <i class="bi bi-person-badge text-4xl text-[#005281]"></i>
                </div>
                <div class="space-y-2">
                    <div class="flex items-center gap-3">
                        <h2 class="text-2xl font-semibold text-gray-700">
                            Halo {{ ucfirst(Auth::user()->name) }}!
                        </h2>
                        <span class="px-3 py-1 text-sm bg-[#005281]/10 text-[#005281] rounded-full">
                            Kepala Toko
                        </span>
                    </div>
                    <div class="flex items-center gap-2 text-sm text-gray-500">
                        <i class="bi bi-clock"></i>
                        <span>{{ now()->format('l, d F Y') }}</span>
                    </div>
                    <p class="text-gray-600 text-sm">
                        Selamat datang di dashboard Kepala Toko untuk supervisi dan manajemen operasional.
                    </p>
                </div>
            </div>
          </div>

          <!-- Kepala Toko Features Grid -->
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
            <div class="bg-white p-6 rounded-xl shadow-lg">
              <div class="flex items-center gap-4">
                <div class="flex items-center justify-center w-12 h-12 bg-green-100 rounded-lg">
                  <i class="bi bi-people text-2xl text-green-600"></i>
                </div>
                <div>
                  <h3 class="font-semibold text-gray-700">Kelola Staff</h3>
                  <p class="text-sm text-gray-500">Manajemen karyawan toko</p>
                </div>
              </div>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-lg">
              <div class="flex items-center gap-4">
                <div class="flex items-center justify-center w-12 h-12 bg-blue-100 rounded-lg">
                  <i class="bi bi-check-circle text-2xl text-blue-600"></i>
                </div>
                <div>
                  <h3 class="font-semibold text-gray-700">Approval Center</h3>
                  <p class="text-sm text-gray-500">Stock opname & Purchase orders</p>
                </div>
              </div>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-lg">
              <div class="flex items-center gap-4">
                <div class="flex items-center justify-center w-12 h-12 bg-purple-100 rounded-lg">
                  <i class="bi bi-graph-up text-2xl text-purple-600"></i>
                </div>
                <div>
                  <h3 class="font-semibold text-gray-700">Laporan Performa</h3>
                  <p class="text-sm text-gray-500">Analisis performa toko</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Development Notice -->
          <div class="bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 p-6 rounded-xl shadow-lg">
            <div class="flex items-center gap-3">
              <div class="flex items-center justify-center w-10 h-10 bg-green-100 rounded-full">
                <i class="bi bi-info-circle text-green-600"></i>
              </div>
              <div>
                <h3 class="font-semibold text-green-800">Dashboard dalam Pengembangan</h3>
                <p class="text-sm text-green-700 mt-1">
                  Fitur Kepala Toko sedang dalam tahap pengembangan. Sistem role berhasil diimplementasi!
                </p>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>

    <script>
      function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        sidebar.classList.toggle('-translate-x-full');
      }

      function toggleDropdown(button) {
        const dropdownMenu = button.nextElementSibling;
        const chevronIcon = button.querySelector('.bi-chevron-down');
        
        dropdownMenu.classList.toggle('max-h-0');
        dropdownMenu.classList.toggle('max-h-40');
        chevronIcon.classList.toggle('rotate-180');
      }
    </script>
  </body>
</html>