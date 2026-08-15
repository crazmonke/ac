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
                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10" cy="8" r="3"/><path d="M4.5 19a5.5 5.5 0 0 1 11 0"/><path d="M17.5 13.5a3.5 3.5 0 0 1 0 7"/><path d="M17.5 13.5v2.2"/></svg>
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
    form.addEventListener('submit', (event) => {
        if (submit.disabled) {
            event.preventDefault();
        }
    });
})();
</script>
