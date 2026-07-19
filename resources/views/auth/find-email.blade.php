<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>이메일 찾기</title>
    <style>
        body { margin: 0; font-family: 'SUIT', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #eef4fa; color: #1b2d45; }
        .wrap { min-height: 100vh; display: flex; flex-direction: column; align-items: center; justify-content: flex-start; padding: 16px 20px 28px; gap: 14px; }
        .card { width: min(430px, 100%); background: #fff; border: 1px solid #d6e1ef; border-radius: 14px; padding: 22px; }
        h1 { margin: 0 0 6px; font-size: 1.45rem; }
        .desc { margin: 0 0 14px; font-size: 0.88rem; color: #53657a; line-height: 1.5; }
        label { display: block; margin-top: 10px; font-size: 0.9rem; }
        input, select { width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #c8d5e7; margin-top: 6px; font: inherit; color: inherit; background: #fff; box-sizing: border-box; }
        select { appearance: none; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 12px center; padding-right: 32px; }
        select:disabled { background-color: #f3f6fa; color: #94a3b8; cursor: not-allowed; }
        .btn { margin-top: 16px; width: 100%; border: 0; background: #0b7a75; color: #fff; padding: 11px; border-radius: 8px; cursor: pointer; font: inherit; font-weight: 700; }
        .err { margin-top: 10px; color: #b42318; font-size: 0.9rem; }
        .meta { margin-top: 14px; font-size: 0.86rem; color: #53657a; }
        a { color: #0f6f67; text-decoration: none; font-weight: 700; }
        .region-selects { display: flex; flex-direction: column; gap: 6px; margin-top: 6px; }
        .apt-combobox { position: relative; margin-top: 6px; }
        .apt-combobox input { margin-top: 0; }
        .apt-dropdown {
            position: absolute; left: 0; right: 0; top: calc(100% + 4px);
            background: #fff; border: 1px solid #d6e1ef; border-radius: 10px;
            box-shadow: 0 12px 24px rgba(20,35,60,0.08);
            max-height: 220px; overflow-y: auto; z-index: 10;
        }
        .apt-option { padding: 10px 12px; cursor: pointer; border-top: 1px solid #eef3f8; font-size: 0.9rem; }
        .apt-option:first-child { border-top: 0; }
        .apt-option:hover, .apt-option.focused { background: #f0f7ff; }
        .apt-option small { display: block; color: #64748b; margin-top: 3px; font-size: 0.8rem; }
        .apt-option mark { background: #fef08a; border-radius: 2px; }
        .apt-combobox-wrap { display: none; margin-top: 8px; }
        .apt-combobox-wrap.visible { display: block; }
        .result-box { margin-top: 14px; padding: 14px; background: #e9f7ef; border: 1px solid #b6e2c8; border-radius: 10px; }
        .result-box .title { font-weight: 700; font-size: 0.95rem; color: #136a45; margin: 0 0 8px; }
        .result-box .email-item { font-size: 0.95rem; font-family: monospace; margin: 4px 0; }
        .result-box.none { background: #fef2f2; border-color: #fca5a5; color: #991b1b; }
    </style>
</head>
<body>
<div class="wrap">
    @include('partials.site-nav', ['apartmentId' => 1])

    <form class="card" method="post" action="{{ route('find-email.attempt') }}">
        @csrf
        <h1>이메일 찾기</h1>
        <p class="desc">가입 시 입력한 닉네임과 공동주택을 선택하면 가입된 이메일을 알려드립니다.</p>

        @if(session('find_result'))
            @php $emails = session('emails', []); @endphp
            @if(count($emails) > 0)
                <div class="result-box">
                    <p class="title">{{ session('searched_name') }}님의 가입 이메일</p>
                    @foreach($emails as $em)
                        <div class="email-item">{{ $em }}</div>
                    @endforeach
                </div>
            @else
                <div class="result-box none">입력하신 정보와 일치하는 계정을 찾을 수 없습니다.</div>
            @endif
        @endif

        <label>닉네임
            <input type="text" name="name" value="{{ old('name') }}" required placeholder="가입 시 입력한 닉네임">
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
        <input id="apartmentId" type="hidden" name="apartment_id" value="">
        <div id="aptSelectError" class="err" style="display:none;">공동주택을 선택해 주세요.</div>
        <div class="meta">가입 시 선택한 공동주택과 동일하게 선택해 주세요.</div>

        @if ($errors->any())
            <div class="err">{{ $errors->first() }}</div>
        @endif

        <button class="btn" type="submit">이메일 찾기</button>

        <div class="meta">
            이메일을 알고 계신가요? <a href="/login">로그인</a> &middot;
            <a href="/forgot-password">비밀번호 찾기</a>
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
    const aptSelectError     = document.getElementById('aptSelectError');
    const form               = document.querySelector('form.card');

    let allApartments = [];

    function resetSelect(sel, placeholder) {
        sel.innerHTML = `<option value="">${placeholder}</option>`;
        sel.disabled = true;
    }

    function clearAptSelection() {
        apartmentIdInput.value = '';
        aptNameInput.value = '';
        allApartments = [];
    }

    function closeDropdown() {
        aptDropdown.style.display = 'none';
        aptDropdown.innerHTML = '';
    }

    function escHtml(str) {
        return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    function highlightMatch(text, query) {
        if (!query) return escHtml(text);
        const idx = text.toLowerCase().indexOf(query.toLowerCase());
        if (idx < 0) return escHtml(text);
        return escHtml(text.slice(0, idx)) + `<mark>${escHtml(text.slice(idx, idx + query.length))}</mark>` + escHtml(text.slice(idx + query.length));
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

    async function loadSido() {
        const items = await fetchRegions({ level: 'sido' });
        items.forEach((v) => { const o = document.createElement('option'); o.value = o.textContent = v; sidoSelect.appendChild(o); });
    }

    sidoSelect.addEventListener('change', async () => {
        resetSelect(sigunguSelect, '시/군/구 선택');
        resetSelect(eupmyeondongSelect, '읍/면/동 선택');
        aptComboboxWrap.classList.remove('visible');
        clearAptSelection(); closeDropdown();
        const sido = sidoSelect.value;
        if (!sido) return;
        const items = await fetchRegions({ level: 'sigungu', sido });
        items.forEach((v) => { const o = document.createElement('option'); o.value = o.textContent = v; sigunguSelect.appendChild(o); });
        sigunguSelect.disabled = false;
    });

    sigunguSelect.addEventListener('change', async () => {
        resetSelect(eupmyeondongSelect, '읍/면/동 선택');
        aptComboboxWrap.classList.remove('visible');
        clearAptSelection(); closeDropdown();
        const sido = sidoSelect.value, sigungu = sigunguSelect.value;
        if (!sido || !sigungu) return;
        const items = await fetchRegions({ level: 'eupmyeondong', sido, sigungu });
        items.forEach((v) => { const o = document.createElement('option'); o.value = o.textContent = v; eupmyeondongSelect.appendChild(o); });
        eupmyeondongSelect.disabled = false;
    });

    eupmyeondongSelect.addEventListener('change', async () => {
        clearAptSelection(); closeDropdown();
        const sido = sidoSelect.value, sigungu = sigunguSelect.value, eupmyeondong = eupmyeondongSelect.value;
        if (!sido || !sigungu || !eupmyeondong) { aptComboboxWrap.classList.remove('visible'); return; }
        aptComboboxWrap.classList.add('visible');
        aptNameInput.placeholder = '불러오는 중…';
        aptNameInput.disabled = true;
        allApartments = await fetchApartmentsByRegion(sido, sigungu, eupmyeondong);
        aptNameInput.disabled = false;
        aptNameInput.placeholder = `공동주택명 검색 (${allApartments.length}개)`;
        aptNameInput.focus();
    });

    function renderDropdown(query) {
        const q = query.trim().toLowerCase();
        const filtered = q ? allApartments.filter((a) => a.name.toLowerCase().includes(q)) : allApartments;
        if (!filtered.length) {
            aptDropdown.innerHTML = '<div class="apt-option" style="color:#94a3b8;cursor:default;">검색 결과 없음</div>';
            aptDropdown.style.display = 'block'; return;
        }
        aptDropdown.innerHTML = filtered.map((a) => `
            <div class="apt-option" data-id="${a.id}" data-name="${escHtml(a.name)}">
                ${highlightMatch(a.name, query.trim())}
                <small>${escHtml(a.road_address || '')}</small>
            </div>
        `).join('');
        aptDropdown.style.display = 'block';
    }

    aptNameInput.addEventListener('input', () => { apartmentIdInput.value = ''; renderDropdown(aptNameInput.value); });
    aptNameInput.addEventListener('focus', () => { if (allApartments.length) renderDropdown(aptNameInput.value); });

    aptDropdown.addEventListener('mousedown', (e) => {
        const item = e.target.closest('.apt-option[data-id]');
        if (!item) return;
        e.preventDefault();
        apartmentIdInput.value = item.dataset.id;
        aptNameInput.value = item.dataset.name;
        aptSelectError.style.display = 'none';
        closeDropdown();
    });

    document.addEventListener('click', (e) => { if (!e.target.closest('#aptCombobox')) closeDropdown(); });

    if (form) {
        form.addEventListener('submit', (e) => {
            if (!apartmentIdInput.value) {
                e.preventDefault();
                aptSelectError.style.display = 'block';
                aptComboboxWrap.scrollIntoView({ behavior: 'smooth', block: 'center' });
            } else {
                aptSelectError.style.display = 'none';
            }
        });
    }

    loadSido();
})();
</script>
</body>
</html>
