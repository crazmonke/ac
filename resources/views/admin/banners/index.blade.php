@extends('admin.layout')

@section('content')
<div style="max-width: 1200px; margin: 0 auto; padding: 24px 16px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <h1 style="margin: 0;">배너 관리</h1>
        <a href="{{ route('admin.banners.create') }}" class="btn btn-primary">새 배너 추가</a>
    </div>

    @if(session('success'))
        <div style="padding: 12px 16px; background: #d1f2eb; border: 1px solid #0f7a72; border-radius: 8px; color: #0f7a72; margin-bottom: 16px;">
            {{ session('success') }}
        </div>
    @endif

    @if($banners->isEmpty())
        <div style="padding: 24px; background: #f8f9fa; border: 1px solid #e0e6ed; border-radius: 8px; text-align: center;">
            <p style="color: #607086; margin: 0;">등록된 배너가 없습니다.</p>
            <a href="{{ route('admin.banners.create') }}" class="btn btn-primary" style="margin-top: 12px;">첫 배너 추가하기</a>
        </div>
    @else
        <div style="background: #fff; border: 1px solid #e0e6ed; border-radius: 8px; overflow: hidden;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead style="background: #f8f9fa; border-bottom: 1px solid #e0e6ed;">
                    <tr>
                        <th style="padding: 12px 16px; text-align: left; font-weight: 600; color: #15243a;">제목</th>
                        <th style="padding: 12px 16px; text-align: left; font-weight: 600; color: #15243a;">유형</th>
                        <th style="padding: 12px 16px; text-align: center; font-weight: 600; color: #15243a;">상태</th>
                        <th style="padding: 12px 16px; text-align: center; font-weight: 600; color: #15243a;">기간</th>
                        <th style="padding: 12px 16px; text-align: right; font-weight: 600; color: #15243a;">작업</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($banners as $banner)
                        <tr style="border-bottom: 1px solid #e0e6ed;">
                            <td style="padding: 12px 16px; color: #15243a;">{{ $banner->title }}</td>
                            <td style="padding: 12px 16px;">
                                <span style="display: inline-block; padding: 4px 8px; background: #f0f2f5; border-radius: 4px; font-size: 0.85rem;">
                                    @if($banner->type === 'image')
                                        🖼️ 이미지
                                    @elseif($banner->type === 'video')
                                        🎬 영상
                                    @else
                                        📝 텍스트
                                    @endif
                                </span>
                            </td>
                            <td style="padding: 12px 16px; text-align: center;">
                                @if($banner->is_active)
                                    <span style="display: inline-block; padding: 4px 8px; background: #d1f2eb; border-radius: 4px; color: #0f7a72; font-size: 0.85rem; font-weight: 600;">활성</span>
                                @else
                                    <span style="display: inline-block; padding: 4px 8px; background: #f0f2f5; border-radius: 4px; color: #607086; font-size: 0.85rem; font-weight: 600;">비활성</span>
                                @endif
                            </td>
                            <td style="padding: 12px 16px; text-align: center; font-size: 0.9rem; color: #607086;">
                                @if($banner->start_date && $banner->end_date)
                                    {{ $banner->start_date->format('m.d') }} ~ {{ $banner->end_date->format('m.d') }}
                                @elseif($banner->start_date)
                                    {{ $banner->start_date->format('m.d') }} ~
                                @elseif($banner->end_date)
                                    ~ {{ $banner->end_date->format('m.d') }}
                                @else
                                    무제한
                                @endif
                            </td>
                            <td style="padding: 12px 16px; text-align: right;">
                                <a href="{{ route('admin.banners.edit', $banner) }}" style="color: #0f7a72; text-decoration: none; margin-right: 8px;">수정</a>
                                <form method="post" action="{{ route('admin.banners.destroy', $banner) }}" style="display: inline;" onsubmit="return confirm('정말 삭제하시겠습니까?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" style="background: none; border: none; color: #a61b1b; cursor: pointer; text-decoration: none;">삭제</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div style="margin-top: 24px;">
            {{ $banners->links('pagination::bootstrap-4') }}
        </div>
    @endif
</div>
@endsection
