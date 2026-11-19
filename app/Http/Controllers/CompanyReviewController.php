<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\CompanyReview;
use App\Models\Internship;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Mahasiswa mengulas tempat magangnya setelah masa magang berakhir (bisa diperbarui). */
class CompanyReviewController extends Controller
{
    /** Boleh mengulas: KP sudah selesai, atau masa magangnya sudah berakhir. */
    public static function canReview(?Internship $internship): bool
    {
        return $internship !== null && $internship->company_id !== null
            && ($internship->status === 'selesai' || ($internship->status === 'berjalan' && $internship->end_date->lte(today())));
    }

    public function store(Request $request): RedirectResponse
    {
        $internship = Internship::where('student_id', $request->user()->id)
            ->whereIn('status', ['berjalan', 'selesai'])->latest('start_date')->first();
        abort_unless(self::canReview($internship), 403);

        $rules = collect(CompanyReview::ASPECTS)->mapWithKeys(fn ($a, $key) => [$key => ['required', 'integer', 'between:1,5']])->all();
        $messages = collect(CompanyReview::ASPECTS)->mapWithKeys(fn ($a, $key) => [$key.'.required' => 'Beri nilai untuk '.mb_strtolower($a[0]).'.'])->all();
        $data = $request->validateWithBag('ulasan', $rules + [
            'recommend' => ['required', 'boolean'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ], $messages + ['recommend.required' => 'Pilih apakah kamu merekomendasikan tempat ini.']);

        $points = collect(CompanyReview::ASPECTS)->keys()->mapWithKeys(fn ($k) => [$k => (int) $data[$k]]);
        $review = CompanyReview::updateOrCreate(
            ['internship_id' => $internship->id],
            $points->all() + [
                'company_id' => $internship->company_id,
                'student_id' => $internship->student_id,
                'rating' => round($points->avg(), 2),
                'recommend' => (bool) $data['recommend'],
                'comment' => filled($data['comment'] ?? null) ? trim($data['comment']) : null,
            ],
        );
        ActivityLog::record('ulasan.simpan', 'Mengulas tempat magang '.$internship->company?->name, $review);

        return redirect()->to(route('kp.status').'#ulasan')->with('status', 'Terima kasih, ulasanmu membantu adik tingkat memilih tempat magang.');
    }
}
