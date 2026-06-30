@php
    $apartmentId = $apartmentId ?? request()->query('apartment_id', 1);
@endphp

<style>
    .admin-nav {
        position: sticky;
        top: 12px;
        z-index: 20;
        margin-bottom: 14px;
        padding: 12px 14px;
        background: rgba(17, 29, 48, 0.96);
        color: #fff;
        border-radius: 14px;
        display: flex;
        gap: 12px;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        box-shadow: 0 14px 32px rgba(17, 29, 48, 0.16);
    }
    .admin-brand {
        display: flex;
        gap: 10px;
        align-items: center;
        font-weight: 800;
    }
    .admin-brand a {
        color: #8ee6d9;
        text-decoration: none;
    }
    .admin-links {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        align-items: center;
    }
    .admin-links a, .admin-links button {
        border: 1px solid rgba(255, 255, 255, 0.14);
        background: rgba(255, 255, 255, 0.08);
        color: #fff;
        border-radius: 999px;
        padding: 7px 11px;
        text-decoration: none;
        font: inherit;
        font-weight: 700;
        cursor: pointer;
    }
    .admin-links .cta {
        background: #8ee6d9;
        border-color: #8ee6d9;
        color: #08302d;
    }
    .admin-links .user-mode {
        background: #f7c96b;
        border-color: #f7c96b;
        color: #403010;
    }
    .admin-links .inline-form { display: inline; margin: 0; }
</style>

<header class="admin-nav">
    <div class="admin-brand">
        <a href="/admin">🛠 관리자</a>
        <span>운영 콘솔</span>
    </div>
    <nav class="admin-links">
        <a href="/admin">대시보드</a>
        <a href="/admin/boards">게시판 관리</a>
        <a href="/admin/reports">신고 관리</a>
        <a class="user-mode" href="/?apartment_id={{ $apartmentId }}">유저모드</a>
        <a class="cta" href="/community?apartment_id={{ $apartmentId }}">커뮤니티 확인</a>
        <form method="post" action="/logout" class="inline-form">
            @csrf
            <button type="submit">로그아웃</button>
        </form>
    </nav>
</header>
