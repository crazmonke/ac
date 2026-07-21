<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>로그인</title>
    <style>
        body { margin: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; background: #eef4fa; color: #1b2d45; }
        .wrap { min-height: 100vh; display: flex; flex-direction: column; align-items: center; justify-content: flex-start; padding: 0px 20px 28px; gap: 14px; }
        .card { width: min(420px, 100%); background: #fff; border: 1px solid #d6e1ef; border-radius: 14px; padding: 22px; }
        h1 { margin: 0 0 14px; font-size: 1.45rem; }
        label { display: block; margin-top: 10px; font-size: 0.9rem; }
        input { width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #c8d5e7; margin-top: 6px; }
        .btn { margin-top: 16px; width: 100%; border: 0; background: #0b7a75; color: #fff; padding: 11px; border-radius: 8px; cursor: pointer; font-weight: 700; }
        .err { margin-top: 10px; color: #b42318; font-size: 0.9rem; }
        .meta { margin-top: 14px; font-size: 0.86rem; color: #53657a; }
        a { color: #0f6f67; text-decoration: none; font-weight: 700; }
    </style>
</head>
<body>
<div class="wrap">
    @include('partials.site-nav', ['apartmentId' => request()->query('apartment_id', 1)])
    <form class="card" method="post" action="{{ route('login.attempt') }}">
        @csrf
        <input type="hidden" name="redirect" value="{{ old('redirect', $redirect ?? '/') }}">
        <h1>로그인</h1>

        <label>이메일
            <input type="email" name="email" value="{{ old('email') }}" required>
        </label>

        <label>비밀번호
            <input type="password" name="password" required>
        </label>

        <label>
            <input type="checkbox" name="remember" value="1" style="width:auto; margin-right:6px;"> 로그인 유지
        </label>

        @if ($errors->any())
            <div class="err">{{ $errors->first() }}</div>
        @endif

        <button class="btn" type="submit">로그인</button>

        <div class="meta">
            계정이 없다면 <a href="/register">회원 가입</a> 후 이용해 주세요.
        </div>
        <div class="meta">
            <a href="/find-email">이메일 찾기</a> &middot; <a href="/forgot-password">비밀번호 찾기</a>
        </div>
    </form>
</div>
</body>
</html>
