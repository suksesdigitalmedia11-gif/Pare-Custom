<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Import Kategori - Pare Custom</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css" />
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
            <div class="max-w-7xl mx-auto">
              <div class="flex justify-between items-center mb-6">
                <h1 class="text-3xl font-bold text-gray-800">Import Kategori</h1>
                <div class="flex space-x-2">
                  <a href="{{ route('owner.category.download-template') }}" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg">
                    <i class="bi bi-download mr-1"></i>Unduh Template Excel
                  </a>
                  <a href="{{ route('owner.category.index') }}" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg">
                    <i class="bi bi-arrow-left mr-1"></i>Kembali ke Daftar Kategori
                  </a>
                </div>
              </div>

              @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                  <span class="block sm:inline">{{ session('success') }}</span>
                </div>
              @endif

              @if($errors->any())
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                  <ul class="list-disc pl-5">
                    @foreach($errors->all() as $error)
                      <li>{{ $error }}</li>
                    @endforeach
                  </ul>
                </div>
              @endif

              <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="p-6">
                  <form action="{{ route('owner.category.import') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-6">
                      <label for="file" class="text-sm font-medium text-gray-600 mb-2 block">
                        <i class="bi bi-upload mr-1"></i>Upload File CSV
                      </label>
                      <input type="file" id="file" name="file" accept=".csv" required class="w-full border rounded-lg p-2.5 focus:outline-none focus:border-orange-500 focus:ring-2 focus:ring-orange-200 transition-all duration-200">
                      <p class="mt-2 text-sm text-gray-500">
                        File CSV harus memiliki kolom: name, description, is_active (1 atau 0).
                      </p>
                      <p class="mt-2 text-sm text-gray-500">
                        <strong>Petunjuk:</strong><br>
                        - <strong>name</strong>: Nama kategori (maks. 255 karakter, wajib).<br>
                        - <strong>description</strong>: Deskripsi kategori (opsional).<br>
                        - <strong>is_active</strong>: Status aktif (1 untuk aktif, 0 untuk nonaktif, wajib).<br>
                        - Unduh template Excel untuk contoh format yang benar.
                      </p>
                    </div>
                    <div class="flex justify-end">
                      <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg">
                        <i class="bi bi-upload mr-1"></i>Import
                      </button>
                    </div>
                  </form>
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