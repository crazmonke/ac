<?php

namespace App\Http\Controllers\Community;

use App\Http\Controllers\Controller;
use App\Models\Apartment;
use Illuminate\Http\Request;

class CommunityPageController extends Controller
{
    public function index(Request $request)
    {
        $apartmentId = max(1, (int) $request->query('apartment_id', 1));
        $apartment = Apartment::query()->find($apartmentId)
            ?? Apartment::query()->orderBy('id')->first();

        $apartmentName = $apartment?->name ?? '입주민';

        if ($apartment) {
            $apartmentId = (int) $apartment->id;
        }

        return view('community.index', [
            'apartmentId' => $apartmentId,
            'apartmentName' => $apartmentName,
        ]);
    }
}
