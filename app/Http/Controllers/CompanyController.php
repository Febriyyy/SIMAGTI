<?php

namespace App\Http\Controllers;

use App\Actions\Companies\SaveCompany;
use App\Http\Requests\Companies\SaveCompanyRequest;
use App\Models\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class CompanyController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Company::class);

        $search = trim((string) $request->query('q'));

        $companies = Company::withCount([
                'internships',
                'internships as active_internships_count' => fn ($q) => $q->where('status', 'berjalan'),
            ])
            ->when($search !== '', fn ($q) => $q->where('name', 'like', "%{$search}%"))
            ->orderBy('name')
            ->limit(200)
            ->get();

        $canManage = $request->user()->can('manage', Company::class);

        return view('perusahaan.index', compact('companies', 'search', 'canManage'));
    }

    public function create(): View
    {
        Gate::authorize('manage', Company::class);

        return view('perusahaan.form', ['company' => null]);
    }

    public function store(SaveCompanyRequest $request, SaveCompany $save): RedirectResponse
    {
        $save->handle($request->user(), null, $request->validated());

        return redirect()->route('perusahaan.index')->with('status', 'Perusahaan ditambahkan.');
    }

    public function edit(Company $company): View
    {
        Gate::authorize('manage', Company::class);

        $company->load('divisions');

        return view('perusahaan.form', compact('company'));
    }

    public function update(SaveCompanyRequest $request, Company $company, SaveCompany $save): RedirectResponse
    {
        $save->handle($request->user(), $company, $request->validated());

        return redirect()->route('perusahaan.index')->with('status', 'Data perusahaan tersimpan.');
    }
}
