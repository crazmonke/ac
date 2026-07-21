<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Apaind - {{ $post->title }}</title>
    <style>
        :root {
            --bg: #f5f7fb;
            --card: #ffffff;
            --ink: #18283d;
            --muted: #607086;
            --line: #dde5ef;
            --brand: #2f52b8;
            --brand-soft: #ebf0ff;
            --danger: #b42318;
            --fixed-actions-height: calc(64px + env(safe-area-inset-bottom));
        }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; background: var(--bg); color: var(--ink); }
        .wrap { max-width: 740px; margin: 0 auto; padding: 12px 12px calc(var(--fixed-actions-height) + 16px); }
        .appbar {
            position: sticky;
            top: 0;
            z-index: 15;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding: 12px 4px 14px;
            background: linear-gradient(180deg, rgba(245,247,251,0.98), rgba(245,247,251,0.82));
            backdrop-filter: blur(8px);
        }
        .appbar .left,
        .appbar .right { display: flex; align-items: center; gap: 8px; }
        .appbar a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            color: var(--ink);
            background: rgba(255,255,255,0.9);
            border: 1px solid var(--line);
            border-radius: 999px;
            padding: 8px 11px;
            font-weight: 700;
            font-size: 0.92rem;
        }
        .appbar .back-chip {
            gap: 4px;
            background: #ffffff;
            border-color: #cfd8e6;
            color: #22344d;
            font-weight: 800;
            padding: 8px;
            line-height: 1;
        }
        .appbar .title { font-weight: 800; font-size: 0.98rem; }
        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 18px;
            padding: 14px;
            box-shadow: 0 10px 24px rgba(20, 35, 60, 0.04);
            margin-bottom: 12px;
        }
        .meta { color: var(--muted); font-size: 0.88rem; }
        .post-head { display: grid; gap: 12px; }
        .post-title { margin: 0; font-size: clamp(1.42rem, 4vw, 2rem); line-height: 1.28; }
        .author-row { display: flex; align-items: center; justify-content: space-between; gap: 10px; }
        .author { display: flex; align-items: center; gap: 10px; min-width: 0; }
        .avatar {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: linear-gradient(145deg, #2e4fb8, #0f6f67);
            border: 1px solid var(--line);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: #fff;
            flex: 0 0 auto;
        }
        .author-name { font-weight: 800; }
        .stats { display: flex; gap: 8px; flex-wrap: wrap; }
        .pill {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            border-radius: 999px;
            padding: 5px 9px;
            font-size: 0.8rem;
            background: var(--brand-soft);
            color: var(--brand);
            font-weight: 700;
        }
        .body {
            line-height: 1.75;
            font-size: 1rem;
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
        .body video {
            display: block;
            width: 100%;
            max-width: 100%;
            border-radius: 10px;
            background: #000;
        }
        .section-title {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            gap: 10px;
            margin: 0 0 10px;
        }
        .section-title h2 { margin: 0; font-size: 1.03rem; }
        .section-title .count { color: var(--muted); font-size: 0.88rem; }
        .flash { margin-bottom: 10px; padding: 10px; border-radius: 10px; border: 1px solid #bee6d9; background: #e8f6f1; color: #166b53; }
        .err { margin-bottom: 10px; padding: 10px; border-radius: 10px; border: 1px solid #f4c8c8; background: #fdecec; color: #9e1d1d; }
        input, textarea { width: 100%; border: 1px solid #c7d8ea; border-radius: 14px; padding: 12px; font: inherit; background: #fff; }
        textarea { min-height: 110px; }
        button, .btn {
            border: 0;
            border-radius: 999px;
            background: var(--brand);
            color: #fff;
            padding: 10px 14px;
            font-weight: 800;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .danger { background: var(--danger); }
        .ghost { background: #e9eef7; color: #23334b; }
        .actions { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 12px; }
        .post-actions { display: flex; gap: 8px; flex-wrap: wrap; }
        .comment-compose-trigger {
            width: 42px;
            height: 42px;
            min-width: 42px;
            padding: 0;
            border-radius: 12px;
        }
        .comment-compose-trigger svg {
            width: 19px;
            height: 19px;
            fill: none;
            stroke: currentColor;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }
        .icon-square-btn {
            width: 42px;
            height: 42px;
            min-width: 42px;
            border-radius: 12px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .icon-square-btn svg {
            width: 19px;
            height: 19px;
            fill: none;
            stroke: currentColor;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }
        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }
        .post-like-center {
            margin-top: 16px;
            display: flex;
            justify-content: center;
        }
        .like-toggle-form { display: inline-flex; }
        .like-toggle-btn {
            border: 1px solid #d7e1ee;
            background: #fff;
            color: #24364e;
            border-radius: 999px;
            padding: 8px 14px;
            display: inline-flex;
            align-items: center;
            gap: 7px;
            font-weight: 800;
            cursor: pointer;
        }
        .like-toggle-btn svg {
            width: 18px;
            height: 18px;
            stroke: currentColor;
            fill: none;
            stroke-width: 1.9;
            stroke-linecap: round;
            stroke-linejoin: round;
        }
        .like-toggle-btn.hearted {
            color: #d01e39;
            border-color: #efc0c8;
            background: #fff6f8;
        }
        .like-toggle-btn.hearted svg {
            fill: currentColor;
            stroke: currentColor;
        }
        .comment { display: grid; grid-template-columns: 22px 1fr; gap: 10px; padding: 14px 0; border-top: 1px solid #edf1f7; cursor: pointer; transition: background 0.2s ease; padding: 12px; margin: -12px; }
        .comment:hover { background: rgba(47, 82, 184, 0.04); border-radius: 10px; }
        .comment:first-child { border-top: 0; }
        .comment-body { min-width: 0; overflow: hidden; }
        .comment-head { display: flex; align-items: center; justify-content: space-between; gap: 8px; }
        .comment-name { font-weight: 600; }
        .comment-meta { color: var(--muted); font-size: 0.8rem; margin-top: 2px; }
        .comment-text {
            margin-top: 8px;
            line-height: 1.65;
            white-space: pre-wrap;
            overflow-wrap: anywhere;
            word-break: break-word;
        }
        .comment-tools { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 10px; }
        .comment-tools a, .comment-tools button {
            border-radius: 999px;
            padding: 7px 10px;
            font-size: 0.86rem;
        }
        .comment-tools a { text-decoration: none; }
        /* Threads 스타일 댓글 액션 버튼 */
        .comment-actions {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 10px;
        }
        .action-btn {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            background: none;
            border: 0;
            padding: 0;
            color: var(--muted);
            font-size: 0.88rem;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            border-radius: 0;
            line-height: 1;
        }
        .action-btn svg {
            width: 17px;
            height: 17px;
            fill: none;
            stroke: currentColor;
            stroke-width: 1.8;
            stroke-linecap: round;
            stroke-linejoin: round;
            flex-shrink: 0;
        }
        .action-btn.hearted { color: #d01e39; }
        .action-btn.hearted svg { fill: #d01e39; stroke: #d01e39; }
        .action-count { min-width: 12px; }
        .action-text { font-size: 0.8rem; color: var(--muted); }
        .danger-text { color: #b42318; }
        .best-box { background: #f7f9ff; border: 1px solid #dbe5ff; border-radius: 16px; padding: 12px; margin-bottom: 12px; }
        .best-label { display: inline-flex; align-items: center; gap: 6px; color: #2f52b8; font-weight: 800; font-size: 0.88rem; margin-bottom: 8px; }

        .reply-box {
            margin-top: 10px;
            padding: 10px;
            border-radius: 12px;
            border: 1px solid #e3eaf5;
            background: #f9fbff;
        }
        .reply-box textarea { min-height: 84px; }
        .attachment-list { list-style: none; margin: 0; padding: 0; display: grid; gap: 8px; }
        .attachment-list li {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding: 10px 12px;
            border: 1px solid #e5ebf4;
            border-radius: 14px;
            background: #fafcff;
        }
        .attachment-list a { color: var(--ink); text-decoration: none; font-weight: 700; }
        .fixed-actions {
            position: fixed;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 25;
            background: rgba(245,247,251,0.96);
            border-top: 1px solid var(--line);
            backdrop-filter: blur(10px);
        }
        .comment-compose-modal {
            position: fixed;
            inset: 0;
            z-index: 60;
            display: none;
            align-items: flex-end;
            justify-content: center;
            background: rgba(12, 18, 28, 0.52);
            padding: 12px;
        }
        .comment-compose-modal.open {
            display: flex;
        }
        .comment-compose-sheet {
            width: min(740px, 100%);
            max-height: min(72vh, 620px);
            overflow: auto;
            border-radius: 16px;
            border: 1px solid #d6e2f0;
            background: #fff;
            padding: 14px;
            box-shadow: 0 18px 40px rgba(18, 33, 56, 0.18);
        }
        .comment-compose-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 8px;
        }
        .comment-compose-title {
            margin: 0;
            font-size: 1rem;
            font-weight: 900;
        }
        .comment-compose-close {
            border: 1px solid #d0dcea;
            border-radius: 10px;
            background: #eef3f9;
            color: #22344d;
            min-height: 34px;
            padding: 6px 10px;
            font-weight: 800;
        }
        .composer {
            background: transparent;
        }
        .composer-inner {
            max-width: 740px;
            margin: 0 auto;
            padding: 10px 12px 8px;
        }
        .composer-bar {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 8px;
            align-items: end;
        }
        .composer-bar textarea { min-height: 58px; max-height: 120px; resize: none; }
        .composer-options {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-top: 8px;
            color: var(--muted);
            font-size: 0.9rem;
            font-weight: 700;
        }
        .composer-option {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .composer-option input {
            width: auto;
            margin: 0;
        }
        .bottom-bar {
            background: transparent;
            border-top: 1px solid var(--line);
        }
        .bottom-bar-inner {
            max-width: 740px;
            margin: 0 auto;
            padding: 10px 12px calc(10px + env(safe-area-inset-bottom));
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
        }
        .bottom-bar a, .bottom-bar button {
            border: 0;
            border-radius: 12px;
            width: 46px;
            height: 46px;
            padding: 0;
            font-weight: 800;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .bottom-bar-icon {
            width: 21px;
            height: 21px;
            fill: none;
            stroke: currentColor;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }
        .bottom-bar .primary { background: var(--brand); color: #fff; }
        .bottom-bar .ghost { background: #eef2f8; color: #24364e; }
        .bottom-bar .danger { background: var(--danger); color: #fff; }
        @media (min-width: 900px) {
            .wrap { max-width: 860px; padding-top: 18px; }
            .composer-inner { max-width: 860px; }
            .bottom-bar-inner { max-width: 860px; }
        }
    </style>
</head>
<body>
@php
    $avatarInitial = static function (?string $name): string {
        $value = trim((string) $name);
        if ($value === '') {
            return 'U';
        }

        return mb_strtoupper(mb_substr($value, 0, 1));
    };
@endphp
@php($communityScope = in_array($post->audience_scope, ['region', 'apartment'], true) ? $post->audience_scope : 'region')

<div class="wrap">
    @include('partials.site-nav', ['apartmentId' => $apartmentId])

    <div class="appbar">
        <div class="left">
            <a class="back-chip" href="#" onclick="navigateBack(event);" data-apartment-id="{{ $apartmentId }}" data-board-slug="{{ $post->board->slug }}" data-scope="{{ $communityScope }}">
                <svg viewBox="0 0 25 25" width="20" height="20" xmlns="http://www.w3.org/2000/svg">
                    <image href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAAABHNCSVQICAgIfAhkiAAAAAlwSFlzAAAOxAAADsQBlSsOGwAAAFxJREFUWIVjYBjGgG8gLY9kYGB4zcDAYDJQlv+G4tCBtDxi1PJRy0ctH7V81PIhYTkTier/U9sBxALkUAgfdcSoI0YdMeqIUUcMJUfQvWOC7IgB65rBwIB2TokGAJkqRkxiPMupAAAAAElFTkSuQmCC" width="25" height="25" x="0" y="0" />
                </svg>
            </a>
            <div class="title">{{ $post->board->name }}</div>
        </div>
        <div class="right">
            <a href="/?apartment_id={{ $apartmentId }}">홈</a>
        </div>
    </div>

    @if(session('status'))
        <div class="flash">{{ session('status') }}</div>
    @endif

    @if($errors->any())
        <div class="err">{{ $errors->first() }}</div>
    @endif

    <section class="card">
        <div class="post-head">
            <div class="meta">{{ $post->audience_scope === 'apartment' ? ($post->apartment->name.' · ') : '동네전용 · ' }}{{ $post->board->name }}</div>
            <h1 class="post-title">{{ $post->title }}</h1>
            <div class="author-row">
                <div class="author">
                    @php($postAuthorName = $post->is_anonymous ? '익명' : ($post->user->name ?? '알 수 없음'))
                    <div class="avatar">{{ $avatarInitial($postAuthorName) }}</div>
                    <div>
                        <div class="author-name">{{ $postAuthorName }}</div>
                        <div class="meta">{{ format_relative_time($post->created_at) }}</div>
                    </div>
                </div>
                <div class="stats">
                    <span class="pill">조회 {{ $post->view_count }}</span>
                    <span class="pill">댓글 {{ $totalCommentCount }}</span>
                </div>
            </div>
        </div>

        <div style="margin-top:16px;" class="body">{!! $post->body !!}</div>

        <div class="post-like-center">
            <form method="post" action="/community/posts/{{ $post->id }}/likes" class="like-toggle-form" data-like-form data-liked="{{ $likedByMe ? '1' : '0' }}">
                @csrf
                @if($likedByMe)
                    @method('DELETE')
                @endif
                <button class="like-toggle-btn {{ $likedByMe ? 'hearted' : '' }}" type="submit" aria-label="좋아요">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.84 4.61a4.98 4.98 0 0 0-7.05 0L12 6.4l-1.79-1.79a4.98 4.98 0 0 0-7.05 7.05L12 20.5l8.84-8.84a4.98 4.98 0 0 0 0-7.05Z"/></svg>
                    <span data-like-count>{{ $likeCount }}</span>
                </button>
            </form>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            <button class="ghost" type="button" id="shareButton" aria-label="공유">
                <svg class="bottom-bar-icon" viewBox="0 0 24 24" aria-hidden="true"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="M8.6 13.5 15.4 17.5"/><path d="M15.4 6.5 8.6 10.5"/></svg>
                <span class="sr-only">공유</span>
            </button>
        </div>

        <?php if ($post->board->board_type === 'poll' && $post->poll): ?>
            <?php
                $votedOptionIds = $userVoteOptionIds ?? collect();
                $hasVoted = $votedOptionIds->isNotEmpty();
                $totalVotes = max(1, (int) ($pollTotalVotes ?? 0));
                $pollOptions = $post->poll->options->sortBy('sort_order');
            ?>
            <section class="card" style="margin-top:16px; background:#f8fbff; border-color:#d8e6ff;">
                <h2 style="margin:0 0 10px; font-size:1.02rem;">투표</h2>
                <div class="meta" style="margin-bottom:10px; font-weight:700; color:#1f3c7a;"><?php echo e($post->poll->question); ?></div>

                <?php
                    $pollIsClosed = $post->poll->closes_at && now()->greaterThanOrEqualTo($post->poll->closes_at);
                ?>

                <?php if ($hasVoted || $pollIsClosed): ?>
                    <div style="display:grid; gap:10px;">
                        <?php foreach ($pollOptions as $option): ?>
                            <?php
                                $count = (int) $option->vote_count;
                                $percent = round(($count / $totalVotes) * 100);
                            ?>
                            <div style="border:1px solid #d8e6ff; border-radius:12px; padding:10px; background:#fff;">
                                <div style="display:flex; justify-content:space-between; gap:8px; align-items:center; font-weight:700;">
                                    <span><?php echo e($option->label); ?></span>
                                    <span class="meta"><?php echo e($count); ?>표 · <?php echo e($percent); ?>%</span>
                                </div>
                                <div style="margin-top:8px; height:8px; background:#e8eefb; border-radius:999px; overflow:hidden;">
                                    <div style="width:<?php echo e($percent); ?>%; height:100%; background:#0f6f67;"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <?php if ($post->poll->results_public): ?>
                        <div class="meta" style="margin-bottom:10px;">투표 후 결과를 바로 확인할 수 있습니다.</div>
                    <?php endif; ?>
                    <form method="post" action="/community/posts/<?php echo e($post->id); ?>/poll-votes?apartment_id=<?php echo e($apartmentId); ?>" style="display:grid; gap:8px;">
                        <?php echo csrf_field(); ?>
                        <?php foreach ($pollOptions as $option): ?>
                            <label style="display:flex; gap:8px; align-items:center; border:1px solid #d8e6ff; border-radius:12px; padding:10px; background:#fff;">
                                <input type="<?php echo e($post->poll->allow_multiple ? 'checkbox' : 'radio'); ?>" name="poll_option_ids[]" value="<?php echo e($option->id); ?>" style="width:auto;">
                                <span><?php echo e($option->label); ?></span>
                            </label>
                        <?php endforeach; ?>
                        <button type="submit" class="btn" style="margin-top:4px;">투표하기</button>
                    </form>
                <?php endif; ?>

                <?php if (! $hasVoted && ! $pollIsClosed && $post->poll->results_public): ?>
                    <div style="display:grid; gap:10px; margin-top:14px;">
                        <?php foreach ($pollOptions as $option): ?>
                            <?php
                                $count = (int) $option->vote_count;
                                $percent = round(($count / $totalVotes) * 100);
                            ?>
                            <div style="border:1px solid #d8e6ff; border-radius:12px; padding:10px; background:#fff; opacity:0.75;">
                                <div style="display:flex; justify-content:space-between; gap:8px; align-items:center; font-weight:700;">
                                    <span><?php echo e($option->label); ?></span>
                                    <span class="meta"><?php echo e($count); ?>표 · <?php echo e($percent); ?>%</span>
                                </div>
                                <div style="margin-top:8px; height:8px; background:#e8eefb; border-radius:999px; overflow:hidden;">
                                    <div style="width:<?php echo e($percent); ?>%; height:100%; background:#0f6f67;"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        <?php endif; ?>

        <div class="actions">
            @if($canComment)
                <button type="button" class="ghost comment-compose-trigger" id="commentComposeOpen" aria-label="댓글등록">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4Z"/></svg>
                    <span class="sr-only">댓글등록</span>
                </button>
            @endif
            @if($canWrite && ($currentUserId === $post->user_id || $isApartmentAdmin))
                <a class="btn icon-square-btn" href="/community/posts/{{ $post->id }}/edit" aria-label="수정">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                    <span class="sr-only">수정</span>
                </a>
                <form method="post" action="/community/posts/{{ $post->id }}" data-delete-form data-delete-type="post" style="display:inline; margin:0;">
                    @csrf @method('DELETE')
                    <button class="danger icon-square-btn" type="submit" aria-label="삭제" onclick="return confirm('정말 삭제할까요?')">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>
                        <span class="sr-only">삭제</span>
                    </button>
                </form>
            @endif
        </div>
    </section>

    <section class="card">
        <div class="section-title">
            <h2>댓글</h2>
            <div class="count">총 {{ $totalCommentCount }}개 · 댓글 {{ $rootCommentCount }}개 · 답글 {{ $replyCount }}개</div>
        </div>

        @if(count($bestCommentIds))
            <div class="best-box">
                @foreach($post->comments->whereIn('id', $bestCommentIds) as $bestComment)
                    <article class="comment" style="padding-top: 24px; cursor: pointer;" onclick="navigateToCommentDetail(event, {{ $post->id }}, {{ $bestComment->id }}, '{{ $apartmentId }}');">
                        @php($bestCommentAuthorName = $bestComment->is_anonymous ? '익명' : ($bestComment->user->name ?? '알 수 없음'))
                        <div class="avatar">{{ $avatarInitial($bestCommentAuthorName) }}</div>
                        <div class="comment-body">
                            <div class="comment-head">
                                <div class="comment-name">{{ $bestCommentAuthorName }}</div>
                                <div class="meta">{{ format_relative_time($bestComment->created_at) }}</div>
                            </div>
                            <div class="comment-text">{{ $bestComment->body }}</div>

                            <div class="comment-actions" onclick="event.stopPropagation();">
                                @php($bCommentLiked = isset($myCommentLikes[$bestComment->id]))
                                @php($bCommentLikeCount = (int)($commentLikeCounts[$bestComment->id] ?? 0))
                                <form method="post" action="/community/comments/{{ $bestComment->id }}/likes"
                                      class="c-like-form" data-like-form-comment
                                      data-liked="{{ $bCommentLiked ? '1' : '0' }}">
                                    @csrf
                                    @if($bCommentLiked) @method('DELETE') @endif
                                    <button type="submit" class="action-btn {{ $bCommentLiked ? 'hearted' : '' }}" aria-label="좋아요">
                                        <svg viewBox="0 0 24 24"><path d="M20.84 4.61a4.98 4.98 0 0 0-7.05 0L12 6.4l-1.79-1.79a4.98 4.98 0 0 0-7.05 7.05L12 20.5l8.84-8.84a4.98 4.98 0 0 0 0-7.05Z"/></svg>
                                        <span class="action-count" data-like-count>{{ $bCommentLikeCount ?: '' }}</span>
                                    </button>
                                </form>
                                @if($canComment)
                                    <a href="/community/posts/{{ $post->id }}/comments/{{ $bestComment->id }}?apartment_id={{ $apartmentId }}"
                                       class="action-btn" aria-label="답글쓰기">
                                        <svg viewBox="0 0 24 24"><path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4Z"/></svg>
                                        <span class="action-count">{{ $bestComment->children->count() ?: '' }}</span>
                                    </a>
                                @endif
                                @if($canComment && ($currentUserId === $bestComment->user_id || $isApartmentAdmin))
                                    <a href="/community/comments/{{ $bestComment->id }}/edit" class="action-btn action-text">
                                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                                        <span class="sr-only">수정</span>
                                    </a>
                                    <form method="post" action="/community/comments/{{ $bestComment->id }}" data-delete-form data-delete-type="comment" style="display:inline; margin:0;">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="action-btn action-text danger-text" onclick="return confirm('댓글을 삭제할까요?')">
                                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>
                                            <span class="sr-only">삭제</span>
                                        </button>
                                    </form>
                                @endif
                            </div>

                            @if($bestComment->children->count())
                                @php($bestChildren = $bestComment->children)
                                @php($bestVisibleChildren = $bestChildren->take(2))
                                @php($bestHasMore = $bestChildren->count() > 2)
                                <div class="children" style="margin-top:24px;">
                                    @foreach($bestVisibleChildren as $child)
                                        <article class="comment" style="grid-template-columns: 22px 1fr; padding-top:24px;">
                                            @php($bestChildAuthorName = $child->is_anonymous ? '익명' : ($child->user->name ?? '알 수 없음'))
                                            <div class="avatar" style="width:20px; height:20px;">{{ $avatarInitial($bestChildAuthorName) }}</div>
                                            <div class="comment-body">
                                                <div class="comment-head">
                                                    <div class="comment-name">{{ $bestChildAuthorName }}</div>
                                                    <div class="meta">{{ format_relative_time($child->created_at) }}</div>
                                                </div>
                                                <div class="comment-text">{{ $child->body }}</div>
                                                <div class="comment-actions">
                                                    @php($bChildLiked = isset($myCommentLikes[$child->id]))
                                                    @php($bChildLikeCount = (int)($commentLikeCounts[$child->id] ?? 0))
                                                    <form method="post" action="/community/comments/{{ $child->id }}/likes"
                                                          class="c-like-form" data-like-form-comment
                                                          data-liked="{{ $bChildLiked ? '1' : '0' }}">
                                                        @csrf
                                                        @if($bChildLiked) @method('DELETE') @endif
                                                        <button type="submit" class="action-btn {{ $bChildLiked ? 'hearted' : '' }}" aria-label="좋아요">
                                                            <svg viewBox="0 0 24 24"><path d="M20.84 4.61a4.98 4.98 0 0 0-7.05 0L12 6.4l-1.79-1.79a4.98 4.98 0 0 0-7.05 7.05L12 20.5l8.84-8.84a4.98 4.98 0 0 0 0-7.05Z"/></svg>
                                                            <span class="action-count" data-like-count>{{ $bChildLikeCount ?: '' }}</span>
                                                        </button>
                                                    </form>
                                                    @if($canComment && ($currentUserId === $child->user_id || $isApartmentAdmin))
                                                        <a href="/community/comments/{{ $child->id }}/edit" class="action-btn action-text">
                                                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                                                            <span class="sr-only">수정</span>
                                                        </a>
                                                        <form method="post" action="/community/comments/{{ $child->id }}" data-delete-form data-delete-type="comment" style="display:inline; margin:0;">
                                                            @csrf @method('DELETE')
                                                            <button type="submit" class="action-btn action-text danger-text" onclick="return confirm('답글을 삭제할까요?')">
                                                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>
                                                                <span class="sr-only">삭제</span>
                                                            </button>
                                                        </form>
                                                    @endif
                                                </div>
                                            </div>
                                        </article>
                                    @endforeach
                                    @if($bestHasMore)
                                        <a href="/community/posts/{{ $post->id }}/comments/{{ $bestComment->id }}?apartment_id={{ $apartmentId }}"
                                           onclick="event.stopPropagation();"
                                           style="display:inline-flex; align-items:center; gap:4px; margin-top:8px; color:var(--brand); font-size:0.88rem; font-weight:700; text-decoration:none;">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M7 13l5 5 5-5"/><path d="M7 7l5 5 5-5"/></svg>
                                            답글 {{ $bestChildren->count() - 2 }}개 더보기
                                        </a>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        @endif

        @forelse($post->comments as $comment)
            @if(in_array($comment->id, $bestCommentIds, true))
                @continue
            @endif
            <article class="comment" onclick="navigateToCommentDetail(event, {{ $post->id }}, {{ $comment->id }}, '{{ $apartmentId }}');">
                @php($commentAuthorName = $comment->is_anonymous ? '익명' : ($comment->user->name ?? '알 수 없음'))
                <div class="avatar">{{ $avatarInitial($commentAuthorName) }}</div>
                <div class="comment-body">
                    <div class="comment-head">
                        <div class="comment-name">{{ $commentAuthorName }}</div>
                        <div class="meta">{{ format_relative_time($comment->created_at) }}</div>
                    </div>
                    <div class="comment-text">{{ $comment->body }}</div>

                    <div class="comment-actions" onclick="event.stopPropagation();">
                        {{-- 좋아요 --}}
                        @php($commentLiked = isset($myCommentLikes[$comment->id]))
                        @php($commentLikeCount = (int)($commentLikeCounts[$comment->id] ?? 0))
                        <form method="post" action="/community/comments/{{ $comment->id }}/likes"
                              class="c-like-form" data-like-form-comment
                              data-liked="{{ $commentLiked ? '1' : '0' }}">
                            @csrf
                            @if($commentLiked) @method('DELETE') @endif
                            <button type="submit" class="action-btn {{ $commentLiked ? 'hearted' : '' }}" aria-label="좋아요">
                                <svg viewBox="0 0 24 24"><path d="M20.84 4.61a4.98 4.98 0 0 0-7.05 0L12 6.4l-1.79-1.79a4.98 4.98 0 0 0-7.05 7.05L12 20.5l8.84-8.84a4.98 4.98 0 0 0 0-7.05Z"/></svg>
                                <span class="action-count" data-like-count>{{ $commentLikeCount ?: '' }}</span>
                            </button>
                        </form>
                        {{-- 답글 말풍선 --}}
                        @if($canComment)
                            <a href="/community/posts/{{ $post->id }}/comments/{{ $comment->id }}?apartment_id={{ $apartmentId }}"
                               class="action-btn" aria-label="답글쓰기">
                                <svg viewBox="0 0 24 24"><path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4Z"/></svg>
                                <span class="action-count">{{ $comment->children->count() ?: '' }}</span>
                            </a>
                        @endif
                        {{-- 수정/삭제 --}}
                        @if($canComment && ($currentUserId === $comment->user_id || $isApartmentAdmin))
                            <a href="/community/comments/{{ $comment->id }}/edit" class="action-btn action-text">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                                <span class="sr-only">수정</span>
                            </a>
                            <form method="post" action="/community/comments/{{ $comment->id }}" data-delete-form data-delete-type="comment" style="display:inline; margin:0;">
                                @csrf @method('DELETE')
                                <button type="submit" class="action-btn action-text danger-text" onclick="return confirm('댓글을 삭제할까요?')">
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>
                                    <span class="sr-only">삭제</span>
                                </button>
                            </form>
                        @endif
                    </div>

                    @if($comment->children->count())
                        @php($childrenList = $comment->children)
                        @php($visibleChildren = $childrenList->take(2))
                        @php($hasMore = $childrenList->count() > 2)
                        <div class="children">
                            @foreach($visibleChildren as $child)
                                <article class="comment" style="grid-template-columns: 32px 1fr;">
                                    @php($childAuthorName = $child->is_anonymous ? '익명' : ($child->user->name ?? '알 수 없음'))
                                    <div class="avatar" style="width:20px; height:20px;">{{ $avatarInitial($childAuthorName) }}</div>
                                    <div class="comment-body">
                                        <div class="comment-head">
                                            <div class="comment-name">{{ $childAuthorName }}</div>
                                            <div class="meta">{{ format_relative_time($child->created_at) }}</div>
                                        </div>
                                        <div class="comment-text">{{ $child->body }}</div>
                                        <div class="comment-actions" onclick="event.stopPropagation();">
                                            @php($childLiked = isset($myCommentLikes[$child->id]))
                                            @php($childLikeCount = (int)($commentLikeCounts[$child->id] ?? 0))
                                            <form method="post" action="/community/comments/{{ $child->id }}/likes"
                                                  class="c-like-form" data-like-form-comment
                                                  data-liked="{{ $childLiked ? '1' : '0' }}">
                                                @csrf
                                                @if($childLiked) @method('DELETE') @endif
                                                <button type="submit" class="action-btn {{ $childLiked ? 'hearted' : '' }}" aria-label="좋아요">
                                                    <svg viewBox="0 0 24 24"><path d="M20.84 4.61a4.98 4.98 0 0 0-7.05 0L12 6.4l-1.79-1.79a4.98 4.98 0 0 0-7.05 7.05L12 20.5l8.84-8.84a4.98 4.98 0 0 0 0-7.05Z"/></svg>
                                                    <span class="action-count" data-like-count>{{ $childLikeCount ?: '' }}</span>
                                                </button>
                                            </form>
                                            @if($canComment && ($currentUserId === $child->user_id || $isApartmentAdmin))
                                                <a href="/community/comments/{{ $child->id }}/edit" onclick="event.stopPropagation();" class="action-btn action-text">
                                                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                                                    <span class="sr-only">수정</span>
                                                </a>
                                                    <form method="post" action="/community/comments/{{ $child->id }}" data-delete-form data-delete-type="comment" style="display:inline; margin:0;">
                                                        @csrf @method('DELETE')
                                                        <button type="submit" class="action-btn action-text danger-text" onclick="event.stopPropagation(); return confirm('답글을 삭제할까요?')">
                                                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>
                                                            <span class="sr-only">삭제</span>
                                                        </button>
                                                    </form>
                                            @endif
                                        </div>
                                    </div>
                                </article>
                            @endforeach
                            @if($hasMore)
                                <a href="/community/posts/{{ $post->id }}/comments/{{ $comment->id }}?apartment_id={{ $apartmentId }}" onclick="event.stopPropagation();" style="display:inline-flex; align-items:center; gap:4px; margin-top:8px; color:var(--brand); font-size:0.88rem; font-weight:700; text-decoration:none;">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M7 13l5 5 5-5"/><path d="M7 7l5 5 5-5"/></svg>
                                    답글 {{ $childrenList->count() - 2 }}개 더보기
                                </a>
                            @endif
                        </div>
                    @endif
                </div>
            </article>
        @empty
            <div class="meta">아직 댓글이 없습니다.</div>
        @endforelse
    </section>
</div>

@if($canComment)
    <div class="comment-compose-modal" id="commentComposeModal" aria-hidden="true">
        <div class="comment-compose-sheet" role="dialog" aria-modal="true" aria-label="댓글 등록">
            <div class="comment-compose-head">
                <h2 class="comment-compose-title">댓글 등록</h2>
                <button type="button" class="comment-compose-close" id="commentComposeClose">닫기</button>
            </div>
            <form method="post" action="/community/posts/{{ $post->id }}/comments" id="commentComposeForm">
                @csrf
                <textarea name="body" placeholder="댓글을 남겨보세요" required></textarea>
                <div class="composer-options">
                    <label class="composer-option">
                        <input type="checkbox" name="is_anonymous" value="1"> 익명
                    </label>
                </div>
                <div class="actions" style="margin-top:10px;">
                    <button type="submit">등록</button>
                </div>
            </form>
        </div>
    </div>
@endif
<!--
<div class="fixed-actions">
    <div class="bottom-bar">
        <div class="bottom-bar-inner">
            <a class="ghost" href="/community?scope={{ $communityScope }}&apartment_id={{ $apartmentId }}" aria-label="목록">
                <svg class="bottom-bar-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M8 6h13"/><path d="M8 12h13"/><path d="M8 18h13"/><path d="M3 6h.01"/><path d="M3 12h.01"/><path d="M3 18h.01"/></svg>
                <span class="sr-only">목록</span>
            </a>
            <button class="ghost" type="button" id="shareButton" aria-label="공유">
                <svg class="bottom-bar-icon" viewBox="0 0 24 24" aria-hidden="true"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="M8.6 13.5 15.4 17.5"/><path d="M15.4 6.5 8.6 10.5"/></svg>
                <span class="sr-only">공유</span>
            </button>
        </div>
    </div>
</div>
-->
<script>
(() => {
    document.addEventListener('submit', async (event) => {
        const form = event.target.closest('form[data-like-form]');
        if (!form) {
            return;
        }

        event.preventDefault();

        if (form.dataset.loading === '1') {
            return;
        }

        form.dataset.loading = '1';
        const button = form.querySelector('button[type="submit"]');
        const methodInput = form.querySelector('input[name="_method"]');
        const prevLiked = form.dataset.liked === '1';

        if (button) {
            button.disabled = true;
        }

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                throw new Error('request failed');
            }

            const payload = await response.json();
            const liked = Boolean(payload.liked);
            const likeCount = Number(payload.like_count ?? 0);

            form.dataset.liked = liked ? '1' : '0';

            if (button) {
                button.classList.toggle('hearted', liked);
                const countNode = button.querySelector('[data-like-count]');
                if (countNode) {
                    countNode.textContent = String(likeCount);
                }
            }

            if (liked && !methodInput) {
                const hiddenMethod = document.createElement('input');
                hiddenMethod.type = 'hidden';
                hiddenMethod.name = '_method';
                hiddenMethod.value = 'delete';
                form.appendChild(hiddenMethod);
            }

            if (!liked && methodInput) {
                methodInput.remove();
            }
        } catch (error) {
            form.dataset.liked = prevLiked ? '1' : '0';
            window.alert('좋아요 처리에 실패했습니다. 잠시 후 다시 시도해 주세요.');
        } finally {
            if (button) {
                button.disabled = false;
            }
            form.dataset.loading = '0';
        }
    });

    const shareButton = document.getElementById('shareButton');
    if (!shareButton) return;

    const shareUrl = window.location.href;
    const shareText = @json($post->title);
    const defaultLabel = shareButton.textContent;
    let labelTimer = null;

    const setTemporaryLabel = (label) => {
        window.clearTimeout(labelTimer);
        shareButton.textContent = label;
        labelTimer = window.setTimeout(() => {
            shareButton.textContent = defaultLabel;
        }, 1400);
    };

    const copyWithSelectionFallback = (text) => {
        const textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.setAttribute('readonly', '');
        textarea.style.position = 'fixed';
        textarea.style.top = '-999px';
        textarea.style.left = '-999px';
        document.body.appendChild(textarea);
        textarea.focus();
        textarea.select();

        try {
            return document.execCommand('copy');
        } catch (error) {
            return false;
        } finally {
            textarea.remove();
        }
    };

    const copyShareUrl = async () => {
        if (navigator.clipboard && window.isSecureContext && document.hasFocus()) {
            try {
                await navigator.clipboard.writeText(shareUrl);
                return true;
            } catch (error) {
                // Fall through to the selection-based copy below.
            }
        }

        return copyWithSelectionFallback(shareUrl);
    };

    shareButton.addEventListener('click', async () => {
        shareButton.disabled = true;

        try {
            if (navigator.share && document.hasFocus()) {
                try {
                    await navigator.share({ title: shareText, text: shareText, url: shareUrl });
                    return;
                } catch (error) {
                    if (error && error.name === 'AbortError') {
                        return;
                    }
                }
            }

            const copied = await copyShareUrl();
            setTemporaryLabel(copied ? '링크 복사됨' : '다시 시도');
        } finally {
            shareButton.disabled = false;
        }
    });

    const commentComposeOpen = document.getElementById('commentComposeOpen');
    const commentComposeClose = document.getElementById('commentComposeClose');
    const commentComposeModal = document.getElementById('commentComposeModal');

    if (commentComposeOpen && commentComposeModal) {
        commentComposeOpen.addEventListener('click', () => {
            commentComposeModal.classList.add('open');
            commentComposeModal.setAttribute('aria-hidden', 'false');
            const input = commentComposeModal.querySelector('textarea[name="body"]');
            if (input) {
                input.focus();
            }
        });
    }

    if (commentComposeClose && commentComposeModal) {
        commentComposeClose.addEventListener('click', () => {
            commentComposeModal.classList.remove('open');
            commentComposeModal.setAttribute('aria-hidden', 'true');
        });
    }

    if (commentComposeModal) {
        commentComposeModal.addEventListener('click', (event) => {
            if (event.target === commentComposeModal) {
                commentComposeModal.classList.remove('open');
                commentComposeModal.setAttribute('aria-hidden', 'true');
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && commentComposeModal.classList.contains('open')) {
                commentComposeModal.classList.remove('open');
                commentComposeModal.setAttribute('aria-hidden', 'true');
            }
        });
    }

    // 댓글 좋아요 AJAX 처리
    document.addEventListener('submit', async (event) => {
        const form = event.target.closest('form[data-like-form-comment]');
        if (!form) return;
        event.preventDefault();
        if (form.dataset.loading === '1') return;
        form.dataset.loading = '1';

        const button = form.querySelector('button[type="submit"]');
        const methodInput = form.querySelector('input[name="_method"]');
        const prevLiked = form.dataset.liked === '1';
        if (button) button.disabled = true;

        try {
            const res = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                credentials: 'same-origin',
            });
            if (!res.ok) throw new Error();
            const data = await res.json();
            const liked = Boolean(data.liked);
            const count = Number(data.like_count ?? 0);
            form.dataset.liked = liked ? '1' : '0';
            if (button) {
                button.classList.toggle('hearted', liked);
                const span = button.querySelector('[data-like-count]');
                if (span) span.textContent = count || '';
            }
            if (liked && !methodInput) {
                const h = document.createElement('input');
                h.type = 'hidden'; h.name = '_method'; h.value = 'delete';
                form.appendChild(h);
            }
            if (!liked && methodInput) methodInput.remove();
        } catch {
            form.dataset.liked = prevLiked ? '1' : '0';
            alert('좋아요 처리에 실패했습니다.');
        } finally {
            if (button) button.disabled = false;
            form.dataset.loading = '0';
        }
    });

    // 삭제 폼 처리 (웹뷰 환경 지원)
    document.addEventListener('submit', async (event) => {
        const deleteForm = event.target.closest('form[data-delete-form]');
        if (deleteForm) {
            event.preventDefault();
            try {
                const res = await fetch(deleteForm.action, {
                    method: 'POST',
                    body: new FormData(deleteForm),
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin'
                });
                if (!res.ok) throw new Error(`실패 (${res.status})`);
                
                // 삭제 타입에 따라 다른 동작
                const deleteType = deleteForm.dataset.deleteType;
                if (deleteType === 'post') {
                    // 게시물 삭제 → 목록으로 이동 (scope, board 유지)
                    const urlParams = new URLSearchParams(window.location.search);
                    const apartmentId = urlParams.get('apartment_id');
                    let redirectUrl = '/community?apartment_id=' + apartmentId;
                    if (urlParams.get('scope')) redirectUrl += '&scope=' + urlParams.get('scope');
                    if (urlParams.get('board')) redirectUrl += '&board=' + urlParams.get('board');
                    window.location.href = redirectUrl;
                } else {
                    // 댓글/답글 삭제 → 현재 페이지 리로드
                    window.location.reload();
                }
            } catch (err) {
                alert('삭제 중 오류: ' + err.message);
            }
            return false;
        }
    });

    // 댓글 상세 페이지로 이동
    window.navigateToCommentDetail = function(event, postId, commentId, apartmentId) {
        // 클릭한 요소가 링크나 버튼이 아닐 때만 이동
        const target = event.target;
        if (target.tagName === 'A' || target.tagName === 'BUTTON' || target.closest('a') || target.closest('button') || target.closest('details')) {
            return;
        }
        
        window.location.href = `/community/posts/${postId}/comments/${commentId}?apartment_id=${apartmentId}`;
    };

    // 뒤로가기 버튼 처리
    window.navigateBack = function(event) {
        event.preventDefault();
        
        const referrer = document.referrer;
        const backChip = event.target.closest('.back-chip');
        const apartmentId = backChip?.getAttribute('data-apartment-id');
        const boardSlug = backChip?.getAttribute('data-board-slug');
        const scope = backChip?.getAttribute('data-scope');
        // 직전 페이지가 게시판 리스트 페이지인지 확인
        // /community?... 형태의 URL이면 리스트 페이지
        if (referrer && /\/community\?/.test(referrer)) {
            // 리스트에서 온 경우: 이전 페이지로 돌아가기
            history.back();
        } else {
            // 작성/수정/기타 페이지에서 온 경우: 게시판 리스트로 이동 (scope, board 유지)
            if (apartmentId && boardSlug && scope) {
                window.location.href = `/community?scope=${scope}&apartment_id=${apartmentId}&board=${boardSlug}`;
            } else if (apartmentId) {
                window.location.href = `/community?apartment_id=${apartmentId}`;
            } else {
                
                history.back();
            }
        }
    };
})();
</script>
</body>
</html>
