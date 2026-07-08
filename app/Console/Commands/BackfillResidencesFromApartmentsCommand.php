<?php

namespace App\Console\Commands;

use App\Models\Apartment;
use App\Models\ResidenceBuilding;
use App\Models\ResidenceComplex;
use App\Services\ResidenceNamingService;
use Illuminate\Console\Command;

class BackfillResidencesFromApartmentsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'residences:backfill-from-apartments {--dry-run : Preview only without DB writes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backfill residence complexes/buildings from existing apartments';

    public function __construct(private readonly ResidenceNamingService $namingService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $createdComplexes = 0;
        $updatedComplexes = 0;
        $createdBuildings = 0;

        Apartment::query()->chunkById(200, function ($apartments) use (&$createdComplexes, &$updatedComplexes, &$createdBuildings, $dryRun) {
            foreach ($apartments as $apartment) {
                $complexPayload = $this->buildComplexPayload($apartment);

                $existing = ResidenceComplex::query()
                    ->where('legacy_apartment_id', $apartment->id)
                    ->first();

                if ($existing) {
                    if (! $dryRun) {
                        $existing->fill($complexPayload)->save();
                    }
                    $updatedComplexes++;
                    $complex = $existing;
                } else {
                    $complex = new ResidenceComplex($complexPayload);
                    if (! $dryRun) {
                        $complex->save();
                    }
                    $createdComplexes++;
                }

                if (! $dryRun) {
                    $buildingPayload = $this->buildBuildingPayload($complex, $apartment);
                    ResidenceBuilding::query()->updateOrCreate(
                        ['normalized_key' => $buildingPayload['normalized_key']],
                        $buildingPayload
                    );
                }

                $createdBuildings++;
            }
        });

        $this->line('residences backfill result');
        $this->line('- created complexes: ' . $createdComplexes);
        $this->line('- updated complexes: ' . $updatedComplexes);
        $this->line('- processed buildings: ' . $createdBuildings);

        if ($dryRun) {
            $this->warn('dry-run mode. no writes were committed.');
        }

        return self::SUCCESS;
    }

    private function buildComplexPayload(Apartment $apartment): array
    {
        $name = $this->namingService->buildDisplayName(
            $apartment->name,
            null,
            $apartment->road_address,
            $apartment->jibun_address,
            'apartment'
        );

        $normalizedKey = $this->buildComplexNormalizedKey(
            $apartment->sido,
            $apartment->sigungu,
            $apartment->eupmyeondong,
            $apartment->road_address,
            $apartment->name,
            (int) $apartment->id
        );

        return [
            'housing_type' => 'apartment',
            'official_name' => $apartment->name,
            'alias_name' => null,
            'auto_display_name' => $name['display_name'],
            'display_name_source' => $name['source'],
            'road_address' => $apartment->road_address,
            'jibun_address' => $apartment->jibun_address,
            'legal_dong_code' => null,
            'postal_code' => null,
            'latitude' => null,
            'longitude' => null,
            'normalized_key' => $normalizedKey,
            'status' => ((bool) ($apartment->is_active ?? true)) ? 'active' : 'hidden',
            'legacy_apartment_id' => $apartment->id,
        ];
    }

    private function buildBuildingPayload(ResidenceComplex $complex, Apartment $apartment): array
    {
        return [
            'complex_id' => $complex->id,
            'building_no' => null,
            'building_name' => $apartment->name,
            'road_address' => $apartment->road_address,
            'jibun_address' => $apartment->jibun_address,
            'bld_main_no' => null,
            'bld_sub_no' => null,
            'legal_dong_code' => null,
            'latitude' => null,
            'longitude' => null,
            'normalized_key' => $this->buildBuildingNormalizedKey($complex->normalized_key, $apartment->name, $apartment->road_address),
        ];
    }

    private function buildComplexNormalizedKey(
        ?string $sido,
        ?string $sigungu,
        ?string $dong,
        ?string $roadAddress,
        ?string $name,
        int $legacyApartmentId
    ): string {
        return $this->namingService->normalize(implode('|', [
            (string) $sido,
            (string) $sigungu,
            (string) $dong,
            (string) $roadAddress,
            (string) $name,
            'legacy:' . $legacyApartmentId,
        ]));
    }

    private function buildBuildingNormalizedKey(string $complexKey, ?string $buildingName, ?string $roadAddress): string
    {
        return $this->namingService->normalize($complexKey . '|' . (string) $buildingName . '|' . (string) $roadAddress);
    }
}
