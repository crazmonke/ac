<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>아파트 커뮤니티 메인</title>
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
        }
        th { font-size: 0.8rem; color: var(--muted); }
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
            gap: 9px;
            flex-wrap: wrap;
            justify-content: space-between;
            color: var(--muted);
        }
        .footer-links { display: flex; gap: 8px; flex-wrap: wrap; }
        .footer-links a { color: var(--ink); text-decoration: none; border: 1px solid var(--line); border-radius: 999px; padding: 6px 10px; background: #fff; }
        .danger-text { color: var(--danger); font-size: 0.86rem; margin: 8px 0 0; }

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
            <span class="hero-badge">비회원 둘러보기 + 회원 전용 커뮤니티</span>
            <h1>비회원도 핵심 메뉴는 보고, 주민 전용 글은 제목 미리보기로 가입을 유도합니다.</h1>
            <p>
                기본 공지/서비스 메뉴는 누구나 접근할 수 있고, 주민 전용 게시물은 제목/메타까지만 노출됩니다.
                상세 내용을 열람하려면 회원가입과 단지 인증이 필요합니다.
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
        @else
            <aside class="quick-login">
                <h3>바로가기</h3>
                <p class="help">입주민 커뮤니티에서 게시글 작성/댓글/첨부파일 기능을 사용할 수 있습니다.</p>
                <a class="btn btn-primary" style="width:100%;" href="/community?apartment_id={{ $apartment->id }}">입주민 커뮤니티로 이동</a>
            </aside>
        @endguest
    </section>

    <section class="section">
        <h2 class="section-title">🧭 사이트맵</h2>
        <div class="grid">
            <article class="card">
                <h4>비회원 공개 메뉴</h4>
                <p class="meta">로그인 없이 열람 가능한 게시판</p>
                <div style="margin-top:10px; display:grid; gap:8px;">
                    @forelse($publicBoards as $board)
                        <a class="title-link" href="/boards/{{ $board->slug }}?apartment_id={{ $apartment->id }}">{{ $board->name }}</a>
                    @empty
                        <p class="meta">현재 공개 게시판이 없습니다. 관리자에서 read_role=guest 게시판을 추가해 주세요.</p>
                    @endforelse
                </div>
            </article>
            <article class="card">
                <h4>주민/회원 전용 메뉴</h4>
                <p class="meta">제목은 보이지만 상세는 회원가입 후 확인</p>
                <div style="margin-top:10px; display:grid; gap:8px;">
                    @forelse($lockedBoards as $board)
                        <div>
                            <a class="title-link" href="/boards/{{ $board->slug }}?apartment_id={{ $apartment->id }}">{{ $board->name }}</a>
                            <span class="status status-lock">🔒 가입 후 열람</span>
                        </div>
                    @empty
                        <p class="meta">전용 메뉴가 없습니다.</p>
                    @endforelse
                </div>
            </article>
        </div>
    </section>

    <section class="section">
        <h2 class="section-title">🏆 입주민 토픽 베스트</h2>
        <article class="card">
            <table>
                <thead>
                <tr>
                    <th>지역명/아파트명</th>
                    <th>게시물 타이틀</th>
                    <th>작성일</th>
                </tr>
                </thead>
                <tbody>
                @forelse($bestTopics as $item)
                    <tr>
                        <td>{{ $apartment->sido }} {{ $apartment->sigungu }} / {{ $item['apartment_name'] }}</td>
                        <td>
                            <a class="title-link" href="{{ $item['url'] }}">{{ $item['title'] }}</a>
                            @if(! $item['can_read'])
                                <span class="lock">회원가입 후 상세보기</span>
                            @endif
                        </td>
                        <td>{{ $item['created_at']?->format('Y-m-d') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="meta">아직 베스트 글이 없습니다.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </article>
    </section>

    <section class="section">
        <h2 class="section-title">🆕 메인 최신글</h2>
        <article class="card">
            <table>
                <thead>
                <tr>
                    <th>게시판</th>
                    <th>타이틀</th>
                    <th>조회/댓글</th>
                    <th>작성일</th>
                </tr>
                </thead>
                <tbody>
                @forelse($latestPosts as $item)
                    <tr>
                        <td>{{ $item['board_name'] }}</td>
                        <td>
                            <a class="title-link" href="{{ $item['url'] }}">{{ $item['title'] }}</a>
                            @if(! $item['can_read'])
                                <span class="lock">🔒 회원 전용 상세</span>
                            @endif
                        </td>
                        <td>{{ $item['view_count'] }} / {{ $item['comment_count'] }}</td>
                        <td>{{ $item['created_at']?->format('Y-m-d') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="meta">아직 최신글이 없습니다.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </article>
    </section>

    <section class="section">
        <h2 class="section-title">📢 공지 / 서비스 공지</h2>
        <article class="card">
            <ul class="notice-list">
                @forelse($notices as $item)
                    <li>
                        <a class="title-link" href="{{ $item['url'] }}">{{ $item['title'] }}</a>
                        <span class="meta">{{ $item['created_at']?->format('Y-m-d') }}</span>
                    </li>
                @empty
                    <li class="meta">등록된 공지사항이 없습니다.</li>
                @endforelse
            </ul>
        </article>
    </section>

    <footer class="footer">
        <div class="footer-links">
            <a href="/privacy">개인정보처리방침</a>
            <a href="/terms">이용약관</a>
            <a href="/register">회원가입</a>
            <a href="/service/signup-guide">가입 안내</a>
        </div>
        <div>© {{ now()->year }} 우리아파트 커뮤니티</div>
    </footer>
</div>
</body>
</html>
