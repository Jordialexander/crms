@if(in_array($cr->status, ['approved','scheduled','in_progress','completed','failed','rollback','closed']))
<div class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm">
    <div class="flex items-center justify-between px-4 py-2.5 border-b border-slate-100">
        <div class="flex gap-0" id="implTabBtns">
            <button data-tab="tab-schedule" data-group="implTabs"
                    class="cr-tab-btn px-3 py-1 text-[0.78rem] tab-active">
                <i class="bi bi-calendar-event text-sky-500 text-xs leading-none mr-1"></i>Jadwal
                @if($cr->schedules->count() > 1)
                <span class="ml-1 text-[0.6rem] font-bold px-1.5 py-0.5 rounded-full bg-slate-200 text-slate-600">{{ $cr->schedules->count() }}</span>
                @endif
            </button>
            @if($cr->implementationLogs->count() > 0 || $cr->status === 'in_progress')
            <button data-tab="tab-impllog" data-group="implTabs"
                    class="cr-tab-btn px-3 py-1 text-[0.78rem] tab-inactive">
                <i class="bi bi-journal-text text-green-500 text-xs leading-none mr-1"></i>Log Implementasi
                @if($cr->implementationLogs->count() > 0)
                <span class="ml-1 text-[0.6rem] font-bold px-1.5 py-0.5 rounded-full bg-slate-200 text-slate-600">{{ $cr->implementationLogs->count() }}</span>
                @endif
            </button>
            @endif
        </div>
        @if($canEditSchedule && $cr->schedule)
        <button id="editScheduleBtn"
                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-[0.72rem] font-medium
                       border border-slate-200 text-slate-600 hover:border-slate-300 hover:bg-slate-50 transition-colors">
            <i class="bi bi-pencil text-xs leading-none"></i> Edit
        </button>
        @endif
    </div>

    <div id="implTabs" class="px-4 py-3">
        {{-- Jadwal tab --}}
        <div id="tab-schedule">
            @if($cr->schedules->count() > 0)
                @foreach($cr->schedules->sortByDesc('round') as $sched)
                @php $isActiveSched = $sched->is_active; @endphp
                @if(!$loop->first)<hr class="my-3 border-slate-100">@endif
                <div class="flex items-center gap-2 mb-2">
                    <div class="w-6 h-6 rounded-full flex items-center justify-center shrink-0
                                {{ $isActiveSched ? 'bg-sky-100 border-2 border-sky-300' : 'bg-slate-100 border-2 border-slate-200' }}">
                        <i class="bi bi-calendar-{{ $isActiveSched ? 'check' : 'x' }} text-[0.6rem]
                                  {{ $isActiveSched ? 'text-sky-600' : 'text-slate-400' }}"></i>
                    </div>
                    <span class="text-[0.78rem] font-bold text-slate-700">Jadwal #{{ $sched->round }}</span>
                    @if($isActiveSched)
                        <span class="s-badge badge-scheduled">Aktif</span>
                    @else
                        <span class="text-[0.62rem] font-semibold px-2 py-0.5 rounded-full bg-slate-100 text-slate-400 border border-slate-200">Diarsipkan</span>
                    @endif
                    <span class="text-[0.68rem] text-slate-400 ml-auto">{{ $sched->pic->name ?? '-' }}</span>
                </div>
                <div class="rounded-lg border p-3 {{ $isActiveSched ? 'bg-sky-50 border-sky-100' : 'bg-slate-50 border-slate-100 opacity-75' }}">
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <div class="text-[0.65rem] text-slate-400 font-semibold uppercase tracking-wide mb-0.5">Rencana Mulai</div>
                            <div class="text-[0.8rem] {{ $isActiveSched ? 'font-semibold text-slate-800' : 'text-slate-600' }}">{{ $sched->planned_start->format('d M Y H:i') }}</div>
                        </div>
                        <div>
                            <div class="text-[0.65rem] text-slate-400 font-semibold uppercase tracking-wide mb-0.5">Rencana Selesai</div>
                            <div class="text-[0.8rem] {{ $isActiveSched ? 'font-semibold text-slate-800' : 'text-slate-600' }}">{{ $sched->planned_end->format('d M Y H:i') }}</div>
                        </div>
                        <div>
                            <div class="text-[0.65rem] text-slate-400 font-semibold uppercase tracking-wide mb-0.5">Downtime Est.</div>
                            <div class="text-[0.8rem] text-slate-700">{{ $sched->estimated_downtime_minutes }} menit</div>
                        </div>
                        @if($sched->actual_start)
                        <div>
                            <div class="text-[0.65rem] text-slate-400 font-semibold uppercase tracking-wide mb-0.5">Aktual Mulai</div>
                            <div class="text-[0.8rem] text-slate-700">{{ $sched->actual_start->format('d M Y H:i') }}</div>
                        </div>
                        @endif
                        @if($sched->actual_end)
                        <div>
                            <div class="text-[0.65rem] text-slate-400 font-semibold uppercase tracking-wide mb-0.5">Aktual Selesai</div>
                            <div class="text-[0.8rem] text-slate-700">{{ $sched->actual_end->format('d M Y H:i') }}</div>
                        </div>
                        @endif
                        @if($sched->notes)
                        <div class="col-span-2">
                            <div class="text-[0.65rem] text-slate-400 font-semibold uppercase tracking-wide mb-0.5">Catatan</div>
                            <div class="text-[0.8rem] text-slate-700">{{ $sched->notes }}</div>
                        </div>
                        @endif
                    </div>
                </div>
                @if($isActiveSched && $canEditSchedule)
                <form id="scheduleEditForm" method="POST" action="{{ route('cr.schedule.store', $cr) }}" class="mt-2 hidden">
                    @csrf
                    <input type="hidden" name="pic_id" value="{{ $cr->pic_id }}">
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[0.72rem] font-semibold text-slate-600 mb-1">Mulai *</label>
                            <input type="datetime-local" name="planned_start" required
                                   value="{{ $sched->planned_start->format('Y-m-d\TH:i') }}"
                                   class="w-full text-[0.8rem] border border-slate-200 rounded-lg px-2 py-1.5 focus:outline-none focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-[0.72rem] font-semibold text-slate-600 mb-1">Selesai *</label>
                            <input type="datetime-local" name="planned_end" required
                                   value="{{ $sched->planned_end->format('Y-m-d\TH:i') }}"
                                   class="w-full text-[0.8rem] border border-slate-200 rounded-lg px-2 py-1.5 focus:outline-none focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-[0.72rem] font-semibold text-slate-600 mb-1">Downtime (menit)</label>
                            <input type="number" name="estimated_downtime_minutes" min="0"
                                   value="{{ $sched->estimated_downtime_minutes }}"
                                   class="w-full text-[0.8rem] border border-slate-200 rounded-lg px-2 py-1.5 focus:outline-none focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-[0.72rem] font-semibold text-slate-600 mb-1">Catatan</label>
                            <input type="text" name="notes" value="{{ $sched->notes }}"
                                   class="w-full text-[0.8rem] border border-slate-200 rounded-lg px-2 py-1.5 focus:outline-none focus:border-blue-500">
                        </div>
                    </div>
                    <div class="flex gap-2 mt-2">
                        <button type="submit" onclick="return confirm('Update jadwal?')"
                                class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-[0.78rem] font-semibold
                                       bg-sky-600 text-white hover:bg-sky-700 transition-colors">
                            <i class="bi bi-calendar-check text-xs"></i> Simpan
                        </button>
                        <button type="button" id="cancelScheduleEditBtn"
                                class="px-3 py-2 rounded-lg text-[0.78rem] font-medium border border-slate-200
                                       text-slate-600 hover:bg-slate-50 transition-colors">
                            Batal
                        </button>
                    </div>
                </form>
                @endif
                @endforeach
            @endif
            
            @if(!$cr->schedule)
                @if($canCreateSchedule)
                <form method="POST" action="{{ route('cr.schedule.store', $cr) }}" class="mt-4">
                    @csrf
                    <input type="hidden" name="pic_id" value="{{ $cr->pic_id }}">
                    <div class="grid grid-cols-2 gap-2 mb-2">
                        <div>
                            <label class="block text-[0.72rem] font-semibold text-slate-600 mb-1">Mulai *</label>
                            <input type="datetime-local" name="planned_start" required
                                   class="w-full text-[0.8rem] border border-slate-200 rounded-lg px-2 py-1.5 focus:outline-none focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-[0.72rem] font-semibold text-slate-600 mb-1">Selesai *</label>
                            <input type="datetime-local" name="planned_end" required
                                   class="w-full text-[0.8rem] border border-slate-200 rounded-lg px-2 py-1.5 focus:outline-none focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-[0.72rem] font-semibold text-slate-600 mb-1">Downtime (menit)</label>
                            <input type="number" name="estimated_downtime_minutes" value="0" min="0"
                                   class="w-full text-[0.8rem] border border-slate-200 rounded-lg px-2 py-1.5 focus:outline-none focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-[0.72rem] font-semibold text-slate-600 mb-1">Catatan</label>
                            <input type="text" name="notes"
                                   class="w-full text-[0.8rem] border border-slate-200 rounded-lg px-2 py-1.5 focus:outline-none focus:border-blue-500">
                        </div>
                    </div>
                    <button class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-[0.78rem] font-semibold
                                   bg-sky-600 text-white hover:bg-sky-700 transition-colors">
                        <i class="bi bi-calendar-plus text-xs leading-none"></i> Simpan Jadwal
                    </button>
                </form>
                @else
                    @if($cr->status === 'approved')
                    <p class="text-[0.78rem] text-slate-400 mt-3">Menunggu engineer PIC menetapkan jadwal implementasi.</p>
                    @endif
                @endif
            @endif
        </div>

        {{-- Log Implementasi tab --}}
        @if($cr->implementationLogs->count() > 0 || $cr->status === 'in_progress')
        <div id="tab-impllog" class="hidden">
            @if($cr->status === 'in_progress' && $cr->implementationLogs->count() === 0)
            <div class="flex items-center gap-2 bg-amber-50 border border-amber-100 rounded-lg px-3 py-2.5 text-[0.78rem] text-amber-700 mb-2">
                <i class="bi bi-gear-wide-connected shrink-0"></i>
                Implementasi sedang berjalan sejak <strong class="ml-1">{{ $cr->schedule?->actual_start?->format('d M Y H:i') ?? '-' }}</strong>
            </div>
            @endif

            @foreach($cr->implementationLogs->sortByDesc('created_at') as $i => $log)
            @php
                $lv = match($log->result_status) {
                    'success'  => ['bg'=>'bg-green-50','border'=>'border-green-100','icon'=>'check-circle-fill','ic'=>'text-green-500','badge'=>'badge-completed','label'=>'Berhasil'],
                    'failed'   => ['bg'=>'bg-red-50',  'border'=>'border-red-100',  'icon'=>'x-circle-fill',    'ic'=>'text-red-500',  'badge'=>'badge-failed',   'label'=>'Gagal'],
                    'rollback' => ['bg'=>'bg-purple-50','border'=>'border-purple-100','icon'=>'arrow-counterclockwise','ic'=>'text-purple-500','badge'=>'badge-rollback','label'=>'Rollback'],
                    default    => ['bg'=>'bg-slate-50','border'=>'border-slate-100','icon'=>'journal-text',      'ic'=>'text-slate-400','badge'=>'badge-draft',    'label'=>'-'],
                };
            @endphp
            @if(!$loop->first)<hr class="my-3 border-slate-100">@endif
            <div class="flex items-center gap-2 mb-2">
                <div class="w-6 h-6 rounded-full flex items-center justify-center shrink-0 {{ $lv['bg'] }} border {{ $lv['border'] }}">
                    <i class="bi bi-{{ $lv['icon'] }} {{ $lv['ic'] }} text-[0.6rem]"></i>
                </div>
                <span class="text-[0.78rem] font-bold text-slate-700">Log #{{ $i + 1 }}</span>
                <span class="s-badge {{ $lv['badge'] }}">{{ $lv['label'] }}</span>
                <span class="text-[0.68rem] text-slate-400 ml-auto">{{ $log->implementer->name ?? '-' }}</span>
            </div>
            <div class="rounded-lg border p-3 {{ $lv['bg'] }} {{ $lv['border'] }}">
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <div class="text-[0.65rem] text-slate-400 font-semibold uppercase tracking-wide mb-0.5">Mulai</div>
                        <div class="text-[0.8rem] text-slate-700">{{ $log->actual_start?->format('d M Y H:i') ?? '-' }}</div>
                    </div>
                    <div>
                        <div class="text-[0.65rem] text-slate-400 font-semibold uppercase tracking-wide mb-0.5">Selesai</div>
                        <div class="text-[0.8rem] text-slate-700">{{ $log->actual_end?->format('d M Y H:i') ?? '-' }}</div>
                    </div>
                    @if($log->result_note)
                    <div class="col-span-2">
                        <div class="text-[0.65rem] text-slate-400 font-semibold uppercase tracking-wide mb-0.5">Hasil</div>
                        <div class="text-[0.8rem] text-slate-700">{{ $log->result_note }}</div>
                    </div>
                    @endif
                    @if($log->issues)
                    <div class="col-span-2">
                        <div class="text-[0.65rem] text-red-400 font-semibold uppercase tracking-wide mb-0.5">Kendala</div>
                        <div class="text-[0.8rem] text-red-700">{{ $log->issues }}</div>
                    </div>
                    @endif
                </div>
                @if($log->evidence_file)
                <a href="{{ route('cr.evidence.download', [$cr, $log]) }}"
                   class="inline-flex items-center gap-1.5 mt-2 px-2.5 py-1 rounded-md text-[0.72rem] font-medium
                          border border-slate-200 text-slate-600 hover:border-blue-300 hover:text-blue-600 hover:bg-blue-50 transition-colors">
                    <i class="bi bi-file-earmark-arrow-down text-xs"></i> Unduh Evidence
                </a>
                @endif
            </div>
            @endforeach

            @if($cr->status === 'in_progress' && $canImplement)
            <hr class="my-3 border-slate-100">
            <div class="text-[0.8rem] font-bold text-slate-700 mb-2">Selesaikan Implementasi</div>
            <form method="POST" action="{{ route('cr.implementation.store', $cr) }}" enctype="multipart/form-data">
                @csrf
                <div class="grid grid-cols-2 gap-2 mb-2">
                    <div>
                        <label class="block text-[0.72rem] font-semibold text-slate-600 mb-1">Status Hasil *</label>
                        <select name="result_status" required
                                class="w-full text-[0.8rem] border border-slate-200 rounded-lg px-2 py-1.5
                                       focus:outline-none focus:border-blue-500">
                            <option value="success">Berhasil</option>
                            <option value="failed">Gagal</option>
                            <option value="rollback">Rollback</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[0.72rem] font-semibold text-slate-600 mb-1">Evidence</label>
                        <input type="file" name="evidence_file"
                               class="w-full text-[0.78rem] border border-slate-200 rounded-lg px-2 py-1.5
                                      file:mr-2 file:rounded file:border-0 file:bg-slate-100 file:text-[0.72rem] file:font-medium file:px-2 file:py-0.5">
                    </div>
                    <div class="col-span-2">
                        <label class="block text-[0.72rem] font-semibold text-slate-600 mb-1">Hasil Implementasi *</label>
                        <textarea name="result_note" rows="2" required placeholder="Deskripsikan hasil..."
                                  class="w-full text-[0.8rem] border border-slate-200 rounded-lg px-3 py-1.5 resize-none
                                         focus:outline-none focus:border-blue-500"></textarea>
                    </div>
                    <div>
                        <label class="block text-[0.72rem] font-semibold text-slate-600 mb-1">Kendala</label>
                        <textarea name="issues" rows="2" placeholder="Opsional..."
                                  class="w-full text-[0.8rem] border border-slate-200 rounded-lg px-3 py-1.5 resize-none
                                         focus:outline-none focus:border-blue-500"></textarea>
                    </div>
                    <div>
                        <label class="block text-[0.72rem] font-semibold text-slate-600 mb-1">Catatan Post-Impl.</label>
                        <textarea name="post_review_note" rows="2" placeholder="Opsional..."
                                  class="w-full text-[0.8rem] border border-slate-200 rounded-lg px-3 py-1.5 resize-none
                                         focus:outline-none focus:border-blue-500"></textarea>
                    </div>
                </div>
                <button onclick="return confirm('Selesaikan implementasi? Waktu dicatat sekarang.')"
                        class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-[0.78rem] font-semibold
                               bg-green-600 text-white hover:bg-green-700 transition-colors">
                    <i class="bi bi-journal-plus text-xs"></i> Simpan & Selesaikan
                </button>
            </form>
            @endif
        </div>
        @endif
    </div>
</div>
@endif
