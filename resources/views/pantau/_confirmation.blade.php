{{-- Konfirmasi satu klik oleh Company Supervisor atas data penempatan, plus permintaan ganti supervisor. --}}
@if (auth()->user()->role === 'supervisor' && $internship->isSupervisedBy(auth()->user()) && $internship->status === 'berjalan')
    @if (! $internship->supervisor_confirmation)
        <section class="card callout" id="konfirmasi">
            <div class="card-head">
                <div>
                    <h2 class="card-title">Konfirmasi penempatan</h2>
                    <p class="card-sub">Cukup sekali</p>
                </div>
            </div>
            <div class="confirm-list">
                @include('pantau._confirm-item', ['internship' => $internship])
            </div>
        </section>
    @elseif ($internship->supervisor_confirmation === 'disputed')
        <div class="alert alert-warning" role="status">
            Laporan Anda: “{{ $internship->supervisor_confirmation_note }}”. Sedang ditindaklanjuti TU.
        </div>
    @endif

@endif
