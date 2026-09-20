<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SubjectResource;
use App\Models\BpsSubject;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SubjectController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $domainId = $request->attributes->get('domain_id');

        $query = BpsSubject::with('category')->where('domain_id', $domainId);

        if ($request->filled('subcat_id')) {
            $query->where('subcat_id', $request->integer('subcat_id'));
        }

        $subjects = $query->orderBy('sub_id')->get();

        return SubjectResource::collection($subjects);
    }
}
