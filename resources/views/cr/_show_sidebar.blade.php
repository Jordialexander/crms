{{-- Lampiran --}}
@if($cr->attachments->count() > 0)
<div class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm">
    <div class="flex items-center justify-between px-4 py-2.5 border-b border-slate-100">
        <span class="flex items-center gap-1.5 text-[0.82rem] font-bold text-slate-800">
            <i class="bi bi-paperclip text-slate-500 text-sm leading-none"></i>Lampiran
        </span>
        <span class="text-[0.65rem] font-bold px-2 py-0.5 rounded-full bg-slate-100 text-slate-500">{{ $cr->attachments->count() }}</span>
    </div>
    <div class="px-4 py-3 flex flex-col gap-1">
        @foreach($cr->attachments as $att)
        <a href="{{ route('cr.attachment.download', [$cr, $att]) }}"
           class="flex items-center gap-2 p-1.5 rounded-lg text-slate-700 hover:bg-slate-50 transition-colors">
            <i class="bi bi-file-earmark text-blue-400 text-sm shrink-0"></i>
            <div class="flex-1 min-w-0">
                <div class="text-[0.78rem] font-semibold truncate">{{ $att->original_name }}</div>
                <div class="text-[0.65rem] text-slate-400">{{ $att->created_at->format('d M Y') }}{{ $att->size ? ' · '.number_format($att->size/1024,0).' KB' : '' }}</div>
            </div>
            <i class="bi bi-download text-slate-400 text-xs shrink-0"></i>
        </a>
        @endforeach
    </div>
</div>
@endif

{{-- Tindakan Approval --}}
@if($cr->current_approver_id === auth()->id())
@can('approve change_request')
@if(in_array($cr->status, ['under_review','waiting_approval']))
@php $isUnderReview = $cr->status === 'under_review'; @endphp
<div class="bg-white rounded-xl overflow-hidden shadow-sm border {{ $isUnderReview ? 'border-blue-300' : 'border-green-300' }}">
    <div class="px-4 py-2.5 border-b {{ $isUnderReview ? 'bg-blue-50 border-blue-100' : 'bg-green-50 border-green-100' }}">
        <span class="flex items-center gap-1.5 text-[0.82rem] font-bold {{ $isUnderReview ? 'text-blue-800' : 'text-green-800' }}">
            <i class="bi bi-{{ $isUnderReview ? 'shield-exclamation' : 'check2-circle' }} text-sm leading-none"></i>
            Tindakan Approval
        </span>
    </div>
    <div class="px-4 py-3">
        @if($cr->status === 'under_review' && !$cr->riskAssessment)
        <p class="text-[0.78rem] text-slate-400">Simpan Risk Assessment di panel kiri untuk menampilkan tindakan approval.</p>
        @else
        <div id="approvalSection" class="{{ ($cr->status === 'waiting_approval' || $cr->riskAssessment) ? '' : 'hidden' }}">
            <form method="POST" action="{{ route('approval.approve', $cr) }}" class="mb-3">
                @csrf
                <label class="block text-[0.72rem] font-semibold text-slate-600 mb-1">Catatan Persetujuan</label>
                <textarea name="note" rows="2" placeholder="Opsional..."
                          class="w-full text-[0.8rem] border border-slate-200 rounded-lg px-3 py-1.5 mb-2 resize-none
                                 focus:outline-none focus:border-green-500"></textarea>
                <button onclick="return confirm('Setujui CR ini?')"
                        class="w-full inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-lg text-[0.78rem]
                               font-semibold bg-green-600 text-white hover:bg-green-700 transition-colors">
                    <i class="bi bi-check-circle text-xs"></i> Approve
                </button>
            </form>
            <div class="border-t border-slate-100 pt-3">
                <form method="POST" action="{{ route('approval.reject', $cr) }}">
                    @csrf
                    <label class="block text-[0.72rem] font-semibold text-red-600 mb-1">Alasan Penolakan *</label>
                    <textarea name="note" rows="2" placeholder="Jelaskan alasan..." required
                              class="w-full text-[0.8rem] border border-red-200 rounded-lg px-3 py-1.5 mb-2 resize-none
                                     focus:outline-none focus:border-red-500"></textarea>
                    <button onclick="return confirm('Tolak CR ini?')"
                            class="w-full inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-lg text-[0.78rem]
                                   font-semibold bg-red-600 text-white hover:bg-red-700 transition-colors">
                        <i class="bi bi-x-circle text-xs"></i> Reject
                    </button>
                </form>
            </div>
        </div>
        @endif
    </div>
</div>
@endif
@endcan
@endif

