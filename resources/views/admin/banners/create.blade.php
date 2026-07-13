@extends('admin.layout')

@section('content')
<div style="max-width: 800px; margin: 0 auto; padding: 24px 16px;">
    <h1 style="margin: 0 0 24px;">새 배너 추가</h1>

    @if($errors->any())
        <div style="padding: 12px 16px; background: #ffebe7; border: 1px solid #b54708; border-radius: 8px; color: #b54708; margin-bottom: 16px;">
            <ul style="margin: 0; padding-left: 20px;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="post" action="{{ route('admin.banners.store') }}" style="background: #fff; border: 1px solid #e0e6ed; border-radius: 8px; padding: 24px;" enctype="multipart/form-data">
        @csrf

        <div style="margin-bottom: 20px;">
            <label style="display: block; margin-bottom: 6px; font-weight: 600; color: #15243a;">
                배너 제목 <span style="color: #a61b1b;">*</span>
            </label>
            <input type="text" name="title" value="{{ old('title') }}" required style="width: 100%; padding: 10px 12px; border: 1px solid #d6e0ea; border-radius: 6px; font-family: inherit; font-size: 1rem;">
        </div>

        <div style="margin-bottom: 20px;">
            <label style="display: block; margin-bottom: 6px; font-weight: 600; color: #15243a;">배너 설명</label>
            <textarea name="description" style="width: 100%; padding: 10px 12px; border: 1px solid #d6e0ea; border-radius: 6px; font-family: inherit; font-size: 1rem; min-height: 80px; resize: vertical;">{{ old('description') }}</textarea>
        </div>

        <div style="margin-bottom: 20px;">
            <label style="display: block; margin-bottom: 6px; font-weight: 600; color: #15243a;">
                배너 유형 <span style="color: #a61b1b;">*</span>
            </label>
            <select name="type" required onchange="updateFieldVisibility()" style="width: 100%; padding: 10px 12px; border: 1px solid #d6e0ea; border-radius: 6px; font-family: inherit; font-size: 1rem;">
                <option value="">선택하세요</option>
                <option value="image" {{ old('type') === 'image' ? 'selected' : '' }}>🖼️ 이미지</option>
                <option value="video" {{ old('type') === 'video' ? 'selected' : '' }}>🎬 영상</option>
                <option value="text" {{ old('type') === 'text' ? 'selected' : '' }}>📝 텍스트</option>
            </select>
        </div>

        <div style="margin-bottom: 20px; display: {{ old('type') === 'image' ? 'block' : 'none' }};" id="image-field">
            <label style="display: block; margin-bottom: 6px; font-weight: 600; color: #15243a;">이미지 URL</label>
            <input type="url" name="image_url" value="{{ old('image_url') }}" style="width: 100%; padding: 10px 12px; border: 1px solid #d6e0ea; border-radius: 6px; font-family: inherit; font-size: 1rem;">
            <p style="margin: 8px 0 0; color: #607086; font-size: 0.9rem;">또는</p>
            <label style="display: block; margin-bottom: 6px; font-weight: 600; color: #15243a; margin-top: 12px;">이미지 파일 업로드</label>
            <input type="file" name="image_file" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp" style="width: 100%; padding: 10px 12px; border: 1px solid #d6e0ea; border-radius: 6px; font-family: inherit; font-size: 1rem;">
            <p style="margin: 6px 0 0; color: #607086; font-size: 0.85rem;">최대 10MB (JPEG, PNG, GIF, WebP)</p>
        </div>

        <div style="margin-bottom: 20px; display: {{ old('type') === 'video' ? 'block' : 'none' }};" id="video-field">
            <label style="display: block; margin-bottom: 6px; font-weight: 600; color: #15243a;">영상 URL</label>
            <input type="url" name="video_url" value="{{ old('video_url') }}" style="width: 100%; padding: 10px 12px; border: 1px solid #d6e0ea; border-radius: 6px; font-family: inherit; font-size: 1rem;">
            <p style="margin: 8px 0 0; color: #607086; font-size: 0.9rem;">또는</p>
            <label style="display: block; margin-bottom: 6px; font-weight: 600; color: #15243a; margin-top: 12px;">영상 파일 업로드</label>
            <input type="file" name="video_file" accept="video/mp4,video/webm,video/ogg" style="width: 100%; padding: 10px 12px; border: 1px solid #d6e0ea; border-radius: 6px; font-family: inherit; font-size: 1rem;">
            <p style="margin: 6px 0 0; color: #607086; font-size: 0.85rem;">최대 100MB (MP4, WebM, OGG)</p>
        </div>

        <div style="margin-bottom: 20px; display: {{ old('type') === 'text' ? 'block' : 'none' }};" id="text-field">
            <label style="display: block; margin-bottom: 6px; font-weight: 600; color: #15243a;">텍스트 내용</label>
            <textarea name="text_content" style="width: 100%; padding: 10px 12px; border: 1px solid #d6e0ea; border-radius: 6px; font-family: inherit; font-size: 1rem; min-height: 100px; resize: vertical;">{{ old('text_content') }}</textarea>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px;">
            <div>
                <label style="display: block; margin-bottom: 6px; font-weight: 600; color: #15243a;">배너 링크 URL</label>
                <input type="url" name="button_url" value="{{ old('button_url') }}" style="width: 100%; padding: 10px 12px; border: 1px solid #d6e0ea; border-radius: 6px; font-family: inherit; font-size: 1rem;">
                <p style="margin: 6px 0 0; color: #607086; font-size: 0.85rem;">배너 전체 영역이 이 링크로 연결됩니다</p>
            </div>
            <div>
                <label style="display: block; margin-bottom: 6px; font-weight: 600; color: #15243a;">정렬순서</label>
                <input type="number" name="sort_order" value="{{ old('sort_order', 0) }}" min="0" style="width: 100%; padding: 10px 12px; border: 1px solid #d6e0ea; border-radius: 6px; font-family: inherit; font-size: 1rem;">
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px;">
            <div>
                <label style="display: block; margin-bottom: 6px; font-weight: 600; color: #15243a;">노출 시작일</label>
                <input type="date" name="start_date" value="{{ old('start_date') }}" style="width: 100%; padding: 10px 12px; border: 1px solid #d6e0ea; border-radius: 6px; font-family: inherit; font-size: 1rem;">
            </div>
            <div>
                <label style="display: block; margin-bottom: 6px; font-weight: 600; color: #15243a;">노출 종료일</label>
                <input type="date" name="end_date" value="{{ old('end_date') }}" style="width: 100%; padding: 10px 12px; border: 1px solid #d6e0ea; border-radius: 6px; font-family: inherit; font-size: 1rem;">
            </div>
        </div>

        <div style="margin-bottom: 20px;">
            <label style="display: flex; align-items: center; gap: 8px;">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }} style="width: 18px; height: 18px; cursor: pointer;">
                <span style="color: #15243a;">활성 상태로 설정</span>
            </label>
        </div>

        <div style="display: flex; gap: 12px;">
            <button type="submit" class="btn btn-primary">배너 추가</button>
            <a href="{{ route('admin.banners.index') }}" class="btn btn-soft">취소</a>
        </div>
    </form>
</div>

<script>
function updateFieldVisibility() {
    const type = document.querySelector('select[name="type"]').value;
    document.getElementById('image-field').style.display = type === 'image' ? 'block' : 'none';
    document.getElementById('video-field').style.display = type === 'video' ? 'block' : 'none';
    document.getElementById('text-field').style.display = type === 'text' ? 'block' : 'none';
}
</script>
@endsection
