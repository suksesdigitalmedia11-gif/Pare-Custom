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
                                    <p class="mt-1 text-lg text-gray-900">{{ $user->id }}</p>
                                </div>
                                <div>
                                    <h3 class="text-sm font-medium text-gray-500">Nama</h3>
                                    <p class="mt-1 text-lg text-gray-900">{{ $user->name }}</p>
                                </div>
                                <div>
                                    <h3 class="text-sm font-medium text-gray-500">Email</h3>
                                    <p class="mt-1 text-lg text-gray-900">{{ $user->email }}</p>
                                </div>
                                <div>
                                    <h3 class="text-sm font-medium text-gray-500">Tipe Pengguna</h3>
                                    <p class="mt-1 text-lg text-gray-900">{{ $user->usertype }}</p>
                                </div>
                            </div>
                            <div class="flex justify-end space-x-2">
                                <a href="{{ route('owner.user.index') }}" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600">Kembali</a>
                                <a href="{{ route('owner.user.edit', $user) }}" class="bg-yellow-500 text-white px-4 py-2 rounded-lg hover:bg-yellow-600">Edit</a>
                                <form action="{{ route('owner.user.destroy', $user) }}" method="POST" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600" onclick="return confirm('Apakah Anda yakin ingin menghapus pengguna ini?')">Hapus</button>
                                </form>
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