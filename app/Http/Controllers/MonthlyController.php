<?php

namespace App\Http\Controllers;

use App\Actions\Monthly\MonthlyCycle;
use App\Models\Internship;
use App\Models\MonthlyReview;
use App\Support\ActiveInternship;
use App\Support\MonthlySummary;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/** Evaluasi bulanan: refleksi mahasiswa, nilai peninjau, catatan dosen pembimbing. */
class MonthlyController extends Controller
{
    /** Data bulan-bulan yang sudah dimulai (dipakai halaman mahasiswa dan tab pemantauan). */
    public static function months(Internship $internship): array
    {
        $reviews = $internship->monthlyReviews()->with(['reviewer', 'lecturer'])->get()->keyBy('month_number');
        $months = [];

        for ($m = 1; $m <= $internship->monthCount(); $m++) {
            [$start, $end] = $internship->monthRange($m);
            if ($start->isFuture()) {
                break;
            }
            $review = $reviews->get($m) ?? new MonthlyReview([
                'internship_id' => $internship->id, 'month_number' => $m,
                'period_start' => $start->toDateString(), 'period_end' => $end->toDateString(),
            ]);
            $months[] = ['month' => $m, 'review' => $review, 'summary' => MonthlySummary::for($internship, $m)];
        }

        return array_reverse($months);
    }

    public function index(Request $request): View
    {
        $internship = ActiveInternship::for($request->user(), ['syllabusItems', 'supervisor', 'lecturer']);

        return view('bulanan.index', [
            'internship' => $internship,
            'months' => $internship ? self::months($internship) : [],
            'canEdit' => $internship && $request->user()->can('manageActivities', $internship),
        ]);
    }

    public function reflect(Request $request, int $month, MonthlyCycle $cycle): RedirectResponse
    {
        $internship = ActiveInternship::for($request->user());
        abort_unless($internship, 404);
        Gate::authorize('manageActivities', $internship);

        $data = $request->validate([
            'learned' => ['required', 'string', 'max:2000'],
            'obstacles' => ['nullable', 'string', 'max:2000'],
            'plan' => ['nullable', 'string', 'max:2000'],
            'syllabus_item_ids' => ['nullable', 'array'],
            'syllabus_item_ids.*' => ['integer'],
        ], [], ['learned' => 'yang dipelajari', 'obstacles' => 'kendala', 'plan' => 'rencana']);

        $cycle->reflect($request->user(), $internship, $month, $data);

        return back()->with('status', "Refleksi bulan ke-{$month} tersimpan.");
    }

    /** Peninjau (Company Supervisor; seminar: dosen pembimbing) memberi nilai bulanan. */
    public function review(Request $request, Internship $internship, int $month, MonthlyCycle $cycle): RedirectResponse
    {
        abort_unless($internship->isReviewedBy($request->user()) && $internship->status === 'berjalan', 403);

        $scale = 'in:1,2,3,4';
        $data = $request->validate([
            'score_hard' => ['required', $scale],
            'score_soft' => ['required', $scale],
            'score_attitude' => ['required', $scale],
            'reviewer_note' => ['nullable', 'string', 'max:1000'],
        ], ['in' => 'Pilih nilai 1 sampai 4.'], ['score_hard' => 'hard skill', 'score_soft' => 'soft skill', 'score_attitude' => 'attitude']);

        $cycle->review($request->user(), $internship, $month, $data);

        return back()->with('status', "Nilai bulan ke-{$month} tersimpan.");
    }

    public function lecturerNote(Request $request, Internship $internship, int $month, MonthlyCycle $cycle): RedirectResponse
    {
        abort_unless($request->user()->role === 'dospem' && (int) $internship->lecturer_id === (int) $request->user()->id && ! $internship->isSeminar(), 403);
        $data = $request->validate(['lecturer_note' => ['required', 'string', 'max:1000']], [], ['lecturer_note' => 'catatan']);

        $cycle->lecturerNote($request->user(), $internship, $month, $data['lecturer_note']);

        return back()->with('status', "Catatan bulan ke-{$month} tersimpan.");
    }
}
