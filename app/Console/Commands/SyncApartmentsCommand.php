<?php

namespace App\Console\Commands;

use App\Models\Apartment;
use App\Models\ApartmentAlias;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class SyncApartmentsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'apartments:sync
        {--source=gov : Data source key, e.g. gov or file}
        {--path=storage/app/import/apartments.json : Local JSON path used for file source}
        {--url= : Remote JSON URL used for gov source}
        {--service-key= : Government API service key used for gov source}
        {--kapt-code= : K-APT apartment code used by the gov lookup API}
        {--rows=500 : Page size for gov source pagination}
        {--start-page=1 : Starting page for gov source pagination}
        {--max-pages=0 : Max number of gov pages to process (0 means no limit)}
        {--deactivate-missing : Set source-bound apartments missing from this sync to inactive}
        {--dry-run : Validate and report without writing to DB}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize apartment master data and aliases from an external source';

    public function handle(): int
    {
        DB::connection()->disableQueryLog();

        $source = trim((string) $this->option('source'));
        $dryRun = (bool) $this->option('dry-run');

        if ($source === '') {
            $this->error('source 옵션이 비어 있습니다. --source=gov 또는 --source=file을 지정해 주세요.');

            return self::FAILURE;
        }

        $stats = [
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'aliases' => 0,
            'invalid' => 0,
        ];

        $seenSourceKeys = [];
        $processedRows = 0;

        if ($source === 'gov') {
            $this->loadRowsFromGov(function (Collection $chunk) use ($source, $dryRun, &$stats, &$seenSourceKeys, &$processedRows): void {
                $processedRows += $chunk->count();
                $this->processRows($chunk, $source, $dryRun, $stats, $seenSourceKeys);
            });
        } else {
            $rows = $this->loadRows($source);

            if ($rows->isEmpty()) {
                $this->warn('동기화할 데이터가 없습니다.');

                return self::SUCCESS;
            }

            $processedRows = $rows->count();
            $this->processRows($rows, $source, $dryRun, $stats, $seenSourceKeys);
        }

        if ($processedRows === 0) {
            $this->warn('동기화할 데이터가 없습니다.');

            return self::SUCCESS;
        }

        $deactivated = 0;

        if (! $dryRun && (bool) $this->option('deactivate-missing')) {
            $deactivated = $this->deactivateMissing($source, $seenSourceKeys);
        }

        $this->line('공동주택 동기화 결과');
        $this->line('- source: ' . $source);
        $this->line('- rows: ' . $processedRows);
        $this->line('- created: ' . $stats['created']);
        $this->line('- updated: ' . $stats['updated']);
        $this->line('- skipped(unchanged): ' . $stats['skipped']);
        $this->line('- aliases processed: ' . $stats['aliases']);
        $this->line('- invalid rows: ' . $stats['invalid']);
        $this->line('- deactivated: ' . $deactivated);

        if ($dryRun) {
            $this->warn('dry-run 모드이므로 DB에는 반영되지 않았습니다.');
        }

        return self::SUCCESS;
    }

    private function processRows(Collection $rows, string $source, bool $dryRun, array &$stats, array &$seenSourceKeys): void
    {
        foreach ($rows as $row) {
            $payload = $this->normalizeRow((array) $row, $source);

            if ($payload === null) {
                $stats['invalid']++;
                continue;
            }

            if ($payload['source_key'] !== null) {
                $seenSourceKeys[] = $payload['source_key'];
            }

            $apartment = $this->resolveApartment($payload);
            $isNew = ! $apartment;

            if ($isNew) {
                $apartment = new Apartment();
            }

            $dirty = $this->fillApartment($apartment, $payload);

            if (! $dryRun) {
                $apartment->save();
                $stats['aliases'] += $this->syncAliases($apartment, $payload['aliases'], $source);
            } elseif (! $isNew) {
                // dry-run에서는 id가 있어야 alias 매핑 시뮬레이션이 가능하므로 현 단계에서는 건너뜀
            }

            if ($isNew) {
                $stats['created']++;
            } elseif ($dirty) {
                $stats['updated']++;
            } else {
                $stats['skipped']++;
            }
        }
    }

    private function loadRows(string $source): Collection
    {
        return match ($source) {
            'file' => $this->loadRowsFromFile(),
            'gov' => $this->loadRowsFromGov(),
            default => $this->loadRowsFromRemote(),
        };
    }

    private function loadRowsFromGov(?callable $onChunk = null): Collection
    {
        $baseUrl = trim((string) ($this->option('url') ?: config('services.apartment_sync.source_url', 'https://apis.data.go.kr/1613000/AptListService3')));
        $serviceKey = trim((string) ($this->option('service-key') ?: config('services.apartment_sync.service_key', '')));
        $rowsPerPage = max(1, min(1000, (int) $this->option('rows')));
        $startPage = max(1, (int) $this->option('start-page'));
        $maxPages = max(0, (int) $this->option('max-pages'));

        if ($serviceKey === '') {
            $this->error('정부 API 서비스키가 비어 있습니다. --service-key 옵션 또는 APARTMENT_SYNC_SERVICE_KEY 설정이 필요합니다.');

            return collect();
        }

        $serviceType = str_contains(strtolower($baseUrl), 'aptlistservice3') ? 'list' : 'basis';
        $endpointPath = $serviceType === 'list' ? '/getTotalAptList3' : '/getAphusBassInfoV4';
        $endpoint = rtrim($baseUrl, '/') . $endpointPath;
        $page = $startPage;
        $totalCount = null;
        $rows = collect();
        $kaptCode = trim((string) $this->option('kapt-code'));
        $processedPages = 0;

        do {
            $query = [
                'serviceKey' => $serviceKey,
                'pageNo' => $page,
                'numOfRows' => $rowsPerPage,
                '_type' => 'json',
            ];

            if ($kaptCode !== '') {
                $query['kaptCode'] = $kaptCode;
            }

            $response = Http::timeout(40)
                ->retry(2, 800, throw: false)
                ->acceptJson()
                ->get($endpoint, $query);

            if (! $response->successful()) {
                $this->error('정부 API 조회 실패: HTTP ' . $response->status() . ' (page ' . $page . ')');

                return collect();
            }

            $payload = $response->json();

            if (! is_array($payload)) {
                $raw = trim((string) $response->body());

                if (stripos($raw, 'forbidden') !== false) {
                    $this->error('정부 API 응답이 Forbidden 입니다. 해당 API(15057332) 활용신청 승인/활성화 여부, 일반인증키(Decoding) 값, 호출 계정 일치 여부를 확인해 주세요.');
                } elseif (stripos($raw, 'unauthorized') !== false) {
                    $this->error('정부 API 응답이 Unauthorized 입니다. 일반인증키(Decoding) 값이 정확한지 확인해 주세요.');
                } else {
                    $this->error('정부 API 응답이 JSON 객체가 아닙니다. 원문: ' . mb_substr($raw, 0, 180));
                }

                return collect();
            }

            $resultCode = Arr::get($payload, 'response.header.resultCode');
            if ($resultCode !== null && (string) $resultCode !== '00') {
                $this->error('정부 API 오류: ' . (string) Arr::get($payload, 'response.header.resultMsg', 'Unknown error'));

                return collect();
            }

            $items = Arr::get($payload, 'response.body.items.item', Arr::get($payload, 'response.body.items', Arr::get($payload, 'response.body.item', [])));

            if (is_array($items) && ! Arr::isList($items)) {
                $items = [$items];
            }

            if (! is_array($items)) {
                $items = [];
            }

            $pageRows = collect();

            foreach ($items as $item) {
                if (is_array($item)) {
                    $mapped = $this->mapGovItem($item);

                    if ($mapped !== null) {
                        $pageRows->push($mapped);
                    }
                }
            }

            if ($page === 1 && empty($items)) {
                if ($serviceType === 'basis') {
                    $this->warn('정부 API가 빈 결과를 반환했습니다. 이 API는 전국 목록 API가 아니라 kaptCode 기반 조회 API일 가능성이 높습니다. --kapt-code 옵션으로 단일 단지를 조회하거나, 전국 초기 적재용 별도 목록 소스가 필요합니다.');
                } else {
                    $this->warn('정부 API가 빈 결과를 반환했습니다. 조회 조건/활용신청 상태/트래픽 제한을 확인해 주세요.');
                }
            }

            if ($onChunk !== null && $pageRows->isNotEmpty()) {
                $onChunk($pageRows->values());
            } else {
                $rows = $rows->merge($pageRows);
            }

            $this->line(sprintf(
                'gov sync page=%d rows=%d total=%s',
                $page,
                $pageRows->count(),
                $totalCount === null ? 'unknown' : (string) $totalCount
            ));

            $totalCount ??= (int) Arr::get($payload, 'response.body.totalCount', 0);
            $page++;
            $processedPages++;

            if ($maxPages > 0 && $processedPages >= $maxPages) {
                $this->warn(sprintf(
                    'max-pages limit reached (%d). Resume with --start-page=%d to continue.',
                    $maxPages,
                    $page
                ));
                break;
            }

            $hasMoreByTotal = $totalCount === null
                ? true
                : ($page <= (int) ceil(max(1, $totalCount) / $rowsPerPage));
        } while (! empty($items) && $hasMoreByTotal);

        return $onChunk !== null
            ? collect()
            : $rows->filter()->values();
    }

    private function loadRowsFromFile(): Collection
    {
        $path = (string) $this->option('path');
        $absolutePath = str_starts_with($path, '/') ? $path : base_path($path);

        if (! is_file($absolutePath)) {
            $this->error('동기화 파일을 찾을 수 없습니다: ' . $absolutePath);

            return collect();
        }

        $raw = file_get_contents($absolutePath);

        if ($raw === false) {
            $this->error('동기화 파일을 읽지 못했습니다: ' . $absolutePath);

            return collect();
        }

        $decoded = json_decode($raw, true);

        if (! is_array($decoded)) {
            $this->error('JSON 형식이 올바르지 않습니다: ' . $absolutePath);

            return collect();
        }

        return $this->extractRows($decoded);
    }

    private function loadRowsFromRemote(): Collection
    {
        $url = trim((string) ($this->option('url') ?: env('APARTMENT_SYNC_SOURCE_URL', '')));

        if ($url === '') {
            $this->error('원격 동기화 URL이 비어 있습니다. --url 옵션 또는 APARTMENT_SYNC_SOURCE_URL 설정이 필요합니다.');

            return collect();
        }

        $response = Http::timeout(25)->retry(2, 700)->get($url);

        if (! $response->successful()) {
            $this->error('원격 데이터 조회 실패: HTTP ' . $response->status());

            return collect();
        }

        $decoded = $response->json();

        if (! is_array($decoded)) {
            $this->error('원격 응답이 JSON 객체/배열이 아닙니다.');

            return collect();
        }

        return $this->extractRows($decoded);
    }

    private function extractRows(array $decoded): Collection
    {
        if (Arr::isList($decoded)) {
            return collect($decoded);
        }

        $candidates = [
            Arr::get($decoded, 'data'),
            Arr::get($decoded, 'items'),
            Arr::get($decoded, 'apartments'),
        ];

        foreach ($candidates as $candidate) {
            if (is_array($candidate)) {
                return collect($candidate);
            }
        }

        return collect();
    }

    private function normalizeRow(array $row, string $source): ?array
    {
        $name = trim((string) Arr::get($row, 'name', ''));
        $sido = trim((string) Arr::get($row, 'sido', ''));
        $sigungu = trim((string) Arr::get($row, 'sigungu', ''));
        $eupmyeondong = trim((string) Arr::get($row, 'eupmyeondong', ''));
        $roadAddress = trim((string) Arr::get($row, 'road_address', ''));

        if ($name === '' || $sido === '' || $sigungu === '' || $eupmyeondong === '' || $roadAddress === '') {
            return null;
        }

        $sourceKey = trim((string) (Arr::get($row, 'source_key', Arr::get($row, 'apartment_code', ''))));

        $aliases = collect((array) Arr::get($row, 'aliases', []))
            ->map(fn ($alias) => trim((string) $alias))
            ->filter()
            ->unique()
            ->values()
            ->all();

        return [
            'name' => $name,
            'sido' => $sido,
            'sigungu' => $sigungu,
            'eupmyeondong' => $eupmyeondong,
            'road_address' => $roadAddress,
            'jibun_address' => trim((string) Arr::get($row, 'jibun_address', '')) ?: null,
            'source' => $source,
            'source_key' => $sourceKey !== '' ? $sourceKey : null,
            'normalized_name' => $this->normalizeKoreanText($name),
            'aliases' => $aliases,
        ];
    }

    private function mapGovItem(array $item): ?array
    {
        $name = trim((string) ($item['kaptName'] ?? $item['kaptname'] ?? $item['name'] ?? ''));
        $sido = trim((string) ($item['as1'] ?? $item['sido'] ?? ''));
        $sigungu = trim((string) ($item['as2'] ?? $item['sigungu'] ?? ''));
        $eupmyeondong = trim((string) ($item['as3'] ?? $item['bjdCodeNm'] ?? $item['eupmyeondong'] ?? ''));
        $roadAddress = trim((string) ($item['doroJuso'] ?? $item['roadAddr'] ?? $item['roadAddrPart1'] ?? $item['road_address'] ?? ''));
        $as4 = trim((string) ($item['as4'] ?? ''));

        if ($roadAddress === '') {
            $rdNm = trim((string) ($item['rdnm'] ?? $item['rdNm'] ?? $item['rd_nm'] ?? ''));
            $buldNo = trim((string) ($item['buldNo'] ?? $item['buld_no'] ?? ''));
            if ($rdNm !== '' || $buldNo !== '') {
                $roadAddress = trim($rdNm . ' ' . $buldNo);
            }
        }

        if ($roadAddress === '') {
            $roadAddress = trim(implode(' ', array_filter([$sido, $sigungu, $eupmyeondong !== '' ? $eupmyeondong : $as4])));
        }

        if ($name === '' || $sido === '' || $sigungu === '' || $roadAddress === '') {
            return null;
        }

        if ($eupmyeondong === '') {
            $eupmyeondong = $as4 !== '' ? $as4 : $sigungu;
        }

        $aliases = collect([
            $item['kaptAddress'] ?? null,
            $item['kaptdaCnt'] ?? null,
            $item['kaptName'] ?? null,
        ])->filter(fn ($value) => is_string($value) && trim($value) !== '')->unique()->values()->all();

        return [
            'source_key' => trim((string) ($item['kaptCode'] ?? $item['kaptcode'] ?? $item['kaptMgrNo'] ?? '')),
            'name' => $name,
            'sido' => $sido,
            'sigungu' => $sigungu,
            'eupmyeondong' => $eupmyeondong,
            'road_address' => $roadAddress,
            'jibun_address' => trim((string) ($item['bjdJuso'] ?? $item['jibunAddr'] ?? $item['kaptAddress'] ?? '')),
            'aliases' => $aliases,
            'synced_at' => Carbon::now()->toIso8601String(),
        ];
    }

    private function resolveApartment(array $payload): ?Apartment
    {
        if ($payload['source_key'] !== null) {
            return Apartment::query()
                ->where('source', $payload['source'])
                ->where('source_key', $payload['source_key'])
                ->first();
        }

        return Apartment::query()
            ->where('sido', $payload['sido'])
            ->where('sigungu', $payload['sigungu'])
            ->where('eupmyeondong', $payload['eupmyeondong'])
            ->where('road_address', $payload['road_address'])
            ->where('normalized_name', $payload['normalized_name'])
            ->first();
    }

    private function fillApartment(Apartment $apartment, array $payload): bool
    {
        $apartment->fill([
            'name' => $payload['name'],
            'sido' => $payload['sido'],
            'sigungu' => $payload['sigungu'],
            'eupmyeondong' => $payload['eupmyeondong'],
            'road_address' => $payload['road_address'],
            'jibun_address' => $payload['jibun_address'],
            'source' => $payload['source'],
            'source_key' => $payload['source_key'],
            'normalized_name' => $payload['normalized_name'],
            'is_active' => true,
            'synced_at' => now(),
        ]);

        return $apartment->isDirty();
    }

    private function syncAliases(Apartment $apartment, array $aliases, string $source): int
    {
        $count = 0;

        foreach ($aliases as $aliasValue) {
            $normalizedAlias = $this->normalizeKoreanText($aliasValue);

            if ($normalizedAlias === '') {
                continue;
            }

            $alias = ApartmentAlias::query()->firstOrNew([
                'apartment_id' => $apartment->id,
                'normalized_alias' => $normalizedAlias,
            ]);

            if (! $alias->exists || ! $alias->is_verified) {
                $alias->alias = $aliasValue;
                $alias->source = $source;
                $alias->confidence = 0.80;
            }

            if ($alias->isDirty()) {
                $alias->save();
                $count++;
            }
        }

        return $count;
    }

    private function deactivateMissing(string $source, array $seenSourceKeys): int
    {
        if (count($seenSourceKeys) === 0) {
            $this->warn('source_key가 없어 deactivate-missing 처리를 건너뜁니다.');

            return 0;
        }

        return Apartment::query()
            ->where('source', $source)
            ->whereNotNull('source_key')
            ->whereNotIn('source_key', $seenSourceKeys)
            ->where('is_active', true)
            ->update([
                'is_active' => false,
                'synced_at' => now(),
            ]);
    }

    private function normalizeKoreanText(string $value): string
    {
        $text = trim(mb_strtolower($value));
        $text = preg_replace('/[\s\-\_\(\)\[\]\.,]+/u', '', $text);

        return $text ?? '';
    }
}
