<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <style>
        body { margin: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; background: #f4f8fb; color: #17263d; }
        .wrap { max-width: 820px; margin: 0 auto; padding: 24px 16px 40px; }
        .panel { background: #fff; border: 1px solid #d5dfec; border-radius: 14px; padding: 16px; }
        a { color: #0f6f67; text-decoration: none; font-weight: 700; }
    </style>
</head>
<body>
<div class="wrap">
    @include('partials.site-nav', ['apartmentId' => request()->query('apartment_id', 1)])
    <a href="/">← 메인으로</a>
    <section class="panel" style="margin-top:10px;">
        <h1 style="margin-top:0;">{{ $title }}</h1>
        <div style="line-height:1.85; white-space:pre-line;">{{ $content }}</div>
    </section>
</div>
</body>
</html>
