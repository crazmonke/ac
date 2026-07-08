<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>공동주택 입주민 커뮤니티</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=SUIT:wght@400;500;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --ink: #132237;
            --muted: #5a6b7f;
            --line: #d4dfeb;
            --brand: #0b7a75;
            --brand-soft: #d8f2ef;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: 'SUIT', sans-serif;
            color: var(--ink);
            background:
                radial-gradient(circle at 20% 10%, rgba(11, 122, 117, 0.1), transparent 30%),
                radial-gradient(circle at 80% 0%, rgba(245, 155, 35, 0.12), transparent 35%),
                linear-gradient(180deg, #edf6fb 0%, #f9fcff 45%, #f3f9fb 100%);
        }
        .shell { max-width: 1080px; margin: 0 auto; padding: 28px 18px 56px; }
        .hero {
            background: linear-gradient(135deg, rgba(11, 122, 117, 0.95), rgba(16, 87, 133, 0.94));
            color: #fff;
            border-radius: 22px;
            padding: 28px;
            box-shadow: 0 28px 48px rgba(11, 57, 85, 0.26);
            animation: fade-up .65s ease-out both;
        }
        .hero h1 { margin: 0 0 10px; font-size: clamp(1.55rem, 3.6vw, 2.5rem); line-height: 1.24; }
        .hero p { margin: 0; opacity: .92; line-height: 1.6; max-width: 700px; }
        .pill {
            display: inline-block;
            background: rgba(255, 255, 255, 0.18);
            border: 1px solid rgba(255, 255, 255, 0.35);
            border-radius: 999px;
            padding: 6px 11px;
            font-size: .82rem;
            margin-bottom: 14px;
        }
        .grid { margin-top: 18px; display: grid; gap: 12px; grid-template-columns: repeat(12, 1fr); }
        .card {
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 16px;
            padding: 16px;
            animation: fade-up .65s ease-out both;
        }
        .card h2 { margin: 0 0 8px; font-size: 1rem; }
        .card p { margin: 0; color: var(--muted); line-height: 1.5; font-size: .94rem; }
        .card a { display: inline-block; margin-top: 10px; text-decoration: none; color: var(--brand); font-weight: 700; }
        .span-4 { grid-column: span 12; }
        .span-6 { grid-column: span 12; }
        .note {
            margin-top: 16px;
            padding: 12px 14px;
            border-radius: 12px;
            background: var(--brand-soft);
            border: 1px solid #b7e7e1;
            font-size: .92rem;
            color: #1d5e5b;
        }
        @media (min-width: 860px) {
            .span-4 { grid-column: span 4; }
            .span-6 { grid-column: span 6; }
            .hero { padding: 34px; }
            .shell { padding-top: 34px; }
        }
        @keyframes fade-up {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
<div class="shell">
    @include('partials.site-nav', ['apartmentId' => request()->query('apartment_id', 1)])

    <section class="hero">
        <span class="pill">MVP · Laravel 11 · SQLite</span>
        <h1>공동주택 입주민 커뮤니티 플랫폼</h1>
        <p>
            실거주 인증 기반의 단지 커뮤니티를 위한 단일 저장소 프로젝트입니다.
            현재 저장소는 API와 Blade UI를 함께 운영하는 구조로 초기 구축되어 있으며,
            트래픽 및 조직 확장 시 API/프론트 분리를 고려할 수 있도록 설계했습니다.
        </p>
    </section>

    <section class="grid">
        <article class="card span-4" style="animation-delay:.05s">
            <h2>공동주택 탐색</h2>
            <p>단지 검색, 기본 정보 조회, 가입 진입.</p>
            <a href="/apartments">페이지 열기</a>
        </article>
        <article class="card span-4" style="animation-delay:.1s">
            <h2>커뮤니티 홈</h2>
            <p>공지/자유/생활정보/나눔장터 게시판 허브.</p>
            <a href="/community">페이지 열기</a>
        </article>
        <article class="card span-4" style="animation-delay:.15s">
            <h2>민원/건의</h2>
            <p>입주민 요청과 관리 커뮤니케이션 시작점.</p>
            <a href="/community/complaints">페이지 열기</a>
        </article>
        <article class="card span-6" style="animation-delay:.2s">
            <h2>권한 모델</h2>
            <p>guest/member/resident/household_rep/owner_verified/tenant_verified/admin.</p>
        </article>
        <article class="card span-6" style="animation-delay:.25s">
            <h2>개발 상태</h2>
            <p>게시판 도메인 마이그레이션 + 기본 API 엔드포인트 + 권한 미들웨어 골격 적용 완료.</p>
        </article>
    </section>

    <div class="note">
        다음 단계: Sanctum 인증 도입, 게시판별 동적 권한 강제, 신고/첨부파일 정책 강화, 관리자 UI 연결.
    </div>
</div>
</body>
</html>
