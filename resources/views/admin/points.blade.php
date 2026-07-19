<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>포인트 회원 관리</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; margin: 0; background: #f5f7fb; color: #1a2a44; }
        .wrap { margin: 0; padding: 24px 28px; }
        .panel { background: #fff; border: 1px solid #dce4ef; border-radius: 12px; padding: 14px; margin-top: 12px; }
        .meta { color: #607086; font-size: 0.85rem; }
        .toolbar { display: flex; gap: 8px; align-items: center; justify-content: space-between; flex-wrap: wrap; }
        .toolbar form { display: flex; gap: 8px; align-items: center; }
        input { border: 1px solid #c8d5e7; border-radius: 8px; padding: 9px; font: inherit; }
        .btn { border: 0; border-radius: 10px; padding: 8px 10px; font-weight: 700; cursor: pointer; text-decoration: none; display: inline-block; font: inherit; }
        .btn-primary { background: #2e4fb8; color: #fff; }
        .btn-sm { padding: 5px 10px; font-size: 0.82rem; }
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; min-width: 700px; }
        th, td { border-bottom: 1px solid #edf1f7; padding: 10px 8px; text-align: left; vertical-align: middle; font-size: 0.88rem; }
        th { background: #f8fbff; }
        .badge { display: inline-flex; padding: 3px 8px; border-radius: 999px; font-size: 0.78rem; font-weight: 700; }
        .point-badge { background: #eef5ff; color: #1d3fa6; }
        a { color: #2e4fb8; text-decoration: none; }
        a:hover { text-decoration: underline; }
        .flash-ok { background: #e8f6f1; border-color: #bee6d9; color: #166b53; }
    </style>
</head>
<body>
<div class="wrap">
    @include('partials.admin-nav')

    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:4px;">
        <h1 style="margin:0;">포인트 회원 관리</h1>
        <a class="btn btn-primary" href="/admin/points/policy">포인트 정책 설정</a>
    </div>

    @if(session('status'))
        <div class="panel flash-ok">{{ session('status') }}</div>
    @endif

    <section class="panel toolbar">
        <div class="meta">회원별 포인트 잔액을 확인하고 개별 이력 및 지급/차감을 관리합니다.</div>
        <form method="get" action="/admin/points">
            <input type="text" name="q" value="{{ $q }}" placeholder="닉네임/이메일 검색">
            <input type="hidden" name="sort" value="{{ $sort }}">
            <input type="hidden" name="dir" value="{{ $dir }}">
            <button class="btn btn-primary" type="submit">검색</button>
        </form>
    </section>

    <section class="panel">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>닉네임</th>
                        <th>이메일</th>
                        <th>
                            <a class="sort-link" href="/admin/points?q={{ urlencode($q) }}&sort=point_balance&dir={{ $sort==='point_balance' && $dir==='desc' ? 'asc' : 'desc' }}">
                                포인트 잔액 {{ $sort==='point_balance' ? ($dir==='desc'?'▼':'▲') : '' }}
                            </a>
                        </th>
                        <th>관리</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr>
                            <td>{{ $user->id }}</td>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td><span class="badge point-badge">{{ number_format($user->point_balance) }} P</span></td>
                            <td>
                                <a class="btn btn-primary btn-sm" href="/admin/points/{{ $user->id }}">이력/지급</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" style="text-align:center; color:#607086;">검색 결과가 없습니다.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div style="margin-top:12px;">{{ $users->links() }}</div>
    </section>
</div>
</body>
</html>
