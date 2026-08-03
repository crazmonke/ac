@php
    $template = $template ?? null;
    $initialQuestions = old('questions', $template?->questions ?? []);
    // old() 값은 문자열 기반이므로 boolean/배열 형태를 JS에서 관대하게 처리한다.
    $selectedSlugs = collect(old('board_slugs', $template?->board_slugs ?? []))->map(fn ($s) => (string) $s)->all();
@endphp

@if($errors->any())
    <div style="padding: 12px 16px; background: #fdecea; border: 1px solid #a61b1b; border-radius: 8px; color: #a61b1b; margin-bottom: 16px;">
        <ul style="margin: 0; padding-left: 18px;">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div style="background: #fff; border: 1px solid #e0e6ed; border-radius: 8px; padding: 20px; display: grid; gap: 16px;">
    <label style="display: block;">
        <span style="font-weight: 600; color: #15243a;">템플릿 이름 *</span>
        <input name="name" value="{{ old('name', $template->name ?? '') }}" required maxlength="100" placeholder="예: 우리 단지 주차 팁" style="width: 100%; margin-top: 6px;">
    </label>

    <label style="display: block;">
        <span style="font-weight: 600; color: #15243a;">템플릿 설명</span>
        <input name="description" value="{{ old('description', $template->description ?? '') }}" maxlength="255" placeholder="템플릿 선택 화면에 보여줄 짧은 설명" style="width: 100%; margin-top: 6px;">
    </label>

    <label style="display: block;">
        <span style="font-weight: 600; color: #15243a;">제목 생성 규칙 *</span>
        <input name="title_template" value="{{ old('title_template', $template->title_template ?? '') }}" required maxlength="160" placeholder='예: "{q1}"에 대한 우리 단지 주차 팁' style="width: 100%; margin-top: 6px;">
        <span style="font-size: 0.85rem; color: #607086;">{q1}~{q10} 위치에 해당 질문의 답변이 들어갑니다. placeholder가 없으면 고정 제목이 됩니다.</span>
    </label>

    <div>
        <span style="font-weight: 600; color: #15243a;">사용 가능 게시판</span>
        <div style="display: flex; flex-wrap: wrap; gap: 12px; margin-top: 8px;">
            @foreach($boardOptions as $option)
                <label style="display: inline-flex; align-items: center; gap: 6px; font-weight: normal;">
                    <input type="checkbox" name="board_slugs[]" value="{{ $option['slug'] }}" style="width: auto;" @checked(in_array($option['slug'], $selectedSlugs, true))>
                    {{ $option['name'] }}
                </label>
            @endforeach
        </div>
        <span style="font-size: 0.85rem; color: #607086;">아무것도 선택하지 않으면 모든 게시판에서 사용할 수 있습니다.</span>
    </div>

    <div style="display: flex; gap: 24px; align-items: center;">
        <label style="display: inline-flex; align-items: center; gap: 6px;">
            <span style="font-weight: 600; color: #15243a;">정렬순서</span>
            <input type="number" name="sort_order" value="{{ old('sort_order', $template->sort_order ?? 0) }}" style="width: 90px;">
        </label>
        <label style="display: inline-flex; align-items: center; gap: 6px;">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" style="width: auto;" @checked((bool) old('is_active', $template->is_active ?? true))>
            <span style="font-weight: 600; color: #15243a;">활성</span>
        </label>
    </div>
</div>

<div style="margin-top: 24px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
        <h2 style="margin: 0; font-size: 1.1rem;">단계별 질문 (최대 {{ \App\Models\PostTemplate::MAX_QUESTIONS }}개)</h2>
        <button type="button" id="pt-add-question" class="btn btn-primary">질문 추가</button>
    </div>
    <div id="pt-question-list" style="display: grid; gap: 16px;"></div>
</div>

