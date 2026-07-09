<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $apartmentName }} 커뮤니티</title>
    <style>
        body { margin: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; background: #f6f7f9; color: #171717; }
        .wrap { max-width: 760px; margin: 0 auto; padding: 14px 12px 24px; }
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
        .mobile-write-fab {
            display: none;
            position: fixed;
            right: 16px;
            bottom: calc(16px + env(safe-area-inset-bottom));
            z-index: 140;
            width: 56px;
            height: 56px;
            border-radius: 999px;
            background: #ffffff;
            color: #0e1726;
            box-shadow: 0 10px 20px rgba(15, 23, 38, 0.2);
            text-decoration: none;
            align-items: center;
            justify-content: center;
            border: 0;
        }
        .mobile-write-fab-icon {
            width: 38px;
            height: 38px;
            display: block;
            stroke: currentColor;
            fill: none;
            stroke-width: 2.1;
            stroke-linecap: round;
            stroke-linejoin: round;
        }
        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }
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
        .body-link { display: block; text-decoration: none; color: inherit; }
        .poll-preview {
            margin-top: 8px;
            border: 1px solid #d9e4f3;
            border-radius: 12px;
            background: #f7fbff;
            padding: 9px 10px;
        }
        .poll-preview-title {
            margin: 0;
            font-size: 0.88rem;
            font-weight: 800;
            color: #1f3f72;
        }
        .poll-preview-options {
            margin-top: 7px;
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }
        .poll-preview-option {
            font-size: 0.76rem;
            border-radius: 999px;
            padding: 3px 8px;
            background: #e8f0fd;
            color: #244171;
        }
        .poll-preview-meta {
            margin-top: 6px;
            font-size: 0.76rem;
            color: #516681;
            font-weight: 700;
        }
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
        .media-lightbox {
            position: fixed;
            inset: 0;
            z-index: 2000;
            background: rgba(10, 14, 20, 0.96);
            display: none;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
        }
        .media-lightbox.open { display: flex; }
        .media-lightbox-close {
            position: absolute;
            top: 16px;
            right: 16px;
            z-index: 5;
            width: 40px;
            height: 40px;
            border-radius: 999px;
            border: 1px solid rgba(255, 255, 255, 0.35);
            background: rgba(20, 24, 32, 0.78);
            color: #fff;
            font-size: 1.4rem;
            line-height: 1;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            touch-action: manipulation;
        }
        .media-lightbox-content {
            max-width: min(980px, 96vw);
            max-height: calc(100vh - 84px);
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            z-index: 1;
            overflow: hidden;
            touch-action: pan-y;
            overscroll-behavior: contain;
        }
        .media-lightbox-track {
            width: 100%;
            height: 100%;
            display: flex;
            transform: translate3d(-100%, 0, 0);
            will-change: transform;
        }
        .media-lightbox-frame {
            flex: 0 0 100%;
            width: 100%;
            max-width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 1px;
        }
        .media-lightbox-frame img,
        .media-lightbox-frame video {
            max-width: 100%;
            max-height: calc(100vh - 84px);
            width: auto;
            height: auto;
            border-radius: 10px;
            background: #0f141d;
        }
        .media-lightbox-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            z-index: 5;
            width: 44px;
            height: 44px;
            border-radius: 999px;
            border: 1px solid rgba(255, 255, 255, 0.35);
            background: rgba(20, 24, 32, 0.78);
            color: #fff;
            font-size: 1.5rem;
            line-height: 1;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            touch-action: manipulation;
        }
        .media-lightbox-nav.prev { left: 16px; }
        .media-lightbox-nav.next { right: 16px; }
        .media-lightbox-nav.hidden { display: none; }
        .media-lightbox-counter {
            position: absolute;
            bottom: 16px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 4;
            color: rgba(255, 255, 255, 0.9);
            font-size: 0.85rem;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 999px;
            border: 1px solid rgba(255, 255, 255, 0.28);
            background: rgba(20, 24, 32, 0.64);
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
        .icon-action svg {
            width: 17px;
            height: 17px;
            stroke: currentColor;
            fill: none;
            stroke-width: 1.9;
            stroke-linecap: round;
            stroke-linejoin: round;
        }
        .icon-action.hearted svg {
            fill: currentColor;
            stroke: currentColor;
        }
        .icon-count { font-weight: 600; }
        .feed-loader {
            margin-top: 10px;
            padding: 10px;
            border-radius: 10px;
            border: 1px dashed #d4ddea;
            color: #5b6d82;
            text-align: center;
            font-size: 0.86rem;
            background: #f8fbff;
        }
        .feed-loader.done {
            border-style: solid;
            background: #f3f7fc;
        }
        .empty-box { border: 1px solid #ffd7b5; background: #fff4e9; color: #7f4310; border-radius: 10px; padding: 12px; }
        .empty-box a { color: #0f6f67; font-weight: 700; text-decoration: none; }

        @media (max-width: 768px) {
            .wrap { padding-bottom: calc(88px + env(safe-area-inset-bottom)); }
            .post-row { gap: 10px; }
            .post-thumb { flex-basis: 86px; width: 86px; border-radius: 9px; }
            .post-actions {
                margin-top: 10px;
                gap: 18px;
                align-items: center;
            }
            .icon-action {
                gap: 6px;
                font-size: 0.95rem;
                padding: 4px 2px;
                min-height: 34px;
            }
            .icon-action svg {
                width: 22px;
                height: 22px;
                stroke-width: 2.1;
            }
            .icon-count {
                font-size: 0.92rem;
            }
            .desktop-write-cta { display: none; }
            .mobile-write-fab {
                display: inline-flex;
            }
        }
    </style>
</head>
<body>
<div class="wrap">
    @include('partials.site-nav', ['apartmentId' => $apartmentId])

    <div class="top">
        <h1 style="margin:0;">커뮤니티</h1>
        <div class="meta">
            <!--
            @if(auth()->check() && $isVerified)
                인증회원 모드: 전국(동네)/동네(내 지역)/공동주택(내 단지) 열람 + 글쓰기 가능
            @elseif(auth()->check())
                비인증회원 모드: 전국(동네)/동네(내 지역) 상세 열람 가능, 공동주택은 인증 후 열람 가능
            @else
                비회원 모드: 전국 동네 공개 게시글 열람
            @endif
            -->
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
            <a class="scope-tab active desktop-write-cta" href="/community/compose?apartment_id={{ $apartmentId }}&scope={{ $scope }}@if($topic !== '')&topic={{ urlencode($topic) }}@endif">글쓰기</a>
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
                $isPoll = (bool) ($post['is_poll'] ?? false);
                $pollQuestion = trim((string) ($post['poll_question'] ?? ''));
                $pollOptions = (array) ($post['poll_options_preview'] ?? []);
                $pollTotalVotes = (int) ($post['poll_total_votes'] ?? 0);
                $likeMethod = $isLiked ? 'delete' : 'post';
                $csrf = csrf_token();
                $heartIcon = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.84 4.61a4.98 4.98 0 0 0-7.05 0L12 6.4l-1.79-1.79a4.98 4.98 0 0 0-7.05 7.05L12 20.5l8.84-8.84a4.98 4.98 0 0 0 0-7.05Z"/></svg>';
                $commentIcon = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4Z"/></svg>';
                $viewIcon = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12Z"/><circle cx="12" cy="12" r="3"/></svg>';

                return '<li class="post-item">'
                    .'<div class="post-row">'
                    .'<span class="author-avatar">'.e($post['author_initial']).'</span>'
                    .'<div class="post-main">'
                    .'<div class="post-head">'
                    .'<div class="author-line"><strong>'.e($post['author_name']).'</strong><span class="meta">· '.e($post['created_label']).'</span></div>'
                    .'</div>'
                    .'<a class="post-title '.$titleClass.'" href="'.e($post['url']).'" '.$signupAttr.'>'.e($post['title']).'</a>'
                    .($bodyPreview !== '' ? '<a class="body-link" href="'.e($post['url']).'" '.$signupAttr.'><div class="body-preview">'.e($bodyPreview).'</div></a>' : '')
                    .($isPoll ? '<a class="body-link" href="'.e($post['url']).'" '.$signupAttr.'><div class="poll-preview"><p class="poll-preview-title">📊 '.e($pollQuestion !== '' ? $pollQuestion : '투표 게시글').'</p>'
                        .(!empty($pollOptions) ? '<div class="poll-preview-options">'.collect($pollOptions)->map(fn ($opt) => '<span class="poll-preview-option">'.e((string) $opt).'</span>')->implode('').'</div>' : '')
                        .'<div class="poll-preview-meta">총 '.e((string) $pollTotalVotes).'표 · 자세히 보려면 눌러주세요</div></div></a>' : '')
                    .(!empty($mediaItems) ? '<div class="media-strip">'.collect($mediaItems)->map(function ($item) use ($signupAttr) {
                        $url = e((string) ($item['url'] ?? ''));
                        $name = e((string) ($item['name'] ?? 'media'));
                        $type = (string) ($item['type'] ?? 'image');

                        if ($url === '') {
                            return '';
                        }

                        if ($type === 'video') {
                            return '<a class="media-card" href="'.$url.'" '.$signupAttr.'><video src="'.$url.'" controls preload="metadata" data-media-trigger data-media-type="video" data-media-src="'.$url.'"></video></a>';
                        }

                        return '<a class="media-card" href="'.$url.'" '.$signupAttr.'><img src="'.$url.'" alt="'.$name.'" data-media-trigger data-media-type="image" data-media-src="'.$url.'"></a>';
                    })->implode('').'</div>' : '')
                    .'<div class="chips">'
                    .'<span class="chip">'.e($post['board_name']).'</span>'
                    .'<span class="chip">'.($post['audience_scope'] === 'region' ? '동네 전용' : ($post['audience_scope'] === 'apartment' ? '공동주택 전용' : '전체')).'</span>'
                    .(!empty($post['topic_name']) ? '<span class="chip">#'.e($post['topic_name']).'</span>' : '')
                    .'<span class="chip">'.e(($post['sigungu'] ?: $post['sido']).($post['audience_scope'] === 'apartment' && !empty($post['apartment_name']) ? ' · '.$post['apartment_name'] : '')).'</span>'
                    .($post['is_guest_visible'] ? '<span class="chip guest-open">비회원 공개</span>' : (!empty($post['access_label']) ? '<span class="chip locked">'.e($post['access_label']).'</span>' : ''))
                    .'</div>'
                    .'<div class="post-actions">'
                    .(auth()->check()
                        ? '<form method="post" action="/community/posts/'.e((string) $post['id']).'/likes" data-like-form data-liked="'.($isLiked ? '1' : '0').'">'
                            .'<input type="hidden" name="_token" value="'.$csrf.'">'
                            .($likeMethod === 'delete' ? '<input type="hidden" name="_method" value="delete">' : '')
                            .'<button class="icon-action '.($isLiked ? 'hearted' : '').'" type="submit" aria-label="좋아요">'.$heartIcon.'<span class="icon-count" data-like-count>'.e((string) $likeCount).'</span></button>'
                        .'</form>'
                        : '<a class="icon-action" href="/login" aria-label="좋아요">'.$heartIcon.'<span class="icon-count">'.e((string) $likeCount).'</span></a>')
                    .'<a class="icon-action" href="'.e($post['url']).'#comments" aria-label="댓글">'.$commentIcon.'<span class="icon-count">'.e((string) $commentCount).'</span></a>'
                    .'<span class="icon-action" aria-label="조회수">'.$viewIcon.'<span class="icon-count">'.e((string) $post['view_count']).'</span></span>'
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
            <ul class="post-list" id="community-feed-list">
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
            <div class="feed-loader{{ $posts->hasMorePages() ? '' : ' done' }}" id="community-feed-loader" data-next-url="{{ $posts->nextPageUrl() }}">
                {{ $posts->hasMorePages() ? '아래로 스크롤하면 다음 게시글을 불러옵니다.' : '마지막 게시글까지 모두 확인했습니다.' }}
            </div>
        @endif

        <noscript>
            @include('partials.pagination', ['paginator' => $posts])
        </noscript>
    </section>
</div>

<div class="media-lightbox" id="media-lightbox" aria-hidden="true">
    <button type="button" class="media-lightbox-close" id="media-lightbox-close" aria-label="닫기">×</button>
    <button type="button" class="media-lightbox-nav prev hidden" id="media-lightbox-prev" aria-label="이전">‹</button>
    <button type="button" class="media-lightbox-nav next hidden" id="media-lightbox-next" aria-label="다음">›</button>
    <div class="media-lightbox-content" id="media-lightbox-content"></div>
    <div class="media-lightbox-counter" id="media-lightbox-counter" hidden></div>
</div>

@if($canCreatePost)
    <a class="mobile-write-fab" href="/community/compose?apartment_id={{ $apartmentId }}&scope={{ $scope }}@if($topic !== '')&topic={{ urlencode($topic) }}@endif" aria-label="글쓰기">
        <svg class="mobile-write-fab-icon" viewBox="0 0 24 24" aria-hidden="true">
            <circle cx="12" cy="12" r="9.2"></circle>
            <path d="M12 7.6v8.8"></path>
            <path d="M7.6 12h8.8"></path>
        </svg>
        <span class="sr-only">글쓰기</span>
    </a>
@endif

<script>
(() => {
    const lightbox = document.getElementById('media-lightbox');
    const lightboxContent = document.getElementById('media-lightbox-content');
    const lightboxClose = document.getElementById('media-lightbox-close');
    const lightboxPrev = document.getElementById('media-lightbox-prev');
    const lightboxNext = document.getElementById('media-lightbox-next');
    const lightboxCounter = document.getElementById('media-lightbox-counter');
    let lightboxItems = [];
    let lightboxIndex = 0;
    let lightboxTrack = null;
    let swipeStartX = 0;
    let swipeStartY = 0;
    let swipeStartAt = 0;
    let swipeDeltaX = 0;
    let swipeAxisLock = '';
    let swipeTracking = false;
    let lightboxAnimating = false;

    const closeLightbox = () => {
        if (!lightbox || !lightboxContent) {
            return;
        }

        lightboxItems = [];
        lightboxIndex = 0;
        lightboxTrack = null;
        swipeTracking = false;
        swipeAxisLock = '';
        swipeDeltaX = 0;
        lightboxAnimating = false;
        lightbox.classList.remove('open');
        lightbox.setAttribute('aria-hidden', 'true');
        lightboxContent.innerHTML = '';
        document.body.style.overflow = '';
    };

    const updateLightboxControls = () => {
        const hasMultiple = lightboxItems.length > 1;
        if (lightboxPrev) {
            lightboxPrev.classList.toggle('hidden', !hasMultiple);
        }
        if (lightboxNext) {
            lightboxNext.classList.toggle('hidden', !hasMultiple);
        }
        if (lightboxCounter) {
            if (hasMultiple) {
                lightboxCounter.hidden = false;
                lightboxCounter.textContent = `${lightboxIndex + 1} / ${lightboxItems.length}`;
            } else {
                lightboxCounter.hidden = true;
                lightboxCounter.textContent = '';
            }
        }
    };

    const getLightboxItem = (indexOffset = 0) => {
        if (!lightboxItems.length) {
            return null;
        }

        const targetIndex = (lightboxIndex + indexOffset + lightboxItems.length) % lightboxItems.length;
        return lightboxItems[targetIndex] || null;
    };

    const createLightboxMediaElement = (item, shouldAutoplay = false) => {
        if (!item || !item.src) {
            return null;
        }

        if (item.type === 'video') {
            const video = document.createElement('video');
            video.src = item.src;
            video.controls = true;
            video.playsInline = true;
            if (shouldAutoplay) {
                video.autoplay = true;
            }
            return video;
        }

        const image = document.createElement('img');
        image.src = item.src;
        image.alt = 'media';
        return image;
    };

    const applyLightboxTrackPosition = (deltaX = 0, animate = false) => {
        if (!lightboxTrack || !lightboxContent) {
            return;
        }

        if (lightboxItems.length <= 1) {
            lightboxTrack.style.transition = 'none';
            lightboxTrack.style.transform = 'translate3d(0, 0, 0)';
            return;
        }

        const width = lightboxContent.clientWidth || 1;
        lightboxTrack.style.transition = animate ? 'transform 230ms cubic-bezier(0.22, 0.7, 0.24, 1)' : 'none';
        lightboxTrack.style.transform = `translate3d(${(-width + deltaX)}px, 0, 0)`;
    };

    const buildLightboxTrack = () => {
        if (!lightboxContent || !lightboxItems.length) {
            return;
        }

        const hasMultiple = lightboxItems.length > 1;
        const previousItem = getLightboxItem(-1);
        const currentItem = getLightboxItem(0);
        const nextItem = getLightboxItem(1);

        if (!currentItem || !currentItem.src) {
            return;
        }

        lightboxContent.innerHTML = '';
        const track = document.createElement('div');
        track.className = 'media-lightbox-track';

        const itemsToRender = hasMultiple ? [previousItem, currentItem, nextItem] : [currentItem];

        itemsToRender.forEach((item, index) => {
            const frame = document.createElement('div');
            frame.className = 'media-lightbox-frame';
            const mediaElement = createLightboxMediaElement(item, hasMultiple ? index === 1 : true);
            if (mediaElement) {
                frame.appendChild(mediaElement);
            }
            track.appendChild(frame);
        });

        lightboxContent.appendChild(track);
        lightboxTrack = track;
        if (hasMultiple) {
            applyLightboxTrackPosition(0, false);
        } else {
            lightboxTrack.style.transition = 'none';
            lightboxTrack.style.transform = 'translate3d(0, 0, 0)';
        }
    };

    const renderLightboxItem = () => {
        if (!lightbox || !lightboxContent || !lightboxItems.length) {
            return;
        }

        buildLightboxTrack();

        updateLightboxControls();
    };

    const openLightbox = (items, index) => {
        if (!Array.isArray(items) || !items.length || !lightbox) {
            return;
        }

        lightboxItems = items;
        lightboxIndex = Math.max(0, Math.min(index, items.length - 1));
        lightbox.classList.add('open');
        lightbox.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        requestAnimationFrame(() => {
            renderLightboxItem();
        });
    };

    const moveLightbox = (delta) => {
        if (lightboxItems.length <= 1 || !lightboxContent || lightboxAnimating) {
            return;
        }

        const direction = delta > 0 ? 1 : -1;
        const width = lightboxContent.clientWidth || 1;

        if (!lightboxTrack) {
            lightboxIndex = (lightboxIndex + direction + lightboxItems.length) % lightboxItems.length;
            renderLightboxItem();
            return;
        }

        if (width <= 1) {
            lightboxIndex = (lightboxIndex + direction + lightboxItems.length) % lightboxItems.length;
            renderLightboxItem();
            return;
        }

        lightboxAnimating = true;
        applyLightboxTrackPosition(direction === 1 ? -width : width, true);

        const handleTransitionEnd = () => {
            if (!lightboxTrack) {
                lightboxAnimating = false;
                return;
            }

            lightboxTrack.removeEventListener('transitionend', handleTransitionEnd);
            lightboxIndex = (lightboxIndex + direction + lightboxItems.length) % lightboxItems.length;
            renderLightboxItem();
            lightboxAnimating = false;
        };

        lightboxTrack.addEventListener('transitionend', handleTransitionEnd, { once: true });
    };

    if (lightboxContent) {
        lightboxContent.addEventListener('touchstart', (event) => {
            if (event.touches.length !== 1 || lightboxAnimating || lightboxItems.length <= 1) {
                swipeTracking = false;
                return;
            }

            swipeTracking = true;
            swipeStartX = event.touches[0].clientX;
            swipeStartY = event.touches[0].clientY;
            swipeStartAt = performance.now();
            swipeDeltaX = 0;
            swipeAxisLock = '';
        }, { passive: true });

        lightboxContent.addEventListener('touchmove', (event) => {
            if (!swipeTracking || event.touches.length !== 1 || !lightboxTrack || lightboxItems.length <= 1) {
                return;
            }

            const deltaX = event.touches[0].clientX - swipeStartX;
            const deltaY = event.touches[0].clientY - swipeStartY;

            if (swipeAxisLock === '') {
                if (Math.abs(deltaX) < 6 && Math.abs(deltaY) < 6) {
                    return;
                }
                swipeAxisLock = Math.abs(deltaX) >= Math.abs(deltaY) ? 'x' : 'y';
            }

            if (swipeAxisLock !== 'x') {
                return;
            }

            event.preventDefault();
            const width = lightboxContent.clientWidth || 1;
            const limitedDeltaX = Math.max(-width * 0.95, Math.min(width * 0.95, deltaX));
            swipeDeltaX = limitedDeltaX;
            applyLightboxTrackPosition(swipeDeltaX, false);
        }, { passive: false });

        lightboxContent.addEventListener('touchend', (event) => {
            if (!swipeTracking || event.changedTouches.length !== 1) {
                swipeTracking = false;
                return;
            }

            if (lightboxItems.length <= 1) {
                swipeTracking = false;
                swipeAxisLock = '';
                swipeDeltaX = 0;
                applyLightboxTrackPosition(0, false);
                return;
            }

            const deltaX = swipeDeltaX !== 0 ? swipeDeltaX : event.changedTouches[0].clientX - swipeStartX;
            const deltaY = event.changedTouches[0].clientY - swipeStartY;
            const elapsed = Math.max(1, performance.now() - swipeStartAt);
            const velocityX = Math.abs(deltaX) / elapsed;

            swipeTracking = false;
            swipeStartAt = 0;
            swipeDeltaX = 0;

            const absX = Math.abs(deltaX);
            const absY = Math.abs(deltaY);
            const isFastFlick = elapsed <= 220 && absX >= 18;
            const isVelocitySwipe = velocityX >= 0.5 && absX >= 16;
            const isDistanceSwipe = absX >= 36;

            if (swipeAxisLock !== 'x') {
                swipeAxisLock = '';
                applyLightboxTrackPosition(0, true);
                return;
            }

            swipeAxisLock = '';

            if (absY > 64 || (!isDistanceSwipe && !isFastFlick && !isVelocitySwipe)) {
                applyLightboxTrackPosition(0, true);
                return;
            }

            moveLightbox(deltaX < 0 ? 1 : -1);
        }, { passive: true });

        lightboxContent.addEventListener('touchcancel', () => {
            swipeTracking = false;
            swipeAxisLock = '';
            swipeDeltaX = 0;
            applyLightboxTrackPosition(0, true);
        }, { passive: true });
    }

    window.addEventListener('resize', () => {
        if (!lightbox || !lightbox.classList.contains('open')) {
            return;
        }

        applyLightboxTrackPosition(0, false);
    });

    document.addEventListener('click', (event) => {
        const trigger = event.target.closest('[data-media-trigger]');
        if (!trigger) {
            return;
        }

        const mediaStrip = trigger.closest('.media-strip');
        if (!mediaStrip) {
            return;
        }

        const triggers = Array.from(mediaStrip.querySelectorAll('[data-media-trigger]'));
        const items = triggers
            .map((node) => ({
                type: node.dataset.mediaType || 'image',
                src: node.dataset.mediaSrc || '',
            }))
            .filter((item) => item.src !== '');
        const index = triggers.indexOf(trigger);

        if (!items.length) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();
        openLightbox(items, index >= 0 ? index : 0);
    });

    if (lightboxClose) {
        lightboxClose.addEventListener('click', closeLightbox);
    }

    if (lightboxPrev) {
        lightboxPrev.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();
            moveLightbox(-1);
        });
    }

    if (lightboxNext) {
        lightboxNext.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();
            moveLightbox(1);
        });
    }

    if (lightbox) {
        lightbox.addEventListener('click', (event) => {
            if (event.target === lightbox) {
                closeLightbox();
            }
        });
    }

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeLightbox();
            return;
        }

        if (event.key === 'ArrowLeft') {
            moveLightbox(-1);
            return;
        }

        if (event.key === 'ArrowRight') {
            moveLightbox(1);
        }
    });
})();

