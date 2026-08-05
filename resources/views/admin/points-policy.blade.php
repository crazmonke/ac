<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>포인트 정책 설정</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; margin: 0; background: #f5f7fb; color: #1a2a44; }
        .wrap { margin: 0; padding: 24px 28px; max-width: 680px; }
        .panel { background: #fff; border: 1px solid #dce4ef; border-radius: 12px; padding: 22px; margin-top: 12px; }
        h2 { margin: 0 0 6px; font-size: 1.05rem; }
        .desc { color: #607086; font-size: 0.85rem; margin: 0 0 16px; line-height: 1.5; }
        label { display: grid; gap: 5px; font-size: 0.9rem; font-weight: 600; color: #42536a; margin-bottom: 14px; }
        input[type=number] { border: 1px solid #c8d5e7; border-radius: 8px; padding: 9px; font: inherit; width: 140px; }
        .unit { font-size: 0.85rem; color: #607086; font-weight: 400; }
        .btn { border: 0; border-radius: 10px; padding: 10px 18px; font-weight: 700; cursor: pointer; font: inherit; }
        .btn-primary { background: #2e4fb8; color: #fff; }
        .btn-back { background: #e7edf7; color: #22344f; text-decoration: none; display: inline-block; }
        .flash-ok { background: #e8f6f1; border-color: #bee6d9; color: #166b53; }
        a { color: #2e4fb8; }
    </style>
</head>
<body>
<div class="wrap">
    @include('partials.admin-nav')

    <div style="display:flex; align-items:center; gap:12px; margin-bottom:4px;">
        <a class="btn btn-back" href="/admin/points">← 회원 목록</a>
        <h1 style="margin:0;">포인트 정책 설정</h1>
    </div>

    @if(session('status'))
        <div class="panel flash-ok">{{ session('status') }}</div>
    @endif

    @if($errors->any())
        <div class="panel" style="background:#fdecec; border-color:#f4c8c8; color:#9e1d1d;">{{ $errors->first() }}</div>
    @endif

    <section class="panel">
        <h2>적립 정책</h2>
        <p class="desc">포인트 적립 기준을 설정합니다. 변경 즉시 이후 활동부터 적용됩니다.</p>
        <form method="post" action="/admin/points/policy">
            @csrf
            @method('put')

            <label>
                게시글 작성 적립 <span class="unit">(1개 작성 시)</span>
                <div style="display:flex; align-items:center; gap:6px;">
                    <input type="number" name="post_points" value="{{ old('post_points', $policy->post_points) }}" min="0" max="9999" required>
                    <span class="unit">P</span>
                </div>
            </label>

            <label>
                댓글 작성 적립 <span class="unit">(게시글당 1회 한정)</span>
                <div style="display:flex; align-items:center; gap:6px;">
                    <input type="number" name="comment_points" value="{{ old('comment_points', $policy->comment_points) }}" min="0" max="9999" required>
                    <span class="unit">P</span>
                </div>
            </label>

            <label>
                회원 1일 최대 적립 <span class="unit">(적립 활동 합산 상한)</span>
                <div style="display:flex; align-items:center; gap:6px;">
                    <input type="number" name="daily_max_points" value="{{ old('daily_max_points', $policy->daily_max_points) }}" min="1" max="99999" required>
                    <span class="unit">P / 일</span>
                </div>
            </label>

            <hr style="border:none; border-top:1px solid #e5eaf2; margin:16px 0;">
            <h2>사용 정책</h2>

            <label>
                최소 사용 가능 포인트 <span class="unit">(이 이상부터 사용 가능)</span>
                <div style="display:flex; align-items:center; gap:6px;">
                    <input type="number" name="min_spend_points" value="{{ old('min_spend_points', $policy->min_spend_points) }}" min="0" max="999999" required>
                    <span class="unit">P</span>
                </div>
            </label>

            <label>
                닉네임 변경 차감 <span class="unit">(프로필 잠금 회원의 닉네임 1회 변경 시. 0 = 무료)</span>
                <div style="display:flex; align-items:center; gap:6px;">
                    <input type="number" name="nickname_change_points" value="{{ old('nickname_change_points', $policy->nickname_change_points) }}" min="0" max="999999" required>
                    <span class="unit">P</span>
                </div>
            </label>

            <label>
                쪽지 일일 무료 발송 <span class="unit">(매일 초기화, 누적 없음)</span>
                <div style="display:flex; align-items:center; gap:6px;">
                    <input type="number" name="daily_free_messages" value="{{ old('daily_free_messages', $policy->daily_free_messages) }}" min="0" max="999" required>
                    <span class="unit">건 / 일</span>
                </div>
            </label>

            <label>
                쪽지 추가 발송 차감 <span class="unit">(무료 소진 후 1건 발송 시. 0 = 무제한 무료)</span>
                <div style="display:flex; align-items:center; gap:6px;">
                    <input type="number" name="message_send_points" value="{{ old('message_send_points', $policy->message_send_points) }}" min="0" max="999999" required>
                    <span class="unit">P / 건</span>
                </div>
            </label>

            <hr style="border:none; border-top:1px solid #e5eaf2; margin:16px 0;">
            <h2>소멸 정책</h2>

            <label>
                포인트 소멸 기간 <span class="unit">(적립 후 N개월 경과 시 소멸. 비워두면 소멸 없음)</span>
                <div style="display:flex; align-items:center; gap:6px;">
                    <input type="number" name="expiry_months" value="{{ old('expiry_months', $policy->expiry_months) }}" min="1" max="120" placeholder="없음">
                    <span class="unit">개월 (미입력 = 소멸 없음)</span>
                </div>
            </label>

            <div style="margin-top:8px;">
                <button class="btn btn-primary" type="submit">정책 저장</button>
            </div>
        </form>
    </section>
</div>
</body>
</html>
