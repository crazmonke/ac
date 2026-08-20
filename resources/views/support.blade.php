<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <style>
        body { margin: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; background: #f4f8fb; color: #17263d; }
        .wrap { max-width: 720px; margin: 0 auto; padding: 24px 16px 48px; }
        .panel { background: #fff; border: 1px solid #d5dfec; border-radius: 14px; padding: 20px; }
        h1 { margin-top: 0; }
        li { margin: 12px 0; line-height: 1.6; }
        a { color: #0f6f67; font-weight: 700; }
    </style>
</head>
<body>
<div class="wrap">
    @include('partials.site-nav', ['apartmentId' => request()->query('apartment_id', 1)])
    <section class="panel">
        <h1>{{ $title }}</h1>
        <ul>
            <li>게시글이나 댓글 화면의 <strong>신고</strong> 버튼으로 내용을 신고해 주세요.</li>
            <li>신고 접수 후 운영팀은 <strong>24시간 이내</strong> 검토하고 삭제, 이용 제한 등 필요한 조치를 진행합니다.</li>
            <li>로그인 후 <a href="/messages/compose?to=admin">앱 내 관리자 문의</a>로 문의해 주세요.</li>
            @if(config('community.support_email'))
                <li>서비스 문의: <a href="mailto:{{ config('community.support_email') }}">{{ config('community.support_email') }}</a></li>
            @endif
            <li>신고 처리 결과나 계정 관련 문의는 이메일에 게시글 주소와 내용을 함께 적어 주세요.</li>
        </ul>
        <a href="/">홈으로 돌아가기</a>
    </section>
</div>
</body>
</html>