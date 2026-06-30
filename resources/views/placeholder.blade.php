<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
    <style>
        body {
            margin: 0;
            font-family: "SUIT", -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            color: #17263d;
            background: #f4f8fb;
        }
        .wrap {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            gap: 14px;
            padding: 20px;
        }
        .card {
            width: min(680px, 100%);
            background: #fff;
            border: 1px solid #d4dfeb;
            border-radius: 16px;
            padding: 24px;
        }
        h1 {
            margin: 0 0 10px;
            font-size: clamp(1.35rem, 4vw, 2rem);
        }
        p {
            margin: 0;
            line-height: 1.6;
            color: #5b6c7e;
        }
        a {
            display: inline-block;
            margin-top: 16px;
            color: #0b7a75;
            text-decoration: none;
            font-weight: 700;
        }
    </style>
</head>
<body>
<div class="wrap">
    @include('partials.site-nav', ['apartmentId' => request()->query('apartment_id', 1)])

    <section class="card">
        <h1>{{ $title }}</h1>
        <p>{{ $description }}</p>
        <a href="/">홈으로 이동</a>
    </section>
</div>
</body>
</html>
