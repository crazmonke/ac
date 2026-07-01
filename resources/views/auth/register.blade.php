<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>회원가입</title>
    <style>
        body { margin: 0; font-family: 'SUIT', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #eef4fa; color: #1b2d45; }
        .wrap { min-height: 100vh; display: flex; flex-direction: column; align-items: center; justify-content: flex-start; padding: 16px 20px 28px; gap: 14px; }
        .card { width: min(430px, 100%); background: #fff; border: 1px solid #d6e1ef; border-radius: 14px; padding: 22px; }
        h1 { margin: 0 0 14px; font-size: 1.45rem; }
        label { display: block; margin-top: 10px; font-size: 0.9rem; }
        input { width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #c8d5e7; margin-top: 6px; }
        .btn { margin-top: 16px; width: 100%; border: 0; background: #0b7a75; color: #fff; padding: 11px; border-radius: 8px; cursor: pointer; font-weight: 700; }
        .err { margin-top: 10px; color: #b42318; font-size: 0.9rem; }
        .meta { margin-top: 14px; font-size: 0.86rem; color: #53657a; }
        .autocomplete { position: relative; }
        .suggestions { position: absolute; left: 0; right: 0; top: calc(100% + 6px); background: #fff; border: 1px solid #d6e1ef; border-radius: 10px; box-shadow: 0 12px 24px rgba(20, 35, 60, 0.08); overflow: hidden; z-index: 10; }
        .suggestion { padding: 10px 12px; cursor: pointer; border-top: 1px solid #eef3f8; }
        .suggestion:first-child { border-top: 0; }
        .suggestion small { display: block; color: #64748b; margin-top: 4px; }
        a { color: #0f6f67; text-decoration: none; font-weight: 700; }
    </style>
</head>
<body>
<div class="wrap">
    @include('partials.site-nav', ['apartmentId' => request()->query('apartment_id', 1)])
    <form class="card" method="post" action="{{ route('register.attempt') }}">
        @csrf
        <input type="hidden" name="redirect" value="{{ old('redirect', $redirect ?? '/') }}">
        <h1>회원가입</h1>

        <label>이름
            <input type="text" name="name" value="{{ old('name') }}" required>
        </label>

        <label>이메일
            <input type="email" name="email" value="{{ old('email') }}" required>
        </label>

        <label>아파트 선택</label>
        <div class="autocomplete">
            <input id="apartmentQuery" type="text" name="apartment_query" value="{{ old('apartment_query', $initialApartmentName) }}" placeholder="아파트명 또는 지역 검색" autocomplete="off" required>
            <input id="apartmentId" type="hidden" name="apartment_id" value="{{ old('apartment_id', request()->query('apartment_id')) }}">
            <input id="latitude" type="hidden" name="latitude" value="{{ old('latitude') }}">
            <input id="longitude" type="hidden" name="longitude" value="{{ old('longitude') }}">
            <div id="apartmentSuggestions" class="suggestions" style="display:none;"></div>
        </div>
        <div class="meta">검색 결과에서 단지를 선택하면 입주민 인증 요청 시 해당 apartment_id가 고정됩니다. 위치 권한을 허용하면 동네 단위 GPS 검증으로 인증이 우선 처리될 수 있습니다.</div>

        <label>비밀번호
            <input type="password" name="password" required>
        </label>

        <label>비밀번호 확인
            <input type="password" name="password_confirmation" required>
        </label>

        @if ($errors->any())
            <div class="err">{{ $errors->first() }}</div>
        @endif

        <button class="btn" type="submit">회원가입 완료</button>

        <div class="meta">
            이미 계정이 있으신가요? <a href="/login">로그인</a>
        </div>
    </form>
</div>

<script>
(function () {
    const queryInput = document.getElementById('apartmentQuery');
    const apartmentIdInput = document.getElementById('apartmentId');
    const latitudeInput = document.getElementById('latitude');
    const longitudeInput = document.getElementById('longitude');
    const suggestionBox = document.getElementById('apartmentSuggestions');
    let lastController = null;

    if ('geolocation' in navigator) {
        navigator.geolocation.getCurrentPosition((position) => {
            latitudeInput.value = String(position.coords.latitude);
            longitudeInput.value = String(position.coords.longitude);
        }, () => {
            latitudeInput.value = '';
            longitudeInput.value = '';
        }, {
            enableHighAccuracy: false,
            timeout: 4000,
            maximumAge: 300000,
        });
    }

    function closeSuggestions() {
        suggestionBox.style.display = 'none';
        suggestionBox.innerHTML = '';
    }

    queryInput.addEventListener('input', async () => {
        const keyword = queryInput.value.trim();
        apartmentIdInput.value = '';

        if (lastController) {
            lastController.abort();
        }

        if (keyword.length < 2) {
            closeSuggestions();
            return;
        }

        lastController = new AbortController();

        try {
            const response = await fetch(`/apartments/search?q=${encodeURIComponent(keyword)}`, {
                headers: { Accept: 'application/json' },
                signal: lastController.signal,
            });

            if (!response.ok) {
                closeSuggestions();
                return;
            }

            const payload = await response.json();
            const rows = payload.data || [];

            if (!rows.length) {
                suggestionBox.innerHTML = '<div class="suggestion">검색 결과가 없습니다.</div>';
                suggestionBox.style.display = 'block';
                return;
            }

            suggestionBox.innerHTML = rows.map((row) => `
                <div class="suggestion" data-id="${row.id}" data-name="${row.name}">
                    ${row.name}
                    <small>${row.region} · ${row.road_address}</small>
                </div>
            `).join('');
            suggestionBox.style.display = 'block';
        } catch (error) {
            closeSuggestions();
        }
    });

    suggestionBox.addEventListener('click', (event) => {
        const item = event.target.closest('.suggestion[data-id]');
        if (!item) {
            return;
        }

        apartmentIdInput.value = item.dataset.id;
        queryInput.value = item.dataset.name;
        closeSuggestions();
    });

    document.addEventListener('click', (event) => {
        if (!event.target.closest('.autocomplete')) {
            closeSuggestions();
        }
    });
})();
</script>
</body>
</html>