document.addEventListener('submit', async (event) => {
    const form = event.target.closest('form[data-like-form]');
    if (!form) {
        return;
    }

    event.preventDefault();

    if (form.dataset.loading === '1') {
        return;
    }

    form.dataset.loading = '1';
    const button = form.querySelector('button[type="submit"]');
    const methodInput = form.querySelector('input[name="_method"]');
    const isLiked = form.dataset.liked === '1';

    if (button) {
        button.disabled = true;
    }

    try {
        const response = await fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
            credentials: 'same-origin',
        });

        if (!response.ok) {
            throw new Error('request failed');
        }

        const payload = await response.json();
        const liked = Boolean(payload.liked);
        const likeCount = Number(payload.like_count ?? 0);

        form.dataset.liked = liked ? '1' : '0';

        if (button) {
            button.classList.toggle('hearted', liked);
            const countNode = button.querySelector('[data-like-count]');
            if (countNode) {
                countNode.textContent = String(likeCount);
            }
        }

        if (liked && !methodInput) {
            const hiddenMethod = document.createElement('input');
            hiddenMethod.type = 'hidden';
            hiddenMethod.name = '_method';
            hiddenMethod.value = 'delete';
            form.appendChild(hiddenMethod);
        }

        if (!liked && methodInput) {
            methodInput.remove();
        }
    } catch (error) {
        form.dataset.liked = isLiked ? '1' : '0';
        window.alert('좋아요 처리에 실패했습니다. 잠시 후 다시 시도해 주세요.');
    } finally {
        if (button) {
            button.disabled = false;
        }
        form.dataset.loading = '0';
    }
});

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

