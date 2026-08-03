@extends('admin.layout')

@section('content')
<div style="max-width: 900px; margin: 0 auto; padding: 24px 16px;">
    <h1 style="margin: 0 0 24px;">새 게시글 템플릿</h1>

    <form method="post" action="{{ route('admin.post-templates.store') }}">
        @csrf
        @include('admin.post-templates._form', ['template' => null])
        <div style="margin-top: 24px; display: flex; gap: 8px;">
            <button type="submit" class="btn btn-primary">등록</button>
            <a href="{{ route('admin.post-templates.index') }}" class="btn">취소</a>
        </div>
    </form>
</div>
@endsection
