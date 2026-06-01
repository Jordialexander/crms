{{-- Info CR card --}}
<div class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm">
    <div class="flex items-center justify-between px-4 py-2.5 border-b border-slate-100">
        <span class="flex items-center gap-1.5 text-[0.82rem] font-bold text-slate-800">
            <i class="bi bi-info-circle text-blue-500 text-sm leading-none"></i>Informasi CR
        </span>
        <div class="flex gap-1.5">
            @php
                $priColor = match($cr->priority) {
                    'low'      => 'bg-slate-100 text-slate-500',
                    'medium'   => 'bg-sky-100 text-sky-700',
                    'high'     => 'bg-amber-100 text-amber-700',
                    'critical' => 'bg-red-100 text-red-700',
                    default    => 'bg-slate-100 text-slate-500',
                };
                
                $optPriority = \App\Models\CrOption::where('type', 'priority')->where('value', $cr->priority)->first();
                $optChangeType = \App\Models\CrOption::where('type', 'change_type')->where('value', $cr->change_type)->first();
                $optCategory = \App\Models\CrOption::where('type', 'category')->where('value', $cr->category)->first();
            @endphp
            <span class="text-[0.65rem] font-bold uppercase px-2 py-0.5 rounded-md {{ $priColor }} cursor-help" 
                  title="Prioritas: {{ ucfirst($cr->priority) }}{{ $optPriority?->description ? ' - ' . $optPriority->description : '' }}">
                {{ $cr->priority }}
            </span>
            <span class="text-[0.65rem] font-bold uppercase px-2 py-0.5 rounded-md bg-slate-100 text-slate-600 cursor-help"
                  title="Tipe Change: {{ ucfirst($cr->change_type) }}{{ $optChangeType?->description ? ' - ' . $optChangeType->description : '' }}">
                {{ $cr->change_type }}
            </span>
            <span class="text-[0.65rem] font-bold uppercase px-2 py-0.5 rounded-md bg-slate-100 text-slate-600 cursor-help"
                  title="Kategori: {{ ucfirst($cr->category) }}{{ $optCategory?->description ? ' - ' . $optCategory->description : '' }}">
                {{ $cr->category }}
            </span>
        </div>
    </div>
    <div class="px-4 py-3">
        <div class="grid grid-cols-2 gap-3 mb-3">
            <div>
                <div class="text-[0.68rem] text-slate-400 font-semibold uppercase tracking-wide mb-0.5">Layanan Terdampak</div>
                <div class="text-[0.82rem] text-slate-800">{{ $cr->affected_service }}</div>
            </div>
            <div>
                <div class="text-[0.68rem] text-slate-400 font-semibold uppercase tracking-wide mb-0.5">Dibuat</div>
                <div class="text-[0.82rem] text-slate-800">{{ $cr->created_at->format('d M Y H:i') }}</div>
            </div>
        </div>

        @php
            $textTabs = array_values(array_filter([
                ['id' => 'tab-desc',      'label' => 'Deskripsi',  'content' => $cr->description,        'style' => '',                                                            'show' => true],
                ['id' => 'tab-reason',    'label' => 'Alasan',     'content' => $cr->reason,             'style' => '',                                                            'show' => true],
                ['id' => 'tab-impact',    'label' => 'Dampak',     'content' => $cr->impact,             'style' => '',                                                            'show' => (bool)$cr->impact],
                ['id' => 'tab-rollback',  'label' => 'Rollback',   'content' => $cr->rollback_plan,      'style' => 'bg-slate-50 rounded-lg border border-slate-100',              'show' => true],
                ['id' => 'tab-rejection', 'label' => 'Penolakan',  'content' => $cr->rejection_note,     'style' => 'bg-red-50 rounded-lg border border-red-100 text-red-700',     'show' => (bool)$cr->rejection_note],
                ['id' => 'tab-cancel',    'label' => 'Pembatalan', 'content' => $cr->cancellation_note,  'style' => 'bg-slate-50 rounded-lg border border-slate-100 text-slate-500','show' => (bool)$cr->cancellation_note],
                ['id' => 'tab-closing',   'label' => 'Penutupan',  'content' => $cr->closing_note,       'style' => 'bg-green-50 rounded-lg border border-green-100 text-green-700','show' => (bool)$cr->closing_note],
            ], fn($t) => $t['show']));
        @endphp
        <div class="flex flex-wrap gap-1 border-b border-slate-100 mb-2" id="crInfoTabBtns">
            @foreach($textTabs as $idx => $t)
            <button data-tab="{{ $t['id'] }}" data-group="crInfoTabs"
                    class="cr-tab-btn px-2.5 py-1 text-[0.75rem] rounded-t-md transition-colors
                           {{ $idx === 0 ? 'tab-active' : 'tab-inactive' }}">
                {{ $t['label'] }}
                @if(in_array($t['id'], ['tab-rejection','tab-cancel'])) <span class="text-red-500">!</span>@endif
            </button>
            @endforeach
        </div>
        <div id="crInfoTabs" class="max-h-[220px] overflow-y-auto">
            @foreach($textTabs as $idx => $t)
            <div id="{{ $t['id'] }}" class="{{ $idx > 0 ? 'hidden' : '' }}">
                <p class="text-[0.82rem] text-slate-700 leading-relaxed p-2 {{ $t['style'] }}">{{ $t['content'] }}</p>
            </div>
            @endforeach
        </div>
    </div>
