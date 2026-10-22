@props([
    'name' => null,
    'show' => false,
    'title' => null,
    'description' => null,
    'variant' => 'modal', // 'modal' or 'drawer'
    'maxWidth' => 'lg', // sm, md, lg, xl, 2xl
    'focusable' => true,
])

@php
    $maxWidthClass = match($maxWidth) {
        'sm' => 'sm:max-w-sm',
        'md' => 'sm:max-w-md',
        'xl' => 'sm:max-w-xl',
        '2xl' => 'sm:max-w-2xl',
        default => 'sm:max-w-lg',
    };
@endphp

<div
    x-data="{
        show: @js($show),
        focusables() {
            let selector = 'a, button, input:not([type=\'hidden\']), textarea, select, details, [tabindex]:not([tabindex=\'-1\'])';
            return [...$el.querySelectorAll(selector)].filter(el => ! el.hasAttribute('disabled'));
        },
        firstFocusable() { return this.focusables()[0] },
        lastFocusable() { return this.focusables().slice(-1)[0] },
        nextFocusable() { return this.focusables()[this.nextFocusableIndex()] || this.firstFocusable() },
        prevFocusable() { return this.focusables()[this.prevFocusableIndex()] || this.lastFocusable() },
        nextFocusableIndex() { return (this.focusables().indexOf(document.activeElement) + 1) % (this.focusables().length + 1) },
        prevFocusableIndex() { return (this.focusables().indexOf(document.activeElement) - 1 + this.focusables().length) % (this.focusables().length) },
    }"
    @if($name)
        x-on:open-modal.window="$event.detail == '{{ $name }}' ? show = true : null"
        x-on:close-modal.window="$event.detail == '{{ $name }}' ? show = false : null"
        x-on:close.stop="show = false"
    @endif
    x-on:keydown.escape.window="show = false"
    x-on:keydown.tab.prevent="$event.shiftKey ? prevFocusable().focus() : nextFocusable().focus()"
    x-show="show"
    class="fixed inset-0 overflow-y-auto px-4 py-6 sm:px-0 z-50 flex items-center justify-center"
    style="display: none;"
    role="dialog"
    aria-modal="true"
>
    <!-- Backdrop Overlay -->
    <div
        x-show="show"
        x-transition:enter="ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 transform transition-all bg-text-main/40 backdrop-blur-xs"
        x-on:click="show = false"
    ></div>

    @if($variant === 'drawer')
        <!-- Slide-over Drawer (Bottom sheet on mobile, right panel on desktop) -->
        <div
            x-show="show"
            x-transition:enter="transform transition ease-in-out duration-300"
            x-transition:enter-start="translate-y-full md:translate-y-0 md:translate-x-full"
            x-transition:enter-end="translate-y-0 md:translate-x-0"
            x-transition:leave="transform transition ease-in-out duration-200"
            x-transition:leave-start="translate-y-0 md:translate-x-0"
            x-transition:leave-end="translate-y-full md:translate-y-0 md:translate-x-full"
            class="fixed inset-x-0 bottom-0 md:inset-y-0 md:right-0 md:left-auto md:w-full md:max-w-md bg-surface border-t md:border-t-0 md:border-l border-border-subtle shadow-subtle rounded-t-2xl md:rounded-none z-50 flex flex-col max-h-[90vh] md:max-h-full"
        >
            <div class="p-5 border-b border-border-subtle flex items-center justify-between">
                <div>
                    @if($title)
                        <h3 class="text-base font-bold text-text-main">{{ $title }}</h3>
                    @endif
                    @if($description)
                        <p class="text-xs text-text-secondary mt-0.5">{{ $description }}</p>
                    @endif
                </div>
                <button 
                    type="button" 
                    x-on:click="show = false" 
                    class="p-2 rounded-lg text-text-secondary hover:text-text-main hover:bg-surface-subtle transition min-h-[44px] min-w-[44px] flex items-center justify-center"
                    aria-label="Tutup"
                >
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="p-5 overflow-y-auto flex-1">
                {{ $slot }}
            </div>

            @if(isset($footer))
                <div class="p-4 border-t border-border-subtle bg-surface-subtle/50 flex items-center justify-end gap-2.5">
                    {{ $footer }}
                </div>
            @endif
        </div>
    @else
        <!-- Standard Centered Modal -->
        <div
            x-show="show"
            x-transition:enter="ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            class="bg-surface rounded-lg border border-border-subtle shadow-subtle overflow-hidden transform transition-all w-full {{ $maxWidthClass }} my-auto z-50"
        >
            @if($title || $description)
                <div class="px-5 pt-5 pb-3 flex items-start justify-between border-b border-border-subtle">
                    <div>
                        @if($title)
                            <h3 class="text-base font-bold text-text-main">{{ $title }}</h3>
                        @endif
                        @if($description)
                            <p class="text-xs text-text-secondary mt-0.5 leading-relaxed">{{ $description }}</p>
                        @endif
                    </div>
                    <button 
                        type="button" 
                        x-on:click="show = false" 
                        class="p-1.5 rounded-lg text-text-secondary hover:text-text-main hover:bg-surface-subtle transition -mr-1.5 min-h-[44px] min-w-[44px] flex items-center justify-center"
                        aria-label="Tutup dialog"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            @endif

            <div class="p-5">
                {{ $slot }}
            </div>

            @if(isset($footer))
                <div class="px-5 py-3.5 bg-surface-subtle/60 border-t border-border-subtle flex items-center justify-end gap-2.5">
                    {{ $footer }}
                </div>
            @endif
        </div>
    @endif
</div>
