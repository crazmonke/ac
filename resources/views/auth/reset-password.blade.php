<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>비밀번호 변경</title>
    <style>
        body { margin: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; background: #eef4fa; color: #1b2d45; }
        .wrap { min-height: 100vh; display: flex; flex-direction: column; align-items: center; justify-content: flex-start; padding: 16px 20px 28px; gap: 14px; }
        .card { width: min(430px, 100%); background: #fff; border: 1px solid #d6e1ef; border-radius: 14px; padding: 22px; }
        h1 { margin: 0 0 6px; font-size: 1.45rem; }
        .desc { margin: 0 0 14px; font-size: 0.88rem; color: #53657a; line-height: 1.5; }
        .notice { background: #fef9c3; border: 1px solid #fde68a; border-radius: 10px; padding: 12px 14px; font-size: 0.87rem; color: #713f12; line-height: 1.6; margin-bottom: 14px; }
        label { display: block; margin-top: 10px; font-size: 0.9rem; }
        input { width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #c8d5e7; margin-top: 6px; font: inherit; color: inherit; background: #fff; box-sizing: border-box; }
        input:disabled { background: #f3f6fa; color: #94a3b8; }
        .btn { margin-top: 16px; width: 100%; border: 0; background: #0b7a75; color: #fff; padding: 11px; border-radius: 8px; cursor: pointer; font: inherit; font-weight: 700; }
        .btn:disabled { background: #94a3b8; cursor: not-allowed; }
        .err { margin-top: 10px; color: #b42318; font-size: 0.9rem; font-weight: 600; }
        .meta { margin-top: 14px; font-size: 0.86rem; color: #53657a; }
        a { color: #0f6f67; text-decoration: none; font-weight: 700; }
        .location-status { margin-top: 10px; padding: 10px 12px; border-radius: 8px; font-size: 0.88rem; font-weight: 600; }
        .location-status.checking { background: #f0f9ff; border: 1px solid #bae6fd; color: #0369a1; }
        .location-status.ok { background: #e9f7ef; border: 1px solid #b6e2c8; color: #136a45; }
        .location-status.fail { background: #fef2f2; border: 1px solid #fca5a5; color: #991b1b; }
        .invalid-card { text-align: center; }
        .invalid-card p { color: #b42318; font-size: 0.95rem; margin: 0 0 16px; }
        .pw-hint { margin-top: 6px; font-size: 0.82rem; color: #64748b; list-style: none; padding: 0; }
        .pw-hint li { margin: 2px 0; }
        .pw-hint li.ok { color: #136a45; }
        .pw-hint li.fail { color: #b42318; }
        .pw-match { margin-top: 5px; font-size: 0.82rem; font-weight: 600; }
        .pw-match.ok { color: #136a45; }
        .pw-match.err { color: #b42318; }
    </style>
</head>
<body>
<div class="wrap">
    @include('partials.site-nav', ['apartmentId' => 1])

    <div class="card">
        <h1>비밀번호 변경</h1>

        @if(! $tokenValid)
            <div class="invalid-card">
                <p>⚠️ 유효하지 않거나 만료된 비밀번호 변경 링크입니다.<br>링크는 발급 후 24시간 내에만 사용할 수 있습니다.</p>
                <a href="/forgot-password" style="display:inline-block; background:#0b7a75; color:#fff; padding:10px 20px; border-radius:8px; font-weight:700;">다시 요청하기</a>
            </div>
        @else
            <p class="desc">새 비밀번호를 입력해 주세요.</p>

            <div class="notice">
                ⚠️ <strong>위치 확인 안내</strong><br>
                보안을 위해 가입 시 등록한 공동주택 반경 <strong>3km 이내</strong>에서만 비밀번호를 변경할 수 있습니다.<br>
                위치 권한을 허용해 주세요. 위치를 확인할 수 없거나 다른 위치에서는 변경이 제한됩니다.
            </div>

            <div id="locationStatus" class="location-status checking">📍 위치 정보 확인 중...</div>

            @if ($errors->has('location'))
                <div class="err">{{ $errors->first('location') }}</div>
            @endif
            @if ($errors->has('token') || $errors->has('email'))
                <div class="err">{{ $errors->first('token') ?: $errors->first('email') }}</div>
            @endif

            <form method="post" action="{{ route('reset-password.update') }}" id="resetForm">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <input type="hidden" name="email" value="{{ $email }}">
                <input id="latitude"  type="hidden" name="latitude"  value="">
                <input id="longitude" type="hidden" name="longitude" value="">

                <label>새 비밀번호
                    <input id="pwInput" type="password" name="password" required autocomplete="new-password">
                    <ul id="pwHint" class="pw-hint">
                        <li id="ph-len">8자 이상</li>
                        <li id="ph-letter">영문자 포함</li>
                        <li id="ph-number">숫자 포함</li>
                        <li id="ph-symbol">특수문자 포함 (예: !@#$%^&*)</li>
                    </ul>
                </label>

                <label>새 비밀번호 확인
                    <input id="pwConfirm" type="password" name="password_confirmation" required autocomplete="new-password">
                    <div id="pwMatch" class="pw-match" style="display:none;"></div>
                </label>

                @if ($errors->has('password'))
                    <div class="err">{{ $errors->first('password') }}</div>
                @endif

                <button id="submitBtn" class="btn" type="submit" disabled>비밀번호 변경</button>
            </form>

            <div class="meta"><a href="/login">로그인으로 돌아가기</a></div>
        @endif
    </div>
</div>

@if($tokenValid)
<script>
(function () {
    const latInput   = document.getElementById('latitude');
    const lngInput   = document.getElementById('longitude');
    const statusEl   = document.getElementById('locationStatus');
    const submitBtn  = document.getElementById('submitBtn');
    const resetForm  = document.getElementById('resetForm');
    const pwInput    = document.getElementById('pwInput');
    const pwConfirm  = document.getElementById('pwConfirm');
    const pwMatch    = document.getElementById('pwMatch');
    const phLen      = document.getElementById('ph-len');
    const phLetter   = document.getElementById('ph-letter');
    const phNumber   = document.getElementById('ph-number');
    const phSymbol   = document.getElementById('ph-symbol');

    let locationOk = false;

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

    _appGetPosition(
        (pos) => {
            latInput.value  = String(pos.coords.latitude);
            lngInput.value  = String(pos.coords.longitude);
            locationOk = true;
            statusEl.textContent = '📍 위치 확인 완료';
            statusEl.className = 'location-status ok';
            updateSubmitState();
        },
        () => {
            statusEl.textContent = '⚠️ 위치 정보를 가져올 수 없습니다. 브라우저 위치 권한을 허용한 후 페이지를 새로고침해 주세요.';
            statusEl.className = 'location-status fail';
            locationOk = false;
            updateSubmitState();
        },
        { enableHighAccuracy: true, timeout: 8000, maximumAge: 0 }
    );

    function updatePwHint() {
        if (!pwInput) return;
        const v = pwInput.value;
        [[phLen, v.length >= 8], [phLetter, /[a-zA-Z]/.test(v)], [phNumber, /\d/.test(v)], [phSymbol, /[\W_]/.test(v)]].forEach(([el, ok]) => {
            if (!el) return;
            el.className = v.length === 0 ? '' : (ok ? 'ok' : 'fail');
        });
    }

    function updatePwMatch() {
        if (!pwInput || !pwConfirm || !pwMatch) return;
        const v1 = pwInput.value, v2 = pwConfirm.value;
        if (!v2) { pwMatch.style.display = 'none'; return; }
        if (v1 === v2) {
            pwMatch.textContent = '비밀번호가 일치합니다.';
            pwMatch.className = 'pw-match ok';
        } else {
            pwMatch.textContent = '비밀번호가 일치하지 않습니다.';
            pwMatch.className = 'pw-match err';
        }
        pwMatch.style.display = 'block';
    }

    function updateSubmitState() {
        submitBtn.disabled = !locationOk;
    }

    if (pwInput) pwInput.addEventListener('input', () => { updatePwHint(); updatePwMatch(); updateSubmitState(); });
    if (pwConfirm) pwConfirm.addEventListener('input', () => { updatePwMatch(); updateSubmitState(); });

    if (resetForm) {
        resetForm.addEventListener('submit', (e) => {
            if (!locationOk || !latInput.value || !lngInput.value) {
                e.preventDefault();
                statusEl.textContent = '⚠️ 위치 정보가 필요합니다. 위치 권한을 허용하고 다시 시도해 주세요.';
                statusEl.className = 'location-status fail';
            }
        });
    }
})();
</script>
@endif
</body>
</html>
