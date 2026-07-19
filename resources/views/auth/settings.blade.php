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
        .account-actions {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 8px;
            margin: 0 0 12px;
        }
        @media (min-width: 740px) {
            .form-grid.two {
                grid-template-columns: 1fr 1fr;
            }
        }
        #settingsPwHint li { margin: 2px 0; }
        #settingsPwHint li.ok { color: #136a45; }
        #settingsPwHint li.fail { color: #b42318; }
    </style>
</head>
<body>
@include('partials.site-nav', ['apartmentId' => $apartmentId])

<div class="shell">
    <h1 class="page-title">계정 설정</h1>

    <div class="account-actions">
        @if($user->hasRoleForApartment('admin', $apartmentId) || $user->hasRoleForApartment('admin'))
            <a class="btn btn-primary" href="/admin">관리자모드</a>
        @endif
        <form method="post" action="/logout" style="margin:0;">
            @csrf
            <button class="btn btn-danger" type="submit">로그아웃</button>
        </form>
    </div>

    @if(session('status'))
        <div class="flash">{{ session('status') }}</div>
    @endif

    <section class="card">
        <h2>프로필 정보</h2>
        <p>프로필 잠금이 해제된 경우에만 닉네임/이메일/공동주택를 수정할 수 있습니다.</p>
        <form method="post" action="/settings/profile" class="form-grid two">
            @csrf
            @method('put')

            <label>
                닉네임
                <input name="name" value="{{ old('name', $user->name) }}" maxlength="120" required @readonly($isProfileLocked)>
            </label>

            <label>
                이메일(아이디)
                <input type="email" name="email" value="{{ old('email', $user->email) }}" maxlength="190" required @readonly($isProfileLocked)>
            </label>

            <div style="grid-column: 1 / -1;">
                <label>공동주택 선택</label>
                <div class="autocomplete">
                    <input id="apartmentQuery" name="apartment_query" value="{{ old('apartment_query', $selectedApartment?->name ?? $user->home_apartment_name) }}" placeholder="공동주택/오피스텔/빌라/도로명 검색" autocomplete="off" required @readonly($isProfileLocked)>
                    <input id="apartmentId" type="hidden" name="apartment_id" value="{{ old('apartment_id', $selectedApartment?->id ?? $user->preferred_apartment_id) }}">
                    <input id="residenceBuildingId" type="hidden" name="residence_building_id" value="{{ old('residence_building_id', $user->preferred_residence_building_id) }}">
                    <div id="apartmentSuggestions" class="suggestions" style="display:none;"></div>
                </div>
                <div class="meta" style="margin-top:6px;">현재 선택: {{ $user->home_apartment_name ?? '미선택' }}</div>
            </div>

            <label>
                동 (선택)
                <input name="residence_dong" value="{{ old('residence_dong', $user->preferredResidenceUnit?->dong) }}" maxlength="40" @readonly($isProfileLocked)>
            </label>

            <label>
                호 (선택)
                <input name="residence_ho" value="{{ old('residence_ho', $user->preferredResidenceUnit?->ho) }}" maxlength="40" @readonly($isProfileLocked)>
            </label>

            <div class="row" style="grid-column: 1 / -1;">
                <button class="btn btn-primary" type="submit" @disabled($isProfileLocked)>프로필 저장</button>
                <span class="meta">
                    @if($isProfileLocked)
                        현재 프로필 잠금 상태입니다. 관리자 회원관리에서 해제 후 저장할 수 있습니다.
                    @else
                        변경 즉시 상단 네비 정보에 반영됩니다.
                    @endif
                </span>
            </div>
        </form>
        @if($errors->has('name') || $errors->has('email'))
            <div class="error">{{ $errors->first('name') ?: $errors->first('email') }}</div>
        @endif
    </section>

    <section class="card">
        <h2>비밀번호 변경</h2>
        <p>현재 비밀번호를 확인한 뒤 새로운 비밀번호로 변경합니다.<br>
        <span style="font-size:0.85rem; color:#62728a;">비밀번호는 영문자·숫자·특수문자(예: !@#$)를 각각 1개 이상 포함하여 8자 이상으로 설정해 주세요.</span></p>
        <form method="post" action="/settings/password" class="form-grid two">
            @csrf
            @method('put')
            <input type="hidden" name="apartment_id" value="{{ $apartmentId }}">

            <label style="grid-column: 1 / -1;">
                현재 비밀번호
                <input type="password" name="current_password" required autocomplete="current-password">
            </label>

            <label style="grid-column: 1 / -1;">
                새 비밀번호
                <input id="settingsPwInput" type="password" name="password" minlength="8" required autocomplete="new-password">
                <ul id="settingsPwHint" style="margin:6px 0 0; padding:0; list-style:none; font-size:0.82rem; color:#64748b;">
                    <li id="sph-len">8자 이상</li>
                    <li id="sph-letter">영문자 포함</li>
                    <li id="sph-number">숫자 포함</li>
                    <li id="sph-symbol">특수문자 포함</li>
                </ul>
            </label>

            <label style="grid-column: 1 / -1;">
                새 비밀번호 확인
                <input id="settingsPwConfirm" type="password" name="password_confirmation" minlength="8" required autocomplete="new-password">
                <div id="settingsPwMatch" style="display:none; margin-top:5px; font-size:0.82rem; font-weight:600;"></div>
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
        <p>선택된 공동주택 기준으로 인증이 진행됩니다. 검수 상태는 아래에서 확인할 수 있습니다.</p>
        <div class="row" style="margin-bottom:10px;">
            <span class="badge">선택 공동주택: {{ $user->home_apartment_name ?? '미선택' }}</span>
            <span class="badge">입주민 권한: {{ $hasResidentRole ? '승인됨' : '미승인' }}</span>
        </div>
        @if($latestMatchReview)
            <p class="meta">최근 공동주택 매칭 검수: {{ $latestMatchReview->status }} · {{ $latestMatchReview->raw_apartment_name }}</p>
        @endif
        @if($latestVerificationRequest)
            <p class="meta">최근 인증 요청: {{ $latestVerificationRequest->status }} · {{ $latestVerificationRequest->apartment->name ?? '미지정' }}</p>
        @endif
        @if($latestResidenceVerification)
            <p class="meta">최근 공동주택 인증 상태: {{ $latestResidenceVerification->verification_status }} · {{ $latestResidenceVerification->complex?->displayName() ?? '미지정' }}</p>
        @endif
        <form id="residentVerificationForm" method="post" action="/settings/resident-verification-request">
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

    <section class="card">
        <h2>포인트</h2>
        <p>게시글·댓글 작성 시 포인트가 적립됩니다.</p>
        <div style="display:flex; align-items:center; gap:16px; flex-wrap:wrap;">
            <div style="background:#eef5ff; border:1px solid #c7d9fb; border-radius:12px; padding:14px 18px;">
                <div class="meta" style="font-size:0.78rem;">현재 포인트 잔액</div>
                <div style="font-size:1.5rem; font-weight:800; color:#1d3fa6;">{{ number_format($user->point_balance) }} P</div>
            </div>
            <a class="btn" href="/points" style="background:#2e4fb8; color:#fff;">포인트 이력 보기</a>
        </div>
    </section>

    <section class="card">
        <h2>계정 탈퇴</h2>
        <p>탈퇴 요청 시 계정 접근이 비활성화되며 즉시 로그아웃됩니다.</p>
        <form method="post" action="/settings/withdraw-request" onsubmit="return confirm('탈퇴 요청을 진행할까요?');">
            @csrf
            <input type="hidden" name="apartment_id" value="{{ $apartmentId }}">
            <button class="btn btn-danger" type="submit" @disabled((bool) $user->withdrawn_at)>
                {{ $user->withdrawn_at ? '이미 탈퇴 처리됨' : '탈퇴 요청' }}
            </button>
        </form>
    </section>
</div>

<script>
(function () {
    const queryInput = document.getElementById('apartmentQuery');
    const apartmentIdInput = document.getElementById('apartmentId');
    const residenceBuildingId = document.getElementById('residenceBuildingId');
    const verificationLatitude = document.getElementById('verificationLatitude');
    const verificationLongitude = document.getElementById('verificationLongitude');
    const residentVerificationForm = document.getElementById('residentVerificationForm');
    const suggestionBox = document.getElementById('apartmentSuggestions');
    let lastController = null;

    function _appGetPosition(success, error, options) {
        if (typeof AppGeoBridge !== 'undefined') {
            var id = Math.random().toString(36).substr(2, 9);
            window['_geoCallback_' + id] = function(lat, lng, acc) {
                delete window['_geoCallback_' + id];
                delete window['_geoError_' + id];
                success({ coords: { latitude: lat, longitude: lng, accuracy: acc, altitude: null, altitudeAccuracy: null, heading: null, speed: null }, timestamp: Date.now() });
            };
            window['_geoError_' + id] = function(code, msg) {
                delete window['_geoCallback_' + id];
                delete window['_geoError_' + id];
                if (error) error({ code: code, message: msg });
            };
            AppGeoBridge.postMessage('get:' + id);
        } else if ('geolocation' in navigator) {
            navigator.geolocation.getCurrentPosition(success, error, options);
        } else if (error) {
            error({ code: 2, message: 'Not supported' });
        }
    }

    async function ensureVerificationCoordinates() {
        if (!verificationLatitude || !verificationLongitude) {
            return;
        }

        if (verificationLatitude.value && verificationLongitude.value) {
            return;
        }

        await new Promise((resolve) => {
            _appGetPosition((position) => {
                verificationLatitude.value = String(position.coords.latitude);
                verificationLongitude.value = String(position.coords.longitude);
                resolve();
            }, () => {
                resolve();
            }, {
                enableHighAccuracy: true,
                timeout: 6000,
                maximumAge: 0,
            });
        });
    }

    _appGetPosition((position) => {
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

    if (!queryInput || !apartmentIdInput || !suggestionBox || queryInput.readOnly) {
        return;
    }

    function closeSuggestions() {
        suggestionBox.style.display = 'none';
        suggestionBox.innerHTML = '';
    }

    queryInput.addEventListener('input', async () => {
        const keyword = queryInput.value.trim();
        apartmentIdInput.value = '';
        if (residenceBuildingId) {
            residenceBuildingId.value = '';
        }

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
                <div class="suggestion" data-id="${row.id || ''}" data-building-id="${row.building_id || ''}" data-name="${row.name}">
                    ${row.name}
                    <small>${row.housing_type || 'residence'} · ${row.region} · ${row.road_address}</small>
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
        if (residenceBuildingId) {
            residenceBuildingId.value = item.dataset.buildingId;
        }
        queryInput.value = item.dataset.name;
        closeSuggestions();
    });

    document.addEventListener('click', (event) => {
        if (!event.target.closest('.autocomplete')) {
            closeSuggestions();
        }
    });

    // password hint for settings
    (function () {
        const pw      = document.getElementById('settingsPwInput');
        const confirm = document.getElementById('settingsPwConfirm');
        const match   = document.getElementById('settingsPwMatch');
        const els = {
            len:    document.getElementById('sph-len'),
            letter: document.getElementById('sph-letter'),
            number: document.getElementById('sph-number'),
            symbol: document.getElementById('sph-symbol'),
        };

        function hint() {
            if (!pw) return;
            const v = pw.value;
            [[els.len, v.length >= 8], [els.letter, /[a-zA-Z]/.test(v)], [els.number, /\d/.test(v)], [els.symbol, /[\W_]/.test(v)]].forEach(([el, ok]) => {
                if (!el) return;
                el.className = v.length === 0 ? '' : (ok ? 'ok' : 'fail');
            });
        }
        function matchHint() {
            if (!pw || !confirm || !match) return;
            if (!confirm.value) { match.style.display = 'none'; return; }
            const same = pw.value === confirm.value;
            match.textContent = same ? '비밀번호가 일치합니다.' : '비밀번호가 일치하지 않습니다.';
            match.style.cssText = 'display:block; margin-top:5px; font-size:0.82rem; font-weight:600; color:' + (same ? '#136a45' : '#b42318') + ';';
        }
        if (pw) { pw.addEventListener('input', () => { hint(); matchHint(); }); }
        if (confirm) { confirm.addEventListener('input', matchHint); }
    })();
        residentVerificationForm.addEventListener('submit', async (event) => {
            if (verificationLatitude.value && verificationLongitude.value) {
                return;
            }

            event.preventDefault();
            await ensureVerificationCoordinates();
            residentVerificationForm.submit();
        });
    }
})();
</script>
</body>
</html>
