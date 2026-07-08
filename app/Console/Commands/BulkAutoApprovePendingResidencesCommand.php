<?php

namespace App\Console\Commands;

use App\Models\ResidentVerificationRequest;
use App\Models\UserRole;
use App\Models\UserResidence;
use App\Services\ApartmentSelectionService;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Throwable;

class BulkAutoApprovePendingResidencesCommand extends Command
{
    protected $signature = 'residences:auto-approve-pending
        {--hours=24 : Include pending rows created within N hours (0 means no time limit)}
        {--limit=200 : Maximum number of rows to scan}
        {--user-id=* : Only include specific user id(s)}
        {--residence-id=* : Only include specific user_residences id(s)}
        {--complex-id=* : Only include specific residence complex id(s)}
        {--apartment-id=* : Only include specific legacy apartment id(s)}
        {--approve-without-coordinates : Admin override for rows missing GPS coordinates}
        {--admin-note= : Optional admin note used for no-coordinate override approvals}
        {--force-all : Allow all-time scan when hours=0 and no id filters are provided}
        {--execute : Persist approval results (default is dry-run preview)}
        {--yes : Skip interactive confirmation when used with --execute}';

    protected $description = 'Safely batch auto-approve pending residence verifications by GPS criteria';

    public function __construct(private readonly ApartmentSelectionService $apartmentSelectionService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $hours = (int) $this->option('hours');
        $limit = max(1, min(2000, (int) $this->option('limit')));
        $execute = (bool) $this->option('execute');
        $approveWithoutCoordinates = (bool) $this->option('approve-without-coordinates');
        $adminNote = trim((string) $this->option('admin-note'));

        if ($hours < 0) {
            $this->error('--hours 값은 0 이상이어야 합니다.');

            return self::FAILURE;
        }

        $userIds = $this->normalizeIdList($this->option('user-id'));
        $residenceIds = $this->normalizeIdList($this->option('residence-id'));
        $complexIds = $this->normalizeIdList($this->option('complex-id'));
        $apartmentIds = $this->normalizeIdList($this->option('apartment-id'));

        if (
            $hours === 0
            && ! (bool) $this->option('force-all')
            && $userIds === []
            && $residenceIds === []
            && $complexIds === []
            && $apartmentIds === []
        ) {
            $this->error('안전장치: 전체 기간 일괄 스캔은 차단됩니다. --force-all 또는 대상 필터를 지정해 주세요.');

            return self::INVALID;
        }

        $query = UserResidence::query()
            ->with([
                'user.preferredApartment',
                'complex.legacyApartment',
                'building',
            ])
            ->where('verification_status', 'pending');

        if ($hours > 0) {
            $query->where('created_at', '>=', now()->subHours($hours));
        }

        if ($userIds !== []) {
            $query->whereIn('user_id', $userIds);
        }

        if ($residenceIds !== []) {
            $query->whereIn('id', $residenceIds);
        }

        if ($complexIds !== []) {
            $query->whereIn('complex_id', $complexIds);
        }

        if ($apartmentIds !== []) {
            $query->whereHas('complex', function ($builder) use ($apartmentIds) {
                $builder->whereIn('legacy_apartment_id', $apartmentIds);
            });
        }

        $candidates = $query
            ->orderBy('id')
            ->limit($limit)
            ->get();

        if ($candidates->isEmpty()) {
            $this->warn('처리 대상 pending 공동주택 인증이 없습니다.');

            return self::SUCCESS;
        }

        $modeLabel = $execute ? 'EXECUTE' : 'DRY-RUN';
        $this->line('residences:auto-approve-pending 시작');
        $this->line('- mode: ' . $modeLabel);
        $this->line('- candidates: ' . $candidates->count());
        $this->line('- hours: ' . $hours);
        $this->line('- limit: ' . $limit);
        $this->line('- approve_without_coordinates: ' . ($approveWithoutCoordinates ? 'yes' : 'no'));

        if ($execute && ! $this->confirmExecution($candidates->count())) {
            $this->warn('요청이 취소되었습니다.');

            return self::SUCCESS;
        }

        $stats = [
            'scanned' => 0,
            'approved' => 0,
            'approved_without_coordinates' => 0,
            'failed' => 0,
            'skipped_no_coordinates' => 0,
            'skipped_missing_relation' => 0,
            'errors' => 0,
        ];

        foreach ($candidates as $residence) {
            $stats['scanned']++;

            $latitude = $this->extractCoordinate($residence->evidence_meta, 'latitude');
            $longitude = $this->extractCoordinate($residence->evidence_meta, 'longitude');

            $user = $residence->user;
            $complex = $residence->complex;
            $building = $residence->building;

            if (! $user || ! $complex || ! $building) {
                $stats['skipped_missing_relation']++;
                continue;
            }

            if ($latitude === null || $longitude === null) {
                if (! $approveWithoutCoordinates) {
                    $stats['skipped_no_coordinates']++;
                    continue;
                }

                try {
                    if ($execute) {
                        DB::transaction(function () use ($residence, $adminNote) {
                            $this->approveWithoutCoordinates($residence, $adminNote);
                        });
                    } else {
                        DB::beginTransaction();

                        try {
                            $this->approveWithoutCoordinates($residence, $adminNote);
                        } finally {
                            DB::rollBack();
                        }
                    }
                } catch (Throwable $e) {
                    $stats['errors']++;
                    $this->warn('실패(no-coordinates): residence_id=' . $residence->id . ' message=' . $e->getMessage());
                    continue;
                }

                $stats['approved']++;
                $stats['approved_without_coordinates']++;
                $stats['skipped_no_coordinates']++;
                continue;
            }

            $legacyApartment = $complex->legacyApartment ?: $user->preferredApartment;

            try {
                if ($execute) {
                    $result = DB::transaction(function () use ($user, $complex, $building, $latitude, $longitude, $legacyApartment, $residence) {
                        $result = $this->apartmentSelectionService->tryAutoApproveResidentByResidence(
                            $user,
                            $complex,
                            $building,
                            $latitude,
                            $longitude,
                            'admin_bulk_auto_approve',
                            $legacyApartment
                        );

                        if (($result['approved'] ?? false) === true) {
                            $this->markResidenceVerified($residence, $result);
                        }

                        return $result;
                    });
                } else {
                    DB::beginTransaction();

                    try {
                        $result = $this->apartmentSelectionService->tryAutoApproveResidentByResidence(
                            $user,
                            $complex,
                            $building,
                            $latitude,
                            $longitude,
                            'admin_bulk_auto_approve_preview',
                            $legacyApartment
                        );
                    } finally {
                        DB::rollBack();
                    }
                }
            } catch (Throwable $e) {
                $stats['errors']++;
                $this->warn('실패: residence_id=' . $residence->id . ' message=' . $e->getMessage());
                continue;
            }

            if (($result['approved'] ?? false) === true) {
                $stats['approved']++;
            } else {
                $stats['failed']++;
            }
        }

        $this->line('처리 결과');
        $this->line('- scanned: ' . $stats['scanned']);
        $this->line('- approved' . ($execute ? '' : ' (preview)') . ': ' . $stats['approved']);
        $this->line('- approved_without_coordinates: ' . $stats['approved_without_coordinates']);
        $this->line('- failed: ' . $stats['failed']);
        $this->line('- skipped_no_coordinates: ' . $stats['skipped_no_coordinates']);
        $this->line('- skipped_missing_relation: ' . $stats['skipped_missing_relation']);
        $this->line('- errors: ' . $stats['errors']);

        if (! $execute) {
            $this->warn('dry-run 모드입니다. DB에는 반영되지 않았습니다. 실제 반영 시 --execute 옵션을 사용하세요.');
        }

        return self::SUCCESS;
    }

