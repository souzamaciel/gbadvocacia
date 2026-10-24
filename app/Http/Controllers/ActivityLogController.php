<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    /**
     * Activity of the whole office, newest first, filtered by period, person, area and text.
     * Records older than the retention period are removed when the screen opens.
     */
    public function index(Request $request): View
    {
        ActivityLog::query()->where('created_at', '<', now()->subMonths(ActivityLog::RETENTION_MONTHS))->delete();

        $filters = [
            'de' => $this->date($request->query('de')),
            'ate' => $this->date($request->query('ate')),
            'usuario' => $request->integer('usuario') ?: null,
            'area' => array_key_exists((string) $request->query('area'), ActivityLogger::EVENTS) ? (string) $request->query('area') : null,
            'busca' => trim((string) $request->query('busca')),
        ];

        $logs = ActivityLog::query()
            ->when($filters['de'], fn ($query, Carbon $date) => $query->where('created_at', '>=', $date->startOfDay()))
            ->when($filters['ate'], fn ($query, Carbon $date) => $query->where('created_at', '<=', $date->copy()->endOfDay()))
            ->when($filters['usuario'], fn ($query, int $id) => $query->where('user_id', $id))
            ->when($filters['area'], fn ($query, string $area) => $query->whereIn('event', array_keys(ActivityLogger::EVENTS[$area])))
            ->when($filters['busca'] !== '', fn ($query) => $query->where(function ($query) use ($filters): void {
                $term = '%'.$filters['busca'].'%';
                $query->where('description', 'like', $term)
                    ->orWhere('subject_label', 'like', $term)
                    ->orWhere('user_name', 'like', $term)
                    ->orWhere('ip', 'like', $term)
                    ->orWhere('location', 'like', $term);
            }))
            ->latest('created_at')
            ->latest('id')
            ->paginate(50)
            ->withQueryString();

        $users = User::query()->orderBy('name')->get(['id', 'name']);

        return view('admin.logs.index', compact('logs', 'users', 'filters'));
    }

    private function date(mixed $value): ?Carbon
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        return Carbon::createFromFormat('Y-m-d', $value)->startOfDay();
    }
}
