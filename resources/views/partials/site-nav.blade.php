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
    
    // 검색창 표시 여부: 메인 페이지(/) 또는 커뮤니티 메인(/community)에서만 표시
    $showSearchBar = in_array(request()->path(), ['', '/', 'community'], true);

    $unreadMessageCount = 0;
    if ($isLoggedIn && $currentUser) {
        try {
            $unreadMessageCount = \App\Models\Message::unreadCountFor($currentUser->id);
        } catch (\Throwable $e) {
            $unreadMessageCount = 0;
        }
    }
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
        flex-wrap: nowrap;
        box-sizing: border-box;
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
        flex: 1 1 auto;
        align-items: center;
        min-width: 0;
    }
    .site-brand-link {
        flex: 0 0 100px;
        display: inline-flex;
    }
    .site-actions-scroll {
        display: flex;
        flex: 1 1 auto;
        min-width: 0;
        gap: 14px;
        align-items: center;
        overflow-x: auto;
        overflow-y: hidden;
        scrollbar-width: none;
        -ms-overflow-style: none;
        touch-action: pan-x;
        -webkit-overflow-scrolling: touch;
    }
    .site-actions-scroll::-webkit-scrollbar { display: none; }
    .site-icon-link {
        display: inline-flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding-left: 15px;
        gap: 5px;
        min-width: 24px;
        text-decoration: none;
        color: #17263d;
    }
    .site-icon-box {
        width: 22px;
        height: 22px;
        border-radius: 8px;
        border: 1px solid #d6e0ea;
        background: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 6px 14px rgba(20, 35, 60, 0.08);
    }
    .site-icon-box svg {
        width: 22px;
        height: 22px;
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
    .site-icon-badge-wrap {
        position: relative;
        display: inline-flex;
    }
    .site-icon-unread {
        position: absolute;
        top: -7px;
        right: -9px;
        min-width: 16px;
        height: 16px;
        padding: 0 4px;
        border-radius: 999px;
        background: #e5484d;
        color: #fff;
        font-size: 0.64rem;
        font-weight: 800;
        line-height: 16px;
        text-align: center;
        box-shadow: 0 0 0 2px #f5f7fb;
    }
    .site-icon-link.active .site-icon-box {
        border-color: #8ba7cf;
        background: #eff5ff;
    }
    @media (max-width: 640px) {
        .site-nav-inner {
            gap: 10px;
            padding: 10px 18px;
        }
        .site-actions {
            gap: 0;
        }
        .site-actions-scroll {
            gap: 12px;
        }
        .site-icon-link {
            min-width: 24px;
        }
        .site-icon-box {
            width: 22px;
            height: 22px;
            border-radius: 8px;
        }
        .site-icon-box svg {
            width: 22px;
            height: 22px;
        }
        .site-icon-label {
            font-size: 0.7rem;
        }
    }
    @media (prefers-reduced-motion: reduce) {
        .site-nav {
            transition: none;
        }
    }
    
    .search-bar {
        position: fixed;
        top: 84px;
        left: 0;
        right: 0;
        z-index: 35;
        background: #fff;
        border-bottom: 1px solid #d6e0ea;
        backdrop-filter: blur(10px);
        padding: 5px 14px;
        width: 100%;
        transform: translateY(0);
        transition: transform 0.24s ease;
        will-change: transform;
        box-sizing: border-box;
        border-top: 1px solid #d6e0ea;
    }
    
    .search-bar.nav-hidden {
        transform: translateY(calc(-100% - 84px));
    }
    
    .search-bar-inner {
        max-width: 1100px;
        margin: 0 auto;
        display: flex;
        gap: 8px;
        align-items: center;
    }
    
    .search-bar-logo {
        width: 22px;
        height: 22px;
        min-width: 22px;
        border-radius: 999px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(145deg, #2e4fb8, #0f6f67);
        color: #fff;
        font-size: 0.75rem;
        font-weight: 900;
        box-shadow: 0 6px 14px rgba(16, 67, 120, 0.3);
        flex-shrink: 0;
    }
    
    .search-bar-input-wrapper {
        flex: 1;
        min-width: 0;
        position: relative;
        display: flex;
        align-items: center;
    }
    
    .search-bar-input {
        width: 100%;
        height: 35px;
        padding: 10px 12px 10px 12px;
        border: 1px solid #d6e0ea;
        border-radius: 20px;
        background: #fff;
        font-size: 1rem;
        color: #17263d;
        outline: none;
        transition: all 0.24s ease;
        padding-right: 32px;
        box-sizing: border-box;
    }
    
    .search-bar-input::placeholder {
        color: #8b9aae;
    }
    
    .search-bar-input:focus {
        border-color: #0f6f67;
        box-shadow: 0 0 0 2px rgba(15, 111, 103, 0.1);
    }
    
    .search-bar-icon {
        position: absolute;
        right: 8px;
        top: 50%;
        transform: translateY(-50%);
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #8b9aae;
        pointer-events: auto;
        flex-shrink: 0;
        cursor: pointer;
    }
    
    .search-bar-icon svg {
        width: 30px;
        height: 30px;
        stroke: currentColor;
        fill: none;
        stroke-width: 2;
        stroke-linecap: round;
        stroke-linejoin: round;
        pointer-events: auto;
    }
    
    @media (max-width: 640px) {
        .search-bar {
            top: 84px;
            padding: 4px 18px;
        }

        .search-bar.nav-hidden {
            transform: translateY(calc(-100% - 84px));
        }
        
        .search-bar-inner {
            gap: 8px;
        }
        
        .search-bar-logo {
            width: 18px;
            height: 18px;
            font-size: 0.75rem;
        }
        
        .search-bar-input {
            height: 34px;
            padding: 10px 10px 10px 10px;
            font-size: 0.9rem;
            padding-right: 28px;
        }
        
        .search-bar-icon {
            width: 30px;
            height: 30px;
            right: 8px;
            pointer-events: auto;
            cursor: pointer;
        }
        
        .search-bar-icon svg {
            width: 30px;
            height: 30px;
        }
    }
    
    @media (prefers-reduced-motion: reduce) {
        .search-bar {
            transition: none;
        }
        .search-bar-input {
            transition: none;
        }
    }
</style>

<header class="site-nav" data-scroll-hide-nav>
    <div class="site-nav-inner">
        <nav class="site-actions" aria-label="주요 메뉴">
            <a class="site-brand-link" href="/">
                <svg viewBox="0 0 100 30" width="100" height="30" xmlns="http://www.w3.org/2000/svg">
                    <image href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAApkAAADvCAYAAAC5S8+nAAAACXBIWXMAAA7DAAAOwwHHb6hkAAAQAElEQVR4AexdB4BdRdX+ztz77r59u9lUEnqRIiBNQClKVxCx/hQRUEAQBBSpSlUQLHSkdxCkSG8JEAgBAiFAKIEklFDSSIO0rW/fu3fm/87c9zZLD2STbJI7O2fOmXJnznzTzp272RhkLkMgQyBDIEMgQyBDIEMgQyBDoIsRyIzMLgY0qy5DIEMgQ2DBEchqyBDIEMgQWPIRyIzMJX8Msx5kCGQIZAhkCGQIZAhkCHQ7BJY6I7PbIZwplCGQIZAhkCGQIZAhkCGwDCKQGZnL4KBnXc4QyBDIEFjECGTNZQhkCCyDCGRG5jI46FmXMwQyBDIEMgQyBDIEMgQWNgKZkbmwEV7Q+rPnMwQyBDIEMgQyBDIEMgSWQAQyI3MJHLRM5QyBDIEMgQyBxYtA1nqGQIbAFyOQGZlfjFFWIkMgQyBDIEMgQyBDIEMgQ+BLIpAZmV8SsKz4giKQPZ8hkCGQIZAhkCGQIbAsIJAZmcvCKGd9zBDIEMgQyBDIEPg8BLK8DIGFgEBmZC4EULMqMwQyBDIEMgQyBDIEMgSWdQQyI3NZnwFZ/xcUgez5DIEMgQyBDIEMgQyBT0EgMzI/BZQsKUMgQyBDIEMgQyBDYElGINO9OyCQGZndYRQyHTIEMgQyBDIEMgQyBDIEljIEMiNzKRvQrDsZAguKQPZ8hkCGQIZAhkCGQFcgkBmZXYFiVkeGQIZAhkCGQIZAhkCGwMJDYImsOTMyl8hhy5TOEMgQyBDIEMgQyBDIEOjeCGRGZvcen0y7DIEMgQVFIHs+QyBDIEMgQ2CxIJAZmYsF9qzRDIEMgQyBDIEMgQyBDIGlG4HPMzKX7p5nvcsQyBDIEMgQyBDIEMgQyBBYaAhkRuZCgzarOEMgQyBDYGEgkNWZIZAhkCGwZCCQGZlLxjhlWmYIZAhkCGQIZAhkCGQILFEILFNG5hI1MpmyGQIZAhkCGQIZAhkCGQJLMAKZkbkED16meoZAhkCGwFKAQNaFDIEMgaUUgczIXEoHNutWhkCGQIZAhkCGQIZAhsDiRCAzMhcn+gvadvZ8hkCGQIZAhkCGQIZAhkA3RSAzMhfzwDjnzFDnQuWLWZWs+QyBDIEMgQyBLkAgqyJDIEMgRSAzMlMcFno42rno3lnvbHjyxBFnHPDuYLfbm3e7HV7/n9tq7C3Jn968tbztm3e07PLmvW27v35f+YB3HnJ/Hv/E7Fs+GHv8O8W5ay905bIGMgQyBDIEMgQyBDIEMgS6GIHMyOxiQD9e3dNNE77x5wnPPHnk6Htazpz8/KuPzX7nlPeaZpba2ksIYth6l0NDkmuNYsmXy+X8h3G7Gdc2F4+3vN9wwYxXzj7k3Uff+u3bj829dsboE95zLv/x+peueNabDIEMgQyBDIEMgQyBpQUBs7R0pLv14/HZ7258yJuPjjp+3PDRz8yZ8F2DOOxjamxvU7AFqYkiRKXQ5Uxo9ScohC5AzoWokZypQw16IY/eiKyx1r7dOqvh2vfH/vN3o+/64KzJI24b6WiZInMZAhkCGQIZAhkCiwCBrIkMga+IQGZkfkXgPu+xM98Z8fip74145Y3ijI3qciEKQT4OaEAaKxA4QMgFkRgAJBGnSTGUw1loGQvrLEuo8WlC1OUMysbl7//gvV/85ZU7m2+ZNvqXfDrzGQIZAhkCGQIZAhkCGQLdEgGaON1SryVSqaFNEzfYe8xAd3/z29vla6I4bwq0G0M4h8gRaZJxgBKcdHSROT4S+hBqdjJPJHQ0RkkmYdQhsMaFqIvyKNcE0aXTXrvhr+8+M9s5J8zOfPdEINMqQyBDIEMgQyBDYJlFgAbOMtv3Lu34RVNGPX7Ku8Nfm2znxj3CAr+Om5AGYOx4O+kbonUJJR9hoLYhSToRPiGn5QQCOG98ss40rb6mNhrWNKXwmzEPlUY2TunH1MxnCGQIZAhkCGQIZAh8IQJZgUWFQGZkLiDSQ50LD333ibZrZozaLgoNIuSBRJDahQhRcVX7sso1+dPkahprYBHRaqw4p9yT4e1mwK/oYoHaKIqmSTE88d1nPrh9xriD+EDmMwQyBDIEMgQyBDIEMgS6BQKmW2ixhCoxZM7ENc8eM7BlRPPkqFeuhreXgaU9yM/cvHgUgNYhLyAdKe2gS5mP+1Qm0OvndGginyVnjgokYaKSPsbqaGQ6S245aDb0hqYgNAHyNfnWqyaPuubsic9dy9tTFtEnMsoQ6HoEshozBDIEMgQyBDIE5hcB2ivzWzQr1xmBf08ZdfKf3nv27Zm2KeolkZFYrP+dS3HN+omcVGJ53jcypLGoIURNSqGxCJLAOybRV0swSXyeSAc3hgalYTwQoUkJTzpwgYE1/hO7K/SorS0+Ovv93/zhnUdfneRcLSvKfIZAhkCGQIZAhkCGwNKPQLftodoq3Va57qrYYeOGvXbd1LFn1gYS55McaOoBQpPPqbmIelqMJQGMQAxEmCcQqOO9JAUt5ZiSElhcKb3BTKvQskrCUkqpDLA6TXHw9qrarALESs4m+booxBvtjRv8fvQ9Hw5rmbAZMpchkCGQIZAhkCGQIZAhsJgQMIup3SWy2cEtE1f8yegH3PPNk9btlcshsGEotCMhokZlDPA+kzeL5BGcCcn5xVxoEApFEi1I/f1K/32cMj9tpyJoZPI5B6HBSWNTQ+cTmaFcv7y79NaSZUASJSdGnISUQyv6FzUFBYmsNVI4Y9yzI2/8YPQFyFyGQIbAZyOQ5WQIZAhkCGQILDQEMiNzPqG9bvLo8896a8T7M8tNtkdQE9Ls45PO0oK0tAlpVILGHkImqmFIg9NLKb56m0mjUL1j4Bh3zK4Sn1dfITU0BSwGFoM6xgxlQ651k/hpHrCV560v4xy/nDtrNcbv9lGUL94wZexRZ0189jXNzyhDIEMgQyBDIEMgQyBDYFEikBpBX77FZeYJ3jaa494aNubyaa8crVZfjeRhrLECgXqA37CBUkVWE4+iS41NZjLCkMaoFzrD7awIyfDmkpeQrBSM0zj1VUAqP0atS/hP4rRnU9MWao7SqGTFWtjwUlRT9FbU0IClckJZ8vU1tXhi7tQNDhk3xI1qmtaf5TOfIZAhkCGQIZAhkCGQIbBIEOhs9SySBpekRoY2Tun309ceTIY1Tfhar1wE40L9/UffBWFIi07x46dyF4mDN/iYDAd/00hOc9CRIMbRaGQBCyMlXjQiEYsiyqYxLmJuuWgaS22ltrgYOthSEKDVsAH6Eh8sCgxvSfkAQCMU+gme7QoNSRj95+w0hFWeR2zLuQAxtQ2CCJNLba2nvPfC9Ds+fPvXyFyGQIbAUoxA1rUMgQyBDIHugwCNle6jTHfS5Prpr//fCeOe/GBW0ozeQT4fWEP7UD2MoflI4CyNQARAJJCYpqTae1BDUr+fO3bGk0NsaTk6PiMC22JLUVO5hHxS07pV3WrP7b/cNy47rP+GF+zda/37tq5b9a1Cqba5vS0ptJfbYzGInLi8ADQXjRERbcvQhiyyetaIWNuwjCgng7AwdaHIFMrW8rbUoQBarpdOfPXav0189mZmZj5DIEMgQyBDIEMgQyBDYKEiYBZq7UtQ5VVVeSsoJ747/IaLJr1wV11oSvoPaQCB0xtIoZEpvIAEeUqgUwMzZBElGn/OUOYFJKx+xrZwIQ1FE9P8TMou3Kl29f+cseo2qz248Y8azlpzyy0PW3njI36z8kbHHLPmpnud/fXvfv2Bb/2o71/W3Kz3tvkVjyi3lZppKlZacqxWigxAysM7fpZ3XlCDM6WOuKMClq06k1CROLG2UFNjHpv5/j4Hjxn4fPZnjlLcsjBDIEMgQyBDIEMgQ2DhIGAWTrVLZq1D3Yz6vcYOfOORue/s3zeq5TViGBladE70btBFDEukfHpbqMamt+iMsAytUBp5QuPPsIgpgvecvMEkM7bICgagR+nUnpvU/m2tbx/w/T4rThQR//CnIbVD7zXmnLbeNledtea3VunVbj50cTkMJNCieaENCye+auXwbQvUpaGD49Um7UrqQZlCAldKBKYcl01NGNhxceu3fjvqrg8fa5qwPjKXIZAhkCHQfRHINMsQyBBYghGgRbQEa9+Fqt/0wevbn/rKEzPfLzWu088UYpME/BRtQEtQMYppEhZ5n6mfq2m8qYGJvKiBB37UhtD4FGqjxp+UeBuapx1oaQ/a9qRsNqpZbtxt39gpv8MaaxRZaL79N2ls3rLZT5b7ek3PU5tLrZBAdaLhCF5SwhgnYiBsVwnUTmumwqq0krdEnZa1UeKS2IqUkgTGIIibQimc8PrwUZdNGXW1PpZRhkCGQIZAhkCGQIZAhkBXImC6srIlta5T3n22/fzxLw+tCQJTb2ohtA4hop+54WjDwUE/hfOW0lkam61MyqfAuTg17ZwRCFjOWJdELrC2bJKwrVQyP2v42qFXrvOddeRzbi6/CLfz1t3pzINW3nClUntTHEsZvNQMaTuyRQdhs6qDwEL441iZ6uzIvWeCWLHULXSOl7NiKAdhPg7Qq6bW/HfC2AOOf2f4GF82CzIEMgQyBDIEMgQyBDIEugiB1FbqosqWtGqGOhfu/dpDyaCZ74R9amtgbGBoiNFcQ+hoTdI+o0HmSmq0QQOnN5Uo0JZj1BXJQ4EWN2Hadwuao7YN7aaHq5n6t3W27X/i1za7Ks1bsPCXy6075fiN9+yxfBy93F5uB+1hXkwKDWJADU3Qqe5kABV3aaBRU5XJtT80iFnESQwrpr5QGz47a8r6e7/6sBvWOmVVfSCjDIEMgQyBDIGFg0BWa4bAsoSAWZY627mv908d941TXr67/F4y1/SuqTP677ed0M6kcUlLDI4WG4kGGiJAPzmDZpujMUkLjpJAP5eDVqXwuzVvOJlmArHFuGS+VbPKW49s8OOVf9hjhQ/4bJf5HUTiazfebdMfFFY7tanYaJxxFjQZHQ1G/TSuDTnLH0ddleBi9oF9AfvhE8hBpWluAqGFILFiC7kazErKOHnM8+OunvHWgVpPRhkCGQIZAhkCGQIZAhkCC4KAWZCHl8RnaXTJ2RNHHfbXKSNfMaHltSRtSAs40Q/ONL5oi2m/aLiR0ZSkIeYgvM0Ulkn/ZJADKr+DaYgfYxryi3SpFNsD+21y2uVrb/N1ESawhoXhj1pr8zOPXWGzLVtb2+K2pARnJLTOQf+3H6e6UmY/i2ShUwUc+6GCJ4Ew7kXmOdC+pqHpmFabi6Kb3htzzUnjnh3M5wNmdyOfqZIhkCGQIZAhkCGQIbAkIUDzaElSd8F0Helc7tBxQ1/978wxF/WMapBLAl7xCSulyeWUKFa8aHJF5lVgHswGBCICQCIfRVKSwKFNSqYgtfjHqt/d6IiV1jsdi8Dttvw6z12++k7LrRLn32kptgCBFBPebFresDox1onkHdTQFa+NIP0xjKWSUKJ3tDLFGUvj1NoE9VGNGTFnxvf3Gf3YtJGNjf1YIvMZAhkCGQIZj2wiawAAEABJREFUAhkCn45Alpoh8DkIqM3xOdlLT9bDcyd9+8RX7y2+1Dp93X65QsjP46EIDS1xlU46mmGAJjGEQ+oci5BanS8noROJwTSILSFwUUvcjs3zK937wAa75rbrt+Lr6VOLJly7b9/Gazb5wdrb1a00tLG5OU/jksaio6mod5qqJA1IquL7xKjnGu9EFMH+FHmXyytd2JhP54Oc/SAu9Tv5zSen3/nh+N8jcxkCGQIZAhkCGQIZAhkCXxKBZcLI/PeUF2886Z0nn2kLy6ZnkA9NIqUgUKsL/OydIia0tNSjal0yWUVecLKMFOAMeD0IZ10IkTgRiUplEx/cZ9N9/r32Nj8XpvGRRe7Zrjvt69/Z8dCVN9nbFhPEiSsZCULth6GNqQRo70jskIBOeZUY5X1nXtOFDxlh6KzJW2dzOWMvfe+li88YP/IO55wWYenMdxECWTUZAhkCGQIZAhkCSzUCS7WRScPIHPTGE7Ovnf72r+prC2FtUtMqll0W8bd2gOcVE4wxqEttKcdUtavII+tMydHIpIXZKnw8tuVweeRx6Urb9P7Nquvdqk8tbtp/pfX/d8FmW9SvFtQ3tpXiWIKAX8BREtqGokqzW45K+rjyKjlXYpb+HVAI+0gZRg1N0PRMENaFUWnIjEl7/Gb0k/Fop3+Qng9mPkMgQyBDIEMgQ2CpRCDrVFciQJOpK6vrPnXx83if3V55IBnb9mGvATW1iEohvwibAg2oWA0pGluGnAYWQ6rtQO4YOkCUkDomIXG8vYSFy6HQUm6zm9asMPSWDXeTDfr3b0Y3chvL8i3/2WinFXZtWOnJuS1zjTVJhIB6U3ewT4bGo6rLniqr9jMyDiEnQsxsMolZVG9vCZjESCTqafKYUWwpHvvCg+0PzZ60vX84CzIEMgQyBDIEMgQyBDIEPgcBGhWfk7uEZt3w/ugjTn/72Q9aTNkWgsjasu+I5Ydg6O0mrU01KS1T9UYTvOyjyFRaV/SVOE0xWl0OLuZFoCkF1tp2h9+v+q0Tzlt3mx39A90wEBF7yjrf+t7xq272f6XmtmI5jhGK0KB0ECJg2HPGGAJMhsoMLHvvP7HD0SJ1iBwc00BDE5rA+19XCALBOWNGDvn3uy8/SRwFmcsQ6IRAJmYIZAhkCGQIZAh0RsB0jizpMg0fc/K45166aPqY8/NRZAKXMzSVeFEH2k5O/1GMdtEwQs5vwwxB0wveyqTNJLSnAOsYJ8VwDjSswqIro780FM9db8fV9llunXOwBLi9Vvz6PRet+4MVVojDScViSxxUUGAvkRqXgEBiASAOOg9ieEdcHAWmEU+oaAFjxcRaRV1tZO6dPmHbI8YMe/M95/IsmfkMgQyBDIEMgQyBDIHuicBi1UqNi8WqQFc1PmT2lNV+NuaRDx5pHv/NXrl8ZGMp0UiiwUgzicait5YqosrOCUBy4D2lgAaWfiYW2lGpDepgQ2dc3Fgu4tv5lYffucH3em5e6DOxq/RdFPV8s3fvOTd/68drbpbv+2JzS0vJBDrcjsZz9U4ToUBloTpSvckMiVv1j7i3OgLBuNrbTDc2sQ6FXFQa19K49nEvDp47fNa0Dflw5jMEMgQyBDIEMgQyBDIEPoKAWh0fSVgSI5dOG/vH4997ZvxM29ynwfByLREbAPzUy1s5OFR8R9eYwlu8NKrmFUADS41NXug5/3nc2dhY2xbb8KD+G+920bpbf0dEaIhiiXPUu3zeRt/f8oBVNj62vdhueSsZirCj7CecsRAa2fCGJj+mSwmpU4OzSCwKxIqGt4cQ+tudgBqaiEIxpWaXRCe++dyrl00ce3n6WBZmCCxFCGRdyRDIEMgQyBBYIATMAj29mB/mDZs5/K3hr10yedSFhcggl+QsP4+rTUnriTaR6A0l6GgqMVVDRgABeIkJGlugWCKBjp+OLUyAsNWWTD9+Cb5w9R1W+d0q6w9i3hLvD15lg0vO+sY2K/W0UbFsE0hgQidqhAtExIhASX9HtQg6B0R6i2k1cPorBMTM0SolMbvI9NBZhygX2Rsnvf2bP7z6zEscD2Fe5jMEMgQyBDIEMgQyBDIEsDCMzEUC6yNzpq3x/Vcfan+madIGy0c1xZqysWooQdSwdJRdSIsnZBo80WqCkmqnnOTU0oSJ9EZPIBGCIG4qx2ar2tWL92yyW7Bl376TtfjSQt+u7z/tzs1/UPvN2v5PNxbbYjEhBDTGiYU4KQo5McoTF/0X5v4GE7zlZLKXyUsWfMC5PGWmCZLYmkIUhS+1zNlwjxeG2Jdnz+7FIpnPEMgQyBDIEMgQyBBYxhFYIo3MiyaPueKUccPfbbFtYT9+Hg/iMHQI/C9TCiQUwIiTmGRJAI1JoTklHGwaR3CUq8QbuZhGKA0mtJbbbfi7ldb/7QXrblHLokutP3f9rbbZd4X1TmhubeMVJawxAucsDUfhLSZhFBBPchhjIYZGJy1RWOtoiDuxjsgw3XNASnGSmNBIOA1xiTeas/87/d2dkbkMgQyBboZApk6GQIZAhsCiRWCJMjKdc+bocSM+uGHqmEPrc4I6RDQkA0sbkoalgxqRxjp/I6cyoTQ+0Uc0cDQ4HQ0qMqvEz8bGhS1JsVRnc6WLV96y/jfLr38Nn1vq/RGrfuO8c9fdeuMeZVsqlWlbBogTZ6PEESMxIG5EiLalg/89TSbTEBf9nU5vdDIeWwd6RMLy1pk4x6/npjbAv8e+8shf3npxmI4XMpchkCGQIZAhkCGQIbBMIkBrYsno94jm6QP2GfNo+dk5E/r1ztVYY0NaiXrjZtkHR4PIQZxjZ5z+F4kxTUpfQAsxET4HvLGDFteYhaM4t73dbppb6a2/fHOX/hsvv3yLlv04La3xLfsMePWfW+zWZ+2o7unZzU0hwsCImJhgEU9ajnqLKYiIVjOJxrm/1aRxKbQuJXKOcRBp3mYSo1C/sXMk4kKhgIenTd76wJefGj/azahnXuYzBDIEMgQyBDIEMgSWMQTMktDfa99/46Bj33hmypT2ZlMf1lqxgdfbgaaP2jjshKixQ9tHGaM0MBEjjZCDTmgk0eSkFQTjSrwCRUt7ggMGbHTMJRtuveHmIumfbGfJZcmvLdJ+6cbf33aP5df5X7GlhTeTLoQIL4SJlQeCuEHqiTTzaFtCDXvPFf2YhiYIaQReJwMCCxPGVlCbrzXvtLetcuyIl+cO/uCDzZG5DIEMgQyBrkUgqy1DIEOgmyNgurN++rn1yNeHP3HxpFeuyRmYHHIlZ4Wfa2leOjUiqb2jXeN4PenQTEPHeqMH3ggKmQumhUwrOUfR2ZLjR9/WuD2qjaP4rHW3WfuI1db9ty+3DAci4o5be7O9//L1rX5WW5ZSKY4tjBTVqiTecE7UmCTuir1iq9ynEVsC5yrkH6BMYzPhtaaRAG1izF9ef/7ZS8e/eb7mZJQhkCGQIZAhkCGQIbBsINBtjcwhjdO33uWVQeVhzZO26VdTi5AfyHVIKvaMpcmof8tRk3wfmF5Po5SGpSuSg3ElNXto7iBieSCUkJ/HsUnNiuMe3vyH0Q49+7+tFSxz9Bkd3mm5lR64eqvN+n4j36+xqdSed0ZvJh0/jxN+PqOYEnjFtZV2JLFm4se8sJD+2oLeLDsvW1vI581/J777x8NHPTueY/Opz32smiyaIZAhkCGQIZAhkCGwhCNguqP+l0x+Y+Lxbzw1rNm2mj5hrYHepInweq1iLMJV9aYR6cAf3mAyBNSgzLNPvHVzKmt+CLFIJCkV28rmt8ttdOoVG26zjojQBGLJzH8Egf7Sv/nijbfos9dyXxvR3FaME/DzuXG8zdQbTJSIGk1PFCoP0eYU4i/ehmc4j2sBtTKdGBtbUxfV4OXGplV+8szg8vDGD9fV7IwyBDIEMgQyBOYhkEkZAksbAlVjrVv0a7Rz0eFvPNN21ZRXVumZC+N62pQBzRhaLjQUXQRaOJRVV/0Xz2pA0gCFZTINH5o4QoLhEwEv2oJQIKEYA/3u28PmzT++tt0Kh39t/TO1gow+GwGhAX7s2htu9dd1Nt0jLNkijcSYiOrfMwpF7XsnMZywAjHEnlgDwig9NEFvMnViadyni1hnnekZmrg5Jzjh5Wdfv37KW1cgcxkCGQIZAhkCGQIZAkstAmoLdIvOPTR36rf+PGpw+0tNU83yuTqEcRgBakMK7UqhfSkxY0VKVoCIxg2jauywEEwR3vgxoQMNGu2RuFYYQbFcxmb5AYMf2PQH0Q79+0/TrIzmD4Hdll/1vss222LN/mFNY7HcDhOEatzrraaB4/WwE8YFkhqcvlKVmQJhTCeXUBJwVMTFsUMU6itAIR9fMu6NQ096w/+Zo5BFF9Bnj2cIZAhkCGQIZAhkCHQ3BNQOWKw6Oefk4smv/+Nvbz3/fLtrR++wEBkbFA1vIGmg0KgEqKQalvo7mHl+udXLMpouvMEEvIHiPFdTBrzdTEKYxJYlLhTb2u1+fdY79ZL1t95FRFhssXZ1iWx83brlpvzz2zutuG3PAXfPbZ2bj2FjMTC8muQYOL4IgJxEdA0NfWHME+Og8UlTFBwzmpYcK0HRwdhcLGG/2gKenjH9u79+aVj55bbZqyNz3RIB51zuxRdfPeTGm+9sOu+iK5O//eOC9n+dd8ncm2+98/JRo8bsOG3atLpuqXimVIZAhsDiRyDTYJlHwCxOBMbMndvnD68/+/5N7489sZ6mZQ458FCztFPyJJDy8E5NFZouNFzoY6ar/WLIYxqPFpDQCQCxxgWwra5s+th86Z/rbbvu4dnncSyo0z9z9K+Nttjj2LU2OYhXwyaJExvyLYBme2wIvBB7w0ZUNhwUJWG64WAxi+MDfmZnBLRCoe8MfCFIgEJNjX2/2IbjX37xzds+nHwyq8h8N0HguedeWef4U/6ebPv93Yu/OOCIK088/ez6Cy+/3lx14+3RJdfc0nDimRcess8hRw/Z76Cjm0857azZr7755rpcu9JN1M/UyBDIEMgQyBCoINDc3Dzg4cFDjz3jX/9+8piT/jbzj8f/5e2LLr/2qtFvvPET7tv+sq5StMuZ6fIa57PCO6aOP/b3bz/zwfOt01boWVMD4f2YdQ6WhojV0JP/Um5prxAE4ddWNTYR0lxRw8WS65/QMaAgJBgTt5US8538qk/e+81da7fr2X/cfKqTFfsCBETE/XLVNa+7ZLNt11otyJdaykWa9iYUziBRw1IAEVhG1YqEYdwTKOrYABAn/uZTow6CxIoJAn5AD4LoorFjzjz5zdce54QXZG6xIUD8a/5yxtnTfnnIUW/edPv9dsaHs0yhroB+/fqiX58+WK5vH/Qn9evVYKIgxPvTZtib73mo1577HPH66f+4YOrMmTMbkLkMgQyBDIEMgcWOAPdz88RTzx72y/3/MPm3R5107uXX/fe799z3UJ/7Bz665gWXXX/QL/b//X2/+d0xTePGvfvjhaWs2gQLq+5PrVc7feo7L7955lMaGTEAABAASURBVOSR/yqbkqkzOYC3Wo6lrbN6k0lCJxKjtifzQyfQyzEamz6f6WITOKt/+zIRh1JrHB42YJNvn7/eFtuLiBqiyFzXIrBxr17v3bTVDvXb9ug3obGtjWMhsRHahTo45DqhDMRqmnAMhMYklJjPMQSUU7DkljxJYJLExbVRjR08Y/oOe744fOpzjY19kblFjsDrr0/u+/NfHtp81Y13DCjU5IrLL9cvzNfWAnyTcBwryyAlC6tjyvTaQsH07dUThdq6+Mb/PTjgR3scNHXYsOc25joXZC5DIEMgQ2CpQmDJ6sygwU/86aDDT7hk1Og3wp71hWKfXr1Mjx4NrfU9eqBHfcHkghyefHak2eOAI+9+edTYPy6M3pmFUeln1Tmcn8d/OfrJtntmv71Oz1wUhklAc0R4YDnamdZznmVw/of2iK/IxywNEho0gBWJecAZ8mYrtGUCmFZbLkU2h7PW/m7Dgauu+YJ/LAsWGgIikpy76darH7bqeqe2t7eHsLYUGDbnwFCKAlofNEKUizAU5qnn4NJOgZIaKwmFGCgmDmEcW9NgDKa3FQcc99KIGffPmPJdfSSjRYPAuHHjan53/HHTXnptNFYY0B/GBPkkSbjWOGgcPxGBiIGIcgEF8D2BQ+9skliOaRz27lVvm5tbC4cfc/Lzjz/+1FbOaQlkLkMgQyBDIENgESPw7vTpA/517mX/5C5se/bqwT3a5QEXW5sUrF7oMaBc6t2zV9Ta3BSe+veL//hha+tKXa2m6eoKP6u+ES1zNvvLO8NnvlX8MOoT1sKVRQ+pEo8nGphqSLL7PM8sK3DkKTkwnwedfiJ3YQKeaUCe2aCBWe8CV2wpt+Ob0YBRg7/1A/nucss18fHMLyIEDlpz7X/9c6NNVi3ELo7jJA6CgC3rRAZohpQA0IZkSMMEJB03JR1jvjSAxiXI85aDTQ8amnHIAi4MzWmvjhpyy/sTf8mnM78IEDjt7MtnvPvOBMM33TAuxyUHDgRgBJUfjl+HGty1+OEAvojjEDpX4p6FUqlkwpoc2ktJdPSJ/xw2ZsyYAdVnMp4hkCGQIZAhsOgQePLRYc++P3U6vzLluT9bCC8JAAkFAPdvPXaNsy4s0YbSL1ZjXx87YPgTI85AF7tFYmSOKc5d69Q3Rzw/B222Pqih6cEDjC07uMiyt9onpqRnFgXCQVlgmWGdhIBY6wyBEkNkmORswk+xbUWXP7DvN/56xUbf/TYyt1gQ2K7vSpP+se3OvdeM6ke2tZetIIBwFgvEkHTs4PyYcrSR2iU6rrRTUCGrcb5AIDFiynzT4tRAfV0tzh839pYHPpi6PR/L/EJE4N9X3HDO40Oeqm/o1dOUyiVwpEIdNKGUjhhTOIi8mWSyU6vS80o8tNZG1nIEnY1L7UXkCxHmzJltTj/rsndYRhai6lnVGQIZAhkCGQKfgsCwZ19YzYSBNbmgICawIiYGd2MHiR33dsebAeessUnMpy24dxeefOYF3nYy2oVez/MurO6TVU1xrnDKWy+/Pt21mQJq2CE9tqTk9NAC7UgHHliCiq3JrmscPMiUxOcl1hjnTORgSzDOlF3Z1JfNrLPX/tYqh6/5jb99stUsZVEisIFI6apvf2frn62w6kWt7SVYCSyM3j6jCM5qR7IkmiGwgB9T65RrhCWVicSWhqkT/Xdc/OTuXFSI8vb8N19/aOSsWT1ZJPMLAYGRI0fmrrruluMa+vQi8LZZuC7hbHVf4IcEB79WmU6pIluuTwtYyzjJ2lbHDctaF5IhLpfjHg118fDhzxfuuPvBny0EtbMqMwQyBDIEMgQ+B4Fp0z9AGAY8YKVVxBgI7SwY/cLIyx8+qHs6N2xnE2t5IFtrS7PmzOHJzLwu9KYL6/pEVc45ufitF6e/1Tor7C2RdYmL2U89mCJhaRqWHe1bpwYl+8eziwWYC29wWsejzPE0QwIbImwutdlvhP3mnPet3Vbaoe/Kk33BLFjsCIiIO3qddY8+e92N1q/h59a2pGRdgHxMs7LDuIThJ3JhivND7DjcJL3MJrnQQJpFTMi68kI7pwZcGLD5iye+y3cVZxZ7J5dCBW69++F7Zs6eiyiXA+Kknl1s5cLTDwawzoaOmxA+gywsnEtINnIcSD4QMxJzuYZc32FNPle69fYHbmSdS5/PepQhkCGQIdCNEeDpyYvMwBiRgnBDFgiNS/3b1v7g5cWdnsMk6Jdi7uWCKBC0d3WXTFdX2Lm+O6e/d899H0yo71OTLyWJNaKZPLC0URqYsdCwFN9fGpRVzjJM5jmngqZbA4EpB9YW22JzyKob7XHNxtv11tszZK7bIbDViiu+fv42O/VaE7WzGltbAdHh4+DybYFDDxoicCqrUZKSAc0bPzcE9ToflDhZilquxuTw9txZhXMmvvsflst8FyIwZcqUwpNPP79boTZftDE/mYhYgRQApxsQdx2OAMdIDUing1cZN1dN47tfKlv/ed3xNZAjzfF0fCl2yNfkw5deHVM/ZMjINbtQ7ayqDIEMgQyBDIEvQMDQcKoWERF/wcf9nanCzd7n8EpPuN0jZNjKM5fXQKIXDT6zqwLTVRV9vJ7JjY19r33/3Z/2rOUn/sRFNKcBcXpbpZ3UzoTCnjGRcecJ4kBnHYQ3IpRojvBeK25HjILLt5693larH7bi+vdoTkbdFwH94+3Xbr1t/30GrHpv49xG2i+c0wmHtKyc95oJ7Re+M4DGin+bcIi0Nzr8fqJzYggkLyJaxNbl8hj43qQ9Xmtuzv4hiQLVRXTnA48dNmXaDBQK+TwXoNZqHAfEqQQuUedvj+PUkHSxpe3oaGxaS7nDwOQTHEfm8Un/oIEErAcIwtAkNsbgxx+72udkQYZAhkCGQIbAIkGA+zSc1f2Z561z+m9qoRd43OtDEYGIGBIgosTbTgNjTBld7EwX19dR3Z2zJ0+eWmpGJFRc+8AcA/E/VS7ssTBdNFU7SuPSwRhAQn4/LbrAmeZye7hJfoXikM136bFT7xUnIHNLBAIi4o5cf6Ofn7H+htuXW1vjYlKOJeCEt7akt5liXR40TtLO6AwArRpwJpDTyhGAMo1MH5o4F7j8Je+8OxGZ6zIE7nvg4XNrcvoFBRAFHFWnEWKfJqabk25SzsWVTYuf0TmWGucYUuKDHDTK0EcZA5+1NEh79qjHw48/tcN7772X1+SMuisCmV4ZAhkCSxMCiU2Q8DLAcl/u2KN9B7lJ08wSEsTQ00ajHBiDQJjny3RdYLquqnk1jW1pWeGBGRPzPXIRaEyAxxX0HxSo+toHz1lcuYH/RMcyhinsJXgyGQtrbL69bONDV9j4tKs33LKWmZlfAhH4wapfG3bLLpv06GXy7zXTvnRG/D/g0rEXHXth6MnAcMKb6kygzaLddXy14jfc0ORq4pdnzooen/3h/2l6RguGwN0DB64z7p2JqOtRx4ocRLHn8lMuIkyjd/ySQqIUk9SrwWlVYA6ZSy1USgCf8c8JJQGHDWA8xz2guXmuvfWOhy9E5jIEMgQyBDIEFgkClkamVSOT5NTQJPmGuS+LcJ8Wnrnc840JECiF5IHxRboy6Poaqd3gmRP+82FcLOUgPItoQ6cHFWVmVmWKwnySIalUpDJGeGy12zLq41zzhStt1nDoamufzqLd1meKfTECa8gaxUe2//46W/Ze4eLm9naIMXz7cBCBEeHoGy9oHAaMk1BxDrCJGFjmmJzgjsnT/lfJytgCIHDffUNeEyD2hj3xFWIuwrATMQmMVg1MwMHwZdHAuZiyVdLNy6shYFmpkGHEQKBFYWtra82gh4cciMxlCGQIZAhkCCwSBLyByTs7WmDcu11Hm6ISN3bRfd981NCUwOdqiS4j02U1VSrioRM+MXP6DvW5MGLPLCo6qxFd7ebHuWEh45Bnn1EuJeDn8adP+PauvbdeZZW2SrUZWwoQuHyTzY88au11f1xsj0s2CEvCtydOcej46zQJnFAGZ4N4UomGjIE6fq7Nc0GMam60rxQb19GkjL4aAsOGjVz12edHRT0a6kOPtG44WpUAIgyQOoGoEML5f9hjnbPWQWJuWiEjXLKuMjZaDCwtECHxpUFEOY1McSYX5eNpMz8I/3v7vesjcxkCiwaBrJUMgWUaAeeNLkszjDu2lytw6N4MAfw+bSA0vERlUqVElzLTpbWxsmdmT/v+xPbmOOT5Iw6sX5gKaKjEQ4qdBp3GSDQsNM2JxJZ3Jjv0WfGiKzfcapsdGGehzC9lCPxmzXUePGXjTb+u/yuMBKY15IQPODsMST3SAJ1cSWjaOOuslmkvl6OHJ059rlN+Jn5JBO4e+NCexfb2UhiEfNKR6DkOUsFehBKJqeDeVAKLcJsy1iam2N5m+HLMNcwUB67YtCxD6OMilDoRE3X0wnxNZAYOGvI4MpchkCGQIZAhsNARSI1MNsNNnCE9N3KG6rlL63YNEUoVgjDHkLrYd3mVQ2fPuJ0XVPnQGEvdaWU6BFRaZZ5MoOHJGD37y09vXnD6MZQn19ph/Rt/X+dbRzMx84sLgUXQ7s9XWGH8Aat9bZekXC7keD0fcnbrRBSdLZzonBpq16iBA06NSAUHThCHYsiCLzU290LmvhIC+g9wnnx6xGl19bX+d2M7sPaIa5UcAGUkLwkiJ7CWpiLgZq284vI7OFsebUzQbET0HYGjB1TfhEUEIimBjiKvM10pqoniMa+/MWD48OF9mJz5DIEMgQyBDIFFigD3ZbanoQhDMR17tQjjEJ7ABl3turzGd5rn1uepbGpAQiUaluwATyqGTNBQUHV6yFlanuXYhldtul32Oa0KzFLOj1x77cFr1daPLsUJQp3snClO+8x54ihbkr6A6duYsy6GdfovmvM5Z+KpbW14r6kp++8mFa8vSQ8PeWqjqTNm1YdhWKTh6A14Go/ez6tKICJgAIYQjk+xWMSGa6217lOD735qvTXW2qaltVgfSMilyxL0BpL+iH+MMjnUeQs1EkhYKrbbm2978FBNzShDIEPgyyGQlc4Q+HIICNKNmBx0ykQgQqpGMU/WQ8Bay5yu9aYrq3uv1LzJHFe2OQm86uxLWr2kLA1dyjSkQUFmS7wl2bT38r8TobXJhMwvGwjsOWDFbXXEdUYIZwwgnOHi79QcLUxLiQyJgyFx/vvbzLAlTuIp5fYtkLkvhUBjY2PfQQ8/PjCKQmvE6J+QsoovYWY9vCueF2EcEFES0MbU37+M99xz11mgu/feG+aAH80lNKzGsJz+V2XCHE/8hE7RV8rXAw4ZYzaOY9TUFcxTz71wwtChQ0OmZT5DIEMgQyBDYCEhICK6N3+MNI0NCsl7l4a691fJp3RdYLquKmBCsXXPprhkQnaO9ZYA7UnaCXS41IgA8zSHVyGG509xy/rll5r/fo4GUkDqUmyxFLofrbba7JVztW+V9A+1g3eVEGPJ+c7Br+T6/9sDCeO0V/jJlWaNg6FRahObmNdmzd23+xf7AAAQAElEQVRm/iDJSlURGDhwSI9Ro9/oV5uPuObKNDAtcaUpT0OQ8xUpaWmuTNG1KxBj4nbeYq628ioH7LXXXhwOzQfWWWP1S9ra2qxJXygjiBRFOGxwISti3S5mfYZE2Rq+IZQgKLU0Nzc889yoE9JasjBDIEMgQyBDYGEgIML9mzcEIuSGJABFzHPpxYLrvP93+5vM1qa+RRoMAtcKOL0pYX/YM55ZALknAOypJjlaDAktiBXD2tl7LQX/knyGc/VPz3x77r8nj4hv/PDV8uiWD89A5j4XgY179z2l7NQggd5WqqVJG1OUhw6cMy59PGWM+9tOmHHNrZmRmUIzX6FzTu4aNOgkyw3FiJAlNPwS4pyoTUhShKvEKh1lws27yrCUJM0/2+sHDzG1w+/x4+2Oi2ns8zazaIzRkcoz0/Apjp+/wQxpWMKxjLW22SZJlJTLUU1Nzg4e8tTJ1CfH8pnPEMgQWFYRyPq9UBHgBQFEBIaGJvd8ygaA0OKidcaQezBDvWRQY9PyLAC/GnIHR9c6bbXLavywvYhSYlmfFPSMmqcuTyumpn6e7AhAa9lis7o+/0zzltxwUnvzhmNnvdPUiLgh4TE7vb3FPvLBuFMenPHWXRzMLsV5yUXpk5qvF+B+WMMrMPGTnNAZEgsKb8HIQM6FAafzRkqAhCo1lsr1yNx8I/DIs8/2fuGl0QfW1NTAJjExjkuOBqeS5WJ1ntLqvAwHI4L2YrtddcUVXv3xdj+vfAZPyxx66KHlFZfv/484LudFTAyW9Tm+Hv39WQvnkpgGJttL6q1NEJfLrWGYM+MnTIiuvv7mXXz5LMgQyBDIEMgQ6HIERAT+AsAIt2dDAiCgU6OSxD3e7/U8BywPXec5s7vYm66szzq7oVaoxmWFqHpqNYO90zRUHM8iGhZAkjh8u++K11WSl0j2XssHh45rnvqSBALDfsKSWxMG/NT4RusHP7xq8ktNbxXnfA2Z+wQCP1x77fZ8LhfGCa/AORdoaRpaJzHnvwWx1HlCGXSMI9S40OBMnIuZtiz6r9Tnpx579kdtxRJtvJx1jjfHFhE5oeaqJKiprIYhYeZGJMKAmxNvMc1uO29/+Fpr9Wn6eMM/3mmXs52NY25k/ncsWVOJWxdfDiystWpgho7GpSfH7zDOFhImR/nI3Hzb3deyTfPxOrN4hkCGQIZAhsCCIyDGQLiHixiA27kPuEn785Tc0Tpz3JatEg3MhNyRo4sdW++6Gq11q2htzrki+6B90c9nyj2BPU3TRe0wEmPOxrssv3wLllA3tnHa+e+0z74sMCYUdgeQYiDGBjCARRiaMD/Xthfue//1Mc/MnHgsMvcJBEJnaJX43+UDrR41MEPnhMaKWNCgdMTVWcpWQYVVnMW56BMVZQmfisDIKVMKjw154uowF4CbSkxDMDUKneNtpkLOFILMdYsqaUXlUgk96upK2++8wzQRRV1T59EWWxzS0qdnTyQ0JI0IDUz4f63OOjiG6XhSZhu+frbLdR9bE0X5+K1x7/S/666HNp5XWyZlCGQIZAgsyQh0L925ZwM0MEXUwiQ5eMetvrLP83hlxDlyNTBpdHLj9mW6MqAl1IXVCQbwHoQdQJ668/Ri3b5j7KAXedh05syLBf7AY/IS51+aO/nZ9+Omo8MgNBBTFAkgMPlAhEYmbMjPiGpl1yKwEQs9P3vyuXdMfW0UMvdxBKzjhOFcByys55wbvpADjUohrmpgCsA4iYUoOlqgvlAWfB4Cwx8YvMKEie9H9bW1EKmuN9fKZ2ioK9Akl5IfBx0A2oNtrW12k4022G+LDTb4gGU/4bffHsn3dtlui7Zi0RqTiwSuqIU4SlzTWgnrhGMbiJnOfOj2YJlvagt5PPDw4MPZHqPMXYb8tGnT6gY9OnTLf192/eXHnnBGeb+D/1je9We/TrbZefdk821+6DbZahf3za1/4L6z48/crj/dz+1/8B/bjz3xjPLFl103cejQpw/S55chuDq6OmjEiIbzLrnqR3845tQL9tz30Ad3/um+z26908+mbL7NbrO/+Z0ftm2+zY/att7p57N/8LNfv73PAX949pg//W3g2eddfvXtdw381ch33unZUdESKOhfZCDlSVxbS2AHlnGVBw0aVHP5dbfseMyf/3bB3gccceP3frT3I9/Z8aevbbr1LuO/udX339p8mx+O2n6XPUb8aPdfP/7rg/9411HHn3bJORdcftqjjw5blXtk8JXg457uN1duw56DIWWenKzO0U4j0bi0NC55+QAl3hgwr2u96dLqHPzvy9EIsCSwj568zIa0fx1EgR7xEmgncNDluTkT3Ie2bcuQhqUenQLJC/sYCOIAEpIMwTUhT3XDwQ2JTZQLMbmted0r3n3eTXPT6lg880QgsS50ljeVjrajE96Gwd9+EVcDYgfv+EoGxLxP4yYrOqVKTFbIyTL/WQhwrgYDBz46mnMv5mdtEDjOS2INFIiiX6dM84+zLNerg7Ow5VIZjNsff2+HZ4UvTb7AxwKmux/tuM0brLsxTpKS8AULqaNRqeOlwyMxy3HMmOHg0x1fI/JRHs+MeOGAV199tcCcpd4/89xLW/3z/Mtu/OleB5d3+sn+c373x1OePfeiqw65/d6B4bDhz4dvjHvHTJk23cxpbEJrWxtaWlvx4ezZeHvCpNLwkaOi+wY9Zs6/4j+r/PboU67ZdfffNP949/3b/3bmeY8NGfLMthwnrpOlD0L9jwPOvejaffc76PcDv7nVrk2H/eaYD8779zUP3DvosaNGvjJ61/fGT/72rNmNK7S1l3rFcZxvL5fDxubWhonvT1tz5KixW97z8JAfXHzdLQcffeo/b9h3z9/O+f5P9x3/++NOHXjzbff/lJgF3Rkx6ieDBz+13WFHnnT/ljv9bO7BR53Wvv/vT275w5//Wd5t9wPfPu+ia49imVx37sOyrNvw4cNr/3nBZQf/ZO+Dhm205S4fHHLMma3/OPviIbffN+io4c+/9Kt3xk/aefrMmWs1t7Su0tLWvvbcxqaNJk2dtsXoN97e4YlnXvi/2+9/+IizL7v+5F/9/vj3Ntx61zk/2/vgZy687Lq/jBz5JV6W1MAiqR2mezznCxkTNKwkahq44SvXJMdDt6vHrUs3Jx4pvKUQawT08OYB0zDPVWMVnvZ3XvYSIHEwzPA544uNroScI3w6MpXu0ADSPqtd6QEwInqgwxhJCYJ8EIRJgPi2995rfn72pO2QOQhfNJwTgqmkgNDQJFZeYiCUmanliKTAMO5otAMUGWT+sxG44447gjfenmDqG+q9oUeDTwsTR+itoqHVGXNOwyW2SG51wxEBWlpazbpfX/u8//u/XafpA59Fm222WdsPdtx295a21igIAiscG0BYv3hZxIQQpoohk0iEpi1cSIe4XAxv/d+gfbGUugkTJvS++PLrb/jp3r9t2++QY4dfcvXN+7725rjQuQQN9XXo2dDDNjT0QF1dHWp5y1wT1SDKRQjDCDnyXK4GtfnaqFAooL6+zvTu2QMNPeoBYvnepGnRzXcP2unw408dustP9k1O//sFU1577TX/60pYwt1Nt9298Z77/e75Xf7vgJZzL77yv08Nf/GHnF/1dbX5KMWgDoVCrYmiyOg8MsQDJBETGhOYXC6HQm0ePevrTd+e9bZfr3qEgWDChMmr3XPfQ9877pTT7t1w8+2nHf7HE2569NEn1+O8N+hGjvqEf/vH+Scc/qfTBj089Jkfz53b1JBnX+sLBROXS/HYsW+uedaFF5+1xy9/+9Qbb4xfozuoTp3lwWHDep938RX7nfjXf92xz4G/f2LbnfeYvMEWO7evs9mOyVrf3N6RlxlPtt15z/Je+x0x+8TTzhl/yRX/uWPIU8O/rc93h34sqA4XXXbddj/cff9n9zn0hOZ/X37D1a+OfuO77aVSv549CqahRx169qhHD13v+TzXek0+DHN+DudyEWpq8pzXBa71Ano31KF/rx5hXz6XlNrrWc/W51169em/PHj/Gb/49SEv3XrrXT8nZgaf4ywPydTEoumoMo1JeK6pfLDCKM3zeirMi3WJ9LlKftkWhEcLjWSeW/KxRzWu9LFkaJrSx9O7Z3yqa1pu6Jx3k1bhpZATHUMOSUV/Dp5qLexTlYzKojEoMpqtz6hscmGAZ2ZNeuKR6W/exMkimrmskhqZoKEJTnohZp5rXGWSeGAEHk+ma3nGDMBMZO7zEHjkyRdPSJIk4rsNRAxEjOWU1HlrAFFOowexE5d3znqDM0li015sxfe33/4hEeHtIz7TMd/t+LPvjwwN5iQ2MXQlQBviq5UxEBEYRg25VOI6ngk3vNpCLQY+OuTS0aNH6yd1LC2O/ak/+sS/3/HjXxz24TkXX/eLN8a9l2+oL9jl+vYx9YU6GBPGuhFYZ0Nnaddz7+AxwO5zATAUXQDk6h1ljgvPBscvWY5X/UwVg4iHVA8eWPX19WbqB7PiG2+7d4U99j964r77//GDx556aon7n9Occ3Iesdp2l72mn/y381556dUx38rz0O3fry+N8QYa3TlwMikaxMJ6AueQ60SWWHpKEmJVJWuSRKe5ifUg792rV0RCsZT0u2fgY3v96tBjRv/op7968q77B23pqAMWs6MO5sJLr/31tTfd9Y8e9YVCz54NNhfVWNG1Q91MEKCuvtYO6Ldc9OzIlzc95s9/vXjq1KnLMWux+HvueWj1Q486+ZGtdvz51COPOGnGBZdcf9Otd9z/o+dffHW7mbPnrGRMYAq1taa+rh51hToaVJGZ09QajnnrnV533vfICudccu0eB//hpOe22un/Wv/4p9OfvPPBR3dZLB1ZgEb1Vxj+/Nezz/jmtj+eds6l1z7x7oSJWzbUF8yA5fqhR48efGnMcd7SLGLI8YWSxlKqNsy17xzz0rnNL9h+rTvuv9y7bX2PevTp3Rs1tXVm5Kg3vnnCmefeveMPdh9z+eXX/Yj1BdVaOvPEJvzyp3Vq3VpvKrMZtkO5o7BQItFbQ7GLfRdXSS1RJdW0k+x7xjT2lz1MSzFbRBOY3s39hNKczUbPnTaFX3Ut72tCqg52woD6exlglMR+Mgkdjvsb55bOmZgFWN4f7IbFSjW50L7ROmu/mya/8g4nSq7jmWVNUABJjgARK4aMEAMNSUbSFEWSMnxM4F2FeTkLPoYA55QMH/HC6T171pdEBCYlK9z4RQ8tEQMIP2eDnBIQCQSl9vbSCssPaP35j3d4DvPhdt1ii+bNN13/qNaWZtZtItG6jVEZJghIBkKu6RDKJMcVEYQRZjfOsU88/eJm89FMty9CvIM/n/yPx/Y66NimewYO3sMYQd++ffKF2kKJ691w0+fB4bQfeSNCY59kDIwRqCEuotgIIEIv5Ph0x8ocDaqERKOqmAtzYa+eDXEdb++ee/HlhkMOP/m1Xx909FvPPfdc30+voPukEjO5/ua79/zuzr98/7LrbrptA2oFlwAAEABJREFU9ty5vZajYdnQo2ejSMB9koej3xQEIh8lJsATqk6xTcuzXh4zPEid0xcn1uNCR4NUMXPO2TAX2D69ekb9+vY270yctPVRx58x9IBDjn54xKhRK2MxujFj3l73v/+79+qGng3stcDyxY3qGq9S2v+QvYytjdG3T5/otTff2O2BB4Zs4ZwTX2YRBVdff8v3d/2//V878sQz3nvo4SHbzpw5a0BtTaTz0Db06JEvFGrjIMxxeIS4UykqTR0BdikIDHJRDvWF2qgP+9mrvgeam1vNoEee2PbY40978Ps/2Wf2VdfdchbLB+jGjvqZM86+5LA/nnz+1LvvH3yKOLdc/379eItezz3VWMc9joMIDhuEa1xEKHcmAzEkMeylEvNgPl4mFGMM4YPWZxjp2dDQ2q/vcpjd1LbueVdcf88Pfrz3U3c/+KjeyAsr6vDpyxWf5Lx3FbLOrwmQkZinpfUpkojA8Add7ExX1kc9WV0aAlWOT3GaJ76EfEpud0t6u/jBoW+2Th8hIiGPBVUv5vAYGpi8tUnfRz7aD8Y4ik4JjgeMMxRDDjCnHWVxsUAiRkwQBPgwKa1w2YSRs95unblUfO5SgL48ERERwC844dyYR/BO46ikw6YxRpG5z0LgxL/8a3Nu3pafZEItIyYgvCYUIXqixiWnsAjzRLfAGBALSk3NzdE2W2113BprrFHEfDihwbTbT3Z+uL3UHicJ357FAJLWz/0RwnY9Z7qIAFACD1CHmiiK7rz3oaO5VvgQllh3zX/u/M1GW+/W/N+7HtghFxi9fStCxPAywSY20bMGoB3AntPgB0TEGHojAsMfEYHIPAKdVIgs9dxEQHKVA8PRyHTW5WlogsCHcVwu1fUoRLX8lPz4sOfW3Ofg46b99czz/+4cG05r6FbhiBGjVt7zV3985Ix/XnxbY3PjgF69eoIvHlGS2Jh7ZQP4viMiEOlMBqJ4KWk6Usc+Epr0AAXxSakSZxEh0ZeUi4gxJgAEsTAhX1uLfn1650c8/9r3DvnN8S+efd5Fv2Z9BovYsU0Z9MhjX5s6/UOjt1c24byx7IwDqKcVrjMxQpkvchJYjmqpJqopDRwydL8PP/ywHovA3XLn/b/cftdfvn3KmRcOfvOtd9btUV/L27o6/St0oP4866xaV96wZwI9lXeVcbDOwDpwvsbO2jjRlcEIOTgiUb62Bj34mfi98VN6/fXMC4/aYde9J/z31jt/w3oX+Vh8EZT3Dnx0wx1/tN+LN9x89yVRFPZraOhRggn8rblz0D2W61t0zMB17knEQEQqNE8GqmkVjjSv8pwFn6NcMoANRGhLuIJOiyDMoXeffuG0mXO/feLJf3v1D0eftDWxEnQ4dw4Uft0nqBTzOB58CavIvpgI/A+5YTtC8uldGFDvrqtNfFXsFQXR3vm4BkwjYzLE/wDCJFr+jKHquiUf0zrlpgltsy7j9UxoLKwRjoKAB7NXN/Ih+0Lve8zxS5M4aJW0iJxGqWO+I+dMcXyrhmtlOpeZKwqQbxcX3jv1rYkvt0z/ma9gWQoIBEAUuLiUC2UB/BwhaORCgmEIdeRW01XO6LMRGPLUs0/07N2TuMEYmJIIkVPiASuGxqYxsWiO8Xm6MaJcLqNvv96ln+3+s0GfXfMnc375k5/M3HiDda8olkr65g0RHoSG24sYSIUM47qRgTooOZ6fNVFNccKkCbvfdtu9q36y1u6fMnLkyNxhfzjxoVP/8s+rS6XWfJ/eDcbxIHVxmcZfwg5wy3AuEvaVKx+cyOmeIQCUNCAeIox0IiFWYNwvDYCHAyXWoZh1EA8P6xIe2nHJ8mbL8YKOdmbJJWX06lmLKJcLr7r+lpN+tMcBc1566aXlWE238M654IyzLv7Zgb//02tj3nhzp379+9hcGCIux7FjH0Eo2OOYFIEYiAgMSUQ06gmCT3XVZKLFLUJDv1frYc+BkMgEAfw8NAFZoHPVcmc2TiwNnILJ5fP9r/vPPf/Z7We/fplj2/NTG1l4ib1fHTv2ZOrDFrjF2aQExYM9EYjh2mE/DMnHGJcoinLRpImTd50yZc5C1VX/EdaBRxx/BW/qb5n8/uQ1luvTYGvzNcYlMeebn+fUGREDXrz4841aO+rP3uigsh+eORqanKeUQ46vIVeyziWWdYEU1kSB7d2nRzR5ytSVTvzrOVfuf8hRV7z66oTerHuxe+dccNjRfznwuJP+8fScuY2b9O7di2ay1TUYeUFg2S/4+SlkSka5QIRkTMopo+JUnEcsw4gIuRGwsIagi0TEkFumKq7WORsncUl/lSRs6NkzfGTIsMe33nqvPMt4L+Jyib6oKPbcK5waJ35JOAgrERFyEnXiYoBhS0Zb8E93XdClVQr1ovoM1WtMOYmvXAKhoETWyYtP75TQjcRXmiYPm9besndOQiOJsQZiVD3x/UGjaMSlgQ6gkuXS8sQB5fJiTENOO5ZjUkRqZCKsQ4HlDZPznJUsYPk2aEoPf/DOHcMbxx+ntS4rpCAI54GID9ltcmIMkmLNBHohRuAZ7bkfB/gUhpn/BAKXXnrdKtOnzyqEQU6nI0Q4iSE0NA3lDgq5ufA2RyLo7hIY09rSWtp0gw2v2+k7m074RKWfk8D64x132O7itpY2cI7HAmFp4YYolFICJYjKXmI5rg3Hm7gkMTff+cBJXA+CJcjde+/ADQ875vTSfQ8P2bHvCv1MGISxjePY2iS23NihG7t1vN3hKne8NQO59s/3UsAfKyJAhUSEYkoU6MXnVZ7i08TL12nhPE+Ux9bayPIwsYk1zroIHICEKkAS27dvT4x+4+3Cvr89fvLtt9//XXwl13UPjRs3rmbfg466+uobbrsnn68p1OQjU25vN9TfiHUhe2xBzhZphHC+UhAI4LEx5ErCqFAmoZMjUOx6uitQ1hwtQSoBYsQY/5zhLWBl3sfiEw0AquBoIHAzaqDhMHHK9I0OPPyEaddff/MPmblIfFtbW4E4rGGMtJIbjiONNsejHxAAIlzD1NcIXxmFL4jkQWBiVbvd6F0FFop76KGhq/9y/6MeHPjI0IMaetTZmqiGN3axsTYxEEMdVTsav2nrjBNHB+pPo1PHgYPCtW3J2CVH4hAxwjQ+oetD5y3JOcufomW9zsU0nqRU36MQDh323G/3Pfi3LwwcPHhzPrDY/KRJk2p32/2AKwYPeeqqXr16NXAOleK4FDprucbRKs5pn40qSET82hahBBK5cLxEBGJYRMgrhAoXmZemY8wYsxjScBWufq0XoOT8Guda19/pdkiSxJaTGA09e5k5xQ+v3GqrPWtvv/32SIBfJ3z5tIo11Dl9miQQqZKB6iPUjf0BY1qwS8l0aW2szClpQA4IoCQVDnUqK1eShdIpdIEb2TSxZU7S/l0amKHja65IqjcBiympwam/NONbcs75VWU5ESwPF8u4TyO3DkXGQwcXO2LhgAaXcpYG02C1PitBzHMjygUBnvrw/bOGN00+1Fe+DASGeMAJDUhQImf4EYkIiQV3JoFTpmV9GR9lSuY/jsC9Dz02sVBXYzltY4WK848wSwgeWUwDNxTeYhJrkVAC0xxw3jGGhoZa+6Nddj314/XNT3zn7bacuMKKy13a3l6kgQCOmOOR7UfMDxR14BhqnIwVCnVxztpCIW9ff+uNfQcPHlxgcrf3zjm54KJrfnPEn//2yoezZ8W9+/SMbFy2Luam73iLY5UsLPvmnOWLpWX/bcjT1QoT2HuOC3EQcEwAHQ8BIP5HeWdyTHWAh1M5iXuM00/wTo1MknI4bjWWxD3FEWnn/3csE/NmukdtTehsEh196t+HnXfBZUc4xwXEGhe1HzFuXMMhfzztyaefe27/Xr0b4iSJI8fD0bIvIEJChag5DUKrc0djEePMYZ8ZIxDwWGlBpvrdQDTPQWUlrccRK5WVNM7MSLSc0NAXiYm6Ec57roFQxBtsHA8xLE/DySFOSqa2tiaOonz+nxdedddJf/3n1cTMqAoLmUwQBj3YvYJQV1Agh4gUYYQ6QmUIjRQl6q9xrl8p9AjDAF3s2Gf59yXX7HTosSc9PWP2zJ2W691b1zUc5xvgWkWE7bNRQ/0EBkJ8oc7xjHSAs5yPOpQ0vjhegI6rJxby+WAb+lLEMYfKWoo3cQ7OupjTnPMjQUNdbVxqb1vzd0ee8sz1N930fbcY5u9TT7203O77/37E2+MnHNzQs97EcXtJ5y+c70fEdc29y8Xg3LNw+p/RGOYZdhTERT0IVwcZRkQEgPAH3mm0g5jiZbgUS8aJioZpnb5dokQ4HYFSqVwuhTX53K8a45lTTv7n5ZOmT/+gXxgYWM3X8nwarFRnuojAcB4ZQ+5lcsoiafXoQtf1NapyVBoQVN08qZoCaBElowG6j5vr5vZ5bu6EphZbLgRcNzrrqb9RDTXgXmUYBxiIblgswGnGkNOLA8kt3w+qdQ6cbEx3+Uo+DxlmMUIfsyjzhItWjAgMN7gwYRWco6EJjBk++/0rRjunb4VY2p0QTGEnRTnBoWUC42UB/HmonMiwDJhPZjSfPPOfgsDtt9+zyRtvT0BNTd4kCY0cEDvRg8BxnoG3mQLOOR6w5IqkSL3h58r29pJd+2tfv2+ffX784adU+4VJG2ywQWmnHb/zn7lzGzm/OVA8X7jR0lP2E163yZSgA8lkZhpwjMulcuGWuwZf/IWNLOYCzjk565xLfv+vf195bY+6WpOPakJbLsNZyynJDjmkfXfsp6OR7bn1n7fYZ0P1je4hgECdkIswFAORKqcMyh0E8Bk+DpKvF9SjQjRsrbOgAkwzAG+EWMx7qgOIjW1iA+b07lGP8y6+8sLTzzznvLQsFpkbPXp0dOQhx77w7vh3tmiorzdJuRw6G8NRdcBROyorVo1wGhlc/s41MzNFwONCPDxn0SpWaS5EpKMfotVwPmmCpoqI9RyiP/pw6CXhLalQSskIy3GoOGNZATGz1oUiQI8e9fn/3Tvo4H0P/MOlji8QWu/CJGHbIgyBUNiQqNZAJIDqCGbFFMglpKzcBsQjSSLqzge6yLOvcvrfz9vmnxdefgs/i6+Uz0clZ3m6+fGCFTEFIWYkbTGfcvE6CPhlBNxnQJ2hDxBTrgVUCT6uz3Ho/XxWQ1Pjhgk01ISy8GxECWyD05smtIl7FGrDU/563sPn/fvK76l+LLRI/M0PPtj74D8cPbppztyNaqJciXOX195JxCXHdc2lx4njYFUIxTluBC7P89xSd6+foPIjQkAM9EeEaRw3QxIJYHQfZo5IWkaE+aAMCSkZATFnIwodcbQAiuQGTKAeIKlcjJMErLNXTS7oT7WMCJ8k3KwKKooIuaTtedkwTo1EOdPR9c50ZZVUP/Yas1LtF5n3nWVNYDkyqRBZN/HTWuduMXbu7OklJPkg3fd0MDmMHEouEO0HSTFTUq11IZA7lknJcmT1ISWWZTqzK54l+BxT/WblOXOcPgKdnESEm22NcokAABAASURBVKwg4IBblxTbmqf9BMuAE6Q/CpZK8HENlYTgwzqmEVqAHP4QYToy92kI3PXgI/+OohzXouZy1jlv7OSdAujADV3ThUiSuNFwU9IEG4Zifrrrrqdo5KvSLtts+XLv3j0fKcexYXOWs9tqu5ZnjXJqU6laDyRtn0tCnKmrK9gRzz1/4Msvv9yrUqDbMeov55x/2a8uuvrG8/v07GEF1J2dBJ2QdKKSuCfwvIGjyN46WlJwEZ8FSa0qX5JBTOjBQeBWwad5mArXPYnpAhEeUwLm+WosayNpfUSUbbIuS9I6SWzPcsNyms9nnAdbH2R7oKP1xgocs/ot1z+86oZbDv3L6Wed7pxfSMxfuP6RUaPq9jn42EkffPDBWnWFWtg4oV6WjkaLVd0pkjPknHHsD5UV1BMEVczjQbkokuJiKlhBNJvkfH9TPIg7a6iE7LCvygPr6xERiBg+JBwnypQIFEPiBx1Qxpwz+ie2Yh7YCVXtWV8fPzfypd/9/BcHna+f+1l44XlxoRFQR6kQAIEmoeKot2OSY9SB3TEkGB8yqQu8Y//P/NfFO11x/W0P9enVq39gAhDJCESV1XPSw0AowWPmcWWM64F7CwVHojfKOcVSzgQfB1/CLGtzHCUSKCoxVi3h5zwjytkmfKuJQwiOe+8+fcx5F10+6PyLr9yIZRa6v/XW+1Y89qjTJ0sg/U3Ie6LERVaNbc5XZy1vvi3nq7VOf9gfYmfJmEa9tcMcHKHeYoTqi8fKx8WA+64VQ26YboyXhelgeSWWAwSsiAQPlIFHg6HTyyvnX2jZpk8nzwsf0GYp+4e0DhFAhAGUQPdxniaJaLrCzngXeirXdbXJRzohhIPke8w2lPt8YUQ+JjFpMfsJxTmHjivNGh6LDY3jSHt9dRGpoFRRUDjAKvqZpMI80lIpzVsyaa6mAiLkfB6dXJrEdIDTECHHmW9CEocWNHSxZqeiS62ok1BxAMSDy5BcQxKhcfDjYeDSOOgoMcz8xxF4+OHhfUa+PGbbmnykv6tD69ySeJg7bh6cs9WZ6fHzAYiusXGppH/XrXTwwXu9jQVwO+ywQ/ztTTa8uam5OWZbxjrdiH37uhPrpmiZri1w2KkUZz3HtxSGOdNeLOLm/z14mGZ2Rzr/kut+/O+r/nN1r4Z6HngwfDNUUKkq9zkhUfJ987iq5Jiin61dkRPakEImxCT4IoAl5yWG8KaRh03giV8yAog/fIwREYhU5z+PMh1D5zkNMkLqHA02sG4wrrLjSciXClAGQojmMQ3Qg69keavZt2+/wrU33nrc2edffoJzuqiYuZD8yJEjc8ccfNxLc+fO7ZWvqTHWWuphQ+phHCOWB7Z1iXGcCoxqns6bqjZ6KHNPlCKB4AFqYqLBLgmIDIIgQGAMZaMOQWCsMUIiF6Pl+JiQeIALczzxBlNzRKDOj5JjSAJ1cOSqh1UD0yZIrNN/j2R69GjAqNdeP+ykM84/0jkX6LMLh8Sqah2kulYaSmdYGvEzS0XtBknoNNoVdM1N//v6lTfc8kif3j29kQdiktbLhlSoMCdeCDmzYu7fhjpx7FDS4pSZroUpOccbP08syrnoOD+dgm3T/UDnMFhOi4u/BYWIjptAhIkMOHQxxIRgvG+fvuHFl103YuDAgcszd6H52x99tOdxp/79vfpCbYGzJ7Z8X7SOM8J64y62zvI23pbIdf6GTrvkO+/7ohNQ1eV4UmkRiPgXm5h9MYExnK+B1Tkb0gVBiCAMEASejBExEOH+IjE7qJiyfoXPz1Wmex1CpjDbp3mucaHkiYF4YlBJI/Ne1VTBa6oCScuSdbk3XVtjtbOdamX/PtKRjizNEOIoHSmLS5jiGvtNKs66QoTD76REPQwEPBhApiSed6wDqBMNOoiLjGcOfDnH0FWyHdJhV45qIvPVM0u9AYSbJ0MAjBjDTKMSpFv8izqqlfqFFEqlXo+hygpWhRyxgNNELUUihkKCx0fTM+qMwL0P3XtJsdiOwJjQ2aTkeKiL4sddxZH0wOAqJaSaCKIrEIG1vLH5wfd2+jm6wO2289a3m9DEiW66lq1RB2f57uSqh0ulETGWrcMYw9tVF/fo2VB6/Kmnz9Q/blwp0W3YLXfet9FFV157X6+ePSMThP5XDqhcehYY9oIgigiTRCHWRU+ZfVfMwVsHp8YVDwZYfzCwaBiYgF0XvgzEKBaLpq2ttdTS0hKT0Ere1tKC9rYih6bs62LhElswgDPw9YEyjVhGOJqWAxmCBUh6MFmmsShD9SQ43sLwSoh2ZqlXrz75i6+49i83/+/uhfaPKTjfzJEn/P2hpsamderr6yLOgSI7UqIq1E8VZUy9U8OERjGx4jNqJFNVlgINEs5URvQTOowRxayksLGuUnux3ba1Fm1LSxtIRqnY1m5KpXaTJIkVjosJAksyImLAJslDjxpgoXVr+yS2y2YcWG9KqgsRZDxknimXS6UePevD50e88I/fH33y/prGx7rU6//6JKmRDOoJBiR4J9TdC9SZqnmROkCJAeMKLdkC+mHDXu197oVXjqwrFKxIEFar0+ZVB6VqGlVRH1OfkMgRTsd5SdJU56xznKQqw+n6VmKa5W6ga4Evn45GGh/mg8SYyaxYRErEACQ1xkgBjPFkDDl1ag3CXKm+vj46/Z+XjOAjC8XffrsL/nLCP17O1dACDIPYWepqXYnq0qB0qq/2VdvWfpFzvjqdUxS9dyBmqS3h2AWRODCcegwsYWkvtVuu97C1tc20tLba1pZWU2xtizmn9aYfIlIMjEnnLUBjn5jBsn3iR9wYUgen/0ubby0N2CKUAD4P6fhhHJ2d6xyhLJ3o43nMWkBvFvD5jz7+Ef3EdxE+FDLxEuhUIoOIhoufpjbNnUpldBR184vYDW6Ezm9sqp2qKeCPQEMGAtXdEwBB6lKehrq2XEdOmg8fZ756BzoKTBOIIUEEVrnhG4+IcO9wXbNzsKXu7LXP4vGglsqd9l249ygxDRXOdKisp4SXFWVkrhMCw5554Re1tTVxQqORe7zfAB13Rk+Ey/NqnJxwlsrlctijR13rSX/6/aBOVX1lca+99krWXm31G1qam2JtTzdV5SRuzDQ2AauVi3DeG8NDhdsQ53yYy0Vz5zSaka+91a3+1w/98y3nnH/lswUevIa3DRCJqDuMCai7MJoSUOVInZ/LTtcxDwdGnAvF992V2tvb7NzGOWhuakJNFJqVVhgwfcN1175mq003PX67Lb993He/vfmJm26y8TFfW33lf9fW1Dza3NRcmj1ndr6t2ArhYjEB25Z5OEJ0UYCHGm8/iCUgaiB4nKHOOavD7ZwzPKCMGIlr87X5M/910fNDF9KvKPz6kKNOGj9+0ncaevZQQ9qKBHk4Yue8bqoVBx6Kjc5TGpdMSpXkXuzxAsGDsJ+KW6lcUsyiRmIWMHGN1VaeusmG64/eYrNvPvztzTf/z7c23eQ/66379YdXWXnlcfV1hdZSuWznzm2KS+3FmM8jDAKtS9uMIVB8/P5KTNiMjpM2WyHaPI5ErGCThG9hLiqVysW6Hg3hXfcM+ve5F16xLbXtam+N0Mhi30RUY3YfxIGtVHWkohojUz2psyU5gIkL7NmGnH/pxecW24phLspxPGzMhghVWnU6NJSp0jzZsRxfnjQBNDA5x/kM551jV1iW3RAaWBDh+HPWwRO1hp+PLMHx0DxvULGYkHgTbbwLjQmMMUHR0AnrEWMKDhLV5GvN3KaWVfY76Kh/sY4u99ffesC/OHdWKRTy3LPikNrGYLvsGzgq1N1Rb6ciybUyXQeEZZnuaEUyS/hSZwTQHpd1LjY1oolzNxcExdVWXvntTTZYf+AWm29y2Vbf3vSSTTfZ8K/f+Mb6F6+x2qpP1tXVzmhqbslzvcd82TTWJbqu2YQL2bDyKuU97JU5ItqWJx8QR4H/YcY8joqjgpXnOIPACn26DowXujAgUF1Ym69KGCqR0Qu7Sea9dguMd/BUwOJ2Cd8MxMFUwCgKEJHAsVG5ot48ZYWD4/OZIyJIf3yEMnm1KLlOAiU+wgx8JJ+PQont6lwE67GC9OAAHWVmUVjaPXECe59ixF5DnSYqUfaskq7GpY8zPfMfQeC0f57/u5kz55iIB4SzSatzjnsS9z4elo6TMCXGVWYauBeCtwytPIS3326bP3yksgWM/OKn2x0bxzyjdFAdrGVbTmWIn9MiAiNijJjISABKzcKDqFBfwGNDht2ObuT+9Ndz5za3tUdRTa0hopZ6gvrGIhKxC4Do3BQygToRcnpiyyiB5mEj0F3Dobm1xTY1N0fL9e538o9/9MMV3xo1TF54cqA8+sDNy9/+3yuOuOGacy+86rJ/nXftleece8v1/75g4D03HfPC04N2fnfsszW/2Xu3YI111lintbX4AI1Tti0m4E0dYdVB5SEI3nhA8S1RBSNCrEWoA32FUVIf0nAKa/I1aOEtyjmnX/g458ZHS2ipBaArrr3p10OGDD+233LL+d9hMyagPphnRAGqr+qtOoNTMmZEDU7KzjjOFyqkWNv2cskQM/Tq2ePcPXfbZfm3Xn7CvDj84ZqBd9+48v/+e/nGN99w0a633nDhAbfdeMkB999xza6PPXjLOs8/9WCPN14cGvzhoP/r9Y2vr729jcvDi20t7JErGcObKe4hbDPPiantkSwnaUqOn8lTspoeWxakPozYvKPaDb165C+98vqrBg9+ekVW2JXeAqLX1vCO7XJsqQNXDmVHssTFev0sHKcWx5Fc82vYI//UVw4uvOI/Wz838uXf6K0z8YJz6a07a6ca1TCd1WlIHcAbaL1xZuvOUuZ846yLExrmLa0tZm5jE+Y0NmLO3LlG/3ev2XPn8ra+ySRx2dBxDRk19A30ucAUxXA/YIZQIMUUYcSosWkN5xBlGCM6VjT4e5gRL4w8+rrrbu/SvwF7yunnHPjiK6OP7Nund+jiRHXUeagviMTWNbLvhqT40CbzuBTYd1TGxlhrfb4YxKVSqZE4oE+vhot//uOdV/vlT7YLnn/y/toH77ru65y3P7rhynOPuO6ys/9487UX/O32/1x0zAN3XrP904/dNeCtV4bKr3Y/qMfaX1v92zRQX2psaoTlJwgAXBts0zm2zcnIBE4LQFBxqZCGTKoKVc4k1d1HOWbsBKO+PtZvwdcpX6IrA4LRldUJJ6OS1qndmCc7NQ6IhCNprtOARTxXeTGSdR03hjqR8lTFkgg+VE411kXARDKIyDzyaZW4yiTvP6VjjuhonmgA4Q9SqtYH4du1GM1xmmMc32Cw1Dvf38r88BA5dplxP2dUViyUM62a7zTOYgvml66nH3506N9r8lHsCA6pAGeNs3oQkBynNLclpjM5jTumlculOM9Li21/+v3buhKNQw89tLV/v3736ucfjpkhaetWh5LT3QgNShJI3MRFD5J6qmrztbV23Lh3C3fdddcK6AZO/2j48y+8aupynIXJAAAQAElEQVR71PPAiWEgBuCMFXLeOkEoQ4CPkG4d8I5DAccyNOQxt7m1ceP119t8/z12CZ567K5/XfiPk6f6QvMZnHbaafbRu24Z9+6YET/51q7fLSTl+LhGHt4mDONA+C1OG/N1GRpuQo2EMQGbt0a8vhC9DdJU0Qu6OO7R0GBeeGXUxmf+69I/MblLvP5L8kuvvmWXnn169XLOGWMCKzAcZ5oHovoIANFJaJiPDrKWBqnlrWfZgq49Lpk5c+baAf2WO/K4w/bJD3vkzj//4x8nTxfhlQDz58cfc8wxbbfffMUzo55/9Ds//vnP+guktbm5kfu6wAElpwYb14G1jnqoShY8DyincQohiQOqnyZhbeIQ5gLu027tf1148el6yz0/esxnGdVC93xVwaqC87BxbJxJXCRO9XU0ByhbTwmrV1uNbAH8jTffOjif59RhnXCWb4gJ9XBwnFdKWjVFZSQhAQxDTXM6JMaEbcV22zi3MQqApzdcb91v7P6DrQsTx46IJr35vEx644Vgq41XL2y48YYbs+hNc+fOQVtra96EuTgwAeuSvEjQKiqnt5ehiM4bCQXCKSwGIgDnjoNEqpP+nu9//nfXI+gip7+q89/b7/te3769I3acRm4QGjHqYxEhmQbA6xA7QOevddbyRSQxNKxtwhdrThaU43I8l3N3heX7n/23P/2u5omHbj/urNNPmKhrGPPpTjvtwOIDd9/wwtgXh261xw9/2hCIjGSdVCs15imE1AEgLFSFXDyJpBwf5xD+sAioIQdN8fs42cQvPZboOm+6rioqrqvCVyg+1EB8tygJqcNXIk65UkfGYhEINDcdLiag6EMHxaWkmimpUsql2hdNUOIIa5pQFuYJOZRT0PEFnTBO9gkvlRSp5JNbfUZJkwgNx1vmVIotG4ydduy8I/dTybHbVZnpjHFd8XDQNPCQ0oSMPALX33THNhMmvt+rUFfgAehPCWLleGhxXTrOak/pIQUeUk6LEOTW5pZwk402ePDHm2/e6ivqwuAHu+x8YrHY1mzCEDQ0eEjQ2BADMUpiKxwihuuO48obEcstu64+b2+76+FrulCVr1TV0KEv97rxljvvaejVw/D0gJ6jEPh9gRUaEk9khgJIJwKdltU0TmEze86c5jVWXfU7N132r773/O+6UV/moGFVn+rvuOCCtjdGPX3ebjt9v09ry9xHY45nGIU0UBzEuFYR8ToRdAgMxDAkketgAOAggIc350Lf3g347+13/WvkyJH9mLHA/rxLbjiwubV1r6gm4m2lgyoghkwEMBIxXiRxnnJucg466uBsEoIHnHNxLIExc+Y0NkdBeMfhf/5dr8cH3noxX1rm3fDhq7l/nnTkBy8+PbD3Ljtuv2Fz09xSXG7nbTDU2uaSoNHm9bBghFqRM2REG4upb4FdMAKJuTGjrq4+fvOttw64445B62qBLiIHkTiti2uW7Tu/bimTg+TjHGvHle2oryU5xtvbRdLnvlr4j/MuWWfqlGlRPl8D6xIaUM46p6PG+rRmX72qR9IkphmfJmCpZqqBNl43r7HKCr8+94w/haNfenKb++64fuwFnKfsk+Mj3t9xxx3J/bde8+rrLw399TFn/rlhvXXWPqHU3tboWCLgyy4gBTECEclzrlgRzlHFRECnOEC/0NC4syaxiQ1zuXDStClrXHjljVuwwAL7i66+5SjOv72jKOL+xLYDGrnGQIwJxShx36I+IqDhqzeJTnUJneUgWGsQoNjUNNfWBuH9R/zm4AGD77v573vttRfX5YKpdvbZf24a9dxjW/3iJz9coVwqflBubzdhLiQ+jvAKPGYpbmACPdOkQgAE8OQAnUYM1TNG4B3JOgvtAg1lzehSMl1aG7tBfStVVrrFfoDpcJU4UiealoqLPaSKXLJUw7k8GOEwMMLNUHWsDBTIIaAXCPvC0Mv4iGOq+AoAlqcnp1eBJCLwjoweoj8CThSmCnSK6M7GTUZAZ6wzlCkt5V6IA4ipq3CQO8Y1DQ6pY9ynaYxyR7rGM8IDgx7bNQpzxlTnGDEhrkbIvVccHQMSN0RuNJY3Rgl40Noff2+nI32ZLg7+ddoxb/fu2WNyqcwrKWM4wQ1EhGQAURIAosZGHnTipJQkNoxq8ualUWN2fOSRR+qYvNj81Tdef7vjXDMicLzx4r2F6hJxf+hYl9oDQMN5JIwL+1vihVxzc8vQ/fbbZ83HB942XP/lPbrYXX75v2b/5fhz9qipyZ/U0twS5XIRjAQFIyYWYqxk1EkAMivUyxgelIbjwRtGQMBnTHuxtfTvy24egwV0gwY9uuUTw184sUd9neXhxbElGmJomEtIGNmaFNkEb6xgePtlBdByXM4OPK75yTwIZ8+a27rFtzY/YtSIR/c6/te/1m/cfKTr/EXnnjb6tusuqK8r1N40d85cGF5MUleOMeA5m+JK8TpRpM76e4fQvZkyworOYX2hztxw2533Dx36np+/WnZBSZxjO9q61qSc5EiqDbnrIEtdSWrbME1LLwg99PBjL9fU1IRaP+vh7S0iHnTUBeyzkADDkPMKIgLPQR4EcXupXG+MXHT4wb9ffshDd95Mo0qvVvFF7pi99mobeM+NZx113O/WYtuDiq3FUi4MtVYrYpS0SdWBN3aOt846PrZgK477GF/+rI0C03Dnnfct8E387bcPXP6V197aqaG+XlVnFwPLgMZlwBtNA79+hCqJzmVDw1EUJ5YVGuVU2MA2NTbZb2/+rZ+OfPaR3Y8++sA5zOxSf845p0278sIzV115pRV/19zI9/cgQsCbX4MAQoLqZ7yOEBFPDKCOK0yZJx3nlDiHOOWIJSytoITkC3RhYLqwLgj4ls+OsU5ODEfGScFEFdJksIwmVEgTncpYrI6aehzIK3oI9WRMKtHOjMkd0Y/nM+74ZJrvPGOST/kEZwK9ltG27TyZExfgpAUnOLp8g9UGuxs5zgGncJGUK83TUbgKiA7zxOlGL4on5xdX07xCy7T0wNCh/Ua9NubPdfX1xEE4l4zxKImzoqSIMYeexhGBBHjQi36qsuusvZbde++fTmbeQvE77rzdXm1tbZEJwlgMbwIkKIFDJ2KM8pSoswgg+mJHxk0zVxPm7x887HQsJnfPPYPWHP7cK9vVFgrcfBPDMx6Osw6cnEQw7FDLqy006ED1laRkwtDSsOZz9upTj/3rj8497fgZWIjuwAN3KI4a8chZa6+55qGNTU0IczlIwCORB7bRm5hAoSYRc0ODzzCRFBt1QeA1a+jZEA1//rn+Dz7y+GY+4SsEPLSCa266/VuBYBVjePoRKILCOacGC4GCKII0MD23msIiRjmb4/EmYWtzc/zr/X71jTv/e9mNTFtofvPNNy8/98QDB6y7zpq/nDnzw5LhnHQcYOrjf91Ex1sgHe1TMszjBuR0ChjLl44wF5rGprmr3H3vtft3FFxgwbSxbd6O+Xa0LUZV1tZJOv9ImuHZArcHXH3jbeuNH/9+vrZQCxEhsZscFVG7yRjObU0jN34OwaQ81pvH9vZ2rLraKv+49Ny/HHv88V/theDwffed/eKwB3dbbbWV/97S2hIHYWBoOfiescfE3bK7LnJI4Ig7yShZGthJEiNk+fGTJq576aXXf+U/aeSckxv+d9vuURRuHwShgUhRRDgtDIwxPIs1iQso9fB5AjDKPNAQNXFbc0vr/nv+atP/3Xjpg1iITl9WHxt421U7bf+db7W0Nr8TBiGMcA/iuJBbEVVMVEdde5BOurCf+ChZOOLouPqc57ZT6a4RTddUU63FdNKw2jXlJBoSUumuVItzBlXFxclVDSXVIeUM6eEYkOg5512qfVV5p0kM9CGlarrKSuyvsipptrAGEYYkeFnDlAAu7JS4qeifJRFA3FwsA449rUIN7XxKTHWdyOMghASKXFoOmVMEBt475KQ4toaHnhqP3Gf8xhgbWhpC0jIQDXlXRMZZm+exZVtb28z3dthxF6ElyuSF4rfdbMOx+SjyN5TUBxA1JIWbn8QCgXciHfsQdUM5jotq3D0+dNhh+qdEfJlFHPzntruGOtjI2pjGIg83NUA8UUPdEEji1ZcSwBdDEe0TCHfEg9fURLl7r7nojMMPPfTHrVgETkTs4Aduuerbm270f3PnNCIMczz8BMYE+o8mIMYUSTEpIpWMd4GmAyKlINDyAe66++HH8BXdHfcO2vCV114/uVBb640wLlIrjreAfGkmVBYCI0IJnnPMhXHue0YAMaZxztxZJ/zxwN7/Ou3o8VgETojZQ/fdfPtGG633izlzZltCwlYd9dLPoNQeKHG00+2oErIAJUcCEh7KPerrMfjJYeePGjWqK27dedTbdm2T7ageZPQ+gW1yznmt2LpPImfuAvsnHn/mOuvEcqqAmAAS6AsIODqhiHD+GEtsQiOe0/gyMLRsiu3t9murr3HT4/ffeqoaPlhA98g9N/1tzTVWfbyFL0psy8A6Lr60345rT41KS66yJxqc1iXGERcjss6zo1773VdV4YYb7u35+uvvbFNbm4+cs+wj/MsQ+6+YRMrZhsrMMzHEqGFZIkYEw6DY1hr+7oDfrHrmmUe9+ek6dG2qiLgrLzl75M9+tPNuTU2No4MgDLmKqJ/hvPEGMhsUpmlqZdYQJybyrJ2HqbMq09Akrlbz04mlxbqMqFCX1cUpb1mZdkqJIoQ/5EKi1z6QsRw7zc7Qa3Sxk6qnpMpKVfBa+YiXwAwHxl0a5dB4oRL1sgYswVJCAnjzlnLMc0JRyWf4QN/qheOgBAKoNbNWcVxk0sbiS70X4qAEdpu+o78qK6UJwklDSRM4kRhjRJ9QtuzSmDFj+gwb8cLRDb0adOPLB/x8ZYIAPLP9Pi0iECFBAPFzTXlcKpXNCgMGzNly83WfwkJ0+unsW9/c4OByuQzqpjdG3JhNCOHtFnS+g6IOKnTz425Hntg8lS+1l4qFV8eeewYWsbv7/kE7vvjK6wNqanI0JGJYm5AsLDfiyuqsaERMwX6kCz00xD6JE/ZTnvrLcYfs0RUHb6Wh+WZ33Hz1PRt/Y539W1pbTRgSZiOWc6EkYnhompAcxpAHhhB7o8FSbx6srljfsz5+buTIXkOHfvnfzXTOmfsHPfwNjt4A1h9zMLmXwajiAgFEeEZ7ruNfAti2BBAJ2Hxgi8W2WYccduh6RxxxRDMWoROuiUF33nT/el9f5+dNzY3+T+Ww+ZA7S8y+RCRG0/lJAVL9EYFu8JzTttjeWrjmhjtvwIK7RCDNxJLwsbKOZoWRzsRo1fsymldN+PJ80vRp9TW1NSHHDcYEfOEIIsMbTGMCnRucPxwnYyC80RMjeRMa6mfjHrX5p0846sBDhBh++VY//YlBd/1nl/pC4dH2Ujuhd6GuPUdj3pOuvypZdpznAMfJ65LP58O333p7/a/yD7GItzw45OFd2a+fGjpW6H9tjqga0fEWVG8HSxBdRzTe2GcRiQy/rRM6RwAAEABJREFU0DTyBvO3B++/xoknHj7703u18FLPPvPUN3feadsDWltbxtLuVz2V8oC+9PKLqBBG9gHeCbvmBR+k8HFHI5S6WH3iQghMF9f5KbpqL9m5Skedo8xO+XbJHcnLizWgElSTvqIFgfcS0yt6+yhntOOBUs31I8YOaKk0f14oWpkP0jQf5fNprBJqohdTgfXw0wAUQ25wgBHX5LOX9oAdT6ERrgidHymBcwUef00H86ok3OUoZx6Dhj67c2Nji81FEQ0IgfAw0H2S3IgwrmSEBzuNDejmiJhJYWtLs9126y2OWhSG0A7f/eZtcbkUczj1Fo0HN4rp0Dm//3CTr3yi5E2r09+9ciiX47AmXxs/8PCjxzI/TMsvmvChRx7fq1hK/1GISxIucxqYetDpWicxwSsiPmTA+SvCOQsXJqX2OUf99pzvqXHNnMXi77vzPzf279fzovZSEbTgjHANiYZiILyNEgYkQMAEY0R4MwMpiASGuOP2e++dhC/phr344oCRL446tqYmgn7ChLNcowSG9YjAz02AmojOQaPzwIgRBDSEm5rbzK4/3HWHvx73uw+wGJwIb4EfvPXB/gP6n1psK0LEeC10qMEu0MOJMI1ELiJIf7SLFoXaAp58ZsQeLLCgPuH50ghtkDVVGNgYPuqkU1RAdZCnWdEpcb7Fe+65p9fkSdNWLBTy4ICokR1x/2g1JghJppODGBOKMQiCoNjW3Fw86rBD9l0Y+8cRBx33E74kWa467vkMKXkjk4Yl9wL2TU9gJUDA22aHMBfk4hnTp6/9wAODV8KXdM8888wKr776+pF5XmPqP/DzbXDwib+2SIIB5wggkYhQJBkhQrm4uakl/MluO+984tGHLZLbd3yKu+i8M178xnprntza1tJoqBfnjyWF1D9kNypPSIUTUmZoHxVBiizKkAUZdpTpSsF0ZWWAdkQYsloOBtR9muZO2FPNJGdplRYvqR4VlaqKdOhNwWeTM8+L5J/wzK7mdeZS7Z9U62fB6sMcWGYTLCYwn96IiFEOiHUSzsF8uCFvt/984Nj43VemlM+dj+Ldsoirzgnl1JAYMARo0xuuAhremiLEynNoiCqkFJZFz40ifHDgkJt79mwA500s0B+EZDHjEGNoXBq+2ZrIiMRiBEaEGDpbV1drvr/vz+/AInD6L4PXXX/tg3hbZbkJWgH0SCw6LgX2ARXyhqY/TDjgSZKYMMiFs+bMjq69/taDFoGavomRI0fmnn/xlQPrCjWM83RjSAUZckumws6SU79qqFNQBCUW0D+0bH+5797rHXro5mWNL0565rH7jyaG06m7zgU17C2E10BCQx7+RRag4hwLvtBKCDqOg6nrUYhfemVUpH+GiEnz7Qc/9HihsallwzAXchwtDzciRbwAtgkONDmbU104BYVNS8xT2v/u6lpf+9qel53z1zEiXO3z3WLXFmTb9tzT/3RJOUkutM7pWgmpKDGjrlACWEbXD8mAxhcpYFpgopoa29TaXDrzX5cs6B9op3Hgmjxa+KjTNGJJJFVS0nzqJaAOXNL4au6V1949vRQn9VGU52fgQG+7IcbwhcPEfq2K7h/i2xAR3+disT2/8YYb3LHffrsvlN/l1t8x3uQb6x3W2tpqAcG8foMxjQtEUqIQUeJ46a+YubXGvz91K3xJ9/jTLy3XXipvFPCFxybWOueJzToPrFTqY5Pg8USchDiYVn6dMetw7l589t+exWJ2/7vpyvtr8zXXxnQiYnSGkPyv6pBrXzppqCkVStdomqdJ1c6mKZ8XzneeB3G+S39RQYEeICzleAlHBtWYxJHR0ekgzdI8dkqU+/jiC9IDI22fKkGJo5ImMBRNSRMZU68RfUo0klJVVE7iQEMJFRkUfJxcq/MEOh3kdG+1EBZmkjIRMblc2M7oZ/qJH7SsePuLxba3p+HuKXOSNZ591x127YiSGzXNbfCZD3XHjBROOD9PwLOQOFAWEjwhHQ6VoXnQEMu6u+Hmu785fvzkuCaKDDfHkMhYxUQgIeePboaRCD9LGgMRQ1lKJjCmpbnVbPyN9S/60WabLbJfx9hrt+1vb2ttNdSPOnLtWJd3zllHO846fhZzNra0R0iRppFia5NSFIa4895BZzo/Ofj0QvbPP//q1u9Pm4koygHU1Dfn5ycDRwJ1J6c+nK8aRwmCqFgsxht+fZ1zT/vTEdP8M90g+MVeu286Z85c3kgZGi/gwUOAqT/g+OJG3Z3GYaiq5XyBs64UmFw4ZcoM89prb/+c6fPlnXPy4itjNwzCwP+RdzgXO8ebTAKo3DliBo34trTOkuGNixIjzx3/+189zvYTyovV81auuN++e945a07j1CCg3S1iqBcgXFEgiYoCppIM2AXPAZgojKLXxrw+kPKCeOsQtHJkPlaHpngMO9KFkicRiAhjX81PmTF1zZooF7GSqoHZKuL7xj3EQETAcQIFnSM05kysv4v5gx2/dxwWottn952vbSsWOWfB6VRpSLtJfURULyWBGHISS8RBGEbjJ0xek/NNGJ8vz7LmyWeeXYPrPc89lG1ZY53+nqfVOcw4X8ocLKcw1wwsK86DASsv2MTdf9Th+z4kIpbxxepVh71++aOzm5qaR+kSd87yJd4VHBX3RO1S7jTbE5OgfZE08DIWgjNdWacq62meMdABvviGqqFyEicLtIPoni5d2qobdVXGBI6ZSpx8ypigrBMJ+5MSKJFEY+To5ARgMryj7Aff6WRmfU4nNHdoNlRjy59pZD77bsvfH37TvT+76PJB4HhD4Eo1oStY6+zTb7W/ds+o0vj3Zrtevo3uHsybL9RUASFznUnTSEyCliUJSaPLKnHO5G+/694H6+rzkXVJCc6SYCDgzRTIxIiGInpYNBvhwWGCiEamjSIp/eL/dj1TRBRlLAp3wAEHtPft12t4OS7p77vR6NGZamEtN/NEeZL3MuPk1qnhmcS8JKrB2LfHNTz00ONfWxR6vv7O+D0ND6swzFnCZ7RNBcmh8sN1Sd3gaJ+l5CLqi5BQ/uWEI07R8p9LizDztD//YcoqK/W7pdhe1AMydtYZa9kPdog+FnC+kDgPdM5YwEUkFOrzpeEjn99mflV9/vk3+rw/edJZ+YiYOa3fGmetx0gDxzRYR4OBxi2casDP5WLa2lqbv7vlZn/bZZddFvnvsn1W3/75lz89s+ZqK97e3l6yXDMlLScMuHwAoUQSEYokGjfCDBGx+dp8acLkyfXDhw+vxQK4AK6klxrSqQ6OFWOC6g9EUCURAdWAc1/+f/zhuATTp39Yz6/EaT9ZF+stCGg0CdQZiL6cgDrpXJEwicvo16ehePDBe83CQnR77rmnW65PnwmlcllVSEm4h1FHww6nFMAYT1Z4LZ6ryUez5jbpmcc3hPlT7umnn+757tsT/pLPRwZJUrS6rhlYa0OSdY6hs1w/nNcAecK14mxLcxN23vm7Z3Dudpu/AHP8YYfNWHP1lUe0tLUUuV2Fziaw+us+5OwHkziTHInQCBHtIGIqIjAkIbboYme6tD4H7iycoOwAe8TQkNKugBK8E4Yp+ZAdY8Ji9V6Pj2ngOunltDPMT4eH2zAFemiXnAZIndajEs8bpgoJKbEuEcaFceVpKqDcETN0dtyIecCyjri5PfjEBHbOmXtear1t5EQ5SYz+i1CJrZOQekSJ89uTjXIGU5rsag+PKc4eMq50FJ8JOrfQ3WQ/76m718sxVLkTSYoTgRcSPfNE0yiy9DLpBw4cWPv6uAn9ampqStYmEWELHScNnBik2NBgQCw8AEn1MFIyQYBia8msvcYa13ATn4lF6KiD23yj9Q9sbmxkqzQ4rLN0xjndzBM1gDQlZBA6528SmG19T1wcR7fe+eCRznHg+fTC8u+8M6vnG2+NO6gmUuiImDGKH0S0RQYEWSWe6HDWeYJzaG1uiTfdeKMD9M/i+PxuFOy+246HNc6ZA6oeElAamdY6a/Uf5hBnpjqeqtbS8HeGPYSzjARB9PSzL/x23Lhx+jsDX9ibZ599Ovzwg1mr58Kc4fNgJXzGgoaldcQHJHKeDVCRGQCbiXliD93753s8J6ITl490E/+Ln+5xZmtr68siQSTQH1WMXFJClTNZPGpieIkbzp09G8+OePUqJn9V7yCucqPrWAfnmI4cRWFMCZB5P9SDEXo9c/Gl3VtvvdV7+vRZPak7+8nHhfsFa6M3pJikY6XjFnH8IAalttaiWX3lVYew9ML2bqUVlj+ivViCsGEI+61EI0g6KIAxniLluVxUamxsWvW1116rn1/l3n57cs/2OF4bjjPX2TysKznH/YnLwpE4T42zDuS+CIcjjOPY1NZEFx34i58vkn9JPr990XK7fW+/Y/nFaCL1DV3CoWMfnFXuqD+JhaRCKaaGzIAvVMRSEPj5jC51pktr86cctE5OTp34TmWAvaJH1XHPSUU9M7iA0sjnhgs90+ukuihVWlPREwN6pnKQfEFARHsk5GmOIHVVnsYYarY+4wmVgWai7qu+sBghDgKJhcUF4kNIEOZ7925jpMOP/MCtcM3wlg+mtJg9a2sC0KTk7QC8cVGpnmvA+cMjCgQBaey0+KzrXijGIybEv0I3daq777WfLl76qKYdhpPOKaWPZi9rMeec+d99QyYbExiIoUXEmcA0xZEz1HCWVSHpeKMXQcQJEwM2/sFOO54vsuhxPPTAfd6TwLQmNokd1LCxcboBWs5Za6xuiJ6g68RoJ5IkQaFQGw97bsSRY8aMqdO0hUXjx4/ZccKkqfn6ulprjAFJb4EhYiCclkrUjM1z/XKlweuqW52z22yxwe3M6Hb+D3/4Q/OKA/rf0t5epOqOKic8f2zIMVAOS4OeuBuNkEAyuSiKZ86aHY4dO379L+qQcy56Z+LEK00YRhzb2AMFAubEsjU+7jynbKiAZXliCtteLMabbPSNm3ba6dsL9UaMCnxpf/TRB85ZecUVh5XicizsCkQgnWoR0ZhARJjqif2GkTDAmHFvf4eJX9nTHkjSdUy0tBZONW1BxXmkKUqaQu71UPnL0Zym1i1a2lpXDPgypU+ypup+oZOaLx7gewD1oEIcN5CichxjvXXW7Ip/Sa9NfiaJiNt4s2+OLCeEloaPcByEBiU8GagsRrnR3ztn3NggF4bFUmmDmTMb1/zMij+WMeylV3ryMRql7H3az9BxEBxnra4FxzVuLdcMbX9uWzxvXVxsa4s332SDuzbeeONPXAJ9rPpFHj3yyB+2r7LCCpOK7e0xpw4c+6FctytB+gMRQLETjx8UR+51MMTWiOlynbu4RmclVZHTAiSB/6ERhVTijPUpnjumwedhMTv5WPuM03dO5PyDEkTohVmqvSMHNAY66ZAY+TTPCoTpSuCjoqRxJtBz85UiOUREcWz9BlBmtvcD3yieNey1psmJC3vVhGKs49uJ6C2og1Qr0yhlbuiwzhFjoCaQsFiGfW5C6cb/jGx/91arLmwAABAASURBVKXJ5S/9i9FegYUYOJ0DSmxDlFeJPUNnYpcY55wlSuI7i2XRPfTQ27kRI0cV+ImnFCdlzkueCX7cOSccR7+DOAWcvpW7WHGKy0nYUF977YYbrjNB44uaNttss/gbX19nt+bmZh5gemNA49IPY6oztWUvqJX4gVaBfXN8WeLh0daG/9398H5MXGh+2oczZtKwAD+VG0672HAjNtzFlISbr4hAf+YpICiVylih/3JXH3LIIZUbqHm53UES7iXbbLvFacRcrUzreFFpbWJdkhhy2IRHqeVnNUsjn4erpnE0wnKcmEnT3t/6i/uAmqnTp20Z5kLFRvcwQETnm6HA8UMI6CA7y3qJpuMkhSm1F9/4/vd3eoX6VQebxbqP33bHbc5qaWkpmiCAH3NhSIIS1fRMwCgDIGTnYHhIT3x/KhbEESOuAYaOmFWQUValj9bt22b5j6bOb6zUHvcpx+WQdfvbOlfZN/T5VObAsWOp7DiWzpbj2NT3qG/SMgubeq/Sp9nxNk5EiDNb81xlA00QUVkiEeXGqoHEl9LlSzZeDvPh2K/grbHjtsiFOkX1AdGAlYOYWst8WC4Q52zVgS9nhovmye9tv+XbWrg70mbf3OBE3sQbDqfvAwcuVVO7p1hBIJISxFAmGUODjWlpyS4NTVfWxsmqo5MSJOU+sdIKZab6iPIq+YTFGFCttPWqQuT07IGQmMUCQiaiYdotMMeRhAQ6Iamvno8aF+ZVHtEsT46hEhknMsPUWPL/CoyxvLev4AxcUhARX/Sq4c0vv/G+/VNdDb8KCIzlPi2CIhzXFYsIwJYcSbmSg2qZhjABC9eEgtaSW+Pp9+LBd77a/vq0aW6h3gpRga/g2RPqiiqBcXbCcbUoWMK4EgiS51+hhSX9EWIRPD78tltaWtqKQWAi7n+cBjy3PUYECzwM2EkfMo3l4RxvDeHi1pZm7LTN9hftsMMOagSw1KL1wvm8+27bjWhrLzVT08hRP6rrlRCGUhlf5aAMdSJW53t9jzr7yGNDL+Az1RNBc7uUJk2dURuEYRyGgQ1MAGO48ZKEsigXgw4u1JImU3upVNp6029dINSzS5Xpwso23ObbUyAuzwOYc4EvHNw3rOWcsUlsrRqbvFF2iXXOxpZ7C/cOGwRB64czZ33ri9SYPn26ndPcPCCqqYkhxITvv6I4eVmMiGgVBvAcjJokKccNDT2Gbr/1Zl/6TyVhEblzTvvTtDA0HxALGBPAiKHuJGg/OhONQdWJczkIQjQ3t9ihQ4d+5TnqbTrLClkfw8/2qsK8XDNPnH8ptkkfB4m4V9Ag0flgOfyWU8LLamDBWWuYQtnGjjqpzE9vvee/la9eMvgwTzj4/Ef7ygT4UcA8F1NUzHnTiAbEroHxL/RTp06taWpt+Xku4kceY4wYgQgJSmCoJMTG6e2msex/iZ/v+/fr+8jee+89A93U/fu8M17Q3xFOrFU8qKWQkPaHIrsIEfFklBvKAONYKO4rTc7P1MSBy0MVFl2bLCY8Q1SuEMiZigpPY2mIxeiqGrhP6KApAhEBAwACEUGH46RjB5GmCEQEDACQe0LqNKqS5rNK2oUQbyhpIgyLFkhaleLWarm0RAJdODjviaZpLe3hJjW5AGXrYn5LaWUVcM6F6HSwCfQHDEEngLYFdQIB26AofDAKUD+jya5799vtzYPfLC22v+1FdT7mxccVUg8E8fFctYdwXjHbsR9MF6aJ3vWCApY99/jQ4T+orY3yjjdQ3P05Zxx4GIBzokI2TXM8LJzlZxOHclI2hdqaxn/848Q3FidiBx54YLH/cv1OLJfLVBIlrwvnpQhHlWRIIqkMcpLhZUIxl4vMlKlT8lff8L9v+mcWQjBp0uS9AhOEIjxuRMiNFcPlSTJCXqFUllQDh2j99Vd+P410z7Df174W19XWPpXQpuTECNN5YkvO8vBM54ixljfevOV03vh0JsrlCmPGvLHnF/Vo1PTpmPHBHNREkeIFMQIRCWGEn5oFjMQMQBeSLOMotZfC1VZdcezqq6/+mf+wkWUXu+/V0HOs415M+wPCOaC6d1BVOwdDkfYHBZZpaW7pO2tW2xZM+yreGatnKBcEfL2sQ0gVT9GRoKRJ5FrSMRA6TfoyZK3NJelLBrcRNsy5YJ01nqzlXtJB1jmrxpZhecRJsuGXaeerlk2SourCx9lRdlpDfyZoh73ALCrG0FBZpjhr6WNrdUyY/Pl+ypQpuWKxtHHIm0yFT8RYUpzO4YB1aIsktuHYJnfZuK3YVvraaqvpn9vivP78+hdn7iorrji7vb0dRghNVRFhX1RWTlFEIEKCAOSgczr9yLvSE8iurC6ti3OegnDQyejTOAXv2SHl7JRDRdb4YiTVgurM04YTqkN5L7hUOxasSGlcQ6aBT3pGjk7Ol9VAqZKu7UCD9AHu+YihO4dT7sCihUpWeN7Lk34YmFzvKBBw8cTM5UbtCiykE5yfOXjjWalXK6qSsEBn8ukCbvp6qAtyxsQ1OcGEWXaV/zzX6p4dX7qZi2ihzIUO9T5HEAioMolcyxEE3TTSNECNY/EYMZ95LpUXm75YjO7iK6/bYNKUGfxUnoe1NBQcZ0Vn4qHIsdQ8cM6AZULHtHIpNl//+lpXSacXk8XVjd132fua5tY2Y0wQqQ7UiTOAY6sRT4JqmudGaFBb/Re8uOvegb9h/8QX6+Jg2vQZ+4Q5LjFo9QLfthhu1JRpQBiST6MhZZhOPdCjrhDTWOpiTbq2ul3XWqs0YPnl7uSnUb9muIS41hxvOSpzx1J2FjpfnE1iUikIjB0/cUJ+9OjRfow+S6Pi+PGmuaU1DnM5xUsPaSUIDIEkbhAanxKrBBA0QSlO4tbVV159koh4VdC9XIc2666+xh+tU+MxgKjqHSSAKAEgI5i6VVmV20vtvSZPn3Y4vqJzwllFVNjsp9QgbEJ8Ootwi2TIgo5jB37Y8hlfIkhsEtjEGms59hSUO8qeWKer1O0cjTanc4TtASWeLV/6D55/CbU6igZB3hrinPa4grIaQaqX1blrFQNDfY3TNEfJ6Ze+zudiR3WfEN6cNo3P2bpAfyVC2xHDZW0gfpwFYkgCCAlKQOica1x+xeWmoZu7Pr0b9i2V2lHtg1fXDx87IiTtkOc+Z17gy8yLdoVkuqKSah3U2VImaSfAboiXhZLGUqqE7IygGznq01kbTmHdONIkdsx3gSmfrTNz6NMHNEwrTEONpyQioGdESN5zM3a6l+kqIlGGK3LBxwHCOwK4yOrCEt46wNHQZBFwskO8ccq1xYWm9QiDeSSQSlyZ6CYfCiQybFz4vDA/x4PE8cZ09FS7z83PtycvTSz/g4uI+vCZRekVJG84AmKFHZxHwnSBAaivkmOcw8ADEcuke2DgkKfz+RqOuSsySA0ARwBJfuZ4bpnFGZweFNZZi5DjPH3ah7//5QGHP7Pvb/7w0IG/O+al3x5x3AeH/uFPrx121Amv/f6Yk1874qiTRv7+mFOeOPzokx4k3XX4USfdfvhRf77nsCNPePB3f/zzY7878oRnDjnyzyMPOfKEUb/9w5/eOviI4yb+5vDjZh942DGzf33IUbN/ddCRM/f5ze+n73PgERP33v/wt/b81aFj9tzvkNf22O+Ql3bf95C7fvbL3176i/2POPfJ5x96q76ulnPZWTEGIgbQeUmqcs4AigL/IyYWy9u1qKb4+pjXf33P4MHz9TtX+JJuxoyZecPbDILHG1bh014x5RART8wHJaje/FKOlVdaoXX77bePWajbehFxm6y/3gjebCjQJS6eGCJc5477c0pc99xPdM4gtNZF7CONx5Ziayv6fF7HJre3u1Ip5qo1rEu4vbBeoSEiwsdInThjhBZsF89965sbj2GBbu3XW+97k4rtJc7R1MiEGMIm8wii+nMew3BPotGul7RlO3PmzFU14yuQI/YJN0Bi6ToeF7YjwlCYpKRxiqnXckpp7MuEznGyJxZsFJZ7hHNWjS44p/PAE/WYVyNVYIQggGNMaWH7urrZtGerrbCPFb24oVHHeXqr7iQmW85dy9OSEFYf+xw+d+JctBdLhVCNTHCPYQeF60LSPckKdLyNFXbZkGBdKaqJntrg6xtNQDd3G62z4jCCRC2JG0P18ySNpeSgE0opjX9kwNOkBQ7NAtfQqQLnxMJRYVdJJGfMR5QLO6QEz8G1BAjLYDE7LqePqK3qqJ6iAmmeimmKhikx7Mik4Egsn3rmpQJDlVPSIp6grXoIKHE2A7yZ1Lhws5I8UowKOrdFLCe6gwg/kUNfWfmso9HJinRD0MnkOcAyFWKthuAagQ3gwlS2JfKSMQ5GrJIJxYZ1ORcbYzF6anz0bS8Xy2/OKH8XX9YtSHnOGarKuUCMKKdVUYawF+SOKT5dwE2AESoPIV+2/KBBg5Yb+9a7hR496mLOlDxEMeBcIAwKERkIIjFjrp8bUIEHB3MF4YdzZkUjXx699ciXX9352edf+uawES/2eXL4C+s+Mez59Yc8OWKDx59+fjPy7R5/6rldST95fNhzuz/+1As/G/r087sNHfbCTkOHPbf1E089v9nQJ0ds9PiTI9Ye8uRzqzz+5LMNlHs98dSIXk88PaLPsGde6D9s+AurPDNi5NrPvfDK+s+/9Or6I196deMXXxn9f6NeG3v48y++8ofJU6atkuM3KhExunmTQ3SiGwPhhIWwX+QiQlHAMqHTuJF8lM8VBt772B+wEFxJ/yYfG+NaisCT16X2FxyxZEAs2aifqKBeQBzH6N27d15EOB7o1m7ltVacUCzRdgYiiDf0qK9UiBCnadoPpRLLgAd3wdrS5xqZ+ADg6a6/X2vAmadYES5jGXBmesOV6foPvSwbgzhnkjh5cd11V5uu8e5Mp522F98jyhDh5giBCLVVIuvk1cDk9ODRZ63/m6ntpfa5nfK/lEjMhJWlzxBDL3S0KfA/AojIPMJXc0acH7d0zKg/57uXaaZVauSYsh0IIMI5ITBGQhGJsIgc2/ItqV7WEh0l4mKtRUoJWUI5MdaSJ4lNkvkzMotRm3BChmIEEBgIOVISYU857kw2KutXDAAmCqPXVl2191ceX9axSPxpp53Gz/6m6IgXIPCOjCcBl6OP+YCTjdynki8cbxZCtdbXWdWbXHwnxSeDxoLu08rBdCFhMTuvA/WsqiEiULVEA9BxUuvC/+wB4eTn8/Qs/OmeJTi4DFmoUh0oauEUL8BPB+bp/lwCaFCyALGyAnCiQ7lqlOeDJdWHC0/LMqrVkPgww9SzDyKi5fXZog40oxE5yTGd5MCoaB2ehwb5OJH46XeTYS+OLx2RVrTwQ/aRjQi7TKaecwRK1BJOE5inzMdVX8Z9uib4jGUiuHvg4+MFYgL9vUHH+SDCfgtESBCorwTwTjFiOZ8uEocmMrW1tTafLxhSTDL52kJYU8t4bS3y+bzN1+Ztbd7LIePMz4NpqM3nUcsyhdo8PLFRsWx7AAAQAElEQVRsHWWSIaG+UGt71CnlK7wWvK0E8wzLk2riQr6G8ZqoJqefVk0sYiBGSBUulEVlknIf1zSBMYHlGUhdauOnnh5+ynvvvZdHF7skjnlyOTgeYM5Zo5wnWBrn6mQOdIk5BkqWh1poTLf5Q+KfB0eDqQ+cIiiAiAZ+/hiKJIH/YYRebxojlqKByFCQw+e41h5F53ioKx6kkmIEZ1mHtY48jevI6edWC+ecpVnQtNlmm82fJfA5bS+KLMsxJjjw25EKvlHxoQ90D+V8gEPsrNX+maQ97lTAl5rfgPBz3gGmuhlKx5MCkc+mjmKfInx2kuE4wTjqT59ydoTedjwjlMRADDcPIRfhOWS6fO2xlU/4lpbeuoNRHaJBBR2nkSePs4UlJ9G4tDEtS3BigTYmxST4RGWfltCo9cKyAZ63MNpVghyLUCKJ9pWGJthvUolEvFwLv1wsGXM3AceJfdG+V5iKnhRZEj27z1Xq8SUeei/vC3RdYLquKl+TvgWzTu2RJy9rRzRXwB+hJAzoKbGDFUEji5tUURLxpiY0KVPrh1ozSp+mU+jwLNwhw5fT3lS7pzLo0lJaHyP0aTwVWCcXNwcX8FgBOqPBTb5ainc41INPGxHQcUKIY376DJ/nxkaZgj7hyBnz5TzXZ5xONlT001L4iGOK0Z3DpiXCgM+8MdNeOGOGq/9IwYUUEW2Xu7jznI04knrP2XPmwRN75BQn7i9aVsssIzRy5MjCMyNeLPTq1bMknCI0ujxXGZCKJwcgIp4YaMQwgIC3Vww4ewwJtABCx02bmzQsD9KUW92w9Zf7lTPdzqNK2UQ3dsqsw1gwBMdESXhYiRg3j5hbyQPHEPofBjDuVAYgEoL6UEDVCROEEU/aB2MgFTJ0IiYOc7kwcYm9/Nr/nsaiXerjODG8BeFZFbPfSUkxUYwce6K8ipNTg7OCQxCGXarDwqqsoaGB8BFEE5TEBDASGCZYUgfGlJlGI4LzS0RCCEfic01MoNDUJNZZet4iWRtS1AjIecPHGZLiZBhnGSLprOEMaBIe4Aurr11aL7UVwlCtU7ck9oIzopKieSSmh5zaPtEK15qXvnTAi16pTdvj7TiFtE7xQ+FrE4ZM1wRljNFzwBh+Wc/nTednBBx7MBStjzIZRSvi0wAdM2GiwUL5dRV8zPnP5do204kvQ90/iD7POKdrULknzjvuSdbpHExCG7uyL/wFQXue70SAYd16nlotLkjHjs1CuAbEiKHjlz7Dr+oG/AIzi+l8BN3eBTlTZCfo2StI5YcYVmav74TzRrb2xTrnURWNdCWZrqxMOEnBrsA7biWUHQm6UpR8ehqwLAUhLX7PSaNaeurQhpMXriNGQSMOjukMmcW4dNK/k8jC+GhloGN5H1a4r4cJVZ8mz3tM8WKaVuuRVEHLVrnK80r7mE6fivARUYtpHR9N1JjviS4y9gcxV5klsY/gAevCJEp27ahvIQqiCrKvqoRTWclpg5qjBM5T9sBjIlRceIBp/sKk7lX3fQ8PO7y5uQ38yhwSAAg/HYsIRFKi4BUWEc81zjIAD0mVPaU5lZBjzzmog63kuElXCbrvdMTTco5l3cfSOCK+rmqTgACfQZrj87SwEgARpqonF6EAQPSHsoiAm7snMbz1TI1NP1cLdXVm4EOP/9E53vajaxzrkpifv2lkGpsksbWWnxJpJFX6bbXverDRIHc8zFiehqhFEsdB12iw8GsJjLHGBJGIATGtUECrT9MNjHe8mzWmZGhQhAF5EHzhrY1TXFR9Zw1x4drUxes4rcg9fg6W3BJDSxPeJq63L6fPdHOyic4BKqld8RuU6+gXU8GIZ5CUgYIlHvhqznEIaCCxMnpWRS8Q0crEc4YQRpVQlXwkjy/rRIyFr1zIKgRhNQIRAQPQcbJQBknEy2KkwPRF49ksqIsyDz9b5dxRkdCnY+HjnF+OuFtrkSRfOGVZS+p58wnHZ+Cc4WAy0Wlz5PTarvHGdihiIBJwHkuB7Xl1WKKbe8kL+KP9IHVW1qUIMnTWaYaerYARoKTRriTTlZVRca+v1pnqDHYRAG/idAA7MsGYRjyxW4wvVs8J6tunKjoW1cNT07yKXmBm2hvGhJKQ0yvTh5jCmA5aSqyTeGiSJ0dAnFamMXLpKJ9iwSQK3msJT+JDn+MlbUbTxD8rPs2LKmkmI2lqGmqyksbE5wmjWh8p9VxbXDea6hBSP5tqIPykzoMH8vm/j8XnusYr4qobiThBifqCOkLlzsQGdTo5zae8LHjd1B4ePPSsugZeLAstQOEtBztOtOBh8ALoKDiyjkTGKYuQK1EGifVx3Dk7OeDQ8iQvMkjzwGnQkdghM5sZqWeNFARarYjyTydeBHDnYh5ILMeH6OfJwljVa7YOtY9rhM8A8zZ5YwI1Mm2uJm+b21ry115781f9UzH4NMe+x46GEMEB/MFjrWOndRv2nHlMYRaxY76jsu1x3KV76Kfp1RVpc0rtEgSh3sjA0NgUf6NJSQyHiCTeyLRMQUCclcIwFwW5XNPnt78cDI8mEeFoGRJIAmjINKhziEHs4BxTASfS94knxuvtkeZ2WzrttNMMtaXally3RkddlcjAOcD+MKSkcbBvAu2y4Cs75wTtIgL+KKqg+MnKNJEkwlKeqN4nS31hiui9qbbCx1kN2Jgh0QvUpaFKjmmAxg0LcrwX6d9Y1nZ9614QwGuCjzgdBz9WH0n9/EhP9OTYOnCRe0rXuPNpSNvgoAtEhHPcIAgChEGYxxLiRAxMYADqzwBVJ0h/kPKQMUsR7LnlfS0fQJe6rq6w8rkcUENAkDrhykHaCw4gp4LGHeiqJSh2I6+qEXCvK5GnF5LqTSWpe2etRTSmxHzNVmIFVnmVGKdIr/UopWWZME/QMp3JZzJg1fSKHiOpT4uloU/RAhRSVSoRxtWLPimUlMjgC2mz7CGroNd+6jrTXBqa4r8hOAdDTRfJ75sJhG2TiC28DCrIOGWvH6MdXhOYDk9YJtyVN9665ZRpM0yupoZv6bHeZHKcLPvudL2Rf8x7jECEBCICoEKUK1moOr+xprNb5wFJ5wWJaT6PE6HKOSjMZ6hpWoFWq5wkohGScpKIQETAgF5SQoUL6AQiAgbqPYFOkzoiPi5GRJgk/nelKBmqZgqFuviOBx5+kEW6xIueuOj4x3ShA81JR/PS99Wx3ymBxhKT4ZgeGINZs2cvETcbc6Z82BrQaCSQMUkNS73R1H8cwM+hPIzYFzGBFc9NKGLiXC5XCsrlDz8XYH44NQFHRUhqvAqPFENZ4xBUPADNADxzydemTXt1Eb3A4iu7XM8VBuSiyCQ25mxIOOaWxBXk5wDnAyeir5xzgXsl/KEHrg/SV/SOiLWKCETEkEAJnovW6ANI5QfkEIExgq/igiBI+CzHn8+zHhHR6nxVIsKBpCiigW425N7T4jaL3NDyakD4ozqIBkCaCO90WKqC5/MX+F//8S+MHE+OI8N0jNOxVQyUtDJDvBAn8XJ33DEmpwndnYKAR4UfRuKlWFWJijMFShTVG2b5rxCFmtpAE7qSqgB2SZ1UOhaqPs+olI+uOE4EP3YcTN8g4/Re7C5Bhz4dgmqWRtgbRihX9deYykrsGHOYUukyI5qs5BMrga/DByygaV5mwFEmdJriSRgR8SFADkEnV3m2U4pucmmqhs4XFx+6TqUoMqo6kfmIlx0M4zGP1JhHKz8Tck9lBneTIgstdC+impIqHF5vpEDqtkuiOj7uKMOJEWYvK/6BBx97Kl9X0BOuyDECP+fypYDboX4udhw5UooHZQUljVCqxCkJhCGILDOJM5Qg8Aypmyf7usHQb7hqUFWJCfTMYZuwyjksbEajvhbKvCDx4kcCVi7cbcjAVknMZVmGvj7lImmOMCLCkJ6iessg1KhAtO9xbaFgJkya2GvQiBHz9b978Pn59Gq4UzHHG2Pe7rN33lNJckdM0mpUyuVCfDhrliaIBt2ZRo9/e9MoiqwIzUhjYiMCY9SYFMMfgHERSmooGh0oh7pCbbjRRhtV/zcyfJqjjYkoDMG6SEIylhVCWIeSl0VCEYGAn+IYQMym70x4by10c/ficyMPj3IRrE0AnetqjChxstNDJ4Pn7IfnacCYTleyL+9tIKZNRCAiAL1ud6DjjGRIr+lk6lX0pAU14UuS8MXKiOHT4sk/LpTFS6AIipwzaTwN9csCTCovopBKqCKqDxjQs2GBMKx6TROfIgjm00zq2bOnL+v8SwO31+qA6jiS9Ezluo850BxQ7gsipfZSef3xHzzZ7V+Qfv+n01bUNRkEBkbXogAi0kEUoI5JMYSSCL9iCAYsvxwnO+Nd6Lt2sojwdQ/UWSBcFQJ1DHWlMK4xHUcljqEyX86nL9aAOrJ9VbGDmFSVQf2FvSIDOCoOnH7sgPPEKQg9ckCXckeJs5KpmteJJJWZzTzWQUHLsjpK87z4BGFClficL8gk+mpqB6/kpTWi42k+Be9UT7boOChKaRpztUOOG4bm02CxPAS0Kp7ifKtBifV9/i2Gr2jBA+0HvC6UVIEOmfGO6lVWYkK1DLvA2FLtb7/99ujV0W+F+ZrI2jjOw2+InGc87PhCwKGi7MevAoNi00lMoxxJn0b8OH/BGSIioKcoKWGeY3U+4uc3WD9jXmYGG8Q8mQPABpjMNMosp96BdapQ5WyIHmAgIsooAxBUHlKBcsWLiM/yUfEh9ynhC49GJKZKhocjSeL7br77JF+iiwL2hW15tXgN4LygVWvLVe5lZkW5HGbPnJO/4447ajSvu5L+N4fvvTN+3yjK8WXSaf9C7QOpJCLEmiTEFdwLNMa0OI7DFVcYMF2Em8bndGy55ZZD754NxnE+8jDTYfGHGgXPxRgoMQ9CQZyYmijqP+b1cct/TrXdIuvt9yYekqPF4vzvZdIIUUOTE6Q6/5XrBFHudF0y/yML4Sv0wgRBWUQgIny6E2lco0zl1GOYek0SQ/kr3C2mY8IaWLeIQH9Yk/eMes5E1u5bYFxI6g2PCuWLgjr3FlSnQlVVoE4jSkCH3vhi13PVnshHNbycjDmM3Oc4h53TgaRcGWdmhBxTzm//lYMXMHabSe9MWf2La/8yJbq+7KTx7/87F4bsmBATga5HkVRmAiAAGGfAvUD0HapkTBCvtOLy3PfQpY4TqAvrs85ybDgmHChWqzIPBJVI9Go8wPeOoUA4fxgyY/F6j3VFBengAk1X4kSDVxap82k+AAQVp51lfzSm3VTRJ1Hw3GdokJI+V6UqRizqM5Ur+YgGH4loAqn6cIWrOhURKkMjLOY9ZXqfJJXQkTtm6m5BilVH6lHg4DGKEvvAT7Khyiy1kD0b0/aVqIOHu4NbYbxKqocwS6i9yks/DXxs+Pl8MYg5piHSw463mf4fWOhQmXRP5AHIJedlcMgog+SRqoAqHVA5sK40pgIzPPMBfJ6KBB3MgnKtS7mv3/+jl7Q9aFtVEsvyLi3PAUNuzwAAEABJREFUEaIArUdEPAcEqUvLeN1YTm0YlUFZ86WjHKoSDSHKgrCSELGosXHSytvMcMSLrxzvHCcQusIRNOitvrAy1cobXlRNdQZE5CMU8gavbG1xbEtLWgDd05VKpZo5c5v2zQVhkVjBOb6eOMs17/9OYswjVfdtNUDZAW+ExuW4bNdac82bmPC5fr311nMDBvSLy+UYYgKWpUHivSFWJGNgeHMqJBPozalBgbfy77z7bn/nfFt8pvv5K68cmZs5Z3a7CQ30H5IoQI7TIyUdbq4Bzv00TtkRRU/si9V88q/ghQtChA+S6KHEWIVXY5oyj4S5X8HG9BXos+IlBh0COEcY7/DV/ji2xHUIxB1ZC1vg0nbE0ym21bY66SkiqlM1h7JQDkhf7DdcdVWssEL/UuW/uOUD6RjOG2sf13VirbP8ypeAL5a9xr377hrURxviM93TT5467SdRFIYcSe87a6mKi0dKAOLnySKfj6LSWmutMQhd7ExX1scpaNP6qLwDGHoCJwoYR8WJTxXGqkRxcXtVpaKDiEBEGFOClx3E/4DpDlUn7Na8WHU0uSa4/cCT5nrSRULiA+nDoswHFFiCXo1ueKyYzji9X+zOp4GyprA4dWAJiP8BGIUAEBESvBOhTImMDwqJkcrjKdMFxGTnuIgQMk3VhgP75OAPctoNbXxq4XunerCZThy+z530Zh7TuJv7NHKWX8q9bmRPP/vSYbU1UZiUy+w+t1vneJ7o2BEQl3KWS+cG4xTUe0A13UeYztIeLeH4giRCSQm6BQigaaQqFy+D9aSkdSlBK1LSZNbr45Q99+nCWJUMRIRU4X5MDeuUTgRIp5+PxozmRD5NhBumX1LcYxystQVQ96bGZvPPsy/+ObrEiWE1liqjQmHKBZogImRKLOblgDchufwbQ0cukr/CgK/oXho7drmW1jYjRvIuva3htNCbGR/hyySNPees43g6GlLWJoZWplm+f78XvqjJAQMGON5kxjTEiBt3DI9LZ4wMMfPGZSg0QoXGZi4XmQ9nzVrzjieeKHxR/Ysrf9yEB7+eJK6nU0w4uas81ccpfuAkhPMQcjtyVXJ+kqblvnQoTkQAqfxgnvNrywfz0lgqjUjKvmRoYNBRBei0ehK7zAjgeRpYtqDERMBI5eXLl1q4AdVJG1BBiTFRIkwiQvUFqHCB/gAB5s81NDS4fn360shM+BJv2F89+5x/2eJiYJxjaa3+L0KG4xzaOLH6Yjnu7fc2Gzx4cLedu/z6VTvjw1khF7y1/l/aO98XTmM/fgpjlXwaUEpYrqYmGr9C/953zB9681+Ks2z+C39RSQESSQea9crHilfjVf6x7MUYFeoMp6GkWnAEdFPxI8IUDhFLMFGc55VSAONMgJbTMkrcahh1KTGHAkPGGXpfeVj4oDBByEESoUTiAobReJWYRhEfddX6Uq5qVPOFghIZ/HNpESjzpAGoVYUDlcPb8QbH+f3RgE6zkxAligvde32JP7zCGlOijvCk652LhDJ19B3RcooL85dmf/pZ5/+qrVgyurGx98RAN0BKOjjsuDLOU74cUHKceS4hVpQ5jAqPR1EDksYVNlCgh/IqiRgIb5xA8lwEIkzzJCwL70QqMhm1sCJqVADCuCejsoA3VxWiHBiwTmv0d/0CY4XETMAIlIRtQmUxlXiqvDBdjHDzFwjYmh52HH/2lxu+Uyx4wxSjUKjFI48/dSrTtRhLLoAXgYhQEfYLwopSYhrmkaFswA2cHKivq7Nj3nj9mtOcM3yg23niYp544sW/1OajkDOD84Mqehx1vtgSTyE4GkqJTTyHc7SdEhNwnFboP+BVlv5/9s4DwJKi6ONVPbNv814i53BEOXJGhSNHCRIFRAwoJgwISDyJEkQkCBwISvwEBATJEgUEERXFBCogko9Lm96+ndf9/arnvb0DES7s7nHH9HZ1VVf3dFf/u6enpmdv771itsSSi11f9Rl4qM0ke4bhJpaHXCTHpzhInHNpkqQeR/OTT9zz6MryPg2PPPK777S3Nrfk5mm+7ZABTyAKkQxMo+Atb3jCqWkpVeckBpBr5MIMIpIjfXucoWVGYyEWmGFRnvWEFeuttrViJGr3HhqNPcQyctx89mwIjqUhfFWxscfqsWzIkxldRbN42OWaPDXjzARVFVWIfUOSxFTvSSNHjuxacqlFz2JMJeU61bhWwV5js0DK9Abuh3iK6X216oIEX+nvO/jGX9z3vv2d4pvvfOgCbrIKtjJnQXx8NgRhPJFIGB/4MaGBKSdTqvqqHz16dPdKK63U957AzWYFN5v13726KrNjVVTUWExNeisxPMZFBROMEOd1nGGhSYLlxmeiOLR63sqhqJM8UGRDMTKF8f8i6puuXk4n3LQiqKUeaEbEkkh5bRNl5pCro6ZeZtwoKi1hAUmtXtTXZAPeimKVgcS0lplBysUNQQb9l4Bn9DCzFC3MjcBOsy/YdjtA1EUvlkf8oMQ7737owlIpFXa32pAjCBLXC5ttBEzF2abBZsHD3dkmKCJatjfTnLywf9AGHGfC2uIUEF0VqutyHuLDkj4shkCzRuLZnLzUdDIQ2JEDjykKKK/VpRJ5qtgDKpKVUehoxDY8Rx/OdDMo7nLCerO/zSiqtOtw8lQrorwAKSU0aM3OuCZglJeMt+/EJfLcCy+sPXHiT1awanNDqqxDSFXp2kFwV+PoBBdKI6GjX1EnDWmD66tU2t785vHj5H0YJk6cmPz92RcOKpVKLBfmR8U+jzMlcB842QwZD08JvppGzrz39vRmo0eNyNZZZ/V/v9eQlHlacYUVTsmvtSnnK0icLF5QY3fMLy8j1ONUGDzVlkJwLa2tbQ/+6tHNmNNZ8wjey5BBLL/44otb/vPK60s0NjamAh7YHtfCQBeMy/SRuKcYg/Act1GLJVwyUHUOBMc1RjCLaklO1i9SjSHR3Vx2Zm0FM9rIMtYqbdqYaF0QIatRI+oQrdawkPVvHekADCbUiRIKLIcU50hVZVYXlKpma6219pU2VhX7x3C8BIumKioW0Dsf59e74L3zoVqpVjPX2NTc9vAjj47/7W9/22D13m/0+BO/X5ul24StcZ/EduaQLTzUKI6J+bQ8MvPs+yt9bsXllptp3Q3eqAa10RAfOrUJEjaX+mrMVVhdE2ZyFupVKJxnUTFLVUXVSHIuIioChUhCyPMiqirxh3G4XCI1fU4xI2K3LaQQGYsBnZHJaBGRbLJziYzQpMTrVUTMkYDqOroVC8ZjsWUgy8P+d4zNc4U1ZBRrkodbNhabLPGNVbAIEvsVQN7qKBiOiBFxQ4G/rTsnAKJSCx7Jq1N0aP67NsoFIV5+9XVrP//Cy03NzY2MlGEaOLA4MQzQxq9qqXhVReNKFONAqFQq/U1NDY3Tmxobny01lP7WkDb8rTFp+FszcnPa+LeWEtRoVEIuPdPSBDdqhJcan2luanyWa/8JPceD9o2mpiaj15qbml+v0ST4pKam5klNjc2T4ZObmlsmNze1TG5B19LUhNw0qbmxaVJLY9PTtPd0S3PzH1uNWpv/0NbaArVCLX9obW56oq2p8Y/Y+EI1+HJgKKrO1p2Nh82RUVlkQ5QaBXgAD9s8q5zANTY2+Jtu/+X5gDBX0YGjEf1LXGE4mE4dck5RtryqqJXhaJq9ra3tpcefePLuuep8iC6+88HfndrQ0FBxSSpmtYqkdIXPbydTwQfxnHAGoPWsHcsHKZd703Grr/bD5ZZbbpZONTZeZ+PJo0Z1TK9U+srscFmcGwn5/MUFS/t0SsQLVWHqJE0aStM6px/43XMuWhr9+yr+38/vPaOltXldZa4dc+xsrlVFNSfGGO3Nx4loA7JxRk5+zqPStvlILjZBTmYiboOInZUZorF/lMZNN/vEdJjNtBHNj4ll8pZiEQkx9mv9QKyZGXXymkObWm9mg5kX6e3dGUborB5MxBCMwnsnG2+6jl94zKgMZzJ1ieMWd/HriYjGdSwiFfq2l3fAklKgIqfwtnZPu+++R5eh/H0VD/3KkTuVy/3LJ0mK3Zgc98qacxnlEB1PhiFGwQbkMwYe/PjNN/vPUAwmX8yD1DKTnDIhcUGyGGLblhfbiePdQkdUIuVeUlHTG0XFvEuUrmeQRktVa5wcIimVajFEm61cRShUVRibp4io2LBVBEkINtxAklMQ++EuFaYfnMhZGfXIgAmCtW2EaK0AEi0FCAWp6RQukIqQyn+FgMb6i21aj4pmgCiMF6KjLO9UuInQE00b1cjYOSwnmSGOF6PoPMqWN2J0CmEKUb0NIbcNbSxHvYDGn918x+Npyr3PgOsrSmrrzIasCgaRHMV8VhbxCTtLb09PdsD+ey39+8fvHvH7X9+18lOP3bma0e9+fftqv334F6s98fAtqz3+4M9Xe/yBGj3481Uevx/ZyHTkf/PgLSv/9le/GPvkw7et8PtHbl/s94/csdjvH420KNxoYXidxlA+5vcP3z6G+mOe+NUvFv7NQ7cuTBuL0s+ijz3w83GP3Q/dd9Navza696Z1fj1AN6/z6/t/vvEj99+yFtetfNm5p3S0t7Y9VK16jt00Ex8qgUXIRshStnulTl5sgzTKskxckrq//P0f2951112ths2cUnQmgFOdYeuA24lYXl0ui4oqZDqIDLMj4lxa7uopL/L1b37n63Pa91Bcd+7ttzc+9ae/faO1o5UTS3YcTh1r/Tjj3G4ukABuxQePHBzYS3+lr7LWGqv+UTXecVb1XWmddcZOXXLJJSf09HRbP2ng6Dx4nNb4UPPWLl14iPmTQN9B7JSlubllvRuuv2WH4T4RerfBnH3hhUv+67n/rNPc2lISFa/McyRVUYXe4WKDMC4EyqhCOsdRQwjmIjFZ/92GRhW92aTBYp91OZbNXsL7WWxCSG1mYBahvPGoo0nj2JXPX5xTK6dgHkUVpWcV1ZxENEYh5JaxxJBnJa45dmznCssud3Olv7/iXCKqmpKIqL2MqQeMkuEjMQTUrN8g0tzS5q786U37P/3005THwnmePProo833PvTrY9vYREOoYlecrJpdzCJrJaDyPgzsnwEdt3s2qqOjsv466x1aqzyobNZnY1a69XaDMAOxrrIoTWABGDOqF9W4sWCJlc1Tym1UllBuhkbGgpNI4kRrJMZVyatITGeUOXWUotVEhAeVsEGJOmQnQZUdC+Ia20E8PNSIK2K5cSE49ElQ4RIhnYkoJAYJMSV5WzS94JcGic8HlRhqLMoxMdCDyNv1qHKldSwi9re/YUMebaHHIdHTDJuwxiIkYJGbrBKwOpepvIDGO++8b62n/vR3aWszn0lF1cixpGo8boasEtYWGjvJTJXFV/W+MrK94zcnHPGVl+ZHaNZff/3+9ddf6xNd3dPFOccJGycHzLhtisGz3w9QEHZJCWyWxkGCKt7devevL5mbcScOjN9OEXtQNm73tIoQZUbQrOqzUlNLi9zzwAPftcA1oZ4AABAASURBVF+6n1E2b6Wrzpx4Zktri4jdMKJdQFmCBoxiSCZ7KvBA4kWTepVKxS+xxKKyxUc3uc4KZ4VUtX/9tdZ8pL+/z4fgM++9q/pq5qv1X8mo4mh6K3MezyYwb57gODrqLvf88OIrb1g7BG7yWelsCOtcd11Ifnz5jV8cObJjY7ORcTluK4GLOlaZMvN1MjvIxr0YLpAqiQlWNmfEtq0p88HVrHHSt7doeSuxfuvEneH6+mLnXDF70TMXgQYj2aXIYlMBt76iLchxDVGJSURlCqs8PKRgqqoikKrxKEo9mCrKFGFcFGc1UdVpG268znneV0vI4lTjSaYKP0orGmx/NcEJOlGUwQtfeUrTezqPO+uCn2z0fli7Qjjj/MuPzrKq/T1cxzjEiYqqUmLzZRx0TBRLIOaT1PX09aVrjFuja7nlFn6FyoMe3WC2iMEp9rPmaZWMijKqOqGziN4Y9SJ7XySYKGarCKnmxAuLMk08dkhVEuxmpxTHTRk5A0iglIlKROI/z26UqjRKhuwloRV7SrJjcG0gH2jHi2OB4nJGzvqlludqtgvaMeAy2qxC9sIfuMLWiFlEJeqqMbgxrjE2QDhggQzt1BitoKO25QWek7w1UEikbl2d92HVGaqva0WGTgIS1onQZd63RoOQI5dYJmx8ATIu6ANEyQIZb7n97q9Wq9VSkiT5BsciUOdE1Ymr8TyvAnekIi7Nensrpe233upU5VE1vwJz9snHvFythi7Gz63AGjfHkgViDzcfOXcJnI2ddUF5EHwZ75pKJbn/vod2mZtTMcPWuURUDWsV0TrlIqnUg7IA83Ua7DbnlvaSlhpK3zv/yjuxjQvrNecN3/egwz4zZWrnVxoa2JJzDNsArMJtUxFRUeWURhRRXeC+inrG3dXV49YaN276Wmut1S2zETbedO3JDZpMz/r7Ux7YWahWm+AViGljzqpV3geqEjwhyt5lWb9vamyWe3750N3f+97EMbPR3ZBUvfKGg3fp68++mHAyDlZljfiIKLiAF4ITEZUY6kxrQlSKqFp+zrdNkEpC4PEgWFAjmFirxiNRgSissxr5qJ7dhGVRu/6drqRHFoXttbkltZSOPfROVwyFzuDMSUVVhUSUHwRRRYIGZJGoE5k9PDbaYN1pLU2NXdXM/kYcJ5g+9EjeiE24Ee0GLyqeHiuqLvM8HDs6Rrr7HnjoxokTr++g/jyNJ373vHG/+8PTO7WPaC8xqU7NWXYut1002oZONBeFOkwtqy34HmFoW43fbItYaQiSaMRgtRuEu1EYBZuWGJd6CFFQdCoupmJprCfzPHhADkAesEnMPoZh85Mg23fkEvqWRKS1lEhLYyIluCaOqo5V5yQwlzHPS2iiDdLCOm1kbA0iYn9pMkVOcVpTn+BwJuKQlUNfJyYnpImU6LOl5OmjX1rTfmkIVVGIhPaFvogqwurBGhWhvhDsfjeSQIYYKJU6BYmLSWJe3jnQ1MwF1pZdZu0h+5nLhkrmfqVpFfrDXuMYFY1ADXYSSQwK7gqJdaReTnZBik8/99xiv/rN7w9ob2/N1DkOUpxokoo6FbKiSl5VVCFXkxNXlmpIFxrRWt784I//UubjoKpho3XX/HJnV2fKRPtg96YRT0RfoxC4W41sEWhwXNKTNiQytWd62213PvSJOR0+fee4CmsQEltj9GPc+swXKFaZInZiFdgCqMtTxycNDb6ru+ujO+66/w9j8TxKvnbUyfs99offX9rW2lzx1cxhsTeMMJPTGklVMUw1VVEPcXIjktgiY6ztbU1+p2222owasxW3+vCH/73OWuOO6unpqYBTGmzOQtX+BwF7C5Cqr/KJvEYzZJf1V3xbS9PIi6+86sF5eQp8+FEnbfnnvz57eUtba0cV28GoCbxwKAQmBBVVyKm85T4UiXoSIuUqwB3XBcJsR3oQ5ktq+xztMCehTjSHWFt9VmZdBQn5Bkrp7Ebb3oPYao+Nhvx6M4I7rKZCadE6jppoX15xGFJVFVUVEqKKCoHEVGIJshihtmhmVk2YDVpj5XVeWONDK93aW+5ytOWD+pYg3oFuvRVezBxFzosmJecSvhwl05WV0N7aOvIHl13y5+uum3efza/+2W3LXn7VdaeNGjFqHbVngpG6VFVlRh5ZhTGIsIQRAtar9Pb0toxdfvmpn9jzY3+WIQpuMNsNQfG4tN4kNwoyCzSuzegokI+lGgdrSU2K2nmWBIzMIyaYRSq83rMbB2kuiSje4ot9Ive/ksllf67IWb8py3d/bdQrp/+6R057pFdOeaQsEx7O5ORfq/z4T21y9Z9a5Jo/Nsq1f2qU//tTk/wUug66PlKjXP/HBrnuqQa5/qmSXP8Hoya54Q8d8tBzHfLvzkbORJ20pUFa1UuDDwYVhmikoBrzQSRCy5M4csubEIdTz1gdFMEKoJyrWAPWjgnk5C0hsMmhoAkiwhDH4LEg1Kjel+Wjvq6ol9e4ldeLFiB+962/3P/1N6ZIQ6kxFXMCuKU0UiIauYrqW8k511SulCurrbbqhTuMHVuR+TzsufP4a/sqfb5a5TNrNQinXxIC2wnrGIGILso2UB6Rgfc68o0NDdmtt/9yYghhjvY1VbUG/4tojz651+gjeG4Jiybb/RQCW0UQFcVhqkpTc4s8+69/f2H3vT935n81NAyKCSedve0td9xz1ci2Vu+DL5nh2M++LC0glZmdYtaK/et95xRnU9SVNU1dT7kvW2fNtW7bffcdn5XZDKqa7brrNk/09/f3AA3dhsywgpy3l4MqaaSqY2LN8cy8OZtQoDx1uvqxJ53zd/vfiWaz67mqDjZ6zITTN77u57/43oiOkSN9FecixMUGSlKauXElo6aFjKs4UbsnhZyqCKQKZ13IXIQafoYhlK/7gDJSbDuwGC3mfC66GrjUzDYaUNSF2AVJzNvYEOpZxKGOCr4CqdJ3jVSRRUXVyOZAMaNOiLPpZY4apVN33mHr86vVbHqwqfdgm+PtA5wWWQfKvYLj5lxZ1IlzSYeqSsn+/EfVL3nWBUf/i5ekhLrDGq+59daFTjz5rFM5lNjJpXTP3KhqRZ1GfJxzOVfHaoXglERZNZFyb2+2607bfGEojXaD2ngIvMViv+AZBUZD4wqJ5KnUA0DkoumN8ty8S80GFSaH1/4gDSy05gaVMhP06KuZXPC7XvneIz1y/VO98hTe5qRp/dLTU5XeXi+95SC9fUG6y156K5n0lSuSZJkk5LXiJFRUPFTtU6nC++FZn5P+vkQq5YT6KpVelb7uIJ1TvTzzvMpdf2iS63BQ73spyMtZkIZGJ83MFKgOQMR9gKxxy8HwnKMJA5JEaQBqhHi/wGMJXKlPpCkV+xHmTYWguZPJx4MquSGPHlsGjDXnsZaPN3gus9PausIUy89g0VyycxLfd9e8/vrri91930NntbY2p0mSelUWoKqoQqI1e1VUIYGM51V8Q0NS2ma7rc5TjqxqFedbtvfee1dWWGaZ6b19fcIw4/LgaVsRC2oJZGs0l8GJO4N10djUlL4xZVLpymt+tj015ijmTeZpbCDeNEhwusiXaZTJGQ+BBxHL0wezz/Vn/Vl7R7v84c9Pf3XrnfebeN11IeHqYYmHfuO4A6644ed3jGhvY7cgmn0imXUeTPbCCWO0OxNV+3gi4tQrLylsAlJqSPwBe+913JyuoQP22edPK41d4dTePp7Davt/7EsCTmTwgb7tHwGBVfCVEHwavM9CCM4ATJyTtNS4+KHfPKH3xhtvHJZP5/StX/rqt7e5/Krr7xo9ctSaGft2iE4vn/XBywfvqSNGQkBFqjkpvEY2VMdwnThR1XzzpNYcRHq0mYg7tK01+kfOYZwhkLe2VVSE/khkToJzIajt+diuqhLbMi4xuJiSV+EHHvNiwwuja/IQsyXE0a8q/ZsNkOGsSp71ogqP5EQtb6Qqksy+WfvuuesTq6y4/Lm95V4a4ykaggfmOBkBIW8x/r5mU80mb32Kqm9ubpa+vvKip5/74+n33nvvknndoU9/+MPLVjj+2O/dX2ps2oPnhbBQM+tVlXtbo62CfaJOZyb7B07epalkWb8svdTiPYd98bOz/PvXMgfBzcE1//MSZoRYK1bjlkDBiLxNFkSODCkywvsiYnhFvE/xLX21oUEeeaOanf1Yp1z6ZFmeec0Lh4qcLDpp4m0hUVaxOgmQV2VumV8bhY0HnTJeVRVF50hd5CLGE5Wcm04FWYWqohQ4mm2kI3xKqVS9/GN6l9z9wuvy0EuTZRJLvlRykkoQDSJchSR5qN0FqIUCiUFJTTFA8cbBVlPk9obaddSM0S4xAe6sHTDJK5tyKIleclPoGVnAz5hghOmNxBQ1Uis3WRas8MDDjy//12ee8y0tLTx4q7a0RBVMbJg1JgMcAQycU+mrVNyySy/12s5bf3iyVV0QaNstP7Jdd3ePF+WGClIRHThV6kG26EQ0g4zbb7Wg85WGJKlc89NbfipzFLhHBhYaDcSFl3OgjveO3TOxFmXmAcQ892YI3nlzUIKk5mi2tbWV/vn8vw8+9awd7r3iirn7V+9Y8K7Rfg91t/0OOZ0XlMtHtLeCh2MUWGlGi20ZUSgzgCxKpostaqbKWUfiPJ+5Zf111invvPP4WfkD7PHqtyeq2n/AJ/f5NZ/gJgnLE3ekHAJv7GYNPIRgeTafUEIGPhxPb05nEDKVJHFpmjSk3zj2jJftdPHt7Q9m3k5Md977U5+78ba7bxszalQHc4bLVRXmEAcYx9fjCHvvAnOLwUA3o3fGSUbFuKoTIBSFg6QoXERlToMqN7TaLAX6DKxruOFnxJqzkre3rTpn/TnBdq5VVVGdIVv7qIyJWhoTEyJZRRelIUtmNKyq0QZVOCSQmt1ieSca7Z7BxTl8TCezG5S1+7Hddrynt7f3WdamTbkDbnvcZir2oqaiKqlaw6peBIkoiqsu0oOjl/qgLV876rQnJk68fG0ZwoB9+o2jT9nqu+de8qemxtLYxLkmW6c+cDPN3K9GA7HVPvM79lDnMbcERs4lSdbT3S177f6xnVXjgpv5ykGVZ3823qV7/KQ+0Tgwbg7ltrC5ql9gehHlx1IhKBQrGZ+H5M2m0M8GJ/JG1WVX/7lbLvltV/r6dPGjSonYqabH2AxjM1ZeFWJrFB95fYyMN46dgVDXmhTyCsIwMS41fWQkCrGliHErV0MNsmWbikoD1/pGL//s65V7nn9NnnqzUzxecMKaCD7fbjABq2iIbmdEyigwbWwbQVVF1Qh7KWMHQ+CKEHgiWX0eCaZHRcQsxmP7G5nhibE/zFLsUrEfIY0UBspARCUPdZ7n5veUjaPj1jvvvZlF6NKUU0yxFSG8UIRIACMAQ7RJgeJcUYa20leRLTb76KGjRo2aTnaBiMcc8ZXfLjxmtFSr3J2qfPa1jT54BtcC+UBCTKFMVT1kEJWaW1tKzzz3bNM999wzgrLZiswBbfgZxJ155OIdAAAQAElEQVQVdXCUtGW95thHPXMQgse59OI9VA2Zr1apGlJOxcotTY1pb6Vnk2O/O6HrwM98fRdzbGhkUONll1238GcPO+E3f3v2uW+MHDESPBxOkmerCs7uECM6rED2O4b2kMRJz7UqmjoHdiKuqbGhctgXDlkZHG2QVJ+z+On99np04w3XObGnXHauoYE+WceqwiLm3pUmGq87ujaXQuBFQdj1JPXMqmMubRxX/vSmX+3y8U9eaX+WhTqDGr9/8cWLf/nIU/7y5z8/c/FCY0an1WqlwqSxnUfczAG2+yw1HQKM7tlzSQeiqooyNOecGCk8EnonOlBvNgX1wdM/vQIUaYzRANZabAse6mTrMipFmppqwmwwDjaqqor9KqrvTDJTUGRVFRVxiMMS6U5Ulb5UNKZ5YipFE4mMqhOFWD+Cl0ml2Y+fP+gTv1pn3GrXTO/sLCsvXirsOaopjx8arz1xURKtGyeCJCKq2uKZE9u3m5pbFj77wh8//pnPH37lc889NwezIu8abr/99satdtn3zOt+dsvdIzraSvTd5Hm5NRJvL235/sTSwD7BNuZKFXuVuqxUTXySJvaZ3I9bfdUnv/X1Qx+VIQ7RkMHqA8j7VDQ2Z6lGWRmvSTnFQpL8nlWkeR99NXgWlftrp5cfPjbFPfp8RUY0pNKYqnlezJ3U7nONnL1QeKYgk/dQYBx1sjHXZEVWZONivE41vbM8slNHqrYaIln9+I+DaBsDhBUiWSry+0lT5OEX35Bu7EoSbKraTqRSD2+RlJwRLUvkM9eijGy+WZkAibVlfAa1s15n5IZQGuja7DKiL8MGQqrFut6yhrnxBYce+POf/W9++8dF7L9JDMELIzTnPwtsXu9ItgAZfqXSXx41amR5m/Gb/0pVB5CkaL6OjMVvuP46+3d3d5eV+0NUnMwIvi5SjztDuEVYH0Eqqq6soumNt913bL3OrPIApt4I/EONfOR2I9SI+eDGtwgF9oHgqctlPvUhOpwVj6MpOAtVX/UucaW2lpbKQ488duOhX/vOK18/asLYEMLMY5lV895Sz/4G5s4f//QXvnPWuS9j0trNTY04S1Wzp4RFbA5WPX7CF1GFhMDJpWgLOXYW7TF1kqTZtM4u/8n99jxm443HvUaluYqq6s88+ZiJziVXMf6KS9ioVEWdVswOVXWiao6lzZuP+bxHW+uZB5usmrkxo0enz/zrxX0/9aWjn/vyN4/7wq23/tZeLvKac5iee+7tjVvssPeZZ33v0pd9lq3U2tJSrlb7PfaUmBPDjNMrbqEQT1wd3dg9CAuipGpJ5CqqKs4cS3XIkMk1EnQyZ8Fx35e41JkxtiUHMgPEROd2okGul2MAtWY/OuEHWzVSPibVnIsYF4HJQKBMjIQLBpRDLWjsQI1B9kgINvCoFcxRiT9UUFVTSMKPzEFQ1XDE1w75nog+lGVVp86l6ARiXsSp2tPYHE+Jgd02g7jVbZNQb2sX+9KOkaNKjz351AF7HvCllyaceNYhg+FsTpgQ3H6fPeyAz35jwvTnX3jxmziYzgdzKqveV6tZ8Lzkeu+MQxSBUmAVBfGg4kVUVLnnbcoVmRvgW0d9ZTdVrcoQBzeo7WvSY+2pJQzQ1oJNAkNFYwqYUMpMSOTyvggll7rHX63I+Y93+lc6XdpeSnzwklXZ8Dx2ekwPkQexm9zIDA+WQMYpGZAkH3Qtb4wxwyyaFImECY4rV2m7TmJyDZ9cB3oYEKCEDfu57j55+PnXpZs+8IPpytOGXSUEjaQwIvK7xbxGTGMijq5d/Yp8PPXc0PO4ZwbGaoQh1iMizIyDDBMjK8sLKFswIutJH7z1/qP7Kv0+bWgwEBy6yFlwRI/s4cwKCzOwoQTjgNbV09u03TZbnrLeeisvMJ/K67P68Z0+e1NHRxunXzbhyiavtj45pZNURTnBFFFVsYd9Tok5C00tra3CJv/FZ599tlFmI/CUEPxCCZyeGsY+4myY52T7mTVn1jAZzEnM2VzZBKHi82acF8mYGhwUcbGdalZqaWlMuVlH3/CzO54dt/7Wr3zxsKM2uO6668xetVZmhUIIOmHChNL4HffZ9cyvn1h+9p//vJAvvZy4eV+tZhkpNngcuABO0Ur7X3ewTUoqKqJ8K4GJcnqp2uI40eju6S2tvtrKt337G1/k4SqDElZaaaW+rx76mZO7urr+kTaUvKrSpStp7oTFeUT2kFgBiRBSowB+DKJs/+rcHOfW5qZF77n3oQu/9u2vv/bxvT57zCWXXLNoCDY+as9CpK5OOOOCxTYd//GJZ170ncn/+c9Lh/N53ONB8OneN1n/IFWmKQ8HNySRJlLyaBDiRAdkSGNepM6FoKqi6kRrJIpyzqIyWWwAdjGNWLuISG9pktUoASUWUToX0Ym4RMU5FYVEVSzU28256SDWjIiZZ7IMX6C7OF6xNO9WFWWkGXlHPo5DKMvVc5R++MMf7vzswZ84Y9r06S/xAsbalYrDuVRVWgYlVVDTvG2VFIEp4z4PnvvdZ8F7vr5kWUtrc0VdOvqnN91x8V4HfvnNAw/+6unXXvvzJWw9cs0sx4svvrhhz/0/v9Vl12z0xq9+9fCVI1qaXHOpqStU8Q05wQwh9mt7ALeNFxwXvmQEHwL3COuVjrCLmURmP2txSZpNmTpZPnXA/ntvsdFGw/L3lAEMMwYpuhB6Na5+lfqPNV3XSSwzDbct47Z8LMtV8yRlMvTpN71c8ft+H3yja0ydZD64KvYFrxJYQgG7A3mBqI/xxJo8sx4VVYJEHZINyHTUzvFQGeAOicWLQkU1J4SYN45GLFhb1ob1W/VB7EZ6uRdH8wVONBORxJa5VYptiF1KQtScBI5FNWsEU1DAYuQaK8/lmMpAXqhqF8KHOgbDF6wFLnGw1rfZCaGj3Ek0LM8LOrF6smCEJ598Mr373ge/3dDYkOEtsH4YXMhPU1iUMe8Dk+G9sH1AcBZmlvVLymed8Ztvdp3Gh8CCgUd9FDvsMLYybvVVzuotl1n3vPgp8y/qVFVUjZxxO72Eu8y5hFXkxKUN5amTp7bcdMu9W9XbmhVeBV82YqmT3XORWGzMCCmtIDAVUY48iEebUs+cO0Q1x5E8a9jbHAp3rWSUC05VZcTIdmFvWeT2e3710LcmnNM3bqOtn9jh45/a7WtfmzDSHig4ngl1zZl0lj/88DNb99vvkIXW3exjeyy7xkefu+TqOzpfeumVm0eO6JBSY2OW8aWXNeNYF2nwOJghOOwyiv/KOxoqwsiwMygPHEwMYp8AK/391aypsdGfcfw391RVRkbZIMWvffEzf9/iI5tMnDZtui+VmjKnzI8mxj3zZPPFPDojXhYUJw/b6Bvbcf5CExhI1cefSnNrkx85qr3t2X+/ePJpP7jo36uuO/7Nj2y7x/cPOuRbK3797LObDacJ99+fGj/77OuaJ0w4e/Su+372I+tsutOPVhj30ak/ufL/Xpk8fernRo0Y1cLnzEq1WnUiiiOpmYgzW0okKQRc2pUDoU5FxYLafmNCjXJtngE3EVVxkKoiqtCIzHEIM/6hGM0JDeYkbw+5laY1rIzPPiWqGscvCFyO7QoJzz1yA9FUIi7m6XbO+4stzF3CAsn7xxBaCga2IkBELEeey3jCkYfdu8kG614yfdq0LOUtSVVZqImtU6+CqCr82H1vvbtoSQi2RafB7PPCVwXPC16Qto42Cepa/vDnvx9x0hnnvLD2xtuXt9t1/wsPPeyIVc4884pWu98nTJjg7FdpkJsnXHBB22e/csRSW2y/72dWXW/LP5/0/cvLv/vTX+5sG9E+ekTHSBHRNHjfJoTYl/UnQazzQEEIwXk+m3vueO95SFg55CVkLnEyefIU2WLTTU8+8vBD79VBvucx6R1jvnDesWgOlKoVAf6cpBZURCExEgI83rRwcjKgl3kSnpo+fYMf/2mq563FNWpgukJmqTBZTBgbBmuJuSKPHGQAMCYu13n0XpS8Uo8mGFGAJOocervGdvfIqWDcbmNE2qMudUyOhAWxXSGEGsHoQWhQqpQnqZNX+/rkty9PlpCquDqU9KpgayRwqQVFb2LenKWWk5pWBMFLLehM3NZpLTu0LJpEz2ZzAKnII6xAUddjgukxNkDkFpj4x78+d+pLL70sLY1NJUbNmL1nf+BB66NzElhX7B+mhzLIVoGX3p4eWX2Vsfevs/rYVxcYMGYaiLIJ7rPbNqdklX4+gTtbGMLU+5w0VdVMnXJCpqLOkXdONam4oJxmNvvb773/ZyGE2smQvGcA89pmHZiGmYlLuUfF5kGCKLIii3grYP+GiaQice9AGVBAGk/EnMYyVBKaMk4gFEV7W0vTyBEj2C+T9V54/j83/eK+B6acdt5V5W8cf07Psqtvmv3o/+6snvT9yyrX3nr99Eef+tMrXd1Tf9be3LDsqFFtpVJDQ1b1mce5TNmrfMAWyBZGCuckA5NCaAkhWBlbEKcdPhivBGyGMlEpdXdOSY/40hcWXWONNbDD7BtcuvLS885bdqnFL+3r609LDU0pc2VzJOpiEFJ7QUCnTaqAIt7ssN+/xW6x+YakZPtQf1blhTqRkSPbS21tbSOnTev68m+e/N0/bv3Jz3vO+OE1lZ985bje08+/sue8y87tueqmm9985tl/3e3Ff3rEiI4OSAAtnlIzwpJYX07KcLMJppBU1OZWAg9w5o65ZD6JAa2XEKKOy3NuTajadSquxlXzvICxzGFQZxdbO44W1Ksii4oYh6QeohnYhl2B+a+rZ49zMU0LFCypXWzdqKJEF9BRK44/5IKwqtAOQ1xCsECECRDr2pIBG0xRo8D4gy0S8rZwuGKu403XXnLisksvdm25p1wplZqFteo0cbbfiCrYqM1T7CYjZyZyfwXuPcwN7ANmi/dZVrXbMrD+0kprR3vqkqT0yqtvHHLfg0/8beJVP+o66sTzsitvvLf/M4ed0Hf0KRd0XXHxdZ0PPfzEi6+/OenSxsbGVUePHuVaW1q5R5wwzLI6OEsjKH1Zx8KjIlQ9GBhJCNVUAs8HKiP74KsV771LNHGVnrKstOIyt5x03AnnqGo5Wj8MCeYOXi8qrl8INnZzdFRm/AhyJFu1QoAHcxrg5OZZPP/J1389qU9dk1NhhjxLJ4VMttWBqmZawFCiraZIlqALUKwUOXXhWiMDN5KquDqBQ9SJiHElTxGpiGrMiYjSZL0zMwWZNqtiIbCsvDgczeem9chfJ3dJQxMt+VBrz+pAGsxCBK6PKUmALNY5svUIe0vUPMfaHGgi1wxZiv22FurtIwdIwEFqISAH5GDcyozIz++R9eNu+cVdX2uwz+QATz5jTOwkNn/BHJe4FkLwcB/n3iamWq3an82Qbcdv/sMxY8ZM55oFMu62225dKyy/VFdvX59PXMrsm3OZOHVOIG6ryCuqcHVeHSeJIFEqNbl/Pv/v0h13PLAe2VmK3EJgHKgLBSiXSPOo+UEAgwAAEABJREFUwo+pNc+LqqjgXKpkquolYJsINqlV8Jaq1bCcCPuJWI75DUyht89qJSpLQ6kha21t8SPa292YUSNKo0Z0uFEj2mXMyA4j19HWmjbY78ZIEJ4nnrWQqogT8tzmxumbtcIaYQD2cgLzPPC8Pfh8YGAk1PYlseCSdMqkN7LPH7j/Bp/4xC6TTDUUpGDy4N03fqm5pfGe3kq/T9OSR+dUeFgrp87qSs652twp2CQlK0dwqnEXFhH1KmLjBbOqt3VfzTLhYZ1yCivtba0CPn7kiI505IgRUId0tHcIJ5bWluEgPrOHcBB1tIRNqnDRkoMbWR9QSYNmKoqjq0LAYY8Ik8Q8baGdKXK5qFqZSvxBNg7QM9WaLTGEkASt2ajqJLanApcYELEHkfXJakBm6n2Q8hy5DHG8NKaxfUWSmSSJIZBCFgMvKgzO1hLK4Y/YYJ0CEuNm9IaBkQ9iNrFAmCPPfVW1anNFzEH48udu+kx7R+ufWLqVhlKTc5I4ZT2KYaRIkSQVEe5pUhH7ipFhH2sn3n/4EswP9yX2pRnrNkjIkjR1rW0t0t7elo3kHuclyHW0t0lHR7uM6GiTttY2Yf+iQXUMjDEFuwdEnTbRa1mVdaGupKwTEXXBKOQvkkxPrE9ClyH1PpRERXrKva65ufTwuad/96tLLdXxpsxlmJ3L3exUfq+6zEAf46lVU9BFDEbBxlkjhStKi4HFYnze0A/+8updD7za7ToaGyR4MzeABzbl5jA52En0IpTGTc+zpoVFxEISm88ZxGVWZorI8zZiShOMWSKBkTjVSDChQ9N7ZK92sQj5QJe076XWHzLtx+KgQl0UIkkpkb+82SWT+qtSsm3Y6ghBIYvkoy2WIEeVybEhy0HUVVEH0a6iiOO09s00yw85abSNvhmbCNxIa9x0RmIBhKKs3nILAj35x7+u/Y8X/uNa2HRYVzYkcAcQrW9cyGhJGbOtCyQJvq+3LEsssXhl2602/zvFC2xU1WzvPXf7aCWrOHGOTz6p1yRBrFGSmsxpphNxccMVW96I6J2784Ff3QOuFL43RHZrBIlry4mqXeBVVeo/giTkxYJKXcxQ8VBhbhSSPKg9ANQqRXNMmQayCPZQcvAYeeiI956HQdV5LvcYwUQzBBXPBT5wPToVFVEuUxJFljxQxQQeNCGL14b4cJMQ+FzHpuZDbDfzvir0I4E7fdIbr8neH/vYF4488iu/tYuHkhQcvn3YwTuIZjf291ecs19p0Phgxmax4bHPilg9xTa4yRkJg7W9KJijDAjiAAUCMfCw8VQZU5WTomq16jJeuuwhXq16yWo6T+uBulKDK9CAxMAcK2tF8wKYzYmVptTB2aRSCJnDHgnWSsxbuRGtmjnYxtyYgtKcBXRkqJDB5iRiSvxVBlsCdr1hAM/tRMj7IY3joiPjDHGOXExrT6xHI4l9ZDQd1SQ426S1GCRkEAOHexyamn4YWI5FEPqnt5BjzJglBC9MT+Z5OoeIha19mxTqDULce2+t/viiMzZV6XusUukXcw7FOVFV4ScV1QwCsrxPHwLzwMseGiIn8liFoQH7vGcdw0Mwx89HR7jqq2kVXZU1GyiHu2o12K+JSIinkXm7qmqdClzEaRMCo1PqiN1DyDEaAujoKdBXCCYLV2bcdxJ89bHvfvfEg1ZZZelh+T3MaFEtcTU+KIxhVwGXhagz2gMntZ0QLsZrJSpaqwev6YaTPfVqaL36r5O2HNVYEtvqFHu0ZkDkMcFoIuOy7YbNTngQUDuwEcRJZGlQ7muESmYmQa+0QxTjjkRVJf7A87zwIBObB6OKWgPC0ghSDnAWpcRGzbZgCVlVoRGaV+nmRnv69elSbUAXy7EWTpR6CFQOtYwq9Yxq+QGGuiabHSZyaiD5DW65ISU6rxs4wNFh90C3tnasDIoQwQfK5mPhpp/f/rnO6T3OuYRpZmOQuBYMd3ttgKuNLq6RIKwLMIFnPT298pGNNrxk7Nhl/moVFmTaceuPvDJm9Kiu/mqWuiRxOJCZ4qyAGbdQIgoskSRixX3BRs3G3drSLA89+njbI3//e+us4qPWhEYnRFR1JnJCv5HUcYvQp61D7EmdJlaP+UIpUqn1RSXuVauEIkAib03s3o4UbzNqWN1IM+qpmg3WFBy1qnFFqsXAHsSI6SkLXAuVAg8uqOx91QVkz8OMxSUsomzSa6/53bbd7htnnn7sZbUWhpztvffe1QvPOuHAiq/eb3+PUtX1xJUuYgPDLBUBOnXOi3OILlU1nZKhyMbH2GwMAT6DvPg4vkwCDifjlPhwRmf5wN4YQvCBa0SEe4lUxNp0SJbHcVHKmTPbX1CKsH/SXxBJu7q7nqOHF3xsJ16fiV1PQrQHPFu/jSSgp594nffUb6B8jqI6beRCnF17yTRbyGE/kbFhFQIRWSD6xkB6bqHWHEUVTe1CWsJ6TuIEJ5IOiDwYbczcC1bBKHYfcIxCfY2bdshoVG+vTR04MwHMBx1lTBN5xi6hB/DFh5BSCWawh7j2eelIqDso0X6V5KLvn7S9aPXujBfdJKFpVRHVHhLDztaRwyLPbPEVASRD6ApAh10OEh/XIxNV4+EtPF+7gSGaXuJ1Ps6t2IwIAdxVVCyKLd/Yv4gYFxxdEVFRbLA1I/EyT3uOl/JyuS+t9lcfv/CsUw7Y+iMb/IvSYY9m2KB1GjSpCsMVCwE0WBEm/hcBmuGnFBjBhj3e+dqrV3RVQtrEAyJRxVgZIBYLo1AZCHxG4aYTFjVRxJOyTio+8DbsgzCfYuMJVomxKWR3gqOVOiW0npCfmRx5yClcxVaXcmMjcT3tlZB8tAHBOGaKqtKSRo4oLlF5oassr5azKIvZIBJZ3gyZ/xmVirXCQP90WsuJUNS/qOlk2ELd3jgEMjmuGGLGmBW2ntAbvpLryMl8HZ783VOfbbCT9BDYF6A4eMOdcTPBytuyKk9eUUH2iCy/UGrvaC9/bLcdf2K6+RqAWTB++eWXn7rl+E2P6uOboMO5BIMUcqr22VVFFBJCjRuEgCkpp5yvvfGmPHHPIx+m9F1jCIGrVfihXVLLxU7sDjVC52qk5CGBOrs7u/rK3ZKm+BUaKlaDjnhKiD1g8vsXhdYI9tYYWMJ5nKGPlWOCrsa1xtHk0fJR8rEJH5rspmcFVRiL+OCbkCV4X3bc1z6EbOqkN9KD9txr5wvOO8V+J4te4/XDkowfP758xfmn7pSk6e29vT0tzhxKrGMUDgN8zgGccaqqqKo3Esl346D1rSqIjY9EAnuvxMELhQzHIjorjzRDZi44fQu2o9j11Bexfs1JcKLsuyow+nQq2NbT1dl50np7br/qfnvvvl3ntO6eRBNHFalR7mTRd4Cwgfbj6RFcOEmlL5mzwJjLNi7aTT3mwplLs5klRV8eIkfjeYrAo8acXKTZjLSQ+BBP/3hBAcLa9cAY1y1d2dKxAssbVqwklWrAsFrdoWRTmpttqzfbbIzWlSOpYS8tGAMoBk+Ovfc+88y5xF95kEELm266ae8ZF52+R6mpdFEVR7PUYPc6/bNG6cRswpS4ngwnVKGNNUEMZYMqAGQAshA5qNc5tuJHWL1IJPJfRGtiKNjCi6sPBRMk3BCmgpgXzUxFiTncYOW9c4nr7u5Omxubn5x48bn7brXVZv+kfJ7ECNBg9Zw3pjbxtSZt6MBg4ACKWNaoVqpRX8u8CxvsIiZb731h6sdGlvj0RuNmt8MWIyUvyHVbzVzP2xLrocwQHEe1XthuWE2l/sxLnxGfZ/qyTCp8to6cU5T+SFWOxb1Uecvm7Uri0SCrKgQvwTjkIWSvok412P/KIQS6E27w4MRsMVKTFAlSFYmSUMlJhTb+NblLJBG74ySwiM1+Y5FLLaiIqor9kMiMgEFCX2GGxiTO/hmmScNE1n+Nou10G7MxUbGxREK/oMQ3J09jXIF1Uq3POfPHtIrU1oSLpzpOHUqFOenp6cnWWWtcZfNNN3hyQcHhvcax987bXFlqbLR7ifvEGTYiqmyw8g4hgKGHWDihWnnzzak8Fd6h2ttU3BoiLDMliWQKSJUcfr4aRVklTRIxp3efvfZYbe01V/3QtDff8A0Jn0UoklpQEe5foTXskLeGqGGRR25FCFgtVJY8KKxGxsgJfYto7RbgAhEbY0pSQcnD2LOG+ExOJt8DvHcqTZxmZD3dnemxR31jldNPP+YOZaPh0mGP9rC+6aoL9lhi8SUvmTJtqjhCboRWRJUYqeLUiSNRjXkRlVoAIcMs5HMbBnhdX+Psh6Fez9vvpbKNxbyVW1MhNqmqoqrx74Wqk0wTBT+R7q6uGzdc81vfuX7ChMpxRxz2bH/Vt6hjKlVtvXGCJSUgtrrs0XEOhD08kqc/77Oq9TIHZOv6RcznkRPbpY9AM2Z3TmiJyGjzaCdZ6lVn+k21vOC9U59Njzj5YM6JIFu/NkYcFfGAI5CDPEh5IagI86PD+efSsC0+1uz022FCLWKJ8CJnFEIWAC0E5hqzqZBAgxq3W2ut7qsvPutriy26yBnT+fSU8gLLRNT7MLsMH+MZawPnMoAnL3qsO8GmAA9h5nVrcp2YY8qFconcrkUXW4drFGLCehXmg6iiQncsXCRHhtN0Ee53r07d1ClTZJkll7zxZ1dfsNn4TdZ5XuZhcIPed5C8TbAJjDy2H2JKojmFnIdYbjLqYYxX//PNT77R1582p4n5ZTlpzRpVyfdgJhqbSOPkBZGmLEilKpp29lUl6Re/eHNjz9ItpelLtjVPX6a9uWv5kVBHc89yHY09y0BLdzSWl25v6lkGWrajqWdZykxeoq2pZ/HWxp7Fm0s9i5UaKiNT14Wf6rNMm4LwEAmShcCGhy1C/i1U02FlLBECi0pe6S5Lj/eSKApiCCS1GFUxMQUFjNUk4W7I+Yx0RrXgxgp3x4yiIZToFR9X4oiU1EjEzDNT1dYLZlteKJWgThaQ0NrSZE4Ce4t3JHFUjN6pqhdFYqg8i3uUh5w69k70pcZSut/eux2pyPGCD0Cy0UYbdX50kw2m95bLmQIIi8MewPHULpjEBh1Y//WN3CDh3gVCLY1ZaOQbIdgiMu27kS2yWO5E4ZBCQqKiKIjITI4kOJnl3h45/YQjXrr+6sv/ss3mWyw8dfKbXoKPv9ifV8+vsVbNFuN2KmE85vNisbzVV0usCxQaAjkjFERVJc3jDCmgMBI+r5qjwEPXB89YJVYnmTKtK2tu7bjtiovOav7yIQc9wwWDGWe7rZVWWqnvgTuv/fxW4z+8++QpU1+zF3CwbBIblOIwqUsFu0VUVFn8KrUAYmBiYzPAIq+VsBZiFMpnkKc0YgO3fczuJ7GpE3VqlGneX4tLEp8kqatU+lOt+pu/9oVPfPb66/eu2oVGDZxcaerK6hKB+DRNWyLxfg3BW3AkwiECMZM0TbxdNwdUxtKnvP2aA2MJgRM6n49bRIkQ3Yoq0cgJwfAqZVnDgL3oZin2Z6XFCR4AABAASURBVJUngw+vYHsGAR054UbiatWIkqjGcfPlIHEEATrX1Nj4F6oMebTP5UovgOpC8C1mHXOff+0TyUSVaOS8OrAgnzrtaW5mT+W6wY62dm//2Y+P/uimG+/z5uTJr2jwQOKYa1tnzJWEjD7tXmxi+spRi8AUYnbMUcwWgS7USGo85q2W5eFUrKUijDAnxmc5p05svJrANf7DOec0kSRpkEpWdV1d3dkO22598gN337CX2SzzOLhB7R+4IzKGJw2rcSMgkkiSbwYwqxfLTR5mevjFaRc1Oldm5bJtBOHGGSA1w8xmiPm2NcBnC/HeNqSgpYas2rXPWkuPfujADyU37bFS68/2WGXETbuuNOL6XVdqv3bnse3X7DK29eqdx7ZetdPY1it3HNv84x1XaL1shxVaL91++daJ2y/Xegl02Q7LtV6+0/KtP95lhdYrd1ux8YY9Vh5xxkpjm5Zubjiv3GcQaQlsnPKZ3haYROyYKlUKVdBHHvXYaXx6XyZv9mTiqBZvRnlrUOqpqeA2LhumZf+bbJNhY8sLqJ0LQ5rSizJGDaSQ1IhcPk7KBQrUMW7EGPyQ2jRMja+33pqv9JZ7bU55aDHcADHOIHZqwsPM5lyVk5TEs6lUensq6bjVPzRp9523mzhMJr4vulHVsM1Wm2/c359x2zL1IfBgrJY8y9X7KjeoFx+MG2ViOvt60NzSUt5o3Y3/btfPwkBo+O214iqsKRVuJKKwJBGZOHFisxB+9KPvTz7mW4c00aefPm1qWShXh8tgkcUaeNIEbEVk+eb3Vy5z8UxR44UqQgcBkiiSiAXjtkMZZ50E0xnnAZc37lXUqSa+r6+fE++y32XH7Xd7+vG7drfP1XnteZ8qc/mTC8++5dQTv/3Rjpa2h6Z3TSujk8Qlhj+naIH3eeHkaoatESuSMEBx3OzPQTzYxnUAvjkPUR/rCkpwQuMkGHY2IcIVgnMmHEg5AbSsu7v7xaUWX2TLPXe59+Nf/epX2YVn6ps9FduanDrBTk+CjbSR2+KC9xLopuqrrup9tvwyyz4+4+pZl6ztlVZa8Xlv7UE0aoPMmQrdao2wQ5CdSqWaVZZaeqlXFltsRFVmMyy9+OJ/HDNq5OS+SiUVOg3ep5ALIXDIwYBURNU1YVfZ8bxkuL6lpTldadVVjpZhCKussopra232Wdbfg30SfLCXykwChzCiqYgZiJOnnCwzN/04WIsstNA/Vl5x7JB94QGL6oXnnHTbScd9ZctSkjw4dcpkCRrwH5zNFfhVBfyYvVCCC0vP8pCINwAhInlWJAJRyFAviBdEk7hohh6d5RmqMF41DkUuIs5pySVJOahLp3V1y+KLLn7bmad950M/uvDM41R5flBnXkeQGXQTwEpBpt4uMsBFBWBFbeSmV4MtqoYzebWnv6k5cbz0BBaHiDP71CwI2JNzS40Cjp5XpYqmbV5e+NUnxrV/e82RU6xsjukdLlx/fe2/crflvrruwun25SzYaQ218htJhR8lC7c0WAKFmuDQV1Vlaj/jcU4EWWJ9qwDVKtqit3mIRWIBiYjkoVpkrPnFlp9Jb9mhoWieJZhan4poVi1fL8p7twcFpTww8vz8ne607VZfSCX+Lp8om1Xw9jJssEMMk/nKjCXKLpI0sGa97LnbTt9UfX9sIMOJ/j677/T35ZZevKenpztjLZeC55Fe7RcPZlXjRr5f0Is6la6uLvnIphv0bLLJ6rP0J54cGKuoEAFfYiA3wE3WmDArPBKqLMy+Pjc6ViD5/Oc/3//3px5u3nSDdY/onj496+3usZtRHF9MeItgaftom3Cd8AwX2sjJFrpIbFskcpMdfakq+TqJWDt2TahdX+OZE0as6voqfVnXtGlu9ZVW/t5PLjq9feJ5p96mysKS9184aN89nnnsoVu2PGjvjx+Y9fY+09MzvWSHQZwEiqpwYsiD1x7XjNWHKmiBn8lIgbyR5wUjmI68yR4e9TWOjENYlRAxN5wDn3vFEZiPUO6cPs2H/v479t3zY+Pvve26+ydM+O/7aunFF34Jr7QrSTlpleCwDTBDZnMRgvcQeZ9Vs6osQd2dt9/hRyjmKO646zYvjRnZdmd/fz/PJakoY4v9Yb8q68CJqNO4puLvAYPFHjtv9/UxY8a8IrMZll9++fJHPrr+NXafJEnaE0Lmg+EmPhXGCTFs7zVIk0ucY3xu3GqrPbfzNpv/dTa7mqPqa665Zv86a676WGdnV0vKKV2Qagms0xCnyLNGAuRBI3ASzWFyfyXbZost7hg7dpm/z1GHs3iRcj99Ys89/3bJ+adue8Ceux3q+ypTu7s6M6chs3Wl+P7g6KDMe3xiw5TlIsylEXphHDkxfx59zHuPLkAeS+oyeeqgRBcgYcyQc5IkCSUh6+zqdCHrf+ngfffZ/qG7r//YvnvsNM+/WMhMgSU7U24uRSDgVqg3oqxRI/KhxhG5sy0VNJHq+agcpmRKuV+SwD0cBP+RTjGGG4k9I2AOo0AVYy6mXqScVSrlzfZddYWoH8Lk/B2Wv2uhUvJgn+dzgCRgyBSpEw3uLb1GoLGvzm0RvtFZlplfZxkW19Uuo67lBU6coYwN1LKwepnms4NmGKJ1OhOZSYE1E6nePXmJBCTUDVC9aH7m4z+68a27775j+bXXXpMqPlPiEuaMh5ntpEHYs1yqmvQEdTL1zTfdrjvvdNmB++52xfw85jm1XVX9Fw85eD0eOq6vr49mlMUgOJn5hixCHnJpKp3Tu2VExwj5+iGHrMV1mcxCcKrinIrSDyRIYkEtMYoCC4/I88BuOcFjSayoTlznf3r1Jed979RTllhz3TWv6uzslmnTO4WppT27h2MjIrQhaHKaObVySI2ob5ytSiMpVxg5UXVoElE4bo7rLpezLk4yVh674qWnn3ji6Ntu/snh48ePL8v7PKhqdcJxh99w6Q+/u/YWH/nI17K+6vSpk6ekfX0VLFce3A4ODdz7QQKuT2ADiHNA4qG6LnIrhzyU58WLqLjEWVrq55y0c3pnuVrJ/rzTVltuecLhv/z4ycd96zkRkXei/fbbeeMpU6a0Ja4hTVzCS5+1o/bZtoL9oirisa9z2lTZccutJ48bN/bFd2pnVnRrrrjiG/vtu+ePJr35ZpmGOSxLxamDErL061JJ0pIkDSWZMnWarLHqqjfttst2dyprdlbaf3udz+2/14ULjRn5x+ld0218TgJYgRb4eiB2tOvoPMsyXwpZ39RDPrnvR9/exlDl6bvylUMO3MelblI3X3vStITHRm/eDl/Eq/Cjzrskcb09ZTeifcQrB+y/t/3O8cyPQS4YmrjGGmtUJhx/+MSJPzpn1Y3X3+AHPd19bsrkqb5SqTQJ60ElSY1HwtoArnFNcu+Dr4AvFCCRuE5R5NzyUK2ejTTYWB1T4RIR1kOl0i/Tp03HQ3D/2mWXnY69/srzVz3xhK/dpXO4DmQIgxvMtg1E8YIHHwHyodZ4zhVlrjCHTgxQyUty7fClnZl4x4TRe7TVzEA2ZmbBsZVJFUhZxP2Zb1p1kY5bJwzTBH563EL7cEqCc6ssS07uMC5flIHNTCIFrEQt9RBEZTobcz+4OsxXKJa9hZOhgDQWCddECsKbq8SgXC+0baTDNF6t21HnAVOMzA5u1gBFER1R4voRtj6tSVSfXyMYh++fdvyoT39m/9sqWebenDIt66uU+ezi2fCD9JX7smnTprVMnTLN77X3njefdcpRn5tfxzoYdu/LW/p3jvrmx7pwqqZMmerK5XLeLAujP8sqvb0VmTq1yy+z5NLZBWectvpaa630n7zCu6c2DwknjgmbuHOJUzZydCKqMhACd53RgEJExTfKO4S9997xjVuu/dGB55x28iKbbLD2VeWesp86darv7ekR76uZNeucE4i9KBFRtmKlL6JEjmBRlXKcrcTFkwvHJ2Xn6LUafE9vr+DE2h5R3nj99b512g9OHHHXLVcfuv/+Ow/6lxYZ4rDpppv2XnzuqT+45vLvL/GpA/b5Wkdbx7NdnV3CQ1vKvb1AVgUWFXUuc4kKGYuZgIWSFSAxqsmiqhRp5GzqaZWTwU6c/e7Ozp6mNLlz5223Gf+dI+/d6Lzvn/Tg3ntrVd4lfOWzn31p1523/PLrk95gfXFvBu/VOWEuUvPEest9rLlp6Q7bbff4V7544Bbv0tR7FqlqOPabX7x1z10/9t3XX5tU6eruoYsgSZpKQ6khOrWV/n4/6Y03ZeyKy997+neOO2b06NHT3rPh/1HBfmfvB6dO2Lm1raX8xhuTfKWv4sBLVARkxWf9/VlPV1fa29U56ajDv7bdjjuOn6X76X90N9vqjTba6KXvnzZh96rvnzxp0qS0UrGXD2/tkATJKpnrnN4ljaXSK2eddtLh41Zd7iErHE7aeNy4135y6fcOv+6Kczv2/8TeJ+LsTp82tZOXvk4xe9k1BEC9Jk6YX+TAa07wgcdXLGNHgMfxwMWevVbGg459Qr0qmmpVent7KlOnTRXWcNfiC4157IufP/hjl11wyhrnffeYM3F4u7jwfRndYFtlzpA5BLHdADiR1NZtVFkZqgHZ8nlmeNJbX365hbeJ6FzyvIjdmz3McLQxiM0o9ho3k1Qd+5PfdKlRx1h2OGiP1TreXLIpkUqVJRcMwyA+cpONArYqZLLZi1WUZ9SvMig0KIh1IXLqwwMrNgiCUaCOEaweY5aEWFfNAp/bKtgTjGjHOoYYhojPddyLYhMVeZyoWj3YghBV1Z923Dc/dsl5Z2+/7VabZyNHjMi6cUa6u7sqrS0t6ZbjP/L7888+aYvTv/PN3a3ugjDmuRnD5z+7321X/fC8xbfZavxtCy+8sO/pKQtUKaWl0grLLDv9cwcdcOI1l53Vsfnm683WZz1njkPiRI1DeBGiyho0wmCWJSmRxWn7nPdekkxGoPmf0ZzNn139owOvuuR7rQcdtP/Hl112mSe4rsxLg51EZF08IM1RzvozQR9joF3PQyVUPSdIVceDKu3tLUsXa2J6J2c6ONZpmmZrrzPu4s8c9Kn1fnbVD0f99MoLzjlgxx2n/09D5pOCtdZaq/u4ow77wWP337TaaScev8Guu+xw8eKLLfJaN3M8Zdr08rRpXWl3dy8P3HKZT8ppNeOMmM0x+OCM7GtAP6c8PT29Mm1aZzZlyjTp6uqaynQ+vf7a65x78jHHLPO7R+/Y8byzJzz2Xs5lHTJVDd//7ncuPPabX91nueWWfkhxv6Z3dcn0zi6XZd6NXWH550749uHbXHz+qZvx2Xqu54D++i4+76STzj3j5L3HrfGhvzDEnklvTpZJkyaXyrx0LjJq1PRDP/3JU6//yfkHjRu30lz/aZrNN9/wxWuvvnC5fffa7eqFxozpshehKZOnVLq7ulxTqWHq+uuMO/nic85a9aD9d/9NHZPh4mAR9vjYNg9ff/VlH955h20vGzmio2x749Sp09Jyuew72tp6thl9PZvyAAAQAElEQVS/+VVXXnfx6ltuvt51Vn+4bHt7P7Z2Tz3hm9/50QM/X/iU7xy71bZbb3XtqJGjX+stV2wtuunTO3lfKgv3eupZrzzAK+wu5kiKE3H2fAvee9Y1L1Zl6ersStkn/NSpU6Snu2v6kgsv8rsD993ziLNPnjD2vjt+utm3vvrZW9dff/3+t9vxfssztsE2CWeGJkOIfkGe1DLBHAlRSi3CVUVVLTNs1N/TtIJgRzD7gnheE4U9KtoZBFtCFJl/Ck2mbhLUjfYdLwybkXQ0pikpZ56NE5lnDvZgFw5WqNlk3MYhMU8luG1+qozBInnBdkq40CIKohjVlfBgKxtej2oY0IbWFcPBzaYa2bhC3e7Yt1nydmI8b6kTK87Xiar6LT+63l2XX3jGYnzq3Pbm/7ts36svv3C3m2/+8dKXnnfqhjtvv8Wv5usBDrLx48evP+nHF56+85X33tjy08vOHvGj885e5oYrr+n45W1XjTrh21/6ztJLL907u12qOmEeIklNFqmtPWNCYJ2agxm4kXgg2K+nLI72PaN9uj5jwpE3/+qeGzf8v8t+sOjR3zrsw/t+fNfPbL3l5ketvtLYK9uam/6pwWfVSiWr9PWSVDJfzVxjmnYttsjo8lrjVr/54x/b4aivfuEz255/5rFtf/zN3U03Xj3xCxOO+dIfOMWw4533tGF+qqCq1b133/YP55014QsP3nnDUjdccckyx337m4fsufsu52yywQYPLr/MsuVRHSOy1PERJoQs+FDhmqwhbZCFxiw0af0N1ntwr4/vevkXD/n0gWecftI6V0783ro3XPPDw+yUl3rM4uyhwTX+swfvc/NtN1y+xS3/N3HhS847fbOzTjl2q2su/eGSd9x0xSqHfHqfX1LnXU9EZ6dH2vL77rvTz2/+6cVr3X7DpatdMfGcL/zgzJOOuP6Kiw75+XWXLH/MkV86dqGFFnrpPducxQrjVljhtXO+e+wnb71u4hJX/+SH651z5kmfu+QH39vqlp9OXPXaH59//NZbb/TmLDY1JNXWG7fyXyeee8pnfvPgLSN+dsUly008/3ubXH3JuavfesMli008/9QD11l++alD0vEcNLqGauVT++/60CXnn/aJJ35169LXXHLuuK9/+dCvf/xjH7t9zTXGvTiyfWRPqEq53NNX6ursTqdP65Sp07qyzq7uSl+5UmlqbOpadumln9lkw/Xv2XXH7c487IufW3/iD85b5v67rtvs5OO+ceauu279mq2POTBtnlziBrvXePfGhJbhRDwCduhghIjCnIjA5q3oQqDeMEYeEKvyfIiOJc6ls/5zyu0T7Ip5qYeQOS5YoUxSVw0Dd6J8MtUsdzBx3AccTMMQ0CxGwm5sNpPYaClEMr3pjBuhMuvjuEiIudNqeogq+XUm0xxRRGMqwxF8bR2YXWZn7BOdyNtswFCFhDIYiMgCF1R12sKjRt2/9rhVfrrJBmvesdSYMf9BN0u/U7jAgTELA1pJtW/jjTeevvXWG7+26qoLd4LVHK8LrpU6SVx6KpYnwRKF6rcJLiY3pg9enJP2WDAbCacPPV859FOPnHnqMVdcesF3z/z59T/65BMP/2Lsn35zT+Nff39/499+/2DjX393f8PTT/wyefKR29rvve2nzT/9yXm7f/fEI0//+lc+9ch2223XjV1hNrqcr6syVr/hhuNePPTg/a78wRnHf/3/rjh/iwfuum7Ubx+9s+FvTz2UPPunRxv+8fSjjc889XDDX3//YPKb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" 
                    width="100" height="30" x="0" y="0" />
                </svg>
            </a>
            <div class="site-actions-scroll" data-site-actions-scroll>
            
            <a class="site-icon-link" href="/community?apartment_id={{ $apartmentId }}" aria-label="커뮤니티">
                <span class="site-icon-box" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><circle cx="7" cy="9" r="2"/><circle cx="12" cy="7.5" r="2"/><circle cx="17" cy="9" r="2"/><path d="M4.5 18a2.8 2.8 0 0 1 5.5 0"/><path d="M9 18a3.4 3.4 0 0 1 6.8 0"/><path d="M14 18a2.8 2.8 0 0 1 5.5 0"/></svg>
                </span>
                <span class="site-icon-label">커뮤니티</span>
            </a>
            <!--
            <a class="site-icon-link" href="/community?scope=region&apartment_id={{ $apartmentId }}" aria-label="동네">
                <span class="site-icon-box" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path d="M3 11.5 8 7l3 2.7L15 6l6 5.5V19a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1z"/><path d="M6.5 20v-4h3v4"/><path d="M16.5 20v-6h3v6"/></svg>
                </span>
                <span class="site-icon-label">동네</span>
            </a>
            -->
            <a class="site-icon-link" href="/community?scope=apartment&apartment_id={{ $apartmentId }}" aria-label="공동주택">
                <span class="site-icon-box" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><rect x="6" y="3" width="12" height="17" rx="1.6"/><path d="M3 20h18"/><path d="M9 7h2M13 7h2M9 10h2M13 10h2M9 13h2M13 13h2"/><path d="M11 20v-4h2v4"/></svg>
                </span>
                <span class="site-icon-label">공동주택</span>
            </a>
            <a class="site-icon-link" href="{{ $isLoggedIn ? '/messages' : '/login?redirect='.urlencode('/messages') }}" aria-label="쪽지">
                <span class="site-icon-badge-wrap">
                    <span class="site-icon-box" aria-hidden="true">
                        <svg viewBox="0 0 24 24"><rect x="3.5" y="5.5" width="17" height="13" rx="2"/><path d="m4.5 7 7.5 6 7.5-6"/></svg>
                    </span>
                    @if($unreadMessageCount > 0)
                        <span class="site-icon-unread">{{ $unreadMessageCount > 99 ? '99+' : $unreadMessageCount }}</span>
                    @endif
                </span>
                <span class="site-icon-label">쪽지</span>
            </a>
            <a class="site-icon-link" href="{{ auth()->check() ? '/settings?apartment_id='.$apartmentId : '/login?redirect='.urlencode(url()->current().(request()->getQueryString() ? '?'.request()->getQueryString() : '')) }}" aria-label="계정">
                <span class="site-icon-box" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="3"/><path d="M5 19a7 7 0 0 1 14 0"/></svg>
                </span>
                @if(!$isLoggedIn)
                    <span class="site-icon-label">로그인</span>
                @else
                    <span class="site-icon-label">계정</span>
                @endif
            </a>
            </div>
        </nav>
    </div>
    @if ($showSearchBar)
    <div class="search-bar">
        <div class="search-bar-inner">
            <div class="search-bar-logo">A</div>
            <div class="search-bar-input-wrapper">
                <input 
                    type="text" 
                    class="search-bar-input" 
                    placeholder="검색어를 입력하세요"
                    aria-label="커뮤니티 검색"
                />
                <div class="search-bar-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24">
                        <circle cx="10" cy="10" r="6"/>
                        <path d="M14.5 14.5l5 5"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>
    @endif
</header>


<script>
    // 스크롤 시 navigation 숨김 처리
    const initNavHideOnScroll = () => {
        const navElement = document.querySelector('[data-scroll-hide-nav]');
        const searchBar = document.querySelector('.search-bar');
        
        if (!navElement) return;
        
        // search-bar의 top 위치를 site-nav 높이에 따라 설정
        if (searchBar) {
            const updateSearchBarPosition = () => {
                const navHeight = navElement.clientHeight;
                searchBar.style.top = navHeight + 'px';
            };
            updateSearchBarPosition();
            // 윈도우 리사이즈 시에도 업데이트
            window.addEventListener('resize', updateSearchBarPosition);
        }
        
        const state = window.__topNavHideOnScrollState || {
            initialized: false,
            navs: new Set(),
            searchBars: new Set(),
            lastScrollY: Math.max(window.scrollY || 0, 0),
            ticking: false,
        };
        
        if (!state.initialized) {
            state.initialized = true;
            state.navs.add(navElement);
            if (searchBar) {
                state.searchBars.add(searchBar);
            }
            window.__topNavHideOnScrollState = state;
            
            const minDelta = 8;
            const revealOffset = 8;

            const setHidden = (isHidden) => {
                state.navs.forEach((nav) => nav.classList.toggle('nav-hidden', isHidden));
                state.searchBars.forEach((bar) => bar.classList.toggle('nav-hidden', isHidden));
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
        }
    };
    
    // DOM 로드 후 초기화
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initNavHideOnScroll);
    } else {
        initNavHideOnScroll();
    }
    
    // 검색창 기능
    (() => {
        const initSearch = () => {
            const searchInput = document.querySelector('.search-bar-input');
            if (!searchInput) return;
            
            // 검색 제출 처리 함수
            const performSearch = () => {
                const query = searchInput.value.trim();
                if (query) {
                    const currentUrl = new URL(window.location);
                    const pathname = currentUrl.pathname;
                    
                    // 홈 페이지에서 검색하는 경우 /community로 리다이렉트
                    if (pathname === '/' || pathname === '') {
                        currentUrl.pathname = '/community';
                    }
                    
                    currentUrl.searchParams.set('q', query);
                    window.location.href = currentUrl.toString();
                }
            };
            
            // 엔터 키 처리
            searchInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    performSearch();
                }
            });
            
            // 돋보기 아이콘 클릭 처리
            const searchIcon = document.querySelector('.search-bar-icon');
            if (searchIcon) {
                // 아이콘 자체에 클릭 이벤트
                searchIcon.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    performSearch();
                });
                
                // 아이콘 내부 SVG에도 클릭 이벤트 (이벤트 전파 방지)
                const svg = searchIcon.querySelector('svg');
                if (svg) {
                    svg.addEventListener('click', (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        performSearch();
                    });
                }
                
                searchIcon.style.cursor = 'pointer';
            }
        };
        
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initSearch);
        } else {
            initSearch();
        }
    })();
</script>
