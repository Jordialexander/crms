@extends('layouts.app')

@section('content')

@php
    $chain       = $cr->approver_chain ?? [];
    $chainUsers  = count($chain) > 0 ? \App\Models\User::whereIn('id', $chain)->get()->keyBy('id') : collect();
    $latestRound = $cr->approvals->max('resubmit_round') ?? 1;
    $riskLevel   = $cr->riskAssessment?->risk_level ?? 'na';

    $statusLabel = match($cr->status) {
        'approved' => $cr->schedule ? 'Approved' : 'Menunggu Jadwal',
        default    => $cr->status_label,
    };

    $statusBadge = match($cr->status) {
        'draft'            => 'bg-slate-100 text-slate-500',
        'need_review'      => 'bg-sky-100 text-sky-700',
        'under_review'     => 'bg-orange-100 text-orange-700',
        'waiting_approval' => 'bg-violet-100 text-violet-700',
        'approved'         => 'bg-emerald-100 text-emerald-700',
        'rejected'         => 'bg-red-100 text-red-700',
        'scheduled'        => 'bg-cyan-100 text-cyan-700',
        'in_progress'      => 'bg-blue-100 text-blue-700',
        'completed'        => 'bg-green-100 text-green-700',
        'failed'           => 'bg-red-100 text-red-700',
        'rollback'         => 'bg-purple-100 text-purple-700',
        default            => 'bg-slate-100 text-slate-500',
    };

    $riskBadge = match($riskLevel) {
        'low'    => 'bg-green-100 text-green-700',
        'medium' => 'bg-amber-100 text-amber-700',
        'high'   => 'bg-red-100 text-red-700',
        default  => 'bg-slate-100 text-slate-500',
    };

    $actionNeeded = null;
    if ($cr->status === 'need_review' && $cr->current_approver_id === auth()->id())
        $actionNeeded = ['label' => 'Mulai Review', 'color' => 'bg-amber-100 text-amber-700'];
    elseif (in_array($cr->status, ['under_review','waiting_approval']) && $cr->current_approver_id === auth()->id())
        $actionNeeded = ['label' => 'Perlu Approval Anda', 'color' => 'bg-blue-100 text-blue-700'];
    elseif ($cr->status === 'approved' && !$cr->schedule && $cr->pic_id === auth()->id())
        $actionNeeded = ['label' => 'Tetapkan Jadwal', 'color' => 'bg-amber-100 text-amber-700'];
    elseif ($cr->status === 'scheduled' && $cr->pic_id === auth()->id())
        $actionNeeded = ['label' => 'Siap Implementasi', 'color' => 'bg-green-100 text-green-700'];
    elseif ($cr->status === 'in_progress' && $cr->pic_id === auth()->id())
        $actionNeeded = ['label' => 'Implementasi Berjalan', 'color' => 'bg-blue-100 text-blue-700'];
    elseif (in_array($cr->status, ['failed','rollback']) && $cr->pic_id === auth()->id() && !$cr->post_mortem_note)
        $actionNeeded = ['label' => 'Isi Post-Mortem', 'color' => 'bg-red-100 text-red-700'];
    elseif (in_array($cr->status, ['failed','rollback']) && !empty($chain) && $chain[0] === auth()->id())
        $actionNeeded = ['label' => 'Ambil Keputusan', 'color' => 'bg-amber-100 text-amber-700'];

    $canEditSchedule   = auth()->user()->can('create schedule') && ($cr->pic_id === auth()->id() || auth()->user()->hasRole('admin')) && $cr->status === 'scheduled';
    $canCreateSchedule = auth()->user()->can('create schedule') && ($cr->pic_id === auth()->id() || auth()->user()->hasRole('admin'));
    $canImplement      = auth()->user()->can('create implementation') && (auth()->id() === $cr->pic_id || auth()->user()->can('view all change_request') || auth()->user()->can('view team change_request'));

    $mainFlow = [
        ['key' => 'draft',            'label' => 'Draft'],
        ['key' => 'need_review',      'label' => 'Perlu Review'],
        ['key' => 'under_review',     'label' => 'Direview'],
        ['key' => 'waiting_approval', 'label' => 'Approval'],
        ['key' => 'approved',         'label' => 'Disetujui'],
        ['key' => 'scheduled',        'label' => 'Dijadwalkan'],
        ['key' => 'in_progress',      'label' => 'Implementasi'],
        ['key' => 'completed',        'label' => 'Selesai'],
        ['key' => 'closed',           'label' => 'Ditutup'],
    ];
    $flowKeys   = array_column($mainFlow, 'key');
    $currentIdx = array_search($cr->status, $flowKeys);
    if ($currentIdx === false) $currentIdx = -1;

    $statusNote = match($cr->status) {
        'draft'            => 'CR masih dalam tahap penyusunan. Requester perlu melengkapi data lalu submit.',
        'need_review'      => 'CR telah disubmit dan menunggu Approver L1 memeriksa kelengkapan dokumen.',
        'under_review'     => 'Approver L1 sedang memeriksa CR. Menunggu keputusan apakah siap dibawa ke tahap approval.',
        'waiting_approval' => 'CR sedang dalam proses persetujuan approver. Menunggu keputusan approve atau reject.',
        'approved'         => $cr->schedule ? 'CR telah disetujui dan sudah dijadwalkan. PIC akan memulai implementasi sesuai jadwal.' : 'CR telah disetujui. PIC perlu menetapkan jadwal implementasi.',
        'scheduled'        => 'Jadwal implementasi sudah ditetapkan. PIC siap memulai implementasi pada waktu yang ditentukan.',
        'in_progress'      => 'Implementasi sedang berjalan. Engineer (PIC) sedang mengeksekusi perubahan.',
        'completed'        => 'Implementasi berhasil diselesaikan. Menunggu konfirmasi penutupan CR.',
        'closed'           => 'CR telah ditutup. Seluruh proses change request sudah selesai.',
        'rejected'         => 'CR ditolak oleh approver. Requester dapat memperbaiki dan submit ulang, atau menutup CR.',
        'failed'           => 'Implementasi gagal. Engineer perlu mengisi analisis post-mortem. Dan perlu menunggu keputusan Approver',
        'rollback'         => 'Implementasi di-rollback. Engineer perlu mengisi analisis post-mortem. Dan perlu menunggu keputusan Approver',
        'canceled'         => 'CR dibatalkan oleh requester.',
        default            => '',
    };

    $trackerVariant = match($cr->status) {
        'approved', 'completed', 'closed' => ['bg' => 'bg-green-50',  'border' => 'border-green-200',  'icon' => 'text-green-500'],
        'rejected', 'failed', 'canceled'  => ['bg' => 'bg-red-50',    'border' => 'border-red-200',    'icon' => 'text-red-500'],
        'rollback'                         => ['bg' => 'bg-purple-50', 'border' => 'border-purple-200', 'icon' => 'text-purple-500'],
        default                            => ['bg' => 'bg-amber-50',  'border' => 'border-amber-200',  'icon' => 'text-amber-500'],
    };
