@extends('admin.layout')

@section('content')
<div style="max-width: 1200px; margin: 0 auto; padding: 24px 16px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <h1 style="margin: 0;">게시글 템플릿 관리</h1>
        <a href="{{ route('admin.post-templates.create') }}" class="btn btn-primary">새 템플릿 추가</a>
    </div>

    @if(session('success'))
        <div style="padding: 12px 16px; background: #d1f2eb; border: 1px solid #0f7a72; border-radius: 8px; color: #0f7a72; margin-bottom: 16px;">
            {{ session('success') }}
        </div>
    @endif

    @if($templates->isEmpty())
        <div style="padding: 24px; background: #f8f9fa; border: 1px solid #e0e6ed; border-radius: 8px; text-align: center;">
            <p style="color: #607086; margin: 0;">등록된 템플릿이 없습니다.</p>
            <a href="{{ route('admin.post-templates.create') }}" class="btn btn-primary" style="margin-top: 12px;">첫 템플릿 추가하기</a>
        </div>
    @else
        <div style="background: #fff; border: 1px solid #e0e6ed; border-radius: 8px; overflow: hidden;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead style="background: #f8f9fa; border-bottom: 1px solid #e0e6ed;">
                    <tr>
                        <th style="padding: 12px 16px; text-align: left; font-weight: 600; color: #15243a;">템플릿 이름</th>
                        <th style="padding: 12px 16px; text-align: left; font-weight: 600; color: #15243a;">사용 게시판</th>
                        <th style="padding: 12px 16px; text-align: center; font-weight: 600; color: #15243a;">질문 수</th>
                        <th style="padding: 12px 16px; text-align: center; font-weight: 600; color: #15243a;">상태</th>
                        <th style="padding: 12px 16px; text-align: center; font-weight: 600; color: #15243a;">정렬</th>
                        <th style="padding: 12px 16px; text-align: right; font-weight: 600; color: #15243a;">작업</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($templates as $template)
                        <tr style="border-bottom: 1px solid #e0e6ed;">
                            <td style="padding: 12px 16px; color: #15243a;">
                                {{ $template->name }}
                                @if($template->description)
                                    <div style="font-size: 0.85rem; color: #607086;">{{ $template->description }}</div>
                                @endif
                            </td>
                            <td style="padding: 12px 16px; font-size: 0.9rem; color: #607086;">
                                @if(empty($template->board_slugs))
                                    전체 게시판
                                @else
                                    {{ collect($template->board_slugs)->map(fn ($slug) => $boardNames[$slug] ?? $slug)->implode(', ') }}
                                @endif
                            </td>
                            <td style="padding: 12px 16px; text-align: center;">{{ count($template->questions ?? []) }}개</td>
                            <td style="padding: 12px 16px; text-align: center;">
                                @if($template->is_active)
                                    <span style="display: inline-block; padding: 4px 8px; background: #d1f2eb; border-radius: 4px; color: #0f7a72; font-size: 0.85rem; font-weight: 600;">활성</span>
                                @else
                                    <span style="display: inline-block; padding: 4px 8px; background: #f0f2f5; border-radius: 4px; color: #607086; font-size: 0.85rem; font-weight: 600;">비활성</span>
                                @endif
                            </td>
                            <td style="padding: 12px 16px; text-align: center; color: #607086;">{{ $template->sort_order }}</td>
                            <td style="padding: 12px 16px; text-align: right;">
                                <a href="{{ route('admin.post-templates.edit', $template) }}" style="color: #0f7a72; text-decoration: none; margin-right: 8px;">수정</a>
                                <form method="post" action="{{ route('admin.post-templates.destroy', $template) }}" style="display: inline;" onsubmit="return confirm('정말 삭제하시겠습니까? 이 템플릿으로 작성된 게시글은 일반 게시글로 유지됩니다.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" style="background: none; border: none; color: #a61b1b; cursor: pointer;">삭제</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div style="margin-top: 24px;">
            {{ $templates->links('pagination::bootstrap-4') }}
        </div>
    @endif
</div>
@endsection
