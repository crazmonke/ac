<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>새 글 작성</title>
    <style>
        :root {
            --bg: #f5f7fb;
            --card: #ffffff;
            --ink: #18283d;
            --muted: #607086;
            --line: #dde5ef;
            --brand: #2f52b8;
        }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; background: var(--bg); color: var(--ink); }
        .wrap { max-width: 740px; margin: 0 auto; padding: 12px; }
        .card { background: var(--card); border: 1px solid var(--line); border-radius: 18px; padding: 14px; margin-bottom: 12px; }
        .meta { color: var(--muted); font-size: 0.88rem; }
        input, textarea { width: 100%; border: 1px solid #c7d8ea; border-radius: 14px; padding: 12px; font: inherit; background: #fff; }
        textarea { min-height: 180px; }
        button, a.btn { border: 0; border-radius: 999px; background: var(--brand); color: #fff; padding: 10px 14px; font-weight: 800; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; }
        a.btn.secondary { background: #dde7f3; color: #20324b; }
        .actions { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 12px; }
        .grid { display: grid; gap: 10px; }
    </style>
</head>
<body>
<div class="wrap">
    @include('partials.site-nav', ['apartmentId' => $apartmentId])
    <section class="card">
        <p class="meta"><a href="/community/{{ $board->slug }}?apartment_id={{ $apartmentId }}">← 목록으로</a></p>
        <h1 style="margin-top:0;">새 글 작성</h1>
        <p class="meta">게시판: {{ $board->name }}</p>

        <form method="post" enctype="multipart/form-data" action="/community/boards/{{ $board->slug }}/posts?apartment_id={{ $apartmentId }}">
            @csrf
            <div class="grid">
                <input name="title" placeholder="제목" required>
                <textarea name="body" placeholder="내용" required></textarea>
                <label><input type="checkbox" name="is_anonymous" value="1" style="width:auto;"> 익명</label>
                <label><input type="checkbox" name="is_guest_visible" value="1" style="width:auto;"> 비회원에게 본문 공개</label>
                <input type="file" name="attachments[]" multiple accept=".jpg,.jpeg,.png,.gif,.pdf">
                <div class="actions">
                    <button type="submit">등록</button>
                    <a class="btn secondary" href="/community/{{ $board->slug }}?apartment_id={{ $apartmentId }}">취소</a>
                </div>
            </div>
        </form>
    </section>
</div>
</body>
</html>
