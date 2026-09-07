<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    /**
     * Log aktivitas "siapa mengubah apa, kapan" — khusus admin
     * (docs/feature/log_aktivitas.md; keputusan user pasca-UAT 03).
     */
    public function index(Request $request): View
    {
        $filters = $request->only(['q', 'user', 'action', 'model', 'from', 'to']);

        $logs = ActivityLog::with('user')
            ->when($filters['q'] ?? null, fn ($q) => $q->where(fn ($w) => $w
                ->where('description', 'like', '%'.$filters['q'].'%')
                ->orWhere('model_label', 'like', '%'.$filters['q'].'%')))
            ->when($filters['user'] ?? null, fn ($q) => $q->where('user_id', $filters['user']))
            ->when($filters['action'] ?? null, fn ($q) => $q->where('action', $filters['action']))
            ->when($filters['model'] ?? null, fn ($q) => $q->where('model_type', $filters['model']))
            ->when(($filters['from'] ?? null) && ($filters['to'] ?? null), fn ($q) => $q
                ->whereDate('created_at', '>=', $filters['from'])
                ->whereDate('created_at', '<=', $filters['to']))
            ->latest('created_at')
            ->paginate(25)
            ->withQueryString();

        return view('admin.activity-logs.index', [
            'logs' => $logs,
            'users' => User::orderBy('name')->get(),
            'modelTypes' => ActivityLog::query()->select('model_type')->distinct()->whereNotNull('model_type')->orderBy('model_type')->pluck('model_type'),
            'filters' => $filters,
        ]);
    }
}
