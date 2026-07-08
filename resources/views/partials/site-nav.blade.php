@php
    $requestedApartmentId = (int) ($apartmentId ?? request()->query('apartment_id', 0));
    $isLoggedIn = $isLoggedIn ?? auth()->check();
    $currentUser = auth()->user();
    $displayId = $currentUser ? explode('@', $currentUser->email)[0] : '';
    $avatarSource = $currentUser ? ($currentUser->name ?: $displayId) : '';
    $avatarFirst = function_exists('mb_substr') ? mb_substr($avatarSource, 0, 1, 'UTF-8') : substr($avatarSource, 0, 1);
    $avatarLetter = function_exists('mb_strtoupper') ? mb_strtoupper($avatarFirst, 'UTF-8') : strtoupper($avatarFirst);
    $preferredApartment = $currentUser?->preferredApartment;
    $preferredResidenceComplex = $currentUser?->preferredResidenceComplex;

    $residenceRegion = trim((string) ($currentUser?->home_sigungu ?? ''));
    $residenceName = trim((string) ($currentUser?->home_apartment_name ?? ''));
    $forceResidenceLabels = ! $preferredApartment && ($preferredResidenceComplex || $residenceName !== '' || $residenceRegion !== '');

    $contextApartment = $preferredApartment;
    if (! $contextApartment && ! $forceResidenceLabels && $requestedApartmentId > 0) {
        $contextApartment = \App\Models\Apartment::query()->find($requestedApartmentId);
    }

    $extractRegion = function (?string $address): string {
        $text = trim((string) $address);
        if ($text === '') {
            return '';
        }

        $tokens = preg_split('/\s+/u', str_replace(',', ' ', $text)) ?: [];
        $city = '';
        $district = '';

        foreach ($tokens as $token) {
            $token = trim((string) $token);
            if ($token === '') {
                continue;
            }

            if ($city === '' && preg_match('/(시|군)$/u', $token)) {
                $city = $token;
                continue;
            }

            if ($district === '' && preg_match('/구$/u', $token)) {
                $district = $token;
                continue;
            }
        }

        if ($city !== '' && $district !== '') {
            return trim($city . ' ' . $district);
        }

        if ($district !== '') {
            return $district;
        }

        if ($city !== '') {
            return $city;
        }

        foreach ($tokens as $token) {
            $token = trim((string) $token);
            if ($token === '') {
                continue;
            }

            if (preg_match('/(동|읍|면|가)$/u', $token)) {
                return $token;
            }
        }

        return '';
    };

    if ($residenceRegion === '' && $preferredResidenceComplex) {
        $residenceRegion = $extractRegion($preferredResidenceComplex->road_address ?: $preferredResidenceComplex->jibun_address);

        if ($residenceRegion === '') {
            $residenceRegion = $extractRegion($preferredResidenceComplex->jibun_address);
        }
    }

    if ($residenceName === '' && $preferredResidenceComplex) {
        $residenceName = $preferredResidenceComplex->displayName();
    }

    $apartmentId = (int) ($contextApartment?->id ?? ($requestedApartmentId > 0 ? $requestedApartmentId : 1));
    $regionLabel = trim((string) ($contextApartment?->sigungu ?: $contextApartment?->eupmyeondong ?: $contextApartment?->sido ?: $residenceRegion));
    $nameLabel = trim((string) ($contextApartment?->name ?: $residenceName));
@endphp

