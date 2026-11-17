<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Internship;
use App\Models\TaskTemplate;
use App\Models\WeeklyTask;
use App\Support\Notify;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Template task milik Company Supervisor: kumpulan task baku (mis. onboarding minggu pertama)
 * yang bisa diterapkan ke mahasiswa magangnya sekali klik. Minggu ke-1 template = minggu KP saat diterapkan.
 */
class TaskTemplateController extends Controller
{
    public const MAX_ITEMS = 30;

    public function index(Request $request): View
    {
        $user = $request->user();

        return view('template.index', [
            'templates' => TaskTemplate::with(['items' => fn ($q) => $q->orderBy('week_number')->orderBy('sort_order')])
                ->where('owner_id', $user->id)->latest('id')->get(),
            'interns' => Internship::with('student')->supervisedBy($user)->where('status', 'berjalan')->orderBy('start_date')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request, 'template');

        DB::transaction(function () use ($request, $data) {
            $template = TaskTemplate::create([
                'owner_id' => $request->user()->id,
                'company_id' => Internship::supervisedBy($request->user())->value('company_id'),
                'name' => $data['nama'],
                'description' => $data['keterangan'] ?? null,
            ]);
            $this->saveItems($template, $data['items']);
            ActivityLog::record('template.buat', 'Membuat template task '.$template->name, $template);
        });

        return redirect()->route('template.index')->with('status', 'Template "'.$data['nama'].'" tersimpan.');
    }

    public function update(Request $request, TaskTemplate $template): RedirectResponse
    {
        $this->authorizeOwner($request, $template);
        $data = $this->validated($request, 'template'.$template->id);

        DB::transaction(function () use ($template, $data) {
            $template->update(['name' => $data['nama'], 'description' => $data['keterangan'] ?? null]);
            $template->items()->delete();
            $this->saveItems($template, $data['items']);
        });

        return redirect()->route('template.index')->with('status', 'Template "'.$data['nama'].'" diperbarui.');
    }

    public function destroy(Request $request, TaskTemplate $template): RedirectResponse
    {
        $this->authorizeOwner($request, $template);
        $template->delete();

        return redirect()->route('template.index')->with('status', 'Template dihapus. Task yang sudah diterapkan tetap ada.');
    }

    /** Terapkan ke satu atau beberapa mahasiswa magang (KP berjalan) milik supervisor ini. */
    public function apply(Request $request, TaskTemplate $template): RedirectResponse
    {
        $this->authorizeOwner($request, $template);
        $data = $request->validateWithBag('apply'.$template->id, [
            'mahasiswa' => ['required', 'array', 'min:1'],
            'mahasiswa.*' => ['integer'],
        ], ['mahasiswa.required' => 'Pilih minimal satu mahasiswa.']);

        $interns = Internship::with('student')->supervisedBy($request->user())->where('status', 'berjalan')
            ->whereIn('id', $data['mahasiswa'])->get();
        if ($interns->isEmpty()) {
            throw ValidationException::withMessages(['mahasiswa' => 'Mahasiswa tidak ditemukan.'])->errorBag('apply'.$template->id);
        }

        $total = 0;
        foreach ($interns as $internship) {
            $count = $template->applyTo($internship, $request->user()->id, max(0, $internship->currentWeek() - 1));
            $total += $count;
            if ($count > 0) {
                Notify::send($internship->student, $count.' task baru dari Company Supervisor', 'Template "'.$template->name.'". Cek di halaman Kegiatan.', route('kegiatan.index'), 'tasks', 'blue');
            }
        }
        ActivityLog::record('template.terapkan', 'Menerapkan template '.$template->name.' ke '.$interns->count().' mahasiswa ('.$total.' task)', $template);

        return redirect()->route('template.index')->with('status', $total > 0
            ? $total.' task dibuat untuk '.$interns->count().' mahasiswa.'
            : 'Tidak ada task yang dibuat: minggu di template melewati akhir periode KP.');
    }

    /** @return array{nama: string, keterangan?: ?string, items: list<array{minggu: int, hari: string, judul: string}>} */
    private function validated(Request $request, string $bag): array
    {
        $rows = collect($request->input('items', []))
            ->filter(fn ($r) => is_array($r) && filled($r['judul'] ?? null))
            ->values()->all();
        $request->merge(['items' => $rows]);

        return $request->validateWithBag($bag, [
            'nama' => ['required', 'string', 'max:120'],
            'keterangan' => ['nullable', 'string', 'max:300'],
            'items' => ['required', 'array', 'min:1', 'max:'.self::MAX_ITEMS],
            'items.*.minggu' => ['required', 'integer', 'between:1,52'],
            'items.*.hari' => ['required', Rule::in(array_keys(WeeklyTask::DAYS))],
            'items.*.judul' => ['required', 'string', 'max:150'],
        ], [
            'nama.required' => 'Beri nama template.',
            'items.required' => 'Isi minimal satu task.',
            'items.max' => 'Maksimal '.self::MAX_ITEMS.' task per template.',
        ]);
    }

    /** @param  list<array{minggu: int, hari: string, judul: string}>  $items */
    private function saveItems(TaskTemplate $template, array $items): void
    {
        foreach (array_values($items) as $n => $item) {
            $template->items()->create([
                'week_number' => (int) $item['minggu'],
                'day' => $item['hari'],
                'title' => trim($item['judul']),
                'sort_order' => $n + 1,
            ]);
        }
    }

    private function authorizeOwner(Request $request, TaskTemplate $template): void
    {
        abort_unless((int) $template->owner_id === (int) $request->user()->id, 403);
    }
}
