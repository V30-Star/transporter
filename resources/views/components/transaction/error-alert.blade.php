@if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show border-0 shadow p-0 overflow-hidden" role="alert">
        <div class="d-flex align-items-center px-4 py-3" style="background-color: #c0392b;">
            <i class="bi bi-exclamation-triangle-fill text-white me-2 fs-5"></i>
            <strong class="text-white fs-6">{{ $title ?? 'Gagal Menyimpan Data!' }}</strong>
            <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="alert"
                aria-label="Close"></button>
        </div>

        <div class="px-4 py-3" style="background-color: #fdeded; border-left: 5px solid #c0392b;">
            <p class="mb-2 text-danger fw-semibold">
                <i class="bi bi-info-circle me-1"></i>
                {{ $message ?? 'Periksa kembali data berikut sebelum menyimpan:' }}
            </p>
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li class="text-danger mb-1">
                        <i class="bi bi-dot fs-5 align-middle"></i>
                        {{ $error }}
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
@endif
