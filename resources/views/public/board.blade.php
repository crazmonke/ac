<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $board->name }}</title>
    <style>
        body { margin: 0; font-family: 'SUIT', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f5f8fb; color: #17263d; }
        .wrap { max-width: 980px; margin: 0 auto; padding: 22px 16px 42px; }
        .top { display: flex; justify-content: space-between; gap: 10px; align-items: center; flex-wrap: wrap; }
        .top a { text-decoration: none; color: #0f6f67; font-weight: 700; }
        .panel { margin-top: 12px; background: #fff; border: 1px solid #d5dfec; border-radius: 14px; padding: 14px; }
        .meta { color: #5b6d82; font-size: 0.9rem; }
        .item { padding: 12px 0; border-top: 1px solid #e7edf5; }
        .item:first-child { border-top: 0; }
        .title { text-decoration: none; color: #17263d; font-weight: 700; }
        .badge { margin-left: 6px; font-size: 0.78rem; border-radius: 999px; padding: 3px 8px; background: #ffefe0; color: #9b4f0a; }
        .cta { margin-top: 14px; padding: 12px; border-radius: 12px; background: #fff4e8; border: 1px solid #ffd5ab; color: #7e4310; }
        .btn { display: inline-block; margin-top: 8px; text-decoration: none; border-radius: 8px; background: #0f6f67; color: #fff; padding: 8px 11px; font-weight: 700; }
    </style>
</head>
<body>
<div class="wrap">
    @include('partials.site-nav', ['apartmentId' => $apartmentId])
    <div class="top">
        <h1 style="margin:0;">{{ $board->name }}</h1>
        <div>
            <a href="/?apartment_id={{ $apartmentId }}">메인</a>
            <span class="meta">·</span>
            <a href="/community?apartment_id={{ $apartmentId }}">입주민 커뮤니티</a>
        </div>
    </div>

    <div class="panel">
        <p class="meta">{{ $board->description ?: '게시판 설명이 없습니다.' }}</p>
        @if(! $canRead)
            <div class="cta">
                이 게시판은 회원/입주민 전용입니다. 제목은 볼 수 있지만 상세 내용은 가입 후 확인할 수 있습니다.
                <br>
                <a class="btn" href="/register?redirect={{ urlencode('/boards/'.$board->slug.'?apartment_id='.$apartmentId) }}">회원가입하고 전체 보기</a>
            </div>
        @endif
    </div>

    <section class="panel">
        @forelse($posts as $post)
            <article class="item">
                <a class="title" href="{{ $canRead ? '/posts/'.$post->id.'?apartment_id='.$apartmentId : '/register?redirect='.urlencode('/posts/'.$post->id.'?apartment_id='.$apartmentId) }}">
                    {{ $post->title }}
                </a>
                @if(! $canRead)
                    <span class="badge">상세는 가입 후</span>
                @endif
                <div class="meta" style="margin-top:5px;">{{ $post->created_at }}</div>
            </article>
        @empty
            <p class="meta">게시글이 없습니다.</p>
        @endforelse

        <div style="margin-top:10px;" class="meta">{{ $posts->links() }}</div>
    </section>
</div>
</body>
</html>
