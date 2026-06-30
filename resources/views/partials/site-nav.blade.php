@php
    $apartmentId = $apartmentId ?? request()->query('apartment_id', 1);
    $isLoggedIn = $isLoggedIn ?? auth()->check();
    $currentUser = auth()->user();
    $displayId = $currentUser ? explode('@', $currentUser->email)[0] : '';
    $avatarSource = $currentUser ? ($currentUser->name ?: $displayId) : '';
    $avatarFirst = function_exists('mb_substr') ? mb_substr($avatarSource, 0, 1, 'UTF-8') : substr($avatarSource, 0, 1);
    $avatarLetter = function_exists('mb_strtoupper') ? mb_strtoupper($avatarFirst, 'UTF-8') : strtoupper($avatarFirst);
@endphp

<style>
    .site-nav {
        position: sticky;
        top: 12px;
        z-index: 20;
        margin-bottom: 14px;
        padding: 12px 14px;
        background: rgba(255, 255, 255, 0.94);
        border: 1px solid #d6e0ea;
        border-radius: 14px;
        display: flex;
        gap: 12px;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
    }
    .site-brand {
        display: flex;
        gap: 10px;
        align-items: center;
        font-weight: 800;
        color: #17263d;
    }
    .site-brand-badge {
        width: 28px;
        height: 28px;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(145deg, #2e4fb8, #0f6f67);
        color: #fff;
        font-size: 0.85rem;
        font-weight: 800;
        box-shadow: 0 6px 14px rgba(16, 67, 120, 0.3);
    }
    .site-brand a {
        color: #0f6f67;
        text-decoration: none;
    }
    .site-links {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        align-items: center;
    }
    .site-links a, .site-links button {
        border: 1px solid #d6e0ea;
        background: #fff;
        color: #17263d;
        border-radius: 999px;
        padding: 7px 11px;
        text-decoration: none;
        font: inherit;
        font-weight: 700;
        cursor: pointer;
    }
    .site-links .cta {
        background: #0f6f67;
        border-color: #0f6f67;
        color: #fff;
    }
    .site-links .inline-form { display: inline; margin: 0; }
    .user-chip {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        text-decoration: none;
        color: #17263d;
        border: 1px solid #d6e0ea;
        border-radius: 999px;
        padding: 5px 10px 5px 6px;
        background: #fff;
        font-weight: 700;
        max-width: 220px;
    }
    .user-chip-avatar {
        width: 24px;
        height: 24px;
        border-radius: 999px;
        background: #dde6f6;
        color: #1f3a72;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.78rem;
        font-weight: 800;
        flex: 0 0 auto;
    }
    .user-chip-id {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        max-width: 160px;
    }
</style>

<header class="site-nav">
    <div class="site-brand">
        <span class="site-brand-badge">A</span>
        <a href="/?apartment_id={{ $apartmentId }}">아파인드</a>
    </div>
    <nav class="site-links">
        <a href="/boards/free?apartment_id={{ $apartmentId }}">게시판</a>
        <a href="/community?apartment_id={{ $apartmentId }}">커뮤니티</a>
        @guest
            <a href="/login?redirect={{ urlencode(url()->current().(request()->getQueryString() ? '?'.request()->getQueryString() : '')) }}">로그인</a>
            <a class="cta" href="/register?redirect={{ urlencode(url()->current().(request()->getQueryString() ? '?'.request()->getQueryString() : '')) }}">회원가입</a>
        @else
            <a class="user-chip" href="/settings?apartment_id={{ $apartmentId }}" title="계정 설정">
                <span class="user-chip-avatar">{{ $avatarLetter ?: 'U' }}</span>
                <span class="user-chip-id">{{ $currentUser->name }} ({{ $displayId }})</span>
            </a>
            @if(auth()->user()->hasRoleForApartment('admin', $apartmentId))
                <a href="/admin">관리자</a>
            @endif
            <form method="post" action="/logout" class="inline-form">
                @csrf
                <button type="submit">로그아웃</button>
            </form>
        @endguest
    </nav>
</header>
