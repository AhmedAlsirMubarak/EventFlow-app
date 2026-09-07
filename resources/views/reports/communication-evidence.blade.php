<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Communication Evidence Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1e293b; }
        h1 { font-size: 18px; margin: 0 0 4px; }
        h2 { font-size: 13px; margin: 0 0 6px; border-bottom: 1px solid #cbd5e1; padding-bottom: 4px; }
        .meta { color: #64748b; font-size: 10px; margin-bottom: 18px; }
        .doc-block { margin-bottom: 22px; }
        .share-block { margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        th, td { border: 1px solid #e2e8f0; padding: 4px 6px; text-align: left; vertical-align: top; }
        th { background: #f1f5f9; font-weight: bold; }
        .summary-table th { width: 110px; }
        .badge { display: inline-block; padding: 1px 6px; border-radius: 8px; background: #e2e8f0; font-size: 9px; }
        .statement { background: #f8fafc; border: 1px solid #e2e8f0; padding: 6px 8px; margin: 0 0 10px; }
        .statement strong { display: block; margin-bottom: 2px; }
        .empty { color: #94a3b8; font-style: italic; }
    </style>
</head>
<body>
    <h1>Communication Evidence Report</h1>
    <div class="meta">
        {{ $event->name }} ({{ $event->code }})
        @if($organization)
            &mdash; scoped to {{ $organization->name }}
        @endif
        <br>Generated {{ $generatedAt->format('M j, Y g:i A') }}
    </div>

    @forelse($documents as $document)
        <div class="doc-block">
            <h2>{{ $document->title }} <span class="badge">{{ $document->category->value }}</span></h2>

            @foreach($document->shares as $share)
                <div class="share-block">
                    <table class="summary-table">
                        <tr>
                            <th>Recipient</th>
                            <td>{{ $share->organization?->name ?? $share->contact?->full_name }}</td>
                        </tr>
                        <tr>
                            <th>Shared By</th>
                            <td>{{ $share->sharer?->name ?? '—' }} on {{ $share->created_at->format('M j, Y g:i A') }}</td>
                        </tr>
                    </table>

                    <table>
                        <thead>
                            <tr>
                                <th>Event</th>
                                <th>By</th>
                                <th>When</th>
                                <th>IP Address</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($share->activities as $activity)
                                <tr>
                                    <td>{{ $activity->type->value }}</td>
                                    <td>{{ $activity->user?->name ?? $activity->contact?->full_name ?? '—' }}</td>
                                    <td>{{ $activity->created_at->format('M j, Y g:i A') }}</td>
                                    <td>{{ $activity->ip_address ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="empty">No activity recorded.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    @foreach($share->acknowledgments as $acknowledgment)
                        <div class="statement">
                            <strong>Acknowledgment on {{ $acknowledgment->acknowledged_at->format('M j, Y g:i A') }}</strong>
                            {{ $acknowledgment->name }}@if($acknowledgment->organization_snapshot) ({{ $acknowledgment->organization_snapshot }})@endif
                            &mdash; "{{ $acknowledgment->statement }}"
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>
    @empty
        <p class="empty">
            No documents have been shared externally for this event @if($organization) with {{ $organization->name }} @endif.
        </p>
    @endforelse
</body>
</html>
