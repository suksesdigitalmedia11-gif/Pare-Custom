<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Profile Settings - Bblara</title>
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
        input[type="text"],
        input[type="email"],
        input[type="password"] {
            background-color: #F9FAFB;
            border-color: #E5E7EB;
        }
        input[type="text"]:focus,
        input[type="email"]:focus,
        input[type="password"]:focus {
            background-color: #F3F4F6;
        }
        .modal-transition {
            transition: all 0.3s ease-out;
        }
        .preview-image {
            transition: opacity 0.3s ease;
        }
        .preview-image:hover {
            opacity: 0.8;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="flex">

        <!-- Sidebar -->
        <x-navbar-owner></x-navbar-owner>

        <!-- Main Content -->
        <div class="flex-1 lg:w-5/6">
            {{-- Navbar Top --}}
            <x-navbar-top-owner></x-navbar-top-owner>

            <!-- Content Wrapper -->
            <div class="p-4 lg:px-8 py-0">
                <div class="p-6 bg-gray-100 min-h-screen">
                    <div class="max-w-7xl mx-auto">
                        <!-- Welcome Message -->
                        <div class="bg-white p-6 mb-6 rounded-lg flex items-center shadow space-x-4">
                            <i class="bi bi-person-circle text-5xl text-[#005281]"></i>
                            <div>
                                <h2 class="text-2xl lg:text-2xl font-semibold text-gray-700">Profile Settings</h2>
                                <p class="text-gray-600 text-base">Manage your account settings and preferences</p>
                            </div>
                        </div>

                        @if(session('success'))
                            <div class="bg-green-100 border border-green-400 text-green-700 px-6 py-4 rounded relative mb-4 text-base" role="alert">
                                <span class="block sm:inline">{{ session('success') }}</span>
                            </div>
                        @endif

                        @if($errors->any())
                            <div class="bg-red-100 border border-red-400 text-red-700 px-6 py-4 rounded relative mb-4 text-base" role="alert">
                                <ul>
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="space-y-8">
                            <!-- Profile Information -->
                            <div class="bg-white p-8 rounded-lg shadow-md">
                                <h3 class="text-xl font-semibold text-gray-700 mb-6">Profile Information</h3>
                                <form action="{{ route('owner.profile.update') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                                    @csrf
                                    @method('PUT')

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <!-- Avatar Section -->
                                        <div class="flex flex-col items-center space-y-6 mb-6">
                                            <div class="relative w-32 h-32 rounded-full overflow-hidden bg-gray-200 group">
                                                @if($user->avatar)
                                                    <img src="{{ Storage::url('avatars/' . $user->avatar) }}" alt="Profile" class="w-full h-full object-cover preview-image" id="avatar-preview">
                                                @else
                                                    <div id="avatar-placeholder" class="w-full h-full flex items-center justify-center text-gray-400">
                                                        <i class="bi bi-person-fill text-6xl"></i>
                                                    </div>
                                                    <img src="{{ asset('storage/avatars/dummy.jpeg') }}" alt="Profile" class="w-full h-full object-cover preview-image hidden" id="avatar-preview">
                                                @endif
                                                <div class="absolute inset-0 bg-black bg-opacity-40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                                    <span class="text-white text-sm">Change Photo</span>
                                                </div>
                                            </div>
                                            <div>
                                                <input type="file" name="avatar" id="avatar" class="hidden" accept="image/*">
                                                <label for="avatar" class="cursor-pointer px-6 py-3 text-base bg-[#005281] text-white rounded-md hover:bg-[#003d61] focus:outline-none focus:ring-2 focus:ring-[#005281] focus:ring-opacity-50 transition-colors duration-200">
                                                    Change Avatar
                                                </label>
                                                <p class="mt-2 text-sm text-gray-500">JPG, PNG, GIF up to 10MB</p>
                                            </div>
                                        </div>

                                        <!-- Name and Email Section -->
                                        <div class="space-y-6">
                                            <div class="space-y-2">
                                                <label for="name" class="block text-base font-medium text-gray-700">Name</label>
                                                <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}" class="h-12 px-4 mt-1 block w-full text-base rounded-md border-gray-300 shadow-sm focus:border-[#005281] focus:ring focus:ring-[#005281] focus:ring-opacity-50 bg-[#F9FAFB]" required>
                                                @error('name')
                                                    <p class="text-red-500 text-base mt-1">{{ $message }}</p>
                                                @enderror
                                            </div>

                                            <div class="space-y-2">
                                                <label for="email" class="block text-base font-medium text-gray-700">Email</label>
                                                <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" class="h-12 px-4 mt-1 block w-full text-base rounded-md border-gray-300 shadow-sm focus:border-[#005281] focus:ring focus:ring-[#005281] focus:ring-opacity-50 bg-[#F9FAFB]" required>
                                                @error('email')
                                                    <p class="text-red-500 text-base mt-1">{{ $message }}</p>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Submit Button -->
                                    <div class="flex justify-end mt-6">
                                        <button type="submit" class="w-full sm:w-auto px-6 py-3 text-base bg-[#005281] text-white rounded-md hover:bg-[#003d61] focus:outline-none focus:ring-2 focus:ring-[#005281] focus:ring-opacity-50 transition-colors duration-200">
                                            Save Changes
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <!-- Update Password -->
                            <div class="bg-white p-8 rounded-lg shadow-md">
                                <h3 class="text-xl font-semibold text-gray-700 mb-6">Update Password</h3>
                                <form action="{{ route('owner.profile.password') }}" method="POST" class="space-y-6">
                                    @csrf
                                    @method('PUT')

                                    <div class="space-y-2">
                                        <label for="current_password" class="block text-base font-medium text-gray-700">Current Password</label>
                                        <input type="password" name="current_password" id="current_password" class="h-12 px-4 mt-1 block w-full text-base rounded-md border-gray-300 shadow-sm focus:border-[#005281] focus:ring focus:ring-[#005281] focus:ring-opacity-50" required>
                                        @error('current_password')
                                            <p class="text-red-500 text-base mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div class="space-y-2">
                                        <label for="password" class="block text-base font-medium text-gray-700">New Password</label>
                                        <input type="password" name="password" id="password" class="h-12 px-4 mt-1 block w-full text-base rounded-md border-gray-300 shadow-sm focus:border-[#005281] focus:ring focus:ring-[#005281] focus:ring-opacity-50" required>
                                        @error('password')
                                            <p class="text-red-500 text-base mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div class="space-y-2">
                                        <label for="password_confirmation" class="block text-base font-medium text-gray-700">Confirm New Password</label>
                                        <input type="password" name="password_confirmation" id="password_confirmation" class="h-12 px-4 mt-1 block w-full text-base rounded-md border-gray-300 shadow-sm focus:border-[#005281] focus:ring focus:ring-[#005281] focus:ring-opacity-50" required>
                                    </div>

                                    <button type="submit" class="w-full sm:w-auto px-6 py-3 text-base bg-[#005281] text-white rounded-md hover:bg-[#003d61] focus:outline-none focus:ring-2 focus:ring-[#005281] focus:ring-opacity-50 transition-colors duration-200">
                                        Update Password
                                    </button>
                                </form>
                            </div>

                            <!-- Delete Account -->
                            <div class="bg-white p-8 rounded-lg shadow-md">
                                <h3 class="text-xl font-semibold text-gray-700 mb-4">Delete Account</h3>
                                <p class="text-base text-gray-600 mb-6">Once your account is deleted, all of its resources and data will be permanently deleted.</p>
                                <button onclick="toggleDeleteModal()" class="px-6 py-3 text-base bg-red-600 text-white rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-opacity-50 transition-colors duration-200">
                                    Delete Account
                                </button>
                            </div>
                        </div>

                        <!-- Delete Account Modal -->
                        <div id="deleteModal" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
                            <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"></div>
                            
                            <div class="flex min-h-screen items-center justify-center p-4 text-center sm:p-0">
                                <div class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
                                    <div class="p-6">
                                        <div class="text-center">
                                            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-red-100 mb-4">
                                                <i class="bi bi-exclamation-triangle text-red-600 text-xl"></i>
                                            </div>
                                            <h3 class="text-lg font-medium text-gray-900 mb-2">Delete Account</h3>
                                            <div class="mt-2">
                                                <p class="text-sm text-gray-500">Are you sure you want to delete your account? This action cannot be undone.</p>
                                            </div>
                                        </div>

                                        <form action="{{ route('owner.profile.destroy') }}" method="POST" class="mt-5">
                                            @csrf
                                            @method('DELETE')
                                            
                                            <div class="mb-4">
                                                <label for="delete-password" class="block text-sm font-medium text-gray-700 mb-2">Please enter your password to confirm</label>
                                                <input type="password" name="password" id="delete-password" class="h-10 px-3 w-full text-sm rounded-md border border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 focus:ring-opacity-50 transition-colors duration-200" required placeholder="Enter your password">
                                            </div>

                                            <div class="flex justify-end gap-3 mt-6">
                                                <button type="button" onclick="toggleDeleteModal()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-300 transition-colors duration-200">
                                                    Cancel
                                                </button>
                                                <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors duration-200">
                                                    Delete Account
                                                </button>
                                            </div>
                                        </form>

                                        <!-- Close button -->
                                        <button onclick="toggleDeleteModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-500 transition-colors duration-200">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
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

        function toggleDeleteModal() {
            const modal = document.getElementById('deleteModal');
            if (modal.classList.contains('hidden')) {
                modal.classList.remove('hidden');
                document.body.style.overflow = 'hidden';
            } else {
                modal.classList.add('hidden');
                document.body.style.overflow = '';
            }
        }

        // Avatar preview functionality
        document.getElementById('avatar').addEventListener('change', function(e) {
            const preview = document.getElementById('avatar-preview');
            const placeholder = document.getElementById('avatar-placeholder');
            
            if (e.target.files && e.target.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.classList.remove('hidden');
                    if (placeholder) {
                        placeholder.classList.add('hidden');
                    }
                }
                
                reader.readAsDataURL(e.target.files[0]);
            }
        });

        // Optional: Close modal when clicking outside
        document.addEventListener('click', function(e) {
            const modal = document.getElementById('deleteModal');
            const modalContent = modal.querySelector('.bg-white');
            if (e.target === modal) {
                toggleDeleteModal();
            }
        });

        // Optional: Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && !document.getElementById('deleteModal').classList.contains('hidden')) {
                toggleDeleteModal();
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
        
    </script>
</body>
</html>