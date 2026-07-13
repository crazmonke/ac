@extends('admin.layout')

@section('content')
<div style="max-width: 800px; margin: 0 auto; padding: 24px 16px;">
    <h1 style="margin: 0 0 24px;">배너 수정</h1>

    @if($errors->any())
        <div style="padding: 12px 16px; background: #ffebe7; border: 1px solid #b54708; border-radius: 8px; color: #b54708; margin-bottom: 16px;">
            <ul style="margin: 0; padding-left: 20px;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="post" action="{{ route('admin.banners.update', $banner) }}" style="background: #fff; border: 1px solid #e0e6ed; border-radius: 8px; padding: 24px;">
        @csrf
        @method('PUT')

        <div style="margin-bottom: 20px;">
            <label style="display: block; margin-bottom: 6px; font-weight: 600; color: #15243a;">
                배너 제목 <span style="color: #a61b1b;">*</span>
            </label>
            <input type="text" name="title" value="{{ old('title', $banner->title) }}" required style="width: 100%; padding: 10px 12px; border: 1px solid #d6e0ea; border-radius: 6px; font-family: inherit; font-size: 1rem; box-sizing: border-box;">
        </div>

        <div style="margin-bottom: 20px;">
            <label style="display: block; margin-bottom: 6px; font-weight: 600; color: #15243a;">배너 설명</label>
            <textarea name="description" style="width: 100%; padding: 10px 12px; border: 1px solid #d6e0ea; border-radius: 6px; font-family: inherit; font-size: 1rem; min-height: 80px; resize: vertical; box-sizing: border-box;">{{ old('description', $banner->description) }}</textarea>
        </div>

        <div style="margin-bottom: 20px;">
            <label style="display: block; margin-bottom: 6px; font-weight: 600; color: #15243a;">
                배너 유형 <span style="color: #a61b1b;">*</span>
            </label>
            <select name="type" required onchange="updateFieldVisibility()" style="width: 100%; padding: 10px 12px; border: 1px solid #d6e0ea; border-radius: 6px; font-family: inherit; font-size: 1rem;">
                <option value="">선택하세요</option>
                <option value="image" {{ old('type', $banner->type) === 'image' ? 'selected' : '' }}>🖼️ 이미지</option>
                <option value="video" {{ old('type', $banner->type) === 'video' ? 'selected' : '' }}>🎬 영상</option>
                <option value="text"  {{ old('type', $banner->type) === 'text'  ? 'selected' : '' }}>📝 텍스트</option>
            </select>
        </div>

        {{-- IMAGE --}}
        <div style="margin-bottom: 20px; display: {{ old('type', $banner->type) === 'image' ? 'block' : 'none' }};" id="image-field">
            <label style="display: block; margin-bottom: 6px; font-weight: 600; color: #15243a;">이미지 URL</label>
            <input type="url" name="image_url" value="{{ old('image_url', $banner->image_url) }}" style="width: 100%; padding: 10px 12px; border: 1px solid #d6e0ea; border-radius: 6px; font-family: inherit; font-size: 1rem; box-sizing: border-box;">
            <p style="margin: 10px 0 6px; color: #607086; font-size: 0.9rem;">또는 파일 직접 업로드 (최대 10MB · JPEG, PNG, GIF, WebP)</p>
            <input type="hidden" name="image_path" id="image_path" value="{{ old('image_path', $banner->image_path) }}">
            <div id="image-upload-area" style="border: 2px dashed #d6e0ea; border-radius: 8px; padding: 20px; text-align: center; cursor: pointer; transition: border-color .2s;" onclick="document.getElementById('image_file_pick').click()">
                <div id="image-upload-label" style="color: #607086; font-size: 0.9rem;">
                    @if($banner->image_path)
                        새 파일로 교체하려면 클릭하거나 드롭하세요
                    @else
                        파일을 클릭하거나 여기에 드롭하세요
                    @endif
                </div>
                @if($banner->image_path)
                    <img id="image-preview" src="{{ asset($banner->image_path) }}" alt="현재 이미지" style="max-width: 100%; max-height: 200px; margin-top: 10px; border-radius: 6px;">
                @else
                    <img id="image-preview" src="" alt="미리보기" style="display:none; max-width: 100%; max-height: 200px; margin-top: 10px; border-radius: 6px;">
                @endif
            </div>
            <input type="file" id="image_file_pick" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp" style="display:none;">
            <div id="image-progress-wrap" style="display:none; margin-top: 8px;">
                <div style="background: #eef2f8; border-radius: 999px; height: 6px; overflow: hidden;">
                    <div id="image-progress-bar" style="height: 100%; background: #0f7a72; width: 0%; transition: width .1s;"></div>
                </div>
                <div id="image-progress-text" style="font-size: 0.82rem; color: #607086; margin-top: 4px;">업로드 중…</div>
            </div>
        </div>

        {{-- VIDEO --}}
        <div style="margin-bottom: 20px; display: {{ old('type', $banner->type) === 'video' ? 'block' : 'none' }};" id="video-field">
            <label style="display: block; margin-bottom: 6px; font-weight: 600; color: #15243a;">영상 URL</label>
            <input type="url" name="video_url" value="{{ old('video_url', $banner->video_url) }}" style="width: 100%; padding: 10px 12px; border: 1px solid #d6e0ea; border-radius: 6px; font-family: inherit; font-size: 1rem; box-sizing: border-box;">
            <p style="margin: 10px 0 6px; color: #607086; font-size: 0.9rem;">또는 파일 직접 업로드 (최대 100MB · MP4, WebM, OGG)</p>
            <input type="hidden" name="video_path" id="video_path" value="{{ old('video_path', $banner->video_path) }}">
            <div id="video-upload-area" style="border: 2px dashed #d6e0ea; border-radius: 8px; padding: 20px; text-align: center; cursor: pointer; transition: border-color .2s;" onclick="document.getElementById('video_file_pick').click()">
                <div id="video-upload-label" style="color: #607086; font-size: 0.9rem;">
                    @if($banner->video_path)
                        현재: {{ basename($banner->video_path) }} — 새 파일로 교체하려면 클릭하세요
                    @else
                        파일을 클릭하거나 여기에 드롭하세요
                    @endif
                </div>
            </div>
            <input type="file" id="video_file_pick" accept="video/mp4,video/webm,video/ogg" style="display:none;">
            <div id="video-progress-wrap" style="display:none; margin-top: 8px;">
                <div style="background: #eef2f8; border-radius: 999px; height: 6px; overflow: hidden;">
                    <div id="video-progress-bar" style="height: 100%; background: #0f7a72; width: 0%; transition: width .1s;"></div>
                </div>
                <div id="video-progress-text" style="font-size: 0.82rem; color: #607086; margin-top: 4px;">업로드 중…</div>
            </div>
        </div>

        {{-- TEXT --}}
        <div style="margin-bottom: 20px; display: {{ old('type', $banner->type) === 'text' ? 'block' : 'none' }};" id="text-field">
            <label style="display: block; margin-bottom: 6px; font-weight: 600; color: #15243a;">텍스트 내용</label>
            <textarea name="text_content" style="width: 100%; padding: 10px 12px; border: 1px solid #d6e0ea; border-radius: 6px; font-family: inherit; font-size: 1rem; min-height: 100px; resize: vertical; box-sizing: border-box;">{{ old('text_content', $banner->text_content) }}</textarea>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px;">
            <div>
                <label style="display: block; margin-bottom: 6px; font-weight: 600; color: #15243a;">배너 링크 URL</label>
                <input type="url" name="button_url" value="{{ old('button_url', $banner->button_url) }}" style="width: 100%; padding: 10px 12px; border: 1px solid #d6e0ea; border-radius: 6px; font-family: inherit; font-size: 1rem; box-sizing: border-box;">
                <p style="margin: 6px 0 0; color: #607086; font-size: 0.85rem;">배너 전체 영역이 이 링크로 연결됩니다</p>
            </div>
            <div>
                <label style="display: block; margin-bottom: 6px; font-weight: 600; color: #15243a;">정렬순서</label>
                <input type="number" name="sort_order" value="{{ old('sort_order', $banner->sort_order) }}" min="0" style="width: 100%; padding: 10px 12px; border: 1px solid #d6e0ea; border-radius: 6px; font-family: inherit; font-size: 1rem; box-sizing: border-box;">
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px;">
            <div>
                <label style="display: block; margin-bottom: 6px; font-weight: 600; color: #15243a;">노출 시작일</label>
                <input type="date" name="start_date" value="{{ old('start_date', $banner->start_date?->format('Y-m-d')) }}" style="width: 100%; padding: 10px 12px; border: 1px solid #d6e0ea; border-radius: 6px; font-family: inherit; font-size: 1rem; box-sizing: border-box;">
            </div>
            <div>
                <label style="display: block; margin-bottom: 6px; font-weight: 600; color: #15243a;">노출 종료일</label>
                <input type="date" name="end_date" value="{{ old('end_date', $banner->end_date?->format('Y-m-d')) }}" style="width: 100%; padding: 10px 12px; border: 1px solid #d6e0ea; border-radius: 6px; font-family: inherit; font-size: 1rem; box-sizing: border-box;">
            </div>
        </div>

        <div style="margin-bottom: 20px;">
            <label style="display: flex; align-items: center; gap: 8px;">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $banner->is_active) ? 'checked' : '' }} style="width: 18px; height: 18px; cursor: pointer;">
                <span style="color: #15243a;">활성 상태로 설정</span>
            </label>
        </div>

        <div id="submit-area" style="display: flex; gap: 12px; align-items: center;">
            <button type="submit" id="submit-btn" class="btn btn-primary">배너 수정</button>
            <a href="{{ route('admin.banners.index') }}" class="btn btn-soft">취소</a>
            <span id="uploading-notice" style="display:none; font-size: 0.88rem; color: #a61b1b;">파일 업로드 중입니다. 완료 후 저장해 주세요.</span>
        </div>
    </form>
</div>

<script>
@include('admin.banners._upload-js', ['csrfToken' => csrf_token()])
</script>
@endsection
