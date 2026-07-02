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
        .scope-tabs-topic { margin-top: 8px; display: flex; align-items: center; gap: 8px; flex-wrap: nowrap; }
        .scope-tabs-topic .scope-tab { font-size: 0.92rem; padding: 6px 11px; line-height: 1.15; white-space: nowrap; }
        .topic-scroll { display: flex; gap: 8px; overflow-x: auto; overflow-y: hidden; flex: 1 1 auto; min-width: 0; scrollbar-width: none; -ms-overflow-style: none; touch-action: pan-x; cursor: grab; }
        .topic-scroll::-webkit-scrollbar { display: none; }
        .topic-scroll.dragging { cursor: grabbing; user-select: none; }
        .topic-scroll .scope-tab { flex: 0 0 auto; }
        .panel { margin-top: 14px; background: #fff; border: 1px solid #d5dfec; border-radius: 12px; padding: 14px; }
        .post-list { list-style: none; margin: 0; padding: 0; }
        .post-item { border-top: 1px solid #edf2f8; padding: 12px 0; }
        .post-item:first-child { border-top: 0; padding-top: 0; }
        .split-section { margin-top: 10px; border: 1px solid #d7e2f1; border-radius: 12px; overflow: hidden; background: #fbfdff; }
        .split-head { padding: 10px 12px; background: linear-gradient(180deg, #eef4ff, #f7faff); border-bottom: 1px solid #d7e2f1; }
        .split-title { margin: 0; font-size: 0.98rem; font-weight: 900; color: #173662; letter-spacing: -0.01em; }
        .split-sub { margin-top: 2px; font-size: 0.8rem; color: #5c6f8a; }
        .split-body { padding: 10px 12px 8px; }
        .split-section .post-list .post-item:first-child { padding-top: 8px; }
        .split-divider { margin: 14px 0 10px; border-top: 2px dashed #c7d6ea; }
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
            @if(auth()->check() && $isVerified)
                인증회원 모드: 전체/동네/아파트 상세 열람 + 글쓰기 가능
            @elseif(auth()->check())
                비인증회원 모드: 전체/동네 상세 열람 가능, 아파트는 제목만 열람
            @else
                비회원 모드: 전체 게시물 제목만 열람
            @endif
        </div>
    </div>

    <div class="scope-tabs">
        <a class="scope-tab {{ $scope === 'all' ? 'active' : '' }}" href="/community?scope=all&apartment_id={{ $apartmentId }}">전체</a>
        <a class="scope-tab {{ $scope === 'region' ? 'active' : '' }}" href="/community?scope=region&apartment_id={{ $apartmentId }}">동네</a>
        <a class="scope-tab {{ $scope === 'apartment' ? 'active' : '' }}" href="/community?scope=apartment&apartment_id={{ $apartmentId }}">아파트</a>
    </div>

    <div class="scope-tabs-topic">
        <a class="scope-tab {{ $topic === '' ? 'active' : '' }}" href="/community?scope={{ $scope }}&apartment_id={{ $apartmentId }}">전체</a>
        <div class="topic-scroll" data-topic-scroll>
            @foreach($topicFacets as $facet)
                <a class="scope-tab {{ $topic === $facet->slug ? 'active' : '' }}"
                   href="/community?scope={{ $scope }}&topic={{ $facet->slug }}&apartment_id={{ $apartmentId }}">#{{ $facet->name }}</a>
            @endforeach
        </div>
    </div>

    <section class="panel" style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;">
        <div class="meta" style="font-size:0.95rem;">
            작성할 게시판을 고르고 태그를 지정해 글을 등록할 수 있습니다.
        </div>
        @if($canCreatePost)
            <a class="scope-tab active" href="/community/compose?apartment_id={{ $apartmentId }}">글쓰기</a>
        @elseif(auth()->check())
            <div class="empty-box" style="margin:0; padding:8px 10px;">글쓰기는 인증회원만 가능합니다. 단지 인증을 완료해 주세요.</div>
        @else
            <a class="scope-tab" href="/register?redirect={{ urlencode('/community?scope='.$scope.'&apartment_id='.$apartmentId) }}">회원가입 후 글쓰기</a>
        @endif
    </section>

    <section class="panel">
        @php
            $renderPostItem = function (array $post) {
                $titleClass = !auth()->check() && !$post['can_read'] ? 'requires-signup' : '';
                $signupAttr = !auth()->check() && !$post['can_read'] ? 'data-signup-url="'.e($post['url']).'"' : '';

                return '<li class="post-item">'
                    .'<a class="post-title '.$titleClass.'" href="'.e($post['url']).'" '.$signupAttr.'>'.e($post['title']).'</a>'
                    .'<div class="chips">'
                    .'<span class="chip">'.e($post['board_name']).'</span>'
                    .'<span class="chip">'.($post['audience_scope'] === 'region' ? '동네 전용' : ($post['audience_scope'] === 'apartment' ? '아파트 전용' : '전체')).'</span>'
                    .(!empty($post['topic_name']) ? '<span class="chip">#'.e($post['topic_name']).'</span>' : '')
                    .'<span class="chip">'.e(($post['sigungu'] ?: $post['sido']).' · '.$post['apartment_name']).'</span>'
                    .($post['is_guest_visible'] ? '<span class="chip guest-open">비회원 공개</span>' : (!empty($post['access_label']) ? '<span class="chip locked">'.e($post['access_label']).'</span>' : ''))
                    .'</div>'
                    .'<div class="meta" style="margin-top:6px;">'.e((string) $post['created_at']).' · 조회 '.e((string) $post['view_count']).' · 댓글 '.e((string) $post['comment_count']).'</div>'
                    .'</li>';
            };
        @endphp

        @if($shouldSplitApartmentFeed)
            <section class="split-section">
                <div class="split-head">
                    <h3 class="split-title">{{ $preferredApartmentName !== '' ? $preferredApartmentName : '내 아파트' }} 게시글</h3>
                    <div class="split-sub">인증한 내 단지의 게시글이 먼저 노출됩니다.</div>
                </div>
                <div class="split-body">
                    <ul class="post-list">
                        @forelse($ownApartmentPosts as $post)
                            {!! $renderPostItem($post) !!}
                        @empty
                            <li class="meta" style="padding:10px 0;">현재 페이지에 내 아파트 게시글이 없습니다.</li>
                        @endforelse
                    </ul>
                </div>
            </section>

            <div class="split-divider"></div>
            <section class="split-section">
                <div class="split-head">
                    <h3 class="split-title">동네/다른 아파트 게시글</h3>
                    <div class="split-sub">같은 동네에 가까운 아파트 글을 우선 노출합니다.</div>
                </div>
                <div class="split-body">
                    <ul class="post-list">
                        @forelse($otherApartmentPosts as $post)
                            {!! $renderPostItem($post) !!}
                        @empty
                            <li class="meta" style="padding:10px 0;">현재 페이지에 다른 아파트 게시글이 없습니다.</li>
                        @endforelse
                    </ul>
                </div>
            </section>
        @else
            <ul class="post-list">
                @forelse($posts as $post)
                    {!! $renderPostItem($post) !!}
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
        @endif

        @include('partials.pagination', ['paginator' => $posts])
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

const topicScroll = document.querySelector('[data-topic-scroll]');
if (topicScroll) {
    let isDragging = false;
    let startX = 0;
    let startScrollLeft = 0;
    let didDrag = false;
    const dragThreshold = 6;

    topicScroll.addEventListener('pointerdown', (event) => {
        if (event.pointerType === 'mouse' && event.button !== 0) {
            return;
        }

        isDragging = true;
        didDrag = false;
        startX = event.clientX;
        startScrollLeft = topicScroll.scrollLeft;
        topicScroll.classList.add('dragging');

        if (typeof topicScroll.setPointerCapture === 'function') {
            topicScroll.setPointerCapture(event.pointerId);
        }
    });

    topicScroll.addEventListener('pointermove', (event) => {
        if (!isDragging) {
            return;
        }

        const deltaX = event.clientX - startX;
        if (Math.abs(deltaX) > dragThreshold) {
            didDrag = true;
        }

        topicScroll.scrollLeft = startScrollLeft - deltaX;
    });

    const finishDrag = (event) => {
        if (!isDragging) {
            return;
        }

        isDragging = false;
        topicScroll.classList.remove('dragging');

        if (typeof topicScroll.releasePointerCapture === 'function') {
            try {
                topicScroll.releasePointerCapture(event.pointerId);
            } catch (error) {
                // Ignore invalid release attempts.
            }
        }
    };

    topicScroll.addEventListener('pointerup', finishDrag);
    topicScroll.addEventListener('pointercancel', finishDrag);
    topicScroll.addEventListener('pointerleave', (event) => {
        if (event.pointerType === 'mouse') {
            finishDrag(event);
        }
    });

    topicScroll.querySelectorAll('a').forEach((link) => {
        link.addEventListener('click', (event) => {
            if (!didDrag) {
                return;
            }

            event.preventDefault();
            didDrag = false;
        });
    });
}
</script>
</body>
</html>
