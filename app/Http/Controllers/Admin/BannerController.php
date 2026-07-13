<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\Request;

class BannerController extends Controller
{
    private function saveBannerFile(\Illuminate\Http\UploadedFile $file): string
    {
        $ext      = strtolower($file->getClientOriginalExtension());
        $filename = 'banner_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $destDir  = public_path('uploads/banners');

        if (! is_dir($destDir)) {
            mkdir($destDir, 0755, true);
        }

        $file->move($destDir, $filename);

        return 'uploads/banners/' . $filename;
    }

    private function deleteBannerFile(?string $path): void
    {
        if ($path && file_exists(public_path($path))) {
            @unlink(public_path($path));
        }
    }

    public function uploadTemp(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:jpeg,jpg,png,gif,webp,mp4,webm,ogg|max:102400',
        ]);

        $path = $this->saveBannerFile($request->file('file'));

        return response()->json([
            'path' => $path,
            'url'  => asset($path),
        ]);
    }

    public function index()
    {
        $banners = Banner::ordered()->paginate(15);

        return view('admin.banners.index', compact('banners'));
    }

    public function create()
    {
        return view('admin.banners.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'        => 'required|string|max:255',
            'description'  => 'nullable|string|max:500',
            'type'         => 'required|in:image,video,text',
            'image_url'    => 'nullable|url',
            'image_path'   => 'nullable|string|max:500',
            'image_file'   => 'nullable|file|mimes:jpeg,jpg,png,gif,webp|max:10240',
            'video_url'    => 'nullable|url',
            'video_path'   => 'nullable|string|max:500',
            'video_file'   => 'nullable|file|mimes:mp4,webm,ogg|max:102400',
            'text_content' => 'nullable|string',
            'button_url'   => 'nullable|url',
            'sort_order'   => 'nullable|integer',
            'is_active'    => 'boolean',
            'start_date'   => 'nullable|date',
            'end_date'     => 'nullable|date|after_or_equal:start_date',
        ]);

        // 파일이 직접 첨부된 경우 (fallback)
        if ($request->hasFile('image_file') && $request->file('image_file')->isValid()) {
            $validated['image_path'] = $this->saveBannerFile($request->file('image_file'));
        }
        if ($request->hasFile('video_file') && $request->file('video_file')->isValid()) {
            $validated['video_path'] = $this->saveBannerFile($request->file('video_file'));
        }

        Banner::create($validated);

        return redirect()->route('admin.banners.index')->with('success', '배너가 등록되었습니다.');
    }

    public function show(Banner $banner)
    {
        return response()->json($banner);
    }

    public function edit(Banner $banner)
    {
        return view('admin.banners.edit', compact('banner'));
    }

    public function update(Request $request, Banner $banner)
    {
        $validated = $request->validate([
            'title'        => 'required|string|max:255',
            'description'  => 'nullable|string|max:500',
            'type'         => 'required|in:image,video,text',
            'image_url'    => 'nullable|url',
            'image_path'   => 'nullable|string|max:500',
            'image_file'   => 'nullable|file|mimes:jpeg,jpg,png,gif,webp|max:10240',
            'video_url'    => 'nullable|url',
            'video_path'   => 'nullable|string|max:500',
            'video_file'   => 'nullable|file|mimes:mp4,webm,ogg|max:102400',
            'text_content' => 'nullable|string',
            'button_url'   => 'nullable|url',
            'sort_order'   => 'nullable|integer',
            'is_active'    => 'boolean',
            'start_date'   => 'nullable|date',
            'end_date'     => 'nullable|date|after_or_equal:start_date',
        ]);

        // 새 이미지가 업로드된 경우 기존 파일 삭제
        $newImagePath = $validated['image_path'] ?? null;
        if ($newImagePath && $newImagePath !== $banner->image_path) {
            $this->deleteBannerFile($banner->image_path);
        }
        if ($request->hasFile('image_file') && $request->file('image_file')->isValid()) {
            $this->deleteBannerFile($banner->image_path);
            $validated['image_path'] = $this->saveBannerFile($request->file('image_file'));
        }

        $newVideoPath = $validated['video_path'] ?? null;
        if ($newVideoPath && $newVideoPath !== $banner->video_path) {
            $this->deleteBannerFile($banner->video_path);
        }
        if ($request->hasFile('video_file') && $request->file('video_file')->isValid()) {
            $this->deleteBannerFile($banner->video_path);
            $validated['video_path'] = $this->saveBannerFile($request->file('video_file'));
        }

        $banner->update($validated);

        return redirect()->route('admin.banners.index')->with('success', '배너가 수정되었습니다.');
    }

    public function destroy(Banner $banner)
    {
        $this->deleteBannerFile($banner->image_path);
        $this->deleteBannerFile($banner->video_path);

        $banner->delete();

        return redirect()->route('admin.banners.index')->with('success', '배너가 삭제되었습니다.');
    }

    public function reorder(Request $request)
    {
        $validated = $request->validate([
            'banner_ids'   => 'required|array',
            'banner_ids.*' => 'integer|exists:banners,id',
        ]);

        foreach ($validated['banner_ids'] as $index => $bannerId) {
            Banner::where('id', $bannerId)->update(['sort_order' => $index]);
        }

        return response()->json(['success' => true]);
    }
}
