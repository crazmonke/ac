<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>포인트 이력</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=SUIT:wght@400;500;700;800&display=swap" rel="stylesheet">
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
        body { margin: 0; background: var(--bg); color: var(--ink); font-family: 'SUIT', sans-serif; }
        .shell { max-width: 880px; margin: 0 auto; padding: 18px 16px 40px; }
        .page-title { margin: 0 0 14px; font-size: clamp(1.25rem, 2.6vw, 1.8rem); }
        .card { background: var(--card); border: 1px solid var(--line); border-radius: 16px; padding: 16px; margin-bottom: 12px; }
        .btn { border: 0; border-radius: 10px; padding: 8px 12px; font-weight: 700; cursor: pointer; text-decoration: none; display: inline-block; font: inherit; color: #22344f; background: #e7edf7; }
        .meta { color: var(--muted); font-size: 0.85rem; }
        .balance-box { background: #eef5ff; border: 1px solid #c7d9fb; border-radius: 12px; padding: 16px 20px; display: inline-flex; align-items: center; gap: 16px; margin-bottom: 12px; }
        .balance-num { font-size: 1.8rem; font-weight: 800; color: #1d3fa6; }
        .filter-bar { display: flex; gap: 6px; flex-wrap: wrap; align-items: center; margin-bottom: 14px; }
        .filter-link { padding: 5px 10px; border-radius: 8px; background: #edf1f7; color: #22344f; font-size: 0.85rem; text-decoration: none; font-weight: 600; }
        .filter-link.active { background: #2e4fb8; color: #fff; }
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; min-width: 560px; }
        th, td { border-bottom: 1px solid #edf1f7; padding: 10px 8px; text-align: left; vertical-align: middle; font-size: 0.88rem; }
        th { background: #f8fbff; }
        .badge { display: inline-flex; padding: 3px 8px; border-radius: 999px; font-size: 0.78rem; font-weight: 700; }
        .earn { background: #eef5ff; color: #1d3fa6; }
        .deduct { background: #fdecec; color: #9e1d1d; }
        .expire { background: #f9f0ff; color: #6b21a8; }
    </style>
</head>
<body>
@include('partials.site-nav', ['apartmentId' => $user->preferred_apartment_id])

<div class="shell">
    <div style="display:flex; align-items:center; gap:12px; margin-bottom:14px;">
        <a class="btn" href="/settings">← 계정 설정</a>
        <h1 class="page-title" style="margin:0;">포인트 이력</h1>
    </div>

    <div class="balance-box">
        <div>
            <div class="meta" style="font-size:0.78rem;">현재 포인트 잔액</div>
            <div class="balance-num">{{ number_format($user->point_balance) }} P</div>
        </div>
    </div>

    <div class="card">
        <div class="filter-bar">
            <span class="meta">기간:</span>
            @foreach(['all' => '전체', '7d' => '최근 7일', '30d' => '최근 30일', '3m' => '3개월', '6m' => '6개월', '1y' => '1년'] as $key => $label)
                <a class="filter-link {{ $period === $key ? 'active' : '' }}"
                   href="/points?period={{ $key }}&type={{ $type }}">{{ $label }}</a>
            @endforeach
        </div>
        <div class="filter-bar">
            <span class="meta">유형:</span>
            @foreach(['all' => '전체', 'earn' => '적립', 'deduct' => '차감', 'expire' => '소멸'] as $key => $label)
                <a class="filter-link {{ $type === $key ? 'active' : '' }}"
                   href="/points?period={{ $period }}&type={{ $key }}">{{ $label }}</a>
            @endforeach
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>일시</th>
                        <th>유형</th>
                        <th>출처</th>
                        <th>금액</th>
                        <th>잔액</th>
                        <th>메모</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $tx)
                        <tr>
                            <td>{{ $tx->created_at->format('Y-m-d H:i') }}</td>
                            <td><span class="badge {{ $tx->type }}">{{ $tx->typeLabel() }}</span></td>
                            <td class="meta">{{ $tx->sourceLabel() }}</td>
                            <td style="font-weight:700; color:{{ $tx->amount >= 0 ? '#1d3fa6' : '#9e1d1d' }};">
                                {{ $tx->amount >= 0 ? '+' : '' }}{{ number_format($tx->amount) }} P
                            </td>
                            <td>{{ number_format($tx->balance_after) }} P</td>
                            <td class="meta">{{ $tx->note ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" style="text-align:center; color:#62728a;">내역이 없습니다.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div style="margin-top:12px;">{{ $transactions->links() }}</div>
    </div>
</div>
</body>
</html>