<style>
    .site-nav {
        position: sticky;
        top: 0;
        z-index: 40;
        width: 100vw;
        margin-left: calc(50% - 50vw);
        margin-right: calc(50% - 50vw);
        margin-bottom: 14px;
        background: rgba(245, 247, 251, 0.96);
        border-bottom: 1px solid #d6e0ea;
        backdrop-filter: blur(10px);
        transform: translateY(0);
        transition: transform 0.24s ease;
        will-change: transform;
    }
    .site-nav.nav-hidden {
        transform: translateY(calc(-100% - 2px));
    }
    .site-nav-inner {
        max-width: 1100px;
        margin: 0 auto;
        padding: 10px 14px;
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
    .site-actions {
        display: flex;
        gap: 14px;
        flex-wrap: wrap;
        align-items: center;
        min-width: 0;
    }
    .site-icon-link {
        display: inline-flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 5px;
        min-width: 74px;
        text-decoration: none;
        color: #17263d;
    }
    .site-icon-box {
        width: 52px;
        height: 52px;
        border-radius: 14px;
        border: 1px solid #d6e0ea;
        background: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 6px 14px rgba(20, 35, 60, 0.08);
    }
    .site-icon-box svg {
        width: 32px;
        height: 32px;
        stroke: #0f1520;
        fill: none;
        stroke-width: 1.8;
        stroke-linecap: round;
        stroke-linejoin: round;
    }
    .site-icon-label {
        font-size: 0.82rem;
        font-weight: 700;
        color: #1d2d45;
        line-height: 1;
        white-space: nowrap;
    }
    .site-icon-link:hover .site-icon-box {
        border-color: #9bb0cf;
        transform: translateY(-1px);
    }
    .site-icon-link.active .site-icon-box {
        border-color: #8ba7cf;
        background: #eff5ff;
    }
    .site-extra {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 38px;
        height: 38px;
        border-radius: 999px;
        border: 1px solid #d6e0ea;
        background: #fff;
        color: #22344f;
        text-decoration: none;
        font-weight: 800;
        font-size: 0.78rem;
    }
    @media (max-width: 640px) {
        .site-nav-inner {
            gap: 10px;
            padding: 10px 18px;
        }
        .site-actions {
            width: 100%;
            justify-content: space-between;
            gap: 8px;
        }
        .site-icon-link {
            min-width: 62px;
        }
        .site-icon-box {
            width: 46px;
            height: 46px;
            border-radius: 12px;
        }
        .site-icon-box svg {
            width: 29px;
            height: 29px;
        }
        .site-icon-label {
            font-size: 0.76rem;
        }
    }
    @media (prefers-reduced-motion: reduce) {
        .site-nav {
            transition: none;
        }
    }
</style>

<header class="site-nav" data-scroll-hide-nav>
    <div class="site-nav-inner">
        <div class="site-brand">
            <span class="site-brand-badge">A</span>
            <a href="/">아파인드</a>
        </div>
        <nav class="site-actions" aria-label="주요 메뉴">
            <a class="site-icon-link" href="/community?scope=region&apartment_id={{ $apartmentId }}" aria-label="동네">
                <span class="site-icon-box" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path d="M3 11.5 8 7l3 2.7L15 6l6 5.5V19a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1z"/><path d="M6.5 20v-4h3v4"/><path d="M16.5 20v-6h3v6"/></svg>
                </span>
                <span class="site-icon-label">동네</span>
            </a>
            <a class="site-icon-link" href="/community?scope=apartment&apartment_id={{ $apartmentId }}" aria-label="공동주택">
                <span class="site-icon-box" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><rect x="6" y="3" width="12" height="17" rx="1.6"/><path d="M3 20h18"/><path d="M9 7h2M13 7h2M9 10h2M13 10h2M9 13h2M13 13h2"/><path d="M11 20v-4h2v4"/></svg>
                </span>
                <span class="site-icon-label">공동주택</span>
            </a>
            <a class="site-icon-link" href="/community?apartment_id={{ $apartmentId }}" aria-label="커뮤니티">
                <span class="site-icon-box" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><circle cx="7" cy="9" r="2"/><circle cx="12" cy="7.5" r="2"/><circle cx="17" cy="9" r="2"/><path d="M4.5 18a2.8 2.8 0 0 1 5.5 0"/><path d="M9 18a3.4 3.4 0 0 1 6.8 0"/><path d="M14 18a2.8 2.8 0 0 1 5.5 0"/></svg>
                </span>
                <span class="site-icon-label">커뮤니티</span>
            </a>
            <a class="site-icon-link" href="{{ auth()->check() ? '/settings?apartment_id='.$apartmentId : '/login?redirect='.urlencode(url()->current().(request()->getQueryString() ? '?'.request()->getQueryString() : '')) }}" aria-label="계정">
                <span class="site-icon-box" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="3"/><path d="M5 19a7 7 0 0 1 14 0"/></svg>
                </span>
                <span class="site-icon-label">계정</span>
            </a>
            @if(auth()->check() && (auth()->user()->hasRoleForApartment('admin', $apartmentId) || auth()->user()->hasRoleForApartment('admin')))
                <a class="site-extra" href="/admin" title="관리자">ADM</a>
            @endif
        </nav>
    </div>
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
