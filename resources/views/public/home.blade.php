@php
    $adsenseClientId = trim((string) config('services.adsense.client_id'));
    $adsenseHomeHeroSlot = trim((string) config('services.adsense.home_hero_slot'));
    $adsenseHomeFeedSlot = trim((string) config('services.adsense.home_feed_slot'));
    $adsenseHomeFeedLayoutKey = trim((string) config('services.adsense.home_feed_layout_key'));
    $adsenseHomeFeedInterval = max(0, (int) config('services.adsense.home_feed_interval', 5));
    $adsenseEnabled = $adsenseClientId !== '' && ($adsenseHomeHeroSlot !== '' || $adsenseHomeFeedSlot !== '');
@endphp
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>공동주택 커뮤니티 메인</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=SUIT:wght@400;500;700;800&display=swap" rel="stylesheet">
    @if($adsenseEnabled)
        <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client={{ $adsenseClientId }}" crossorigin="anonymous"></script>
    @endif
    <style>
        :root {
            --bg: #f4f8fb;
            --ink: #15243a;
            --muted: #607086;
            --line: #d6e0ea;
            --card: #ffffff;
            --brand: #0f7a72;
            --brand-strong: #0a5b56;
            --warm: #ffefe0;
            --warn: #b54708;
            --danger: #a61b1b;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: 'SUIT', sans-serif;
            color: var(--ink);
            background:
                radial-gradient(circle at 12% -10%, rgba(15, 122, 114, 0.2), transparent 30%),
                radial-gradient(circle at 88% 0%, rgba(245, 158, 11, 0.18), transparent 34%),
                var(--bg);
        }
        .shell { max-width: 1180px; margin: 0 auto; padding: 18px 16px 46px; }
        .btn {
            border: 0;
            border-radius: 10px;
            padding: 9px 12px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .btn-primary { background: var(--brand); color: #fff; }
        .btn-soft { background: #fff; border: 1px solid var(--line); color: var(--ink); }
        .hero {
            margin: 16px 0 0 0;
            position: relative;
            border-radius: 0;
            overflow: hidden;
            background: #f0f0f0;
            aspect-ratio: 16 / 9;
            min-height: 250px;
            max-height: 500px;
            width: 100vw;
            margin-left: calc(-50vw + 50%);
        }
        .flicking-viewport {
            width: 100%;
            height: 100%;
        }
        .flicking-camera {
            display: flex;
            height: 100%;
        }
        .banner-slide {
            width: 100%;
            height: 100%;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
            text-decoration: none;
            color: inherit;
        }
        .banner-slide img,
        .banner-slide video {
            width: 100%;
            height: 100%;
            object-fit: cover;
            position: absolute;
            inset: 0;
        }
        .banner-content {
            position: relative;
            z-index: 2;
            padding: 24px;
            color: #fff;
            text-align: center;
            background: linear-gradient(135deg, rgba(0,0,0,0.3), rgba(0,0,0,0.1));
            width: 100%;
            max-width: calc(1180px - 32px);
            margin: 0 auto;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            transition: background 0.3s ease;
        }
        .banner-content:hover {
            background: linear-gradient(135deg, rgba(0,0,0,0.45), rgba(0,0,0,0.25)) !important;
        }
        .banner-content h2 {
            margin: 0 0 8px;
            font-size: clamp(1.25rem, 4vw, 2rem);
            line-height: 1.3;
        }
        .banner-content p {
            margin: 0 0 16px;
            font-size: clamp(0.9rem, 2vw, 1rem);
            opacity: 0.95;
            max-width: 600px;
        }
        .banner-btn {
            display: none;
        }
        .banner-btn:hover {
            background: #fff;
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.15);
        }
        .banner-indicators {
            position: absolute;
            bottom: 16px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 10;
            display: flex;
            gap: 6px;
        }
        .banner-indicator {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.5);
            cursor: pointer;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.7);
        }
        .banner-indicator.active {
            background: #fff;
            transform: scale(1.2);
        }
        .banner-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            z-index: 9;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.6);
            border: 0;
            cursor: pointer;
            display: none;
            align-items: center;
            justify-content: center;
            color: #0f7a72;
            font-size: 20px;
            transition: all 0.3s ease;
        }
        .banner-nav:hover {
            background: rgba(255, 255, 255, 0.9);
        }
        .banner-nav.prev { left: 16px; }
        .banner-nav.next { right: 16px; }
        @media (min-width: 900px) {
            .banner-nav { display: flex; }
            .banner-nav.prev { left: calc(50% - 590px); }
            .banner-nav.next { right: calc(50% - 590px); }
            .hero { min-height: 350px; }
        }
        .hero-main {
            background: linear-gradient(140deg, rgba(18, 76, 110, 0.96), rgba(15, 122, 114, 0.94));
            color: #fff;
            border-radius: 22px;
            border: 1px solid rgba(255, 255, 255, 0.22);
            padding: 24px;
            position: relative;
            overflow: hidden;
        }
        .hero-main::after {
            content: '';
            position: absolute;
            width: 300px;
            height: 300px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.09);
            right: -70px;
            top: -70px;
        }
        .hero-main h1 { margin: 0 0 8px; line-height: 1.25; font-size: clamp(1.35rem, 3vw, 2.2rem); }
        .hero-main p { margin: 0; max-width: 700px; opacity: 0.95; line-height: 1.6; }
        .hero-badge {
            display: inline-block;
            margin-bottom: 12px;
            border-radius: 999px;
            padding: 5px 10px;
            font-size: 0.82rem;
            background: rgba(255, 255, 255, 0.17);
            border: 1px solid rgba(255, 255, 255, 0.26);
        }
        .hero-ad-panel {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 18px;
            padding: 14px;
        }
        .quick-login {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 18px;
            padding: 16px;
        }
        .quick-login h3 { margin: 0 0 10px; font-size: 1.02rem; }
        .quick-login .help { color: var(--muted); font-size: 0.87rem; margin-bottom: 10px; }
        .quick-login input {
            width: 100%;
            margin-bottom: 8px;
            border: 1px solid #c8d4e4;
            border-radius: 10px;
            padding: 10px;
            font: inherit;
        }
        .section { margin-top: 18px; }
        .section-title {
            display: flex;
            align-items: center;
            gap: 7px;
            margin: 0 0 10px;
            font-size: 1.08rem;
        }
        .grid { display: grid; gap: 12px; grid-template-columns: 1fr; }
        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 16px;
            padding: 14px;
        }
        .card h4 { margin: 0 0 4px; }
        .meta { color: var(--muted); font-size: 0.85rem; }
        .ad-label {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: 4px 8px;
            background: #eef3f8;
            color: #5f7187;
            font-size: 0.72rem;
            font-weight: 800;
            letter-spacing: 0.02em;
        }
        .ad-copy {
            margin-top: 8px;
            color: #41546d;
            font-size: 0.86rem;
            line-height: 1.5;
        }
        .adsense-slot {
            display: block;
            width: 100%;
            min-height: 120px;
        }
        .hero-ad-slot {
            margin-top: 10px;
            min-height: 140px;
        }
        .status {
            display: inline-block;
            border-radius: 999px;
            font-size: 0.74rem;
            font-weight: 700;
            padding: 4px 8px;
            margin-top: 7px;
        }
        .status-open { background: #e5f6f3; color: #085b57; }
        .status-lock { background: var(--warm); color: var(--warn); }
        .feed-list { list-style: none; margin: 0; padding: 0; }
        .feed-item { border-top: 1px solid #e4ebf2; padding: 12px 0; }
        .feed-item:first-child { border-top: 0; padding-top: 2px; }
        .feed-ad-item { border-top: 1px solid #e4ebf2; padding: 12px 0; }
        .feed-row { display: flex; gap: 10px; align-items: flex-start; }
        .avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: linear-gradient(145deg, #182230, #3b4a62);
            color: #fff;
            font-size: 0.85rem;
            font-weight: 800;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 36px;
        }
        .feed-main { flex: 1 1 auto; min-width: 0; }
        .author { display: inline-flex; align-items: center; gap: 6px; }
        .author strong { font-size: 0.92rem; }
        .title-link { color: var(--ink); text-decoration: none; font-weight: 700; line-height: 1.45; display: block; margin-top: 4px; }
        .body-preview { margin-top: 6px; color: #233145; font-size: 0.92rem; line-height: 1.5; }
        .body-link {
            display: block;
            text-decoration: none;
            color: inherit;
        }
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
        .chips { margin-top: 8px; display: flex; gap: 5px; flex-wrap: wrap; }
        .chip { font-size: 0.74rem; border-radius: 999px; padding: 3px 8px; background: #eef1f6; color: #344054; }
        .chip.locked { background: #fff2e7; color: #9a4a16; }
        .media-strip {
            margin-top: 8px;
            display: flex;
            gap: 8px;
            overflow-x: auto;
            scroll-snap-type: x mandatory;
            -webkit-overflow-scrolling: touch;
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
        }
        .feed-ad-shell {
            border: 1px solid #dfe7f1;
            border-radius: 14px;
            background: linear-gradient(180deg, #fcfdff, #f7faff);
            padding: 12px;
        }
        .feed-ad-slot {
            margin-top: 10px;
            min-height: 160px;
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
        .actions { margin-top: 9px; display: flex; gap: 10px; align-items: center; }
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
            color: var(--muted);
            text-align: center;
            font-size: 0.86rem;
            background: #f8fbff;
        }
        .feed-loader.done {
            border-style: solid;
            background: #f3f7fc;
        }
        .notice-list { list-style: none; margin: 0; padding: 0; }
        .notice-list li {
            border-top: 1px solid #e8eef5;
            padding: 10px 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }
        .notice-list li:first-child { border-top: 0; }
        .footer {
            margin-top: 24px;
            border-top: 1px solid #d6e0ea;
            padding-top: 14px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
            color: var(--muted);
        }
        .footer-links {
            display: flex;
            justify-content: center;
            align-items: center;
            flex-wrap: wrap;
            gap: 0;
            font-size: 0.8rem;
            line-height: 1.4;
        }
        .footer-links a {
            color: var(--muted);
            text-decoration: none;
            padding: 0 6px;
        }
        .footer-links a + a::before {
            content: '|';
            color: #95a5b8;
            margin-right: 12px;
        }
        .footer-copy {
            font-size: 0.78rem;
            color: #7a8a9f;
            text-align: center;
        }
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
        .danger-text { color: var(--danger); font-size: 0.86rem; margin: 8px 0 0; }

        @media (max-width: 640px) {
            .feed-row { gap: 8px; }
            .media-card { flex-basis: min(320px, 84vw); }
            .actions {
                margin-top: 10px;
                gap: 18px;
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
            .mobile-write-fab {
                display: inline-flex;
            }
        }

        @media (min-width: 900px) {
            .hero { grid-template-columns: 1.4fr 1fr; }
            .grid { grid-template-columns: 1fr 1fr; }
        }
    </style>
</head>
<body>
@include('partials.site-nav', ['apartmentId' => $apartment->id])

@if($banners && $banners->count() > 0)
    <section class="hero" id="hero-banner">
        <div class="flicking-viewport">
            <div class="flicking-camera">
                @foreach($banners as $banner)
                    @if($banner->button_url)
                        <a href="{{ $banner->button_url }}" class="banner-slide" data-banner-id="{{ $banner->id }}">
                    @else
                        <div class="banner-slide" data-banner-id="{{ $banner->id }}">
                    @endif
                        @if($banner->type === 'image' && ($banner->image_url || $banner->image_path))
                            <img src="{{ $banner->image_url ?: asset($banner->image_path) }}" alt="{{ $banner->title }}" loading="lazy">
                        @elseif($banner->type === 'video' && ($banner->video_url || $banner->video_path))
                            <video src="{{ $banner->video_url ?: asset($banner->video_path) }}" muted autoplay loop playsinline></video>
                        @endif
                        @if($banner->type === 'text')
                            <div class="banner-content">
                                <h2>{{ $banner->title }}</h2>
                                @if($banner->description)
                                    <p>{{ $banner->description }}</p>
                                @endif
                            </div>
                        @endif
                    @if($banner->button_url)
                        </a>
                    @else
                        </div>
                    @endif
                @endforeach
                </div>
            </div>
            @if($banners->count() > 1)
                <button type="button" class="banner-nav prev" aria-label="이전 배너">‹</button>
                <button type="button" class="banner-nav next" aria-label="다음 배너">›</button>
                <div class="banner-indicators" id="banner-indicators">
                    @foreach($banners as $index => $banner)
                        <button type="button" class="banner-indicator {{ $index === 0 ? 'active' : '' }}" data-index="{{ $index }}" aria-label="배너 {{ $index + 1 }}"></button>
                    @endforeach
                </div>
            @endif
        </section>
@endif

<div class="shell">
    @if($adsenseEnabled && $adsenseHomeHeroSlot !== '')
        <section class="hero-ad-panel" aria-label="홈 상단 광고">
            <span class="ad-label">광고</span>
            <div class="ad-copy">회원 상태 안내 아래에 자연스럽게 노출되는 스폰서 영역입니다.</div>
            <ins class="adsbygoogle adsense-slot hero-ad-slot"
                 data-ad-client="{{ $adsenseClientId }}"
                 data-ad-slot="{{ $adsenseHomeHeroSlot }}"
                 data-ad-format="auto"
                 data-full-width-responsive="true"></ins>
        </section>
    @endif

    <section class="section">
        <!--
        <h2 class="section-title">🆕 {{ $feedTitle }}</h2>
        <p class="meta" style="margin:0 0 8px;">{{ $feedDescription }}</p>
        -->
        <article class="card">
            <ul class="feed-list" id="home-feed-list">
                @forelse($feedPosts as $item)
                    <li class="feed-item">
                        <div class="feed-row">
                            <span class="avatar">{{ $item['author_initial'] }}</span>
                            <div class="feed-main">
                                <div class="author"><strong>{{ $item['author_name'] }}</strong><span class="meta">· {{ $item['created_label'] }}</span></div>
                                <a class="title-link" href="{{ $item['url'] }}">{{ $item['title'] }}</a>
                                @if(!empty($item['body_preview']))
                                    <a class="body-link" href="{{ $item['url'] }}"><div class="body-preview">{{ $item['body_preview'] }}</div></a>
                                @endif
                                @if(($item['is_poll'] ?? false) === true)
                                    <a class="body-link" href="{{ $item['url'] }}">
                                        <div class="poll-preview">
                                            <p class="poll-preview-title">📊 {{ $item['poll_question'] !== '' ? $item['poll_question'] : '투표 게시글' }}</p>
                                            @if(!empty($item['poll_options_preview']))
                                                <div class="poll-preview-options">
                                                    @foreach($item['poll_options_preview'] as $pollOption)
                                                        <span class="poll-preview-option">{{ $pollOption }}</span>
                                                    @endforeach
                                                </div>
                                            @endif
                                            <div class="poll-preview-meta">총 {{ $item['poll_total_votes'] ?? 0 }}표 · 자세히 보려면 눌러주세요</div>
                                        </div>
                                    </a>
                                @endif
                                @if(!empty($item['media_items']))
                                    <div class="media-strip">
                                        @foreach($item['media_items'] as $media)
                                            <a class="media-card" href="{{ $item['url'] }}">
                                                @if(($media['type'] ?? 'image') === 'video')
                                                    <video src="{{ $media['url'] }}" controls preload="metadata" data-media-trigger data-media-type="video" data-media-src="{{ $media['url'] }}"></video>
                                                @else
                                                    <img src="{{ $media['url'] }}" alt="{{ $media['name'] ?? 'media' }}" data-media-trigger data-media-type="image" data-media-src="{{ $media['url'] }}">
                                                @endif
                                            </a>
                                        @endforeach
                                    </div>
                                @endif
                                <div class="chips">
                                    <span class="chip">{{ $item['board_name'] }}</span>
                                    @if(($item['audience_scope'] ?? 'all') === 'apartment')
                                        <span class="chip">{{ $item['apartment_name'] }}</span>
                                    @endif
                                    @if(!empty($item['access_label']))
                                        <span class="chip locked">{{ $item['access_label'] }}</span>
                                    @endif
                                </div>
                                <div class="actions">
                                    @auth
                                        <form method="post" action="/community/posts/{{ $item['id'] }}/likes" data-like-form data-liked="{{ ($item['liked_by_me'] ?? false) ? '1' : '0' }}">
                                            @csrf
                                            @if(($item['liked_by_me'] ?? false) === true)
                                                @method('delete')
                                            @endif
                                            <button class="icon-action {{ ($item['liked_by_me'] ?? false) ? 'hearted' : '' }}" type="submit" aria-label="좋아요">
                                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.84 4.61a4.98 4.98 0 0 0-7.05 0L12 6.4l-1.79-1.79a4.98 4.98 0 0 0-7.05 7.05L12 20.5l8.84-8.84a4.98 4.98 0 0 0 0-7.05Z"/></svg>
                                                <span class="icon-count" data-like-count>{{ $item['like_count'] ?? 0 }}</span>
                                            </button>
                                        </form>
                                    @else
                                        <a class="icon-action" href="/login" aria-label="좋아요">
                                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.84 4.61a4.98 4.98 0 0 0-7.05 0L12 6.4l-1.79-1.79a4.98 4.98 0 0 0-7.05 7.05L12 20.5l8.84-8.84a4.98 4.98 0 0 0 0-7.05Z"/></svg>
                                            <span class="icon-count">{{ $item['like_count'] ?? 0 }}</span>
                                        </a>
                                    @endauth
                                    <a class="icon-action" href="{{ $item['url'] }}#comments" aria-label="댓글">
                                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4Z"/></svg>
                                        <span class="icon-count">{{ $item['comment_count'] }}</span>
                                    </a>
                                    <span class="icon-action" aria-label="조회수">
                                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                                        <span class="icon-count">{{ $item['view_count'] }}</span>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </li>
                    @if($adsenseEnabled && $adsenseHomeFeedSlot !== '' && $adsenseHomeFeedInterval > 0 && $loop->iteration % $adsenseHomeFeedInterval === 0 && ! $loop->last)
                        <li class="feed-ad-item" aria-label="피드 광고">
                            <div class="feed-ad-shell">
                                <span class="ad-label">광고</span>
                                <div class="ad-copy">게시글 흐름 사이에 자연스럽게 노출되는 스폰서 콘텐츠입니다.</div>
                                <ins class="adsbygoogle adsense-slot feed-ad-slot"
                                     data-ad-client="{{ $adsenseClientId }}"
                                     data-ad-slot="{{ $adsenseHomeFeedSlot }}"
                                     @if($adsenseHomeFeedLayoutKey !== '')
                                         data-ad-format="fluid"
                                         data-ad-layout-key="{{ $adsenseHomeFeedLayoutKey }}"
                                     @else
                                         data-ad-format="auto"
                                         data-full-width-responsive="true"
                                     @endif></ins>
                            </div>
                        </li>
                    @endif
                @empty
                    <li class="meta">현재 노출 가능한 게시글이 없습니다.</li>
                @endforelse
            </ul>
            <div class="feed-loader{{ $feedPosts->hasMorePages() ? '' : ' done' }}" id="home-feed-loader" data-next-url="{{ $feedPosts->nextPageUrl() }}">
                {{ $feedPosts->hasMorePages() ? '아래로 스크롤하면 다음 게시글을 불러옵니다.' : '마지막 게시글까지 모두 확인했습니다.' }}
            </div>
            <noscript>
                @include('partials.pagination', ['paginator' => $feedPosts])
            </noscript>
        </article>
    </section>

    <footer class="footer">
        <div class="footer-links">
            <a href="/privacy">개인정보</a>
            <a href="/terms">이용약관</a>
            <a href="/register">나의 공동주택 찾기</a>
            <a href="/service/signup-guide">가입안내</a>
            <a href="/reports/new?apartment_id={{ $apartment->id }}">신고접수</a>
        </div>
        <div class="footer-copy">© {{ now()->year }} 아파인드 (Apaind)</div>
    </footer>
</div>
@if($isVerifiedUser)
    <a class="mobile-write-fab" href="/community/compose?apartment_id={{ $apartment->id }}&scope=all" aria-label="글쓰기">
        <svg class="mobile-write-fab-icon" viewBox="0 0 24 24" aria-hidden="true">
            <circle cx="12" cy="12" r="9.2"></circle>
            <path d="M12 7.6v8.8"></path>
            <path d="M7.6 12h8.8"></path>
        </svg>
        <span class="sr-only">글쓰기</span>
    </a>
@endif
<div class="media-lightbox" id="media-lightbox" aria-hidden="true">
    <button type="button" class="media-lightbox-close" id="media-lightbox-close" aria-label="닫기">×</button>
    <button type="button" class="media-lightbox-nav prev hidden" id="media-lightbox-prev" aria-label="이전">‹</button>
    <button type="button" class="media-lightbox-nav next hidden" id="media-lightbox-next" aria-label="다음">›</button>
    <div class="media-lightbox-content" id="media-lightbox-content"></div>
    <div class="media-lightbox-counter" id="media-lightbox-counter" hidden></div>
</div>
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

    const initializeAds = function (root) {
        if (!{{ $adsenseEnabled ? 'true' : 'false' }}) {
            return;
        }

        const scope = root || document;

        scope.querySelectorAll('.adsbygoogle').forEach((element) => {
            if (element.dataset.adInit === '1') {
                return;
            }

            element.dataset.adInit = '1';

            try {
                (window.adsbygoogle = window.adsbygoogle || []).push({});
            } catch (error) {
                element.dataset.adInit = '0';
            }
        });
    };

    initializeAds(document);

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

    const list = document.getElementById('home-feed-list');
    const loader = document.getElementById('home-feed-loader');

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
            const nextList = documentFragment.getElementById('home-feed-list');
            const nextLoader = documentFragment.getElementById('home-feed-loader');

            if (!nextList) {
                setDone();
                return;
            }

            nextList.querySelectorAll('.feed-item').forEach((item) => {
                list.appendChild(item);
            });

            nextList.querySelectorAll('.feed-ad-item').forEach((item) => {
                list.appendChild(item);
            });

            initializeAds(list);

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
<script>
// 간단한 배너 캐러셀 (Flicking 라이브러리 없이)
(() => {
    const heroBanner = document.getElementById('hero-banner');
    if (!heroBanner) {
        return;
    }

    const viewport = heroBanner.querySelector('.flicking-viewport');
    const camera = heroBanner.querySelector('.flicking-camera');
    const slides = heroBanner.querySelectorAll('.banner-slide');
    const indicators = heroBanner.querySelectorAll('.banner-indicator');
    
    if (!viewport || !camera || slides.length === 0) {
        return;
    }

    let currentIndex = 0;
    let autoPlayInterval = null;
    let isDragging = false;
    let dragStartX = 0;
    let dragStartIndex = 0;

    const updateSlidePosition = (index, smooth = true) => {
        const offset = -index * 100;
        camera.style.transition = smooth ? 'transform 0.5s cubic-bezier(0.22, 0.7, 0.24, 1)' : 'none';
        camera.style.transform = `translateX(${offset}%)`;
        
        indicators.forEach((indicator, i) => {
            indicator.classList.toggle('active', i === index);
        });
    };

    const goToSlide = (index) => {
        currentIndex = (index + slides.length) % slides.length;
        updateSlidePosition(currentIndex);
    };

    const nextSlide = () => {
        goToSlide(currentIndex + 1);
    };

    const prevSlide = () => {
        goToSlide(currentIndex - 1);
    };

    const startAutoPlay = () => {
        if (autoPlayInterval) clearInterval(autoPlayInterval);
        autoPlayInterval = setInterval(nextSlide, 5000);
    };

    const stopAutoPlay = () => {
        if (autoPlayInterval) clearInterval(autoPlayInterval);
    };

    // 초기 설정
    updateSlidePosition(0, false);

    // 네비게이션 버튼
    const prevBtn = heroBanner.querySelector('.banner-nav.prev');
    const nextBtn = heroBanner.querySelector('.banner-nav.next');

    if (prevBtn) prevBtn.addEventListener('click', () => { stopAutoPlay(); prevSlide(); startAutoPlay(); });
    if (nextBtn) nextBtn.addEventListener('click', () => { stopAutoPlay(); nextSlide(); startAutoPlay(); });

    // 인디케이터
    indicators.forEach((indicator, index) => {
        indicator.addEventListener('click', () => { stopAutoPlay(); goToSlide(index); startAutoPlay(); });
    });

    // 터치/마우스 드래그
    viewport.addEventListener('mousedown', (e) => {
        isDragging = true;
        dragStartX = e.clientX;
        dragStartIndex = currentIndex;
        stopAutoPlay();
        camera.style.transition = 'none';
    });

    viewport.addEventListener('mousemove', (e) => {
        if (!isDragging) return;
        const deltaX = e.clientX - dragStartX;
        const offset = (-(dragStartIndex * 100) + (deltaX / viewport.clientWidth) * 100);
        camera.style.transform = `translateX(${offset}%)`;
    });

    viewport.addEventListener('mouseup', (e) => {
        if (!isDragging) return;
        isDragging = false;
        const deltaX = e.clientX - dragStartX;
        if (Math.abs(deltaX) > 50) {
            if (deltaX > 0) prevSlide();
            else nextSlide();
        } else {
            updateSlidePosition(dragStartIndex);
        }
        startAutoPlay();
    });

    viewport.addEventListener('mouseleave', () => {
        if (isDragging) {
            isDragging = false;
            updateSlidePosition(dragStartIndex);
            startAutoPlay();
        }
    });

    // 터치 지원
    viewport.addEventListener('touchstart', (e) => {
        isDragging = true;
        dragStartX = e.touches[0].clientX;
        dragStartIndex = currentIndex;
        stopAutoPlay();
        camera.style.transition = 'none';
    });

    viewport.addEventListener('touchmove', (e) => {
        if (!isDragging) return;
        const deltaX = e.touches[0].clientX - dragStartX;
        const offset = (-(dragStartIndex * 100) + (deltaX / viewport.clientWidth) * 100);
        camera.style.transform = `translateX(${offset}%)`;
    });

    viewport.addEventListener('touchend', (e) => {
        if (!isDragging) return;
        isDragging = false;
        const deltaX = e.changedTouches[0].clientX - dragStartX;
        if (Math.abs(deltaX) > 50) {
            if (deltaX > 0) prevSlide();
            else nextSlide();
        } else {
            updateSlidePosition(dragStartIndex);
        }
        startAutoPlay();
    });

    // 호버 시 자동 회전 중지
    viewport.addEventListener('mouseenter', stopAutoPlay);
    viewport.addEventListener('mouseleave', startAutoPlay);
    viewport.addEventListener('touchstart', stopAutoPlay);
    viewport.addEventListener('touchend', startAutoPlay);

    startAutoPlay();
})();
</script>
</body>
</html>
