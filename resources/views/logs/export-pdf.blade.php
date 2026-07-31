<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Logs Export</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        th { background: #f5f5f5; font-weight: bold; }
        h1 { font-size: 18px; margin-bottom: 10px; }
    </style>
</head>
<body>
    <h1>Activity Logs Export</h1>
    <p>Generated: {{ now()->format('d M Y H:i:s') }}</p>
    <table>
        <thead>
            <tr>
                <th>Time</th>
                <th>User</th>
                <th>Module</th>
                <th>Action</th>
                <th>Description</th>
                <th>IP Address</th>
            </tr>
        </thead>
        <tbody>
            @foreach($logs as $log)
                @php $props = $log->properties ?? []; @endphp
                <tr>
                    <td>{{ $log->created_at->toDateTimeString() }}</td>
                    <td>{{ $log->causer?->name ?? 'System' }}</td>
                    <td>{{ $props['module'] ?? '-' }}</td>
                    <td>{{ $log->event ?? '-' }}</td>
                    <td>{{ $log->description }}</td>
                    <td>{{ $props['ip_address'] ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
