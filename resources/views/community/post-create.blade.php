<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>새 글 작성</title>
    <style>
        :root {
            --bg: #f5f7fb;
            --card: #ffffff;
            --ink: #18283d;
            --muted: #607086;
            --line: #dde5ef;
            --brand: #2f52b8;
        }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; background: var(--bg); color: var(--ink); }
        .wrap { max-width: 740px; margin: 0 auto; padding: 12px; }
        .card { background: var(--card); border: 1px solid var(--line); border-radius: 18px; padding: 14px; margin-bottom: 12px; }
        .meta { color: var(--muted); font-size: 0.88rem; }
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
        input, textarea { width: 100%; border: 1px solid #c7d8ea; border-radius: 14px; padding: 12px; font: inherit; background: #fff; }
        .form-select {
            width: 100%;
            border: 1px solid #c7d8ea;
            border-radius: 14px;
            padding: 12px 42px 12px 12px;
            font: inherit;
            color: #18283d;
            background-color: #fff;
            appearance: none;
            -webkit-appearance: none;
            background-image: linear-gradient(45deg, transparent 50%, #61748f 50%), linear-gradient(135deg, #61748f 50%, transparent 50%);
            background-position: calc(100% - 18px) 50%, calc(100% - 12px) 50%;
            background-size: 6px 6px, 6px 6px;
            background-repeat: no-repeat;
            transition: border-color 0.18s ease, box-shadow 0.18s ease;
        }
        .form-select:focus {
            outline: none;
            border-color: #9eb8df;
            box-shadow: 0 0 0 3px rgba(47, 82, 184, 0.12);
        }
        textarea { min-height: 180px; }
        button, a.btn { border: 0; border-radius: 999px; background: var(--brand); color: #fff; padding: 10px 14px; font-weight: 800; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; }
        a.btn.secondary { background: #dde7f3; color: #20324b; }
        .actions { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 12px; }
        .grid { display: grid; gap: 10px; }
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

        @media (max-width: 768px) {
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
    @php($communityScope = request('scope', ($board->apartment_id ? 'apartment' : 'region')))
    <section class="card">
        <p class="meta"><a class="back-chip" href="/community?scope={{ $communityScope }}&apartment_id={{ $apartmentId }}">← 커뮤니티로</a></p>
        <h1 style="margin-top:0;">새 글 작성</h1>
        <p class="meta">게시판: {{ $board->name }}</p>

        <form method="post" enctype="multipart/form-data" action="/community/boards/{{ $board->slug }}/posts?apartment_id={{ $apartmentId }}" class="js-smarteditor-form">
            @csrf
            <div class="grid">
                <input name="title" placeholder="제목" value="{{ old('title') }}" required>
                <div class="editor-shell">
                    <div class="editor-toolbar">
                        <div class="editor-toolbar-scroll js-editor-toolbar">
                            <button type="button" class="editor-tool accent labelled" data-editor-action="photo">사진</button>
                            <div class="editor-layer-wrap">
                                <button type="button" class="editor-tool layer-toggle" data-editor-toggle="heading" aria-haspopup="true" aria-expanded="false">
                                    <span class="js-heading-label">본문</span>
                                    <span class="editor-tool-caret">▾</span>
                                </button>
                                <div class="editor-layer" data-editor-layer="heading">
                                    <p class="editor-layer-title">제목 스타일</p>
                                    <div class="editor-style-grid">
                                        <button type="button" class="editor-style-option" data-editor-heading="p">
                                            <strong>본문</strong>
                                            <span>기본 문단으로 되돌리기</span>
                                        </button>
                                        <button type="button" class="editor-style-option" data-editor-heading="h2">
                                            <strong style="font-size:1.15rem;">H2 제목</strong>
                                            <span>큰 섹션 제목</span>
                                        </button>
                                        <button type="button" class="editor-style-option" data-editor-heading="h3">
                                            <strong style="font-size:1rem;">H3 소제목</strong>
                                            <span>작은 섹션 제목</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="editor-layer-wrap">
                                <button type="button" class="editor-tool layer-toggle" data-editor-toggle="fontsize" aria-haspopup="true" aria-expanded="false">
                                    <span class="js-fontsize-label">16px</span>
                                    <span class="editor-tool-caret">▾</span>
                                </button>
                                <div class="editor-layer" data-editor-layer="fontsize">
                                    <p class="editor-layer-title">글자 크기</p>
                                    <div class="editor-size-grid">
                                        <button type="button" class="editor-size-option" data-editor-size="12" data-size="12">12</button>
                                        <button type="button" class="editor-size-option" data-editor-size="14" data-size="14">14</button>
                                        <button type="button" class="editor-size-option" data-editor-size="16" data-size="16">16</button>
                                        <button type="button" class="editor-size-option" data-editor-size="18" data-size="18">18</button>
                                        <button type="button" class="editor-size-option" data-editor-size="24" data-size="24">24</button>
                                        <button type="button" class="editor-size-option" data-editor-size="32" data-size="32">32</button>
                                    </div>
                                </div>
                            </div>
                            <div class="editor-layer-wrap">
                                <button type="button" class="editor-tool layer-toggle" data-editor-toggle="textcolor" aria-haspopup="true" aria-expanded="false">
                                    <span class="editor-tool-swatch js-textcolor-swatch" style="--swatch:#20324b; background:#20324b;"></span>
                                    <span>글자색</span>
                                    <span class="editor-tool-caret">▾</span>
                                </button>
                                <div class="editor-layer" data-editor-layer="textcolor">
                                    <p class="editor-layer-title">글자색</p>
                                    <div class="editor-color-grid">
                                        <button type="button" class="editor-color-option" data-editor-color="#20324b" style="--swatch:#20324b;" aria-label="진한 남색"></button>
                                        <button type="button" class="editor-color-option" data-editor-color="#2452a3" style="--swatch:#2452a3;" aria-label="파랑"></button>
                                        <button type="button" class="editor-color-option" data-editor-color="#0f766e" style="--swatch:#0f766e;" aria-label="청록"></button>
                                        <button type="button" class="editor-color-option" data-editor-color="#c2410c" style="--swatch:#c2410c;" aria-label="주황"></button>
                                        <button type="button" class="editor-color-option" data-editor-color="#b91c1c" style="--swatch:#b91c1c;" aria-label="빨강"></button>
                                        <button type="button" class="editor-color-option" data-editor-color="#7c3aed" style="--swatch:#7c3aed;" aria-label="보라"></button>
                                        <button type="button" class="editor-color-option" data-editor-color="#be185d" style="--swatch:#be185d;" aria-label="핑크"></button>
                                        <button type="button" class="editor-color-option" data-editor-color="#15803d" style="--swatch:#15803d;" aria-label="초록"></button>
                                        <button type="button" class="editor-color-option" data-editor-color="#a16207" style="--swatch:#a16207;" aria-label="황토"></button>
                                        <button type="button" class="editor-color-option" data-editor-color="#111827" style="--swatch:#111827;" aria-label="검정"></button>
                                    </div>
                                    <div class="editor-layer-section editor-custom-color" data-editor-custom-color="text">
                                        <input type="color" value="#20324b" aria-label="글자색 직접 선택">
                                        <input type="text" value="#20324b" aria-label="글자색 HEX 입력" placeholder="#20324b">
                                        <button type="button" class="editor-tool" data-editor-custom-apply="text">적용</button>
                                    </div>
                                </div>
                            </div>
                            <div class="editor-layer-wrap">
                                <button type="button" class="editor-tool layer-toggle" data-editor-toggle="highlight" aria-haspopup="true" aria-expanded="false">
                                    <span class="editor-tool-swatch js-highlight-swatch" style="--swatch:#fef08a; background:#fef08a;"></span>
                                    <span>배경색</span>
                                    <span class="editor-tool-caret">▾</span>
                                </button>
                                <div class="editor-layer" data-editor-layer="highlight">
                                    <p class="editor-layer-title">배경색</p>
                                    <div class="editor-color-grid">
                                        <button type="button" class="editor-color-option" data-editor-bgcolor="#fef08a" style="--swatch:#fef08a;" aria-label="노랑"></button>
                                        <button type="button" class="editor-color-option" data-editor-bgcolor="#fed7aa" style="--swatch:#fed7aa;" aria-label="살구"></button>
                                        <button type="button" class="editor-color-option" data-editor-bgcolor="#fecdd3" style="--swatch:#fecdd3;" aria-label="연분홍"></button>
                                        <button type="button" class="editor-color-option" data-editor-bgcolor="#bfdbfe" style="--swatch:#bfdbfe;" aria-label="하늘"></button>
                                        <button type="button" class="editor-color-option" data-editor-bgcolor="#bbf7d0" style="--swatch:#bbf7d0;" aria-label="민트"></button>
                                        <button type="button" class="editor-color-option" data-editor-bgcolor="#ddd6fe" style="--swatch:#ddd6fe;" aria-label="라벤더"></button>
                                        <button type="button" class="editor-color-option" data-editor-bgcolor="#fde68a" style="--swatch:#fde68a;" aria-label="골드"></button>
                                        <button type="button" class="editor-color-option" data-editor-bgcolor="#e5e7eb" style="--swatch:#e5e7eb;" aria-label="회색"></button>
                                        <button type="button" class="editor-color-option" data-editor-bgcolor="#ffffff" style="--swatch:#ffffff;" aria-label="흰색"></button>
                                        <button type="button" class="editor-color-option" data-editor-bgcolor="transparent" style="--swatch:linear-gradient(135deg, #ffffff 0%, #ffffff 45%, #fca5a5 45%, #fca5a5 55%, #ffffff 55%, #ffffff 100%); background-image:linear-gradient(135deg, #ffffff 0%, #ffffff 45%, #fca5a5 45%, #fca5a5 55%, #ffffff 55%, #ffffff 100%);" aria-label="배경 제거"></button>
                                    </div>
                                    <div class="editor-layer-section editor-custom-color" data-editor-custom-color="highlight">
                                        <input type="color" value="#fef08a" aria-label="배경색 직접 선택">
                                        <input type="text" value="#fef08a" aria-label="배경색 HEX 입력" placeholder="#fef08a">
                                        <button type="button" class="editor-tool" data-editor-custom-apply="highlight">적용</button>
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="editor-tool icon-only" data-editor-action="bold" aria-label="굵게"><strong>B</strong></button>
                            <button type="button" class="editor-tool icon-only" data-editor-action="italic" aria-label="기울임"><em>I</em></button>
                            <button type="button" class="editor-tool icon-only" data-editor-action="underline" aria-label="밑줄"><span style="text-decoration:underline;">U</span></button>
                            <button type="button" class="editor-tool icon-only" data-editor-action="align-left" aria-label="왼쪽 정렬">≡</button>
                            <button type="button" class="editor-tool icon-only" data-editor-action="align-center" aria-label="가운데 정렬">≣</button>
                            <button type="button" class="editor-tool icon-only" data-editor-action="align-right" aria-label="오른쪽 정렬">☰</button>
                            <button type="button" class="editor-tool icon-only" data-editor-action="unorderedlist" aria-label="목록">•</button>
                            <button type="button" class="editor-tool icon-only" data-editor-action="orderedlist" aria-label="번호 목록">1.</button>
                            <div class="editor-layer-wrap">
                                <button type="button" class="editor-tool layer-toggle" data-editor-toggle="quote" aria-haspopup="true" aria-expanded="false">
                                    <span class="js-quote-label">인용</span>
                                    <span class="editor-tool-caret">▾</span>
                                </button>
                                <div class="editor-layer" data-editor-layer="quote">
                                    <p class="editor-layer-title">인용구 스타일</p>
                                    <div class="editor-style-grid">
                                        <button type="button" class="editor-style-option" data-editor-quote="basic">
                                            <strong>기본 인용</strong>
                                            <span>깔끔한 회색 인용문</span>
                                        </button>
                                        <button type="button" class="editor-style-option" data-editor-quote="note">
                                            <strong>노트</strong>
                                            <span>파란 포인트 박스</span>
                                        </button>
                                        <button type="button" class="editor-style-option" data-editor-quote="tip">
                                            <strong>팁</strong>
                                            <span>청록 하이라이트</span>
                                        </button>
                                        <button type="button" class="editor-style-option" data-editor-quote="warning">
                                            <strong>주의</strong>
                                            <span>오렌지 경고 박스</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="editor-tool icon-only" data-editor-action="divider" aria-label="구분선">―</button>
                            <button type="button" class="editor-tool icon-only" data-editor-action="link" aria-label="링크">🔗</button>
                        </div>
                    </div>
                    <div class="editor-host">
                        <textarea id="editorBody" name="body" placeholder="내용" style="width:100%; min-width:100px; height:360px;" data-editor-required="true">{{ old('body') }}</textarea>
                    </div>
                </div>
                <div class="meta">에디터 로딩에 실패하면 기본 입력창으로 자동 전환됩니다.</div>
                <label>노출 카테고리
                    <select name="audience_scope" class="form-select" style="margin-top:6px;">
                        <option value="region" @selected(old('audience_scope', 'region') === 'region')>동네 (비회원은 제목만, 로그인 회원은 상세 가능)</option>
                        <option value="apartment" @selected(old('audience_scope') === 'apartment')>아파트 (같은 단지 인증 회원만 상세)</option>
                    </select>
                </label>
                <div class="meta" style="margin-top:-4px;">글쓰기는 인증회원만 가능합니다.</div>
                <label>태그/섹션 선택
                    <select name="post_topic_id" class="form-select" style="margin-top:6px;">
                        <option value="">선택 안 함</option>
                        @foreach($topicOptions as $topic)
                            <option value="{{ $topic->id }}" @selected((string) old('post_topic_id') === (string) $topic->id)>#{{ $topic->name }}</option>
                        @endforeach
                    </select>
                </label>
                <div class="meta" style="margin-top:-4px;">기존 태그를 선택하거나, 아래에 새 태그를 입력하면 새로 생성됩니다.</div>
                <input name="new_topic" value="{{ old('new_topic') }}" placeholder="새 태그 만들기 (예: 반려동물, 리모델링, 육아)">
                @if($board->board_type === 'poll')
                    <label>투표 제목
                        <input name="poll_question" value="{{ old('poll_question') }}" style="margin-top:6px;" placeholder="예: 단지 회의 시간은 언제가 좋을까요?" required>
                    </label>
                    <label>투표 선택지
                        <textarea name="poll_options" style="margin-top:6px; min-height: 120px;" placeholder="선택지를 한 줄에 하나씩 입력하세요.&#10;오전 10시&#10;오후 2시&#10;오후 8시" required>{{ old('poll_options') }}</textarea>
                    </label>
                    <label><input type="checkbox" name="poll_allow_multiple" value="1" style="width:auto;" @checked(old('poll_allow_multiple'))> 복수 선택 허용</label>
                    <label><input type="checkbox" name="poll_results_public" value="1" style="width:auto;" @checked(old('poll_results_public', true))> 투표 결과 공개</label>
                @endif
                <label><input type="checkbox" name="is_anonymous" value="1" style="width:auto;" @checked(old('is_anonymous'))> 익명</label>
                <label><input type="checkbox" name="is_guest_visible" value="1" style="width:auto;" @checked(old('is_guest_visible'))> 비회원에게 본문 공개</label>
                <div class="actions">
                    <button type="submit">등록</button>
                    <a class="btn secondary" href="/community?scope={{ $communityScope }}&apartment_id={{ $apartmentId }}">취소</a>
                </div>
            </div>
        </form>
    </section>
</div>

<script src="/vendor/smarteditor2/js/service/HuskyEZCreator.js"></script>
<script>
(function () {
    const textarea = document.getElementById('editorBody');
    const form = document.querySelector('.js-smarteditor-form');
    if (!textarea || !form) {
        return;
    }

    const editorRef = [];
    const csrfToken = form.querySelector('input[name="_token"]')?.value || '';
    const toolbar = form.querySelector('.js-editor-toolbar');
    const headingLabel = form.querySelector('.js-heading-label');
    const fontSizeLabel = form.querySelector('.js-fontsize-label');
    const quoteLabel = form.querySelector('.js-quote-label');
    const textColorSwatch = form.querySelector('.js-textcolor-swatch');
    const highlightSwatch = form.querySelector('.js-highlight-swatch');
    const photoInput = document.createElement('input');
    photoInput.type = 'file';
    photoInput.accept = 'image/*';
    photoInput.multiple = true;
    photoInput.style.display = 'none';
    document.body.appendChild(photoInput);
    const legacyFontSizeMap = {
        '12': '1',
        '14': '2',
        '16': '3',
        '18': '4',
        '24': '5',
        '32': '6',
    };
    const fontPixelMap = {
        '1': '12px',
        '2': '14px',
        '3': '16px',
        '4': '18px',
        '5': '24px',
        '6': '32px',
        '7': '40px',
    };
    const defaultTextColor = '#20324b';
    const defaultHighlightColor = '#fef08a';
    const quoteStyles = {
        basic: 'margin:16px 0; padding:14px 16px; border-left:4px solid #94a3b8; background-color:#f8fafc; color:#334155; border-radius:14px;',
        note: 'margin:16px 0; padding:16px 18px; border-left:4px solid #2563eb; background-color:#eff6ff; color:#1e3a8a; border-radius:16px;',
        tip: 'margin:16px 0; padding:16px 18px; border-left:4px solid #0f766e; background-color:#ecfeff; color:#115e59; border-radius:16px;',
        warning: 'margin:16px 0; padding:16px 18px; border-left:4px solid #ea580c; background-color:#fff7ed; color:#9a3412; border-radius:16px;',
    };
    const headingLabelMap = {
        p: '본문',
        h2: 'H2',
        h3: 'H3',
    };
    const quoteLabelMap = {
        basic: '기본 인용',
        note: '노트',
        tip: '팁',
        warning: '주의',
    };

    const getSkinIFrame = () => document.querySelector('.editor-host iframe');
    const getEditorIFrame = () => getSkinIFrame()?.contentWindow?.document?.querySelector('#se2_iframe') || null;
    const getEditorDocument = () => getEditorIFrame()?.contentWindow?.document || null;
    let savedRange = null;

    const getCurrentRange = () => {
        const doc = getEditorDocument();
        const selection = doc?.getSelection?.();
        if (selection && selection.rangeCount) {
            return selection.getRangeAt(0);
        }

        return savedRange;
    };

    const normalizeHexColor = (value) => {
        const raw = String(value || '').trim();
        if (!/^#?[0-9a-fA-F]{3}([0-9a-fA-F]{3})?$/.test(raw)) {
            return null;
        }

        let hex = raw.startsWith('#') ? raw : '#' + raw;
        if (hex.length === 4) {
            hex = '#' + hex.slice(1).split('').map(function (char) {
                return char + char;
            }).join('');
        }

        return hex.toLowerCase();
    };

    const rgbToHex = (value) => {
        const match = String(value || '').match(/rgba?\((\d+),\s*(\d+),\s*(\d+)/i);
        if (!match) {
            return normalizeHexColor(value);
        }

        return '#' + match.slice(1, 4).map(function (part) {
            return Number(part).toString(16).padStart(2, '0');
        }).join('');
    };

    const getSelectionAnchorNode = () => {
        const range = getCurrentRange();
        if (!range) {
            return null;
        }

        let node = range.startContainer;
        if (node && node.nodeType === 3) {
            node = node.parentNode;
        }

        return node && node.nodeType === 1 ? node : null;
    };

    const findClosestElement = (node, selector) => {
        if (!node) {
            return null;
        }

        return node.closest ? node.closest(selector) : null;
    };

    const syncCustomColorInputs = (type, color) => {
        const container = form.querySelector('[data-editor-custom-color="' + type + '"]');
        if (!container || !color || color === 'transparent') {
            return;
        }

        const colorInput = container.querySelector('input[type="color"]');
        const textInput = container.querySelector('input[type="text"]');
        if (colorInput) {
            colorInput.value = color;
        }
        if (textInput) {
            textInput.value = color;
        }
    };

    const escapeHtmlAttr = (value) => String(value || '')
        .replace(/&/g, '&amp;')
        .replace(/"/g, '&quot;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');

    const uploadPhotoAndInsert = async (file) => {
        if (!file || !editorRef[0]) {
            return;
        }

        const formData = new FormData();
        formData.append('file', file);

        const response = await fetch('/community/editor/photos', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
            body: formData,
        });

        if (!response.ok) {
            throw new Error('upload_failed');
        }

        const payload = await response.json();
        const imageUrl = payload.url || '';

        if (!imageUrl) {
            throw new Error('invalid_response');
        }

        editorRef[0].exec('FOCUS');
        editorRef[0].exec('PASTE_HTML', [
            '<p><img src="'+escapeHtmlAttr(imageUrl)+'" alt="'+escapeHtmlAttr(payload.name || file.name || 'image')+'"></p>',
        ]);
    };

    const uploadMultiplePhotosAndInsert = async (files) => {
        const validFiles = Array.from(files || []).filter(Boolean);
        if (!validFiles.length) {
            return;
        }

        for (const file of validFiles) {
            await uploadPhotoAndInsert(file);
        }
    };

    const applyEditorChrome = () => {
        const skinIFrame = getSkinIFrame();
        if (!skinIFrame) {
            return;
        }

        skinIFrame.style.width = '100%';
        skinIFrame.style.maxWidth = '100%';
        skinIFrame.style.border = '1px solid #cfd9e8';
        skinIFrame.style.borderRadius = '14px';
        skinIFrame.style.background = '#ffffff';

        const skinDoc = skinIFrame.contentWindow?.document;
        if (!skinDoc) {
            return;
        }

        if (!skinDoc.getElementById('smarteditor2-modern-override')) {
            const style = skinDoc.createElement('style');
            style.id = 'smarteditor2-modern-override';
            style.textContent = `
                html, body { margin: 0 !important; overflow-x: hidden !important; background: #ffffff !important; }
                #smart_editor2, #smart_editor2_content { width: 100% !important; min-width: 0 !important; border: 0 !important; }
                .se2_tool { display: none !important; }
                .husky_seditor_editing_area_container { width: 100% !important; min-width: 0 !important; }
                #se2_iframe, .se2_input_wysiwyg { width: 100% !important; min-width: 0 !important; }
                .se2_layer { max-width: min(92vw, 560px) !important; z-index: 200 !important; }
            `;
            skinDoc.head.appendChild(style);
        }

        const editorIFrame = getEditorIFrame();
        if (editorIFrame) {
            editorIFrame.style.width = '100%';
            editorIFrame.style.maxWidth = '100%';
        }
    };

    const syncToolbarState = () => {
        const node = getSelectionAnchorNode();
        const target = node;
        if (!target) {
            return;
        }

        const computed = getEditorIFrame()?.contentWindow?.getComputedStyle(target);
        const tagName = findClosestElement(node, 'h2, h3, p, blockquote')?.tagName?.toLowerCase() || 'p';
        if (headingLabel) {
            headingLabel.textContent = headingLabelMap[tagName] || '본문';
        }

        const fontSizePx = computed?.fontSize ? Math.round(parseFloat(computed.fontSize)) + 'px' : '16px';
        if (fontSizeLabel) {
            fontSizeLabel.textContent = fontSizePx;
        }

        const textColor = rgbToHex(computed?.color) || defaultTextColor;
        if (textColorSwatch) {
            textColorSwatch.style.background = textColor;
            textColorSwatch.style.backgroundImage = 'none';
            textColorSwatch.style.setProperty('--swatch', textColor);
        }
        syncCustomColorInputs('text', textColor);

        const backgroundColor = rgbToHex(computed?.backgroundColor) || 'transparent';
        if (highlightSwatch) {
            if (backgroundColor === 'transparent' || backgroundColor === '#000000' && String(computed?.backgroundColor || '').includes('0)')) {
                highlightSwatch.style.background = '#ffffff';
                highlightSwatch.style.backgroundImage = 'linear-gradient(135deg, #ffffff 0%, #ffffff 45%, #fca5a5 45%, #fca5a5 55%, #ffffff 55%, #ffffff 100%)';
            } else {
                highlightSwatch.style.background = backgroundColor;
                highlightSwatch.style.backgroundImage = 'none';
                syncCustomColorInputs('highlight', backgroundColor);
            }
        }

        const quoteNode = findClosestElement(node, 'blockquote');
        let currentQuote = '인용';
        if (quoteNode) {
            const styleText = quoteNode.getAttribute('style') || '';
            if (styleText.includes('#2563eb')) {
                currentQuote = quoteLabelMap.note;
            } else if (styleText.includes('#0f766e')) {
                currentQuote = quoteLabelMap.tip;
            } else if (styleText.includes('#ea580c')) {
                currentQuote = quoteLabelMap.warning;
            } else {
                currentQuote = quoteLabelMap.basic;
            }
        }

        if (quoteLabel) {
            quoteLabel.textContent = currentQuote;
        }
    };

    const closeAllLayers = () => {
        form.querySelectorAll('[data-editor-layer]').forEach(function (layer) {
            layer.classList.remove('is-open');
        });

        form.querySelectorAll('[data-editor-toggle]').forEach(function (toggle) {
            toggle.setAttribute('aria-expanded', 'false');
        });
    };

    const saveEditorSelection = () => {
        const doc = getEditorDocument();
        const selection = doc?.getSelection?.();
        if (!selection || !selection.rangeCount) {
            return;
        }

        savedRange = selection.getRangeAt(0).cloneRange();
    };

    const restoreEditorSelection = () => {
        const doc = getEditorDocument();
        const selection = doc?.getSelection?.();
        if (!doc || !selection || !savedRange) {
            return false;
        }

        const editorIFrame = getEditorIFrame();
        editorIFrame?.contentWindow?.focus();
        selection.removeAllRanges();
        selection.addRange(savedRange);
        return true;
    };

    const normalizeFontMarkup = () => {
        const doc = getEditorDocument();
        if (!doc) {
            return;
        }

        doc.querySelectorAll('font[size]').forEach(function (node) {
            const px = fontPixelMap[node.getAttribute('size') || ''];
            if (!px) {
                return;
            }

            const span = doc.createElement('span');
            span.style.fontSize = px;

            while (node.firstChild) {
                span.appendChild(node.firstChild);
            }

            node.replaceWith(span);
        });
    };

    const execBrowserCommand = (command, value) => {
        const editorIFrame = getEditorIFrame();
        const doc = getEditorDocument();
        if (!editorIFrame || !doc) {
            return false;
        }

        editorIFrame.contentWindow.focus();

        try {
            doc.execCommand('styleWithCSS', false, true);
        } catch (error) {
            // Some engines ignore this command.
        }

        restoreEditorSelection();
        const result = doc.execCommand(command, false, value ?? null);
        saveEditorSelection();
        return result;
    };

    const applyInlineStyle = (styles) => {
        const doc = getEditorDocument();
        const selection = doc?.getSelection?.();
        if (!doc || !selection) {
            return false;
        }

        restoreEditorSelection();
        const range = getCurrentRange();
        if (!range) {
            return false;
        }

        const span = doc.createElement('span');
        Object.entries(styles).forEach(function ([property, value]) {
            if (value === null || value === '') {
                span.style.removeProperty(property);
            } else {
                span.style.setProperty(property, value);
            }
        });

        if (range.collapsed) {
            const marker = doc.createTextNode('\u200b');
            span.appendChild(marker);
            range.insertNode(span);

            const nextRange = doc.createRange();
            nextRange.setStart(marker, 1);
            nextRange.collapse(true);
            selection.removeAllRanges();
            selection.addRange(nextRange);
        } else {
            const fragment = range.extractContents();
            span.appendChild(fragment);
            range.insertNode(span);

            const nextRange = doc.createRange();
            nextRange.selectNodeContents(span);
            selection.removeAllRanges();
            selection.addRange(nextRange);
        }

        saveEditorSelection();
        return true;
    };

    const replaceClosestBlockTag = (tagName) => {
        const doc = getEditorDocument();
        const selection = doc?.getSelection?.();
        if (!doc || !selection) {
            return false;
        }

        restoreEditorSelection();
        const node = getSelectionAnchorNode();
        const block = findClosestElement(node, 'p, h1, h2, h3, h4, div, blockquote, li');
        if (!block || block === doc.body || !block.parentNode) {
            return false;
        }

        if (block.tagName.toLowerCase() === tagName) {
            return true;
        }

        const replacement = doc.createElement(tagName);
        if (block.hasAttribute('style')) {
            replacement.setAttribute('style', block.getAttribute('style'));
        }

        while (block.firstChild) {
            replacement.appendChild(block.firstChild);
        }

        block.parentNode.replaceChild(replacement, block);

        const nextRange = doc.createRange();
        nextRange.selectNodeContents(replacement);
        selection.removeAllRanges();
        selection.addRange(nextRange);
        saveEditorSelection();
        return true;
    };

    const applyFontSize = (pixelSize) => {
        const legacySize = legacyFontSizeMap[String(pixelSize)] || '3';

        const applied = execBrowserCommand('fontSize', legacySize);
        if (applied) {
            normalizeFontMarkup();
        } else {
            applyInlineStyle({ 'font-size': pixelSize + 'px' });
        }

        if (fontSizeLabel) {
            fontSizeLabel.textContent = pixelSize + 'px';
        }

        if (applied || savedRange) {
            syncToolbarState();
        }
    };

    const applyTextColor = (color) => {
        const applied = execBrowserCommand('foreColor', color) || applyInlineStyle({ color: color });
        if (applied && textColorSwatch) {
            textColorSwatch.style.background = color;
            textColorSwatch.style.setProperty('--swatch', color);
            syncCustomColorInputs('text', color);
            syncToolbarState();
        }
    };

    const applyHighlightColor = (color) => {
        const applied = color === 'transparent'
            ? execBrowserCommand('hiliteColor', 'transparent') || execBrowserCommand('backColor', 'transparent') || applyInlineStyle({ 'background-color': 'transparent' })
            : execBrowserCommand('hiliteColor', color) || execBrowserCommand('backColor', color) || applyInlineStyle({ 'background-color': color });

        if (applied && highlightSwatch) {
            if (color === 'transparent') {
                highlightSwatch.style.background = '#ffffff';
                highlightSwatch.style.backgroundImage = 'linear-gradient(135deg, #ffffff 0%, #ffffff 45%, #fca5a5 45%, #fca5a5 55%, #ffffff 55%, #ffffff 100%)';
            } else {
                highlightSwatch.style.backgroundImage = 'none';
                highlightSwatch.style.background = color;
            }

            highlightSwatch.style.setProperty('--swatch', color);
            syncCustomColorInputs('highlight', color);
            syncToolbarState();
        }
    };

    const applyHeadingStyle = (tagName) => {
        if (!tagName) {
            return;
        }

        const targetTag = String(tagName).toUpperCase();
        if (execBrowserCommand('formatBlock', '<' + targetTag + '>') || execBrowserCommand('formatBlock', targetTag) || replaceClosestBlockTag(String(tagName).toLowerCase())) {
            if (headingLabel) {
                headingLabel.textContent = headingLabelMap[tagName] || '본문';
            }
            syncToolbarState();
        }
    };

    const applyQuotePreset = (preset) => {
        const quoteStyle = quoteStyles[preset] || quoteStyles.basic;
        const doc = getEditorDocument();
        if (!doc) {
            return;
        }

        restoreEditorSelection();
        let quoteNode = findClosestElement(getSelectionAnchorNode(), 'blockquote');

        if (!quoteNode) {
            editorRef[0]?.exec('PASTE_HTML', ['<blockquote style="' + quoteStyle + '">인용문을 입력하세요.</blockquote><p></p>']);
        } else {
            quoteNode.setAttribute('style', quoteStyle);
        }

        saveEditorSelection();

        if (quoteLabel) {
            quoteLabel.textContent = quoteLabelMap[preset] || '인용';
        }

        syncToolbarState();
    };

    const applyResponsiveHeight = () => {
        if (!editorRef[0]) {
            return;
        }

        const height = window.matchMedia('(max-width: 768px)').matches ? 300 : 380;
        try {
            editorRef[0].exec('MSG_EDITING_AREA_RESIZE', ['100%', height + 'px']);
        } catch (error) {
            // Ignore resize command failures for compatibility.
        }
    };

    if (window.nhn && window.nhn.husky && window.nhn.husky.EZCreator) {
        window.nhn.husky.EZCreator.createInIFrame({
            oAppRef: editorRef,
            elPlaceHolder: 'editorBody',
            sSkinURI: '/vendor/smarteditor2/SmartEditor2Skin.html',
            fCreator: 'createSEditor2',
            htParams: {
                bUseToolbar: true,
                bUseVerticalResizer: false,
                bUseModeChanger: false,
            },
            fOnAppLoad: function () {
                applyEditorChrome();
                applyResponsiveHeight();
                syncToolbarState();

                const doc = getEditorDocument();
                if (doc) {
                    ['keyup', 'mouseup', 'focusin'].forEach(function (eventName) {
                        doc.addEventListener(eventName, function () {
                            saveEditorSelection();
                            syncToolbarState();
                        });
                    });

                    doc.addEventListener('selectionchange', function () {
                        saveEditorSelection();
                        syncToolbarState();
                    });
                }
            },
        });
    }

    window.addEventListener('resize', function () {
        applyEditorChrome();
        applyResponsiveHeight();
    });

    document.addEventListener('mousedown', function (event) {
        if (!form.contains(event.target)) {
            closeAllLayers();
        }
    });

    photoInput.addEventListener('change', async function () {
        const files = photoInput.files;
        if (!files || !files.length) {
            return;
        }

        try {
            await uploadMultiplePhotosAndInsert(files);
        } catch (error) {
            alert('이미지 업로드에 실패했습니다. 파일 형식/용량을 확인해 주세요.');
        } finally {
            photoInput.value = '';
        }
    });

    if (toolbar) {
        toolbar.addEventListener('mousedown', function (event) {
            if (event.target.closest('[data-editor-toggle]') || event.target.closest('[data-editor-size]') || event.target.closest('[data-editor-color]') || event.target.closest('[data-editor-bgcolor]') || event.target.closest('[data-editor-heading]') || event.target.closest('[data-editor-quote]') || event.target.closest('[data-editor-custom-apply]')) {
                saveEditorSelection();
                event.preventDefault();
            }
        });

        toolbar.addEventListener('input', function (event) {
            const container = event.target.closest('[data-editor-custom-color]');
            if (!container) {
                return;
            }

            const colorInput = container.querySelector('input[type="color"]');
            const textInput = container.querySelector('input[type="text"]');
            if (event.target === colorInput && textInput) {
                textInput.value = colorInput.value;
            } else if (event.target === textInput && colorInput) {
                const normalized = normalizeHexColor(textInput.value);
                if (normalized) {
                    colorInput.value = normalized;
                }
            }
        });

        toolbar.addEventListener('click', async function (event) {
            const toggle = event.target.closest('[data-editor-toggle]');
            if (toggle) {
                restoreEditorSelection();
                syncToolbarState();
                const layerName = toggle.dataset.editorToggle;
                const layer = form.querySelector('[data-editor-layer="' + layerName + '"]');
                const willOpen = layer && !layer.classList.contains('is-open');
                closeAllLayers();
                if (layer && willOpen) {
                    layer.classList.add('is-open');
                    toggle.setAttribute('aria-expanded', 'true');
                }
                return;
            }

            const headingButton = event.target.closest('[data-editor-heading]');
            if (headingButton) {
                applyHeadingStyle(headingButton.dataset.editorHeading);
                closeAllLayers();
                return;
            }

            const sizeButton = event.target.closest('[data-editor-size]');
            if (sizeButton) {
                applyFontSize(sizeButton.dataset.editorSize);
                closeAllLayers();
                return;
            }

            const colorButton = event.target.closest('[data-editor-color]');
            if (colorButton) {
                applyTextColor(colorButton.dataset.editorColor);
                closeAllLayers();
                return;
            }

            const bgColorButton = event.target.closest('[data-editor-bgcolor]');
            if (bgColorButton) {
                applyHighlightColor(bgColorButton.dataset.editorBgcolor);
                closeAllLayers();
                return;
            }

            const quoteButton = event.target.closest('[data-editor-quote]');
            if (quoteButton) {
                applyQuotePreset(quoteButton.dataset.editorQuote);
                closeAllLayers();
                return;
            }

            const customApplyButton = event.target.closest('[data-editor-custom-apply]');
            if (customApplyButton) {
                const type = customApplyButton.dataset.editorCustomApply;
                const container = customApplyButton.closest('[data-editor-custom-color]');
                const textInput = container?.querySelector('input[type="text"]');
                const normalized = normalizeHexColor(textInput?.value || '');
                if (!normalized) {
                    alert('색상 코드는 #20324b 같은 HEX 형식으로 입력해 주세요.');
                    return;
                }

                if (type === 'text') {
                    applyTextColor(normalized);
                } else if (type === 'highlight') {
                    applyHighlightColor(normalized);
                }
                closeAllLayers();
                return;
            }

            const button = event.target.closest('[data-editor-action]');
            if (!button) {
                return;
            }

            const action = button.dataset.editorAction;
            if (action === 'photo') {
                photoInput.click();
                return;
            }

            if (!editorRef[0]) {
                textarea.focus();
                return;
            }

            editorRef[0].exec('FOCUS');
            saveEditorSelection();

            if (action === 'bold') {
                editorRef[0].exec('EXECCOMMAND', ['bold', false, false]);
            } else if (action === 'italic') {
                editorRef[0].exec('EXECCOMMAND', ['italic', false, false]);
            } else if (action === 'underline') {
                editorRef[0].exec('EXECCOMMAND', ['underline', false, false]);
            } else if (action === 'align-left') {
                editorRef[0].exec('EXECCOMMAND', ['justifyLeft', false, false]);
            } else if (action === 'align-center') {
                editorRef[0].exec('EXECCOMMAND', ['justifyCenter', false, false]);
            } else if (action === 'align-right') {
                editorRef[0].exec('EXECCOMMAND', ['justifyRight', false, false]);
            } else if (action === 'unorderedlist') {
                editorRef[0].exec('EXECCOMMAND', ['insertUnorderedList', false, false]);
            } else if (action === 'orderedlist') {
                editorRef[0].exec('EXECCOMMAND', ['insertOrderedList', false, false]);
            } else if (action === 'blockquote') {
                editorRef[0].exec('PASTE_HTML', ['<blockquote>인용문을 입력하세요.</blockquote><p></p>']);
            } else if (action === 'divider') {
                editorRef[0].exec('PASTE_HTML', ['<hr><p></p>']);
            } else if (action === 'link') {
                const url = window.prompt('링크 주소를 입력해 주세요.', 'https://');
                if (url && url.trim() !== '') {
                    editorRef[0].exec('EXECCOMMAND', ['createLink', false, url.trim()]);
                }
            }

            saveEditorSelection();
            syncToolbarState();
        });
    }

    form.addEventListener('submit', function (event) {
        if (editorRef[0]) {
            editorRef[0].exec('UPDATE_CONTENTS_FIELD', []);
        }

        const normalized = textarea.value
            .replace(/<img\b[^>]*>/gi, ' image ')
            .replace(/<[^>]+>/g, ' ')
            .replace(/&nbsp;/gi, ' ')
            .trim();

        if (normalized === '') {
            event.preventDefault();
            alert('내용을 입력해 주세요.');
            if (editorRef[0]) {
                editorRef[0].exec('FOCUS');
            } else {
                textarea.focus();
            }
        }
    });
})();
</script>
</body>
</html>
