<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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

        return response()->json([
            'data' => $this->apartmentSelectionService->search($query),
        ]);
    }
}
