<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>회원가입</title>
    <style>
        body { margin: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; background: #eef4fa; color: #1b2d45; }
        .wrap { min-height: 100vh; display: flex; flex-direction: column; align-items: center; justify-content: flex-start; padding: 0px 20px 28px; gap: 14px; }
        .card { width: min(430px, 100%); background: #fff; border: 1px solid #d6e1ef; border-radius: 14px; padding: 22px; }
        h1 { margin: 0 0 14px; font-size: 1.45rem; }
        label { display: block; margin-top: 10px; font-size: 0.9rem; }
        input, select { width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #c8d5e7; margin-top: 6px; font: inherit; color: inherit; background: #fff; box-sizing: border-box; }
        select { appearance: none; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 12px center; padding-right: 32px; }
        select:disabled { background-color: #f3f6fa; color: #94a3b8; cursor: not-allowed; }
        .btn { margin-top: 16px; width: 100%; border: 0; background: #0b7a75; color: #fff; padding: 11px; border-radius: 8px; cursor: pointer; font-weight: 700; font: inherit; font-weight: 700; }
        .err { margin-top: 10px; color: #b42318; font-size: 0.9rem; }
        .meta { margin-top: 14px; font-size: 0.86rem; color: #53657a; }
        a { color: #0f6f67; text-decoration: none; font-weight: 700; }

        /* region cascading selects */
        .region-selects { display: flex; flex-direction: column; gap: 6px; margin-top: 6px; }

        /* apartment name combobox */
        .apt-combobox { position: relative; margin-top: 6px; }
        .apt-combobox input { margin-top: 0; }
        .apt-dropdown {
            position: absolute; left: 0; right: 0; top: calc(100% + 4px);
            background: #fff; border: 1px solid #d6e1ef; border-radius: 10px;
            box-shadow: 0 12px 24px rgba(20, 35, 60, 0.08);
            max-height: 220px; overflow-y: auto; z-index: 10;
        }
        .apt-option {
            padding: 10px 12px; cursor: pointer; border-top: 1px solid #eef3f8;
            font-size: 0.9rem;
        }
        .apt-option:first-child { border-top: 0; }
        .apt-option:hover, .apt-option.focused { background: #f0f7ff; }
        .apt-option small { display: block; color: #64748b; margin-top: 3px; font-size: 0.8rem; }
        .apt-option mark { background: #fef08a; border-radius: 2px; }
        .apt-combobox-wrap { display: none; margin-top: 8px; }
        .apt-combobox-wrap.visible { display: block; }
        .pw-hint { margin-top: 6px; font-size: 0.82rem; color: #64748b; }
        .pw-hint li { margin: 2px 0; }
        .pw-hint li.ok { color: #136a45; }
        .pw-hint li.fail { color: #b42318; }
        .email-status { margin-top: 5px; font-size: 0.82rem; font-weight: 600; }
        .email-status.ok { color: #136a45; }
        .email-status.err { color: #b42318; }
    </style>
</head>
<body>
<div class="wrap">
    @include('partials.site-nav', ['apartmentId' => request()->query('apartment_id', 1)])
    <form class="card" method="post" action="{{ route('register.attempt') }}">
        @csrf
        <input type="hidden" name="redirect" value="{{ old('redirect', $redirect ?? '/') }}">
        <h1>회원가입</h1>

        <label>닉네임
            <input type="text" name="name" value="{{ old('name') }}" required>
        </label>

        <label>이메일
            <input id="emailInput" type="email" name="email" value="{{ old('email') }}" required autocomplete="email">
            <div id="emailStatus" class="email-status" style="display:none;"></div>
        </label>

        <label>공동주택 찾기</label>
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

        <input id="apartmentId" type="hidden" name="apartment_id" value="{{ old('apartment_id', request()->query('apartment_id')) }}">
        <input id="apartmentQuery" type="hidden" name="apartment_query" value="{{ old('apartment_query') }}">
        <input id="homeSido" type="hidden" name="home_sido" value="{{ old('home_sido') }}">
        <input id="homeSigngu" type="hidden" name="home_sigungu" value="{{ old('home_sigungu') }}">
        <input id="homeEupmyeondong" type="hidden" name="home_eupmyeondong" value="{{ old('home_eupmyeondong') }}">
        <input id="roadAddress" type="hidden" name="road_address" value="{{ old('road_address') }}">
        <input id="residenceBuildingId" type="hidden" name="residence_building_id" value="{{ old('residence_building_id') }}">
        <input id="latitude" type="hidden" name="latitude" value="{{ old('latitude') }}">
        <input id="longitude" type="hidden" name="longitude" value="{{ old('longitude') }}">
        <div id="aptSelectError" class="err" style="display:none;">공동주택을 선택해 주세요.</div>

        <div class="meta">읍/면/동까지 선택 후 공동주택을 골라주세요. 위치 권한을 허용하면 GPS 검증으로 인증이 우선 처리됩니다.</div>

        <label>비밀번호
            <input id="pwInput" type="password" name="password" required autocomplete="new-password">
            <ul id="pwHint" class="pw-hint">
                <li id="ph-len">8자 이상</li>
                <li id="ph-letter">영문자 포함</li>
                <li id="ph-number">숫자 포함</li>
                <li id="ph-symbol">특수문자 포함 (예: !@#$%^&*)</li>
            </ul>
        </label>

        <label>비밀번호 확인
            <input id="pwConfirmInput" type="password" name="password_confirmation" required autocomplete="new-password">
            <div id="pwMatchHint" class="email-status" style="display:none;"></div>
        </label>

        <fieldset style="border:0; padding:0; margin:18px 0 0; display:grid; gap:10px;">
            <legend style="font-weight:700; margin-bottom:4px;">필수 동의</legend>
            <label style="display:flex; gap:8px; align-items:flex-start;">
                <input type="checkbox" name="is_adult" value="1" @checked(old('is_adult')) required style="width:auto; margin-top:4px;">
                <span>만 18세 이상입니다.</span>
            </label>
            <label style="display:flex; gap:8px; align-items:flex-start;">
                <input type="checkbox" name="terms_agreed" value="1" @checked(old('terms_agreed')) required style="width:auto; margin-top:4px;">
                <span><a href="/terms" target="_blank" rel="noopener">이용약관</a>에 동의합니다.</span>
            </label>
            <label style="display:flex; gap:8px; align-items:flex-start;">
                <input type="checkbox" name="privacy_agreed" value="1" @checked(old('privacy_agreed')) required style="width:auto; margin-top:4px;">
                <span><a href="/privacy" target="_blank" rel="noopener">개인정보처리방침</a>에 동의합니다.</span>
            </label>
        </fieldset>

        @if ($errors->any())
            <div class="err">{{ $errors->first() }}</div>
        @endif

        <button class="btn" type="submit">나의 공동주택 찾기 완료</button>

        <div class="meta">
            이미 계정이 있으신가요? <a href="/login">로그인</a>
        </div>
    </form>
</div>

<script>
(function () {
    const sidoSelect         = document.getElementById('sidoSelect');
    const sigunguSelect      = document.getElementById('sigunguSelect');
    const eupmyeondongSelect = document.getElementById('eupmyeondongSelect');
    const aptComboboxWrap    = document.getElementById('aptComboboxWrap');
    const aptNameInput       = document.getElementById('aptNameInput');
    const aptDropdown        = document.getElementById('aptDropdown');
    const apartmentIdInput   = document.getElementById('apartmentId');
    const apartmentQueryInput = document.getElementById('apartmentQuery');
    const residenceBuildingIdInput = document.getElementById('residenceBuildingId');
    const latitudeInput      = document.getElementById('latitude');
    const longitudeInput     = document.getElementById('longitude');
    const aptSelectError     = document.getElementById('aptSelectError');
    const registerForm       = document.querySelector('form.card');

    let allApartments = [];   // full list for current eupmyeondong
    let selectedAptName = '';

    // ── geolocation ──────────────────────────────────────────────────────────

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

    async function ensureGeoCoordinates() {
        if (!latitudeInput || !longitudeInput) {
            console.log('[GPS] Input elements not found');
            return;
        }
        
        // 이미 좌표가 있으면 스킵
        if (latitudeInput.value && longitudeInput.value) {
            console.log('[GPS] Coordinates already exist:', latitudeInput.value, longitudeInput.value);
            return;
        }
        
        return new Promise((resolve) => {
            console.log('[GPS] Starting GPS collection...');
            try {
                _appGetPosition(
                    (pos) => {
                        console.log('[GPS] Success! Lat:', pos.coords.latitude, 'Lon:', pos.coords.longitude);
                        latitudeInput.value  = String(pos.coords.latitude);
                        longitudeInput.value = String(pos.coords.longitude);
                        resolve();
                    },
                    (error) => {
                        console.log('[GPS] Error:', error.message);
                        resolve(); // GPS 실패해도 계속 진행
                    },
                    { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
                );
            } catch (error) {
                console.log('[GPS] Exception:', error.message);
                resolve();
            }
        });
    }

    // 페이지 로드 시 GPS 미리 수집 시도
    console.log('[Init] Page loaded, attempting to collect GPS...');
    _appGetPosition(
        (pos) => {
            console.log('[Init] Initial GPS success! Lat:', pos.coords.latitude);
            latitudeInput.value  = String(pos.coords.latitude);
            longitudeInput.value = String(pos.coords.longitude);
        },
        (error) => {
            console.log('[Init] Initial GPS failed:', error.message);
        },
        { enableHighAccuracy: false, timeout: 5000, maximumAge: 0 }
    );

    // ── helpers ───────────────────────────────────────────────────────────────

    function resetSelect(sel, placeholder) {
        sel.innerHTML = `<option value="">${placeholder}</option>`;
        sel.disabled = true;
    }

    function clearAptSelection() {
        apartmentIdInput.value        = '';
        residenceBuildingIdInput.value = '';
        selectedAptName = '';
        aptNameInput.value = '';
        allApartments = [];
    }

    function closeDropdown() {
        aptDropdown.style.display = 'none';
        aptDropdown.innerHTML = '';
    }

    function escHtml(str) {
        return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function highlightMatch(text, query) {
        if (!query) return escHtml(text);
        const idx = text.toLowerCase().indexOf(query.toLowerCase());
        if (idx < 0) return escHtml(text);
        return escHtml(text.slice(0, idx)) +
               `<mark>${escHtml(text.slice(idx, idx + query.length))}</mark>` +
               escHtml(text.slice(idx + query.length));
    }

    // ── cascading region API calls ────────────────────────────────────────────

    async function fetchRegions(params) {
        const qs  = new URLSearchParams(params).toString();
        const res = await fetch(`/apartments/regions?${qs}`, { headers: { Accept: 'application/json' } });
        if (!res.ok) return [];
        const json = await res.json();
        return json.data || [];
    }

    async function fetchApartmentsByRegion(sido, sigungu, eupmyeondong) {
        const qs  = new URLSearchParams({ sido, sigungu, eupmyeondong }).toString();
        const res = await fetch(`/apartments/by-region?${qs}`, { headers: { Accept: 'application/json' } });
        if (!res.ok) return [];
        const json = await res.json();
        return json.data || [];
    }

    // ── populate selects ──────────────────────────────────────────────────────

    async function loadSido() {
        const items = await fetchRegions({ level: 'sido' });
        items.forEach((v) => {
            const opt = document.createElement('option');
            opt.value = opt.textContent = v;
            sidoSelect.appendChild(opt);
        });
    }

    sidoSelect.addEventListener('change', async () => {
        resetSelect(sigunguSelect, '시/군/구 선택');
        resetSelect(eupmyeondongSelect, '읍/면/동 선택');
        aptComboboxWrap.classList.remove('visible');
        clearAptSelection();
        closeDropdown();

        const sido = sidoSelect.value;
        if (!sido) return;

        const items = await fetchRegions({ level: 'sigungu', sido });
        items.forEach((v) => {
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

        const sido    = sidoSelect.value;
        const sigungu = sigunguSelect.value;
        if (!sido || !sigungu) return;

        const items = await fetchRegions({ level: 'eupmyeondong', sido, sigungu });
        items.forEach((v) => {
            const opt = document.createElement('option');
            opt.value = opt.textContent = v;
            eupmyeondongSelect.appendChild(opt);
        });
        eupmyeondongSelect.disabled = false;
    });

    eupmyeondongSelect.addEventListener('change', async () => {
        clearAptSelection();
        closeDropdown();

        const sido         = sidoSelect.value;
        const sigungu      = sigunguSelect.value;
        const eupmyeondong = eupmyeondongSelect.value;

        if (!sido || !sigungu || !eupmyeondong) {
            aptComboboxWrap.classList.remove('visible');
            return;
        }

        // 선택된 지역 정보를 hidden input에 저장
        document.getElementById('homeSido').value = sido;
        document.getElementById('homeSigngu').value = sigungu;
        document.getElementById('homeEupmyeondong').value = eupmyeondong;

        aptComboboxWrap.classList.add('visible');
        aptNameInput.placeholder = '불러오는 중…';
        aptNameInput.disabled = true;

        allApartments = await fetchApartmentsByRegion(sido, sigungu, eupmyeondong);

        aptNameInput.disabled = false;
        aptNameInput.placeholder = `공동주택명 검색 (${allApartments.length}개)`;
        aptNameInput.focus();
    });

    // ── apartment name combobox ───────────────────────────────────────────────

    function renderDropdown(query) {
        const q = query.trim().toLowerCase();
        const filtered = q
            ? allApartments.filter((a) => a.name.toLowerCase().includes(q))
            : allApartments;

        if (!filtered.length) {
            // 검색 결과가 없을 때, 사용자 입력값이 있으면 "공동주택" 옵션 표시
            if (query.trim()) {
                // 선택된 지역 정보를 기반으로 도로명주소 생성
                const homeSidoInput = document.getElementById('homeSido');
                const homeSignguInput = document.getElementById('homeSigngu');
                const homeEupmyeondongInput = document.getElementById('homeEupmyeondong');
                const roadAddr = [homeSidoInput.value, homeSignguInput.value, homeEupmyeondongInput.value]
                    .filter(v => v && v !== '')
                    .join(' ');
                const customLabel = query.trim() + ' 공동주택';
                aptDropdown.innerHTML = `
                    <div class="apt-option" data-id="0" data-building-id="" data-name="${escHtml(customLabel)}" data-road-address="${escHtml(roadAddr)}" data-custom="true">
                        <strong>${escHtml(customLabel)}</strong>
                        <small style="color:#0f7a72; font-weight: 500;">직접 입력한 주소</small>
                    </div>
                `;
            } else {
                aptDropdown.innerHTML = '<div class="apt-option" style="color:#94a3b8;cursor:default;">검색 결과 없음</div>';
            }
            aptDropdown.style.display = 'block';
            return;
        }

        aptDropdown.innerHTML = filtered.map((a) => `
            <div class="apt-option" data-id="${a.id}" data-building-id="${a.building_id || ''}" data-name="${escHtml(a.name)}" data-road-address="${escHtml(a.road_address || '')}">
                ${highlightMatch(a.name, query.trim())}
                <small>${escHtml(a.road_address || '')}</small>
            </div>
        `).join('');
        aptDropdown.style.display = 'block';
    }

    aptNameInput.addEventListener('input', () => {
        apartmentIdInput.value = '';
        residenceBuildingIdInput.value = '';
        selectedAptName = '';
        renderDropdown(aptNameInput.value);
    });

    aptNameInput.addEventListener('focus', () => {
        if (allApartments.length) renderDropdown(aptNameInput.value);
    });

    aptDropdown.addEventListener('mousedown', (e) => {
        const item = e.target.closest('.apt-option[data-id]');
        if (!item) return;
        e.preventDefault();
        apartmentIdInput.value        = item.dataset.id;
        residenceBuildingIdInput.value = item.dataset.buildingId;
        selectedAptName = item.dataset.name;
        aptNameInput.value = item.dataset.name;
        
        // road_address 저장
        document.getElementById('roadAddress').value = item.dataset.roadAddress || '';
        
        // 커스텀 공동주택(apartment_id = 0)인 경우 apartment_query 설정
        if (item.dataset.id === '0') {
            apartmentQueryInput.value = item.dataset.name;
        } else {
            apartmentQueryInput.value = '';
        }
        
        aptSelectError.style.display = 'none';
        closeDropdown();
    });

    document.addEventListener('click', (e) => {
        if (!e.target.closest('#aptCombobox')) closeDropdown();
    });

    // ── form submit ───────────────────────────────────────────────────────────

    if (registerForm) {
        registerForm.addEventListener('submit', function (event) {
            if (!apartmentIdInput.value) {
                event.preventDefault();
                aptSelectError.style.display = 'block';
                aptComboboxWrap.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return;
            }
            aptSelectError.style.display = 'none';

            // GPS 좌표가 이미 있으면 form 제출
            if (latitudeInput.value && longitudeInput.value) {
                return; // form 자동 제출
            }
            
            // GPS 좌표가 없으면 먼저 수집 시도
            event.preventDefault();
            console.log('[Register] Starting GPS collection...');
            ensureGeoCoordinates().then(function () {
                console.log('[Register] GPS collection completed');
                console.log('[Register] Latitude:', latitudeInput.value);
                console.log('[Register] Longitude:', longitudeInput.value);
                console.log('[Register] Submitting form...');
                registerForm.submit();
            });
        });
    }

    // ── email duplicate check ──────────────────────────────────────────────────

    const emailInput  = document.getElementById('emailInput');
    const emailStatus = document.getElementById('emailStatus');
    let emailCheckTimer = null;

    if (emailInput && emailStatus) {
        emailInput.addEventListener('input', () => {
            emailStatus.style.display = 'none';
            clearTimeout(emailCheckTimer);
            emailCheckTimer = setTimeout(() => checkEmail(), 600);
        });
        emailInput.addEventListener('blur', () => {
            clearTimeout(emailCheckTimer);
            checkEmail();
        });
    }

    async function checkEmail() {
        const email = emailInput ? emailInput.value.trim() : '';
        if (!email || !email.includes('@')) { emailStatus.style.display = 'none'; return; }
        try {
            const res = await fetch('/auth/check-email?email=' + encodeURIComponent(email), { headers: { Accept: 'application/json' } });
            const json = await res.json();
            emailStatus.textContent = json.message;
            emailStatus.className = 'email-status ' + (json.available ? 'ok' : 'err');
            emailStatus.style.display = 'block';
        } catch (_) { emailStatus.style.display = 'none'; }
    }

    // ── password strength indicator ────────────────────────────────────────────

    const pwInput        = document.getElementById('pwInput');
    const pwConfirmInput = document.getElementById('pwConfirmInput');
    const pwMatchHint    = document.getElementById('pwMatchHint');
    const phLen          = document.getElementById('ph-len');
    const phLetter       = document.getElementById('ph-letter');
    const phNumber       = document.getElementById('ph-number');
    const phSymbol       = document.getElementById('ph-symbol');

    function updatePwHint() {
        if (!pwInput) return;
        const v = pwInput.value;
        const checks = [
            [phLen,    v.length >= 8],
            [phLetter, /[a-zA-Z]/.test(v)],
            [phNumber, /\d/.test(v)],
            [phSymbol, /[\W_]/.test(v)],
        ];
        checks.forEach(([el, ok]) => {
            if (!el) return;
            el.className = v.length === 0 ? '' : (ok ? 'ok' : 'fail');
        });
    }

    function updatePwMatch() {
        if (!pwInput || !pwConfirmInput || !pwMatchHint) return;
        const v1 = pwInput.value, v2 = pwConfirmInput.value;
        if (!v2) { pwMatchHint.style.display = 'none'; return; }
        if (v1 === v2) {
            pwMatchHint.textContent = '비밀번호가 일치합니다.';
            pwMatchHint.className = 'email-status ok';
        } else {
            pwMatchHint.textContent = '비밀번호가 일치하지 않습니다.';
            pwMatchHint.className = 'email-status err';
        }
        pwMatchHint.style.display = 'block';
    }

    if (pwInput) pwInput.addEventListener('input', () => { updatePwHint(); updatePwMatch(); });
    if (pwConfirmInput) pwConfirmInput.addEventListener('input', updatePwMatch);
    updatePwHint();

    loadSido();
})();
</script>
</body>
</html>
