<?php

namespace App\Console\Commands;

use App\Models\Apartment;
use App\Models\ApartmentAlias;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
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
        $source = trim((string) $this->option('source'));
        $dryRun = (bool) $this->option('dry-run');

        if ($source === '') {
            $this->error('source 옵션이 비어 있습니다. --source=gov 또는 --source=file을 지정해 주세요.');

            return self::FAILURE;
        }

        $rows = $this->loadRows($source);

        if ($rows->isEmpty()) {
            $this->warn('동기화할 데이터가 없습니다.');

            return self::SUCCESS;
        }

        $stats = [
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'aliases' => 0,
            'invalid' => 0,
        ];

        $seenSourceKeys = [];

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

        $deactivated = 0;

        if (! $dryRun && (bool) $this->option('deactivate-missing')) {
            $deactivated = $this->deactivateMissing($source, $seenSourceKeys);
        }

        $this->line('아파트 동기화 결과');
        $this->line('- source: ' . $source);
        $this->line('- rows: ' . $rows->count());
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

    private function loadRows(string $source): Collection
    {
        return match ($source) {
            'file' => $this->loadRowsFromFile(),
            default => $this->loadRowsFromRemote(),
        };
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