</div>

{{-- Risk Assessment card --}}
<div class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm">
    <div class="flex items-center justify-between px-4 py-2.5 border-b border-slate-100">
        <span class="flex items-center gap-1.5 text-[0.82rem] font-bold text-slate-800">
            <i class="bi bi-shield-exclamation text-amber-500 text-sm leading-none"></i>Risk Assessment
        </span>
        @if($cr->riskAssessment && in_array($cr->status, ['under_review','waiting_approval']) && $cr->current_approver_id === auth()->id())
        <button id="editRiskBtn"
                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-[0.72rem] font-medium
                       border border-amber-200 text-amber-700 hover:bg-amber-50 transition-colors">
            <i class="bi bi-pencil text-xs leading-none"></i> Edit
        </button>
        @endif
    </div>
    <div class="px-4 py-3">
        @if($cr->riskAssessment)
        <div id="riskSummary">
            <div class="grid grid-cols-2 gap-2 mb-3">
                @foreach([
                    ['label' => 'Dampak Layanan', 'score' => $cr->riskAssessment->impact_score],
                    ['label' => 'Kompleksitas',   'score' => $cr->riskAssessment->complexity_score],
                    ['label' => 'User Terdampak', 'score' => $cr->riskAssessment->user_impact_score],
                    ['label' => 'Prob. Gagal',    'score' => $cr->riskAssessment->failure_probability_score],
                ] as $item)
                <div>
                    <div class="text-[0.68rem] text-slate-400 font-semibold mb-1">{{ $item['label'] }}</div>
                    <div class="flex items-center gap-2">
                        <div class="flex-1 h-1.5 rounded-full bg-slate-100 overflow-hidden">
                            <div class="h-full rounded-full {{ $item['score'] <= 2 ? 'bg-green-500' : ($item['score'] <= 3 ? 'bg-amber-400' : 'bg-red-500') }}"
                                 style="width:{{ $item['score'] * 20 }}%"></div>
                        </div>
                        <span class="text-[0.72rem] font-bold text-slate-600 w-7 shrink-0">{{ $item['score'] }}/5</span>
                    </div>
                </div>
                @endforeach
            </div>
            <div class="flex items-center gap-3 bg-slate-50 border border-slate-100 rounded-lg px-3 py-2">
                <div class="text-center shrink-0">
                    <div class="text-[1.3rem] font-black text-slate-800 leading-none">{{ $cr->riskAssessment->total_score }}</div>
                    <div class="text-[0.6rem] text-slate-400 uppercase tracking-wide">Skor</div>
                </div>
                <div class="w-px h-8 bg-slate-200 shrink-0"></div>
                <div>
                    <span class="s-badge badge-{{ $cr->riskAssessment->risk_level }}">{{ strtoupper($cr->riskAssessment->risk_level) }}</span>
                    <div class="text-[0.62rem] text-slate-400 mt-0.5">5–8=Low · 9–14=Medium · 15–20=High</div>
                </div>
                @if($cr->riskAssessment->notes)
                <div class="text-[0.72rem] text-slate-500 ml-auto max-w-[140px] truncate" title="{{ $cr->riskAssessment->notes }}">
                    {{ $cr->riskAssessment->notes }}
                </div>
                @endif
            </div>
        </div>
        @endif

        @if(in_array($cr->status, ['under_review','waiting_approval']) && $cr->current_approver_id === auth()->id())
        <form id="riskAssessmentForm" method="POST" action="{{ route('cr.risk.store', $cr) }}"
              class="mt-2 {{ $cr->riskAssessment ? 'hidden' : '' }}">
            @csrf
            <div class="grid grid-cols-2 gap-2 mb-2">
                @foreach([
                    ['name' => 'impact_score',              'label' => 'Dampak Layanan'],
                    ['name' => 'complexity_score',          'label' => 'Kompleksitas'],
                    ['name' => 'user_impact_score',         'label' => 'User Terdampak'],
                    ['name' => 'failure_probability_score', 'label' => 'Prob. Gagal'],
                ] as $f)
                <div>
                    <label class="block text-[0.72rem] font-semibold text-slate-600 mb-1">{{ $f['label'] }} (1–5)</label>
                    <select name="{{ $f['name'] }}" id="{{ $f['name'] }}"
                            class="risk-score w-full text-[0.8rem] border border-slate-200 rounded-lg px-2 py-1.5
                                   focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10" required>
                        <option value="">Pilih...</option>
                        @for($i=1;$i<=5;$i++)
                        <option value="{{ $i }}" {{ $cr->riskAssessment?->{$f['name']} == $i ? 'selected' : '' }}>{{ $i }}</option>
                        @endfor
                    </select>
                </div>
                @endforeach
                <div class="col-span-2">
                    <label class="block text-[0.72rem] font-semibold text-slate-600 mb-1">Catatan</label>
                    <textarea name="notes" rows="2" placeholder="Opsional..."
                              class="w-full text-[0.8rem] border border-slate-200 rounded-lg px-3 py-1.5 resize-none
                                     focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10">{{ $cr->riskAssessment?->notes }}</textarea>
                </div>
            </div>
            <div class="flex items-center gap-2 bg-slate-50 border border-slate-100 rounded-lg px-3 py-2 mb-2">
                <span class="text-[0.72rem] text-slate-400">Total:</span>
                <strong id="totalScore" class="text-[0.82rem]">{{ $cr->riskAssessment?->total_score ?? '-' }}</strong>
                <span class="text-[0.72rem] text-slate-400 ml-2">Level:</span>
                <span id="riskLevelDisplay">
                    @if($cr->riskAssessment)
                        <span class="s-badge badge-{{ $cr->riskAssessment->risk_level }}">{{ strtoupper($cr->riskAssessment->risk_level) }}</span>
                    @else
                        <span class="text-[0.72rem] text-slate-400">-</span>
                    @endif
                </span>
            </div>
            <div class="flex gap-2">
                <button id="saveRiskBtn" type="submit"
                        class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-lg text-[0.78rem]
                               font-semibold bg-amber-500 text-white hover:bg-amber-600 transition-colors">
                    <i class="bi bi-shield-check text-xs leading-none"></i>
                    {{ $cr->riskAssessment ? 'Update' : 'Simpan' }} Risk Assessment
                </button>
                @if($cr->riskAssessment)
                <button type="button" id="cancelRiskBtn"
                        class="px-3 py-2 rounded-lg text-[0.78rem] font-medium border border-slate-200
                               text-slate-600 hover:bg-slate-50 transition-colors">
                    Batal
                </button>
                @endif
            </div>
        </form>
        @elseif($cr->status === 'need_review' && $cr->current_approver_id === auth()->id())
        <div class="mt-2">
            <form method="POST" action="{{ route('need-review.start', $cr) }}" onsubmit="return confirm('Mulai review CR ini?')">
                @csrf
                <button class="w-full inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-lg
                               text-[0.78rem] font-semibold bg-amber-500 text-white hover:bg-amber-600 transition-colors">
                    <i class="bi bi-play-fill text-xs leading-none"></i> Mulai Review
                </button>
            </form>
        </div>
        @elseif(!$cr->riskAssessment)
        <p class="text-[0.78rem] text-slate-400 mt-1">
            {{ $cr->status === 'draft' ? 'Risk assessment diisi setelah CR disubmit.' : 'Menunggu Approver L1 menetapkan risk assessment.' }}
        </p>
        @endif
    </div>
</div>
