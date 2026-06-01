@extends('layouts.app')

@php
    $kindMeta = function ($n) {
        $kind = data_get($n->data, 'kind');
        return match ($kind) {
            'cr_submitted'           => ['icon' => 'send-check',        'color' => 'info',      'title' => 'CR Disubmit'],
            'cr_need_review'         => ['icon' => 'eye',               'color' => 'info',      'title' => 'Perlu Review'],
            'cr_under_review'        => ['icon' => 'shield-exclamation','color' => 'warning',   'title' => 'Sedang Direview'],
            'cr_waiting_approval'    => ['icon' => 'hourglass-split',   'color' => 'primary',   'title' => 'Menunggu Approval'],
            'cr_needs_approval'      => ['icon' => 'bell',              'color' => 'warning',   'title' => 'Butuh Approval'],
            'cr_approved'            => ['icon' => 'check-circle',        'color' => 'success',   'title' => 'CR Disetujui'],
            'cr_rejected'            => ['icon' => 'x-circle',            'color' => 'danger',    'title' => 'CR Ditolak'],
            'cr_assigned_engineer'   => ['icon' => 'tools',               'color' => 'primary',   'title' => 'Tugas Implementasi'],
            'cr_scheduled'           => ['icon' => 'calendar-check',      'color' => 'primary',   'title' => 'CR Dijadwalkan'],
            'cr_in_progress'         => ['icon' => 'play-circle',         'color' => 'warning',   'title' => 'Implementasi Berjalan'],
            'cr_implementation_done'     => ['icon' => 'clipboard-check',        'color' => 'success', 'title' => 'Implementasi Berhasil'],
            'cr_implementation_failed'   => ['icon' => 'clipboard-x',            'color' => 'danger',  'title' => 'Implementasi Gagal'],
            'cr_implementation_rollback' => ['icon' => 'arrow-counterclockwise', 'color' => 'warning', 'title' => 'Implementasi Rollback'],
            'cr_rescheduled'         => ['icon' => 'calendar-x',          'color' => 'warning',   'title' => 'CR Dijadwalkan Ulang'],
            'cr_post_mortem_filled'  => ['icon' => 'file-earmark-text',   'color' => 'secondary', 'title' => 'Post-Mortem Diisi'],
            'cr_closed'              => ['icon' => 'lock',                 'color' => 'secondary', 'title' => 'CR Ditutup'],
            default                  => ['icon' => 'info-circle',          'color' => 'secondary', 'title' => 'Notifikasi'],
        };
    };
@endphp

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="fw-bold mb-1">Notifikasi</h4>
        <p class="text-muted mb-0">Aktivitas penting sesuai role Anda</p>
    </div>
    <button class="btn btn-sm btn-outline-secondary" id="page-read-all-btn">
        <i class="bi bi-check2-all me-1"></i>Tandai semua dibaca
    </button>
</div>

<div class="card border-0 shadow-sm">
    <div class="list-group list-group-flush">
        @forelse($notifications as $n)
            @php
                $meta  = $kindMeta($n);
                $kind  = data_get($n->data, 'kind');
                $crId  = data_get($n->data, 'cr_id');
                $target = $crId
                    ? match($kind) {
                        'cr_need_review'      => route('need-review.show', $crId),
                        'cr_needs_approval'   => route('approval.show', $crId),
                        default               => route('cr.show', $crId),
                    }
                    : route('notifications.index');
            @endphp
            <div class="list-group-item d-flex justify-content-between align-items-start page-notif-item {{ $n->read_at ? '' : 'bg-light' }}"
                 data-notif-id="{{ $n->id }}" data-read="{{ $n->read_at ? '1' : '0' }}">
                <div class="d-flex gap-3">
                    <div class="pt-1">
                        <i class="bi bi-{{ $meta['icon'] }} text-{{ $meta['color'] }}"></i>
                    </div>
                    <div>
                        <div class="fw-semibold">{{ $meta['title'] }}
                            <span class="badge bg-primary ms-1 page-notif-new {{ $n->read_at ? 'd-none' : '' }}">NEW</span>
                        </div>
                        <div class="text-muted small">
                            {{ data_get($n->data,'cr_number') }} - {{ data_get($n->data,'title') }}
                        </div>
                        <div class="text-muted small">{{ $n->created_at->diffForHumans() }}</div>
                    </div>
                </div>
                <a href="{{ $target }}"
                   class="btn btn-sm btn-outline-primary page-notif-open"
                   data-open-url="{{ route('notifications.open', $n->id) }}"
                   data-target="{{ $target }}">Buka</a>
            </div>
        @empty
            <div class="list-group-item text-center text-muted py-5">
                Tidak ada notifikasi.
            </div>
        @endforelse
    </div>
    @if($notifications->hasPages())
        <div class="card-footer bg-white">{{ $notifications->links() }}</div>
    @endif
</div>

<script>
(function () {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

    function markPageItemRead(item) {
        if (item.dataset.read === '1') return;
        item.dataset.read = '1';
        item.classList.remove('bg-light');
        const badge = item.querySelector('.page-notif-new');
        if (badge) badge.classList.add('d-none');
    }

    document.querySelectorAll('.page-notif-open').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const item = btn.closest('.page-notif-item');
            const openUrl = btn.dataset.openUrl;
            const target = btn.dataset.target;

            fetch(openUrl, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            }).then(function () {
                if (item) markPageItemRead(item);
                const unread = document.querySelectorAll('.page-notif-item[data-read="0"]').length;
                const badge = document.getElementById('notif-badge');
                if (badge) {
                    if (unread > 0) { badge.textContent = unread; badge.classList.remove('d-none'); }
                    else badge.classList.add('d-none');
                }
                window.location.href = target;
            }).catch(function () {
                window.location.href = target;
            });
        });
    });

    const readAllBtn = document.getElementById('page-read-all-btn');
    if (readAllBtn) {
        readAllBtn.addEventListener('click', function () {
            fetch('{{ route('notifications.readAll') }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            }).then(function () {
                document.querySelectorAll('.page-notif-item').forEach(markPageItemRead);
                const badge = document.getElementById('notif-badge');
                if (badge) badge.classList.add('d-none');
                // Update juga dropdown items jika ada
                document.querySelectorAll('.notif-item').forEach(function (item) {
                    item.dataset.read = '1';
                    item.classList.remove('bg-light');
                    const b = item.querySelector('.notif-new-badge');
                    if (b) b.classList.add('d-none');
                });
            });
        });
    }
})();
</script>
@endsection
