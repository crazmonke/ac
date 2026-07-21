<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>알림</title>
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

        .shell { max-width: 640px; margin: 0 auto; padding: 0 0 32px; }

        /* 앱 전용 상단 헤더 */
        .app-header {
            position: sticky;
            top: 0;
            z-index: 10;
            background: rgba(244, 248, 251, 0.96);
            border-bottom: 1px solid var(--line);
            backdrop-filter: blur(10px);
            padding: 14px 18px 12px;
        }
        .app-header h1 { margin: 0; font-size: 1.2rem; font-weight: 800; }

        /* 알림 목록 */
        .notif-list { padding: 0 12px; margin-top: 8px; }

        .notif-item {
            display: flex;
            gap: 12px;
            align-items: flex-start;
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 14px;
            padding: 14px;
            margin-bottom: 8px;
            text-decoration: none;
            color: inherit;
            transition: background 0.15s;
        }
        .notif-item:active { background: #eef3fa; }

        .notif-icon {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            flex-shrink: 0;
        }
        .icon-comment { background: #eef5ff; }
        .icon-like    { background: #fff0f0; }
        .icon-point   { background: #f0fff8; }
        .icon-notice  { background: #fffbec; }

        .notif-body { flex: 1; min-width: 0; }
        .notif-title {
            font-size: 0.9rem;
            font-weight: 700;
            margin: 0 0 3px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .notif-desc {
            font-size: 0.82rem;
            color: var(--muted);
            margin: 0 0 4px;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }
        .notif-time {
            font-size: 0.75rem;
            color: #98aabf;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--muted);
        }
        .empty-state .emoji { font-size: 2.4rem; margin-bottom: 12px; }
        .empty-state p { margin: 0; font-size: 0.92rem; line-height: 1.6; }
    </style>
</head>
<body>
<div class="shell">
    <div class="app-header">
        <h1>알림</h1>
    </div>

    <div class="notif-list">
        @forelse($notifications as $notif)
            <a class="notif-item" href="{{ $notif->link }}">
                <div class="notif-icon icon-{{ $notif->type }}">
                    @if($notif->type === 'comment') 💬
                    @elseif($notif->type === 'like') ❤️
                    @elseif($notif->type === 'point') 🪙
                    @else 📢
                    @endif
                </div>
                <div class="notif-body">
                    <p class="notif-title">{{ $notif->title }}</p>
                    @if($notif->body)
                        <p class="notif-desc">{{ $notif->body }}</p>
                    @endif
                    <span class="notif-time">{{ $notif->created_at->diffForHumans() }}</span>
                </div>
            </a>
        @empty
            <div class="empty-state">
                <div class="emoji">🔔</div>
                <p>아직 알림이 없습니다.<br>게시글 작성, 댓글, 좋아요 활동이<br>여기에 표시됩니다.</p>
            </div>
        @endforelse
    </div>
</div>
</body>
</html>
