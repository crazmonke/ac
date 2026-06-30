<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>게시판 관리</title>
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
    <h1>게시판 관리</h1>
    <p><a href="/admin">대시보드로</a></p>
    <table>
        <thead>
        <tr>
            <th>ID</th>
            <th>이름</th>
            <th>슬러그</th>
            <th>타입</th>
            <th>읽기/쓰기/댓글</th>
            <th>활성</th>
        </tr>
        </thead>
        <tbody>
        @forelse($boards as $board)
            <tr>
                <td>{{ $board->id }}</td>
                <td>{{ $board->name }}</td>
                <td>{{ $board->slug }}</td>
                <td>{{ $board->board_type }}</td>
                <td>{{ $board->read_role }} / {{ $board->write_role }} / {{ $board->comment_role }}</td>
                <td>{{ $board->is_active ? 'Y' : 'N' }}</td>
            </tr>
        @empty
            <tr><td colspan="6">게시판 데이터가 없습니다.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
</body>
</html>
