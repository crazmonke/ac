<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>게시판 관리</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; margin: 0; background: #f5f7fb; color: #1a2a44; }
        .wrap { margin: 0; padding: 24px 28px; }
        .panel { background: #fff; border: 1px solid #dce4ef; border-radius: 12px; padding: 14px; margin-bottom: 14px; }
        .grid { display: grid; grid-template-columns: repeat(6, minmax(80px, 1fr)); gap: 8px; }
        label { font-size: 12px; color: #5a6c83; }
        input, select, textarea { width: 100%; border: 1px solid #c9d5e8; border-radius: 8px; padding: 8px; box-sizing: border-box; font: inherit; }
        textarea { min-height: 56px; }
        button { border: 0; border-radius: 8px; padding: 8px 10px; font-weight: 700; cursor: pointer; font: inherit; }
        .btn { background: #0f6f67; color: #fff; }
        .btn-danger { background: #b42318; color: #fff; }
        .btn-muted { background: #dde7f3; color: #2b3a52; }
        table { width: 100%; border-collapse: collapse; background: #fff; border: 1px solid #dce4ef; border-radius: 12px; overflow: hidden; }
        th, td { padding: 8px; border-bottom: 1px solid #edf1f7; text-align: left; vertical-align: middle; }
        .small { font-size: 12px; color: #5a6c83; }
        a { color: #0f6f67; text-decoration: none; font-weight: 600; }
        .flash { background: #e8f6f1; border: 1px solid #bee6d9; color: #166b53; border-radius: 8px; padding: 10px; margin-bottom: 12px; }
        .error-box { background: #ffebe7; border: 1px solid #b54708; color: #b54708; border-radius: 8px; padding: 10px; margin-bottom: 12px; }
        td input, td select { padding: 5px 7px; font-size: 0.84rem; }
        .cb-wrap { display: flex; flex-direction: column; gap: 4px; }
        .cb-wrap label { display: flex; align-items: center; gap: 4px; white-space: nowrap; }
        .cb-wrap input[type=checkbox] { width: auto; }
        .action-btns { display: flex; flex-direction: column; gap: 6px; }
    </style>
</head>
<body>
<div class="wrap">
    @include('partials.admin-nav')
    <h1>게시판 관리</h1>

    @if(session('status'))
        <div class="flash">{{ session('status') }}</div>
    @endif

    @if(isset($errors) && $errors->any())
        <div class="error-box">
            <ul style="margin:0; padding-left:18px;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="panel">
        <h2>게시판 생성</h2>
        <form method="post" action="/admin/boards">
            @csrf
            <div class="grid">
                <div>
                    <label>카테고리</label>
                    <select name="category_id" required>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>{{ $category->name }} ({{ $category->slug }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label>단지</label>
                    <select name="apartment_id">
                        <option value="">공용</option>
                        @foreach($apartments as $apartment)
                            <option value="{{ $apartment->id }}" @selected(old('apartment_id') == $apartment->id)>{{ $apartment->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label>게시판명</label>
                    <input name="name" value="{{ old('name') }}" required>
                </div>
                <div>
                    <label>슬러그</label>
                    <input name="slug" value="{{ old('slug') }}" required>
                </div>
                <div>
                    <label>타입</label>
                    <select name="board_type" required>
                        @foreach($boardTypes as $type)
                            <option value="{{ $type }}" @selected(old('board_type') === $type)>{{ $type }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label>정렬</label>
                    <input type="number" name="sort_order" value="{{ old('sort_order', 0) }}" min="0">
                </div>
                <div>
                    <label>읽기 권한</label>
                    <select name="read_role" required>
                        @foreach($roleLabels as $role => $label)
                            <option value="{{ $role }}" @selected(old('read_role') === $role)>{{ $label }} ({{ $role }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label>쓰기 권한</label>
                    <select name="write_role" required>
                        @foreach($roleLabels as $role => $label)
                            <option value="{{ $role }}" @selected(old('write_role') === $role)>{{ $label }} ({{ $role }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label>댓글 권한</label>
                    <select name="comment_role" required>
                        @foreach($roleLabels as $role => $label)
                            <option value="{{ $role }}" @selected(old('comment_role') === $role)>{{ $label }} ({{ $role }})</option>
                        @endforeach
                        <option value="none" @selected(old('comment_role') === 'none')>비활성 (none)</option>
                    </select>
                </div>
                <div>
                    <label><input type="checkbox" name="allow_file" value="1" @checked(old('allow_file', true)) style="width:auto;"> 파일 허용</label>
                    <label><input type="checkbox" name="allow_anonymous" value="1" @checked(old('allow_anonymous')) style="width:auto;"> 익명 허용</label>
                    <label><input type="checkbox" name="is_active" value="1" @checked(old('is_active', true)) style="width:auto;"> 활성</label>
                </div>
                <div style="grid-column: span 2;">
                    <label>설명</label>
                    <textarea name="description">{{ old('description') }}</textarea>
                </div>
                <div>
                    <button class="btn" type="submit">생성</button>
                </div>
            </div>
        </form>
    </section>

    <table>
        <thead>
        <tr>
            <th>ID</th>
            <th>게시판명 / 슬러그 / 타입 / 정렬</th>
            <th>권한</th>
            <th>옵션</th>
            <th>작업</th>
        </tr>
        </thead>
        <tbody>
        @forelse($boards as $board)
            <tr>
                <td style="white-space:nowrap;">
                    {{ $board->id }}<br>
                    <span class="small">{{ $board->category?->name }}</span>
                </td>
                <td>
                    <input form="board-update-{{ $board->id }}" name="name" value="{{ $board->name }}" required style="margin-bottom:4px;">
                    <input form="board-update-{{ $board->id }}" name="slug" value="{{ $board->slug }}" required style="margin-bottom:4px;">
                    <select form="board-update-{{ $board->id }}" name="board_type" required style="margin-bottom:4px;">
                        @foreach($boardTypes as $type)
                            <option value="{{ $type }}" @selected($board->board_type === $type)>{{ $type }}</option>
                        @endforeach
                    </select>
                    <input form="board-update-{{ $board->id }}" type="number" name="sort_order" value="{{ $board->sort_order }}" min="0">
                </td>
                <td>
                    <select form="board-update-{{ $board->id }}" name="read_role" required style="margin-bottom:4px;">
                        @foreach($roleLabels as $role => $label)
                            <option value="{{ $role }}" @selected($board->read_role === $role)>{{ $label }} ({{ $role }})</option>
                        @endforeach
                    </select>
                    <select form="board-update-{{ $board->id }}" name="write_role" required style="margin-bottom:4px;">
                        @foreach($roleLabels as $role => $label)
                            <option value="{{ $role }}" @selected($board->write_role === $role)>{{ $label }} ({{ $role }})</option>
                        @endforeach
                    </select>
                    <select form="board-update-{{ $board->id }}" name="comment_role" required>
                        @foreach($roleLabels as $role => $label)
                            <option value="{{ $role }}" @selected($board->comment_role === $role)>{{ $label }} ({{ $role }})</option>
                        @endforeach
                        <option value="none" @selected($board->comment_role === 'none')>비활성 (none)</option>
                    </select>
                </td>
                <td>
                    <div class="cb-wrap">
                        <label><input form="board-update-{{ $board->id }}" type="checkbox" name="allow_file" value="1" @checked($board->allow_file)> 파일</label>
                        <label><input form="board-update-{{ $board->id }}" type="checkbox" name="allow_anonymous" value="1" @checked($board->allow_anonymous)> 익명</label>
                        <label><input form="board-update-{{ $board->id }}" type="checkbox" name="is_active" value="1" @checked($board->is_active)> 활성</label>
                    </div>
                </td>
                <td>
                    <div class="action-btns">
                        <button form="board-update-{{ $board->id }}" class="btn-muted" type="submit">수정</button>
                        <button form="board-delete-{{ $board->id }}" class="btn-danger" type="submit">삭제</button>
                    </div>
                </td>
            </tr>
        @empty
            <tr><td colspan="5">게시판 데이터가 없습니다.</td></tr>
        @endforelse
        </tbody>
    </table>

    {{-- 수정/삭제 폼을 테이블 밖에 배치 (form 속성으로 각 행의 입력과 연결) --}}
    @foreach($boards as $board)
        <form id="board-update-{{ $board->id }}" method="post" action="/admin/boards/{{ $board->id }}" style="display:none;">
            @csrf
            @method('PUT')
        </form>
        <form id="board-delete-{{ $board->id }}" method="post" action="/admin/boards/{{ $board->id }}" style="display:none;"
              onsubmit="return confirm('게시판 [{{ addslashes($board->name) }}]을 영구 삭제할까요? 되돌릴 수 없습니다.')">
            @csrf
            @method('DELETE')
        </form>
    @endforeach
</div>
</body>
</html>
