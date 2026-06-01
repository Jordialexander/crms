{{-- Header card --}}
<div class="bg-white border border-slate-200 rounded-xl px-4 py-3 mb-3 shadow-sm">
    <div class="flex items-start justify-between gap-3 flex-wrap">
        <div class="min-w-0">
            <div class="flex items-center flex-wrap gap-1.5 mb-1.5">
                <span class="font-bold text-slate-900 text-[1.05rem]">{{ $cr->cr_number }}</span>
                <span class="inline-flex items-center gap-1 text-[0.68rem] font-bold px-2 py-0.5 rounded-full {{ $statusBadge }} cursor-help" title="{{ $statusNote }}">
                    <span class="w-[5px] h-[5px] rounded-full bg-current"></span>
                    {{ strtoupper($statusLabel) }}
                </span>
                @if($actionNeeded)
                <span class="inline-flex items-center gap-1 text-[0.68rem] font-bold px-2 py-0.5 rounded-full {{ $actionNeeded['color'] }}">
                    <i class="bi bi-lightning-fill text-[0.55rem]"></i>{{ $actionNeeded['label'] }}
                </span>
                @endif
                <span class="inline-flex items-center gap-1 text-[0.68rem] font-semibold px-2 py-0.5 rounded-full {{ $riskBadge }} cursor-help" title="Tingkat Risiko: {{ strtoupper($riskLevel) }}">
                    RISK: {{ strtoupper($riskLevel) }}
                </span>
            </div>
            <p class="text-[0.82rem] text-slate-500 mb-2.5">{{ $cr->title }}</p>

            <div class="flex flex-wrap gap-4">
                @foreach([['label'=>'Requester','name'=>$cr->requester->name??'R','bg'=>'bg-slate-200','text'=>'text-slate-600'],['label'=>'PIC','name'=>$cr->pic->name??'P','bg'=>'bg-sky-100','text'=>'text-sky-700']] as $p)
                <div class="flex items-center gap-1.5">
                    <div class="w-6 h-6 rounded-full {{ $p['bg'] }} flex items-center justify-center text-[0.65rem] font-bold {{ $p['text'] }} shrink-0">
                        {{ strtoupper(substr($p['name'], 0, 1)) }}
                    </div>
                    <div>
                        <div class="text-[0.65rem] text-slate-400 leading-none">{{ $p['label'] }}</div>
                        <div class="text-[0.75rem] font-semibold text-slate-700 leading-tight">
                            {{ $p['label'] === 'PIC' ? ($cr->pic->name ?? 'Belum ditugaskan') : ($cr->requester->name ?? '-') }}
                        </div>
                    </div>
                </div>
                @endforeach
                @foreach($chain as $i => $aid)
                @php $sa = $cr->approvals->where('resubmit_round', $latestRound)->firstWhere('step', $i+1); @endphp
                <div class="flex items-center gap-1.5">
                    <div class="w-6 h-6 rounded-full bg-emerald-100 flex items-center justify-center text-[0.65rem] font-bold text-emerald-700 shrink-0">
                        {{ strtoupper(substr($chainUsers[$aid]->name ?? 'A', 0, 1)) }}
                    </div>
                    <div>
                        <div class="text-[0.65rem] text-slate-400 leading-none">L{{ $i+1 }}</div>
                        <div class="text-[0.75rem] font-semibold text-slate-700 leading-tight flex items-center gap-1">
                            {{ $chainUsers[$aid]->name ?? '-' }}
                            @if($sa)
                                @php $aBadge = match($sa->status) { 'approved'=>'bg-green-100 text-green-700','rejected'=>'bg-red-100 text-red-700','submitted'=>'bg-blue-100 text-blue-700',default=>'bg-slate-100 text-slate-500' }; @endphp
                                <span class="text-[0.58rem] font-bold px-1.5 py-0.5 rounded-full {{ $aBadge }}">{{ strtoupper($sa->status) }}</span>
                            @elseif($cr->current_approver_id == $aid)
                                <span class="text-[0.58rem] font-bold px-1.5 py-0.5 rounded-full bg-amber-100 text-amber-700">MENUNGGU</span>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Action buttons --}}
        <div class="flex flex-wrap gap-1.5 items-start shrink-0">
            <a href="{{ route('cr.index') }}"
               class="inline-flex items-center justify-center w-7 h-7 rounded-md border border-slate-200
                      text-slate-500 hover:border-slate-300 hover:bg-slate-50 transition-colors">
                <i class="bi bi-arrow-left text-xs leading-none"></i>
            </a>
            <a href="{{ route('activity-log.show', $cr) }}"
               class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-md text-[0.75rem] font-medium
                      border border-slate-200 text-slate-600 hover:border-blue-300 hover:text-blue-600 hover:bg-blue-50 transition-colors">
                <i class="bi bi-clock-history text-xs leading-none"></i> Timeline
                <span class="inline-flex items-center justify-center min-w-[16px] h-[16px] px-1 rounded-full
                             bg-slate-200 text-slate-600 text-[0.58rem] font-bold leading-none">{{ $cr->activityLogs->count() }}</span>
            </a>

            @if(in_array($cr->status, ['draft','rejected']) && (auth()->id() == $cr->requester_id || auth()->user()->hasRole('admin')))
                <a href="{{ route('cr.edit', $cr) }}"
                   class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-md text-[0.75rem] font-medium
                          border border-slate-200 text-slate-600 hover:border-slate-300 hover:bg-slate-50 transition-colors">
                    <i class="bi bi-pencil text-xs leading-none"></i> Edit
                </a>
                @can('submit change_request')
                <form method="POST" action="{{ route('cr.submit', $cr) }}"
                      onsubmit="return confirm('{{ $cr->status === "rejected" ? "Submit ulang CR ini?" : "Submit CR ini untuk direview?" }}')">
                    @csrf
                    <button class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-md text-[0.75rem] font-semibold
                                   bg-blue-600 text-white hover:bg-blue-700 transition-colors">
                        <i class="bi bi-send text-xs leading-none"></i>
                        {{ $cr->status === 'rejected' ? 'Submit Ulang' : 'Submit' }}
                    </button>
                </form>
                @endcan
            @endif

            @if($cr->status === 'rejected' && (auth()->id() == $cr->requester_id || auth()->user()->hasRole('admin')))
            @can('cancel change_request')
            <form method="POST" action="{{ route('cr.close-rejected', $cr) }}" onsubmit="return confirm('Tutup CR ini?')">
                @csrf
                <button class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-md text-[0.75rem] font-medium
                               border border-slate-200 text-slate-600 hover:border-slate-300 hover:bg-slate-50 transition-colors">
                    <i class="bi bi-archive text-xs leading-none"></i> Tutup CR
                </button>
            </form>
            @endcan
            @endif

            @if($cr->status === 'draft' && auth()->id() == $cr->requester_id)
            @can('cancel change_request')
            <button onclick="toggleModal('cancelCrModal')"
                    class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-md text-[0.75rem] font-medium
                           border border-red-200 text-red-600 hover:border-red-300 hover:bg-red-50 transition-colors">
                <i class="bi bi-x-circle text-xs leading-none"></i> Batalkan
            </button>
            @endcan
            @endif

            @if($cr->status === 'scheduled' && $cr->pic_id === auth()->id())
            @can('create implementation')
            <form method="POST" action="{{ route('cr.start-implementation', $cr) }}" onsubmit="return confirm('Mulai implementasi CR ini?')">
                @csrf
                <button class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-md text-[0.75rem] font-semibold
                               bg-green-600 text-white hover:bg-green-700 transition-colors">
                    <i class="bi bi-play-fill text-xs leading-none"></i> Mulai Implementasi
                </button>
            </form>
            @endcan
            @endif

            @if($cr->status === 'completed')
            @can('edit implementation')
            <button onclick="toggleModal('closeCrModal')"
                    class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-md text-[0.75rem] font-semibold
                           bg-slate-700 text-white hover:bg-slate-800 transition-colors">
                <i class="bi bi-lock text-xs leading-none"></i> Tutup CR
            </button>
            @endcan
            @endif

            @if(in_array($cr->status, ['failed','rollback']) && auth()->user()->hasRole('admin'))
            <form method="POST" action="{{ route('cr.reschedule', $cr) }}" onsubmit="return confirm('Reschedule CR ini?')">
                @csrf
                <button class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-md text-[0.75rem] font-semibold
                               bg-amber-500 text-white hover:bg-amber-600 transition-colors">
                    <i class="bi bi-arrow-clockwise text-xs leading-none"></i> Reschedule
                </button>
            </form>
            @endif

            @if($cr->status === 'draft' && auth()->user()->hasRole('admin'))
            @can('delete change_request')
            <form method="POST" action="{{ route('cr.destroy', $cr) }}" onsubmit="return confirm('Hapus CR ini secara permanen?')">
                @csrf @method('DELETE')
                <button class="inline-flex items-center justify-center w-7 h-7 rounded-md border border-red-200
                               text-red-500 hover:border-red-300 hover:bg-red-50 transition-colors">
                    <i class="bi bi-trash text-xs leading-none"></i>
                </button>
            </form>
            @endcan
            @endif
        </div>
    </div>
