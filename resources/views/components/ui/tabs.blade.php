@props([
    'tabs' => [],
    'active' => null,
    'variant' => 'underline', // 'underline' or 'pills'
])

<div {{ $attributes->merge(['class' => 'w-full']) }}>
    @if($variant === 'pills')
        <div class="inline-flex p-1 bg-surface-subtle rounded-lg border border-border-subtle max-w-full overflow-x-auto gap-1">
            @foreach($tabs as $key => $tab)
                @php
                    $label = is_array($tab) ? ($tab['label'] ?? $key) : $tab;
                    $count = is_array($tab) ? ($tab['count'] ?? null) : null;
                    $isActive = (string)$active === (string)$key;
                @endphp
                <button
                    type="button"
                    {{ is_array($tab) && isset($tab['action']) ? $tab['action'] : '' }}
                    class="min-h-[38px] px-3.5 py-1.5 text-xs font-semibold rounded-md transition select-none flex items-center gap-1.5 whitespace-nowrap {{
                        $isActive 
                            ? 'bg-surface text-text-main shadow-subtle border border-border-subtle' 
                            : 'text-text-secondary hover:text-text-main'
                    }}"
                >
                    <span>{{ $label }}</span>
                    @if($count !== null)
                        <span class="px-1.5 py-0.5 rounded-full text-[10px] tabular-nums font-bold {{ $isActive ? 'bg-primary text-white' : 'bg-surface text-text-secondary border border-border-subtle' }}">
                            {{ $count }}
                        </span>
                    @endif
                </button>
            @endforeach
            {{ $slot }}
        </div>
    @else
        <!-- Underline Tabs (Standard SaaS layout) -->
        <div class="border-b border-border-subtle overflow-x-auto flex items-center gap-6">
            @foreach($tabs as $key => $tab)
                @php
                    $label = is_array($tab) ? ($tab['label'] ?? $key) : $tab;
                    $count = is_array($tab) ? ($tab['count'] ?? null) : null;
                    $isActive = (string)$active === (string)$key;
                @endphp
                <button
                    type="button"
                    {{ is_array($tab) && isset($tab['action']) ? $tab['action'] : '' }}
                    class="min-h-[44px] py-3 text-xs sm:text-sm font-semibold border-b-2 transition select-none flex items-center gap-2 whitespace-nowrap {{
                        $isActive 
                            ? 'border-primary text-text-main' 
                            : 'border-transparent text-text-secondary hover:text-text-main hover:border-border-hover'
                    }}"
                >
                    <span>{{ $label }}</span>
                    @if($count !== null)
                        <span class="px-1.5 py-0.5 rounded-full text-[10px] tabular-nums font-bold {{ $isActive ? 'bg-primary text-white' : 'bg-surface-subtle text-text-secondary border border-border-subtle' }}">
                            {{ $count }}
                        </span>
                    @endif
                </button>
            @endforeach
            {{ $slot }}
        </div>
    @endif
</div>
