<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BannerController extends Controller
{
    private function handleFileUpload(Request $request, string $fileFieldName, string $pathFieldName): ?string
    {
        if ($request->hasFile($fileFieldName)) {
            $file = $request->file($fileFieldName);
            if ($file->isValid()) {
                $path = $file->store('banners', 'public');
                return $path;
            }
        }
        return null;
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
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'type' => 'required|in:image,video,text',
            'image_url' => 'nullable|url',
            'image_file' => 'nullable|file|mimes:jpeg,jpg,png,gif,webp|max:10240',
            'video_url' => 'nullable|url',
            'video_file' => 'nullable|file|mimes:mp4,webm,ogg|max:102400',
            'text_content' => 'nullable|string',
            'button_url' => 'nullable|url',
            'sort_order' => 'nullable|integer',
            'is_active' => 'boolean',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        // 파일 업로드 처리
        if ($imagePath = $this->handleFileUpload($request, 'image_file', 'image_path')) {
            $validated['image_path'] = $imagePath;
        }

        if ($videoPath = $this->handleFileUpload($request, 'video_file', 'video_path')) {
            $validated['video_path'] = $videoPath;
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
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'type' => 'required|in:image,video,text',
            'image_url' => 'nullable|url',
            'image_file' => 'nullable|file|mimes:jpeg,jpg,png,gif,webp|max:10240',
            'video_url' => 'nullable|url',
            'video_file' => 'nullable|file|mimes:mp4,webm,ogg|max:102400',
            'text_content' => 'nullable|string',
            'button_url' => 'nullable|url',
            'sort_order' => 'nullable|integer',
            'is_active' => 'boolean',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        // 기존 파일 삭제 및 새 파일 업로드
        if ($request->hasFile('image_file')) {
            if ($banner->image_path && Storage::disk('public')->exists($banner->image_path)) {
                Storage::disk('public')->delete($banner->image_path);
            }
            if ($imagePath = $this->handleFileUpload($request, 'image_file', 'image_path')) {
                $validated['image_path'] = $imagePath;
            }
        }

        if ($request->hasFile('video_file')) {
            if ($banner->video_path && Storage::disk('public')->exists($banner->video_path)) {
                Storage::disk('public')->delete($banner->video_path);
            }
            if ($videoPath = $this->handleFileUpload($request, 'video_file', 'video_path')) {
                $validated['video_path'] = $videoPath;
            }
        }

        $banner->update($validated);

        return redirect()->route('admin.banners.index')->with('success', '배너가 수정되었습니다.');
    }

    public function destroy(Banner $banner)
    {
        // 파일 삭제
        if ($banner->image_path && Storage::disk('public')->exists($banner->image_path)) {
            Storage::disk('public')->delete($banner->image_path);
        }
        if ($banner->video_path && Storage::disk('public')->exists($banner->video_path)) {
            Storage::disk('public')->delete($banner->video_path);
        }

        $banner->delete();

        return redirect()->route('admin.banners.index')->with('success', '배너가 삭제되었습니다.');
    }

    public function reorder(Request $request)
    {
        $validated = $request->validate([
            'banner_ids' => 'required|array',
            'banner_ids.*' => 'integer|exists:banners,id',
        ]);

        foreach ($validated['banner_ids'] as $index => $bannerId) {
            Banner::where('id', $bannerId)->update(['sort_order' => $index]);
        }

        return response()->json(['success' => true]);
    }
}
