<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>검색: {{ $searchQuery }}</title>
    <style>
        :root {
            --bg: #f5f7fb;
            --card: #ffffff;
            --ink: #18283d;
            --muted: #607086;
            --line: #dde5ef;
            --brand: #2f52b8;
            --brand-soft: #ebf0ff;
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
            padding-top: 50px;
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
        .post-item {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 12px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.16s ease;
        }
        .post-item:hover { background: var(--brand-soft); border-color: var(--brand); }
        .post-item a { text-decoration: none; color: var(--ink); }
        .author-row { display: flex; align-items: center; gap: 8px; margin-bottom: 8px; }
        .avatar {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: linear-gradient(145deg, #2e4fb8, #0f6f67);
            border: 1px solid var(--line);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: #fff;
            font-size: 0.8rem;
            flex: 0 0 auto;
        }
        .author-name { font-weight: 700; font-size: 0.95rem; }
        .post-title { 
            font-size: 1rem; 
            font-weight: 700; 
            margin: 0 0 8px 0; 
            line-height: 1.4;
            display: -webkit-box;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 2;
            overflow: hidden;
        }
        .post-preview {
            font-size: 0.9rem;
            color: var(--muted);
            line-height: 1.4;
            display: -webkit-box;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 2;
            overflow: hidden;
            margin-bottom: 8px;
        }
        .post-meta { 
            display: flex; 
            gap: 12px; 
            font-size: 0.8rem; 
            color: var(--muted);
        }
        mark {
            background-color: #FFEB3B;
            font-weight: bold;
            border-radius: 2px;
            padding: 0 2px;
        }
        .empty-message {
            text-align: center;
            padding: 40px 20px;
            color: var(--muted);
        }
        .empty-message .icon { font-size: 3rem; margin-bottom: 12px; }
    </style>
</head>
<body>
    @include('partials.site-nav', ['apartmentId' => $apartmentId])

    <div class="wrap">
        <div class="appbar">
            <div class="left">
                <a class="back-chip" href="#" onclick="navigateBack(event);" data-apartment-id="{{ $apartmentId }}">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 12H5M12 19l-7-7 7-7"/>
                    </svg>
                </a>
                <span class="title" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $searchQuery }}</span>
            </div>
        </div>

        @if($posts->count() > 0)
            <section>
                @foreach($posts as $post)
                    @php
                        $canRead = auth()->check() || $post['can_read'];
                        $highlightText = function ($text, $query) {
                            if (trim($query) === '') {
                                return $text;
                            }
                            $pattern = '/(' . preg_quote($query, '/') . ')/iu';
                            return preg_replace($pattern, '<mark style="background-color: #FFEB3B; font-weight: bold;">$1</mark>', $text);
                        };
                        
                        $titleDisplay = $highlightText(e($post['title']), $searchQuery);
                        $bodyPreviewDisplay = $highlightText(e(trim((string) ($post['body_preview'] ?? ''))), $searchQuery);
                    @endphp
                    <a href="{{ $post['url'] }}" style="text-decoration: none; color: inherit;">
                        <div class="post-item">
                            <div class="author-row">
                                <div class="avatar">{{ $post['author_initial'] }}</div>
                                <div style="flex: 1;">
                                    <div class="author-name">{{ $post['author_name'] }}</div>
                                    <div class="meta">{{ $post['created_label'] }}</div>
                                </div>
                            </div>
                            <h3 class="post-title">{!! $titleDisplay !!}</h3>
                            @if($bodyPreviewDisplay !== '')
                                <div class="post-preview">{!! $bodyPreviewDisplay !!}</div>
                            @endif
                            <div class="post-meta">
                                <span>💬 {{ $post['comment_count'] }}</span>
                                <span>👁 {{ $post['view_count'] }}</span>
                            </div>
                        </div>
                    </a>
                @endforeach
            </section>
        @else
            <div class="empty-message">
                <div class="icon">🔍</div>
                <p><strong>검색 결과가 없습니다.</strong></p>
                <p style="font-size: 0.9rem;">다른 검색어로 다시 시도해주세요.</p>
            </div>
        @endif
    </div>

    <script>
        const navigateBack = (event) => {
            if (event) {
                event.preventDefault();
            }
            const backChip = document.querySelector('[data-apartment-id]');
            const apartmentId = backChip?.dataset.apartmentId || '1';
            window.location.href = `/community?apartment_id=${apartmentId}`;
        };
    </script>
</body>
</html>
