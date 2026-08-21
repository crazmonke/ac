<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>쪽지 쓰기</title>
    <style>
        :root {
            --bg: #f4f8fb;
            --ink: #15243a;
            --muted: #62728a;
            --line: #d6e0ea;
            --card: #ffffff;
            --brand: #2e4fb8;
        }
        * { box-sizing: border-box; }
        body { margin: 0; background: var(--bg); color: var(--ink); font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; }
        .shell { max-width: 720px; margin: 0 auto; padding: 0px 16px 40px; }
        .top-row { display: flex; align-items: center; gap: 12px; margin-bottom: 14px; }
        .btn { border: 0; border-radius: 10px; padding: 8px 12px; font-weight: 700; cursor: pointer; text-decoration: none; display: inline-block; font: inherit; color: #22344f; background: #e7edf7; }
        .btn-primary { background: var(--brand); color: #fff; }
        .page-title { margin: 0; font-size: clamp(1.15rem, 2.4vw, 1.45rem); }
        .card { background: var(--card); border: 1px solid var(--line); border-radius: 16px; padding: 16px; }
        .err { background: #fdecec; border: 1px solid #f2c1c1; color: #9e1d1d; border-radius: 12px; padding: 10px 14px; margin-bottom: 12px; font-size: 0.9rem; }
        label { display: block; font-weight: 700; font-size: 0.88rem; margin-bottom: 6px; }
        .field { margin-bottom: 14px; }
        input[type="text"], textarea { width: 100%; border: 1px solid var(--line); border-radius: 12px; padding: 10px 12px; font: inherit; }
        input[type="text"]:focus, textarea:focus { outline: 2px solid #b9ccf5; border-color: #8ba7cf; }
        textarea { min-height: 160px; resize: vertical; }
        .recipient-box { position: relative; }
        .recipient-chip { display: inline-flex; align-items: center; gap: 8px; background: #eef5ff; border: 1px solid #c7d9fb; color: #1d3fa6; border-radius: 999px; padding: 6px 12px; font-weight: 700; font-size: 0.9rem; }
        .recipient-chip button { border: 0; background: transparent; color: #1d3fa6; font-weight: 800; cursor: pointer; font-size: 1rem; line-height: 1; padding: 0; }
        .search-results { position: absolute; top: calc(100% + 4px); left: 0; right: 0; background: #fff; border: 1px solid var(--line); border-radius: 12px; box-shadow: 0 10px 24px rgba(20, 35, 60, 0.12); z-index: 20; overflow: hidden; display: none; }
        .search-results.open { display: block; }
        .search-results button { display: block; width: 100%; text-align: left; border: 0; background: #fff; padding: 10px 14px; font: inherit; cursor: pointer; }
        .search-results button:hover { background: #f2f7ff; }
        .search-results .no-result { padding: 10px 14px; color: var(--muted); font-size: 0.88rem; }
        .admin-shortcut { margin-top: 8px; font-size: 0.85rem; }
        .admin-shortcut a { color: #1d3fa6; font-weight: 700; text-decoration: none; }
        .form-actions { display: flex; justify-content: flex-end; gap: 8px; margin-top: 6px; }
        .hint { color: var(--muted); font-size: 0.8rem; margin-top: 6px; }
    </style>
</head>
<body>
@include('partials.site-nav', ['apartmentId' => $apartmentId])

<div class="shell">
    <div class="top-row">
        <a class="btn" href="/messages">← 쪽지함</a>
        <h1 class="page-title">쪽지 쓰기</h1>
    </div>

    @if($errors->any())
        <div class="err">{{ $errors->first() }}</div>
    @endif

    <form class="card" method="post" action="/messages">
        @csrf
        @include('messages.partials.quota-notice')
        <div class="field recipient-box">
            <label for="recipient-search">받는 사람</label>

            <div id="recipient-selected" style="{{ $recipient ? '' : 'display:none;' }} margin-bottom:8px;">
                <span class="recipient-chip">
                    <span id="recipient-name">{{ $recipient?->name }}</span>
                    <button type="button" onclick="clearRecipient()" aria-label="받는 사람 지우기">×</button>
                </span>
            </div>

            <input type="hidden" name="receiver_id" id="receiver-id" value="{{ old('receiver_id', $recipient?->id) }}">
            <input type="text" id="recipient-search" placeholder="회원 이름으로 검색 (2자 이상)" autocomplete="off"
                   style="{{ $recipient ? 'display:none;' : '' }}">
            <div class="search-results" id="search-results"></div>

            @if($adminUser)
                <div class="admin-shortcut">
                    관리자에게 문의하시겠어요?
                    <a href="#" onclick="selectRecipient({{ $adminUser->id }}, '{{ $adminUser->name }} (관리자)'); return false;">관리자에게 쪽지 보내기</a>
                </div>
            @endif
        </div>

        <div class="field">
            <label for="content">내용</label>
            <textarea name="content" id="content" maxlength="2000" placeholder="쪽지 내용을 입력하세요" required>{{ old('content') }}</textarea>
            <div class="hint">최대 2000자까지 입력할 수 있습니다.</div>
        </div>

        <div class="form-actions">
            <button class="btn btn-primary" type="submit">보내기</button>
        </div>
    </form>
</div>

<script>
    var searchInput = document.getElementById('recipient-search');
    var resultsBox = document.getElementById('search-results');
    var searchTimer = null;

    function selectRecipient(id, name) {
        document.getElementById('receiver-id').value = id;
        document.getElementById('recipient-name').textContent = name;
        document.getElementById('recipient-selected').style.display = '';
        searchInput.style.display = 'none';
        searchInput.value = '';
        resultsBox.classList.remove('open');
    }

    function clearRecipient() {
        document.getElementById('receiver-id').value = '';
        document.getElementById('recipient-selected').style.display = 'none';
        searchInput.style.display = '';
        searchInput.focus();
    }

    searchInput.addEventListener('input', function () {
        var keyword = searchInput.value.trim();
        clearTimeout(searchTimer);

        if (keyword.length < 2) {
            resultsBox.classList.remove('open');
            return;
        }

        searchTimer = setTimeout(function () {
            fetch('/messages/users/search?q=' + encodeURIComponent(keyword), {
                headers: { 'Accept': 'application/json' }
            })
                .then(function (res) { return res.ok ? res.json() : { data: [] }; })
                .then(function (json) {
                    resultsBox.innerHTML = '';

                    if (!json.data || json.data.length === 0) {
                        resultsBox.innerHTML = '<div class="no-result">검색 결과가 없습니다.</div>';
                    } else {
                        json.data.forEach(function (user) {
                            var button = document.createElement('button');
                            button.type = 'button';
                            button.textContent = user.name;
                            button.addEventListener('click', function () {
                                selectRecipient(user.id, user.name);
                            });
                            resultsBox.appendChild(button);
                        });
                    }

                    resultsBox.classList.add('open');
                })
                .catch(function () {
                    resultsBox.classList.remove('open');
                });
        }, 250);
    });

    document.addEventListener('click', function (event) {
        if (!event.target.closest('.recipient-box')) {
            resultsBox.classList.remove('open');
        }
    });
</script>
</body>
</html>