(() => {
    const list = document.getElementById('community-feed-list');
    const loader = document.getElementById('community-feed-loader');

    if (!list || !loader || !('IntersectionObserver' in window)) {
        return;
    }

    let loading = false;

    const setDone = () => {
        loader.classList.add('done');
        loader.textContent = '마지막 게시글까지 모두 확인했습니다.';
        observer.disconnect();
    };

    const observer = new IntersectionObserver(async (entries) => {
        const target = entries[0];
        if (!target || !target.isIntersecting || loading) {
            return;
        }

        const nextUrl = loader.dataset.nextUrl;
        if (!nextUrl) {
            setDone();
            return;
        }

        loading = true;
        loader.textContent = '게시글을 불러오는 중입니다...';

        try {
            const response = await fetch(nextUrl, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                throw new Error('failed to load next page');
            }

            const html = await response.text();
            const documentFragment = new DOMParser().parseFromString(html, 'text/html');
            const nextList = documentFragment.getElementById('community-feed-list');
            const nextLoader = documentFragment.getElementById('community-feed-loader');

            if (!nextList) {
                setDone();
                return;
            }

            nextList.querySelectorAll('.post-item').forEach((item) => {
                list.appendChild(item);
            });

            loader.dataset.nextUrl = nextLoader ? (nextLoader.dataset.nextUrl || '') : '';

            if (!loader.dataset.nextUrl) {
                setDone();
            } else {
                loader.classList.remove('done');
                loader.textContent = '아래로 스크롤하면 다음 게시글을 불러옵니다.';
            }
        } catch (error) {
            loader.textContent = '불러오기에 실패했습니다. 잠시 후 다시 스크롤해 주세요.';
        } finally {
            loading = false;
        }
    }, { rootMargin: '240px 0px' });

    observer.observe(loader);
})();
</script>
</body>
</html>
