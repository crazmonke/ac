<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Apartment;
use App\Services\ApartmentSelectionService;
use Illuminate\Http\Request;

class ApartmentSearchController extends Controller
{
    public function __construct(private readonly ApartmentSelectionService $apartmentSelectionService)
    {
    }

    public function index(Request $request)
    {
        $query = trim((string) $request->query('q', ''));
        $limit = (int) $request->query('limit', 20);

        $results = $this->apartmentSelectionService->search($query, $limit);

        return response()->json([
            'data' => $results,
            'results' => $results, // Admin dashboard compatibility
        ]);
    }

    public function regions(Request $request)
    {
        $level   = (string) $request->query('level', '');
        $sido    = trim((string) $request->query('sido', ''));
        $sigungu = trim((string) $request->query('sigungu', ''));

        $base = Apartment::query()->where('is_active', true);

        $data = match ($level) {
            'sido'         => $base->distinct()->orderBy('sido')->pluck('sido'),
            'sigungu'      => $base->where('sido', $sido)->distinct()->orderBy('sigungu')->pluck('sigungu'),
            'eupmyeondong' => $base->where('sido', $sido)->where('sigungu', $sigungu)->distinct()->orderBy('eupmyeondong')->pluck('eupmyeondong'),
            default        => collect(),
        };

        return response()->json(['data' => $data->values()]);
    }

    public function byRegion(Request $request)
    {
        $sido         = trim((string) $request->query('sido', ''));
        $sigungu      = trim((string) $request->query('sigungu', ''));
        $eupmyeondong = trim((string) $request->query('eupmyeondong', ''));

        if ($sido === '' || $sigungu === '' || $eupmyeondong === '') {
            return response()->json(['data' => []]);
        }

        $apartments = Apartment::query()
            ->where('is_active', true)
            ->where('sido', $sido)
            ->where('sigungu', $sigungu)
            ->where('eupmyeondong', $eupmyeondong)
            ->orderBy('name')
            ->get(['id', 'name', 'road_address']);

        return response()->json([
            'data' => $apartments->map(fn ($a) => [
                'id'           => $a->id,
                'building_id'  => null,
                'name'         => $a->name,
                'road_address' => $a->road_address,
            ]),
        ]);
    }
}
