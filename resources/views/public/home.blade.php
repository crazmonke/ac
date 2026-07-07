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
        table { width: 100%; border-collapse: collapse; }
        th, td {
            border-bottom: 1px solid #e4ebf2;
            text-align: left;
            padding: 10px 6px;
            font-size: 0.93rem;
            vertical-align: top;
        }
        th { font-size: 0.8rem; color: var(--muted); }
        .post-table { table-layout: fixed; }
        .best-table th:nth-child(1), .best-table td:nth-child(1) { width: 94px; }
        .best-table th:nth-child(3), .best-table td:nth-child(3) { width: 54px; }
        .latest-table th:nth-child(1), .latest-table td:nth-child(1) { width: 74px; }
        .latest-table th:nth-child(3), .latest-table td:nth-child(3) { width: 48px; }
        .latest-table th:nth-child(4), .latest-table td:nth-child(4) { width: 52px; }
        .board-name {
            display: inline-block;
            max-width: 100%;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            font-weight: 700;
        }
        .title-cell .title-link {
            display: -webkit-box;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 2;
            overflow: hidden;
            line-height: 1.35;
            word-break: break-word;
        }
        .title-submeta {
            margin-top: 2px;
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
            align-items: center;
        }
        .title-link {
            color: var(--ink);
            text-decoration: none;
            font-weight: 700;
        }
        .lock {
            color: var(--warn);
            font-size: 0.8rem;
            margin-left: 6px;
        }
        .region-brand {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
        }
        .region-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            padding: 3px 8px;
            font-size: 0.76rem;
            border: 1px solid #d0daea;
            background: #f8fbff;
            color: #3a4c68;
            font-weight: 700;
        }
        .brand-icon {
            width: 28px;
            height: 28px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(145deg, #2e4fb8, #0f7a72);
            color: #fff;
            font-size: 0.68rem;
            font-weight: 800;
            letter-spacing: 0.02em;
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
        .danger-text { color: var(--danger); font-size: 0.86rem; margin: 8px 0 0; }

        @media (max-width: 640px) {
            th, td { padding: 8px 4px; font-size: 0.86rem; }
            .best-table th:nth-child(1), .best-table td:nth-child(1) { width: 82px; }
            .latest-table th:nth-child(1), .latest-table td:nth-child(1) { width: 58px; }
            .latest-table th:nth-child(3), .latest-table td:nth-child(3) { width: 40px; }
            .latest-table th:nth-child(4), .latest-table td:nth-child(4) { width: 44px; }
            .board-name { font-size: 0.8rem; }
            .lock { display: inline-block; margin-left: 0; }
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
            <table class="post-table latest-table">
                <thead>
                <tr>
                    <th>게시판</th>
                    <th>제목</th>
                    <th>조회</th>
                    <th>일자</th>
                </tr>
                </thead>
                <tbody>
                @forelse($feedPosts as $item)
                    <tr>
                        <td class="board-col"><span class="board-name">{{ $item['board_name'] }}</span></td>
                        <td class="title-cell">
                            <a class="title-link" href="{{ $item['url'] }}">{{ $item['title'] }}</a>
                            <div class="title-submeta">
                                @if($item['comment_count'] > 0)
                                    <span class="meta">댓글 {{ $item['comment_count'] }}</span>
                                @endif
                                @if(!empty($item['access_label']))
                                    <span class="lock">{{ $item['access_label'] }}</span>
                                @endif
                            </div>
                        </td>
                        <td>{{ $item['view_count'] }}</td>
                        <td>{{ $item['display_date'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="meta">현재 노출 가능한 게시글이 없습니다.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
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
