<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>신고 접수</title>
    <style>
        body { margin: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; background: #f4f8fb; color: #17263d; }
        .wrap { max-width: 760px; margin: 0 auto; padding: 20px 16px 40px; }
        .card { background: #fff; border: 1px solid #d5dfec; border-radius: 14px; padding: 16px; }
        .meta { color: #5b6d82; font-size: 0.9rem; }
        label { display: block; margin-top: 10px; font-size: 0.9rem; font-weight: 700; color: #334964; }
        input, select, textarea { width: 100%; margin-top: 6px; border: 1px solid #c8d5e7; border-radius: 10px; padding: 10px; font: inherit; }
        textarea { min-height: 130px; resize: vertical; }
        .btn { margin-top: 14px; border: 0; border-radius: 10px; background: #0f6f67; color: #fff; padding: 10px 12px; font-weight: 700; cursor: pointer; }
        .err { margin-top: 10px; color: #b42318; font-size: 0.9rem; }
        a { color: #0f6f67; text-decoration: none; font-weight: 700; }
    </style>
</head>
<body>
<div class="wrap">
    @include('partials.site-nav', ['apartmentId' => $apartmentId])

    <div class="card">
        <h1 style="margin:0 0 8px;">신고 접수</h1>
        <p class="meta">게시글 또는 댓글의 신고 버튼에서 바로 접수할 수 있습니다. 접수된 신고는 운영팀이 24시간 이내 검토하고 필요한 조치를 진행합니다. 문의가 필요하면 <a href="/support">문의 및 신고 안내</a>를 확인해 주세요.</p>

        <form method="post" action="/reports">
            @csrf
            <input type="hidden" name="apartment_id" value="{{ $apartmentId }}">

            <label>신고 대상 유형
                <select name="reportable_type" required>
                    <option value="post" @selected(old('reportable_type', $defaultType) === 'post')>게시글</option>
                    <option value="comment" @selected(old('reportable_type', $defaultType) === 'comment')>댓글</option>
                </select>
            </label>

            <label>신고 대상 ID
                <input type="number" name="reportable_id" min="1" value="{{ old('reportable_id', $defaultId > 0 ? $defaultId : '') }}" required>
            </label>

            <label>신고 사유
                <select name="reason" required>
                    <option value="spam" @selected(old('reason') === 'spam')>스팸/도배</option>
                    <option value="abuse" @selected(old('reason') === 'abuse')>욕설/비하/괴롭힘</option>
                    <option value="illegal" @selected(old('reason') === 'illegal')>불법/유해 정보</option>
                    <option value="other" @selected(old('reason') === 'other')>기타</option>
                </select>
            </label>

            <label>상세 내용
                <textarea name="detail" maxlength="2000" placeholder="운영팀이 판단할 수 있도록 구체적으로 작성해 주세요.">{{ old('detail') }}</textarea>
            </label>

            @if ($errors->any())
                <div class="err">{{ $errors->first() }}</div>
            @endif

            <button class="btn" type="submit">신고 접수하기</button>
        </form>

        <p class="meta" style="margin-top:12px;"><a href="/?apartment_id={{ $apartmentId }}">메인으로 돌아가기</a></p>
    </div>
</div>
</body>
</html>
