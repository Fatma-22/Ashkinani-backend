<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Nationality;
use Illuminate\Http\JsonResponse;

class MetaController extends Controller
{
    public function nationalities(): JsonResponse
    {
        $nationalities = Nationality::orderBy('sort_order')
            ->orderBy('name_en')
            ->get(['name_en as nationality', 'name_ar as nationality_ar']);

        return response()->json($nationalities);
    }
}
