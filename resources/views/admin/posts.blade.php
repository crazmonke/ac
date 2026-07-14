<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>게시글 관리</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; margin: 0; background: #f5f7fb; color: #1a2a44; }
        .wrap { margin: 0; padding: 24px 28px; }
        .panel { background: #fff; border: 1px solid #dce4ef; border-radius: 12px; padding: 14px; margin-top: 12px; }
        .meta { color: #607086; font-size: 0.85rem; }
        .toolbar { display: flex; gap: 8px; align-items: center; justify-content: space-between; flex-wrap: wrap; }
        .toolbar form { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
        input, select { border: 1px solid #c8d5e7; border-radius: 8px; padding: 8px 10px; font: inherit; }
        .btn { border: 0; border-radius: 10px; padding: 8px 12px; font-weight: 700; cursor: pointer; font: inherit; }
        .btn-primary { background: #2e4fb8; color: #fff; }
        .btn-danger { background: #b42318; color: #fff; }
        .btn-muted { background: #e7edf7; color: #22344f; }
        .btn-sm { padding: 5px 9px; font-size: 0.82rem; }
        .btn[disabled] { opacity: 0.5; cursor: not-allowed; }
        .table-wrap { overflow-x: auto; margin-top: 10px; }
        table { width: 100%; border-collapse: collapse; min-width: 1100px; }
        th, td { border-bottom: 1px solid #edf1f7; padding: 9px 8px; text-align: left; vertical-align: middle; font-size: 0.87rem; }
        th { background: #f8fbff; position: sticky; top: 0; z-index: 1; white-space: nowrap; }
        .status { display: inline-flex; padding: 2px 7px; border-radius: 999px; font-size: 0.76rem; font-weight: 700; }
        .ok { background: #e8f6f1; color: #166b53; }
        .warn { background: #fff4e8; color: #8d4a1c; }
        .danger { background: #fdecec; color: #9e1d1d; }
        .cb-col { width: 36px; text-align: center !important; }
        .title-cell { max-width: 320px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .bulk-bar { display: flex; gap: 8px; align-items: center; background: #1a2a44; color: #fff; padding: 10px 14px; border-radius: 10px; margin-bottom: 8px; }
        .bulk-bar select { background: #2e4fb8; color: #fff; border-color: #4a6fd8; padding: 6px 10px; }
        .bulk-count { font-weight: 700; min-width: 60px; }
    </style>
</head>
<body>
<div class="wrap">
    @include('partials.admin-nav')

    <h1>게시글 관리</h1>

    @if(session('status'))
        <div class="panel" style="background:#e8f6f1; border-color:#bee6d9; color:#166b53;">{{ session('status') }}</div>
    @endif

    <section class="panel toolbar">
        <div class="meta">모든 게시글 조회 · 일괄 삭제 · 일괄 숨김 처리</div>
        <form method="get" action="/admin/posts">
            <select name="board_id">
                <option value="">전체 게시판</option>
                @foreach($boards as $board)
                    <option value="{{ $board->id }}" @selected((string)$boardId === (string)$board->id)>{{ $board->name }}</option>
                @endforeach
            </select>
            <select name="visibility">
                <option value="" @selected($visibilityFilter === '')>전체 공개 상태</option>
                <option value="public" @selected($visibilityFilter === 'public')>공개</option>
                <option value="resident_only" @selected($visibilityFilter === 'resident_only')>입주민 전용</option>
                <option value="deleted" @selected($visibilityFilter === 'deleted')>숨김</option>
            </select>
            <input type="text" name="q" value="{{ $q }}" placeholder="제목/본문 검색">
            <button class="btn btn-primary" type="submit">검색</button>
            @if($q || $boardId || $visibilityFilter)
                <a href="/admin/posts" class="btn btn-muted">초기화</a>
            @endif
        </form>
    </section>

    <form id="bulkForm" method="post" action="/admin/posts/bulk">
        @csrf
        <div class="bulk-bar" id="bulkBar" style="display:none;">
            <span class="bulk-count"><span id="checkedCount">0</span>개 선택</span>
            <select name="action" id="bulkAction">
                <option value="hide">숨김 처리</option>
                <option value="show">숨김 해제</option>
                <option value="delete">삭제</option>
            </select>
            <button class="btn btn-danger" type="submit" onclick="return confirmBulk()">일괄 적용</button>
            <button class="btn btn-muted" type="button" onclick="uncheckAll()">선택 해제</button>
        </div>

        <section class="panel table-wrap" style="margin-top:0; padding:0; border-radius:12px; overflow:hidden;">
            <table>
                <thead>
                <tr>
                    <th class="cb-col"><input type="checkbox" id="checkAll" title="전체 선택"></th>
                    <th>ID</th>
                    <th>제목</th>
                    <th>작성자</th>
                    <th>게시판</th>
                    <th>공개범위</th>
                    <th>댓글</th>
                    <th>작성일</th>
                    <th>관리</th>
                </tr>
                </thead>
                <tbody>
                @forelse($posts as $post)
                    <tr>
                        <td class="cb-col">
                            <input type="checkbox" name="ids[]" value="{{ $post->id }}" class="row-cb">
                        </td>
                        <td>{{ $post->id }}</td>
                        <td class="title-cell" title="{{ $post->title }}">
                            <a href="/community/posts/{{ $post->id }}?apartment_id={{ $post->apartment_id }}" target="_blank" style="color:#2e4fb8; text-decoration:none;">{{ $post->title }}</a>
                        </td>
                        <td>{{ $post->user?->name ?? '(삭제됨)' }}<br><span class="meta">{{ $post->user?->email }}</span></td>
                        <td>{{ $post->board?->name ?? '-' }}</td>
                        <td>
                            @if($post->visibility === 'deleted')
                                <span class="status danger">숨김</span>
                            @elseif($post->visibility === 'public')
                                <span class="status ok">공개</span>
                            @else
                                <span class="status warn">입주민</span>
                            @endif
                        </td>
                        <td>{{ $post->comment_count ?? 0 }}</td>
                        <td style="white-space:nowrap;">{{ $post->created_at?->format('Y-m-d H:i') ?? '-' }}</td>
                        <td style="white-space:nowrap;">
                            <button class="btn btn-danger btn-sm" type="button" onclick="deleteSingle({{ $post->id }})">삭제</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" style="text-align:center; padding:24px; color:#607086;">게시글이 없습니다.</td></tr>
                @endforelse
                </tbody>
            </table>
        </section>
    </form>

    {{-- 개별 삭제용 폼 (bulkForm 중첩 방지) --}}
    <form id="singleDeleteForm" method="post" style="display:none;">
        @csrf
        @method('delete')
    </form>

    @include('partials.pagination', ['paginator' => $posts])
</div>

<script>
(function () {
    const checkAll = document.getElementById('checkAll');
    const bulkBar = document.getElementById('bulkBar');
    const checkedCount = document.getElementById('checkedCount');
    const bulkAction = document.getElementById('bulkAction');

    function updateBulkBar() {
        const checked = document.querySelectorAll('.row-cb:checked');
        const n = checked.length;
        checkedCount.textContent = n;
        bulkBar.style.display = n > 0 ? 'flex' : 'none';
    }

    checkAll.addEventListener('change', function () {
        document.querySelectorAll('.row-cb').forEach(cb => cb.checked = this.checked);
        updateBulkBar();
    });

    document.querySelectorAll('.row-cb').forEach(cb => {
        cb.addEventListener('change', function () {
            const all = document.querySelectorAll('.row-cb');
            const checked = document.querySelectorAll('.row-cb:checked');
            checkAll.checked = all.length === checked.length;
            checkAll.indeterminate = checked.length > 0 && checked.length < all.length;
            updateBulkBar();
        });
    });

    window.deleteSingle = function (id) {
        if (!confirm('이 게시글을 삭제할까요?')) return;
        const form = document.getElementById('singleDeleteForm');
        form.action = '/admin/posts/' + id;
        form.submit();
    };

    window.uncheckAll = function () {
        document.querySelectorAll('.row-cb').forEach(cb => cb.checked = false);
        checkAll.checked = false;
        checkAll.indeterminate = false;
        updateBulkBar();
    };

    window.confirmBulk = function () {
        const count = document.querySelectorAll('.row-cb:checked').length;
        const action = bulkAction.options[bulkAction.selectedIndex].text;
        return confirm(count + '개 게시글을 [' + action + '] 하시겠습니까?');
    };
})();
</script>
</body>
</html>
