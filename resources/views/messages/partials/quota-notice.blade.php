{{-- 쪽지 발송 한도 안내 ($messageQuota = MessageService::quotaFor()) --}}
@if(!empty($messageQuota) && $messageQuota['free_limit'] > 0)
    <div class="notice" style="margin-bottom:10px; font-size:0.88rem;">
        @if($messageQuota['free_remaining'] > 0)
            오늘 무료 발송 <strong>{{ $messageQuota['free_remaining'] }}건</strong> 남았습니다.
            (일 {{ $messageQuota['free_limit'] }}건, 매일 초기화)
        @elseif($messageQuota['cost'] > 0)
            @php
                $quotaBalanceText = number_format($messageQuota['balance']).'P';
                if ($messageQuota['min_spend'] > 0) {
                    $quotaBalanceText .= ', '.number_format($messageQuota['min_spend']).'P 이상 보유 시 사용 가능';
                }
            @endphp
            오늘 무료 발송 {{ $messageQuota['free_limit'] }}건을 모두 사용했습니다.
            추가 발송 시 건당 <strong>{{ number_format($messageQuota['cost']) }}P</strong>가 차감됩니다.
            (보유 {{ $quotaBalanceText }})
        @endif
    </div>
@endif
