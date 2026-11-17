<?php

namespace App\Http\Controllers;

use App\Models\Portfolio;
use App\Models\PortfolioCertificate;
use App\Models\PortfolioProject;
use App\Models\StoredFile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** Proyek dan sertifikat pelatihan di portofolio mahasiswa (tambah, ubah, hapus). */
class PortfolioItemController extends Controller
{
    private const DISK = 'local';

    // ---------- Proyek ----------

    public function storeProject(Request $request): RedirectResponse
    {
        $portfolio = Portfolio::forUser($request->user());
        if ($portfolio->projects()->count() >= Portfolio::MAX_PROJECTS) {
            throw ValidationException::withMessages(['title' => 'Paling banyak '.Portfolio::MAX_PROJECTS.' proyek.']);
        }
        $data = $this->projectData($request, 'project');
        $project = $portfolio->projects()->create($data + ['position' => (int) $portfolio->projects()->max('position') + 1]);
        $this->attachImage($request, $project);

        return $this->back('Proyek ditambahkan.', '#proyek');
    }

    public function updateProject(Request $request, PortfolioProject $project): RedirectResponse
    {
        $this->own($request->user(), $project->portfolio);
        $project->update($this->projectData($request, 'project-'.$project->id));
        if ($request->boolean('remove_image')) {
            $this->dropFile($project->image);
            $project->update(['image_file_id' => null]);
        }
        $this->attachImage($request, $project);

        return $this->back('Proyek diperbarui.', '#proyek');
    }

    public function destroyProject(Request $request, PortfolioProject $project): RedirectResponse
    {
        $this->own($request->user(), $project->portfolio);
        $image = $project->image;
        $project->delete();
        $this->dropFile($image);

        return $this->back('Proyek dihapus.', '#proyek');
    }

    // ---------- Sertifikat ----------

    public function storeCertificate(Request $request): RedirectResponse
    {
        $portfolio = Portfolio::forUser($request->user());
        if ($portfolio->certificates()->count() >= Portfolio::MAX_CERTIFICATES) {
            throw ValidationException::withMessages(['name' => 'Paling banyak '.Portfolio::MAX_CERTIFICATES.' sertifikat.']);
        }
        $data = $this->certificateData($request, 'cert');
        $certificate = $portfolio->certificates()->create($data + ['position' => (int) $portfolio->certificates()->max('position') + 1]);
        $this->attachCertificateFile($request, $certificate);

        return $this->back('Sertifikat ditambahkan.', '#sertifikat');
    }

    public function updateCertificate(Request $request, PortfolioCertificate $certificate): RedirectResponse
    {
        $this->own($request->user(), $certificate->portfolio);
        $certificate->update($this->certificateData($request, 'cert-'.$certificate->id));
        $this->attachCertificateFile($request, $certificate);

        return $this->back('Sertifikat diperbarui.', '#sertifikat');
    }

    public function destroyCertificate(Request $request, PortfolioCertificate $certificate): RedirectResponse
    {
        $this->own($request->user(), $certificate->portfolio);
        $file = $certificate->file;
        $certificate->delete();
        $this->dropFile($file);

        return $this->back('Sertifikat dihapus.', '#sertifikat');
    }

    // ---------- Bantuan ----------

    private function projectData(Request $request, string $bag): array
    {
        $data = $request->validateWithBag($bag, [
            'title' => ['required', 'string', 'max:120'],
            'role' => ['nullable', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:1000'],
            'tech' => ['nullable', 'string', 'max:200'],
            'url' => ['nullable', 'url:http,https', 'max:255'],
            'year' => ['nullable', 'integer', 'between:2000,'.(now()->year + 1)],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
        ], [
            'title.required' => 'Nama proyek wajib diisi.',
            'url.url' => 'Tautan harus diawali http:// atau https://.',
            'image.max' => 'Gambar paling besar 3 MB.',
            'image.mimes' => 'Gambar harus JPG, PNG, atau WEBP.',
        ]);
        $data['tech'] = collect(explode(',', (string) ($data['tech'] ?? '')))->map(fn ($t) => trim($t))->filter()->unique()->take(10)->implode(', ') ?: null;
        unset($data['image']);

        return $data;
    }

    private function certificateData(Request $request, string $bag): array
    {
        $data = $request->validateWithBag($bag, [
            'name' => ['required', 'string', 'max:150'],
            'issuer' => ['required', 'string', 'max:120'],
            'issued_on' => ['nullable', 'date', 'before_or_equal:today'],
            'credential_url' => ['nullable', 'url:http,https', 'max:255'],
            'file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ], [
            'name.required' => 'Nama pelatihan wajib diisi.',
            'issuer.required' => 'Penyelenggara wajib diisi.',
            'issued_on.before_or_equal' => 'Tanggal terbit tidak boleh di masa depan.',
            'credential_url.url' => 'Tautan harus diawali http:// atau https://.',
            'file.max' => 'Berkas paling besar 5 MB.',
            'file.mimes' => 'Berkas harus PDF, JPG, atau PNG.',
        ]);
        unset($data['file']);

        return $data;
    }

    private function attachImage(Request $request, PortfolioProject $project): void
    {
        if (! $request->hasFile('image')) {
            return;
        }
        $old = $project->image;
        $project->update(['image_file_id' => $this->storeFile($request->file('image'), $request->user())->id]);
        $this->dropFile($old);
    }

    private function attachCertificateFile(Request $request, PortfolioCertificate $certificate): void
    {
        if (! $request->hasFile('file')) {
            return;
        }
        $old = $certificate->file;
        $certificate->update(['file_id' => $this->storeFile($request->file('file'), $request->user())->id]);
        $this->dropFile($old);
    }

    private function storeFile(UploadedFile $file, User $user): StoredFile
    {
        $path = $file->storeAs("portofolio/{$user->id}", Str::uuid().'.'.strtolower($file->extension()), self::DISK);

        return StoredFile::create([
            'disk' => self::DISK,
            'path' => $path,
            'original_name' => mb_substr($file->getClientOriginalName(), 0, 200),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'checksum' => hash_file('sha256', $file->getRealPath()),
            'uploaded_by' => $user->id,
        ]);
    }

    private function dropFile(?StoredFile $file): void
    {
        if (! $file) {
            return;
        }
        Storage::disk($file->disk)->delete($file->path);
        $file->delete();
    }

    private function own(User $user, Portfolio $portfolio): void
    {
        abort_unless((int) $portfolio->user_id === (int) $user->id, 403);
    }

    private function back(string $message, string $anchor): RedirectResponse
    {
        return redirect()->to(route('portofolio.edit').$anchor)->with('status', $message);
    }
}
