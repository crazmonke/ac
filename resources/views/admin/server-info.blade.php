<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>서버 정보 — 관리자</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; margin: 0; background: #f5f7fb; color: #1a2a44; }
        .wrap { max-width: 860px; margin: 0 auto; padding: 24px; }
        .card { background: #fff; border: 1px solid #dce4ef; border-radius: 12px; padding: 20px; margin-bottom: 16px; }
        h1 { margin-top: 0; }
        h2 { margin: 0 0 12px; font-size: 1rem; color: #3a5070; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 9px 12px; border-bottom: 1px solid #edf1f7; text-align: left; font-size: 0.92rem; }
        th { font-weight: 700; color: #3a5070; width: 220px; }
        .badge { display: inline-block; padding: 2px 10px; border-radius: 999px; font-size: 0.8rem; font-weight: 700; }
        .ok   { background: #d1fae5; color: #065f46; }
        .warn { background: #fef9c3; color: #713f12; }
        .bad  { background: #fee2e2; color: #991b1b; }
        .note { font-size: 0.85rem; color: #607086; margin-top: 8px; }
    </style>
</head>
<body>
<div class="wrap">
    @include('partials.admin-nav')
    <h1>서버 정보</h1>

    <div class="card">
        <h2>PHP 업로드 설정</h2>
        @php
            $recommended = [
                'upload_max_filesize' => '512M',
                'post_max_size'       => '600M',
                'max_execution_time'  => '300',
                'max_input_time'      => '300',
                'memory_limit'        => '256M',
            ];

            $toBytes = function (?string $value): int {
                $raw = trim((string) $value);
                if ($raw === '' || $raw === '-1') return PHP_INT_MAX;
                $unit = strtolower(substr($raw, -1));
                $number = (float) $raw;
                if (ctype_alpha($unit)) {
                    match($unit) {
                        'g' => $number *= 1024 * 1024 * 1024,
                        'm' => $number *= 1024 * 1024,
                        'k' => $number *= 1024,
                        default => null,
                    };
                }
                return (int) $number;
            };
        @endphp
        <table>
            <thead>
                <tr><th>설정 항목</th><th>현재 값</th><th>권장 값</th><th>상태</th></tr>
            </thead>
            <tbody>
                @foreach($phpSettings as $key => $value)
                    @php
                        $rec = $recommended[$key] ?? null;
                        $currentBytes = $toBytes($value);
                        $recBytes = $rec ? $toBytes($rec) : null;
                        if (!$rec) {
                            $status = 'ok';
                        } elseif ($currentBytes >= $recBytes) {
                            $status = 'ok';
                        } elseif ($currentBytes >= $recBytes * 0.5) {
                            $status = 'warn';
                        } else {
                            $status = 'bad';
                        }
                        $labels = ['ok' => '충족', 'warn' => '부족', 'bad' => '미달'];
                    @endphp
                    <tr>
                        <th>{{ $key }}</th>
                        <td><code>{{ $value ?: '(미설정)' }}</code></td>
                        <td><code>{{ $rec ?? '—' }}</code></td>
                        <td><span class="badge {{ $status }}">{{ $labels[$status] }}</span></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <p class="note">
            상태가 <strong>부족/미달</strong>이면 <code>public/.htaccess</code>의 <code>php_value</code> 설정이 서버에서 허용되지 않는 것입니다.<br>
            Cafe24 호스팅 관리 패널 → PHP 설정에서 직접 변경하거나, 고객센터에 문의해 주세요.
        </p>
    </div>

    <div class="card">
        <h2>ffmpeg (영상 자동 압축)</h2>
        <table>
            <tbody>
                <tr>
                    <th>사용 가능 여부</th>
                    <td>
                        @if($ffmpegPath)
                            <span class="badge ok">사용 가능</span>
                            <code style="margin-left:8px;">{{ $ffmpegPath }}</code>
                        @else
                            <span class="badge bad">사용 불가</span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <th>버전</th>
                    <td><code>{{ $ffmpegVersion }}</code></td>
                </tr>
            </tbody>
        </table>
        @if(!$ffmpegPath)
            <p class="note">ffmpeg를 사용할 수 없으면 영상 자동 압축이 동작하지 않습니다. Cafe24 고객센터에 ffmpeg 설치 여부를 확인해 주세요.</p>
        @endif
    </div>
</div>
</body>
</html>
