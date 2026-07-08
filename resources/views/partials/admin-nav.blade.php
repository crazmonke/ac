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
        transform: translateY(0);
        transition: transform 0.24s ease;
        will-change: transform;
    }
    .admin-nav.nav-hidden {
        transform: translateY(calc(-100% - 18px));
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
    @media (prefers-reduced-motion: reduce) {
        .admin-nav {
            transition: none;
        }
    }
</style>

<header class="admin-nav" data-scroll-hide-nav>
    <div class="admin-brand">
        <a href="/admin">🛠 관리자</a>
        <span>운영 콘솔</span>
    </div>
    <nav class="admin-links">
        <a href="/admin">대시보드</a>
        <a href="/admin/review-queue">검수 큐</a>
        <a href="/admin/users">회원 관리</a>
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

<script>
    (() => {
        const currentNav = document.currentScript?.previousElementSibling;
        if (!(currentNav instanceof HTMLElement) || !currentNav.matches('[data-scroll-hide-nav]')) {
            return;
        }

        const state = window.__topNavHideOnScrollState || {
            initialized: false,
            navs: new Set(),
            lastScrollY: Math.max(window.scrollY || 0, 0),
            ticking: false,
        };
        state.navs.add(currentNav);
        window.__topNavHideOnScrollState = state;

        if (state.initialized) {
            return;
        }
        state.initialized = true;

        const minDelta = 8;
        const revealOffset = 8;

        const setHidden = (isHidden) => {
            state.navs.forEach((nav) => nav.classList.toggle('nav-hidden', isHidden));
        };

        const update = () => {
            const currentY = Math.max(window.scrollY || 0, 0);
            const delta = currentY - state.lastScrollY;

            if (currentY <= revealOffset) {
                setHidden(false);
            } else if (delta > minDelta) {
                setHidden(true);
            } else if (delta < -minDelta) {
                setHidden(false);
            }

            state.lastScrollY = currentY;
            state.ticking = false;
        };

        window.addEventListener('scroll', () => {
            if (!state.ticking) {
                window.requestAnimationFrame(update);
                state.ticking = true;
            }
        }, { passive: true });
    })();
</script>
