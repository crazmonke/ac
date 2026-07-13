<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>알림 발송</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; margin: 0; background: #f5f7fb; color: #1a2a44; }
        .wrap { margin: 0; padding: 24px 28px; }
        .card { background: #fff; border: 1px solid #dce4ef; border-radius: 12px; padding: 24px; margin-bottom: 16px; }
        label { display: block; font-weight: 600; margin-bottom: 6px; font-size: 14px; }
        select, input[type=text], textarea {
            width: 100%; box-sizing: border-box;
            border: 1px solid #c8d4e3; border-radius: 8px;
            padding: 10px 12px; font: inherit; font-size: 14px;
            background: #f9fafb; margin-bottom: 14px;
        }
        select:focus, input:focus, textarea:focus {
            outline: none; border-color: #0f6f67; background: #fff;
        }
        textarea { resize: vertical; min-height: 80px; }
        .hint { font-size: 12px; color: #6b7a99; margin-top: -10px; margin-bottom: 14px; }
        button[type=submit] {
            background: #0f6f67; color: #fff; border: none; border-radius: 8px;
            padding: 12px 24px; font: inherit; font-weight: 700; cursor: pointer; font-size: 15px;
        }
        button[type=submit]:active { opacity: 0.85; }
        .alert-success {
            background: #e6f7f5; border: 1px solid #9de0d8; border-radius: 8px;
            padding: 12px 16px; color: #0a4d47; margin-bottom: 16px; font-weight: 600;
        }
        .alert-error {
            background: #fef2f2; border: 1px solid #fca5a5; border-radius: 8px;
            padding: 12px 16px; color: #7f1d1d; margin-bottom: 16px;
        }
        h1 { font-size: 22px; margin: 0 0 20px; }
        .topic-desc { font-size: 13px; color: #6b7a99; }
    </style>
</head>
<body>
<div class="wrap">
    @include('partials.admin-nav')

    <h1>알림 발송</h1>

    @if(session('status'))
        <div class="alert-success">{{ session('status') }}</div>
    @endif

    @if($errors->any())
        <div class="alert-error">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <div class="card">
        <form method="POST" action="/admin/notifications">
            @csrf

            <label for="topic">토픽 (수신 대상)</label>
            <select name="topic" id="topic">
                <option value="notice" {{ old('topic') === 'notice' ? 'selected' : '' }}>notice — 공지 알림 구독자</option>
                <option value="new_post" {{ old('topic') === 'new_post' ? 'selected' : '' }}>new_post — 새 글 알림 구독자</option>
                <option value="comment" {{ old('topic') === 'comment' ? 'selected' : '' }}>comment — 댓글 알림 구독자</option>
            </select>
            <p class="hint">앱에서 해당 토픽 알림을 켜 둔 사용자에게만 전송됩니다.</p>

            <label for="title">알림 제목</label>
            <input type="text" name="title" id="title" maxlength="100"
                   value="{{ old('title') }}" placeholder="예) 공지사항 안내" required>

            <label for="body">알림 내용</label>
            <textarea name="body" id="body" maxlength="300" required
                      placeholder="예) 새로운 공지사항이 등록되었습니다. 확인해 주세요.">{{ old('body') }}</textarea>

            <button type="submit">발송하기</button>
        </form>
    </div>

    <div class="card">
        <strong>안내</strong>
        <ul class="topic-desc">
            <li>발송 즉시 Firebase FCM을 통해 해당 토픽을 구독 중인 기기로 푸시 알림이 전송됩니다.</li>
            <li>앱 설정에서 해당 알림을 끈 사용자는 수신하지 않습니다.</li>
            <li>딥링크(특정 게시글 이동)가 필요한 경우에는 개발자에게 문의하세요.</li>
        </ul>
    </div>
</div>
</body>
</html>
