<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SubjectCategoryResource;
use App\Models\BpsSubjectCategory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SubjectCategoryController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $domainId = $request->attributes->get('domain_id');

        $categories = BpsSubjectCategory::where('domain_id', $domainId)
            ->orderBy('subcat_id')
            ->get();

        return SubjectCategoryResource::collection($categories);
    }
}
