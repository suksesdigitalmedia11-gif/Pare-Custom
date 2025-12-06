<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Editor Dashboard - Pare Custom</title>
    <script src="https://cdn.tailwindcss.com"></script>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" />
    <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;600&display=swap" rel="stylesheet">
    <style>
            body { font-family: 'Raleway', sans-serif; }
    </style>
  </head>
  <body class="bg-gray-100">
        @php
            use Illuminate\Support\Facades\Storage;
            use Illuminate\Support\Str;
            $statusColors = [
                'pending' => 'bg-amber-100 text-amber-800',
                'in_progress' => 'bg-blue-100 text-blue-800',
                'waiting_customer' => 'bg-purple-100 text-purple-800',
                'approved' => 'bg-emerald-100 text-emerald-800',
                'rejected' => 'bg-red-100 text-red-800',
            ];
        @endphp

    <div class="flex">

            <x-navbar-editor></x-navbar-editor>

      <div class="flex-1 lg:w-5/6">
                <x-navbar-top-editor></x-navbar-top-editor>

                <div class="p-4 lg:p-8 space-y-6">
                    <div class="bg-white p-6 rounded-xl shadow-lg">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                            <div>
                                <p class="text-sm text-gray-500">{{ $rangeLabel }}</p>
                                <h1 class="text-2xl font-semibold text-gray-800 mt-1">Workspace Desain (DTF & Jersey)</h1>
                                <p class="text-gray-600 text-sm">Pantau status desain yang berasal dari pesanan DTF dan Jersey, lalu kirim update ke customer.</p>
                    </div>
                    <div class="flex items-center gap-2 text-sm text-gray-500">
                                <i class="bi bi-calendar3 text-lg text-blue-500"></i>
                                {{ now()->translatedFormat('l, d F Y') }}
                            </div>
                        </div>
                        @if(session('success'))
                            <div class="mt-4 bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-2 rounded-lg">
                                {{ session('success') }}
                            </div>
                        @endif
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                        <div class="bg-white rounded-xl shadow p-5 border-l-4 border-amber-400">
                            <p class="text-sm text-gray-500 flex items-center gap-2">
                                <span class="inline-flex w-2 h-2 rounded-full bg-amber-400"></span>
                                Menunggu Brief
                            </p>
                            <h3 class="text-3xl font-semibold text-gray-800 mt-2">{{ $overview['pending'] }}</h3>
                        </div>
                        <div class="bg-white rounded-xl shadow p-5 border-l-4 border-blue-500">
                            <p class="text-sm text-gray-500 flex items-center gap-2">
                                <span class="inline-flex w-2 h-2 rounded-full bg-blue-500"></span>
                                Proses Desain
                            </p>
                            <h3 class="text-3xl font-semibold text-gray-800 mt-2">{{ $overview['in_progress'] }}</h3>
                        </div>
                        <div class="bg-white rounded-xl shadow p-5 border-l-4 border-purple-500">
                            <p class="text-sm text-gray-500 flex items-center gap-2">
                                <span class="inline-flex w-2 h-2 rounded-full bg-purple-500"></span>
                                Menunggu Customer
                            </p>
                            <h3 class="text-3xl font-semibold text-gray-800 mt-2">{{ $overview['waiting_customer'] }}</h3>
                </div>
                        <div class="bg-white rounded-xl shadow p-5 border-l-4 border-emerald-500">
                            <p class="text-sm text-gray-500 flex items-center gap-2">
                                <span class="inline-flex w-2 h-2 rounded-full bg-emerald-500"></span>
                                Disetujui
                            </p>
                            <h3 class="text-3xl font-semibold text-gray-800 mt-2">{{ $overview['approved'] }}</h3>
            </div>
          </div>

                    <div class="bg-white rounded-xl shadow p-5">
                        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <label class="text-sm text-gray-500 mb-1 block">Cari Order / Customer</label>
                                <input type="text" name="search" value="{{ $search }}" placeholder="Contoh: SAL2412 / Rina" class="w-full border-gray-200 rounded-lg" />
                            </div>
                            <div>
                                <label class="text-sm text-gray-500 mb-1 block">Status</label>
                                <select name="status" class="w-full border-gray-200 rounded-lg">
                                    @foreach($statusOptions as $value => $label)
                                        <option value="{{ $value }}" @selected($statusFilter === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                </div>
                <div>
                                <label class="text-sm text-gray-500 mb-1 block">Rentang Waktu</label>
                                <select name="range" class="w-full border-gray-200 rounded-lg">
                                    <option value="today" @selected($rangeFilter === 'today')>Hari Ini</option>
                                    <option value="week" @selected($rangeFilter === 'week')>7 Hari</option>
                                    <option value="month" @selected($rangeFilter === 'month')>30 Hari</option>
                                    <option value="all" @selected($rangeFilter === 'all')>Semua Waktu</option>
                                </select>
                </div>
                            <div class="flex items-end gap-2">
                                <button class="w-full bg-blue-600 text-white rounded-lg py-2 hover:bg-blue-700">Terapkan</button>
                                <a href="{{ route('editor.dashboard') }}" class="w-full text-center bg-gray-100 text-gray-600 rounded-lg py-2 hover:bg-gray-200">Reset</a>
              </div>
                        </form>
            </div>

                    <section class="bg-white rounded-xl shadow p-5" id="activity">
                        <div class="flex items-center justify-between mb-4">
                <div>
                                <h2 class="text-lg font-semibold text-gray-800">Board Kanban</h2>
                                <p class="text-sm text-gray-500">Visualisasi status desain dalam format kanban board.</p>
                </div>
              </div>
                    </section>

                    <section class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4" id="board">
                        @php
                            $pipelineTitles = [
                                'pending' => 'Butuh Brief',
                                'in_progress' => 'Sedang Dikerjakan',
                                'waiting_customer' => 'Menunggu Customer',
                                'approved' => 'Siap Produksi',
                            ];
                        @endphp
                        @foreach($pipelineTitles as $status => $label)
                            <div class="bg-white rounded-xl shadow border border-gray-100 flex flex-col">
                                <div class="p-4 border-b flex items-center justify-between">
                                    <p class="font-semibold text-gray-700">{{ $label }}</p>
                                    <span class="text-sm text-gray-400">{{ ($pipeline[$status] ?? collect())->count() }}</span>
            </div>
                                <div class="p-4 space-y-3 overflow-y-auto max-h-[360px]">
                                    @forelse(($pipeline[$status] ?? collect()) as $item)
                                        <div class="border border-gray-100 rounded-lg p-3 shadow-sm">
                                            <div class="flex items-center justify-between">
                                                <p class="text-sm font-semibold text-gray-800">{{ $item->salesOrder->so_number }}</p>
                                                <span class="text-xs px-2 py-1 rounded-full {{ $statusColors[$item->design_status] ?? 'bg-gray-100 text-gray-600' }}">
                                                    {{ \App\Models\SalesOrderItem::designStatusOptions()[$item->design_status] ?? $item->design_status }}
                                                </span>
                </div>
                                            <p class="text-sm text-gray-600 mt-1">{{ $item->product_name }}</p>
                                            <p class="text-xs text-gray-400">
                                                {{ $item->salesOrder->customer->name ?? 'Customer Umum' }}
                                            </p>
                                            <div class="flex items-center gap-3 mt-3 text-xs text-gray-500">
                                                <span><i class="bi bi-calendar-event mr-1"></i>{{ optional($item->salesOrder->deadline)->format('d M') ?? '-' }}</span>
                                                <a href="{{ route('editor.sales.show', $item->sales_order_id) }}" class="inline-flex items-center gap-1 text-blue-600 hover:underline">
                                                    <i class="bi bi-box-arrow-up-right"></i>
                                                    Detail
                                                </a>
                                            </div>
              </div>
                                    @empty
                                        <p class="text-sm text-gray-400">Belum ada item.</p>
                                    @endforelse
            </div>
                </div>
                        @endforeach
                    </section>

                    <section class="bg-white rounded-xl shadow">
                        <div class="p-5 border-b flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
                <div>
                                <h2 class="text-lg font-semibold text-gray-800">Antrean Detail</h2>
                                <p class="text-sm text-gray-500">Daftar lengkap order DTF dan Jersey yang membutuhkan desain.</p>
                            </div>
                            <p class="text-sm text-gray-400">Menampilkan {{ $tasks->firstItem() }} - {{ $tasks->lastItem() }} dari {{ $tasks->total() }} data</p>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead class="bg-gray-50">
                                    <tr class="text-left text-gray-500">
                                        <th class="px-5 py-3 font-medium">Order</th>
                                        <th class="px-5 py-3 font-medium">Produk</th>
                                        <th class="px-5 py-3 font-medium">Status</th>
                                        <th class="px-5 py-3 font-medium">File</th>
                                        <th class="px-5 py-3 font-medium">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @forelse($tasks as $task)
                                        <tr class="align-top">
                                            <td class="px-5 py-4">
                                                <p class="font-semibold text-gray-800">{{ $task->salesOrder->so_number }}</p>
                                                <p class="text-xs text-gray-500">{{ $task->salesOrder->customer->name ?? 'Customer Umum' }}</p>
                                                <p class="text-xs text-gray-400 mt-1">
                                                    Deadline: {{ optional($task->salesOrder->deadline)->format('d M Y') ?? '-' }}
                                                </p>
                                            </td>
                                            <td class="px-5 py-4">
                                                <p class="font-semibold text-gray-800">{{ $task->product_name }}</p>
                                                <p class="text-xs text-gray-500">Qty {{ $task->qty }}</p>
                                                @if($task->design_notes)
                                                    <p class="text-xs text-gray-400 mt-1">Catatan: {{ Str::limit($task->design_notes, 60) }}</p>
                                                @endif
                                            </td>
                                            <td class="px-5 py-4">
                                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium {{ $statusColors[$task->design_status] ?? 'bg-gray-100 text-gray-600' }}">
                                                    {{ \App\Models\SalesOrderItem::designStatusOptions()[$task->design_status] ?? $task->design_status }}
                                                </span>
                                                <p class="text-xs text-gray-400 mt-2">Update: {{ $task->updated_at->diffForHumans() }}</p>
                                            </td>
                                            <td class="px-5 py-4 space-y-1 text-xs">
                                                @if($task->design_reference_path)
                                                    <a href="{{ Storage::url($task->design_reference_path) }}" target="_blank" class="flex items-center gap-1 text-blue-600 hover:underline">
                                                        <i class="bi bi-cloud-arrow-down"></i> Brief
                                                    </a>
                                                @endif
                                                @if($task->design_preview_path)
                                                    <a href="{{ Storage::url($task->design_preview_path) }}" target="_blank" class="flex items-center gap-1 text-emerald-600 hover:underline">
                                                        <i class="bi bi-eye"></i> Preview
                                                    </a>
                                                @endif
                                                @unless($task->design_reference_path || $task->design_preview_path)
                                                    <span class="text-gray-400">Belum ada file</span>
                                                @endunless
                                            </td>
                                            <td class="px-5 py-4">
                                                <details class="bg-gray-50 rounded-lg">
                                                    <summary class="cursor-pointer px-3 py-2 text-sm font-medium text-blue-600">Perbarui</summary>
                                                    <div class="px-3 py-3 space-y-3 text-xs text-gray-600">
                                                        <form action="{{ route('editor.design-tasks.update', $task) }}" method="POST" enctype="multipart/form-data" class="space-y-3">
                                                            @csrf
                                                            @method('PATCH')
                                                            <div class="space-y-1">
                                                                <label class="text-xs font-semibold text-gray-600">Status Desain</label>
                                                                <select name="design_status" class="w-full border-gray-200 rounded-lg">
                                                                    @foreach($designStatusOptions as $value => $label)
                                                                        <option value="{{ $value }}" @selected($task->design_status === $value)>{{ $label }}</option>
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                            <div class="space-y-1">
                                                                <label class="text-xs font-semibold">Brief / Catatan</label>
                                                                <textarea name="design_brief" rows="2" class="w-full border-gray-200 rounded-lg" placeholder="Ringkasan kebutuhan">{{ old('design_brief', $task->design_brief) }}</textarea>
                                                            </div>
                                                            <div class="space-y-1">
                                                                <label class="text-xs font-semibold">Catatan Editor</label>
                                                                <textarea name="design_notes" rows="2" class="w-full border-gray-200 rounded-lg" placeholder="Progress terakhir">{{ old('design_notes', $task->design_notes) }}</textarea>
                                                            </div>
                                                            <div class="space-y-1">
                                                                <label class="text-xs font-semibold">Feedback Customer</label>
                                                                <textarea name="design_feedback" rows="2" class="w-full border-gray-200 rounded-lg" placeholder="Catatan customer">{{ old('design_feedback', $task->design_feedback) }}</textarea>
                                                            </div>
                                                            <div class="space-y-1">
                                                                <label class="text-xs font-semibold">Upload Brief</label>
                                                                <input type="file" name="design_reference" class="w-full text-xs text-gray-500">
                                                            </div>
                                                            <div class="space-y-1">
                                                                <label class="text-xs font-semibold">Upload Preview</label>
                                                                <input type="file" name="design_preview" class="w-full text-xs text-gray-500">
                                                            </div>
                                                            <button class="w-full bg-blue-600 text-white rounded-lg py-1.5 text-sm hover:bg-blue-700">Simpan</button>
                                                        </form>
                                                    </div>
                                                </details>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="px-5 py-10 text-center text-gray-400">
                                                Tidak ada data sesuai filter.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                </div>
                        <div class="px-5 py-4">
                            {{ $tasks->links() }}
              </div>
                    </section>

                    <section class="bg-white rounded-xl shadow p-5">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h2 class="text-lg font-semibold text-gray-800">Aktivitas Terakhir</h2>
                                <p class="text-sm text-gray-500">Riwayat 5 update terbaru.</p>
            </div>
          </div>
                        <div class="space-y-4">
                            @forelse($recentActivities as $activity)
                                <div class="flex gap-3">
                                    <div class="w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center text-blue-500">
                                        <i class="bi bi-brush"></i>
              </div>
              <div>
                                        <p class="text-sm text-gray-800">
                                            {{ $activity->salesOrder->customer->name ?? 'Customer Umum' }} • {{ $activity->product_name }}
                                        </p>
                                        <p class="text-xs text-gray-500">
                                            Status: {{ \App\Models\SalesOrderItem::designStatusOptions()[$activity->design_status] ?? $activity->design_status }} — {{ $activity->updated_at->diffForHumans() }}
                </p>
              </div>
            </div>
                            @empty
                                <p class="text-sm text-gray-400">Belum ada aktivitas terbaru.</p>
                            @endforelse
          </div>
                    </section>
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