@endphp

@include('cr._show_header')

{{-- Riwayat Approval --}}
@php
    $approvalsByRound = $cr->approvals->count() > 0
        ? $cr->approvals->sortBy(['resubmit_round','step'])->groupBy('resubmit_round')
        : collect();
    $totalRounds = $approvalsByRound->keys()->max() ?? 0;
@endphp

{{-- Main 2-column layout --}}
<div class="grid grid-cols-1 xl:grid-cols-[1fr_300px] gap-3">

    {{-- Left column --}}
    <div class="flex flex-col gap-3 min-w-0">
        @include('cr._show_info')
        @include('cr._show_schedule')

        {{-- Riwayat Approval --}}
        @if($cr->approvals->count() > 0)
        <div class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm">
            <div class="flex items-center justify-between px-4 py-2.5 border-b border-slate-100">
                <span class="flex items-center gap-1.5 text-[0.82rem] font-bold text-slate-800">
                    <i class="bi bi-people text-blue-500 text-sm leading-none"></i>Riwayat Approval
                </span>
                @if($totalRounds > 1)
                <span class="text-[0.65rem] font-bold px-2 py-0.5 rounded-full bg-slate-100 text-slate-500">{{ $totalRounds }} Pengajuan</span>
                @endif
            </div>
            <div class="px-4 py-3">
                @foreach($approvalsByRound->sortKeysDesc() as $round => $roundApprovals)
                <div class="mb-3 last:mb-0">
                    @if($totalRounds > 1)
                    <div class="flex items-center gap-2 mb-1.5">
                        <span class="text-[0.62rem] font-bold uppercase tracking-wide {{ $round == $totalRounds ? 'text-blue-600' : 'text-slate-400' }}">
                            <i class="bi bi-arrow-repeat mr-0.5"></i>
                            {{ $round == $totalRounds ? 'Pengajuan Ke-'.$round.' · Terbaru' : 'Pengajuan Ke-'.$round }}
                        </span>
                        @if($round < $totalRounds)
                        <span class="text-[0.58rem] font-semibold px-1.5 py-0.5 rounded-full bg-slate-100 text-slate-400 border border-slate-200">Lama</span>
                        @endif
                    </div>
                    @endif
                    @foreach($roundApprovals->sortBy('step') as $appr)
                    <div class="flex items-start gap-2 py-1.5 {{ !$loop->last ? 'border-b border-slate-50' : '' }}">
                        <div class="flex flex-col items-center shrink-0 pt-0.5">
                            @php
                                $dotBg = match($appr->status) {
                                    'approved'  => 'bg-green-100 text-green-700',
                                    'rejected'  => 'bg-red-100 text-red-700',
                                    'submitted' => 'bg-blue-100 text-blue-700',
                                    default     => 'bg-slate-100 text-slate-500',
                                };
                            @endphp
                            <div class="w-5 h-5 rounded-full flex items-center justify-center text-[0.58rem] font-bold {{ $dotBg }}">
                                @if($appr->status === 'approved') <i class="bi bi-check"></i>
                                @elseif($appr->status === 'rejected') <i class="bi bi-x"></i>
                                @elseif($appr->status === 'submitted') <i class="bi bi-hourglass-split"></i>
                                @else {{ $appr->step }}
                                @endif
                            </div>
                            @if(!$loop->last)
                            <div class="w-px flex-1 bg-slate-100 my-0.5" style="min-height:12px"></div>
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between gap-1 flex-wrap">
                                <span class="text-[0.78rem] font-semibold {{ $round < $totalRounds ? 'text-slate-400' : 'text-slate-700' }}">
                                    L{{ $appr->step }} · {{ $appr->approver->name ?? '-' }}
                                </span>
                                <span class="s-badge badge-{{ in_array($appr->status, ['pending','submitted','approved','rejected','canceled']) ? $appr->status : 'draft' }}"
                                      style="font-size:.58rem;padding:.1rem .4rem">
                                    {{ strtoupper($appr->status) }}
                                </span>
                            </div>
                            @if($appr->note)
                            <div class="text-[0.72rem] text-slate-500 mt-0.5 italic">"{{ $appr->note }}"</div>
                            @endif
                            @if($appr->approved_at)
                            <div class="text-[0.65rem] text-slate-400 mt-0.5">{{ $appr->approved_at->format('d M Y, H:i') }}</div>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    {{-- Right column --}}
    <div class="flex flex-col gap-3">
        @include('cr._show_sidebar')
    </div>

