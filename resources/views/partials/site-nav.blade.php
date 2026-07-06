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
    .site-links {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        align-items: center;
        min-width: 0;
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
    .site-links a.filter-link {
        background: #eff5ff;
        color: #25457a;
        border-color: #d5e2f4;
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
    @media (max-width: 640px) {
        .site-nav-inner {
            gap: 10px;
            padding: 10px 18px;
        }
        .site-links {
            width: 100%;
        }
    }
</style>

<header class="site-nav">
    <div class="site-nav-inner">
        <div class="site-brand">
            <span class="site-brand-badge">A</span>
            <a href="/">아파인드</a>
        </div>
        <nav class="site-links">
            <a href="/community?apartment_id={{ $apartmentId }}">커뮤니티</a>
            @guest
                <a href="/login?redirect={{ urlencode(url()->current().(request()->getQueryString() ? '?'.request()->getQueryString() : '')) }}">로그인</a>
                <a class="cta" href="/register?redirect={{ urlencode(url()->current().(request()->getQueryString() ? '?'.request()->getQueryString() : '')) }}">회원가입</a>
            @else
                @if($regionLabel !== '' || $nameLabel !== '')
                    <a class="filter-link" href="/community?scope=region&apartment_id={{ $apartmentId }}">{{ $regionLabel !== '' ? $regionLabel : '동네' }}</a>
                    <a class="filter-link" href="/community?scope=apartment&apartment_id={{ $apartmentId }}">{{ $nameLabel !== '' ? $nameLabel : '공동주택' }}</a>
                @endif
                <a class="user-chip" href="/settings?apartment_id={{ $apartmentId }}" title="계정 설정">
                    <span class="user-chip-avatar">{{ $avatarLetter ?: 'U' }}</span>
                    <span class="user-chip-id">{{ $currentUser->name }} ({{ $displayId }})</span>
                </a>
                @if(auth()->user()->hasRoleForApartment('admin', $apartmentId) || auth()->user()->hasRoleForApartment('admin'))
                    <a href="/admin">관리자</a>
                @endif
                <form method="post" action="/logout" class="inline-form">
                    @csrf
                    <button type="submit">로그아웃</button>
                </form>
            @endguest
        </nav>
    </div>
</header>
