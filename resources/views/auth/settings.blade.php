<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>계정 설정</title>
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
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }
        .shell {
            max-width: 880px;
            margin: 0 auto;
            padding: 0px 16px 40px;
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
        .availability {
            margin-top: 6px;
            font-size: 0.86rem;
            font-weight: 600;
        }
        .availability.ok { color: var(--ok-ink); }
        .availability.fail { color: var(--danger); }
        .meta {
            color: var(--muted);
            font-size: 0.85rem;
        }
        /* 공동주택 찾기 */
        .region-selects { display: flex; flex-direction: column; gap: 6px; margin-top: 6px; }
        .region-selects select { width: 100%; padding: 10px; border-radius: 10px; border: 1px solid #c8d5e7; font: inherit; color: var(--ink); background: #fff; appearance: none; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 12px center; padding-right: 32px; }
        .region-selects select:disabled { background-color: #f3f6fa; color: #94a3b8; cursor: not-allowed; }
        .apt-combobox-wrap { display: none; margin-top: 8px; }
        .apt-combobox-wrap.visible { display: block; }
        .apt-combobox { position: relative; }
        .apt-combobox input { margin-top: 0; }
        .apt-dropdown { position: absolute; left: 0; right: 0; top: calc(100% + 4px); background: #fff; border: 1px solid var(--line); border-radius: 10px; box-shadow: 0 12px 24px rgba(20, 35, 60, 0.08); max-height: 220px; overflow-y: auto; z-index: 20; }
        .apt-option { padding: 10px 12px; cursor: pointer; border-top: 1px solid #eef3f8; font-size: 0.9rem; }
        .apt-option:first-child { border-top: 0; }
        .apt-option:hover, .apt-option.focused { background: #f0f7ff; }
        .apt-option small { display: block; color: #64748b; margin-top: 3px; font-size: 0.8rem; }
        .apt-option mark { background: #fef08a; border-radius: 2px; }
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
                <label>공동주택 찾기</label>
                @if(!$isProfileLocked)
                <div class="region-selects">
                    <select id="sidoSelect">
                        <option value="">도/특별시/광역시 선택</option>
                    </select>
                    <select id="sigunguSelect" disabled>
                        <option value="">시/군/구 선택</option>
                    </select>
                    <select id="eupmyeondongSelect" disabled>
                        <option value="">읍/면/동 선택</option>
                    </select>
                </div>
                <div class="apt-combobox-wrap" id="aptComboboxWrap">
                    <div class="apt-combobox" id="aptCombobox">
                        <input id="aptNameInput" type="text" placeholder="공동주택명 검색 (예: 현대)" autocomplete="off">
                        <div id="aptDropdown" class="apt-dropdown" style="display:none;"></div>
                    </div>
                </div>
                @else
                <input type="text" value="{{ $user->home_apartment_name ?? '미선택' }}" readonly style="background:#f3f6fa; color:#94a3b8;">
                @endif
                <input id="apartmentQuery" type="hidden" name="apartment_query" value="{{ old('apartment_query', $user->home_apartment_name) }}">
                <input id="apartmentId" type="hidden" name="apartment_id" value="{{ old('apartment_id', $selectedApartment?->id ?? $user->preferred_apartment_id) }}">
                <input id="residenceBuildingId" type="hidden" name="residence_building_id" value="{{ old('residence_building_id', $user->preferred_residence_building_id) }}">
                <input id="homeSido" type="hidden" name="home_sido" value="{{ old('home_sido', $user->home_sido) }}">
                <input id="homeSigngu" type="hidden" name="home_sigungu" value="{{ old('home_sigungu', $user->home_sigungu) }}">
                <input id="homeEupmyeondong" type="hidden" name="home_eupmyeondong" value="{{ old('home_eupmyeondong', $user->home_eupmyeondong) }}">
                <input id="roadAddress" type="hidden" name="road_address" value="">
                <input id="profileLatitude" type="hidden" name="latitude" value="">
                <input id="profileLongitude" type="hidden" name="longitude" value="">
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

    @if($isProfileLocked)
    <section class="card">
        <h2>닉네임 변경 (포인트 사용)</h2>
        @php $nicknameCost = (int) $pointPolicy->nickname_change_points; @endphp
        <p>
            프로필이 잠금 상태여도 닉네임은
            @if($nicknameCost > 0)
                포인트 <strong>{{ number_format($nicknameCost) }}P</strong>를 사용해
            @endif
            변경할 수 있습니다.
            보유 포인트: <strong>{{ number_format((int) $user->point_balance) }}P</strong>
            @if($nicknameCost > 0 && (int) $pointPolicy->min_spend_points > 0)
                <span class="meta">(포인트는 {{ number_format((int) $pointPolicy->min_spend_points) }}P 이상 보유 시 사용 가능)</span>
            @endif
        </p>
          <form id="nicknameForm" method="post" action="/settings/nickname" class="form-grid two"
              @if($nicknameCost > 0) onsubmit="return confirm('닉네임을 변경하면 {{ number_format($nicknameCost) }}P가 차감됩니다. 진행할까요?');" @endif>
            @csrf
            @method('put')
            <input type="hidden" name="apartment_id" value="{{ $apartmentId }}">
            <label>
                새 닉네임
                <input id="nicknameInput" name="name" value="{{ old('name') }}" maxlength="120" placeholder="현재: {{ $user->name }}" required autocomplete="off">
                <div id="nicknameAvailability" class="availability" aria-live="polite"></div>
            </label>
            <div class="row" style="align-items:end;">
                <button id="nicknameSubmit" class="btn btn-primary" type="submit" disabled>
                    @if($nicknameCost > 0)
                        {{ number_format($nicknameCost) }}P 사용하고 변경
                    @else
                        닉네임 변경
                    @endif
                </button>
            </div>
        </form>
        @if($errors->has('nickname'))
            <div class="error">{{ $errors->first('nickname') }}</div>
        @endif
    </section>
    @endif

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
        <h2>차단 관리</h2>
        <p>차단한 사용자 목록을 확인하고 차단을 해제할 수 있습니다.</p>
        <a class="btn" href="/blocked-users" style="background:#2e4fb8; color:#fff;">차단 목록 보기</a>
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
    const nicknameForm = document.getElementById('nicknameForm');
    const nicknameInput = document.getElementById('nicknameInput');
    const nicknameAvailability = document.getElementById('nicknameAvailability');
    const nicknameSubmit = document.getElementById('nicknameSubmit');
    let nicknameCheckTimer;
    let nicknameCheckToken = 0;

    function setNicknameAvailability(message, available) {
        if (!nicknameAvailability || !nicknameSubmit) return;
        nicknameAvailability.textContent = message;
        nicknameAvailability.className = 'availability ' + (available ? 'ok' : 'fail');
        nicknameSubmit.disabled = !available;
    }

    async function checkNickname() {
        const name = nicknameInput?.value.trim() || '';
        const token = ++nicknameCheckToken;
        if (!name) {
            setNicknameAvailability('닉네임을 입력해 주세요.', false);
            return;
        }
        setNicknameAvailability('중복 확인 중…', false);
        try {
            const response = await fetch('/settings/nickname-availability?' + new URLSearchParams({ name }), { headers: { Accept: 'application/json' } });
            const result = await response.json();
            if (token === nicknameCheckToken) setNicknameAvailability(result.message, result.available === true);
        } catch (error) {
            if (token === nicknameCheckToken) setNicknameAvailability('닉네임 중복 확인에 실패했습니다. 잠시 후 다시 시도해 주세요.', false);
        }
    }

    if (nicknameInput) {
        nicknameInput.addEventListener('input', () => {
            clearTimeout(nicknameCheckTimer);
            nicknameCheckTimer = setTimeout(checkNickname, 300);
        });
        if (nicknameInput.value.trim()) checkNickname();
    }
    if (nicknameForm) {
        nicknameForm.addEventListener('submit', (event) => {
            if (nicknameSubmit?.disabled) event.preventDefault();
        });
    }

    // ── GPS 공통 헬퍼 ──────────────────────────────────────────────────────────
    function _appGetPosition(success, error, options) {
        if (typeof AppGeoBridge !== 'undefined') {
            var id = Math.random().toString(36).substr(2, 9);
            window['_geoCallback_' + id] = function(lat, lng, acc) {
                delete window['_geoCallback_' + id];
                delete window['_geoError_' + id];
                success({ coords: { latitude: lat, longitude: lng, accuracy: acc }, timestamp: Date.now() });
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

    // ── 입주민 인증 GPS ────────────────────────────────────────────────────────
    const verificationLatitude  = document.getElementById('verificationLatitude');
    const verificationLongitude = document.getElementById('verificationLongitude');
    const residentVerificationForm = document.getElementById('residentVerificationForm');

    _appGetPosition((pos) => {
        if (verificationLatitude)  verificationLatitude.value  = String(pos.coords.latitude);
        if (verificationLongitude) verificationLongitude.value = String(pos.coords.longitude);
    }, () => {}, { enableHighAccuracy: false, timeout: 4000, maximumAge: 300000 });

    if (residentVerificationForm) {
        residentVerificationForm.addEventListener('submit', async (e) => {
            if (verificationLatitude && verificationLongitude && verificationLatitude.value && verificationLongitude.value) return;
            e.preventDefault();
            await new Promise(resolve => _appGetPosition(pos => {
                if (verificationLatitude)  verificationLatitude.value  = String(pos.coords.latitude);
                if (verificationLongitude) verificationLongitude.value = String(pos.coords.longitude);
                resolve();
            }, () => resolve(), { enableHighAccuracy: true, timeout: 6000, maximumAge: 0 }));
            residentVerificationForm.submit();
        });
    }

    // ── 비밀번호 힌트 ─────────────────────────────────────────────────────────
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
        if (pw) pw.addEventListener('input', () => { hint(); matchHint(); });
        if (confirm) confirm.addEventListener('input', matchHint);
    })();

    // ── 공동주택 찾기 (프로필 잠금 시 스킵) ──────────────────────────────────
    const sidoSelect         = document.getElementById('sidoSelect');
    if (!sidoSelect) return; // 프로필 잠금 상태

    const sigunguSelect      = document.getElementById('sigunguSelect');
    const eupmyeondongSelect = document.getElementById('eupmyeondongSelect');
    const aptComboboxWrap    = document.getElementById('aptComboboxWrap');
    const aptNameInput       = document.getElementById('aptNameInput');
    const aptDropdown        = document.getElementById('aptDropdown');
    const apartmentIdInput   = document.getElementById('apartmentId');
    const apartmentQueryInput = document.getElementById('apartmentQuery');
    const residenceBuildingIdInput = document.getElementById('residenceBuildingId');
    const homeSidoInput      = document.getElementById('homeSido');
    const homeSignguInput    = document.getElementById('homeSigngu');
    const homeEupmyeondongInput = document.getElementById('homeEupmyeondong');
    const roadAddressInput   = document.getElementById('roadAddress');
    const profileLatitude    = document.getElementById('profileLatitude');
    const profileLongitude   = document.getElementById('profileLongitude');
    const profileForm        = document.querySelector('form[action="/settings/profile"]');

    // 기존 선택값 (PHP에서 주입)
    const initSido         = homeSidoInput?.value || '';
    const initSigungu      = homeSignguInput?.value || '';
    const initEupmyeondong = homeEupmyeondongInput?.value || '';
    const initAptName      = apartmentQueryInput?.value || '';
    const initBuildingId   = residenceBuildingIdInput?.value || '';

    let allApartments = [];

    function escHtml(str) {
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function highlightMatch(text, query) {
        if (!query) return escHtml(text);
        const idx = text.toLowerCase().indexOf(query.toLowerCase());
        if (idx < 0) return escHtml(text);
        return escHtml(text.slice(0, idx)) + `<mark>${escHtml(text.slice(idx, idx + query.length))}</mark>` + escHtml(text.slice(idx + query.length));
    }

    function resetSelect(sel, placeholder) {
        sel.innerHTML = `<option value="">${placeholder}</option>`;
        sel.disabled = true;
    }

    function clearAptSelection() {
        if (apartmentIdInput) apartmentIdInput.value = '';
        if (residenceBuildingIdInput) residenceBuildingIdInput.value = '';
        if (aptNameInput) aptNameInput.value = '';
        allApartments = [];
    }

    function closeDropdown() {
        aptDropdown.style.display = 'none';
        aptDropdown.innerHTML = '';
    }

    async function fetchRegions(params) {
        const res = await fetch(`/apartments/regions?${new URLSearchParams(params)}`, { headers: { Accept: 'application/json' } });
        if (!res.ok) return [];
        return (await res.json()).data || [];
    }

    async function fetchApartmentsByRegion(sido, sigungu, eupmyeondong) {
        const res = await fetch(`/apartments/by-region?${new URLSearchParams({ sido, sigungu, eupmyeondong })}`, { headers: { Accept: 'application/json' } });
        if (!res.ok) return [];
        return (await res.json()).data || [];
    }

    function renderDropdown(query) {
        const q = query.trim().toLowerCase();
        const filtered = q ? allApartments.filter(a => a.name.toLowerCase().includes(q)) : allApartments;

        if (!filtered.length) {
            if (query.trim()) {
                const roadAddr = [homeSidoInput?.value, homeSignguInput?.value, homeEupmyeondongInput?.value].filter(v => v).join(' ');
                const customLabel = query.trim() + ' 공동주택';
                aptDropdown.innerHTML = `
                    <div class="apt-option" data-id="0" data-building-id="" data-name="${escHtml(customLabel)}" data-road-address="${escHtml(roadAddr)}" data-custom="true">
                        <strong>${escHtml(customLabel)}</strong>
                        <small style="color:#0f7a72; font-weight:500;">직접 입력한 주소 (관리자 검수 후 반영)</small>
                    </div>`;
            } else {
                aptDropdown.innerHTML = '<div class="apt-option" style="color:#94a3b8;cursor:default;">검색 결과 없음</div>';
            }
            aptDropdown.style.display = 'block';
            return;
        }

        aptDropdown.innerHTML = filtered.map(a => `
            <div class="apt-option" data-id="${a.id}" data-building-id="${a.building_id || ''}" data-name="${escHtml(a.name)}" data-road-address="${escHtml(a.road_address || '')}">
                ${highlightMatch(a.name, query.trim())}
                <small>${escHtml(a.road_address || '')}</small>
            </div>`).join('');
        aptDropdown.style.display = 'block';
    }

    // ── 지역 cascading ────────────────────────────────────────────────────────
    async function loadSido() {
        const items = await fetchRegions({ level: 'sido' });
        items.forEach(v => {
            const opt = document.createElement('option');
            opt.value = opt.textContent = v;
            if (v === initSido) opt.selected = true;
            sidoSelect.appendChild(opt);
        });
    }

    async function loadSigungu(sido, preselectValue) {
        resetSelect(sigunguSelect, '시/군/구 선택');
        if (!sido) return;
        const items = await fetchRegions({ level: 'sigungu', sido });
        items.forEach(v => {
            const opt = document.createElement('option');
            opt.value = opt.textContent = v;
            if (v === preselectValue) opt.selected = true;
            sigunguSelect.appendChild(opt);
        });
        sigunguSelect.disabled = false;
    }

    async function loadEupmyeondong(sido, sigungu, preselectValue) {
        resetSelect(eupmyeondongSelect, '읍/면/동 선택');
        if (!sido || !sigungu) return;
        const items = await fetchRegions({ level: 'eupmyeondong', sido, sigungu });
        items.forEach(v => {
            const opt = document.createElement('option');
            opt.value = opt.textContent = v;
            if (v === preselectValue) opt.selected = true;
            eupmyeondongSelect.appendChild(opt);
        });
        eupmyeondongSelect.disabled = false;
    }

    async function loadApartments(sido, sigungu, eupmyeondong, preselectName, preselectBuildingId) {
        aptComboboxWrap.classList.remove('visible');
        clearAptSelection();
        if (!sido || !sigungu || !eupmyeondong) return;

        aptComboboxWrap.classList.add('visible');
        aptNameInput.placeholder = '불러오는 중…';
        aptNameInput.disabled = true;

        allApartments = await fetchApartmentsByRegion(sido, sigungu, eupmyeondong);

        aptNameInput.disabled = false;
        aptNameInput.placeholder = `공동주택명 검색 (${allApartments.length}개)`;

        // 기존 선택값 복원
        if (preselectName) {
            aptNameInput.value = preselectName;
            if (preselectBuildingId) {
                residenceBuildingIdInput.value = preselectBuildingId;
                // apartment_id, apartment_query는 hidden에 이미 세팅돼 있으므로 유지
            }
        }
    }

    // ── 초기값 복원 (기존 선택 있을 때) ──────────────────────────────────────
    async function initPreselect() {
        await loadSido();
        if (!initSido) return;
        await loadSigungu(initSido, initSigungu);
        if (!initSigungu) return;
        await loadEupmyeondong(initSido, initSigungu, initEupmyeondong);
        if (!initEupmyeondong) return;
        await loadApartments(initSido, initSigungu, initEupmyeondong, initAptName, initBuildingId);
    }

    initPreselect();

    // ── 선택 변경 이벤트 ──────────────────────────────────────────────────────
    sidoSelect.addEventListener('change', async () => {
        resetSelect(sigunguSelect, '시/군/구 선택');
        resetSelect(eupmyeondongSelect, '읍/면/동 선택');
        aptComboboxWrap.classList.remove('visible');
        clearAptSelection();
        closeDropdown();
        homeSidoInput.value = sidoSelect.value;
        homeSignguInput.value = '';
        homeEupmyeondongInput.value = '';
        const sido = sidoSelect.value;
        if (!sido) return;
        const items = await fetchRegions({ level: 'sigungu', sido });
        items.forEach(v => {
            const opt = document.createElement('option');
            opt.value = opt.textContent = v;
            sigunguSelect.appendChild(opt);
        });
        sigunguSelect.disabled = false;
    });

    sigunguSelect.addEventListener('change', async () => {
        resetSelect(eupmyeondongSelect, '읍/면/동 선택');
        aptComboboxWrap.classList.remove('visible');
        clearAptSelection();
        closeDropdown();
        homeSignguInput.value = sigunguSelect.value;
        homeEupmyeondongInput.value = '';
        const sido = sidoSelect.value, sigungu = sigunguSelect.value;
        if (!sido || !sigungu) return;
        const items = await fetchRegions({ level: 'eupmyeondong', sido, sigungu });
        items.forEach(v => {
            const opt = document.createElement('option');
            opt.value = opt.textContent = v;
            eupmyeondongSelect.appendChild(opt);
        });
        eupmyeondongSelect.disabled = false;
    });

    eupmyeondongSelect.addEventListener('change', async () => {
        clearAptSelection();
        closeDropdown();
        homeEupmyeondongInput.value = eupmyeondongSelect.value;
        await loadApartments(sidoSelect.value, sigunguSelect.value, eupmyeondongSelect.value, '', '');
        if (aptComboboxWrap.classList.contains('visible')) aptNameInput.focus();
    });

    // ── 공동주택 콤보박스 ────────────────────────────────────────────────────
    aptNameInput.addEventListener('input', () => {
        if (apartmentIdInput) apartmentIdInput.value = '';
        if (residenceBuildingIdInput) residenceBuildingIdInput.value = '';
        renderDropdown(aptNameInput.value);
    });

    aptNameInput.addEventListener('focus', () => {
        if (allApartments.length) renderDropdown(aptNameInput.value);
    });

    aptDropdown.addEventListener('mousedown', (e) => {
        const item = e.target.closest('.apt-option[data-id]');
        if (!item) return;
        e.preventDefault();
        if (apartmentIdInput)        apartmentIdInput.value        = item.dataset.id;
        if (residenceBuildingIdInput) residenceBuildingIdInput.value = item.dataset.buildingId;
        if (roadAddressInput)        roadAddressInput.value         = item.dataset.roadAddress || '';
        aptNameInput.value = item.dataset.name;
        // apartment_query hidden: 직접입력인 경우 이름 저장, DB 선택인 경우 비워도 무방하지만 채워둠
        if (apartmentQueryInput) apartmentQueryInput.value = item.dataset.name;
        closeDropdown();
    });

    document.addEventListener('click', (e) => {
        if (!e.target.closest('#aptCombobox')) closeDropdown();
    });

    // ── 프로필 저장 시 GPS 수집 후 제출 ──────────────────────────────────────
    if (profileForm) {
        profileForm.addEventListener('submit', function (e) {
            if (profileLatitude.value && profileLongitude.value) return; // 이미 있으면 바로 제출
            e.preventDefault();
            _appGetPosition(
                pos => {
                    profileLatitude.value  = String(pos.coords.latitude);
                    profileLongitude.value = String(pos.coords.longitude);
                    profileForm.submit();
                },
                () => { profileForm.submit(); }, // GPS 실패해도 제출
                { enableHighAccuracy: true, timeout: 8000, maximumAge: 0 }
            );
        });
    }
})();
</script>
</body>
</html>
