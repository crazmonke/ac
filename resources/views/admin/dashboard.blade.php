<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>관리자 대시보드</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; margin: 0; background: #f5f7fb; color: #1a2a44; }
        .wrap { max-width: 1000px; margin: 0 auto; padding: 24px; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 12px; margin-bottom: 16px; }
        .card { background: #fff; border: 1px solid #dce4ef; border-radius: 12px; padding: 16px; }
        a { color: #0f6f67; text-decoration: none; font-weight: 600; }
        table { width: 100%; border-collapse: collapse; background: #fff; border: 1px solid #dce4ef; border-radius: 12px; overflow: hidden; }
        th, td { padding: 10px; border-bottom: 1px solid #edf1f7; text-align: left; }
    </style>
</head>
<body>
<div class="wrap">
    @include('partials.admin-nav')
    <h1>관리자 대시보드</h1>

    <section class="grid">
        <article class="card">
            <strong>전체 게시판</strong>
            <div>{{ $boardsCount }}</div>
        </article>
        <article class="card">
            <strong>대기 신고</strong>
            <div>{{ $pendingReportsCount }}</div>
        </article>
    </section>

    <table>
        <thead>
        <tr>
            <th>ID</th>
            <th>대상</th>
            <th>사유</th>
            <th>상태</th>
            <th>일시</th>
        </tr>
        </thead>
        <tbody>
        @forelse($latestReports as $report)
            <tr>
                <td>{{ $report->id }}</td>
                <td>{{ class_basename($report->reportable_type) }}#{{ $report->reportable_id }}</td>
                <td>{{ $report->reason }}</td>
                <td>{{ $report->status }}</td>
                <td>{{ $report->created_at }}</td>
            </tr>
        @empty
            <tr><td colspan="5">신고 데이터가 없습니다.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
</body>
</html>