<template id="pt-question-tpl">
    <div class="pt-question" style="background: #fff; border: 1px solid #e0e6ed; border-radius: 8px; padding: 16px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
            <div>
                <strong class="pt-q-title">질문</strong>
                <span class="pt-q-key-badge" style="display: none; margin-left: 8px; padding: 2px 8px; background: #eef2ff; color: #3949ab; border-radius: 4px; font-size: 0.8rem;"></span>
            </div>
            <div style="display: flex; gap: 6px;">
                <button type="button" class="pt-q-up btn" title="위로">↑</button>
                <button type="button" class="pt-q-down btn" title="아래로">↓</button>
                <button type="button" class="pt-q-remove btn" style="color: #a61b1b;">삭제</button>
            </div>
        </div>
        <input type="hidden" class="pt-f-key">
        <div style="display: grid; gap: 10px;">
            <label style="display: block;">
                <span style="font-weight: 600; font-size: 0.9rem;">질문 내용 *</span>
                <input class="pt-f-label" required maxlength="255" placeholder="예: 주로 언제 주차 자리가 부족한가요?" style="width: 100%; margin-top: 4px;">
            </label>
            <div style="display: flex; gap: 16px; align-items: center; flex-wrap: wrap;">
                <label style="display: inline-flex; align-items: center; gap: 6px;">
                    <span style="font-weight: 600; font-size: 0.9rem;">답변 형식</span>
                    <select class="pt-f-type">
                        <option value="single">단일 선택</option>
                        <option value="multiple">다중 선택</option>
                        <option value="text">짧은 주관식</option>
                        <option value="yes_no">예/아니오</option>
                    </select>
                </label>
                <label style="display: inline-flex; align-items: center; gap: 6px;">
                    <input type="checkbox" class="pt-f-required" style="width: auto;"> <span style="font-size: 0.9rem;">필수 답변</span>
                </label>
                <label class="pt-maxlen-wrap" style="display: none; align-items: center; gap: 6px;">
                    <span style="font-size: 0.9rem;">최대 글자 수</span>
                    <input type="number" class="pt-f-maxlen" min="1" max="1000" style="width: 90px;">
                </label>
            </div>
            <label style="display: block;">
                <span style="font-weight: 600; font-size: 0.9rem;">본문 문장 규칙</span>
                <input class="pt-f-format" maxlength="500" placeholder="예: 우리 단지는 {answer}에 주차 자리가 가장 부족해요." style="width: 100%; margin-top: 4px;">
                <span style="font-size: 0.8rem; color: #607086;">{answer} 위치에 답변이 삽입됩니다. 비우면 아래 선택지별 문장만 사용됩니다.</span>
            </label>
            <div class="pt-options-wrap" style="display: none;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-weight: 600; font-size: 0.9rem;">선택지</span>
                    <button type="button" class="pt-opt-add btn" style="font-size: 0.85rem;">선택지 추가</button>
                </div>
                <div class="pt-option-list" style="display: grid; gap: 6px; margin-top: 6px;"></div>
                <span style="font-size: 0.8rem; color: #607086;">각 선택지의 '조건부 문장'은 사용자가 그 선택지를 골랐을 때만 본문에 덧붙습니다.</span>
            </div>
            <div class="pt-yesno-wrap" style="display: none; grid-template-columns: 1fr; gap: 6px;">
                <label style="display: block; font-size: 0.9rem;">
                    "예" 선택 시 문장
                    <input class="pt-f-yes" maxlength="500" placeholder="예: 손세차장이 있어서 편리해요." style="width: 100%; margin-top: 4px;">
                </label>
                <label style="display: block; font-size: 0.9rem;">
                    "아니오" 선택 시 문장
                    <input class="pt-f-no" maxlength="500" placeholder="예: 아쉽게도 손세차장은 없어요." style="width: 100%; margin-top: 4px;">
                </label>
            </div>
        </div>
    </div>
</template>

<template id="pt-option-tpl">
    <div class="pt-option" style="display: flex; gap: 6px; align-items: center;">
        <input class="pt-o-label" maxlength="100" placeholder="선택지 (예: 평일 저녁)" style="flex: 0 0 32%;">
        <input class="pt-o-sentence" maxlength="500" placeholder="조건부 문장 (선택)" style="flex: 1;">
        <button type="button" class="pt-o-remove btn" style="color: #a61b1b;">✕</button>
    </div>
</template>

