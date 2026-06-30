<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>신고 관리</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; margin: 0; background: #f5f7fb; color: #1a2a44; }
        .wrap { max-width: 1100px; margin: 0 auto; padding: 24px; }
        table { width: 100%; border-collapse: collapse; background: #fff; border: 1px solid #dce4ef; border-radius: 12px; overflow: hidden; }
        th, td { padding: 10px; border-bottom: 1px solid #edf1f7; text-align: left; }
        a { color: #0f6f67; text-decoration: none; font-weight: 600; }
    </style>
</head>
<body>
<div class="wrap">
    <h1>신고 관리</h1>
    <p><a href="/admin">대시보드로</a></p>
    <table>
        <thead>
        <tr>
            <th>ID</th>
            <th>대상</th>
            <th>사유</th>
            <th>상태</th>
            <th>관리 메모</th>
            <th>신고일시</th>
        </tr>
        </thead>
        <tbody>
        @forelse($reports as $report)
            <tr>
                <td>{{ $report->id }}</td>
                <td>{{ class_basename($report->reportable_type) }}#{{ $report->reportable_id }}</td>
                <td>{{ $report->reason }}</td>
                <td>{{ $report->status }}</td>
                <td>{{ $report->admin_note }}</td>
                <td>{{ $report->created_at }}</td>
            </tr>
        @empty
            <tr><td colspan="6">신고 데이터가 없습니다.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
</body>
</html>
