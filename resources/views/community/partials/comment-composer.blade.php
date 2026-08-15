<div class="comment-composer" data-comment-composer>
    <form method="post" action="/community/posts/{{ $post->id }}/comments" enctype="multipart/form-data" data-comment-composer-form>
        @csrf
        @if(isset($parentCommentId))
            <input type="hidden" name="parent_id" value="{{ $parentCommentId }}">
        @endif
        <input type="file" name="photo" accept="image/jpeg,image/png,image/gif,image/webp" data-comment-photo-input hidden>
        <div class="comment-photo-preview" data-comment-photo-preview hidden>
            <img alt="첨부할 사진" data-comment-photo-image>
            <button type="button" class="comment-photo-remove" data-comment-photo-remove aria-label="첨부 사진 제거">×</button>
        </div>
        <div class="comment-composer-row">
            <button type="button" class="comment-composer-tool" data-comment-photo-trigger aria-label="사진 첨부">
                <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="8.5" cy="9" r="1.5"/><path d="m4.5 18 5.2-5.2 3.3 3.2 2.2-2.2L20 18"/></svg>
            </button>
            <label class="comment-composer-tool comment-anonymous-toggle" aria-label="익명으로 작성">
                <input type="checkbox" name="is_anonymous" value="1" data-comment-anonymous>
                <svg viewBox="0 0 24 24" aria-hidden="true"><circle class="anonymous-icon-bg" cx="12" cy="12" r="10"/><circle class="anonymous-icon-person" cx="12" cy="9" r="3.4"/><path class="anonymous-icon-person" d="M5.6 19.2c.9-3.6 3.2-5.4 6.4-5.4s5.5 1.8 6.4 5.4Z"/></svg>
                <span class="anonymous-tooltip" data-anonymous-tooltip role="status" aria-live="polite"></span>
            </label>
            <textarea name="body" rows="1" placeholder="{{ isset($parentCommentId) ? '답글을 입력해주세요.' : '댓글을 입력해주세요.' }}" data-comment-body></textarea>
            <button type="submit" class="comment-submit" data-comment-submit aria-label="등록" disabled>
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m4 3 17 9-17 9 3-9Z"/></svg>
            </button>
        </div>
    </form>
</div>
<script>
(() => {
    const composer = document.querySelector('[data-comment-composer]');
    if (!composer) {
        return;
    }

    const form = composer.querySelector('[data-comment-composer-form]');
    const body = composer.querySelector('[data-comment-body]');
    const photoInput = composer.querySelector('[data-comment-photo-input]');
    const photoTrigger = composer.querySelector('[data-comment-photo-trigger]');
    const photoPreview = composer.querySelector('[data-comment-photo-preview]');
    const photoImage = composer.querySelector('[data-comment-photo-image]');
    const photoRemove = composer.querySelector('[data-comment-photo-remove]');
    const submit = composer.querySelector('[data-comment-submit]');
    const anonymousToggle = composer.querySelector('[data-comment-anonymous]');
    const anonymousTooltip = composer.querySelector('[data-anonymous-tooltip]');
    let anonymousTooltipTimer = null;

    const updateSubmitState = () => {
        const hasBody = body.value.trim().length > 0;
        const hasPhoto = photoInput.files.length > 0;
        submit.disabled = !hasBody && !hasPhoto;
    };

    const resizeBody = () => {
        body.style.height = '42px';
        body.style.height = `${Math.min(body.scrollHeight, 112)}px`;
    };

    photoTrigger.addEventListener('click', () => photoInput.click());
    body.addEventListener('focus', () => composer.classList.add('is-focused'));
    body.addEventListener('input', () => {
        composer.classList.add('is-focused');
        resizeBody();
        updateSubmitState();
    });
    photoInput.addEventListener('change', () => {
        const [photo] = photoInput.files;
        if (!photo) {
            return;
        }

        photoImage.src = URL.createObjectURL(photo);
        photoPreview.hidden = false;
        composer.classList.add('is-focused');
        updateSubmitState();
    });
    photoRemove.addEventListener('click', () => {
        photoInput.value = '';
        photoImage.removeAttribute('src');
        photoPreview.hidden = true;
        updateSubmitState();
    });
    anonymousToggle.addEventListener('change', () => {
        window.clearTimeout(anonymousTooltipTimer);
        anonymousTooltip.textContent = anonymousToggle.checked ? '익명 설정' : '익명 해제';
        anonymousTooltip.classList.add('visible');
        anonymousTooltipTimer = window.setTimeout(() => {
            anonymousTooltip.classList.remove('visible');
        }, 1400);
    });
    form.addEventListener('submit', (event) => {
        if (submit.disabled) {
            event.preventDefault();
        }
    });
})();
</script>
