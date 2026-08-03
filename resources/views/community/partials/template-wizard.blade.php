{{--
    설문조사형 게시글 템플릿 위저드 (post-create / post-edit 공용)

    필요 변수:
    - $wizardTemplates: [['id','name','description','questions'], ...] (create: 목록, edit: 해당 템플릿 1개)
    - $wizardMode: 'create' | 'edit'
    - $wizardInitialAnswers: edit 시 기존 답변 배열 (create는 null)
    - $wizardFixedTemplateId: edit 시 템플릿 id (create는 null)
    - $board, $apartmentId
--}}
<style>
    .pt-modal { position: fixed; inset: 0; z-index: 95; display: none; align-items: center; justify-content: center; background: rgba(15, 20, 29, 0.48); padding: 14px; }
    .pt-modal.open { display: flex; }
    .pt-sheet { width: min(640px, 100%); max-height: min(86vh, 760px); overflow: auto; border-radius: 18px; background: #fff; border: 1px solid #d9e3ef; padding: 18px; }
    .pt-head { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 12px; }
    .pt-title { margin: 0; font-size: 1.05rem; font-weight: 900; color: #1c2d44; }
    .pt-close { border: 1px solid #cfd8e6; border-radius: 10px; background: #eef3f9; color: #20324b; padding: 6px 12px; font-weight: 700; cursor: pointer; }
    .pt-view { display: none; }
    .pt-view.active { display: block; }
    .pt-card { display: block; width: 100%; text-align: left; border: 1px solid #d9e3ef; border-radius: 14px; background: #f8fafd; padding: 14px; margin-bottom: 10px; cursor: pointer; font: inherit; }
    .pt-card:hover { border-color: #2f52b8; background: #eef3fc; }
    .pt-card strong { display: block; color: #1c2d44; }
    .pt-card span { display: block; margin-top: 4px; color: #607086; font-size: 0.88rem; }
    .pt-progress { margin-bottom: 14px; }
    .pt-step-label { font-size: 0.9rem; font-weight: 800; color: #2f52b8; }
    .pt-progress-track { margin-top: 6px; height: 6px; border-radius: 999px; background: #e4eaf3; overflow: hidden; }
    .pt-progress-fill { height: 100%; border-radius: 999px; background: #2f52b8; transition: width 0.2s ease; }
    .pt-q-label { font-size: 1.02rem; font-weight: 800; color: #18283d; margin: 0 0 12px; }
    .pt-q-required { color: #c0392b; }
    .pt-choice { display: flex; align-items: flex-start; gap: 8px; border: 1px solid #d9e3ef; border-radius: 12px; padding: 11px 12px; margin-bottom: 8px; cursor: pointer; background: #fff; }
    .pt-choice:hover { border-color: #2f52b8; }
    .pt-choice input { width: auto; margin-top: 3px; }
    .pt-nav { display: flex; justify-content: space-between; gap: 8px; margin-top: 16px; }
    .pt-nav button { border-radius: 12px; padding: 11px 18px; font-weight: 800; cursor: pointer; border: 1px solid #cfd8e6; background: #eef3f9; color: #20324b; }
    .pt-nav button.pt-primary { background: #2f52b8; border-color: #2f52b8; color: #fff; }
    .pt-nav button:disabled { opacity: 0.45; cursor: default; }
    .pt-error { color: #c0392b; font-size: 0.88rem; margin-top: 8px; display: none; }
    .pt-preview-box { border: 1px solid #d9e3ef; border-radius: 14px; padding: 14px; background: #f8fafd; }
    .pt-preview-title { margin: 0 0 10px; font-size: 1.05rem; color: #18283d; }
    .pt-preview-body p { margin: 0 0 10px; line-height: 1.6; color: #22344d; }
    .pt-preview-note { font-size: 0.85rem; color: #607086; margin-top: 10px; }
</style>

<div class="pt-modal" id="pt-wizard-modal" aria-hidden="true">
    <div class="pt-sheet" role="dialog" aria-modal="true" aria-label="템플릿으로 작성하기">
        <div class="pt-head">
            <h2 class="pt-title js-pt-title">템플릿으로 작성하기</h2>
            <button type="button" class="pt-close js-pt-close">닫기</button>
        </div>
        <div class="pt-view js-pt-view-list">
            <p class="meta" style="margin-top:0;">상황에 맞는 템플릿을 선택하면 몇 가지 질문에 답하는 것만으로 게시글이 완성됩니다.</p>
            <div class="js-pt-template-list"></div>
        </div>
        <div class="pt-view js-pt-view-wizard">
            <div class="pt-progress">
                <span class="pt-step-label js-pt-step-label"></span>
                <div class="pt-progress-track"><div class="pt-progress-fill js-pt-progress-fill" style="width:0%"></div></div>
            </div>
            <div class="js-pt-question"></div>
            <p class="pt-error js-pt-error"></p>
            <div class="pt-nav">
                <button type="button" class="js-pt-prev">이전 단계</button>
                <button type="button" class="pt-primary js-pt-next">다음 단계</button>
            </div>
        </div>
        <div class="pt-view js-pt-view-preview">
            <div class="pt-preview-box">
                <h3 class="pt-preview-title js-pt-preview-title"></h3>
                <div class="pt-preview-body js-pt-preview-body"></div>
            </div>
            <p class="pt-preview-note">내용을 바꾸고 싶다면 "답변 수정"으로 돌아가 답변을 고쳐 주세요.</p>
            <p class="pt-error js-pt-preview-error"></p>
            <div class="pt-nav">
                <button type="button" class="js-pt-back">답변 수정</button>
                <button type="button" class="pt-primary js-pt-publish">{{ ($wizardMode ?? 'create') === 'edit' ? '수정' : '작성' }}</button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var config = {
        mode: @json($wizardMode ?? 'create'),
        templates: @json($wizardTemplates ?? []),
        initialAnswers: @json($wizardInitialAnswers ?? null),
        fixedTemplateId: @json($wizardFixedTemplateId ?? null),
        userId: @json(auth()->id()),
        boardId: @json($board->id ?? ($post->board_id ?? 0)),
        draftTtlMs: 7 * 24 * 60 * 60 * 1000
    };

    var form = document.querySelector('.js-smarteditor-form');
    var modal = document.getElementById('pt-wizard-modal');
    var openButtons = document.querySelectorAll('.js-template-open');
    if (!form || !modal || !config.templates.length) { return; }

    var views = {
        list: modal.querySelector('.js-pt-view-list'),
        wizard: modal.querySelector('.js-pt-view-wizard'),
        preview: modal.querySelector('.js-pt-view-preview')
    };
    var titleEl = modal.querySelector('.js-pt-title');
    var listEl = modal.querySelector('.js-pt-template-list');
    var stepLabel = modal.querySelector('.js-pt-step-label');
    var progressFill = modal.querySelector('.js-pt-progress-fill');
    var questionEl = modal.querySelector('.js-pt-question');
    var errorEl = modal.querySelector('.js-pt-error');
    var previewTitleEl = modal.querySelector('.js-pt-preview-title');
    var previewBodyEl = modal.querySelector('.js-pt-preview-body');
    var previewErrorEl = modal.querySelector('.js-pt-preview-error');

    var current = { template: null, step: 0, answers: {}, previewAnswers: null };

    function csrfToken() {
        var input = form.querySelector('input[name="_token"]');
        return input ? input.value : '';
    }

    function draftKey(templateId) {
        return 'pt_draft:' + config.userId + ':' + config.boardId + ':' + templateId;
    }

    function loadDraft(templateId) {
        try {
            var raw = localStorage.getItem(draftKey(templateId));
            if (!raw) return null;
            var draft = JSON.parse(raw);
            if (!draft || !draft.savedAt || (Date.now() - draft.savedAt) > config.draftTtlMs) {
                localStorage.removeItem(draftKey(templateId));
                return null;
            }
            return draft;
        } catch (e) { return null; }
    }

    function saveDraft() {
        if (config.mode !== 'create' || !current.template) return;
        try {
            localStorage.setItem(draftKey(current.template.id), JSON.stringify({
                answers: current.answers,
                step: current.step,
                savedAt: Date.now()
            }));
        } catch (e) { /* localStorage 사용 불가 환경은 무시 */ }
    }

    function clearDraft() {
        if (!current.template) return;
        try { localStorage.removeItem(draftKey(current.template.id)); } catch (e) {}
    }

    function show(view) {
        Object.keys(views).forEach(function (key) {
            views[key].classList.toggle('active', key === view);
        });
    }

    function openModal() { modal.classList.add('open'); modal.setAttribute('aria-hidden', 'false'); }
    function closeModal() { modal.classList.remove('open'); modal.setAttribute('aria-hidden', 'true'); }

    function questionOptions(question) {
        if (question.type === 'yes_no') {
            return (question.options && question.options.length === 2)
                ? question.options
                : [{ label: '예' }, { label: '아니오' }];
        }
        return question.options || [];
    }

    function renderQuestion() {
        var questions = current.template.questions;
        var question = questions[current.step];
        var answer = current.answers[question.key];
        stepLabel.textContent = (current.step + 1) + '단계 / ' + questions.length + '단계';
        progressFill.style.width = Math.round(((current.step + 1) / questions.length) * 100) + '%';
        errorEl.style.display = 'none';

        var html = '<p class="pt-q-label">' + escapeText(question.label)
            + (question.required ? ' <span class="pt-q-required">*</span>' : '') + '</p>';

        if (question.type === 'text') {
            var maxAttr = question.max_length ? ' maxlength="' + question.max_length + '"' : '';
            html += '<textarea class="js-pt-input" rows="3"' + maxAttr + ' placeholder="답변을 입력해 주세요.">' + escapeText(answer || '') + '</textarea>';
            if (question.max_length) {
                html += '<p class="meta" style="margin:6px 0 0;">최대 ' + question.max_length + '자</p>';
            }
        } else {
            var multiple = question.type === 'multiple';
            var selected = multiple ? (Array.isArray(answer) ? answer : []) : [answer];
            questionOptions(question).forEach(function (option, index) {
                var checked = selected.indexOf(option.label) !== -1 ? ' checked' : '';
                html += '<label class="pt-choice">'
                    + '<input type="' + (multiple ? 'checkbox' : 'radio') + '" name="pt-answer" value="' + escapeAttr(option.label) + '"' + checked + '>'
                    + '<span>' + escapeText(option.label) + '</span>'
                    + '</label>';
            });
        }

        questionEl.innerHTML = html;
        var prevBtn = modal.querySelector('.js-pt-prev');
        prevBtn.disabled = current.step === 0 && (config.mode === 'edit' || config.templates.length === 1);
        prevBtn.textContent = current.step === 0 ? '템플릿 다시 선택' : '이전 단계';
        modal.querySelector('.js-pt-next').textContent =
            current.step === questions.length - 1 ? '미리보기' : '다음 단계';
        show('wizard');
    }

    function collectAnswer() {
        var question = current.template.questions[current.step];
        if (question.type === 'text') {
            var input = questionEl.querySelector('.js-pt-input');
            var value = input ? input.value.trim() : '';
            if (value) { current.answers[question.key] = value; } else { delete current.answers[question.key]; }
            return value;
        }
        var checked = Array.prototype.map.call(
            questionEl.querySelectorAll('input[name="pt-answer"]:checked'),
            function (input) { return input.value; }
        );
        if (question.type === 'multiple') {
            if (checked.length) { current.answers[question.key] = checked; } else { delete current.answers[question.key]; }
            return checked.length ? checked : '';
        }
        if (checked.length) { current.answers[question.key] = checked[0]; } else { delete current.answers[question.key]; }
        return checked[0] || '';
    }

    function startTemplate(template, restored) {
        current.template = template;
        current.step = restored ? Math.min(restored.step || 0, template.questions.length - 1) : 0;
        current.answers = restored ? (restored.answers || {}) : {};
        titleEl.textContent = template.name;
        renderQuestion();
    }

    function renderList() {
        listEl.innerHTML = '';
        config.templates.forEach(function (template) {
            var button = document.createElement('button');
            button.type = 'button';
            button.className = 'pt-card';
            button.innerHTML = '<strong>' + escapeText(template.name) + '</strong>'
                + (template.description ? '<span>' + escapeText(template.description) + '</span>' : '')
                + '<span>' + template.questions.length + '개의 질문</span>';
            button.addEventListener('click', function () { selectTemplate(template); });
            listEl.appendChild(button);
        });
        titleEl.textContent = '템플릿으로 작성하기';
        show('list');
    }

    function selectTemplate(template) {
        var restored = null;
        if (config.mode === 'create') {
            var draft = loadDraft(template.id);
            if (draft && Object.keys(draft.answers || {}).length) {
                if (confirm('작성 중이던 답변이 있습니다. 이어서 작성할까요?')) {
                    restored = draft;
                } else {
                    try { localStorage.removeItem(draftKey(template.id)); } catch (e) {}
                }
            }
        } else if (config.initialAnswers) {
            restored = { answers: config.initialAnswers, step: 0 };
        }
        startTemplate(template, restored);
    }

    function requestPreview() {
        var nextBtn = modal.querySelector('.js-pt-next');
        nextBtn.disabled = true;
        fetch('/community/post-templates/' + current.template.id + '/preview', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken()
            },
            body: JSON.stringify({ answers: current.answers })
        }).then(function (response) {
            return response.json().then(function (payload) { return { ok: response.ok, payload: payload }; });
        }).then(function (result) {
            nextBtn.disabled = false;
            if (!result.ok) {
                var message = result.payload && result.payload.message ? result.payload.message : '미리보기 생성에 실패했습니다.';
                errorEl.textContent = message;
                errorEl.style.display = 'block';
                return;
            }
            current.previewAnswers = result.payload.answers || current.answers;
            previewTitleEl.textContent = result.payload.title;
            previewBodyEl.innerHTML = result.payload.body_html;
            previewErrorEl.style.display = 'none';
            titleEl.textContent = '미리보기';
            show('preview');
        }).catch(function () {
            nextBtn.disabled = false;
            errorEl.textContent = '네트워크 오류로 미리보기를 만들지 못했습니다.';
            errorEl.style.display = 'block';
        });
    }

    function publish() {
        var templateInput = form.querySelector('input[name="post_template_id"]');
        var answersInput = form.querySelector('input[name="template_answers"]');
        if (!templateInput || !answersInput) return;
        templateInput.value = current.template.id;
        answersInput.value = JSON.stringify(current.previewAnswers || current.answers);
        clearDraft();
        // 네이티브 submit: 본문 에디터 검증(빈 내용 차단)을 우회한다 — 제목/본문은 서버가 답변으로 생성.
        HTMLFormElement.prototype.submit.call(form);
    }

    modal.querySelector('.js-pt-close').addEventListener('click', closeModal);
    modal.addEventListener('click', function (event) {
        if (event.target === modal) closeModal();
    });

    modal.querySelector('.js-pt-prev').addEventListener('click', function () {
        collectAnswer();
        saveDraft();
        if (current.step === 0) {
            if (config.mode === 'create' && config.templates.length > 1) renderList();
            return;
        }
        current.step -= 1;
        renderQuestion();
    });

    modal.querySelector('.js-pt-next').addEventListener('click', function () {
        var question = current.template.questions[current.step];
        var value = collectAnswer();
        var empty = !value || (Array.isArray(value) && !value.length);
        if (question.required && empty) {
            errorEl.textContent = '답변을 입력해 주세요.';
            errorEl.style.display = 'block';
            return;
        }
        saveDraft();
        if (current.step >= current.template.questions.length - 1) {
            requestPreview();
            return;
        }
        current.step += 1;
        renderQuestion();
    });

    modal.querySelector('.js-pt-back').addEventListener('click', function () {
        titleEl.textContent = current.template.name;
        renderQuestion();
    });

    modal.querySelector('.js-pt-publish').addEventListener('click', publish);

    // 답변 입력 시마다 임시 저장
    questionEl.addEventListener('change', function () { collectAnswer(); saveDraft(); });
    questionEl.addEventListener('input', function () { collectAnswer(); saveDraft(); });

    Array.prototype.forEach.call(openButtons, function (button) {
        button.addEventListener('click', function () {
            openModal();
            if (config.mode === 'edit' || config.templates.length === 1) {
                if (!current.template) {
                    selectTemplate(config.mode === 'edit' && config.fixedTemplateId
                        ? config.templates.filter(function (t) { return t.id === config.fixedTemplateId; })[0] || config.templates[0]
                        : config.templates[0]);
                } else {
                    renderQuestion();
                }
            } else if (!current.template) {
                renderList();
            } else {
                renderQuestion();
            }
        });
    });

    function escapeText(value) {
        var div = document.createElement('div');
        div.textContent = value == null ? '' : String(value);
        return div.innerHTML;
    }

    function escapeAttr(value) {
        return escapeText(value).replace(/"/g, '&quot;');
    }
})();
</script>
