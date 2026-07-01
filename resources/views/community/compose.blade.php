<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>글쓰기 게시판 선택</title>
    <style>
        body { margin: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; background: #f4f8fb; color: #17263d; }
        .wrap { max-width: 920px; margin: 0 auto; padding: 24px; }
        .panel { background: #fff; border: 1px solid #d5dfec; border-radius: 12px; padding: 14px; margin-bottom: 14px; }
        .meta { color: #5b6d82; font-size: 0.92rem; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 10px; margin-top: 10px; }
        .board-card { border: 1px solid #dbe4f1; border-radius: 10px; padding: 12px; text-decoration: none; color: #17263d; background: #fff; }
        .board-card:hover { border-color: #0f6f67; }
        .btn { border: 1px solid #d5dfec; border-radius: 999px; padding: 8px 12px; text-decoration: none; color: #1d3552; background: #fff; font-weight: 700; }
        .empty { border: 1px solid #ffd7b5; background: #fff4e9; color: #7f4310; border-radius: 10px; padding: 12px; }
    </style>
</head>
<body>
<div class="wrap">
    @include('partials.site-nav', ['apartmentId' => $apartmentId])

    <section class="panel">
        <p class="meta"><a href="/community?apartment_id={{ $apartmentId }}">← 커뮤니티로 돌아가기</a></p>
        <h1 style="margin-top:0;">글쓰기</h1>
        <p class="meta">작성할 게시판을 선택해 주세요.</p>

        @if($writableBoards->count() > 0)
            <div class="grid">
                @foreach($writableBoards as $board)
                    <a class="board-card" href="/community/boards/{{ $board->slug }}/create?apartment_id={{ (int) ($board->apartment_id ?: $apartmentId) }}">
                        <strong>{{ $board->name }}</strong>
                        @if(!empty($board->description))
                            <div class="meta" style="margin-top:6px;">{{ \Illuminate\Support\Str::limit($board->description, 80) }}</div>
                        @endif
                    </a>
                @endforeach
            </div>
        @else
            <div class="empty">인증된 아파트에서 작성 가능한 게시판이 없습니다. 관리자에게 게시판 설정을 요청해 주세요.</div>
        @endif
    </section>
</div>
</body>
</html>
