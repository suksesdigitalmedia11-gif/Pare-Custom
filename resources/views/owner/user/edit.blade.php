<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Pengguna - Pare Custom</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4f46e5; /* Indigo 600 - A more modern blue */
            --primary-hover: #4338ca; /* Indigo 700 */
            --secondary-color: #f97316; /* Orange 500 */
            --success-color: #10b981; /* Emerald 500 */
            --error-color: #ef4444; /* Red 500 */
            --text-dark: #1f2937; /* Gray 800 */
            --text-medium: #4b5563; /* Gray 600 */
            --text-light: #9ca3af; /* Gray 400 */
            --bg-light: #f9fafb; /* Gray 50 */
            --card-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        body {
            font-family: 'Raleway', sans-serif;
            background-color: #f3f4f6;
            color: var(--text-dark);
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
            background-color: var(--primary-color);
            transition: width 0.3s ease;
        }
        
        .hover-link:hover .nav-text::after {
            width: 100%;
        }
        
        input[type="text"],
        input[type="email"],
        input[type="password"],
        select {
            border: 2px solid #e2e8f0;
            padding-top: 0.75rem;
            padding-bottom: 0.75rem;
            padding-right: 2.5rem;
            border-radius: 0.75rem;
            width: 100%;
            transition: all 0.3s ease;
            font-size: 0.95rem;
        }
        
        /* Default padding-left */
        input[type="text"]:not([class*="pl-"]),
        input[type="email"]:not([class*="pl-"]),
        input[type="password"]:not([class*="pl-"]),
        select:not([class*="pl-"]) {
            padding-left: 1rem;
        }
        
        input[type="text"]:focus,
        input[type="email"]:focus,
        input[type="password"]:focus,
        select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.2);
            outline: none;
        }
        
        input[type="text"]:hover:not(:focus),
        input[type="email"]:hover:not(:focus),
        input[type="password"]:hover:not(:focus),
        select:hover:not(:focus) {
            border-color: #cbd5e0;
        }
        
        select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 0.75rem center;
            background-repeat: no-repeat;
            background-size: 1.5em 1.5em;
            padding-right: 2.5rem;
        }
        
        .btn {
            transition: all 0.3s ease;
            font-weight: 500;
            padding: 0.75rem 1.5rem;
            border-radius: 0.75rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        
        .btn:active {
            transform: translateY(0);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
            font-weight: 600;
        }
        
        .btn-primary:hover {
            background-color: var(--primary-hover);
        }
        
        .btn-secondary {
            background-color: white;
            color: var(--text-medium);
            border: 2px solid #e5e7eb;
            font-weight: 600;
        }
        
        .btn-secondary:hover {
            background-color: #f3f4f6;
        }
        
        .form-card {
            transition: all 0.3s ease;
            box-shadow: var(--card-shadow);
            border-radius: 1rem;
            border: 1px solid rgba(229, 231, 235, 0.5);
        }
        
        .form-card:hover {
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            font-weight: 600;
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-dark);
            font-size: 0.95rem;
        }
        
        .form-hint {
            font-size: 0.875rem;
            color: var(--text-medium);
            margin-top: 0.5rem;
        }
        
        .card-header {
            background-color: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            padding: 1.25rem 1.5rem;
            border-top-left-radius: 1rem;
            border-top-right-radius: 1rem;
        }
        
        .error-feedback {
            padding: 0.5rem;
            border-radius: 0.5rem;
            background-color: rgba(239, 68, 68, 0.1);
            border-left: 3px solid var(--error-color);
            margin-top: 0.5rem;
        }
        
        /* Password strength indicator */
        .password-strength {
            height: 5px;
            border-radius: 5px;
            margin-top: 0.5rem;
            background-color: #e5e7eb;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .password-strength-indicator {
            height: 100%;
            width: 0%;
            transition: all 0.3s ease;
        }
        
        .strength-weak {
            width: 33%;
            background-color: #ef4444;
        }
        
        .strength-medium {
            width: 66%;
            background-color: #f59e0b;
        }
        
        .strength-strong {
            width: 100%;
            background-color: #10b981;
        }
        
        /* Tooltip styling */
        .tooltip {
            position: relative;
            display: inline-block;
            cursor: help;
        }
        
        .tooltip .tooltip-text {
            visibility: hidden;
            width: 200px;
            background-color: #333;
            color: #fff;
            text-align: center;
            border-radius: 6px;
            padding: 8px;
            position: absolute;
            z-index: 1;
            bottom: 125%;
            left: 50%;
            margin-left: -100px;
            opacity: 0;
            transition: opacity 0.3s;
            font-size: 0.8rem;
            font-weight: normal;
        }
        
        .tooltip:hover .tooltip-text {
            visibility: visible;
            opacity: 1;
        }
        
        /* User type cards */
        .user-type-card {
            border: 2px solid #e5e7eb;
            border-radius: 0.75rem;
            padding: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .user-type-card:hover {
            border-color: #d1d5db;
            transform: translateY(-2px);
        }
        
        .user-type-card.selected {
            border-color: var(--primary-color);
            background-color: rgba(79, 70, 229, 0.05);
        }
        
        .user-type-card .user-type-icon {
            background-color: #f3f4f6;
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 0.75rem;
            transition: all 0.3s ease;
        }
        
        .user-type-card.selected .user-type-icon {
            background-color: var(--primary-color);
            color: white;
        }
        
        /* Animation for changed fields */
        @keyframes highlight {
            0% { background-color: rgba(79, 70, 229, 0.1); }
            100% { background-color: transparent; }
        }
        
        .highlight-change {
            animation: highlight 2s ease-out;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="flex">
        
        <x-navbar-owner></x-navbar-owner>
        
        <div class="flex-1 lg:w-5/6">
            <x-navbar-top-owner></x-navbar-top-owner>
            
            <div class="p-4 lg:p-8">
                <div class="p-4 lg:p-6 bg-gray-100 min-h-screen">
                    <div class="max-w-2xl mx-auto">
                        <!-- Breadcrumb - Enhanced -->
                        <nav class="flex mb-5" aria-label="Breadcrumb">
                            <ol class="inline-flex items-center space-x-1 md:space-x-3 bg-transparent px-4 py-2 rounded-lg">
                                <li class="inline-flex items-center">
                                    <a href="#" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-indigo-600 transition-colors">
                                        <i class="bi bi-house-door-fill mr-2"></i>
                                        Dashboard
                                    </a>
                                </li>
                                <li>
                                    <div class="flex items-center">
                                        <svg class="w-5 h-5 text-gray-400 mx-1" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                                        </svg>
                                        <a href="{{ route('owner.user.index') }}" class="ml-1 text-sm font-medium text-gray-700 hover:text-indigo-600 transition-colors">Pengguna</a>
                                    </div>
                                </li>
                                <li aria-current="page">
                                    <div class="flex items-center">
                                        <svg class="w-5 h-5 text-gray-400 mx-1" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                                        </svg>
                                        <span class="ml-1 text-sm font-medium text-indigo-600">Edit Pengguna</span>
                                    </div>
                                </li>
                            </ol>
                        </nav>
                        
                        <!-- Page Header - Enhanced with icon -->
                        <div class="mb-6 bg-white rounded-lg p-5 shadow-sm border-l-4 border-indigo-500">
                            <div class="flex items-start">
                                <div class="p-2 bg-indigo-100 rounded-lg mr-4">
                                    <i class="bi bi-person text-2xl text-indigo-600"></i>
                                </div>
                                <div>
                                    <h1 class="text-2xl font-bold text-gray-800">Edit Pengguna</h1>
                                    <p class="text-gray-600 mt-1">Ubah informasi untuk pengguna: <span class="font-semibold">{{ $user->name }}</span></p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Form Card - Enhanced visual design -->
                        <div class="bg-white rounded-xl overflow-hidden form-card">
                            <div class="card-header">
                                <h2 class="text-lg font-semibold text-gray-800 flex items-center">
                                    <i class="bi bi-person-badge mr-2 text-indigo-500"></i>
                                    Informasi Pengguna
                                </h2>
                            </div>
                            
                            <div class="p-6">
                                <form action="{{ route('owner.user.update', $user) }}" method="POST" id="userForm">
                                    @csrf
                                    @method('PUT')
                                    
                                    <div class="space-y-6">
                                        <!-- Nama Field - Enhanced styling -->
                                        <div class="form-group">
                                            <label for="name" class="form-label flex items-center">
                                                <span>Nama Lengkap</span>
                                                <span class="ml-1 text-red-500">*</span>
                                            </label>
                                            <div class="relative">
                                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                    <i class="bi bi-person text-gray-400"></i>
                                                </div>
                                                <input type="text" name="name" id="name" class="pl-10 focus:ring-2 focus:ring-indigo-200" 
                                                       value="{{ old('name', $user->name) }}" placeholder="Masukkan nama lengkap" required>
                                            </div>
                                            @error('name')
                                                <div class="error-feedback">
                                                    <p class="text-sm text-red-600 flex items-start">
                                                        <i class="bi bi-exclamation-circle mr-1 mt-1"></i>
                                                        <span>{{ $message }}</span>
                                                    </p>
                                                </div>
                                            @enderror
                                        </div>
                                        
                                        <!-- Email Field - Enhanced styling -->
                                        <div class="form-group">
                                            <label for="email" class="form-label flex items-center">
                                                <span>Email</span>
                                                <span class="ml-1 text-red-500">*</span>
                                                <div class="tooltip ml-1">
                                                    <i class="bi bi-info-circle text-gray-400"></i>
                                                    <span class="tooltip-text">Email ini akan digunakan sebagai username untuk login</span>
                                                </div>
                                            </label>
                                            <div class="relative">
                                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                    <i class="bi bi-envelope text-gray-400"></i>
                                                </div>
                                                <input type="email" name="email" id="email" class="pl-10 focus:ring-2 focus:ring-indigo-200" 
                                                       value="{{ old('email', $user->email) }}" placeholder="nama@contoh.com" required>
                                            </div>
                                            @error('email')
                                                <div class="error-feedback">
                                                    <p class="text-sm text-red-600 flex items-start">
                                                        <i class="bi bi-exclamation-circle mr-1 mt-1"></i>
                                                        <span>{{ $message }}</span>
                                                    </p>
                                                </div>
                                            @enderror
                                        </div>
                                        
                                        <!-- User Type Field - Enhanced with cards -->
                                        <div class="form-group">
                                            <label class="form-label flex items-center">
                                                <span>Tipe Pengguna</span>
                                                <span class="ml-1 text-red-500">*</span>
                                            </label>
                                            
                                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mt-2">
                                                <div class="user-type-card {{ old('usertype', $user->usertype) == 'owner' ? 'selected' : '' }}" onclick="selectUserType('owner', this)">
                                                    <div class="user-type-icon">
                                                        <i class="bi bi-crown text-xl"></i>
                                                    </div>
                                                    <h3 class="font-semibold">Owner</h3>
                                                    <p class="text-sm text-gray-500 mt-1">Akses penuh ke semua fitur sistem</p>
                                                    <input type="radio" name="usertype" value="owner" class="hidden" {{ old('usertype', $user->usertype) == 'owner' ? 'checked' : '' }}>
                                                </div>

                                                <div class="user-type-card {{ old('usertype', $user->usertype) == 'finance' ? 'selected' : '' }}" onclick="selectUserType('finance', this)">
                                                    <div class="user-type-icon">
                                                        <i class="bi bi-calculator text-xl"></i>
                                                    </div>
                                                    <h3 class="font-semibold">Finance</h3>
                                                    <p class="text-sm text-gray-500 mt-1">Akses ke keuangan dan laporan</p>
                                                    <input type="radio" name="usertype" value="finance" class="hidden" {{ old('usertype', $user->usertype) == 'finance' ? 'checked' : '' }}>
                                                </div>

                                                <div class="user-type-card {{ old('usertype', $user->usertype) == 'kepala_toko' ? 'selected' : '' }}" onclick="selectUserType('kepala_toko', this)">
                                                    <div class="user-type-icon">
                                                        <i class="bi bi-person-badge text-xl"></i>
                                                    </div>
                                                    <h3 class="font-semibold">Kepala Toko</h3>
                                                    <p class="text-sm text-gray-500 mt-1">Supervisory dan approval akses</p>
                                                    <input type="radio" name="usertype" value="kepala_toko" class="hidden" {{ old('usertype', $user->usertype) == 'kepala_toko' ? 'checked' : '' }}>
                                                </div>

                                                <div class="user-type-card {{ old('usertype', $user->usertype) == 'admin' ? 'selected' : '' }}" onclick="selectUserType('admin', this)">
                                                    <div class="user-type-icon">
                                                        <i class="bi bi-gear text-xl"></i>
                                                    </div>
                                                    <h3 class="font-semibold">Admin</h3>
                                                    <p class="text-sm text-gray-500 mt-1">Akses administrasi sistem</p>
                                                    <input type="radio" name="usertype" value="admin" class="hidden" {{ old('usertype', $user->usertype) == 'admin' ? 'checked' : '' }}>
                                                </div>

                                                <div class="user-type-card {{ old('usertype', $user->usertype) == 'editor' ? 'selected' : '' }}" onclick="selectUserType('editor', this)">
                                                    <div class="user-type-icon">
                                                        <i class="bi bi-pencil-square text-xl"></i>
                                                    </div>
                                                    <h3 class="font-semibold">Editor</h3>
                                                    <p class="text-sm text-gray-500 mt-1">Akses edit data dan konten</p>
                                                    <input type="radio" name="usertype" value="editor" class="hidden" {{ old('usertype', $user->usertype) == 'editor' ? 'checked' : '' }}>
                                                </div>
                                                

                                            </div>
                                            
                                            @error('usertype')
                                                <div class="error-feedback mt-3">
                                                    <p class="text-sm text-red-600 flex items-start">
                                                        <i class="bi bi-exclamation-circle mr-1 mt-1"></i>
                                                        <span>{{ $message }}</span>
                                                    </p>
                                                </div>
                                            @enderror
                                        </div>

                                        @if($user->id !== auth()->id())
                                            <div class="form-group mt-6">
                                                <label class="form-label flex items-center">
                                                    <span>Status Akun & Hak Akses</span>
                                                    <span class="ml-1 text-red-500">*</span>
                                                </label>
                                                <p class="text-xs text-gray-500 mb-3">Jika karyawan telah keluar/resign, ubah status ke Nonaktif untuk mencabut akses tanpa merusak data penjualan atau shift.</p>
                                                
                                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                    <label class="border-2 rounded-xl p-4 cursor-pointer flex items-center space-x-3 transition-all {{ old('is_active', $user->is_active ?? true) ? 'border-green-500 bg-green-50/50' : 'border-gray-200 hover:border-gray-300' }}">
                                                        <input type="radio" name="is_active" value="1" class="text-green-600 focus:ring-green-500" {{ old('is_active', $user->is_active ?? true) ? 'checked' : '' }}>
                                                        <div>
                                                            <div class="font-semibold text-gray-800 flex items-center">
                                                                <span class="w-2 h-2 mr-2 bg-green-500 rounded-full"></span>
                                                                Akun Aktif
                                                            </div>
                                                            <p class="text-xs text-gray-500 mt-0.5">Karyawan dapat login dan menggunakan sistem sesuai perannya.</p>
                                                        </div>
                                                    </label>

                                                    <label class="border-2 rounded-xl p-4 cursor-pointer flex items-center space-x-3 transition-all {{ !old('is_active', $user->is_active ?? true) ? 'border-red-500 bg-red-50/50' : 'border-gray-200 hover:border-gray-300' }}">
                                                        <input type="radio" name="is_active" value="0" class="text-red-600 focus:ring-red-500" {{ !old('is_active', $user->is_active ?? true) ? 'checked' : '' }}>
                                                        <div>
                                                            <div class="font-semibold text-gray-800 flex items-center">
                                                                <span class="w-2 h-2 mr-2 bg-red-500 rounded-full"></span>
                                                                Akun Nonaktif (Karyawan Keluar)
                                                            </div>
                                                            <p class="text-xs text-gray-500 mt-0.5">Akses login dicabut seketika, namun seluruh riwayat data tetap tersimpan aman.</p>
                                                        </div>
                                                    </label>
                                                </div>
                                            </div>
                                        @endif
                                        
                                        <div class="border-t border-gray-200 my-6"></div>
                                        
                                        <div class="bg-blue-50 p-4 rounded-lg mb-6">
                                            <div class="flex items-start">
                                                <div class="text-blue-500 mr-3">
                                                    <i class="bi bi-info-circle-fill text-xl"></i>
                                                </div>
                                                <div>
                                                    <h3 class="font-semibold text-blue-800">Ubah Password</h3>
                                                    <p class="text-sm text-blue-700 mt-1">Kosongkan bidang password jika Anda tidak ingin mengubahnya</p>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Password Field - Enhanced with strength meter -->
                                        <div class="form-group">
                                            <label for="password" class="form-label flex items-center">
                                                <span>Password Baru</span>
                                                <div class="tooltip ml-1">
                                                    <i class="bi bi-info-circle text-gray-400"></i>
                                                    <span class="tooltip-text">Kosongkan jika tidak ingin mengubah password</span>
                                                </div>
                                            </label>
                                            <div class="relative">
                                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                    <i class="bi bi-lock text-gray-400"></i>
                                                </div>
                                                <input type="password" name="password" id="password" class="pl-10 focus:ring-2 focus:ring-indigo-200" 
                                                       placeholder="Minimal 8 karakter" onkeyup="checkPasswordStrength()">
                                                <button type="button" class="absolute inset-y-0 right-0 pr-3 flex items-center" onclick="togglePasswordVisibility('password')">
                                                    <i class="bi bi-eye text-gray-400 hover:text-gray-600 transition-colors"></i>
                                                </button>
                                            </div>
                                            
                                            <!-- Password strength meter -->
                                            <div class="password-strength mt-2">
                                                <div class="password-strength-indicator" id="passwordStrengthBar"></div>
                                            </div>
                                            <p class="text-xs text-gray-500 mt-1" id="passwordStrengthText">Kekuatan password: belum diisi</p>
                                            
                                            @error('password')
                                                <div class="error-feedback">
                                                    <p class="text-sm text-red-600 flex items-start">
                                                        <i class="bi bi-exclamation-circle mr-1 mt-1"></i>
                                                        <span>{{ $message }}</span>
                                                    </p>
                                                </div>
                                            @enderror
                                        </div>
                                        
                                        <!-- Confirm Password Field - Enhanced styling -->
                                        <div class="form-group">
                                            <label for="password_confirmation" class="form-label flex items-center">
                                                <span>Konfirmasi Password Baru</span>
                                            </label>
                                            <div class="relative">
                                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                    <i class="bi bi-lock-fill text-gray-400"></i>
                                                </div>
                                                <input type="password" name="password_confirmation" id="password_confirmation" class="pl-10 focus:ring-2 focus:ring-indigo-200" 
                                                       placeholder="Masukkan kembali password" onkeyup="checkPasswordMatch()">
                                                <button type="button" class="absolute inset-y-0 right-0 pr-3 flex items-center" onclick="togglePasswordVisibility('password_confirmation')">
                                                    <i class="bi bi-eye text-gray-400 hover:text-gray-600 transition-colors"></i>
                                                </button>
                                            </div>
                                            <p class="text-xs mt-1" id="passwordMatchMessage"></p>
                                        </div>
                                    </div>
                                    
                                    <!-- Action Buttons - Enhanced styling -->
                                    <div class="flex flex-col sm:flex-row justify-end gap-3 mt-8">
                                        <a href="{{ route('owner.user.index') }}" class="btn btn-secondary order-2 sm:order-1">
                                            <i class="bi bi-arrow-left mr-2"></i> Kembali
                                        </a>
                                        <button type="submit" class="btn btn-primary order-1 sm:order-2" id="submitBtn">
                                            <i class="bi bi-check2 mr-2"></i> Simpan Perubahan
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        
                        <!-- Last login info -->
                        <div class="mt-6 bg-white rounded-lg p-5 shadow-sm">
                            <h3 class="font-semibold text-gray-800 mb-2">Informasi Akun</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                <div>
                                    <p class="text-gray-600">Tanggal Dibuat:</p>
                                    <p class="font-medium">{{ $user->created_at->format('d M Y, H:i') }}</p>
                                </div>
                                <div>
                                    <p class="text-gray-600">Terakhir Login:</p>
                                    <p class="font-medium">{{ $user->last_login_at ?? 'Belum pernah login' }}</p>
                                </div>
                                <div>
                                    <p class="text-gray-600">Terakhir Diubah:</p>
                                    <p class="font-medium">{{ $user->updated_at->format('d M Y, H:i') }}</p>
                                </div>
                                <div>
                                    <p class="text-gray-600">Status:</p>
                                    <span class="px-2 py-1 rounded-full text-xs font-medium {{ $user->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ $user->is_active ? 'Aktif' : 'Tidak Aktif' }}
                                    </span>
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
            const navbarOwner = document.querySelector('x-navbar-owner');
            if (navbarOwner) {
                navbarOwner.classList.toggle('hidden');
                navbarOwner.classList.toggle('lg:block');
            }
        }
        
        function togglePasswordVisibility(inputId) {
            const passwordInput = document.getElementById(inputId);
            const icon = event.currentTarget.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        }
        
        function selectUserType(type, element) {
            // Remove selected class from all cards
            const cards = document.querySelectorAll('.user-type-card');
            cards.forEach(card => {
                card.classList.remove('selected');
            });
            
            // Add selected class to clicked card
            element.classList.add('selected');
            
            // Select the radio input
            const radioInput = element.querySelector('input[type="radio"]');
            radioInput.checked = true;
        }
        
        function checkPasswordStrength() {
            const password = document.getElementById('password').value;
            const strengthBar = document.getElementById('passwordStrengthBar');
            const strengthText = document.getElementById('passwordStrengthText');
            
            // Reset the strength bar
            strengthBar.className = 'password-strength-indicator';
            
            if (password.length === 0) {
                strengthText.textContent = 'Kekuatan password: belum diisi';
                return;
            }
            
            // Check strength
            let strength = 0;
            
            // Length check
            if (password.length >= 8) {
                strength += 1;
            }
            
            // Character variety checks
            if (/[A-Z]/.test(password)) {
                strength += 1;
            }
            if (/[0-9]/.test(password)) {
                strength += 1;
            }
            if (/[^A-Za-z0-9]/.test(password)) {
                strength += 1;
            }
            
            // Update strength indicator
            if (strength === 1) {
                strengthBar.classList.add('strength-weak');
                strengthText.textContent = 'Kekuatan password: lemah';
                strengthText.className = 'text-xs text-red-500 mt-1';
            } else if (strength === 2 || strength === 3) {
                strengthBar.classList.add('strength-medium');
                strengthText.textContent = 'Kekuatan password: sedang';
                strengthText.className = 'text-xs text-amber-500 mt-1';
            } else if (strength >= 4) {
                strengthBar.classList.add('strength-strong');
                strengthText.textContent = 'Kekuatan password: kuat';
                strengthText.className = 'text-xs text-green-500 mt-1';
            }
        }
        
        function checkPasswordMatch() {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('password_confirmation').value;
            const messageElement = document.getElementById('passwordMatchMessage');
            
            if (confirmPassword.length === 0) {
                messageElement.textContent = '';
                return;
            }
            
            if (password === confirmPassword) {
                messageElement.textContent = 'Password cocok!';
                messageElement.className = 'text-xs text-green-500 mt-1';
            } else {
                messageElement.textContent = 'Password tidak cocok!';
                messageElement.className = 'text-xs text-red-500 mt-1';
            }
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
        
        // Highlight changes
        function trackChanges() {
            const formInputs = document.querySelectorAll('input[type="text"], input[type="email"], select');
            formInputs.forEach(input => {
                const originalValue = input.value;
                input.addEventListener('change', function() {
                    if (this.value !== originalValue) {
                        this.classList.add('highlight-change');
                        setTimeout(() => {
                            this.classList.remove('highlight-change');
                        }, 2000);
                    }
                });
            });
        }
        
        // Initialize form elements on page load
        document.addEventListener('DOMContentLoaded', function() {
            trackChanges();
            
            // Form validation before submit
            document.getElementById('userForm').addEventListener('submit', function(event) {
                const password = document.getElementById('password').value;
                const confirmPassword = document.getElementById('password_confirmation').value;
                
                if (password !== '' && password !== confirmPassword) {
                    event.preventDefault();
                    alert('Password dan konfirmasi password tidak cocok!');
                }
            });
        });
    </script>
</body>
</html>