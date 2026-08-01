<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $peer->name }}님과의 쪽지</title>
    <style>
        :root {
            --bg: #f4f8fb;
            --ink: #15243a;
            --muted: #62728a;
            --line: #d6e0ea;
            --card: #ffffff;
            --brand: #2e4fb8;
        }
        * { box-sizing: border-box; }
        body { margin: 0; background: var(--bg); color: var(--ink); font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; }
        .shell { max-width: 720px; margin: 0 auto; padding: 18px 16px 40px; }
        .top-row { display: flex; align-items: center; gap: 12px; margin-bottom: 14px; }
        .btn { border: 0; border-radius: 10px; padding: 8px 12px; font-weight: 700; cursor: pointer; text-decoration: none; display: inline-block; font: inherit; color: #22344f; background: #e7edf7; }
        .page-title { margin: 0; font-size: clamp(1.15rem, 2.4vw, 1.45rem); }
        .flash { background: #e7f6ec; border: 1px solid #b9e3c6; color: #1f7a3d; border-radius: 12px; padding: 10px 14px; margin-bottom: 12px; font-size: 0.9rem; }
        .err { background: #fdecec; border: 1px solid #f2c1c1; color: #9e1d1d; border-radius: 12px; padding: 10px 14px; margin-bottom: 12px; font-size: 0.9rem; }
        .thread { display: flex; flex-direction: column; gap: 8px; margin-bottom: 16px; }
        .bubble-row { display: flex; }
        .bubble-row.mine { justify-content: flex-end; }
        .bubble { max-width: 78%; border-radius: 16px; padding: 10px 14px; font-size: 0.92rem; line-height: 1.55; white-space: pre-wrap; word-break: break-word; }
        .bubble-row.mine .bubble { background: var(--brand); color: #fff; border-bottom-right-radius: 6px; }
        .bubble-row.theirs .bubble { background: var(--card); border: 1px solid var(--line); border-bottom-left-radius: 6px; }
        .bubble-meta { font-size: 0.7rem; color: #98aabf; margin-top: 3px; }
        .bubble-row.mine .bubble-meta { text-align: right; }
        .reply-card { background: var(--card); border: 1px solid var(--line); border-radius: 16px; padding: 14px; }
        .reply-card textarea { width: 100%; min-height: 90px; border: 1px solid var(--line); border-radius: 12px; padding: 10px 12px; font: inherit; resize: vertical; }
        .reply-card textarea:focus { outline: 2px solid #b9ccf5; border-color: #8ba7cf; }
        .reply-actions { display: flex; justify-content: flex-end; margin-top: 10px; }
        .btn-primary { background: var(--brand); color: #fff; }
        .pagination-wrap { margin-bottom: 14px; }
        .notice { color: var(--muted); font-size: 0.88rem; text-align: center; padding: 14px; }
    </style>
</head>
<body>
@include('partials.site-nav', ['apartmentId' => $apartmentId])

<div class="shell">
    <div class="top-row">
        <a class="btn" href="/messages">← 쪽지함</a>
        <h1 class="page-title">{{ $peer->name }}</h1>
    </div>

    @if(session('status'))
        <div class="flash">{{ session('status') }}</div>
    @endif

    @if($errors->any())
        <div class="err">{{ $errors->first() }}</div>
    @endif

    <div class="pagination-wrap">{{ $messages->links() }}</div>

    <div class="thread">
        @forelse($messages->reverse() as $message)
            <div class="bubble-row {{ $message->sender_id === $user->id ? 'mine' : 'theirs' }}">
                <div>
                    <div class="bubble">{{ $message->content }}</div>
                    <div class="bubble-meta">
                        {{ $message->created_at->format('Y-m-d H:i') }}
                        @if($message->sender_id === $user->id)
                            · {{ $message->read_at ? '읽음' : '안읽음' }}
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="notice">아직 주고받은 쪽지가 없습니다. 첫 쪽지를 보내보세요.</div>
        @endforelse
    </div>

    @if($canReply)
        <form class="reply-card" method="post" action="/messages">
            @csrf
            <input type="hidden" name="receiver_id" value="{{ $peer->id }}">
            <textarea name="content" maxlength="2000" placeholder="쪽지 내용을 입력하세요" required>{{ old('content') }}</textarea>
            <div class="reply-actions">
                <button class="btn btn-primary" type="submit">보내기</button>
            </div>
        </form>
    @else
        <div class="notice">이 사용자에게는 쪽지를 보낼 수 없습니다.</div>
    @endif
</div>
<script>
    // 최신 쪽지가 바로 보이도록 진입 시 스레드 하단으로 스크롤
    window.addEventListener('load', function () {
        var thread = document.querySelector('.thread');
        if (thread && thread.getBoundingClientRect().bottom > window.innerHeight) {
            window.scrollTo(0, document.body.scrollHeight);
        }
    });
</script>
</body>
</html>
