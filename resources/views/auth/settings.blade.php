<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>계정 설정</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=SUIT:wght@400;500;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #f4f8fb;
            --ink: #15243a;
            --muted: #62728a;
            --line: #d6e0ea;
            --card: #ffffff;
            --brand: #2e4fb8;
            --danger: #b42318;
            --ok-bg: #e9f7ef;
            --ok-line: #b6e2c8;
            --ok-ink: #136a45;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: var(--bg);
            color: var(--ink);
            font-family: 'SUIT', sans-serif;
        }
        .shell {
            max-width: 880px;
            margin: 0 auto;
            padding: 18px 16px 40px;
        }
        .page-title {
            margin: 0 0 14px;
            font-size: clamp(1.25rem, 2.6vw, 1.8rem);
        }
        .flash {
            margin-bottom: 12px;
            border: 1px solid var(--ok-line);
            border-radius: 12px;
            background: var(--ok-bg);
            color: var(--ok-ink);
            padding: 10px 12px;
            font-weight: 600;
        }
        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 16px;
            padding: 16px;
            margin-bottom: 12px;
        }
        .card h2 {
            margin: 0 0 6px;
            font-size: 1.02rem;
        }
        .card p {
            margin: 0 0 12px;
            color: var(--muted);
            font-size: 0.92rem;
            line-height: 1.5;
        }
        .form-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 10px;
        }
        label {
            display: grid;
            gap: 6px;
            font-size: 0.9rem;
            color: var(--muted);
            font-weight: 600;
        }
        input {
            width: 100%;
            border: 1px solid #c8d5e7;
            border-radius: 10px;
            padding: 10px;
            font: inherit;
            color: var(--ink);
            background: #fff;
        }
        .row {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            align-items: center;
        }
        .btn {
            border: 0;
            border-radius: 10px;
            padding: 10px 12px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .btn-primary {
            background: var(--brand);
            color: #fff;
        }
        .btn-danger {
            background: var(--danger);
            color: #fff;
        }
        .error {
            margin-top: 8px;
            color: var(--danger);
            font-size: 0.86rem;
            font-weight: 600;
        }
        .meta {
            color: var(--muted);
            font-size: 0.85rem;
        }
        .autocomplete { position: relative; }
        .suggestions { position: absolute; left: 0; right: 0; top: calc(100% + 6px); background: #fff; border: 1px solid var(--line); border-radius: 10px; box-shadow: 0 12px 24px rgba(20, 35, 60, 0.08); overflow: hidden; z-index: 10; }
        .suggestion { padding: 10px 12px; cursor: pointer; border-top: 1px solid #eef3f8; }
        .suggestion:first-child { border-top: 0; }
        .suggestion small { display: block; color: var(--muted); margin-top: 4px; }
        .badge { display: inline-flex; align-items: center; gap: 6px; padding: 4px 8px; border-radius: 999px; background: #eef2f8; color: #22344f; font-size: 0.8rem; font-weight: 700; }
        @media (min-width: 740px) {
            .form-grid.two {
                grid-template-columns: 1fr 1fr;
            }
        }
    </style>
</head>
<body>
@include('partials.site-nav', ['apartmentId' => $apartmentId])

<div class="shell">
    <h1 class="page-title">계정 설정</h1>

    @if(session('status'))
        <div class="flash">{{ session('status') }}</div>
    @endif

    <section class="card">
        <h2>프로필 정보</h2>
        <p>상단 네비에 노출되는 사용자 이름과 계정 정보를 수정합니다.</p>
        <form method="post" action="/settings/profile" class="form-grid two">
            @csrf
            @method('put')
            <input type="hidden" name="apartment_id" value="{{ $apartmentId }}">

            <label>
                이름
                <input name="name" value="{{ old('name', $user->name) }}" maxlength="120" required>
            </label>

            <label>
                이메일(아이디)
                <input type="email" name="email" value="{{ old('email', $user->email) }}" maxlength="190" required>
            </label>

            <div style="grid-column: 1 / -1;">
                <label>아파트 선택</label>
                <div class="autocomplete">
                    <input id="apartmentQuery" name="apartment_query" value="{{ old('apartment_query', $selectedApartment?->name) }}" placeholder="아파트명 또는 지역 검색" autocomplete="off" required>
                    <input id="apartmentId" type="hidden" name="apartment_id" value="{{ old('apartment_id', $selectedApartment?->id) }}">
                    <div id="apartmentSuggestions" class="suggestions" style="display:none;"></div>
                </div>
                <div class="meta" style="margin-top:6px;">현재 선택: {{ $selectedApartment?->name ?? '미선택' }}</div>
            </div>

            <div class="row" style="grid-column: 1 / -1;">
                <button class="btn btn-primary" type="submit">프로필 저장</button>
                <span class="meta">변경 즉시 상단 네비 정보에 반영됩니다.</span>
            </div>
        </form>
        @if($errors->has('name') || $errors->has('email'))
            <div class="error">{{ $errors->first('name') ?: $errors->first('email') }}</div>
        @endif
    </section>

    <section class="card">
        <h2>비밀번호 변경</h2>
        <p>현재 비밀번호를 확인한 뒤 새로운 비밀번호로 변경합니다.</p>
        <form method="post" action="/settings/password" class="form-grid two">
            @csrf
            @method('put')
            <input type="hidden" name="apartment_id" value="{{ $apartmentId }}">

            <label>
                현재 비밀번호
                <input type="password" name="current_password" required>
            </label>

            <label>
                새 비밀번호
                <input type="password" name="password" minlength="8" required>
            </label>

            <label style="grid-column: 1 / -1;">
                새 비밀번호 확인
                <input type="password" name="password_confirmation" minlength="8" required>
            </label>

            <div class="row" style="grid-column: 1 / -1;">
                <button class="btn btn-primary" type="submit">비밀번호 변경</button>
            </div>
        </form>
        @if($errors->has('current_password') || $errors->has('password'))
            <div class="error">{{ $errors->first('current_password') ?: $errors->first('password') }}</div>
        @endif
    </section>

    <section class="card">
        <h2>입주민 인증</h2>
        <p>선택된 아파트 기준으로 입주민 인증이 진행됩니다. 검수 상태는 아래에서 확인할 수 있습니다.</p>
        <div class="row" style="margin-bottom:10px;">
            <span class="badge">선택 아파트: {{ $selectedApartment?->name ?? '미선택' }}</span>
            <span class="badge">입주민 권한: {{ $hasResidentRole ? '승인됨' : '미승인' }}</span>
        </div>
        @if($latestMatchReview)
            <p class="meta">최근 아파트 매칭 검수: {{ $latestMatchReview->status }} · {{ $latestMatchReview->raw_apartment_name }}</p>
        @endif
        @if($latestVerificationRequest)
            <p class="meta">최근 인증 요청: {{ $latestVerificationRequest->status }} · {{ $latestVerificationRequest->apartment->name ?? '미지정' }}</p>
        @endif
        <form method="post" action="/settings/resident-verification-request">
            @csrf
            <input type="hidden" name="apartment_id" value="{{ $apartmentId }}">
            <input id="verificationLatitude" type="hidden" name="latitude" value="{{ old('latitude') }}">
            <input id="verificationLongitude" type="hidden" name="longitude" value="{{ old('longitude') }}">
            <label>
                요청 메모
                <input name="request_note" value="{{ old('request_note') }}" placeholder="동/호수, 인증 참고 메모를 남길 수 있습니다.">
            </label>
            <p class="meta" style="margin:8px 0 10px;">위치 권한을 허용하면 동네 단위 GPS 검증으로 인증이 우선 승인될 수 있습니다.</p>
            <button class="btn btn-danger" type="submit">입주민 인증 요청</button>
        </form>
    </section>
</div>

<script>
(function () {
    const queryInput = document.getElementById('apartmentQuery');
    const apartmentIdInput = document.getElementById('apartmentId');
    const verificationLatitude = document.getElementById('verificationLatitude');
    const verificationLongitude = document.getElementById('verificationLongitude');
    const suggestionBox = document.getElementById('apartmentSuggestions');
    let lastController = null;

    if ('geolocation' in navigator) {
        navigator.geolocation.getCurrentPosition((position) => {
            if (verificationLatitude && verificationLongitude) {
                verificationLatitude.value = String(position.coords.latitude);
                verificationLongitude.value = String(position.coords.longitude);
            }
        }, () => {
            if (verificationLatitude && verificationLongitude) {
                verificationLatitude.value = '';
                verificationLongitude.value = '';
            }
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
