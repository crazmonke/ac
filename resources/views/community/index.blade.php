<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $apartmentName }} 커뮤니티</title>
    <style>
        body { margin: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; background: #f4f8fb; color: #17263d; }
        .wrap { max-width: 1080px; margin: 0 auto; padding: 24px; }
        .top { display: flex; justify-content: space-between; align-items: center; gap: 10px; flex-wrap: wrap; }
        .meta { color: #5b6d82; font-size: 0.92rem; }
        .scope-tabs { margin-top: 12px; display: flex; gap: 8px; flex-wrap: wrap; }
        .scope-tab { border: 1px solid #d5dfec; border-radius: 999px; padding: 7px 12px; text-decoration: none; color: #20344f; background: #fff; font-weight: 700; }
        .scope-tab.active { background: #0f6f67; border-color: #0f6f67; color: #fff; }
        .panel { margin-top: 14px; background: #fff; border: 1px solid #d5dfec; border-radius: 12px; padding: 14px; }
        .post-list { list-style: none; margin: 0; padding: 0; }
        .post-item { border-top: 1px solid #edf2f8; padding: 12px 0; }
        .post-item:first-child { border-top: 0; padding-top: 0; }
        .post-title { color: #17263d; text-decoration: none; font-weight: 700; }
        .chips { margin-top: 6px; display: flex; gap: 6px; flex-wrap: wrap; }
        .chip { font-size: 0.78rem; border-radius: 999px; padding: 3px 8px; background: #ecf2ff; color: #294f8f; }
        .chip.guest-open { background: #e9f8ef; color: #18603a; }
        .chip.locked { background: #fff4e8; color: #8d4a1c; }
        .empty-box { border: 1px solid #ffd7b5; background: #fff4e9; color: #7f4310; border-radius: 10px; padding: 12px; }
        .empty-box a { color: #0f6f67; font-weight: 700; text-decoration: none; }
    </style>
</head>
<body>
<div class="wrap">
    @include('partials.site-nav', ['apartmentId' => $apartmentId])

    <div class="top">
        <h1 style="margin:0;">커뮤니티</h1>
        <div class="meta">
            @if($isResident)
                입주민 모드: {{ $regionLabel }} · {{ $apartmentName }} 중심
            @else
                비회원/일반회원 모드: 전국 게시글 노출
            @endif
        </div>
    </div>

    <div class="scope-tabs">
        <a class="scope-tab {{ $scope === 'all' ? 'active' : '' }}" href="/community?scope=all&apartment_id={{ $apartmentId }}">전국</a>
        <a class="scope-tab {{ $scope === 'region' ? 'active' : '' }}" href="/community?scope=region&apartment_id={{ $apartmentId }}">동네</a>
        <a class="scope-tab {{ $scope === 'apartment' ? 'active' : '' }}" href="/community?scope=apartment&apartment_id={{ $apartmentId }}">아파트</a>
    </div>

    <section class="panel">
        <ul class="post-list">
            @forelse($posts as $post)
                <li class="post-item">
                    <a class="post-title {{ !auth()->check() && !$post['can_read'] ? 'requires-signup' : '' }}"
                       href="{{ $post['url'] }}"
                       @if(!auth()->check() && !$post['can_read']) data-signup-url="{{ $post['url'] }}" @endif>
                        {{ $post['title'] }}
                    </a>
                    <div class="chips">
                        <span class="chip">{{ $post['board_name'] }}</span>
                        <span class="chip">{{ $post['sigungu'] ?: $post['sido'] }} · {{ $post['apartment_name'] }}</span>
                        @if($post['is_guest_visible'])
                            <span class="chip guest-open">비회원 공개</span>
                        @elseif(!$post['can_read'])
                            <span class="chip locked">상세는 회원/입주민 전용</span>
                        @endif
                    </div>
                    <div class="meta" style="margin-top:6px;">{{ $post['created_at'] }} · 조회 {{ $post['view_count'] }} · 댓글 {{ $post['comment_count'] }}</div>
                </li>
            @empty
                @if($requiresSignupForScope)
                    <li class="empty-box">
                        동네/아파트 범위 게시글은 회원가입 후 단지 인증을 완료하면 볼 수 있습니다.
                        <br>
                        <a href="/register?redirect={{ urlencode('/community?scope='.$scope.'&apartment_id='.$apartmentId) }}">회원가입 및 인증 진행하기</a>
                    </li>
                @else
                    <li class="meta">노출할 게시글이 없습니다.</li>
                @endif
            @endforelse
        </ul>

        <div style="margin-top:10px;" class="meta">{{ $posts->links() }}</div>
    </section>
</div>

<script>
document.querySelectorAll('.requires-signup').forEach((link) => {
    link.addEventListener('click', (event) => {
        event.preventDefault();
        const shouldMove = window.confirm('이 게시글 본문은 회원 전용입니다. 회원가입 페이지로 이동할까요?');
        if (shouldMove) {
            window.location.href = link.dataset.signupUrl || '/register';
        }
    });
});
</script>
</body>
</html>
