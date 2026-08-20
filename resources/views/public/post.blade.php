<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apaind - {{ $post->title }}</title>
    <style>
        body { margin: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; background: #f5f8fb; color: #17263d; }
        .wrap { max-width: 880px; margin: 0 auto; padding: 0px 16px 40px;  }
        .top a { color: #0f6f67; text-decoration: none; font-weight: 700; }
        .panel { margin-top: 12px; background: #fff; border: 1px solid #d5dfec; border-radius: 14px; padding: 14px; }
        .meta { color: #5b6d82; font-size: 0.9rem; }
        .body {
            margin-top: 14px;
            line-height: 1.75;
            color: #1d2c42;
            overflow-wrap: anywhere;
            word-break: break-word;
        }
        .body p,
        .body ul,
        .body ol,
        .body blockquote,
        .body pre {
            margin: 0 0 1em;
        }
        .body a {
            color: #1f4ca1;
            text-decoration: underline;
        }
        .body img {
            max-width: 100%;
            height: auto;
            border-radius: 10px;
        }
        .body video,
        .body iframe,
        .body object,
        .body embed {
            display: block;
            max-width: 100%;
            border-radius: 10px;
        }
        .body video {
            width: 100%;
            height: auto;
            background: #000;
        }
        .body iframe,
        .body object,
        .body embed {
            width: 100%;
            aspect-ratio: 16 / 9;
        }
        .gate { margin-top: 12px; border: 1px solid #ffd5ab; border-radius: 12px; background: #fff4e8; padding: 14px; color: #7e4310; }
        .btn { text-decoration: none; display: inline-block; margin-top: 10px; border-radius: 9px; background: #0f6f67; color: #fff; padding: 9px 12px; font-weight: 700; }
    </style>
</head>
<body>
<div class="wrap">
    @include('partials.site-nav', ['apartmentId' => $apartmentId])
    <div class="top">
        <a href="/?apartment_id={{ $apartmentId }}">← 메인으로</a>
    </div>

    <section class="panel">
        <h1 style="margin:0 0 8px;">{{ $post->title }}</h1>
        <div class="meta">{{ $post->board->name }} · {{ $post->created_at }}</div>

        @if($canRead)
            <div class="body">{!! $post->body !!}</div>

            @auth
                <a class="btn" href="/reports/new?type=post&id={{ $post->id }}&apartment_id={{ $apartmentId }}">신고</a>
                <!--<a class="btn" href="/community/posts/{{ $post->id }}?apartment_id={{ $apartmentId }}">댓글/첨부 포함 전체 화면으로 이동</a>-->
            @endauth
        @else
            <div class="gate">
                이 글은 카테고리 권한 게시글입니다. 목록은 누구나 볼 수 있지만 본문 열람은 관련 인증 회원에게만 허용됩니다.
                <br>
                @if($isLoggedIn)
                    <a class="btn" href="/settings?apartment_id={{ $apartmentId }}">인증 상태 확인/변경</a>
                @else
                    <a class="btn" href="/register?redirect={{ urlencode('/posts/'.$post->id.'?apartment_id='.$apartmentId) }}">'나의 공동주택 찾기'하고 본문 보기</a>
                @endif
            </div>
        @endif
    </section>
</div>
</body>
</html>
