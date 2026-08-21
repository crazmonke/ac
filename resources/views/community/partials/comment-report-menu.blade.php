@php
    $__showCommentMenu = ($currentUserId ?? null) !== null && (int) ($currentUserId ?? -1) !== (int) ($commentUserId ?? -2);
@endphp
@if($__showCommentMenu)
<span class="comment-menu" onclick="event.stopPropagation();">
    <button type="button" class="action-btn" data-comment-menu-toggle aria-haspopup="true" aria-expanded="false" aria-label="더보기">
        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="5" r="1.7" fill="currentColor" stroke="none"/><circle cx="12" cy="12" r="1.7" fill="currentColor" stroke="none"/><circle cx="12" cy="19" r="1.7" fill="currentColor" stroke="none"/></svg>
    </button>
    <div class="comment-menu-dropdown" data-comment-menu-dropdown hidden role="menu">
        <a href="/reports/new?type=comment&id={{ $commentId }}&apartment_id={{ $apartmentId }}" role="menuitem">신고</a>
        <form method="post" action="/community/comments/{{ $commentId }}/hide" style="margin:0;">
            @csrf
            <input type="hidden" name="redirect" value="{{ $redirectUrl ?? url()->current() }}">
            <button type="submit" class="menu-danger" role="menuitem">숨기기</button>
        </form>
    </div>
</span>
@endif
