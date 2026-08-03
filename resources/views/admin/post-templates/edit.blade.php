@extends('admin.layout')

@section('content')
<div style="max-width: 900px; margin: 0 auto; padding: 24px 16px;">
    <h1 style="margin: 0 0 8px;">게시글 템플릿 수정</h1>
    <p style="color: #a4620b; background: #fdf3e3; border: 1px solid #e8c78a; border-radius: 8px; padding: 10px 14px; font-size: 0.9rem;">
        이미 사용된 템플릿의 질문을 삭제하면 해당 질문에 대한 기존 게시글의 답변은 수정 시 무시됩니다.
    </p>

    <form method="post" action="{{ route('admin.post-templates.update', $template) }}">
        @csrf
        @method('PUT')
        @include('admin.post-templates._form')
        <div style="margin-top: 24px; display: flex; gap: 8px;">
            <button type="submit" class="btn btn-primary">저장</button>
            <a href="{{ route('admin.post-templates.index') }}" class="btn">취소</a>
        </div>
    </form>
</div>
@endsection
