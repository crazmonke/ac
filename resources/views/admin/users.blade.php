<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>회원 관리</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; margin: 0; background: #f5f7fb; color: #1a2a44; }
        .wrap { max-width: 1380px; margin: 0 auto; padding: 24px; }
        .panel { background: #fff; border: 1px solid #dce4ef; border-radius: 12px; padding: 14px; margin-top: 12px; }
        .meta { color: #607086; font-size: 0.85rem; }
        .toolbar { display: flex; gap: 8px; align-items: center; justify-content: space-between; flex-wrap: wrap; }
        .toolbar form { display: flex; gap: 8px; align-items: center; }
        input { border: 1px solid #c8d5e7; border-radius: 8px; padding: 9px; font: inherit; }
        .btn { border: 0; border-radius: 10px; padding: 8px 10px; font-weight: 700; cursor: pointer; }
        .btn-primary { background: #2e4fb8; color: #fff; }
        .btn-danger { background: #b42318; color: #fff; }
        .btn-muted { background: #e7edf7; color: #22344f; }
        .btn[disabled] { opacity: 0.5; cursor: not-allowed; }
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; min-width: 1500px; }
        th, td { border-bottom: 1px solid #edf1f7; padding: 10px 8px; text-align: left; vertical-align: top; font-size: 0.88rem; }
        th { background: #f8fbff; position: sticky; top: 0; z-index: 1; }
        .status { display: inline-flex; padding: 3px 8px; border-radius: 999px; font-size: 0.78rem; font-weight: 700; }
        .ok { background: #e8f6f1; color: #166b53; }
        .warn { background: #fff4e8; color: #8d4a1c; }
        .danger { background: #fdecec; color: #9e1d1d; }
        .actions { display: grid; gap: 6px; }
        .inline { display: inline; }
    </style>
</head>
<body>
<div class="wrap">
    @include('partials.admin-nav')

    <h1>회원 관리</h1>

    @if(session('status'))
        <div class="panel" style="background:#e8f6f1; border-color:#bee6d9; color:#166b53;">{{ session('status') }}</div>
    @endif

    @if($errors->any())
        <div class="panel" style="background:#fdecec; border-color:#f4c8c8; color:#9e1d1d;">{{ $errors->first() }}</div>
    @endif

    <section class="panel toolbar">
        <div class="meta">회원가입 계정 목록과 운영 제어(인증/접근/탈퇴/프로필잠금)를 관리합니다.</div>
        <form method="get" action="/admin/users">
            <input type="text" name="q" value="{{ $q }}" placeholder="이름/이메일/공동주택/지역 검색">
            <button class="btn btn-primary" type="submit">검색</button>
        </form>
    </section>

    <section class="panel table-wrap">
        <table>
            <thead>
            <tr>
                <th>ID</th>
                <th>기본 정보</th>
                <th>작성글</th>
                <th>댓글</th>
                <th>최근 로그인</th>
                <th>가입일</th>
                <th>인증여부</th>
                <th>지역</th>
                <th>공동주택명</th>
                <th>접근허용</th>
                <th>탈퇴</th>
                <th>프로필잠금</th>
            </tr>
            </thead>
            <tbody>
            @forelse($users as $member)
                @php
                    $isWithdrawn = (bool) $member->withdrawn_at;
                    $isAccessAllowed = (bool) ($member->access_allowed ?? true);
                    $isVerified = (bool) ($member->computed_is_verified ?? false);
                    $isProfileLocked = (bool) ($member->profile_locked ?? true);
                    $regionLabel = trim((string) ($member->computed_region_label ?? ''));
                @endphp
                <tr>
                    <td>{{ $member->id }}</td>
                    <td>
                        <strong>{{ $member->name }}</strong><br>
                        <span class="meta">{{ $member->email }}</span>
                    </td>
                    <td>{{ (int) $member->posts_count }}</td>
                    <td>{{ (int) $member->comments_count }}</td>
                    <td>{{ $member->last_login_at ? $member->last_login_at->format('Y-m-d H:i') : '-' }}</td>
                    <td>{{ $member->created_at ? $member->created_at->format('Y-m-d H:i') : '-' }}</td>
                    <td>
                        <div class="actions">
                            @if($isVerified)
                                <span class="status ok">인증됨</span>
                                <form method="post" action="/admin/users/{{ $member->id }}/verification" class="inline">
                                    @csrf
                                    @method('put')
                                    <input type="hidden" name="action" value="reject">
                                    <button class="btn btn-danger" type="submit">반려</button>
                                </form>
                            @else
                                <span class="status warn">미인증</span>
                                <form method="post" action="/admin/users/{{ $member->id }}/verification" class="inline">
                                    @csrf
                                    @method('put')
                                    <input type="hidden" name="action" value="approve">
                                    <button class="btn btn-primary" type="submit">승인</button>
                                </form>
                            @endif
                        </div>
                    </td>
                    <td>{{ $regionLabel !== '' ? $regionLabel : '-' }}</td>
                    <td>{{ $member->computed_residence_name ?: '-' }}</td>
                    <td>
                        <div class="actions">
                            @if($isAccessAllowed)
                                <span class="status ok">허용</span>
                                <form method="post" action="/admin/users/{{ $member->id }}/access" class="inline">
                                    @csrf
                                    @method('put')
                                    <input type="hidden" name="action" value="deny">
                                    <button class="btn btn-danger" type="submit">거부</button>
                                </form>
                            @else
                                <span class="status danger">거부됨</span>
                                <form method="post" action="/admin/users/{{ $member->id }}/access" class="inline">
                                    @csrf
                                    @method('put')
                                    <input type="hidden" name="action" value="allow">
                                    <button class="btn btn-primary" type="submit">허용</button>
                                </form>
                            @endif
                        </div>
                    </td>
                    <td>
                        <div class="actions">
                            @if($isWithdrawn)
                                <span class="status danger">탈퇴됨</span>
                                <button class="btn btn-muted" type="button" disabled>탈퇴</button>
                            @else
                                <span class="status ok">활동중</span>
                                <form method="post" action="/admin/users/{{ $member->id }}" class="inline" onsubmit="return confirm('해당 회원을 탈퇴 처리할까요?');">
                                    @csrf
                                    @method('delete')
                                    <button class="btn btn-danger" type="submit">탈퇴</button>
                                </form>
                            @endif
                        </div>
                    </td>
                    <td>
                        <div class="actions">
                            @if($isProfileLocked)
                                <span class="status warn">잠금</span>
                                <form method="post" action="/admin/users/{{ $member->id }}/profile-lock" class="inline">
                                    @csrf
                                    @method('put')
                                    <input type="hidden" name="action" value="unlock">
                                    <button class="btn btn-primary" type="submit">해제</button>
                                </form>
                            @else
                                <span class="status ok">해제</span>
                                <form method="post" action="/admin/users/{{ $member->id }}/profile-lock" class="inline">
                                    @csrf
                                    @method('put')
                                    <input type="hidden" name="action" value="lock">
                                    <button class="btn btn-danger" type="submit">잠금</button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="12">회원 데이터가 없습니다.</td>
                </tr>
            @endforelse
            </tbody>
        </table>

        @include('partials.pagination', ['paginator' => $users])
    </section>
</div>
</body>
</html>
