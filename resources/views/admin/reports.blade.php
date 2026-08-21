<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>신고 관리</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; margin: 0; background: #f5f7fb; color: #1a2a44; }
        .wrap { margin: 0; padding: 24px 28px; }
        table { width: 100%; border-collapse: collapse; background: #fff; border: 1px solid #dce4ef; border-radius: 12px; overflow: hidden; }
        th, td { padding: 8px; border-bottom: 1px solid #edf1f7; text-align: left; vertical-align: top; }
        a { color: #0f6f67; text-decoration: none; font-weight: 600; }
        select, input, textarea { width: 95%; border: 1px solid #c9d5e8; border-radius: 8px; padding: 7px; font: inherit; }
        button { border: 0; border-radius: 8px; background: #0f6f67; color: #fff; padding: 8px 10px; font-weight: 700; cursor: pointer; }
        .flash { background: #e8f6f1; border: 1px solid #bee6d9; color: #166b53; border-radius: 8px; padding: 10px; margin-bottom: 12px; }
        .detail-cell { max-width: 220px; white-space: pre-wrap; word-break: break-word; color: #33465f; font-size: 0.88rem; }
        .bulk-bar { display: flex; gap: 8px; align-items: center; background: #1a2a44; color: #fff; padding: 10px 14px; border-radius: 10px; margin-bottom: 8px; }
        .bulk-bar select { width: auto; background: #2e4fb8; color: #fff; border-color: #4a6fd8; padding: 6px 10px; }
        .bulk-count { font-weight: 700; min-width: 60px; }
        .cb-col { width: 32px; text-align: center; }
    </style>
</head>
<body>
<div class="wrap">
    @include('partials.admin-nav')
    <h1>신고 관리</h1>

    @php
        $reasonLabels = [
            'spam' => '스팜/도배',
            'abuse' => '욕설/비하/괴롭힌',
            'illegal' => '불법/유해 정보',
            'other' => '기타',
        ];
        $statusLabels = [
            'pending' => '대기중',
            'reviewed' => '검토완료',
            'dismissed' => '반려',
            'hidden' => '숨김처리',
        ];
    @endphp

    @if(session('status'))
        <div class="flash">{{ session('status') }}</div>
    @endif

    <form id="bulkForm" method="post" action="/admin/reports/bulk">
        @csrf
        <div class="bulk-bar" id="bulkBar" style="display:none;">
            <span class="bulk-count"><span id="checkedCount">0</span>개 선택</span>
            <select name="action" id="bulkAction">
                <option value="hide">숨김 처리</option>
                <option value="dismiss">반려</option>
            </select>
            <button type="submit" onclick="return confirmBulk()">일괄 적용</button>
            <button type="button" onclick="uncheckAll()">선택 해제</button>
        </div>

        <table>
            <thead>
            <tr>
                <th class="cb-col"><input type="checkbox" id="checkAll" title="전체 선택"></th>
                <th>ID</th>
                <th>대상</th>
                <th>사유</th>
                <th>상세 내용</th>
                <th>상태 변경</th>
                <th>관리 메모</th>
                <th>신고일시</th>
                <th>작업</th>
            </tr>
            </thead>
            <tbody>
            @forelse($reports as $report)
                @php($reportTargetLabel = class_basename($report->reportable_type) === 'Post' ? '게시글' : ($report->reportable?->parent_id ? '답글' : '댓글'))
                <tr>
                    <td class="cb-col"><input type="checkbox" name="ids[]" value="{{ $report->id }}" class="row-cb"></td>
                    <td>{{ $report->id }}</td>
                    <td>{{ $reportTargetLabel }}#{{ $report->reportable_id }}</td>
                    <td>{{ $reasonLabels[$report->reason] ?? $report->reason }}</td>
                    <td class="detail-cell">{{ $report->detail ?: '-' }}</td>
                    <td>{{ $statusLabels[$report->status] ?? $report->status }}</td>
                    <td>{{ $report->admin_note }}</td>
                    <td>{{ $report->created_at }}</td>
                    <td>
                        <a href="#" class="edit-report-link" data-report-id="{{ $report->id }}" data-status="{{ $report->status }}" data-admin-note="{{ $report->admin_note }}">수정</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="9">신고 데이터가 없습니다.</td></tr>
            @endforelse
            </tbody>
        </table>
    </form>

    {{-- 개별 상태 변경용 폼 (bulkForm 중첩 방지) --}}
    <dialog id="editDialog" style="border:0; border-radius: 12px; padding: 0; width: min(420px, 90vw);">
        <form id="editForm" method="post" style="padding: 16px;">
            @csrf
            @method('PUT')
            <h3 style="margin:0 0 10px;">신고 상태 변경</h3>
            <label style="display:block; font-size:0.85rem; font-weight:700; margin-bottom:4px;">상태
                <select name="status" id="editStatus" required>
                    <option value="pending">대기중</option>
                    <option value="reviewed">검토완료</option>
                    <option value="dismissed">반려</option>
                    <option value="hidden">숨김처리</option>
                </select>
            </label>
            <label style="display:block; font-size:0.85rem; font-weight:700; margin: 10px 0 4px;">관리 메모
                <textarea name="admin_note" id="editAdminNote" rows="3"></textarea>
            </label>
            <div style="display:flex; gap:8px; margin-top:12px; justify-content:flex-end;">
                <button type="button" onclick="document.getElementById('editDialog').close()" style="background:#c9d5e8; color:#1a2a44;">취소</button>
                <button type="submit">저장</button>
            </div>
        </form>
    </dialog>
</div>
<script>
(function () {
    const checkAll = document.getElementById('checkAll');
    const bulkBar = document.getElementById('bulkBar');
    const checkedCount = document.getElementById('checkedCount');
    const bulkAction = document.getElementById('bulkAction');
    const editDialog = document.getElementById('editDialog');
    const editForm = document.getElementById('editForm');

    function updateBulkBar() {
        const checked = document.querySelectorAll('.row-cb:checked');
        checkedCount.textContent = checked.length;
        bulkBar.style.display = checked.length > 0 ? 'flex' : 'none';
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

    window.uncheckAll = function () {
        document.querySelectorAll('.row-cb').forEach(cb => cb.checked = false);
        checkAll.checked = false;
        checkAll.indeterminate = false;
        updateBulkBar();
    };

    window.confirmBulk = function () {
        const count = document.querySelectorAll('.row-cb:checked').length;
        const action = bulkAction.options[bulkAction.selectedIndex].text;
        return confirm(count + '건의 신고를 [' + action + '] 처리하시겠습니까?');
    };

    document.querySelectorAll('.edit-report-link').forEach(function (link) {
        link.addEventListener('click', function (event) {
            event.preventDefault();
            editReport(link.dataset.reportId, link.dataset.status, link.dataset.adminNote);
        });
    });

    window.editReport = function (id, status, adminNote) {
        editForm.action = '/admin/reports/' + id;
        document.getElementById('editStatus').value = status;
        document.getElementById('editAdminNote').value = adminNote || '';
        editDialog.showModal();
    };
})();
</script>
</body>
</html>
