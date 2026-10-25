<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Companies\BuildCompanyStats;
use App\Http\Controllers\Controller;
use App\Http\Resources\CompanyResource;
use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class CompanyController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Company::class);

        $companies = Company::withCount([
                'internships',
                'internships as active_internships_count' => fn ($q) => $q->where('status', 'berjalan'),
            ])
            ->orderBy('name')
            ->paginate(50);

        return CompanyResource::collection($companies);
    }

    public function stats(Company $company, BuildCompanyStats $build): JsonResponse
    {
        Gate::authorize('viewStats', $company);

        return response()->json(['data' => $build->handle($company)]);
    }
}
