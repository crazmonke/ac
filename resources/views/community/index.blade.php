<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>커뮤니티 홈</title>
    <style>
        body { margin: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; background: #f4f8fb; color: #17263d; }
        .wrap { max-width: 1080px; margin: 0 auto; padding: 24px; }
        .top { display: flex; justify-content: space-between; align-items: center; gap: 10px; flex-wrap: wrap; }
        .grid { margin-top: 16px; display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 12px; }
        .card { background: #fff; border: 1px solid #d5dfec; border-radius: 12px; padding: 14px; }
        .meta { color: #5b6d82; font-size: 0.9rem; }
        .pill { display: inline-block; padding: 3px 8px; border: 1px solid #c7d8ea; border-radius: 999px; font-size: 0.78rem; margin-right: 6px; }
        .err { margin-top: 14px; padding: 10px; border-radius: 8px; background: #fdecec; border: 1px solid #f4c8c8; color: #9e1d1d; }
        a { color: #0f6f67; text-decoration: none; font-weight: 700; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="top">
        <h1>입주민 커뮤니티</h1>
        <div class="meta">apartment_id={{ $apartmentId }} · <a href="/admin/boards">게시판 관리</a></div>
    </div>

    <div id="errorBox" class="err" style="display:none;"></div>
    <div id="boardContainer" class="grid"></div>
</div>

<script>
(async function loadBoards() {
    const apartmentId = {{ $apartmentId }};
    const errorBox = document.getElementById('errorBox');
    const container = document.getElementById('boardContainer');

    try {
        const response = await fetch(`/community/api/apartments/${apartmentId}/boards`, {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json'
            }
        });

        if (!response.ok) {
            const text = await response.text();
            throw new Error(`API ${response.status}: ${text.slice(0, 200)}`);
        }

        const payload = await response.json();
        const categories = payload.data || [];

        if (!categories.length) {
            container.innerHTML = '<div class="card">현재 표시할 게시판이 없습니다.</div>';
            return;
        }

        const cards = [];

        categories.forEach((category) => {
            const boards = category.boards || [];
            if (!boards.length) return;

            boards.forEach((board) => {
                cards.push(`
                    <article class="card">
                        <h3><a href="/community/${board.slug}?apartment_id=${apartmentId}">${board.name}</a></h3>
                        <div class="meta">카테고리: ${category.name} (${category.slug})</div>
                        <p class="meta">${board.description || '설명 없음'}</p>
                        <div>
                            <span class="pill">읽기 ${board.read_role}</span>
                            <span class="pill">쓰기 ${board.write_role}</span>
                            <span class="pill">댓글 ${board.comment_role}</span>
                        </div>
                        <div class="meta" style="margin-top:8px;">slug: ${board.slug}</div>
                    </article>
                `);
            });
        });

        container.innerHTML = cards.length ? cards.join('') : '<div class="card">현재 표시할 게시판이 없습니다.</div>';
    } catch (error) {
        errorBox.style.display = 'block';
        errorBox.textContent = '게시판 목록 로드 실패: ' + error.message;
    }
})();
</script>
</body>
</html>
