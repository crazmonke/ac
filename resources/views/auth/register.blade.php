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
        .apt-loading { font-size: 0.85rem; color: #64748b; margin-top: 6px; padding: 2px 0; }
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

        <label>공동주택 선택</label>
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
        <input id="residenceBuildingId" type="hidden" name="residence_building_id" value="{{ old('residence_building_id') }}">
        <input id="latitude" type="hidden" name="latitude" value="{{ old('latitude') }}">
        <input id="longitude" type="hidden" name="longitude" value="{{ old('longitude') }}">
        <div id="aptSelectError" class="err" style="display:none;">공동주택을 선택해 주세요.</div>

        <div class="meta">읍/면/동까지 선택 후 공동주택을 골라주세요. 동/호를 입력하면 세대 단위로 가입됩니다. 위치 권한을 허용하면 GPS 검증으로 인증이 우선 처리됩니다.</div>

        <label>동 (선택)
            <input type="text" name="residence_dong" value="{{ old('residence_dong') }}" placeholder="예: 101">
        </label>

        <label>호 (선택)
            <input type="text" name="residence_ho" value="{{ old('residence_ho') }}" placeholder="예: 1203">
        </label>

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
    const sidoSelect         = document.getElementById('sidoSelect');
    const sigunguSelect      = document.getElementById('sigunguSelect');
    const eupmyeondongSelect = document.getElementById('eupmyeondongSelect');
    const aptComboboxWrap    = document.getElementById('aptComboboxWrap');
    const aptNameInput       = document.getElementById('aptNameInput');
    const aptDropdown        = document.getElementById('aptDropdown');
    const apartmentIdInput   = document.getElementById('apartmentId');
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
        if (!latitudeInput || !longitudeInput) return;
        if (latitudeInput.value && longitudeInput.value) return;
        await new Promise((resolve) => {
            _appGetPosition(
                (pos) => {
                    latitudeInput.value  = String(pos.coords.latitude);
                    longitudeInput.value = String(pos.coords.longitude);
                    resolve();
                },
                () => resolve(),
                { enableHighAccuracy: true, timeout: 6000, maximumAge: 0 }
            );
        });
    }

    _appGetPosition(
        (pos) => {
            latitudeInput.value  = String(pos.coords.latitude);
            longitudeInput.value = String(pos.coords.longitude);
        },
        () => {},
        { enableHighAccuracy: false, timeout: 4000, maximumAge: 300000 }
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
            aptDropdown.innerHTML = '<div class="apt-option" style="color:#94a3b8;cursor:default;">검색 결과 없음</div>';
            aptDropdown.style.display = 'block';
            return;
        }

        aptDropdown.innerHTML = filtered.map((a) => `
            <div class="apt-option" data-id="${a.id}" data-building-id="${a.building_id || ''}" data-name="${escHtml(a.name)}">
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

            if (latitudeInput.value && longitudeInput.value) return;
            event.preventDefault();
            ensureGeoCoordinates().then(function () { registerForm.submit(); });
        });
    }

    // ── init ──────────────────────────────────────────────────────────────────
    loadSido();
})();
</script>
</body>
</html>
