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
        .controls { margin-top: 12px; display: grid; grid-template-columns: 1fr 200px; gap: 10px; }
        .grid { margin-top: 16px; display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 12px; }
        .card { background: #fff; border: 1px solid #d5dfec; border-radius: 12px; padding: 14px; }
        .meta { color: #5b6d82; font-size: 0.9rem; }
        .pill { display: inline-block; padding: 3px 8px; border: 1px solid #c7d8ea; border-radius: 999px; font-size: 0.78rem; margin-right: 6px; }
        .err { margin-top: 14px; padding: 10px; border-radius: 8px; background: #fdecec; border: 1px solid #f4c8c8; color: #9e1d1d; }
        input, select { width: 100%; border: 1px solid #c7d8ea; border-radius: 8px; padding: 8px; }
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
    <div class="controls">
        <input id="searchInput" placeholder="게시판 이름/설명 검색">
        <select id="categorySelect">
            <option value="">전체 카테고리</option>
        </select>
    </div>
    <div id="boardContainer" class="grid"></div>
</div>

<script>
(async function loadBoards() {
    const apartmentId = {{ $apartmentId }};
    const errorBox = document.getElementById('errorBox');
    const container = document.getElementById('boardContainer');
    const searchInput = document.getElementById('searchInput');
    const categorySelect = document.getElementById('categorySelect');

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
        let boardRows = [];

        if (!categories.length) {
            container.innerHTML = '<div class="card">현재 표시할 게시판이 없습니다.</div>';
            return;
        }

        categories.forEach((category) => {
            const option = document.createElement('option');
            option.value = String(category.id);
            option.textContent = `${category.name} (${category.slug})`;
            categorySelect.appendChild(option);

            const boards = category.boards || [];
            boards.forEach((board) => {
                boardRows.push({ category, board });
            });
        });

        function render() {
            const q = (searchInput.value || '').trim().toLowerCase();
            const categoryFilter = categorySelect.value;

            const filtered = boardRows.filter(({ category, board }) => {
                const byCategory = !categoryFilter || String(category.id) === categoryFilter;
                const haystack = `${board.name} ${board.description || ''} ${category.name}`.toLowerCase();
                const byQuery = !q || haystack.includes(q);
                return byCategory && byQuery;
            });

            if (!filtered.length) {
                container.innerHTML = '<div class="card">조건에 맞는 게시판이 없습니다.</div>';
                return;
            }

            const cards = filtered.map(({ category, board }) => `
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

            container.innerHTML = cards.join('');
        }

        searchInput.addEventListener('input', render);
        categorySelect.addEventListener('change', render);
        render();
    } catch (error) {
        errorBox.style.display = 'block';
        errorBox.textContent = '게시판 목록 로드 실패: ' + error.message;
    }
})();
</script>
</body>
</html>