</div>

{{-- Modals --}}
@if($cr->status === 'draft' && auth()->id() == $cr->requester_id && auth()->user()->can('cancel change_request'))
<div id="cancelCrModal" class="hidden fixed inset-0 z-50 flex items-center justify-center px-4">
    <div class="absolute inset-0 bg-black/40" onclick="toggleModal('cancelCrModal')"></div>
    <div class="relative bg-white rounded-xl shadow-xl w-full max-w-sm">
        <div class="flex items-center justify-between px-4 py-3 border-b border-red-100">
            <span class="text-[0.88rem] font-bold text-red-700 flex items-center gap-1.5">
                <i class="bi bi-x-circle"></i> Batalkan CR
            </span>
            <button onclick="toggleModal('cancelCrModal')" class="text-slate-400 hover:text-slate-600 leading-none">&times;</button>
        </div>
        <form method="POST" action="{{ route('cr.cancel', $cr) }}">
            @csrf
            <div class="px-4 py-3">
                <p class="text-[0.8rem] text-slate-500 mb-2">CR <strong>{{ $cr->cr_number }}</strong> akan dibatalkan.</p>
                <label class="block text-[0.75rem] font-semibold text-slate-700 mb-1">Alasan Pembatalan *</label>
                <textarea name="cancellation_note" rows="3" placeholder="Jelaskan alasan..." required minlength="3"
                          class="w-full text-[0.8rem] border border-slate-200 rounded-lg px-3 py-2 resize-none
                                 focus:outline-none focus:border-red-400 focus:ring-2 focus:ring-red-400/10"></textarea>
            </div>
            <div class="flex justify-end gap-2 px-4 py-3 border-t border-slate-100">
                <button type="button" onclick="toggleModal('cancelCrModal')"
                        class="px-3 py-1.5 rounded-lg text-[0.78rem] font-medium border border-slate-200
                               text-slate-600 hover:bg-slate-50 transition-colors">
                    Kembali
                </button>
                <button class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[0.78rem] font-semibold
                               bg-red-600 text-white hover:bg-red-700 transition-colors">
                    <i class="bi bi-x-circle text-xs"></i> Batalkan CR
                </button>
            </div>
        </form>
    </div>
