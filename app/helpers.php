<?php

use Carbon\Carbon;

if (! function_exists('format_relative_time')) {
    /**
     * Threads 스타일로 상대 시간을 반환합니다.
     * 예: 방금, 5분, 22시간, 3일, 2주, 1개월, 2026.01.15
     */
    function format_relative_time(Carbon|string|null $datetime): string
    {
        if ($datetime === null) {
            return '';
        }

        $carbon = $datetime instanceof Carbon ? $datetime : Carbon::parse($datetime);
        $diffSeconds = (int) $carbon->diffInSeconds(now());

        if ($diffSeconds < 60) {
            return '방금';
        }

        $diffMinutes = (int) $carbon->diffInMinutes(now());
        if ($diffMinutes < 60) {
            return $diffMinutes . '분';
        }

        $diffHours = (int) $carbon->diffInHours(now());
        if ($diffHours < 24) {
            return $diffHours . '시간';
        }

        $diffDays = (int) $carbon->diffInDays(now());
        if ($diffDays < 7) {
            return $diffDays . '일';
        }

        $diffWeeks = (int) $carbon->diffInWeeks(now());
        if ($diffWeeks < 5) {
            return $diffWeeks . '주';
        }

        $diffMonths = (int) $carbon->diffInMonths(now());
        if ($diffMonths < 12) {
            return $diffMonths . '개월';
        }

        return $carbon->format('Y.m.d');
    }
}
