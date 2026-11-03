@props([
    'title',
    'value',
    'subtitle' => null,
    'trend' => null, // e.g. "+12%" or "Hari ini"
    'trendType' => 'neutral', // up, down, neutral, warning
    'icon' => null,
])

<div {{ $attributes->merge(['class' => 'bg-white border border-slate-200/90 rounded-xl p-5 shadow-2xs flex flex-col justify-between']) }}>
    <div class="flex items-start justify-between gap-3">
        <div>
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block">{{ $title }}</span>
            <div class="text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight mt-1.5 tabular-nums">
                {{ $value }}
            </div>
        </div>
        @if(isset($icon))
            <div class="p-2.5 rounded-lg bg-slate-50 border border-slate-100 text-slate-700 flex-shrink-0">
                {{ $icon }}
            </div>
        @endif
    </div>

    @if($subtitle || $trend)
        <div class="mt-3.5 pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
            @if($subtitle)
                <span>{{ $subtitle }}</span>
            @endif
            @if($trend)
                @php
                    $trendBadge = match($trendType) {
                        'up' => 'bg-emerald-50 text-emerald-700 border-emerald-200/60',
                        'down' => 'bg-rose-50 text-rose-700 border-rose-200/60',
                        'warning' => 'bg-amber-50 text-amber-700 border-amber-200/60',
                        default => 'bg-slate-100 text-slate-600 border-slate-200/60',
                    };
                @endphp
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold border {{ $trendBadge }}">
                    {{ $trend }}
                </span>
            @endif
        </div>
    @endif
</div>
