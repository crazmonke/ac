<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>차단 관리</title>

    <style>
        :root {
            --bg: #f4f8fb;
            --ink: #15243a;
            --muted: #62728a;
            --line: #d6e0ea;
            --card: #ffffff;
            --brand: #2e4fb8;
        }
        * { box-sizing: border-box; }
        body { margin: 0; background: var(--bg); color: var(--ink); font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; }
        .shell { max-width: 880px; margin: 0 auto; padding: 0px 16px 40px; }
        .page-title { margin: 0 0 14px; font-size: clamp(1.25rem, 2.6vw, 1.8rem); }
        .card { background: var(--card); border: 1px solid var(--line); border-radius: 16px; padding: 16px; margin-bottom: 12px; }
        .btn { border: 0; border-radius: 10px; padding: 8px 12px; font-weight: 700; cursor: pointer; text-decoration: none; display: inline-block; font: inherit; color: #22344f; background: #e7edf7; }
        .btn-danger { background: #fdecec; color: #9e1d1d; }
        .meta { color: var(--muted); font-size: 0.85rem; }
        .flash { background: #e8f6f1; border: 1px solid #bee6d9; color: #166b53; border-radius: 8px; padding: 10px; margin-bottom: 12px; }
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        th, td { border-bottom: 1px solid #edf1f7; padding: 10px 8px; text-align: left; vertical-align: middle; font-size: 0.88rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        th { background: #f8fbff; }
        .col-user { width: auto; }
        .col-date { width: 84px; color: var(--muted); font-size: 0.8rem; }
        .col-manage { width: 76px; text-align: right; }
        .col-manage .btn { padding: 6px 8px; font-size: 0.8rem; }
    </style>
</head>
<body>
@include('partials.site-nav', ['apartmentId' => $user->preferred_apartment_id])

<div class="shell">
    <div style="display:flex; align-items:center; gap:12px; margin-bottom:14px;">
        <a class="btn" href="/settings">← 계정 설정</a>
        <h1 class="page-title" style="margin:0;">차단 관리</h1>
    </div>

    @if(session('status'))
        <div class="flash">{{ session('status') }}</div>
    @endif

    <div class="card">
        <p class="meta" style="margin:0 0 10px;">차단한 사용자가 작성한 게시글/댓글은 목록에서 보이지 않으며, 차단을 해제하면 다시 노출됩니다.</p>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th class="col-user">사용자</th>
                        <th class="col-date">차단일</th>
                        <th class="col-manage">관리</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($blockedUsers as $blockedUser)
                        <tr>
                            <td class="col-user">{{ $blockedUser->blocked->name ?? '(탈퇴한 사용자)' }}</td>
                            <td class="col-date">{{ format_relative_time($blockedUser->created_at) }}</td>
                            <td class="col-manage">
                                <form method="post" action="/users/{{ $blockedUser->blocked_id }}/block" onsubmit="return confirm('차단을 해제할까요?');" style="margin:0;">
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="redirect" value="/blocked-users">
                                    <button class="btn btn-danger" type="submit">해제</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" style="text-align:center; color:#62728a;">차단한 사용자가 없습니다.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div style="margin-top:12px;">{{ $blockedUsers->links() }}</div>
    </div>
</div>
</body>
</html>
