<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $user->name }} 포인트 이력</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; margin: 0; background: #f5f7fb; color: #1a2a44; }
        .wrap { margin: 0; padding: 24px 28px; }
        .panel { background: #fff; border: 1px solid #dce4ef; border-radius: 12px; padding: 14px; margin-top: 12px; }
        .meta { color: #607086; font-size: 0.85rem; }
        .btn { border: 0; border-radius: 10px; padding: 8px 12px; font-weight: 700; cursor: pointer; font: inherit; text-decoration: none; display: inline-block; }
        .btn-primary { background: #2e4fb8; color: #fff; }
        .btn-danger { background: #b42318; color: #fff; }
        .btn-back { background: #e7edf7; color: #22344f; }
        input { border: 1px solid #c8d5e7; border-radius: 8px; padding: 9px; font: inherit; }
        .form-row { display: flex; gap: 8px; align-items: flex-end; flex-wrap: wrap; }
        label { display: grid; gap: 4px; font-size: 0.88rem; font-weight: 600; color: #42536a; }
        .badge { display: inline-flex; padding: 3px 8px; border-radius: 999px; font-size: 0.78rem; font-weight: 700; }
        .earn { background: #eef5ff; color: #1d3fa6; }
        .deduct { background: #fdecec; color: #9e1d1d; }
        .expire { background: #f9f0ff; color: #6b21a8; }
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; min-width: 640px; }
        th, td { border-bottom: 1px solid #edf1f7; padding: 10px 8px; text-align: left; vertical-align: middle; font-size: 0.88rem; }
        th { background: #f8fbff; }
        .balance-box { background: #eef5ff; border: 1px solid #c7d9fb; border-radius: 12px; padding: 16px 20px; display: inline-flex; align-items: center; gap: 16px; }
        .balance-num { font-size: 1.8rem; font-weight: 800; color: #1d3fa6; }
        a { color: #2e4fb8; }
        .flash-ok { background: #e8f6f1; border-color: #bee6d9; color: #166b53; }
        .flash-err { background: #fdecec; border-color: #f4c8c8; color: #9e1d1d; }
        .filter-bar { display: flex; gap: 6px; flex-wrap: wrap; align-items: center; }
        .filter-link { padding: 5px 10px; border-radius: 8px; background: #edf1f7; color: #22344f; font-size: 0.85rem; text-decoration: none; font-weight: 600; }
        .filter-link.active { background: #2e4fb8; color: #fff; }
    </style>
</head>
<body>
<div class="wrap">
    @include('partials.admin-nav')

    <div style="display:flex; align-items:center; gap:12px; margin-bottom:12px;">
        <a class="btn btn-back" href="/admin/points">← 목록</a>
        <h1 style="margin:0;">{{ $user->name }} 포인트 이력</h1>
    </div>

    @if(session('status'))
        <div class="panel flash-ok">{{ session('status') }}</div>
    @endif
    @if($errors->any())
        <div class="panel flash-err">{{ $errors->first() }}</div>
    @endif

    {{-- 잔액 요약 --}}
    <div class="panel" style="display:flex; gap:20px; flex-wrap:wrap; align-items:center; justify-content:space-between;">
        <div>
            <div class="meta">{{ $user->email }}</div>
            <div class="balance-box" style="margin-top:8px;">
                <div>
                    <div class="meta" style="font-size:0.78rem;">현재 포인트 잔액</div>
                    <div class="balance-num">{{ number_format($user->point_balance) }} P</div>
                </div>
            </div>
        </div>

        {{-- 지급 / 차감 폼 --}}
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <form method="post" action="/admin/points/{{ $user->id }}/grant" style="display:flex; gap:6px; align-items:flex-end; flex-wrap:wrap;">
                @csrf
                <label>지급 금액
                    <input type="number" name="amount" min="1" max="999999" placeholder="P" style="width:90px;" required>
                </label>
                <label>메모
                    <input type="text" name="note" placeholder="사유" style="width:140px;" maxlength="200">
                </label>
                <button class="btn btn-primary" type="submit">포인트 지급</button>
            </form>

            <form method="post" action="/admin/points/{{ $user->id }}/deduct" style="display:flex; gap:6px; align-items:flex-end; flex-wrap:wrap;">
                @csrf
                <label>차감 금액
                    <input type="number" name="amount" min="1" max="999999" placeholder="P" style="width:90px;" required>
                </label>
                <label>메모
                    <input type="text" name="note" placeholder="사유" style="width:140px;" maxlength="200">
                </label>
                <button class="btn btn-danger" type="submit">포인트 차감</button>
            </form>
        </div>
    </div>

    {{-- 필터 --}}
    <section class="panel" style="padding:10px 14px;">
        <div class="filter-bar">
            <span class="meta">기간:</span>
            @foreach(['all' => '전체', '7d' => '최근 7일', '30d' => '최근 30일', '3m' => '3개월', '6m' => '6개월', '1y' => '1년'] as $key => $label)
                <a class="filter-link {{ $period === $key ? 'active' : '' }}"
                   href="/admin/points/{{ $user->id }}?period={{ $key }}&type={{ $type }}">{{ $label }}</a>
            @endforeach
            &nbsp;
            <span class="meta">유형:</span>
            @foreach(['all' => '전체', 'earn' => '적립', 'deduct' => '차감', 'expire' => '소멸'] as $key => $label)
                <a class="filter-link {{ $type === $key ? 'active' : '' }}"
                   href="/admin/points/{{ $user->id }}?period={{ $period }}&type={{ $key }}">{{ $label }}</a>
            @endforeach
        </div>
    </section>

    {{-- 거래 내역 --}}
    <section class="panel">
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
                        <th>소멸 예정일</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $tx)
                        <tr>
                            <td>{{ $tx->created_at->format('Y-m-d H:i') }}</td>
                            <td><span class="badge {{ $tx->type }}">{{ $tx->typeLabel() }}</span></td>
                            <td>{{ $tx->sourceLabel() }}</td>
                            <td style="font-weight:700; color:{{ $tx->amount >= 0 ? '#1d3fa6' : '#9e1d1d' }};">
                                {{ $tx->amount >= 0 ? '+' : '' }}{{ number_format($tx->amount) }} P
                            </td>
                            <td>{{ number_format($tx->balance_after) }} P</td>
                            <td class="meta">{{ $tx->note ?? '-' }}</td>
                            <td class="meta">{{ $tx->expires_at ? $tx->expires_at->format('Y-m-d') : '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" style="text-align:center; color:#607086;">내역이 없습니다.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div style="margin-top:12px;">{{ $transactions->links() }}</div>
    </section>
</div>
</body>
</html>