</div>

{{-- Status tracker --}}
<div class="rounded-xl border px-4 py-3 mb-3 {{ $trackerVariant['bg'] }} {{ $trackerVariant['border'] }}">
    @if(!in_array($cr->status, ['rejected','canceled']))
    <div class="flex items-center overflow-x-auto pb-1 mb-2.5" style="scrollbar-width:thin">
        @foreach($mainFlow as $i => $step)
            @php
                $isDone    = $currentIdx > $i;
                $isCurrent = $currentIdx === $i;
                if ($currentIdx === -1) {
                    $failIdx = array_search('in_progress', $flowKeys);
                    $isDone  = $i <= $failIdx;
                    $isCurrent = false;
                }
            @endphp
            <div class="flex items-center shrink-0">
                <div class="flex flex-col items-center" style="min-width:52px">
                    <div class="w-[22px] h-[22px] rounded-full flex items-center justify-center
                                {{ $isDone ? 'bg-green-500' : ($isCurrent ? 'bg-blue-500' : 'bg-slate-200') }}">
                        @if($isDone)
                            <i class="bi bi-check text-white text-[0.6rem] font-black"></i>
                        @else
                            <div class="w-2 h-2 rounded-full {{ $isCurrent ? 'bg-white' : 'bg-slate-400' }}"></div>
                        @endif
                    </div>
                    <span class="text-[0.6rem] mt-0.5 whitespace-nowrap text-center leading-tight
                                 {{ $isDone ? 'text-green-600' : ($isCurrent ? 'text-blue-600 font-bold' : 'text-slate-400') }}">
                        {{ $step['label'] }}
                    </span>
                </div>
                @if(!$loop->last)
                <div class="h-[2px] flex-1 mx-px mb-4 {{ $isDone ? 'bg-green-400' : 'bg-slate-200' }}" style="min-width:18px"></div>
                @endif
            </div>
        @endforeach
        @if(in_array($cr->status, ['failed','rollback']))
        <div class="flex items-center shrink-0">
            <div class="h-[2px] mb-4 bg-red-300" style="min-width:18px"></div>
            <div class="flex flex-col items-center" style="min-width:52px">
                <div class="w-[22px] h-[22px] rounded-full flex items-center justify-center
                            {{ $cr->status === 'rollback' ? 'bg-purple-500' : 'bg-red-500' }}">
                    <i class="bi bi-{{ $cr->status === 'rollback' ? 'arrow-counterclockwise' : 'x' }} text-white text-[0.6rem]"></i>
                </div>
                <span class="text-[0.6rem] mt-0.5 font-bold whitespace-nowrap
                             {{ $cr->status === 'rollback' ? 'text-purple-600' : 'text-red-600' }}">
                    {{ $cr->status === 'rollback' ? 'Rollback' : 'Gagal' }}
                </span>
            </div>
        </div>
        @endif
    </div>
    @endif
    <div class="flex items-start gap-2">
        <i class="bi bi-info-circle-fill mt-0.5 shrink-0 text-[0.8rem] {{ $trackerVariant['icon'] }}"></i>
        <div class="text-[0.78rem] leading-relaxed text-slate-700">
            <span class="font-semibold">Status saat ini:</span>
            <span class="inline-flex items-center gap-1 text-[0.68rem] font-bold px-2 py-0.5 rounded-full mx-1 {{ $statusBadge }}">
                {{ strtoupper($statusLabel) }}
            </span>
            {{ $statusNote }}
        </div>
    </div>
</div>
