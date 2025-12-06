<?php

namespace App\Http\Controllers\Editor;

use App\Http\Controllers\Controller;
use App\Models\SalesOrderItem;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $statusFilter = $request->get('status', 'all');
        $rangeFilter = $request->get('range', 'week');
        $search = trim($request->get('search', ''));

        $tasksQuery = SalesOrderItem::with(['salesOrder.customer'])
            ->designQueue()
            ->latest('updated_at');

        if ($statusFilter !== 'all') {
            $tasksQuery->where('design_status', $statusFilter);
        }

        if ($search !== '') {
            $tasksQuery->where(function (Builder $query) use ($search) {
                $query->where('product_name', 'like', "%{$search}%")
                    ->orWhereHas('salesOrder', function (Builder $orderQuery) use ($search) {
                        $orderQuery->where('so_number', 'like', "%{$search}%")
                            ->orWhereHas('customer', function (Builder $customerQuery) use ($search) {
                                $customerQuery->where('name', 'like', "%{$search}%");
                            });
                    });
            });
        }

        $rangeLabel = $this->applyRangeFilter($tasksQuery, $rangeFilter);

        $tasks = $tasksQuery->paginate(10)->withQueryString();

        $statusSummary = SalesOrderItem::designQueue()
            ->selectRaw('design_status, COUNT(*) as total')
            ->groupBy('design_status')
            ->pluck('total', 'design_status');

        $overview = [
            'pending' => $statusSummary['pending'] ?? 0,
            'in_progress' => $statusSummary['in_progress'] ?? 0,
            'waiting_customer' => $statusSummary['waiting_customer'] ?? 0,
            'approved' => $statusSummary['approved'] ?? 0,
        ];

        $pipelineStatuses = ['pending', 'in_progress', 'waiting_customer', 'approved'];
        $pipeline = SalesOrderItem::with(['salesOrder.customer'])
            ->designQueue()
            ->whereIn('design_status', $pipelineStatuses)
            ->orderBy('updated_at', 'desc')
            ->get()
            ->groupBy('design_status')
            ->map(fn($group) => $group->take(4));

        $recentActivities = SalesOrderItem::with(['salesOrder.customer'])
            ->designQueue()
            ->orderBy('updated_at', 'desc')
            ->limit(5)
            ->get();

        $statusOptions = ['all' => 'Semua Status'] + SalesOrderItem::designStatusOptions();

        return view('editor.dashboard', [
            'tasks' => $tasks,
            'overview' => $overview,
            'pipeline' => $pipeline,
            'recentActivities' => $recentActivities,
            'statusOptions' => $statusOptions,
            'designStatusOptions' => SalesOrderItem::designStatusOptions(),
            'statusFilter' => $statusFilter,
            'rangeFilter' => $rangeFilter,
            'rangeLabel' => $rangeLabel,
            'search' => $search,
        ]);
    }

    private function applyRangeFilter(Builder $query, string $range): string
    {
        $range = in_array($range, ['today', 'week', 'month', 'all'], true) ? $range : 'week';

        return match ($range) {
            'today' => tap('Hari ini', fn() => $query->whereDate('created_at', Carbon::today())),
            'week' => tap('7 Hari Terakhir', fn() => $query->whereBetween('created_at', [
                Carbon::today()->subDays(6),
                Carbon::today()->endOfDay(),
            ])),
            'month' => tap('30 Hari Terakhir', fn() => $query->whereBetween('created_at', [
                Carbon::today()->subDays(29),
                Carbon::today()->endOfDay(),
            ])),
            default => 'Semua Waktu',
        };
    }
}

