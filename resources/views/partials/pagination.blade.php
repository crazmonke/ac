@php
    /** @var \Illuminate\Contracts\Pagination\LengthAwarePaginator|\Illuminate\Pagination\LengthAwarePaginator $paginator */
@endphp

@if($paginator->hasPages())
    <style>
        .pager-wrap { margin-top: 12px; }
        .pager-nav {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
        }
        .pager-link,
        .pager-current,
        .pager-disabled {
            min-width: 34px;
            height: 34px;
            border-radius: 9px;
            border: 1px solid #d5dfec;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0 10px;
            text-decoration: none;
            font-weight: 700;
            font-size: 0.88rem;
            background: #fff;
            color: #20344f;
        }
        .pager-link:hover { border-color: #0f6f67; color: #0f6f67; }
        .pager-current {
            background: #0f6f67;
            border-color: #0f6f67;
            color: #fff;
        }
        .pager-disabled {
            color: #94a4b8;
            background: #f7f9fc;
        }
        .pager-meta {
            margin-top: 8px;
            color: #5b6d82;
            font-size: 0.82rem;
        }
    </style>

    <div class="pager-wrap">
        <nav class="pager-nav" aria-label="페이지 이동">
            @if($paginator->onFirstPage())
                <span class="pager-disabled" aria-disabled="true">이전</span>
            @else
                <a class="pager-link" href="{{ $paginator->previousPageUrl() }}" rel="prev">이전</a>
            @endif

            @foreach($paginator->getUrlRange(1, $paginator->lastPage()) as $page => $url)
                @if($page === $paginator->currentPage())
                    <span class="pager-current" aria-current="page">{{ $page }}</span>
                @else
                    <a class="pager-link" href="{{ $url }}">{{ $page }}</a>
                @endif
            @endforeach

            @if($paginator->hasMorePages())
                <a class="pager-link" href="{{ $paginator->nextPageUrl() }}" rel="next">다음</a>
            @else
                <span class="pager-disabled" aria-disabled="true">다음</span>
            @endif
        </nav>

        <div class="pager-meta">총 {{ number_format($paginator->total()) }}개 · {{ $paginator->currentPage() }}/{{ $paginator->lastPage() }} 페이지</div>
    </div>
@endif
