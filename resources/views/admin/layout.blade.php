<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', '관리자')</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            margin: 0;
            padding: 0;
            background: #f5f7fb;
            color: #1a2a44;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 9px 16px;
            border: 0;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            font-size: 0.95rem;
            transition: all 0.2s;
        }
        .btn-primary {
            background: #0f7a72;
            color: #fff;
        }
        .btn-primary:hover {
            background: #0a5b56;
        }
        .btn-soft {
            background: #fff;
            border: 1px solid #dce4ef;
            color: #1a2a44;
        }
        .btn-soft:hover {
            background: #f5f7fb;
        }
    </style>
</head>
<body>
@include('partials.admin-nav')

@yield('content')
</body>
</html>
