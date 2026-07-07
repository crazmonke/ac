<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $apartmentName }} 커뮤니티</title>
    <style>
        body { margin: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; background: #f6f7f9; color: #171717; }
        .wrap { max-width: 760px; margin: 0 auto; padding: 14px 12px 110px; }
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
        .desktop-write-cta { display: inline-flex; }
        .mobile-bottom-nav { display: none; }
        .has-mobile-bottom-nav { }
        .panel { margin-top: 12px; background: #fff; border: 1px solid #e3e6eb; border-radius: 16px; padding: 12px; }
        .post-list { list-style: none; margin: 0; padding: 0; }
        .post-item { border-top: 1px solid #e3e6eb; padding: 12px 0; }
        .post-item:first-child { border-top: 0; padding-top: 2px; }
        .post-row { display: flex; align-items: flex-start; gap: 10px; }
        .author-avatar {
            flex: 0 0 36px;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: linear-gradient(145deg, #182230, #3b4a62);
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.85rem;
            font-weight: 800;
            letter-spacing: 0.02em;
        }
        .post-main { flex: 1 1 auto; min-width: 0; }
        .post-head { display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-bottom: 4px; }
        .author-line { display: inline-flex; align-items: center; gap: 6px; min-width: 0; }
        .author-line strong { font-size: 0.93rem; max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .author-line .meta { font-size: 0.8rem; }
        .split-section { margin-top: 10px; border: 1px solid #d7e2f1; border-radius: 12px; overflow: hidden; background: #fbfdff; }
        .split-head { padding: 10px 12px; background: linear-gradient(180deg, #eef4ff, #f7faff); border-bottom: 1px solid #d7e2f1; }
        .split-title { margin: 0; font-size: 0.98rem; font-weight: 900; color: #173662; letter-spacing: -0.01em; }
        .split-sub { margin-top: 2px; font-size: 0.8rem; color: #5c6f8a; }
        .split-body { padding: 10px 12px 8px; }
        .split-section .post-list .post-item:first-child { padding-top: 8px; }
        .split-divider { margin: 14px 0 10px; border-top: 2px dashed #c7d6ea; }
        .post-title { color: #121212; text-decoration: none; font-weight: 700; line-height: 1.42; display: -webkit-box; -webkit-box-orient: vertical; -webkit-line-clamp: 3; overflow: hidden; }
        .body-preview { margin-top: 6px; color: #222b37; font-size: 0.92rem; line-height: 1.5; }
        .media-strip {
            margin-top: 8px;
            display: flex;
            gap: 8px;
            overflow-x: auto;
            scroll-snap-type: x mandatory;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: thin;
        }
        .media-card {
            flex: 0 0 min(340px, 78vw);
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid #d9e1ec;
            background: #edf2f8;
            scroll-snap-align: start;
        }
        .media-card img,
        .media-card video {
            width: 100%;
            height: 220px;
            object-fit: cover;
            display: block;
            background: #dce5f0;
        }
        .post-thumb {
            flex: 0 0 94px;
            width: 94px;
            aspect-ratio: 4 / 3;
            border-radius: 10px;
            overflow: hidden;
            border: 1px solid #d7e2f1;
            background: #eef3f9;
        }
        .post-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .chips { margin-top: 7px; display: flex; gap: 5px; flex-wrap: wrap; }
        .chip { font-size: 0.74rem; border-radius: 999px; padding: 3px 8px; background: #eef1f6; color: #344054; }
        .chip.guest-open { background: #e9f8ef; color: #18603a; }
        .chip.locked { background: #fff4e8; color: #8d4a1c; }
        .post-actions { margin-top: 8px; display: flex; gap: 10px; }
        .post-actions .meta { font-size: 0.79rem; }
        .icon-action {
            border: 0;
            background: transparent;
            color: #334155;
            cursor: pointer;
            padding: 0;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 0.82rem;
            text-decoration: none;
        }
        .icon-action.hearted { color: #d01e39; }
        .empty-box { border: 1px solid #ffd7b5; background: #fff4e9; color: #7f4310; border-radius: 10px; padding: 12px; }
        .empty-box a { color: #0f6f67; font-weight: 700; text-decoration: none; }

        @media (max-width: 768px) {
            .wrap { padding-bottom: calc(96px + env(safe-area-inset-bottom)); }
            .post-row { gap: 10px; }
            .post-thumb { flex-basis: 86px; width: 86px; border-radius: 9px; }
            .desktop-write-cta { display: none; }
            .mobile-bottom-nav {
                position: fixed;
                left: 0;
                right: 0;
                bottom: 0;
                z-index: 120;
                display: block;
                padding: 8px 12px calc(8px + env(safe-area-inset-bottom));
                background: linear-gradient(180deg, #eef4ff, #f7faff);
                border-top: 1px solid rgba(220, 243, 246, 0.42);
                backdrop-filter: blur(8px);
            }
            .mobile-bottom-nav-inner { max-width: 1080px; margin: 0 auto; display: flex; align-items: center; justify-content: flex-end; gap: 10px; min-height: 58px; }
            .mobile-nav-item { text-decoration: none; color: #02451b; display: inline-flex; flex-direction: column; align-items: center; justify-content: center; min-width: 64px; padding: 2px 6px; font-weight: 700; }
            .mobile-nav-item-icon {
                width: 32px;
                height: 32px;
                border-radius: 10px;
                background: linear-gradient(145deg, #d9f7ee 0%, #aeead8 100%);
                color: #0f5f61;
                border: 1px solid rgba(217, 247, 238, 0.75);
                box-shadow: 0 6px 14px rgba(6, 45, 51, 0.22);
                display: inline-flex;
                align-items: center;
                justify-content: center;
                font-size: 1.35rem;
                line-height: 1;
                margin-bottom: 3px;
            }
            .mobile-nav-item-label { font-size: 0.78rem; line-height: 1.1; letter-spacing: -0.01em; }
        }
    </style>
</head>
<body class="{{ $canCreatePost ? 'has-mobile-bottom-nav' : '' }}">
<div class="wrap">
    @include('partials.site-nav', ['apartmentId' => $apartmentId])

    <div class="top">
        <h1 style="margin:0;">커뮤니티</h1>
        <div class="meta">
            @if(auth()->check() && $isVerified)
                인증회원 모드: 전국(동네)/동네(내 지역)/공동주택(내 단지) 열람 + 글쓰기 가능
            @elseif(auth()->check())
                비인증회원 모드: 전국(동네)/동네(내 지역) 상세 열람 가능, 공동주택은 인증 후 열람 가능
            @else
                비회원 모드: 전국 동네 공개 게시글 열람
            @endif
        </div>
    </div>

    <div class="scope-tabs">
        <a class="scope-tab {{ $scope === 'all' ? 'active' : '' }}" href="/community?scope=all&apartment_id={{ $apartmentId }}">전국</a>
        <a class="scope-tab {{ $scope === 'region' ? 'active' : '' }}" href="/community?scope=region&apartment_id={{ $apartmentId }}">동네</a>
        <a class="scope-tab {{ $scope === 'apartment' ? 'active' : '' }}" href="/community?scope=apartment&apartment_id={{ $apartmentId }}">공동주택</a>
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
            <a class="scope-tab active desktop-write-cta" href="/community/compose?apartment_id={{ $apartmentId }}">글쓰기</a>
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
                $isLiked = (bool) ($post['liked_by_me'] ?? false);
                $likeCount = (int) ($post['like_count'] ?? 0);
                $commentCount = (int) ($post['comment_count'] ?? 0);
                $bodyPreview = trim((string) ($post['body_preview'] ?? ''));
                $mediaItems = (array) ($post['media_items'] ?? []);
                $likeMethod = $isLiked ? 'delete' : 'post';
                $csrf = csrf_token();

                return '<li class="post-item">'
                    .'<div class="post-row">'
                    .'<span class="author-avatar">'.e($post['author_initial']).'</span>'
                    .'<div class="post-main">'
                    .'<div class="post-head">'
                    .'<div class="author-line"><strong>'.e($post['author_name']).'</strong><span class="meta">· '.e($post['created_label']).'</span></div>'
                    .'</div>'
                    .'<a class="post-title '.$titleClass.'" href="'.e($post['url']).'" '.$signupAttr.'>'.e($post['title']).'</a>'
                    .($bodyPreview !== '' ? '<div class="body-preview">'.e($bodyPreview).'</div>' : '')
                    .(!empty($mediaItems) ? '<div class="media-strip">'.collect($mediaItems)->map(function ($item) use ($signupAttr) {
                        $url = e((string) ($item['url'] ?? ''));
                        $name = e((string) ($item['name'] ?? 'media'));
                        $type = (string) ($item['type'] ?? 'image');

                        if ($url === '') {
                            return '';
                        }

                        if ($type === 'video') {
                            return '<a class="media-card" href="'.$url.'" '.$signupAttr.'><video src="'.$url.'" controls preload="metadata"></video></a>';
                        }

                        return '<a class="media-card" href="'.$url.'" '.$signupAttr.'><img src="'.$url.'" alt="'.$name.'"></a>';
                    })->implode('').'</div>' : '')
                    .'<div class="chips">'
                    .'<span class="chip">'.e($post['board_name']).'</span>'
                    .'<span class="chip">'.($post['audience_scope'] === 'region' ? '동네 전용' : ($post['audience_scope'] === 'apartment' ? '공동주택 전용' : '전체')).'</span>'
                    .(!empty($post['topic_name']) ? '<span class="chip">#'.e($post['topic_name']).'</span>' : '')
                    .'<span class="chip">'.e(($post['sigungu'] ?: $post['sido']).' · '.$post['apartment_name']).'</span>'
                    .($post['is_guest_visible'] ? '<span class="chip guest-open">비회원 공개</span>' : (!empty($post['access_label']) ? '<span class="chip locked">'.e($post['access_label']).'</span>' : ''))
                    .'</div>'
                    .'<div class="post-actions">'
                    .(auth()->check()
                        ? '<form method="post" action="/community/posts/'.e((string) $post['id']).'/likes">'
                            .'<input type="hidden" name="_token" value="'.$csrf.'">'
                            .($likeMethod === 'delete' ? '<input type="hidden" name="_method" value="delete">' : '')
                            .'<button class="icon-action '.($isLiked ? 'hearted' : '').'" type="submit" aria-label="좋아요">'.($isLiked ? '❤' : '♡').' '.e((string) $likeCount).'</button>'
                        .'</form>'
                        : '<a class="icon-action" href="/login">♡ '.e((string) $likeCount).'</a>')
                    .'<a class="icon-action" href="'.e($post['url']).'#comments" aria-label="댓글">💬 '.e((string) $commentCount).'</a>'
                    .'<span class="meta">조회 '.e((string) $post['view_count']).'</span>'
                    .'</div>'
                    .'</div>'
                    .'</div>'
                    .'</li>';
            };
        @endphp

        @if($shouldSplitApartmentFeed)
            <section class="split-section">
                <div class="split-head">
                    <h3 class="split-title">{{ $preferredApartmentName !== '' ? $preferredApartmentName : '내 공동주택' }} 게시글</h3>
                    <div class="split-sub">인증한 내 단지의 게시글이 먼저 노출됩니다.</div>
                </div>
                <div class="split-body">
                    <ul class="post-list">
                        @forelse($ownApartmentPosts as $post)
                            {!! $renderPostItem($post) !!}
                        @empty
                            <li class="meta" style="padding:10px 0;">현재 페이지에 내 공동주택 게시글이 없습니다.</li>
                        @endforelse
                    </ul>
                </div>
            </section>

            <div class="split-divider"></div>
            <section class="split-section">
                <div class="split-head">
                    <h3 class="split-title">동네/다른 공동주택 게시글</h3>
                    <div class="split-sub">같은 동네에 가까운 공동주택 글을 우선 노출합니다.</div>
                </div>
                <div class="split-body">
                    <ul class="post-list">
                        @forelse($otherApartmentPosts as $post)
                            {!! $renderPostItem($post) !!}
                        @empty
                            <li class="meta" style="padding:10px 0;">현재 페이지에 다른 공동주택 게시글이 없습니다.</li>
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
                            동네/공동주택 범위 게시글은 회원가입 후 단지 인증을 완료하면 볼 수 있습니다.
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

@if($canCreatePost)
    <nav class="mobile-bottom-nav" aria-label="모바일 하단 메뉴">
        <div class="mobile-bottom-nav-inner">
            <a class="mobile-nav-item" href="/community/compose?apartment_id={{ $apartmentId }}" aria-label="글쓰기">
                <span class="mobile-nav-item-icon">+</span>
                <span class="mobile-nav-item-label">글쓰기</span>
            </a>
        </div>
    </nav>
@endif

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
    const enablePointerDrag = window.matchMedia('(hover: none), (pointer: coarse)').matches;

    if (enablePointerDrag) {
        topicScroll.addEventListener('pointerdown', (event) => {
            if (event.pointerType === 'mouse') {
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
}
</script>
</body>
</html>