<script>
(function () {
    var MAX_QUESTIONS = {{ \App\Models\PostTemplate::MAX_QUESTIONS }};
    var initial = @json($initialQuestions);
    var list = document.getElementById('pt-question-list');
    var addBtn = document.getElementById('pt-add-question');
    var qTpl = document.getElementById('pt-question-tpl');
    var oTpl = document.getElementById('pt-option-tpl');

    function addOption(block, data) {
        var optionList = block.querySelector('.pt-option-list');
        var node = oTpl.content.firstElementChild.cloneNode(true);
        node.querySelector('.pt-o-label').value = (data && data.label) || '';
        node.querySelector('.pt-o-sentence').value = (data && data.sentence) || '';
        node.querySelector('.pt-o-remove').addEventListener('click', function () {
            node.remove();
            renumber();
        });
        optionList.appendChild(node);
    }

    function applyType(block) {
        var type = block.querySelector('.pt-f-type').value;
        block.querySelector('.pt-options-wrap').style.display = (type === 'single' || type === 'multiple') ? 'block' : 'none';
        block.querySelector('.pt-yesno-wrap').style.display = (type === 'yes_no') ? 'grid' : 'none';
        block.querySelector('.pt-maxlen-wrap').style.display = (type === 'text') ? 'inline-flex' : 'none';
        if ((type === 'single' || type === 'multiple') && block.querySelectorAll('.pt-option').length === 0) {
            addOption(block); addOption(block);
        }
        renumber();
    }

    function addQuestion(data) {
        if (list.children.length >= MAX_QUESTIONS) return;
        data = data || {};
        var block = qTpl.content.firstElementChild.cloneNode(true);
        block.querySelector('.pt-f-key').value = data.key || '';
        block.querySelector('.pt-f-label').value = data.label || '';
        block.querySelector('.pt-f-type').value = data.type || 'single';
        block.querySelector('.pt-f-required').checked = data.required === true || data.required === '1' || data.required === 1;
        block.querySelector('.pt-f-format').value = data.output_format || '';
        block.querySelector('.pt-f-maxlen').value = data.max_length || '';

        var options = Array.isArray(data.options) ? data.options : Object.values(data.options || {});
        if ((data.type || 'single') === 'yes_no') {
            block.querySelector('.pt-f-yes').value = (options[0] && options[0].sentence) || '';
            block.querySelector('.pt-f-no').value = (options[1] && options[1].sentence) || '';
        } else {
            options.forEach(function (option) { addOption(block, option); });
        }

        block.querySelector('.pt-f-type').addEventListener('change', function () { applyType(block); });
        block.querySelector('.pt-opt-add').addEventListener('click', function () { addOption(block); renumber(); });
        block.querySelector('.pt-q-remove').addEventListener('click', function () {
            if (!confirm('이 질문을 삭제할까요?')) return;
            block.remove();
            renumber();
        });
        block.querySelector('.pt-q-up').addEventListener('click', function () {
            if (block.previousElementSibling) list.insertBefore(block, block.previousElementSibling);
            renumber();
        });
        block.querySelector('.pt-q-down').addEventListener('click', function () {
            if (block.nextElementSibling) list.insertBefore(block.nextElementSibling, block);
            renumber();
        });

        list.appendChild(block);
        applyType(block);
    }

    // name 속성은 제출 직전이 아니라 renumber 시점마다 갱신해 서버 검증 에러 표시와 순서를 일치시킨다.
    function renumber() {
        Array.prototype.forEach.call(list.children, function (block, qi) {
            block.querySelector('.pt-q-title').textContent = (qi + 1) + '번 질문';
            var key = block.querySelector('.pt-f-key').value;
            var badge = block.querySelector('.pt-q-key-badge');
            badge.style.display = key ? 'inline-block' : 'none';
            badge.textContent = key ? '제목 placeholder: {' + key + '}' : '';

            var base = 'questions[' + qi + ']';
            block.querySelector('.pt-f-key').name = base + '[key]';
            block.querySelector('.pt-f-label').name = base + '[label]';
            block.querySelector('.pt-f-type').name = base + '[type]';
            block.querySelector('.pt-f-required').name = base + '[required]';
            block.querySelector('.pt-f-required').value = '1';
            block.querySelector('.pt-f-format').name = base + '[output_format]';
            block.querySelector('.pt-f-maxlen').name = base + '[max_length]';

            var type = block.querySelector('.pt-f-type').value;
            if (type === 'yes_no') {
                block.querySelector('.pt-f-yes').name = base + '[options][0][sentence]';
                block.querySelector('.pt-f-no').name = base + '[options][1][sentence]';
            } else {
                block.querySelector('.pt-f-yes').removeAttribute('name');
                block.querySelector('.pt-f-no').removeAttribute('name');
            }
            Array.prototype.forEach.call(block.querySelectorAll('.pt-option'), function (optionRow, oi) {
                var enabled = (type === 'single' || type === 'multiple');
                if (enabled) {
                    optionRow.querySelector('.pt-o-label').name = base + '[options][' + oi + '][label]';
                    optionRow.querySelector('.pt-o-sentence').name = base + '[options][' + oi + '][sentence]';
                } else {
                    optionRow.querySelector('.pt-o-label').removeAttribute('name');
                    optionRow.querySelector('.pt-o-sentence').removeAttribute('name');
                }
            });
        });
        addBtn.disabled = list.children.length >= MAX_QUESTIONS;
        addBtn.textContent = list.children.length >= MAX_QUESTIONS ? '질문 최대 개수 도달' : '질문 추가';
    }

    addBtn.addEventListener('click', function () { addQuestion(); });

    var initialArray = Array.isArray(initial) ? initial : Object.values(initial || {});
    if (initialArray.length === 0) {
        addQuestion();
    } else {
        initialArray.forEach(function (question) { addQuestion(question); });
    }
    renumber();
})();
</script>
