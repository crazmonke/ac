<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>쪽지함</title>
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
        .page-title { margin: 0; font-size: clamp(1.25rem, 2.6vw, 1.6rem); }
        .top-row { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 14px; }
        .btn { border: 0; border-radius: 10px; padding: 8px 14px; font-weight: 700; cursor: pointer; text-decoration: none; display: inline-block; font: inherit; }
        .btn-primary { background: var(--brand); color: #fff; }
        .tabs { display: flex; gap: 6px; margin-bottom: 14px; }
        .tab { padding: 6px 12px; border-radius: 999px; background: #edf1f7; color: #22344f; font-size: 0.88rem; text-decoration: none; font-weight: 700; }
        .tab.active { background: var(--brand); color: #fff; }
        .flash { background: #e7f6ec; border: 1px solid #b9e3c6; color: #1f7a3d; border-radius: 12px; padding: 10px 14px; margin-bottom: 12px; font-size: 0.9rem; }
        .msg-item { display: flex; gap: 12px; align-items: flex-start; background: var(--card); border: 1px solid var(--line); border-radius: 14px; padding: 14px; margin-bottom: 8px; text-decoration: none; color: inherit; }
        .msg-item:hover { background: #f2f7ff; }
        .avatar { width: 42px; height: 42px; border-radius: 12px; background: #e5edf9; color: #2e4fb8; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1.05rem; flex-shrink: 0; }
        .msg-body { flex: 1; min-width: 0; }
        .msg-top { display: flex; align-items: center; justify-content: space-between; gap: 8px; }
        .msg-name { font-weight: 800; font-size: 0.94rem; }
        .msg-time { font-size: 0.75rem; color: #98aabf; white-space: nowrap; }
        .msg-preview { font-size: 0.85rem; color: var(--muted); margin: 4px 0 0; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; }
        .unread-badge { min-width: 20px; height: 20px; border-radius: 999px; background: #e5484d; color: #fff; font-size: 0.72rem; font-weight: 800; display: inline-flex; align-items: center; justify-content: center; padding: 0 6px; flex-shrink: 0; }
        .direction { font-size: 0.72rem; font-weight: 700; padding: 2px 8px; border-radius: 999px; }
        .direction.received { background: #eef5ff; color: #1d3fa6; }
        .direction.sent { background: #f2f2f7; color: #5a5f6e; }
        .empty-state { text-align: center; padding: 60px 20px; color: var(--muted); }
        .empty-state .emoji { font-size: 2.4rem; margin-bottom: 12px; }
        .empty-state p { margin: 0; font-size: 0.92rem; line-height: 1.6; }
        .pagination-wrap { margin-top: 12px; }
    </style>
</head>
<body>
@include('partials.site-nav', ['apartmentId' => $apartmentId])

@php
    $avatarInitial = static function (?string $name): string {
        $value = trim((string) $name);
        return $value === '' ? 'U' : mb_strtoupper(mb_substr($value, 0, 1));
    };
@endphp

<div class="shell">
    <div class="top-row">
        <h1 class="page-title">쪽지함</h1>
        <a class="btn btn-primary" href="/messages/compose">쪽지 쓰기</a>
    </div>

    @if(session('status'))
        <div class="flash">{{ session('status') }}</div>
    @endif

    <div class="tabs">
        <a class="tab {{ $box === 'conversations' ? 'active' : '' }}" href="/messages">대화</a>
        <a class="tab {{ $box === 'received' ? 'active' : '' }}" href="/messages?box=received">받은 쪽지</a>
        <a class="tab {{ $box === 'sent' ? 'active' : '' }}" href="/messages?box=sent">보낸 쪽지</a>
    </div>

    @if($box === 'conversations')
        @forelse($conversations as $conversation)
            @php($peer = $conversation['peer'])
            @php($last = $conversation['last_message'])
            <a class="msg-item" href="/messages/{{ $peer->id }}">
                <div class="avatar">{{ $avatarInitial($peer->name) }}</div>
                <div class="msg-body">
                    <div class="msg-top">
                        <span class="msg-name">{{ $peer->name }}</span>
                        <span class="msg-time">{{ format_relative_time($last->created_at) }}</span>
                    </div>
                    <p class="msg-preview">{{ $last->sender_id === $user->id ? '나: ' : '' }}{{ \Illuminate\Support\Str::limit($last->content, 80) }}</p>
                </div>
                @if($conversation['unread_count'] > 0)
                    <span class="unread-badge">{{ $conversation['unread_count'] > 99 ? '99+' : $conversation['unread_count'] }}</span>
                @endif
            </a>
        @empty
            <div class="empty-state">
                <div class="emoji">💌</div>
                <p>아직 주고받은 쪽지가 없습니다.<br>게시글 작성자 이름을 눌러 쪽지를 보내보세요.</p>
            </div>
        @endforelse
    @else
        @forelse($messages as $message)
            @php($peer = $box === 'received' ? $message->sender : $message->receiver)
            <a class="msg-item" href="/messages/{{ $peer?->id ?? '' }}" style="{{ $box === 'received' && $message->read_at === null ? 'border-color:#b9ccf5; background:#f4f8ff;' : '' }}">
                <div class="avatar">{{ $avatarInitial($peer?->name) }}</div>
                <div class="msg-body">
                    <div class="msg-top">
                        <span class="msg-name">
                            {{ $peer?->name ?? '알 수 없음' }}
                            <span class="direction {{ $box }}">{{ $box === 'received' ? '받음' : '보냄' }}</span>
                        </span>
                        <span class="msg-time">{{ format_relative_time($message->created_at) }}</span>
                    </div>
                    <p class="msg-preview">{{ \Illuminate\Support\Str::limit($message->content, 80) }}</p>
                </div>
                @if($box === 'received' && $message->read_at === null)
                    <span class="unread-badge">N</span>
                @endif
            </a>
        @empty
            <div class="empty-state">
                <div class="emoji">💌</div>
                <p>{{ $box === 'received' ? '받은 쪽지가 없습니다.' : '보낸 쪽지가 없습니다.' }}</p>
            </div>
        @endforelse

        @if($messages)
            <div class="pagination-wrap">{{ $messages->links() }}</div>
        @endif
    @endif
</div>
</body>
</html>
