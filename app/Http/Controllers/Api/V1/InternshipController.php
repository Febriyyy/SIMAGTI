<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\InternshipResource;
use App\Http\Resources\WeeklyTaskResource;
use App\Models\Internship;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

/** Baca-saja. Siapa melihat apa ditentukan Internship::visibleTo() dan InternshipPolicy. */
class InternshipController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $status = $request->query('status');

        $internships = Internship::with(['student', 'company', 'mentor', 'lecturer'])
            ->withProgress()
            ->visibleTo($request->user())
            ->when(is_string($status) && array_key_exists($status, Internship::STATUSES), fn ($q) => $q->where('status', $status))
            ->latest('id')
            ->paginate(min($request->integer('per_page', 20), 100));

        return InternshipResource::collection($internships);
    }

    public function show(Internship $internship): InternshipResource
    {
        Gate::authorize('view', $internship);

        return new InternshipResource(
            Internship::with(['student', 'company', 'mentor', 'lecturer'])->withProgress()->findOrFail($internship->id)
        );
    }

    public function tasks(Request $request, Internship $internship): AnonymousResourceCollection
    {
        Gate::authorize('view', $internship);

        $tasks = $internship->tasks()
            ->withCount(['evidences', 'comments'])
            ->when($request->filled('minggu'), fn ($q) => $q->where('week_number', $request->integer('minggu')))
            ->orderBy('week_number')
            ->get()
            ->sortBy(fn ($task) => $task->week_number * 10 + $task->dayOrder())
            ->values();

        return WeeklyTaskResource::collection($tasks);
    }
}
