<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>신고 관리</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; margin: 0; background: #f5f7fb; color: #1a2a44; }
        .wrap { max-width: 1200px; margin: 0 auto; padding: 24px; }
        table { width: 100%; border-collapse: collapse; background: #fff; border: 1px solid #dce4ef; border-radius: 12px; overflow: hidden; }
        th, td { padding: 8px; border-bottom: 1px solid #edf1f7; text-align: left; vertical-align: top; }
        a { color: #0f6f67; text-decoration: none; font-weight: 600; }
        select, input { width: 100%; border: 1px solid #c9d5e8; border-radius: 8px; padding: 7px; }
        button { border: 0; border-radius: 8px; background: #0f6f67; color: #fff; padding: 8px 10px; font-weight: 700; cursor: pointer; }
        .flash { background: #e8f6f1; border: 1px solid #bee6d9; color: #166b53; border-radius: 8px; padding: 10px; margin-bottom: 12px; }
    </style>
</head>
<body>
<div class="wrap">
    @include('partials.admin-nav')
    <h1>신고 관리</h1>

    @if(session('status'))
        <div class="flash">{{ session('status') }}</div>
    @endif

    <table>
        <thead>
        <tr>
            <th>ID</th>
            <th>대상</th>
            <th>사유</th>
            <th>상태 변경</th>
            <th>관리 메모</th>
            <th>신고일시</th>
            <th>작업</th>
        </tr>
        </thead>
        <tbody>
        @forelse($reports as $report)
            <tr>
                <form method="post" action="/admin/reports/{{ $report->id }}">
                    @csrf
                    @method('PUT')
                    <td>{{ $report->id }}</td>
                    <td>{{ class_basename($report->reportable_type) }}#{{ $report->reportable_id }}</td>
                    <td>{{ $report->reason }}</td>
                    <td>
                        <select name="status" required>
                            <option value="pending" @selected($report->status === 'pending')>pending</option>
                            <option value="reviewed" @selected($report->status === 'reviewed')>reviewed</option>
                            <option value="dismissed" @selected($report->status === 'dismissed')>dismissed</option>
                            <option value="hidden" @selected($report->status === 'hidden')>hidden</option>
                        </select>
                    </td>
                    <td>
                        <input name="admin_note" value="{{ $report->admin_note }}">
                    </td>
                    <td>{{ $report->created_at }}</td>
                    <td><button type="submit">저장</button></td>
                </form>
            </tr>
        @empty
            <tr><td colspan="7">신고 데이터가 없습니다.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
</body>
</html>
