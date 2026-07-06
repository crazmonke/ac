<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>검수 큐</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; margin: 0; background: #f5f7fb; color: #1a2a44; }
        .wrap { max-width: 1180px; margin: 0 auto; padding: 24px; }
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
        h1, h2, h3 { margin: 0; }
    </style>
</head>
<body>
<div class="wrap">
    @include('partials.admin-nav')
    <h1>검수 큐</h1>

    @if(session('status'))
        <div class="card" style="background:#e8f6f1; border-color:#bee6d9; color:#166b53;">{{ session('status') }}</div>
    @endif

    <section class="section">
        <h2>공동주택 매칭 검수</h2>
        <div class="grid" style="margin-top:12px;">
            @forelse($matchReviews as $review)
                <article class="card">
                    <h3>{{ $review->raw_apartment_name }}</h3>
                    <p class="meta">요청 사용자: {{ $review->user->name ?? '미지정' }} · 상태 <span class="status">{{ $review->status }}</span></p>
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
                        <select name="resolved_apartment_id">
                            <option value="">확정 공동주택 선택</option>
                            @foreach(($matchSuggestions[$review->id] ?? collect()) as $suggestion)
                                <option value="{{ $suggestion['id'] }}">{{ $suggestion['label'] }}</option>
                            @endforeach
                        </select>
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
</body>
</html>
