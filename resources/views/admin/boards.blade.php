<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>게시판 관리</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; margin: 0; background: #f5f7fb; color: #1a2a44; }
        .wrap { max-width: 1200px; margin: 0 auto; padding: 24px; }
        .panel { background: #fff; border: 1px solid #dce4ef; border-radius: 12px; padding: 14px; margin-bottom: 14px; }
        .grid { display: grid; grid-template-columns: repeat(6, minmax(80px, 1fr)); gap: 8px; }
        label { font-size: 12px; color: #5a6c83; }
        input, select, textarea { width: 100%; border: 1px solid #c9d5e8; border-radius: 8px; padding: 8px; }
        textarea { min-height: 56px; }
        button { border: 0; border-radius: 8px; padding: 8px 10px; font-weight: 700; cursor: pointer; }
        .btn { background: #0f6f67; color: #fff; }
        .btn-danger { background: #b42318; color: #fff; }
        .btn-muted { background: #dde7f3; color: #2b3a52; }
        table { width: 100%; border-collapse: collapse; background: #fff; border: 1px solid #dce4ef; border-radius: 12px; overflow: hidden; }
        th, td { padding: 8px; border-bottom: 1px solid #edf1f7; text-align: left; vertical-align: top; }
        .small { font-size: 12px; color: #5a6c83; }
        a { color: #0f6f67; text-decoration: none; font-weight: 600; }
        .flash { background: #e8f6f1; border: 1px solid #bee6d9; color: #166b53; border-radius: 8px; padding: 10px; margin-bottom: 12px; }
    </style>
</head>
<body>
<div class="wrap">
    <h1>게시판 관리</h1>
    <p><a href="/admin">대시보드로</a></p>

    @if(session('status'))
        <div class="flash">{{ session('status') }}</div>
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
                            <option value="{{ $category->id }}">{{ $category->name }} ({{ $category->slug }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label>단지</label>
                    <select name="apartment_id">
                        <option value="">공용</option>
                        @foreach($apartments as $apartment)
                            <option value="{{ $apartment->id }}">{{ $apartment->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label>게시판명</label>
                    <input name="name" required>
                </div>
                <div>
                    <label>슬러그</label>
                    <input name="slug" required>
                </div>
                <div>
                    <label>타입</label>
                    <select name="board_type" required>
                        @foreach($boardTypes as $type)
                            <option value="{{ $type }}">{{ $type }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label>정렬</label>
                    <input type="number" name="sort_order" value="0" min="0">
                </div>
                <div>
                    <label>읽기 권한</label>
                    <select name="read_role" required>
                        @foreach($roles as $role)
                            <option value="{{ $role }}">{{ $role }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label>쓰기 권한</label>
                    <select name="write_role" required>
                        @foreach($roles as $role)
                            <option value="{{ $role }}">{{ $role }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label>댓글 권한</label>
                    <select name="comment_role" required>
                        @foreach($roles as $role)
                            <option value="{{ $role }}">{{ $role }}</option>
                        @endforeach
                        <option value="none">none</option>
                    </select>
                </div>
                <div>
                    <label><input type="checkbox" name="allow_file" value="1" checked style="width:auto;"> 파일 허용</label>
                    <label><input type="checkbox" name="allow_anonymous" value="1" style="width:auto;"> 익명 허용</label>
                    <label><input type="checkbox" name="is_active" value="1" checked style="width:auto;"> 활성</label>
                </div>
                <div style="grid-column: span 2;">
                    <label>설명</label>
                    <textarea name="description"></textarea>
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
            <th>정보</th>
            <th>권한</th>
            <th>옵션</th>
            <th>작업</th>
        </tr>
        </thead>
        <tbody>
        @forelse($boards as $board)
            <tr>
                <td>{{ $board->id }}</td>
                <td>
                    <form method="post" action="/admin/boards/{{ $board->id }}">
                        @csrf
                        @method('PUT')
                        <input name="name" value="{{ $board->name }}" required>
                        <input name="slug" value="{{ $board->slug }}" required>
                        <select name="board_type" required>
                            @foreach($boardTypes as $type)
                                <option value="{{ $type }}" @selected($board->board_type === $type)>{{ $type }}</option>
                            @endforeach
                        </select>
                        <input type="number" name="sort_order" value="{{ $board->sort_order }}" min="0">
                        <div class="small">카테고리: {{ $board->category?->name }}</div>
                </td>
                <td>
                        <select name="read_role" required>
                            @foreach($roles as $role)
                                <option value="{{ $role }}" @selected($board->read_role === $role)>{{ $role }}</option>
                            @endforeach
                        </select>
                        <select name="write_role" required>
                            @foreach($roles as $role)
                                <option value="{{ $role }}" @selected($board->write_role === $role)>{{ $role }}</option>
                            @endforeach
                        </select>
                        <select name="comment_role" required>
                            @foreach($roles as $role)
                                <option value="{{ $role }}" @selected($board->comment_role === $role)>{{ $role }}</option>
                            @endforeach
                            <option value="none" @selected($board->comment_role === 'none')>none</option>
                        </select>
                </td>
                <td>
                    <label class="small"><input type="checkbox" name="allow_file" value="1" @checked($board->allow_file) style="width:auto;"> 파일</label>
                    <label class="small"><input type="checkbox" name="allow_anonymous" value="1" @checked($board->allow_anonymous) style="width:auto;"> 익명</label>
                    <label class="small"><input type="checkbox" name="is_active" value="1" @checked($board->is_active) style="width:auto;"> 활성</label>
                </td>
                <td>
                        <button class="btn-muted" type="submit">수정</button>
                    </form>
                    <form method="post" action="/admin/boards/{{ $board->id }}" onsubmit="return confirm('삭제할까요?')" style="margin-top: 6px;">
                        @csrf
                        @method('DELETE')
                        <button class="btn-danger" type="submit">삭제</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="5">게시판 데이터가 없습니다.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
</body>
</html>
