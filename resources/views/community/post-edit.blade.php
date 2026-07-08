<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>게시글 수정</title>
    <style>
        body { margin: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; background: #f4f8fb; color: #17263d; }
        .wrap { max-width: 860px; margin: 0 auto; padding: 24px; }
        .panel { background: #fff; border: 1px solid #d5dfec; border-radius: 12px; padding: 14px; margin-bottom: 14px; }
        input, textarea { width: 100%; border: 1px solid #c7d8ea; border-radius: 8px; padding: 9px; }
        .form-select {
            width: 100%;
            border: 1px solid #c7d8ea;
            border-radius: 8px;
            padding: 9px 38px 9px 10px;
            font: inherit;
            color: #17263d;
            background-color: #fff;
            appearance: none;
            -webkit-appearance: none;
            background-image: linear-gradient(45deg, transparent 50%, #61748f 50%), linear-gradient(135deg, #61748f 50%, transparent 50%);
            background-position: calc(100% - 16px) 50%, calc(100% - 10px) 50%;
            background-size: 6px 6px, 6px 6px;
            background-repeat: no-repeat;
            transition: border-color 0.18s ease, box-shadow 0.18s ease;
        }
        .form-select:focus {
            outline: none;
            border-color: #9eb8df;
            box-shadow: 0 0 0 3px rgba(47, 82, 184, 0.12);
        }
        textarea { min-height: 140px; }
        button, a.btn { border: 0; border-radius: 8px; background: #0f6f67; color: #fff; padding: 8px 12px; font-weight: 700; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; }
        a.btn.secondary { background: #dde7f3; color: #20324b; }
        .actions { display: flex; gap: 8px; flex-wrap: wrap; }
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
        .editor-shell { border: 1px solid #d6e1ee; border-radius: 16px; overflow: hidden; background: #fff; }
        .editor-toolbar { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; padding: 10px 12px; border-bottom: 1px solid #e4ebf5; background: linear-gradient(180deg, #fbfdff, #f4f8ff); }
        .editor-toolbar-scroll { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; width: 100%; }
        .editor-tool { border: 1px solid #ccd9eb; border-radius: 11px; background: #fff; color: #20324b; min-width: 40px; height: 38px; padding: 0 12px; font-size: 0.92rem; font-weight: 700; }
        .editor-tool.icon-only { width: 40px; min-width: 40px; padding: 0; }
        .editor-tool.accent { color: #2452a3; border-color: #bdd0ee; background: #f5f9ff; }
        .editor-tool.layer-toggle { gap: 6px; padding-right: 10px; }
        .editor-tool-caret { font-size: 0.75rem; color: #58708d; }
        .editor-tool-swatch { width: 14px; height: 14px; border-radius: 999px; border: 1px solid rgba(32, 50, 75, 0.18); box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.65); }
        .editor-layer-wrap { position: relative; flex: 0 0 auto; }
        .editor-layer { position: absolute; top: calc(100% + 10px); left: 0; z-index: 30; display: none; min-width: 176px; padding: 10px; border: 1px solid #d4deeb; border-radius: 16px; background: rgba(255, 255, 255, 0.98); box-shadow: 0 18px 45px rgba(26, 47, 80, 0.16); backdrop-filter: blur(12px); }
        .editor-layer.is-open { display: block; }
        .editor-layer-title { margin: 0 0 8px; font-size: 0.8rem; font-weight: 800; color: #61728a; }
        .editor-layer-section { margin-top: 10px; }
        .editor-size-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 8px; }
        .editor-size-option { border: 1px solid #d5dfec; border-radius: 12px; background: #fff; color: #20324b; min-height: 42px; padding: 8px 6px; font-weight: 800; font-size: 0.88rem; }
        .editor-size-option[data-size="12"] { font-size: 0.78rem; }
        .editor-size-option[data-size="14"] { font-size: 0.86rem; }
        .editor-size-option[data-size="16"] { font-size: 0.96rem; }
        .editor-size-option[data-size="18"] { font-size: 1.02rem; }
        .editor-size-option[data-size="24"] { font-size: 1.14rem; }
        .editor-size-option[data-size="32"] { font-size: 1.28rem; }
        .editor-color-grid { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 8px; }
        .editor-color-option { width: 28px; height: 28px; border: 1px solid rgba(32, 50, 75, 0.16); border-radius: 999px; background: var(--swatch, #20324b); padding: 0; }
        .editor-color-option[data-color="#ffffff"] { box-shadow: inset 0 0 0 1px rgba(32, 50, 75, 0.18); }
        .editor-style-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; }
        .editor-style-option { border: 1px solid #d5dfec; border-radius: 14px; background: #fff; color: #20324b; min-height: 58px; padding: 10px; text-align: left; }
        .editor-style-option strong { display: block; font-size: 0.94rem; }
        .editor-style-option span { display: block; margin-top: 4px; color: #607086; font-size: 0.77rem; }
        .editor-custom-color { display: flex; align-items: center; gap: 8px; margin-top: 10px; }
        .editor-custom-color input[type="color"] { width: 42px; height: 42px; padding: 0; border-radius: 12px; overflow: hidden; cursor: pointer; }
        .editor-custom-color input[type="text"] { flex: 1 1 auto; min-width: 0; height: 42px; padding: 0 12px; border: 1px solid #c7d8ea; border-radius: 12px; font-size: 0.9rem; }
        .editor-custom-color button { flex: 0 0 auto; height: 42px; padding: 0 12px; border-radius: 12px; }
        .editor-host { padding: 0; }
        .editor-host textarea { border: 0; border-radius: 0; }
        .compose-editor-wrap {
            border: 1px solid #d8e2ef;
            border-radius: 12px;
            background: #fff;
            padding: 10px;
        }
        .mobile-compose-tools {
            display: none;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            margin-top: 8px;
        }
        .mobile-compose-button {
            border: 1px solid #cfd8e6;
            border-radius: 999px;
            background: #f2f6fb;
            color: #20324b;
            min-height: 36px;
            padding: 8px 12px;
            font-size: 0.88rem;
            font-weight: 800;
        }
        .mobile-compose-note {
            display: none;
            color: #607086;
            font-size: 0.82rem;
        }
        .mobile-media-preview {
            display: none;
            margin-top: 10px;
            padding: 10px;
            border: 1px solid #dbe4f0;
            border-radius: 12px;
            background: #f8fbff;
            gap: 8px;
            grid-template-columns: repeat(auto-fill, minmax(84px, 1fr));
        }
        .mobile-media-preview-title {
            grid-column: 1 / -1;
            margin: 0;
            font-size: 0.82rem;
            font-weight: 800;
            color: #50627c;
        }
        .mobile-media-item {
            position: relative;
            border: 1px solid #d8e2ef;
            border-radius: 10px;
            overflow: hidden;
            background: #f7f9fc;
            aspect-ratio: 1;
        }
        .mobile-media-item img,
        .mobile-media-item video {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            background: #eef3f9;
        }
        .mobile-media-remove {
            position: absolute;
            top: 4px;
            right: 4px;
            width: 22px;
            height: 22px;
            min-width: 22px;
            border-radius: 999px;
            border: 1px solid rgba(255, 255, 255, 0.45);
            background: rgba(15, 20, 29, 0.72);
            color: #fff;
            font-size: 0.78rem;
            font-weight: 900;
            padding: 0;
            line-height: 1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .mobile-options-trigger {
            display: none;
            width: 100%;
            min-height: 40px;
            border-radius: 10px;
            border: 1px solid #cfd8e6;
            background: #eef3f9;
            color: #20324b;
            font-weight: 800;
        }
        .mobile-options-modal {
            position: fixed;
            inset: 0;
            z-index: 90;
            display: none;
            align-items: flex-end;
            justify-content: center;
            background: rgba(15, 20, 29, 0.48);
            padding: 14px;
        }
        .mobile-options-modal.open {
            display: flex;
        }
        .mobile-options-sheet {
            width: min(740px, 100%);
            max-height: min(72vh, 620px);
            overflow: auto;
            border-radius: 16px;
            background: #fff;
            border: 1px solid #d9e3ef;
            padding: 14px;
        }
        .mobile-options-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 10px;
        }
        .mobile-options-title {
            margin: 0;
            font-size: 1rem;
            font-weight: 900;
            color: #1c2d44;
        }
        .mobile-options-close {
            border: 1px solid #cfd8e6;
            border-radius: 10px;
            min-height: 34px;
            padding: 6px 10px;
            background: #f2f6fb;
            color: #20324b;
            font-weight: 800;
        }
        .mobile-options-body {
            display: grid;
            gap: 10px;
        }

        @media (max-width: 768px) {
            .wrap.mobile-compose-mode {
                padding: 14px 12px 20px;
            }
            .wrap.mobile-compose-mode #editorBody {
                min-height: 10vh;
                border: 0;
                border-radius: 12px;
                box-shadow: none;
                resize: vertical;
                font-size: 1rem;
                line-height: 1.55;
            }
            .wrap.mobile-compose-mode .compose-editor-wrap {
                border-color: #e1e8f2;
                padding: 10px 10px 8px;
            }
            .wrap.mobile-compose-mode .mobile-compose-tools {
                display: flex;
            }
            .wrap.mobile-compose-mode .mobile-media-preview.has-items {
                display: grid;
            }
            .wrap.mobile-compose-mode .mobile-compose-note {
                display: block;
            }
            .wrap.mobile-compose-mode .mobile-options-trigger {
                display: inline-flex;
                align-items: center;
                justify-content: center;
            }
            .wrap.mobile-compose-mode .js-post-option-field {
                display: none;
            }
            .wrap.mobile-compose-mode .mobile-options-body .js-post-option-field {
                display: block !important;
            }
            .wrap.mobile-compose-mode .mobile-options-body .publish-option.js-post-option-field {
                display: inline-flex !important;
                justify-content: flex-start;
                width: 100%;
            }
            .editor-toolbar { padding: 8px 10px; }
            .editor-toolbar-scroll { flex-wrap: nowrap; overflow-x: auto; overflow-y: hidden; -webkit-overflow-scrolling: touch; }
            .editor-tool { flex: 0 0 auto; height: 42px; min-width: 42px; padding: 0 10px; border-radius: 12px; font-size: 1rem; }
            .editor-tool.labelled { min-width: 58px; }
            .editor-layer { position: fixed; left: 12px; right: 12px; top: auto; bottom: 76px; min-width: 0; }
        }
    </style>
</head>
<body>
<div class="wrap">
    @include('partials.site-nav', ['apartmentId' => $apartmentId])
    <p class="meta"><a class="back-chip" href="/community/posts/{{ $post->id }}?apartment_id={{ $apartmentId }}">← 상세로 돌아가기</a></p>

    <section class="panel">
        <h1>게시글 수정</h1>
        <form method="post" enctype="multipart/form-data" action="/community/posts/{{ $post->id }}" class="js-smarteditor-form">
            @csrf
            @method('PUT')
            <div style="display:grid; gap:10px;">
                <input name="title" value="{{ old('title', $post->title) }}" required>
                <div class="compose-editor-wrap">
                    <textarea id="editorBody" name="body" style="width:100%; min-width:100px; height:200px;" data-editor-required="true">{{ old('body', $post->body) }}</textarea>
                    <div class="mobile-compose-tools">
                        <button type="button" class="mobile-compose-button js-mobile-image-button">사진 추가</button>
                        <button type="button" class="mobile-compose-button js-mobile-video-button">영상 추가</button>
                        <span class="mobile-compose-note">모바일에서는 간편 작성 모드로 동작합니다.</span>
                    </div>
                    <div class="mobile-media-preview js-mobile-media-preview" aria-live="polite">
                        <p class="mobile-media-preview-title">첨부된 미디어</p>
                    </div>
                    <input type="file" class="js-mobile-image-input" accept="image/*" multiple hidden>
                    <input type="file" class="js-mobile-video-input" accept="video/mp4,video/quicktime,video/webm,video/x-m4v" multiple hidden>
                </div>
                <label class="js-post-option-field">노출 카테고리
                    <select name="audience_scope" class="form-select" style="margin-top:6px;">
                        <option value="region" @selected(old('audience_scope', $post->audience_scope ?? 'region') === 'region')>동네 (비회원은 제목만, 로그인 회원은 상세 가능)</option>
                        <option value="apartment" @selected(old('audience_scope', $post->audience_scope ?? 'region') === 'apartment')>공동주택 (같은 단지 인증 회원만 상세)</option>
                    </select>
                </label>
                <div class="meta js-post-option-field" style="margin-top:-4px;">글쓰기는 인증회원만 가능합니다.</div>
                <label class="js-post-option-field">태그/섹션 선택
                    <select name="post_topic_id" class="form-select" style="margin-top:6px;">
                        <option value="">선택 안 함</option>
                        @foreach($topicOptions as $topic)
                            <option value="{{ $topic->id }}" @selected((string) old('post_topic_id', $post->post_topic_id) === (string) $topic->id)>#{{ $topic->name }}</option>
                        @endforeach
                    </select>
                </label>
                <div class="meta js-post-option-field" style="margin-top:-4px;">새 태그를 입력하면 선택한 기존 태그보다 우선 적용됩니다.</div>
                <input class="js-post-option-field" name="new_topic" value="{{ old('new_topic') }}" placeholder="새 태그 만들기 (입력 시 선택값보다 우선)">
                @if($post->board->board_type === 'poll')
                    <label>투표 제목
                        <input name="poll_question" value="{{ old('poll_question', $post->poll->question ?? '') }}" style="margin-top:6px;" placeholder="예: 단지 회의 시간은 언제가 좋을까요?" required>
                    </label>
                    <label>투표 선택지
                        <textarea name="poll_options" style="margin-top:6px; min-height: 120px;" placeholder="선택지를 한 줄에 하나씩 입력하세요." required>{{ old('poll_options', isset($post->poll) ? $post->poll->options->pluck('label')->implode("\n") : '') }}</textarea>
                    </label>
                    <label><input type="checkbox" name="poll_allow_multiple" value="1" style="width:auto;" @checked(old('poll_allow_multiple', $post->poll->allow_multiple ?? false))> 복수 선택 허용</label>
                    <label><input type="checkbox" name="poll_results_public" value="1" style="width:auto;" @checked(old('poll_results_public', $post->poll->results_public ?? true))> 투표 결과 공개</label>
                @endif
                <label class="js-post-option-field"><input type="checkbox" name="is_anonymous" value="1" style="width:auto;" @checked(old('is_anonymous', $post->is_anonymous))> 익명</label>
                <label class="js-post-option-field"><input type="checkbox" name="is_guest_visible" value="1" style="width:auto;" @checked(old('is_guest_visible', $post->is_guest_visible))> 비회원에게 본문 공개</label>
                <div class="actions">
                    <button type="button" class="mobile-options-trigger js-mobile-options-open">게시물 옵션</button>
                    <button type="submit">수정 저장</button>
                    <a class="btn secondary" href="/community/posts/{{ $post->id }}?apartment_id={{ $apartmentId }}">취소</a>
                </div>
            </div>

            <div class="mobile-options-modal" id="mobile-post-options-modal" aria-hidden="true">
                <div class="mobile-options-sheet" role="dialog" aria-modal="true" aria-label="게시물 옵션">
                    <div class="mobile-options-head">
                        <h2 class="mobile-options-title">게시물 옵션</h2>
                        <button type="button" class="mobile-options-close js-mobile-options-close">닫기</button>
                    </div>
                    <div class="mobile-options-body js-mobile-options-body"></div>
                </div>
            </div>
        </form>
    </section>

    @if($post->files->count())
        <section class="panel">
            <h2>현재 첨부파일</h2>
            <ul>
                @foreach($post->files as $file)
                    <li>{{ $file->original_name }}</li>
                @endforeach
            </ul>
        </section>
    @endif
</div>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/lang/summernote-ko-KR.min.js"></script>
<script>
(function () {
    const textarea = document.getElementById('editorBody');
    const form = document.querySelector('.js-smarteditor-form');
    if (!textarea || !form) {
        return;
    }

    const wrap = document.querySelector('.wrap');
    const isMobileComposer = window.matchMedia('(max-width: 768px)').matches;
    const mobileImageButton = form.querySelector('.js-mobile-image-button');
    const mobileImageInput = form.querySelector('.js-mobile-image-input');
    const mobileVideoButton = form.querySelector('.js-mobile-video-button');
    const mobileVideoInput = form.querySelector('.js-mobile-video-input');
    const mobileMediaPreview = form.querySelector('.js-mobile-media-preview');
    const mobileOptionsOpen = form.querySelector('.js-mobile-options-open');
    const mobileOptionsClose = form.querySelector('.js-mobile-options-close');
    const mobileOptionsModal = document.getElementById('mobile-post-options-modal');
    const mobileOptionsBody = form.querySelector('.js-mobile-options-body');
    const mobileOptionFields = Array.from(form.querySelectorAll('.js-post-option-field'));

    if (isMobileComposer && wrap) {
        wrap.classList.add('mobile-compose-mode');
        const viewport = document.querySelector('meta[name="viewport"]');
        if (viewport) {
            viewport.setAttribute('content', 'width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no');
        }
        document.addEventListener('gesturestart', function (event) {
            event.preventDefault();
        }, { passive: false });

        if (mobileOptionsBody && mobileOptionFields.length) {
            mobileOptionFields.forEach((field) => {
                mobileOptionsBody.appendChild(field);
            });
        }

        if (mobileOptionsOpen && mobileOptionsModal) {
            mobileOptionsOpen.addEventListener('click', function () {
                mobileOptionsModal.classList.add('open');
                mobileOptionsModal.setAttribute('aria-hidden', 'false');
            });
        }

        if (mobileOptionsClose && mobileOptionsModal) {
            mobileOptionsClose.addEventListener('click', function () {
                mobileOptionsModal.classList.remove('open');
                mobileOptionsModal.setAttribute('aria-hidden', 'true');
            });
        }

        if (mobileOptionsModal) {
            mobileOptionsModal.addEventListener('click', function (event) {
                if (event.target === mobileOptionsModal) {
                    mobileOptionsModal.classList.remove('open');
                    mobileOptionsModal.setAttribute('aria-hidden', 'true');
                }
            });
        }
    }

    const csrfToken = form.querySelector('input[name="_token"]')?.value || '';
    const mobileMediaAssets = [];
    let mobileMediaSeq = 0;

    const escapeHtml = (value) => String(value || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');

    const uploadEditorImage = async (file) => {
        const formData = new FormData();
        formData.append('file', file);

        const response = await fetch('/community/editor/photos', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                Accept: 'application/json',
            },
            body: formData,
        });

        if (!response.ok) {
            const payload = await response.json().catch(() => null);
            const message = payload?.errors?.file?.[0] || payload?.message || '이미지 업로드에 실패했습니다.';
            throw new Error(message);
        }

        const payload = await response.json();
        if (!payload?.url) {
            throw new Error('이미지 업로드 응답이 올바르지 않습니다.');
        }

        return payload;
    };

    const uploadEditorVideo = async (file) => {
        if (!file || !String(file.type || '').startsWith('video/')) {
            throw new Error('영상 파일만 업로드할 수 있습니다.');
        }

        const formData = new FormData();
        formData.append('file', file);

        const response = await fetch('/community/editor/videos', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                Accept: 'application/json',
            },
            body: formData,
        });

        if (!response.ok) {
            const payload = await response.json().catch(() => null);
            const message = payload?.errors?.file?.[0] || payload?.message || '영상 업로드에 실패했습니다.';
            throw new Error(message);
        }

        const payload = await response.json();
        if (!payload?.url) {
            throw new Error('영상 업로드 응답이 올바르지 않습니다.');
        }

        return payload;
    };

    const renderMobileMediaPreview = () => {
        if (!mobileMediaPreview) {
            return;
        }

        mobileMediaPreview.innerHTML = '<p class="mobile-media-preview-title">첨부된 미디어</p>';
        mobileMediaPreview.classList.toggle('has-items', mobileMediaAssets.length > 0);
        if (!mobileMediaAssets.length) {
            return;
        }

        mobileMediaAssets.forEach((asset) => {
            const item = document.createElement('div');
            item.className = 'mobile-media-item';
            item.dataset.mediaId = asset.id;

            const media = asset.type === 'video'
                ? document.createElement('video')
                : document.createElement('img');

            media.src = asset.url;
            media.alt = asset.name || asset.type;
            if (asset.type === 'video') {
                media.preload = 'metadata';
                media.muted = true;
                media.playsInline = true;
            }

            const removeButton = document.createElement('button');
            removeButton.type = 'button';
            removeButton.className = 'mobile-media-remove';
            removeButton.dataset.removeMediaId = asset.id;
            removeButton.setAttribute('aria-label', '첨부 삭제');
            removeButton.textContent = '×';

            item.appendChild(media);
            item.appendChild(removeButton);
            mobileMediaPreview.appendChild(item);
        });
    };

    const registerMobileMedia = (type, payload) => {
        const id = `m_${Date.now()}_${mobileMediaSeq++}`;
        mobileMediaAssets.push({
            id,
            type,
            url: String(payload?.url || ''),
            name: String(payload?.name || ''),
        });
        renderMobileMediaPreview();
        return id;
    };

    const bootstrapMobileContent = () => {
        if (!isMobileComposer) {
            return;
        }

        let value = String(textarea.value || '');
        if (value.trim() === '') {
            return;
        }

        const temp = document.createElement('div');
        temp.innerHTML = value;

        temp.querySelectorAll('img[src]').forEach((node) => {
            registerMobileMedia('image', {
                url: node.getAttribute('src') || '',
                name: node.getAttribute('alt') || 'image',
            });
            node.remove();
        });

        temp.querySelectorAll('video[src]').forEach((node) => {
            registerMobileMedia('video', {
                url: node.getAttribute('src') || '',
                name: 'video',
            });
            node.remove();
        });

        temp.querySelectorAll('video source[src]').forEach((node) => {
            registerMobileMedia('video', {
                url: node.getAttribute('src') || '',
                name: 'video',
            });
            const parentVideo = node.closest('video');
            if (parentVideo) {
                parentVideo.remove();
            }
        });

        value = temp.textContent || '';
        value = value
            .replace(/\n{3,}/g, '\n\n')
            .trim();

        textarea.value = value;
    };

    if (mobileMediaPreview) {
        mobileMediaPreview.addEventListener('click', (event) => {
            const removeButton = event.target.closest('[data-remove-media-id]');
            if (!removeButton) {
                return;
            }

            const mediaId = removeButton.dataset.removeMediaId;
            const index = mobileMediaAssets.findIndex((asset) => asset.id === mediaId);
            if (index >= 0) {
                mobileMediaAssets.splice(index, 1);
                renderMobileMediaPreview();
            }
        });
    }

    bootstrapMobileContent();

    if (!isMobileComposer && typeof window.jQuery !== 'undefined') {
        window.jQuery(textarea).summernote({
            lang: 'ko-KR',
            height: 340,
            placeholder: '내용',
            toolbar: [
                ['style', ['style']],
                ['font', ['bold', 'italic', 'underline', 'clear']],
                ['fontname', ['fontname']],
                ['fontsize', ['fontsize']],
                ['color', ['color']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['insert', ['link', 'picture']],
                ['view', ['codeview']],
            ],
            callbacks: {
                onImageUpload: async function (files) {
                    for (const file of Array.from(files || [])) {
                        try {
                            const payload = await uploadEditorImage(file);
                            window.jQuery(textarea).summernote('insertImage', payload.url, payload.name || file.name || 'image');
                        } catch (error) {
                            alert(error?.message || '이미지 업로드에 실패했습니다.');
                        }
                    }
                },
            },
        });
    }

    if (isMobileComposer && mobileImageButton && mobileImageInput) {
        mobileImageButton.addEventListener('click', function () {
            mobileImageInput.click();
        });

        mobileImageInput.addEventListener('change', async function () {
            const files = Array.from(mobileImageInput.files || []);
            if (!files.length) {
                return;
            }

            for (const file of files) {
                try {
                    const payload = await uploadEditorImage(file);
                    registerMobileMedia('image', {
                        url: payload.url,
                        name: payload.name || file.name || 'image',
                    });
                } catch (error) {
                    alert(error?.message || '이미지 업로드에 실패했습니다.');
                }
            }

            mobileImageInput.value = '';
        });
    }

    if (isMobileComposer && mobileVideoButton && mobileVideoInput) {
        mobileVideoButton.addEventListener('click', function () {
            mobileVideoInput.click();
        });

        mobileVideoInput.addEventListener('change', async function () {
            const files = Array.from(mobileVideoInput.files || []);
            if (!files.length) {
                return;
            }

            for (const file of files) {
                try {
                    const payload = await uploadEditorVideo(file);
                    registerMobileMedia('video', {
                        url: payload.url,
                        name: payload.name || file.name || 'video',
                    });
                } catch (error) {
                    alert(error?.message || '영상 업로드에 실패했습니다.');
                }
            }

            mobileVideoInput.value = '';
        });
    }

    form.addEventListener('submit', function (event) {
        let html = textarea.value || '';

        if (!isMobileComposer && typeof window.jQuery !== 'undefined') {
            html = window.jQuery(textarea).summernote('code') || '';
        } else {
            const textHtml = escapeHtml(html)
                .replace(/\n{2,}/g, '</p><p>')
                .replace(/\n/g, '<br>');

            const mediaHtml = mobileMediaAssets
                .map((asset) => {
                    if (!asset.url) {
                        return '';
                    }

                    if (asset.type === 'video') {
                        return `<p><video controls playsinline preload="metadata" src="${escapeHtml(asset.url)}"></video></p>`;
                    }

                    return `<p><img src="${escapeHtml(asset.url)}" alt="${escapeHtml(asset.name || 'image')}"></p>`;
                })
                .filter(Boolean)
                .join('');

            const normalizedText = textHtml.trim() !== '' ? `<p>${textHtml}</p>` : '';
            html = `${normalizedText}${mediaHtml}`;
            textarea.value = html;
        }

        const normalized = html
            .replace(/<img\b[^>]*>/gi, ' image ')
            .replace(/<video\b[^>]*>[\s\S]*?<\/video>/gi, ' video ')
            .replace(/<video\b[^>]*>/gi, ' video ')
            .replace(/<[^>]+>/g, ' ')
            .replace(/&nbsp;/gi, ' ')
            .trim();

        if (normalized === '') {
            event.preventDefault();
            alert('내용을 입력해 주세요.');
        }
    });
})();
</script>
</body>
</html>