    /**
     * @param  mixed  $raw
     * @return array<int>
     */
    private function normalizeIdList(mixed $raw): array
    {
        if (! is_array($raw)) {
            return [];
        }

        return collect($raw)
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    private function extractCoordinate(mixed $evidenceMeta, string $key): ?float
    {
        if (! is_array($evidenceMeta)) {
            return null;
        }

        $value = Arr::get($evidenceMeta, $key);

        if (! is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }

    private function confirmExecution(int $count): bool
    {
        if ((bool) $this->option('yes')) {
            return true;
        }

        if (! $this->input->isInteractive()) {
            $this->error('--execute 사용 시 비대화형 환경에서는 --yes를 함께 지정해야 합니다.');

            return false;
        }

        return (bool) $this->confirm('총 ' . $count . '건에 대해 실제 승인 반영을 진행할까요?', false);
    }

    private function markResidenceVerified(UserResidence $residence, array $result): void
    {
        $evidence = is_array($residence->evidence_meta) ? $residence->evidence_meta : [];
        $history = Arr::get($evidence, 'bulk_auto_approve_history', []);

        if (! is_array($history)) {
            $history = [];
        }

        $history[] = [
            'at' => now()->toDateTimeString(),
            'approved' => true,
            'reason' => (string) ($result['reason'] ?? 'matched_by_google_geocode'),
            'details' => $result['details'] ?? null,
        ];

        $evidence['bulk_auto_approve_history'] = $history;

        $residence->fill([
            'verification_status' => 'verified',
            'verification_method' => 'gps',
            'gps_verified_at' => now(),
            'distance_m' => isset($result['details']['distance_meters']) && is_numeric($result['details']['distance_meters'])
                ? (int) $result['details']['distance_meters']
                : null,
            'evidence_meta' => $evidence,
        ])->save();
    }

    private function approveWithoutCoordinates(UserResidence $residence, string $adminNote = ''): void
    {
        $residence->loadMissing(['user.preferredApartment', 'complex.legacyApartment', 'building']);

        $user = $residence->user;
        $complex = $residence->complex;
        $building = $residence->building;

        if (! $user || ! $complex || ! $building) {
            throw new \RuntimeException('사용자/공동주택/동 데이터가 누락되어 관리자 승인 처리를 진행할 수 없습니다.');
        }

        $legacyApartment = $complex->legacyApartment ?: $user->preferredApartment;

        if ($legacyApartment) {
            UserRole::query()->firstOrCreate([
                'user_id' => $user->id,
                'apartment_id' => $legacyApartment->id,
                'role' => 'resident',
            ], [
                'granted_at' => now(),
                'granted_by' => null,
            ]);

            ResidentVerificationRequest::query()->updateOrCreate([
                'user_id' => $user->id,
                'apartment_id' => $legacyApartment->id,
                'status' => 'approved',
            ], [
                'residence_complex_id' => $residence->complex_id,
                'residence_building_id' => $residence->building_id,
                'residence_unit_id' => $residence->unit_id,
                'verification_method' => 'admin',
                'distance_m' => null,
                'request_note' => '관리자 일괄 승인 처리',
                'admin_note' => $adminNote !== '' ? $adminNote : 'GPS 좌표 누락 건 관리자 일괄 승인 처리',
                'reviewed_by' => null,
                'reviewed_at' => now(),
            ]);

            ResidentVerificationRequest::query()
                ->where('user_id', $user->id)
                ->where('apartment_id', $legacyApartment->id)
                ->where('status', 'pending')
                ->delete();
        }

        $evidence = is_array($residence->evidence_meta) ? $residence->evidence_meta : [];
        $history = Arr::get($evidence, 'bulk_admin_approve_history', []);

        if (! is_array($history)) {
            $history = [];
        }

        $history[] = [
            'at' => now()->toDateTimeString(),
            'reason' => 'no_coordinates_admin_override',
            'admin_note' => $adminNote !== '' ? $adminNote : 'GPS 좌표 누락 건 관리자 일괄 승인 처리',
        ];

        $evidence['bulk_admin_approve_history'] = $history;

        $residence->fill([
            'verification_status' => 'verified',
            'verification_method' => 'admin',
            'gps_verified_at' => null,
            'distance_m' => null,
            'evidence_meta' => $evidence,
        ])->save();
    }
}
