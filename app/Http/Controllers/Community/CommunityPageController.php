<?php

namespace App\Http\Controllers\Community;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CommunityPageController extends Controller
{
    public function index(Request $request)
    {
        $apartmentId = max(1, (int) $request->query('apartment_id', 1));

        return view('community.index', [
            'apartmentId' => $apartmentId,
        ]);
    }
}
