<?php

namespace App\Actions\Laporan;

use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;

class AuditLogReport
{
    /** @return array{headings:array<string,string>,rows:array<int,array<string,mixed>>} */
    public function handle(array $params): array
    {
        $headings = [
            'waktu' => 'Waktu',
            'causer' => 'Causer',
            'subject' => 'Subject',
            'event' => 'Event',
            'properties' => 'Properties',
        ];

        $search = $params['search'] ?? null;

        $rows = Activity::query()
            ->with(['causer', 'subject'])
            ->when($params['from'] ?? null, fn ($query, $from) => $query->whereDate('created_at', '>=', $from))
            ->when($params['to'] ?? null, fn ($query, $to) => $query->whereDate('created_at', '<=', $to))
            ->when($params['event'] ?? null, fn ($query, $event) => $query->where('event', $event))
            ->when($search, fn ($query, $term) => $query->where(function ($q) use ($term) {
                $q->where('description', 'like', '%'.$term.'%')
                    ->orWhere('subject_type', 'like', '%'.$term.'%');
            }))
            ->orderByDesc('id')
            ->limit(500)
            ->get()
            ->map(function (Activity $activity) {
                $changes = $activity->attribute_changes ?? $activity->properties;

                return [
                    'waktu' => $activity->created_at?->format('d-m-Y H:i'),
                    'causer' => $activity->causer?->name ?? 'sistem',
                    'subject' => class_basename($activity->subject_type ?? 'Subject').' #'.$activity->subject_id,
                    'event' => $activity->event ?? $activity->description,
                    'properties' => Str::limit(
                        json_encode($changes ?? [], JSON_UNESCAPED_UNICODE) ?: '',
                        180,
                    ),
                ];
            })
            ->all();

        return ['headings' => $headings, 'rows' => $rows];
    }
}