</div>
@endif

@if($cr->status === 'completed')
@can('edit implementation')
<div id="closeCrModal" class="hidden fixed inset-0 z-50 flex items-center justify-center px-4">
    <div class="absolute inset-0 bg-black/40" onclick="toggleModal('closeCrModal')"></div>
    <div class="relative bg-white rounded-xl shadow-xl w-full max-w-sm">
        <div class="flex items-center justify-between px-4 py-3 border-b border-slate-100">
            <span class="text-[0.88rem] font-bold text-slate-800 flex items-center gap-1.5">
                <i class="bi bi-lock text-slate-500"></i> Tutup CR
            </span>
            <button onclick="toggleModal('closeCrModal')" class="text-slate-400 hover:text-slate-600 leading-none">&times;</button>
        </div>
        <form method="POST" action="{{ route('cr.close', $cr) }}">
            @csrf
            <div class="px-4 py-3">
                <p class="text-[0.8rem] text-slate-500 mb-2">CR <strong>{{ $cr->cr_number }}</strong> akan ditutup secara permanen.</p>
                <label class="block text-[0.75rem] font-semibold text-slate-700 mb-1">Catatan Penutupan *</label>
                <textarea name="closing_note" rows="3" required minlength="5"
                          placeholder="Tuliskan ringkasan hasil implementasi..."
                          class="w-full text-[0.8rem] border border-slate-200 rounded-lg px-3 py-2 resize-none
                                 focus:outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-400/10">{{ old('closing_note') }}</textarea>
                <p class="text-[0.68rem] text-slate-400 mt-1">Wajib diisi. Minimal 5 karakter.</p>
            </div>
            <div class="flex justify-end gap-2 px-4 py-3 border-t border-slate-100">
                <button type="button" onclick="toggleModal('closeCrModal')"
                        class="px-3 py-1.5 rounded-lg text-[0.78rem] font-medium border border-slate-200
                               text-slate-600 hover:bg-slate-50 transition-colors">
                    Kembali
                </button>
                <button class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[0.78rem] font-semibold
                               bg-slate-800 text-white hover:bg-slate-900 transition-colors">
                    <i class="bi bi-lock text-xs"></i> Tutup CR
                </button>
            </div>
        </form>
    </div>
</div>
@endcan
@endif

<script>
// Tab switching — data-tab & data-group attributes
document.addEventListener('click', function (e) {
    const btn = e.target.closest('[data-tab]');
    if (!btn) return;
    const tabId  = btn.dataset.tab;
    const group  = btn.dataset.group;
    const container = document.getElementById(group);
    if (!container) return;

    // Hide all panels in this group
    container.querySelectorAll(':scope > div').forEach(el => el.classList.add('hidden'));
    const panel = document.getElementById(tabId);
    if (panel) panel.classList.remove('hidden');

    // Update button styles within the same group's button bar
    document.querySelectorAll(`[data-group="${group}"]`).forEach(b => {
        b.classList.remove('tab-active');
        b.classList.add('tab-inactive');
    });
    btn.classList.remove('tab-inactive');
    btn.classList.add('tab-active');
});