{{-- Post-mortem --}}
@if(in_array($cr->status, ['failed','rollback']) && $cr->pic_id === auth()->id())
@can('create implementation')
@php $isFailed = $cr->status === 'failed'; @endphp
<div class="bg-white rounded-xl overflow-hidden shadow-sm border {{ $isFailed ? 'border-red-300' : 'border-slate-200' }}">
    <div class="flex items-center justify-between px-4 py-2.5 border-b {{ $isFailed ? 'bg-red-50 border-red-100' : 'bg-slate-50 border-slate-100' }}">
        <span class="flex items-center gap-1.5 text-[0.82rem] font-bold {{ $isFailed ? 'text-red-800' : 'text-slate-700' }}">
            <i class="bi bi-file-text text-sm leading-none"></i>
            {{ $isFailed ? 'Post-Mortem Kegagalan' : 'Analisis Rollback' }}
        </span>
        @if($cr->post_mortem_note)
        <span class="s-badge badge-completed" style="font-size:.65rem">Terisi</span>
        @else
        <span class="s-badge badge-in_progress" style="font-size:.65rem">Wajib</span>
        @endif
    </div>
    <div class="px-4 py-3">
        @if($cr->post_mortem_note)
        <div class="text-[0.8rem] text-slate-700 bg-slate-50 rounded-lg border border-slate-100 p-2.5 mb-2">{{ $cr->post_mortem_note }}</div>
        <button id="editPostMortemBtn"
                class="w-full inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-lg text-[0.78rem]
                       font-medium border border-slate-200 text-slate-600 hover:bg-slate-50 transition-colors">
            <i class="bi bi-pencil text-xs"></i> Edit Analisis
        </button>
        @endif
        <form id="postMortemForm" method="POST" action="{{ route('cr.post-mortem', $cr) }}"
              class="{{ $cr->post_mortem_note ? 'hidden' : '' }}">
            @csrf
            <textarea name="post_mortem_note" rows="3" required minlength="10"
                      placeholder="{{ $isFailed ? 'Jelaskan penyebab kegagalan...' : 'Jelaskan alasan rollback...' }}"
                      class="w-full text-[0.8rem] border border-slate-200 rounded-lg px-3 py-1.5 mb-2 resize-none
                             focus:outline-none focus:border-red-400">{{ $cr->post_mortem_note }}</textarea>
            <div class="flex gap-2">
                <button onclick="return confirm('Simpan analisis ini?')"
                        class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-lg text-[0.78rem]
                               font-semibold {{ $isFailed ? 'bg-red-600 hover:bg-red-700' : 'bg-slate-700 hover:bg-slate-800' }} text-white transition-colors">
                    <i class="bi bi-save text-xs"></i> Simpan
                </button>
                @if($cr->post_mortem_note)
                <button type="button" id="cancelPostMortemBtn"
                        class="px-3 py-2 rounded-lg text-[0.78rem] font-medium border border-slate-200
                               text-slate-600 hover:bg-slate-50 transition-colors">
                    Batal
                </button>
                @endif
            </div>
        </form>
    </div>
</div>
@endcan
@endif

{{-- Keputusan Approver L1 --}}
@if(in_array($cr->status, ['failed','rollback']))
@php $isApproverL1 = !empty($chain) && $chain[0] === auth()->id(); @endphp
@if($isApproverL1)
@can('approve change_request')
<div class="bg-white border border-amber-300 rounded-xl overflow-hidden shadow-sm">
    <div class="px-4 py-2.5 border-b bg-amber-50 border-amber-100">
        <span class="flex items-center gap-1.5 text-[0.82rem] font-bold text-amber-800">
            <i class="bi bi-exclamation-triangle text-amber-500 text-sm leading-none"></i>Keputusan Approver
        </span>
    </div>
    <div class="px-4 py-3">
        @if($cr->status === 'failed' && !$cr->post_mortem_note)
        <div class="flex items-center gap-2 bg-amber-50 border border-amber-100 rounded-lg px-3 py-2 text-[0.78rem] text-amber-700">
            <i class="bi bi-hourglass shrink-0"></i> Menunggu engineer mengisi post-mortem.
        </div>
        @else
            @if($cr->post_mortem_note)
            <div class="mb-3">
                <div class="text-[0.68rem] text-slate-400 font-semibold uppercase tracking-wide mb-1">Post-Mortem Engineer</div>
                <div class="text-[0.78rem] text-slate-700 bg-slate-50 border border-slate-100 rounded-lg p-2.5">
                    {{ \Illuminate\Support\Str::limit($cr->post_mortem_note, 120) }}
                </div>
            </div>
            @endif
            <form method="POST" action="{{ route('cr.reschedule-decision', $cr) }}" class="mb-2"
                  onsubmit="return confirm('Izinkan reschedule? Jadwal lama dihapus.')">
                @csrf
                <input type="hidden" name="action" value="reschedule">
                <button class="w-full inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-lg text-[0.78rem]
                               font-semibold bg-green-600 text-white hover:bg-green-700 transition-colors">
                    <i class="bi bi-arrow-clockwise text-xs"></i> Izinkan Reschedule
                </button>
            </form>
            <form method="POST" action="{{ route('cr.reschedule-decision', $cr) }}"
                  onsubmit="return confirm('Tutup CR secara permanen?')">
                @csrf
                <input type="hidden" name="action" value="close">
                <button class="w-full inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-lg text-[0.78rem]
                               font-semibold border border-red-200 text-red-600 hover:bg-red-50 hover:border-red-300 transition-colors">
                    <i class="bi bi-x-circle text-xs"></i> Tutup CR
                </button>
            </form>
        @endif
    </div>
</div>
@endcan
@endif
@endif
