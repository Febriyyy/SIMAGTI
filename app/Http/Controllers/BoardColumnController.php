<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\BoardColumn;
use App\Models\Internship;
use App\Support\ActiveInternship;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/** Kolom board diatur mahasiswa sendiri agar sesuai alur kerjanya (poin 13). */
class BoardColumnController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $internship = $this->internship($request);
        $data = $this->validated($request);

        if ($internship->boardColumns()->count() >= 8) {
            throw ValidationException::withMessages(['name' => 'Maksimal 8 kolom per board.']);
        }

        $internship->boardColumns()->create($data + ['position' => (int) $internship->boardColumns()->max('position') + 1]);

        return back()->with('status', 'Kolom ditambahkan.');
    }

    public function update(Request $request, BoardColumn $column): RedirectResponse
    {
        $internship = $this->internship($request, $column);
        $data = $this->validated($request, true);

        DB::transaction(function () use ($internship, $column, $data) {
            if (isset($data['position'])) {
                $ids = $internship->boardColumns()->whereKeyNot($column->id)->pluck('id')->all();
                array_splice($ids, max(0, min((int) $data['position'], count($ids))), 0, [$column->id]);
                foreach ($ids as $i => $id) {
                    BoardColumn::whereKey($id)->update(['position' => $i]);
                }
                unset($data['position']);
            }

            $column->update($data);

            // Kategori kolom berubah: status task di dalamnya ikut.
            if (isset($data['category'])) {
                foreach ($column->tasks as $task) {
                    $task->update(['status' => $column->taskStatus()]);
                }
            }
        });

        return back()->with('status', 'Kolom diperbarui.');
    }

    public function destroy(Request $request, BoardColumn $column): RedirectResponse
    {
        $internship = $this->internship($request, $column);
        $columns = $internship->boardColumns()->get();

        if ($columns->count() <= 2) {
            throw ValidationException::withMessages(['kolom' => 'Board minimal punya 2 kolom.']);
        }

        // Task di kolom yang dihapus dipindah ke kolom pertama lain dengan kategori yang sama (atau kolom pertama).
        $target = $columns->first(fn ($c) => $c->id !== $column->id && $c->category === $column->category)
            ?? $columns->first(fn ($c) => $c->id !== $column->id);

        DB::transaction(function () use ($column, $target) {
            foreach ($column->tasks as $task) {
                $task->update(['board_column_id' => $target->id]);
            }
            ActivityLog::record('kolom.hapus', "Menghapus kolom board \"{$column->name}\"");
            $column->delete();
        });

        return back()->with('status', "Kolom dihapus. Task di dalamnya dipindah ke {$target->name}.");
    }

    private function internship(Request $request, ?BoardColumn $column = null): Internship
    {
        $internship = ActiveInternship::for($request->user());
        abort_unless($internship, 404);
        Gate::authorize('manageActivities', $internship);

        if ($column && (int) $column->internship_id !== (int) $internship->id) {
            abort(404);
        }

        return $internship;
    }

    private function validated(Request $request, bool $partial = false): array
    {
        return $request->validate([
            'name' => [$partial ? 'sometimes' : 'required', 'string', 'max:60'],
            'category' => [$partial ? 'sometimes' : 'required', Rule::in(array_keys(BoardColumn::CATEGORIES))],
            'position' => ['sometimes', 'integer', 'min:0', 'max:20'],
        ], [], ['name' => 'nama kolom', 'category' => 'arti kolom']);
    }
}
