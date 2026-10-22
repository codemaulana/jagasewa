@props([
    'title',
    'description' => null,
    'breadcrumbs' => [],
])

<div {{ $attributes->merge(['class' => 'pb-5 border-b border-border-subtle flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3']) }}>
    <div class="space-y-1">
        @if(!empty($breadcrumbs))
            <nav class="flex items-center gap-1.5 text-xs text-text-muted mb-1" aria-label="Breadcrumb">
                @foreach($breadcrumbs as $bc)
                    @if(isset($bc['url']))
                        <a href="{{ $bc['url'] }}" class="hover:text-text-main transition">{{ $bc['label'] }}</a>
                        <span class="text-border-hover">/</span>
                    @else
                        <span class="text-text-secondary font-medium">{{ $bc['label'] }}</span>
                    @endif
                @endforeach
            </nav>
        @endif

        <h1 class="text-xl sm:text-2xl font-bold text-text-main tracking-tight leading-tight">
            {{ $title }}
        </h1>

        @if($description)
            <p class="text-xs sm:text-sm text-text-secondary leading-relaxed max-w-2xl">
                {{ $description }}
            </p>
        @endif
    </div>

    @if($slot->isNotEmpty())
        <div class="flex items-center gap-2.5 flex-wrap sm:flex-nowrap flex-shrink-0">
            {{ $slot }}
        </div>
    @endif
</div>