// Modal toggle
function toggleModal(id) {
    document.getElementById(id)?.classList.toggle('hidden');
}

document.addEventListener('DOMContentLoaded', function () {
    // Risk assessment toggle
    const raForm      = document.getElementById('riskAssessmentForm');
    const editRisk    = document.getElementById('editRiskBtn');
    const cancelRisk  = document.getElementById('cancelRiskBtn');
    const riskSummary = document.getElementById('riskSummary');

    function calcRisk() {
        const ids = ['impact_score','complexity_score','user_impact_score','failure_probability_score'];
        const total = ids.reduce((s, id) => s + (parseInt(document.getElementById(id)?.value) || 0), 0);
        const elScore = document.getElementById('totalScore');
        const elLevel = document.getElementById('riskLevelDisplay');
        if (elScore) elScore.textContent = total > 0 ? total : '-';
        if (elLevel && total > 0) {
            const level = total <= 8 ? 'LOW' : (total <= 14 ? 'MEDIUM' : 'HIGH');
            const cls   = level === 'LOW' ? 'badge-completed' : (level === 'MEDIUM' ? 'badge-in_progress' : 'badge-failed');
            elLevel.innerHTML = `<span class="s-badge ${cls}">${level}</span>`;
        }
    }

    document.addEventListener('change', e => {
        if (e.target?.classList.contains('risk-score')) calcRisk();
    });

    if (editRisk) editRisk.addEventListener('click', () => {
        raForm?.classList.remove('hidden');
        editRisk.classList.add('hidden');
        riskSummary?.classList.add('hidden');
        calcRisk();
    });
    if (cancelRisk) cancelRisk.addEventListener('click', () => {
        raForm?.classList.add('hidden');
        editRisk?.classList.remove('hidden');
        riskSummary?.classList.remove('hidden');
    });

    if (raForm) {
        calcRisk();
        raForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const btn = document.getElementById('saveRiskBtn');
            if (btn) { btn.disabled = true; btn.innerHTML = '<i class="bi bi-hourglass-split mr-1"></i>Menyimpan...'; }
            fetch('{{ route("cr.risk.store", $cr) }}', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                body: new FormData(this)
            }).then(r => r.json()).then(d => {
                if (d.success) { setTimeout(() => location.reload(), 400); }
                else {
                    alert(d.message || 'Error');
                    if (btn) { btn.disabled = false; btn.innerHTML = '<i class="bi bi-shield-check mr-1"></i>Simpan'; }
                }
            }).catch(() => {
                alert('Gagal menyimpan');
                if (btn) { btn.disabled = false; }
            });
        });
    }

    // Schedule edit toggle
    const editSched   = document.getElementById('editScheduleBtn');
    const cancelSched = document.getElementById('cancelScheduleEditBtn');
    const schedForm   = document.getElementById('scheduleEditForm');
    if (editSched)   editSched.addEventListener('click',   () => { schedForm?.classList.remove('hidden'); editSched.classList.add('hidden'); });
    if (cancelSched) cancelSched.addEventListener('click', () => { schedForm?.classList.add('hidden'); editSched?.classList.remove('hidden'); });

    // Post-mortem toggle
    const editPM   = document.getElementById('editPostMortemBtn');
    const cancelPM = document.getElementById('cancelPostMortemBtn');
    const pmForm   = document.getElementById('postMortemForm');
    if (editPM)   editPM.addEventListener('click',   () => { pmForm?.classList.remove('hidden'); editPM.classList.add('hidden'); });
    if (cancelPM) cancelPM.addEventListener('click', () => { pmForm?.classList.add('hidden'); editPM?.classList.remove('hidden'); });

    // Show approval section after risk save
    const approvalSection = document.getElementById('approvalSection');
    if (approvalSection && raForm) {
        raForm.addEventListener('submit', () => approvalSection.classList.remove('hidden'));
    }
});
</script>

@endsection
