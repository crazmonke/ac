<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>검수 큐</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; margin: 0; background: #f5f7fb; color: #1a2a44; }
        .wrap { margin: 0; padding: 24px 28px; }
        .section { margin-top: 18px; }
        .card { background: #fff; border: 1px solid #dce4ef; border-radius: 12px; padding: 16px; margin-bottom: 12px; }
        .grid { display: grid; gap: 12px; grid-template-columns: 1fr; }
        .meta { color: #607086; font-size: 0.88rem; }
        .status { display: inline-flex; padding: 4px 8px; border-radius: 999px; background: #eef2f8; font-size: 0.78rem; font-weight: 700; }
        .suggestions { display: flex; gap: 8px; flex-wrap: wrap; margin: 8px 0 0; }
        .suggestions span { background: #f4f8ff; border: 1px solid #d9e4ff; border-radius: 999px; padding: 4px 8px; font-size: 0.8rem; }
        .form-grid { display: grid; grid-template-columns: 1fr; gap: 10px; margin-top: 10px; }
        select, textarea, input { width: 100%; border: 1px solid #c8d5e7; border-radius: 8px; padding: 9px; font: inherit; }
        textarea { min-height: 90px; }
        .actions { display: flex; gap: 8px; flex-wrap: wrap; }
        .btn { border: 0; border-radius: 10px; padding: 10px 12px; font-weight: 700; cursor: pointer; }
        .btn-primary { background: #2e4fb8; color: #fff; }
        .btn-danger { background: #b42318; color: #fff; }
        .btn-muted { background: #455a8f; color: #fff; }
        .inline-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
        .output { white-space: pre-wrap; background: #0f172a; color: #e2e8f0; border-radius: 10px; padding: 10px; font-size: 0.84rem; overflow: auto; }
        h1, h2, h3 { margin: 0; }
        .apt-search-wrapper { position: relative; }
        .apt-search-dropdown { position: absolute; top: 100%; left: 0; right: 0; background: #fff; border: 1px solid #c8d5e7; border-top: none; border-radius: 0 0 8px 8px; max-height: 200px; overflow-y: auto; display: none; z-index: 100; }
        .apt-search-dropdown.show { display: block; }
        .apt-search-option { padding: 10px 9px; border-bottom: 1px solid #f0f0f0; cursor: pointer; transition: background 0.15s; }
        .apt-search-option:hover { background: #f4f8ff; }
        .apt-search-option.selected { background: #e3efff; }
        .apt-search-no-results { padding: 10px 9px; color: #607086; text-align: center; }
    </style>
</head>
<body>
<div class="wrap">
    @include('partials.admin-nav')
    <h1>검수 큐</h1>

    @if(session('status'))
        <div class="card" style="background:#e8f6f1; border-color:#bee6d9; color:#166b53;">{{ session('status') }}</div>
    @endif

    @if($errors->has('bulk_auto_approve'))
        <div class="card" style="background:#fff1f2; border-color:#fecdd3; color:#9f1239;">{{ $errors->first('bulk_auto_approve') }}</div>
    @endif

    <section class="section">
        <h2>공동주택 매칭 검수</h2>
        <div class="grid" style="margin-top:12px;">
            @forelse($matchReviews as $review)
                <article class="card">
                    <h3>{{ $review->raw_apartment_name }}</h3>
                    <p class="meta">요청 사용자: {{ $review->user->name ?? '미지정' }} · 상태 <span class="status">{{ $review->status }}</span></p>
                    
                    @if($review->latitude && $review->longitude)
                        <p class="meta">📍 GPS 좌표: {{ number_format($review->latitude, 6) }}, {{ number_format($review->longitude, 6) }}</p>
                    @endif
                    
                    @if($review->road_address)
                        <p class="meta">📮 도로명주소: {{ $review->road_address }}</p>
                    @endif
                    
                    @if($review->user?->home_sido)
                        <p class="meta">지역: {{ $review->user->home_sido }} {{ $review->user->home_sigungu }} {{ $review->user->home_eupmyeondong }}</p>
                    @endif
                    
                    @if($review->suggestedApartment)
                        <p class="meta">자동 제안: {{ $review->suggestedApartment->name }}</p>
                    @endif
                    
                    <div class="suggestions">
                        @foreach(($matchSuggestions[$review->id] ?? collect()) as $suggestion)
                            <span>{{ $suggestion['label'] }}</span>
                        @endforeach
                    </div>
                    
                    <form method="post" action="/admin/review-queue/matches/{{ $review->id }}" class="form-grid">
                        @csrf
                        @method('put')
                        <div class="apt-search-wrapper">
                            <input type="text" class="apt-search-input" name="custom_apartment_name" placeholder="공동주택명 검색" data-review-id="{{ $review->id }}" data-sido="{{ $review->user?->home_sido ?? '' }}" data-sigungu="{{ $review->user?->home_sigungu ?? '' }}" data-eupmyeondong="{{ $review->user?->home_eupmyeondong ?? '' }}" autocomplete="off">
                            <input type="hidden" class="apt-search-hidden-id" name="resolved_apartment_id">
                            <div class="apt-search-dropdown"></div>
                        </div>
                        <div class="create-new-apt-option" style="display: none; padding: 8px; background: #f0f8ff; border: 1px solid #d0e8ff; border-radius: 4px; margin: 8px 0;">
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; margin: 0;">
                                <input type="checkbox" class="create-new-apartment-check" name="create_new_apartment" value="1">
                                <span>✨ 이 공동주택을 데이터베이스에 새로 추가</span>
                            </label>
                            <p style="font-size: 0.9em; color: #666; margin: 6px 0 0 0;">체크하면 입력한 공동주택명이 새로운 항목으로 DB에 추가되고 사용자와 매칭됩니다.</p>
                        </div>
                        <textarea name="admin_note" placeholder="검수 메모"></textarea>
                        <div class="actions">
                            <button class="btn btn-primary" type="submit" name="status" value="resolved">매칭 확정</button>
                            <button class="btn btn-danger" type="submit" name="status" value="rejected">반려</button>
                        </div>
                    </form>
                </article>
            @empty
                <article class="card">대기 중인 공동주택 매칭 검수 요청이 없습니다.</article>
            @endforelse
        </div>
    </section>

    <section class="section">
        <h2>입주민 인증 검수</h2>
        <div class="grid" style="margin-top:12px;">
            @forelse($verificationRequests as $requestItem)
                <article class="card">
                    <h3>{{ $requestItem->user->name }} · {{ $requestItem->apartment->name }}</h3>
                    <p class="meta">이메일: {{ $requestItem->user->email }} · 상태 <span class="status">{{ $requestItem->status }}</span></p>
                    @if($requestItem->request_note)
                        <p class="meta">요청 메모: {{ $requestItem->request_note }}</p>
                    @endif
                    <form method="post" action="/admin/review-queue/verifications/{{ $requestItem->id }}" class="form-grid">
                        @csrf
                        @method('put')
                        <textarea name="admin_note" placeholder="승인/반려 메모">{{ $requestItem->admin_note }}</textarea>
                        <div class="actions">
                            <button class="btn btn-primary" type="submit" name="status" value="approved">승인</button>
                            <button class="btn btn-danger" type="submit" name="status" value="rejected">반려</button>
                        </div>
                    </form>
                </article>
            @empty
                <article class="card">대기 중인 입주민 인증 요청이 없습니다.</article>
            @endforelse
        </div>
    </section>

    <section class="section">
        <h2>공동주택 인증 검수</h2>
        <article class="card" style="margin-top:12px; border-style: dashed;">
            <h3>일괄 승인 실행</h3>
            <p class="meta" style="margin-top:8px;">검수 큐의 pending 공동주택 인증 요청을 범위 제한으로 일괄 처리합니다. 먼저 미리보기 후 실제 실행을 권장합니다.</p>
            <form method="post" action="/admin/review-queue/residence-verifications/bulk-auto-approve" class="form-grid" style="margin-top:10px;">
                @csrf
                <div class="inline-grid">
                    <label>
                        조회 시간 범위(시간)
                        <input type="number" name="hours" min="0" max="720" value="{{ old('hours', 72) }}" required>
                    </label>
                    <label>
                        최대 처리 건수
                        <input type="number" name="limit" min="1" max="2000" value="{{ old('limit', 200) }}" required>
                    </label>
                </div>
                <label style="display:flex; align-items:center; gap:8px; color:#1a2a44; font-weight:600;">
                    <input type="checkbox" name="include_no_coordinates" value="1" @checked(old('include_no_coordinates', true)) style="width:auto;">
                    GPS 좌표 누락 건도 관리자 기준으로 일괄 승인
                </label>
                <label>
                    관리자 메모(선택)
                    <input type="text" name="admin_note" maxlength="500" value="{{ old('admin_note', '검수큐 일괄 승인 처리') }}" placeholder="예: 검수큐 일괄 승인 처리">
                </label>
                <div class="actions">
                    <button class="btn btn-muted" type="submit" name="mode" value="preview">미리보기 실행</button>
                    <button class="btn btn-danger" type="submit" name="mode" value="execute" onclick="return confirm('선택한 조건으로 실제 일괄 승인을 실행할까요?');">실제 일괄 승인</button>
                </div>
            </form>
            @if(session('bulkAutoApproveOutput'))
                <div style="margin-top:10px;">
                    <div class="meta" style="margin-bottom:6px;">최근 실행 로그</div>
                    <pre class="output">{{ session('bulkAutoApproveOutput') }}</pre>
                </div>
            @endif
        </article>
        <div class="grid" style="margin-top:12px;">
            @forelse($residenceVerificationRequests as $requestItem)
                @php
                    $meta = is_array($requestItem->evidence_meta ?? null) ? $requestItem->evidence_meta : [];
                    $hasCoords = isset($meta['latitude'], $meta['longitude']) && is_numeric($meta['latitude']) && is_numeric($meta['longitude']);
                @endphp
                <article class="card">
                    <h3>{{ $requestItem->user->name }} · {{ $requestItem->complex?->displayName() ?? '미지정' }}</h3>
                    <p class="meta">이메일: {{ $requestItem->user->email }} · 상태 <span class="status">{{ $requestItem->verification_status }}</span></p>
                    <p class="meta">건물: {{ $requestItem->building?->road_address ?? '-' }} · 세대: {{ $requestItem->unit?->unit_label_generated ?? '미입력' }}</p>
                    <p class="meta">GPS 좌표: {{ $hasCoords ? '저장됨' : '없음' }}</p>
                    <form method="post" action="/admin/review-queue/residence-verifications/{{ $requestItem->id }}" class="form-grid">
                        @csrf
                        @method('put')
                        <div class="actions">
                            <button class="btn btn-primary" type="submit" name="status" value="approved">승인</button>
                            <button class="btn btn-danger" type="submit" name="status" value="rejected">반려</button>
                        </div>
                    </form>
                    <form method="post" action="/admin/review-queue/residence-verifications/{{ $requestItem->id }}/retry" class="form-grid" style="margin-top:6px;">
                        @csrf
                        <div class="actions">
                            <button class="btn" type="submit" style="background:#455a8f; color:#fff;" @disabled(! $hasCoords)>재검증</button>
                        </div>
                    </form>
                </article>
            @empty
                <article class="card">대기 중인 공동주택 인증 요청이 없습니다.</article>
            @endforelse
        </div>
    </section>

    <section class="section">
        <h2>중복 공동주택 병합 검수</h2>
        <div class="grid" style="margin-top:12px;">
            @forelse($mergeCandidates as $candidate)
                <article class="card">
                    <h3>유사도 {{ number_format($candidate->score, 2) }}</h3>
                    <p class="meta">source: {{ $candidate->sourceComplex?->displayName() ?? '-' }}</p>
                    <p class="meta">target: {{ $candidate->targetComplex?->displayName() ?? '-' }}</p>
                    @if(is_array($candidate->reason))
                        <p class="meta">distance_m: {{ $candidate->reason['distance_m'] ?? '-' }} · name_similarity: {{ $candidate->reason['name_similarity'] ?? '-' }}</p>
                    @endif
                    <form method="post" action="/admin/review-queue/merges/{{ $candidate->id }}" class="form-grid">
                        @csrf
                        @method('put')
                        <div class="actions">
                            <button class="btn btn-primary" type="submit" name="status" value="approved">병합 승인</button>
                            <button class="btn btn-danger" type="submit" name="status" value="rejected">병합 반려</button>
                        </div>
                    </form>
                </article>
            @empty
                <article class="card">검토할 중복 병합 후보가 없습니다.</article>
            @endforelse
        </div>
    </section>
</div>

<script>
document.querySelectorAll('.apt-search-input').forEach(input => {
    input.addEventListener('input', async (e) => {
        const query = e.target.value.trim();
        const reviewId = e.target.dataset.reviewId;
        const dropdown = e.target.nextElementSibling.nextElementSibling; // skip hidden input
        const hiddenInput = e.target.nextElementSibling;
        const form = e.target.closest('form');
        const createNewOption = form?.querySelector('.create-new-apt-option');
        
        if (!query) {
            dropdown.classList.remove('show');
            if (createNewOption) createNewOption.style.display = 'none';
            return;
        }
        
        try {
            const sido = e.target.dataset.sido || '';
            const sigungu = e.target.dataset.sigungu || '';
            const eupmyeondong = e.target.dataset.eupmyeondong || '';
            const regionParams = new URLSearchParams({ q: query, limit: '10' });
            if (sido) regionParams.set('sido', sido);
            if (sigungu) regionParams.set('sigungu', sigungu);
            if (eupmyeondong) regionParams.set('eupmyeondong', eupmyeondong);
            const response = await fetch(`/apartments/search?${regionParams.toString()}`);
            const data = await response.json();
            const results = data.results || data.data || [];
            
            if (results.length === 0) {
                // 검색 결과 없음 → 사용자 입력을 직접입력 옵션으로 제공
                dropdown.innerHTML = `<div class="apt-search-option" data-id="0" data-name="${query}" style="background: #fff9e6; border-top: 2px solid #ffd966;">
                    ${query}
                    <span style="color: #ff9800; font-size: 0.85em; margin-left: 8px;">
                        💡 직접입력
                    </span>
                </div>`;
            } else {
                dropdown.innerHTML = results.map(apt => {
                    const label = apt.name || apt.label || '';
                    const region = [apt.sido, apt.sigungu, apt.eupmyeondong].filter(x => x).join(' ');
                    return `<div class="apt-search-option" data-id="${apt.id}" data-name="${label}">
                        ${label}
                        <span style="color: #999; font-size: 0.85em; margin-left: 8px;">
                            ${region}
                        </span>
                    </div>`;
                }).join('');
            }
            
            dropdown.classList.add('show');
            
            dropdown.querySelectorAll('.apt-search-option').forEach(option => {
                option.addEventListener('click', () => {
                    const id = option.dataset.id;
                    const name = option.dataset.name;
                    input.value = name;
                    hiddenInput.value = id;
                    dropdown.classList.remove('show');
                    
                    // 직접입력(id=0) 선택 시 새 공동주택 생성 옵션 표시
                    if (createNewOption) {
                        if (id === '0' || id === 0) {
                            createNewOption.style.display = 'block';
                        } else {
                            createNewOption.style.display = 'none';
                        }
                    }
                });
            });
        } catch (error) {
            console.error('검색 오류:', error);
            dropdown.innerHTML = '<div class="apt-search-no-results">검색 오류 발생</div>';
            dropdown.classList.add('show');
        }
    });
    
    // Close dropdown when clicking outside
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.apt-search-wrapper')) {
            document.querySelectorAll('.apt-search-dropdown').forEach(dd => {
                dd.classList.remove('show');
            });
        }
    });
    
    // Focus event to show suggestions
    input.addEventListener('focus', async (e) => {
        const query = e.target.value.trim();
        if (query.length > 0) {
            e.target.dispatchEvent(new Event('input'));
        }
    });
});
</script>
</body>
</html>
