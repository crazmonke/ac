<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class BackfillUserRegionFromResidenceCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'residences:backfill-user-region {--dry-run : Preview only without DB writes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backfill users.home_* fields from preferred residence complex/building addresses';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $updated = 0;
        $checked = 0;

        User::query()
            ->with(['preferredResidenceComplex', 'preferredResidenceBuilding'])
            ->whereNotNull('preferred_residence_complex_id')
            ->chunkById(200, function ($users) use ($dryRun, &$updated, &$checked) {
                foreach ($users as $user) {
                    $checked++;

                    $needsBackfill = trim((string) $user->home_sido) === ''
                        || trim((string) $user->home_sigungu) === ''
                        || trim((string) $user->home_eupmyeondong) === '';

                    if (! $needsBackfill) {
                        continue;
                    }

                    $region = $this->extractRegion(
                        (string) ($user->preferredResidenceBuilding?->road_address ?: $user->preferredResidenceComplex?->road_address ?: ''),
                        (string) ($user->preferredResidenceBuilding?->jibun_address ?: $user->preferredResidenceComplex?->jibun_address ?: '')
                    );

                    if (! $region['sido'] && ! $region['sigungu'] && ! $region['eupmyeondong']) {
                        continue;
                    }

                    if (! $dryRun) {
                        $user->forceFill([
                            'home_sido' => $region['sido'],
                            'home_sigungu' => $region['sigungu'],
                            'home_eupmyeondong' => $region['eupmyeondong'],
                            'home_apartment_name' => $user->home_apartment_name ?: $user->preferredResidenceComplex?->displayName(),
                        ])->save();
                    }

                    $updated++;
                }
            });

        $this->line('user region backfill result');
        $this->line('- checked: ' . $checked);
        $this->line('- updated: ' . $updated);

        if ($dryRun) {
            $this->warn('dry-run mode. no writes were committed.');
        }

        return self::SUCCESS;
    }

    private function extractRegion(string $roadAddress, string $jibunAddress = ''): array
    {
        $address = trim($roadAddress !== '' ? $roadAddress : $jibunAddress);

        if ($address === '') {
            return ['sido' => null, 'sigungu' => null, 'eupmyeondong' => null];
        }

        $tokens = preg_split('/\s+/u', str_replace(',', ' ', $address)) ?: [];
        $tokens = array_values(array_filter(array_map(function ($token) {
            $token = trim((string) $token);

            return $token !== '' ? $token : null;
        }, $tokens)));
        $tokens = array_values(array_filter($tokens, fn ($token) => $token !== '대한민국'));

        $sido = null;
        $cityToken = null;
        $districtToken = null;
        $dong = null;

        foreach ($tokens as $token) {
            if ($sido === null && preg_match('/(도|특별시|광역시|자치시)$/u', $token)) {
                $sido = $token;
                continue;
            }

            if ($cityToken === null && preg_match('/시$/u', $token)) {
                $cityToken = $token;
                continue;
            }

            if ($districtToken === null && preg_match('/(구|군)$/u', $token)) {
                $districtToken = $token;
                continue;
            }

            if ($dong === null && preg_match('/(동|읍|면|가)$/u', $token)) {
                $dong = $token;
            }
        }

        if ($sido === null && $cityToken !== null) {
            $sido = $cityToken;
        }

        $sigungu = null;
        if ($cityToken !== null && $districtToken !== null) {
            $sigungu = trim($cityToken . ' ' . $districtToken);
        } elseif ($districtToken !== null) {
            $sigungu = $districtToken;
        } elseif ($cityToken !== null) {
            $sigungu = $cityToken;
        }

        return [
            'sido' => $sido,
            'sigungu' => $sigungu,
            'eupmyeondong' => $dong,
        ];
    }
}
