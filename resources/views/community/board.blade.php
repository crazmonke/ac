<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $board->name }}</title>
    <style>
        body { margin: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; background: #f4f8fb; color: #17263d; }
        .wrap { max-width: 1100px; margin: 0 auto; padding: 24px; }
        .panel { background: #fff; border: 1px solid #d5dfec; border-radius: 12px; padding: 14px; margin-bottom: 14px; }
        .meta { color: #5b6d82; font-size: 0.9rem; }
        .back-chip {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            border-radius: 999px;
            border: 1px solid #cfd8e6;
            background: #e9eef5;
            color: #22344d;
            padding: 8px 14px;
            font-size: 0.9rem;
            font-weight: 800;
            text-decoration: none;
            line-height: 1;
            transition: background-color 0.16s ease, border-color 0.16s ease;
        }
        .back-chip:hover { background: #dfe7f2; border-color: #c4d0e2; }
        .back-chip:focus-visible { outline: none; box-shadow: 0 0 0 3px rgba(47, 82, 184, 0.14); }
        .flash { margin-bottom: 10px; padding: 10px; border-radius: 8px; border: 1px solid #bee6d9; background: #e8f6f1; color: #166b53; }
        .err { margin-bottom: 10px; padding: 10px; border-radius: 8px; border: 1px solid #f4c8c8; background: #fdecec; color: #9e1d1d; }
        .grid { display: grid; gap: 8px; grid-template-columns: repeat(2, minmax(120px, 1fr)); }
        input, textarea, select { width: 100%; border: 1px solid #c7d8ea; border-radius: 8px; padding: 9px; }
        textarea { min-height: 90px; }
        button, .btn { border: 0; border-radius: 999px; background: #0f6f67; color: #fff; padding: 10px 14px; font-weight: 700; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; }
        .btn-secondary { background: #dde7f3; color: #20324b; }
        a { color: #0f6f67; text-decoration: none; font-weight: 700; }
        .list { margin-top: 12px; }
        .list-head { display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap; margin-bottom: 12px; }
        .item { background: #fff; border: 1px solid #d5dfec; border-radius: 10px; padding: 12px; margin-bottom: 8px; }
        .item h3 { margin: 0 0 6px; }
        .item-row { display: flex; align-items: flex-start; gap: 12px; }
        .item-main { flex: 1 1 auto; min-width: 0; }
        .item-thumb {
            flex: 0 0 94px;
            width: 94px;
            aspect-ratio: 4 / 3;
            border-radius: 10px;
            overflow: hidden;
            border: 1px solid #d7e2f1;
            background: #eef3f9;
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.35);
        }
        .item-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .item-title-link {
            color: #17263d;
            display: inline;
            text-decoration: none;
            font-weight: 800;
            line-height: 1.45;
        }
        .pill { display: inline-block; border: 1px solid #c9d8eb; border-radius: 999px; padding: 2px 8px; font-size: 12px; }
        .post-preview {
            margin-top: 10px;
            color: #24364e;
            line-height: 1.7;
            font-size: 0.94rem;
            overflow: hidden;
            display: -webkit-box;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 4;
        }
        .post-preview p,
        .post-preview ul,
        .post-preview ol,
        .post-preview blockquote,
        .post-preview h1,
        .post-preview h2,
        .post-preview h3,
        .post-preview h4,
        .post-preview pre {
            margin: 0 0 0.55em;
        }
        .post-preview img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
        }
        .post-preview a { color: inherit; text-decoration: underline; }
        .desktop-write-cta { display: inline-flex; }
        .mobile-bottom-nav { display: none; }

        @media (max-width: 768px) {
            .wrap { padding-bottom: calc(96px + env(safe-area-inset-bottom)); }
            .desktop-write-cta { display: none; }
            .item-row { gap: 10px; }
            .item-thumb { flex-basis: 86px; width: 86px; border-radius: 9px; }
            .mobile-bottom-nav {
                position: fixed;
                left: 0;
                right: 0;
                bottom: 0;
                z-index: 120;
                display: block;
                padding: 8px 12px calc(8px + env(safe-area-inset-bottom));
                background: linear-gradient(180deg, #eef4ff, #f7faff);
                border-top: 1px solid rgba(220, 243, 246, 0.42);
                backdrop-filter: blur(8px);
            }
            .mobile-bottom-nav-inner { max-width: 1100px; margin: 0 auto; display: flex; align-items: center; justify-content: flex-end; gap: 10px; min-height: 58px; }
            .mobile-nav-item { text-decoration: none; color: #02451b;  display: inline-flex; flex-direction: column; align-items: center; justify-content: center; min-width: 64px; padding: 2px 6px; font-weight: 700; }
            .mobile-nav-item-icon {
                width: 32px;
                height: 32px;
                border-radius: 10px;
                background: linear-gradient(145deg, #d9f7ee 0%, #aeead8 100%);
                color: #0f5f61;
                border: 1px solid rgba(217, 247, 238, 0.75);
                box-shadow: 0 6px 14px rgba(6, 45, 51, 0.22);
                display: inline-flex;
                align-items: center;
                justify-content: center;
                font-size: 1.35rem;
                line-height: 1;
                margin-bottom: 3px;
            }
            .mobile-nav-item-label { font-size: 0.78rem; line-height: 1.1; letter-spacing: -0.01em; }
        }
    </style>
</head>
<body>
<div class="wrap">
    @include('partials.site-nav', ['apartmentId' => $apartmentId])

    <h1>{{ $board->name }}</h1>
    <p class="meta"><a class="back-chip" href="/community?apartment_id={{ $apartmentId }}">← 커뮤니티로</a></p>

    @if(session('status'))
        <div class="flash">{{ session('status') }}</div>
    @endif

    @if($errors->any())
        <div class="err">{{ $errors->first() }}</div>
    @endif

    <section class="panel">
        <form method="get" action="/community/{{ $board->slug }}">
            <input type="hidden" name="apartment_id" value="{{ $apartmentId }}">
            <div class="grid">
                <div>
                    <input name="q" placeholder="검색어(제목/본문)" value="{{ $q }}">
                </div>
                <div>
                    <select name="sort">
                        <option value="latest" @selected($sort === 'latest')>최신순</option>
                        <option value="oldest" @selected($sort === 'oldest')>오래된순</option>
                        <option value="views" @selected($sort === 'views')>조회수순</option>
                        <option value="comments" @selected($sort === 'comments')>댓글순</option>
                    </select>
                </div>
                <div>
                    <select name="topic">
                        <option value="">태그 전체</option>
                        @foreach($topicOptions as $option)
                            <option value="{{ $option->slug }}" @selected($topic === $option->slug)>#{{ $option->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <button type="submit">검색/정렬 적용</button>
                </div>
            </div>
        </form>
    </section>

    <section class="list">
        <div class="list-head">
            <div>
                <strong>글 목록</strong>
                <div class="meta">{{ $board->name }} · 총 {{ $posts->total() }}개</div>
            </div>
            @if($canWrite)
                <a class="btn desktop-write-cta" href="/community/boards/{{ $board->slug }}/create?apartment_id={{ $apartmentId }}">새글작성</a>
            @endif
        </div>
        @forelse($posts as $post)
            @php
                $access = $postAccessMap[$post->id] ?? ['can_read' => true, 'access_label' => null, 'url' => '/community/posts/'.$post->id.'?apartment_id='.$apartmentId, 'thumbnail_url' => null];
                $thumbnailUrl = $access['thumbnail_url'] ?? null;
            @endphp
            <article class="item">
                <div class="item-row">
                    <div class="item-main">
                        <h3>
                            <a class="item-title-link" href="{{ $access['url'] }}">{{ $post->title }}</a>
                            @if($post->is_notice)
                                <span class="pill">공지</span>
                            @endif
                            @if(! $access['can_read'])
                                <span class="pill">{{ $access['access_label'] ?? '상세 제한' }}</span>
                            @elseif($post->is_guest_visible)
                                <span class="pill">비회원 공개</span>
                            @endif
                        </h3>
                        <div class="meta">
                            작성자: {{ $post->is_anonymous ? '익명' : ($post->user->name ?? '알 수 없음') }}
                            · 댓글 {{ $post->comment_count }}
                            · 조회 {{ $post->view_count }}
                            · {{ format_relative_time($post->created_at) }}
                        </div>
                        @if($post->topic)
                            <div class="meta" style="margin-top:6px;">태그: <span class="pill">#{{ $post->topic->name }}</span></div>
                        @endif
                        @if($access['can_read'])
                            <div class="post-preview">{!! $post->body !!}</div>
                        @else
                            <p class="meta">본문은 권한이 충족되면 열람할 수 있습니다.</p>
                        @endif
                    </div>
                    @if($thumbnailUrl)
                        <a class="item-thumb" href="{{ $access['url'] }}" aria-label="{{ $post->title }} 대표 이미지">
                            <img src="{{ $thumbnailUrl }}" alt="{{ $post->title }}">
                        </a>
                    @endif
                </div>
            </article>
        @empty
            <div class="item">게시글이 없습니다.</div>
        @endforelse
    </section>

    @include('partials.pagination', ['paginator' => $posts])
</div>

@if($canWrite)
    <nav class="mobile-bottom-nav" aria-label="모바일 하단 메뉴">
        <div class="mobile-bottom-nav-inner">
            <a class="mobile-nav-item" href="/community/boards/{{ $board->slug }}/create?apartment_id={{ $apartmentId }}" aria-label="글쓰기">
                <span class="mobile-nav-item-icon">+</span>
                <span class="mobile-nav-item-label">글쓰기</span>
            </a>
        </div>
    </nav>
@endif
</body>
</html>
