@php
    $apartmentId = $apartmentId ?? request()->query('apartment_id', 1);
    $isLoggedIn = $isLoggedIn ?? auth()->check();
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
</style>

<header class="site-nav">
    <div class="site-brand">
        <a href="/?apartment_id={{ $apartmentId }}">아파인드</a>
    </div>
    <nav class="site-links">
        <a href="/boards/free?apartment_id={{ $apartmentId }}">게시판</a>
        <a href="/community?apartment_id={{ $apartmentId }}">커뮤니티</a>
        @guest
            <a href="/login?redirect={{ urlencode(url()->current().(request()->getQueryString() ? '?'.request()->getQueryString() : '')) }}">로그인</a>
            <a class="cta" href="/register?redirect={{ urlencode(url()->current().(request()->getQueryString() ? '?'.request()->getQueryString() : '')) }}">회원가입</a>
        @else
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
