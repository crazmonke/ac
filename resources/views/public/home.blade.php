<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>공동주택 커뮤니티 메인</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=SUIT:wght@400;500;700;800&display=swap" rel="stylesheet">
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
            margin-top: 16px;
            display: grid;
            gap: 12px;
            grid-template-columns: 1fr;
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
        .danger-text { color: var(--danger); font-size: 0.86rem; margin: 8px 0 0; }

        @media (max-width: 640px) {
            .feed-row { gap: 8px; }
            .media-card { flex-basis: min(320px, 84vw); }
        }

        @media (min-width: 900px) {
            .hero { grid-template-columns: 1.4fr 1fr; }
            .grid { grid-template-columns: 1fr 1fr; }
        }
    </style>
</head>
<body>
@include('partials.site-nav', ['apartmentId' => $apartment->id])

<div class="shell">
    @if(session('status'))
        <p class="danger-text">{{ session('status') }}</p>
    @endif

    <section class="hero">
        <article class="hero-main">
            <span class="hero-badge">상태별 맞춤 게시글 노출</span>
            <h1>로그인/인증 상태에 맞춰 읽을 수 있는 게시글만 최신순으로 보여줍니다.</h1>
            <p>
                로그인 전에는 전국 동네 공개 게시글을, 로그인 후에는 계정 상태에 맞는 게시글을 최신순으로 제공합니다.
                인증 회원은 인증 동네 + 내 공동주택 게시글, 비인증 회원은 동네/비인증 열람 가능 게시글 중심으로 확인할 수 있습니다.
            </p>
        </article>

        @guest
            <aside class="quick-login">
                <h3>빠른 로그인</h3>
                <p class="help">바로 로그인해서 댓글/작성/전용 게시판을 이용하세요.</p>
                <form method="post" action="/login">
                    @csrf
                    <input type="email" name="email" placeholder="이메일" required>
                    <input type="password" name="password" placeholder="비밀번호" required>
                    <button class="btn btn-primary" style="width:100%;" type="submit">로그인</button>
                </form>
                <a class="btn btn-soft" style="width:100%; margin-top:8px;" href="/register">회원가입 하고 전체 글 보기</a>
            </aside>
        @endguest
    </section>

    <section class="section">
        <h2 class="section-title">🆕 {{ $feedTitle }}</h2>
        <p class="meta" style="margin:0 0 8px;">{{ $feedDescription }}</p>
        <article class="card">
            <ul class="feed-list">
                @forelse($feedPosts as $item)
                    <li class="feed-item">
                        <div class="feed-row">
                            <span class="avatar">{{ $item['author_initial'] }}</span>
                            <div class="feed-main">
                                <div class="author"><strong>{{ $item['author_name'] }}</strong><span class="meta">· {{ $item['created_label'] }}</span></div>
                                <a class="title-link" href="{{ $item['url'] }}">{{ $item['title'] }}</a>
                                @if(!empty($item['body_preview']))
                                    <div class="body-preview">{{ $item['body_preview'] }}</div>
                                @endif
                                @if(!empty($item['media_items']))
                                    <div class="media-strip">
                                        @foreach($item['media_items'] as $media)
                                            <a class="media-card" href="{{ $item['url'] }}">
                                                @if(($media['type'] ?? 'image') === 'video')
                                                    <video src="{{ $media['url'] }}" controls preload="metadata"></video>
                                                @else
                                                    <img src="{{ $media['url'] }}" alt="{{ $media['name'] ?? 'media' }}">
                                                @endif
                                            </a>
                                        @endforeach
                                    </div>
                                @endif
                                <div class="chips">
                                    <span class="chip">{{ $item['board_name'] }}</span>
                                    <span class="chip">{{ $item['apartment_name'] }}</span>
                                    @if(!empty($item['access_label']))
                                        <span class="chip locked">{{ $item['access_label'] }}</span>
                                    @endif
                                </div>
                                <div class="actions">
                                    @auth
                                        <form method="post" action="/community/posts/{{ $item['id'] }}/likes">
                                            @csrf
                                            @if(($item['liked_by_me'] ?? false) === true)
                                                @method('delete')
                                            @endif
                                            <button class="icon-action {{ ($item['liked_by_me'] ?? false) ? 'hearted' : '' }}" type="submit">{{ ($item['liked_by_me'] ?? false) ? '❤' : '♡' }} {{ $item['like_count'] ?? 0 }}</button>
                                        </form>
                                    @else
                                        <a class="icon-action" href="/login">♡ {{ $item['like_count'] ?? 0 }}</a>
                                    @endauth
                                    <a class="icon-action" href="{{ $item['url'] }}#comments">💬 {{ $item['comment_count'] }}</a>
                                    <span class="meta">조회 {{ $item['view_count'] }}</span>
                                </div>
                            </div>
                        </div>
                    </li>
                @empty
                    <li class="meta">현재 노출 가능한 게시글이 없습니다.</li>
                @endforelse
            </ul>
            @include('partials.pagination', ['paginator' => $feedPosts])
        </article>
    </section>

    <footer class="footer">
        <div class="footer-links">
            <a href="/privacy">개인정보</a>
            <a href="/terms">이용약관</a>
            <a href="/register">회원가입</a>
            <a href="/service/signup-guide">가입안내</a>
            <a href="/reports/new?apartment_id={{ $apartment->id }}">신고접수</a>
        </div>
        <div class="footer-copy">© {{ now()->year }} 아파인드 (Apaind)</div>
    </footer>
</div>
</body>
</html>
