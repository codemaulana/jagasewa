@props([
    'initialToasts' => [],
])

@php
    $flashes = [];
    if (session()->has('success')) {
        $flashes[] = ['id' => uniqid(), 'type' => 'success', 'message' => session('success')];
    }
    if (session()->has('error')) {
        $flashes[] = ['id' => uniqid(), 'type' => 'danger', 'message' => session('error')];
    }
    if (session()->has('warning')) {
        $flashes[] = ['id' => uniqid(), 'type' => 'warning', 'message' => session('warning')];
    }
    if (session()->has('info')) {
        $flashes[] = ['id' => uniqid(), 'type' => 'info', 'message' => session('info')];
    }
@endphp

<div
    x-data="{
        toasts: {{ Js::from(array_merge($flashes, $initialToasts)) }},
        add(message, type = 'success', title = null) {
            const id = Date.now().toString(36) + Math.random().toString(36).substr(2);
            this.toasts.push({ id, message, type, title });
            setTimeout(() => this.remove(id), 4500);
        },
        remove(id) {
            this.toasts = this.toasts.filter(t => t.id !== id);
        }
    }"
    x-on:toast.window="add($event.detail.message, $event.detail.type || 'success', $event.detail.title)"
    x-on:notify.window="add($event.detail.message || $event.detail, $event.detail.type || 'success', $event.detail.title)"
    class="fixed bottom-4 right-4 sm:bottom-6 sm:right-6 z-50 flex flex-col gap-2.5 max-w-sm w-full pointer-events-none px-4 sm:px-0"
    role="status"
    aria-live="polite"
>
    <template x-for="toast in toasts" :key="toast.id">
        <div
            x-show="true"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-3 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
            x-transition:leave-end="opacity-0 translate-y-3 scale-95"
            class="pointer-events-auto p-3.5 rounded-lg border shadow-subtle flex items-start gap-3 bg-surface transition"
            :class="{
                'border-success-border bg-success-bg text-success': toast.type === 'success',
                'border-danger-border bg-danger-bg text-danger': toast.type === 'danger' || toast.type === 'error',
                'border-warning-border bg-warning-bg text-warning': toast.type === 'warning',
                'border-info-border bg-info-bg text-info': toast.type === 'info',
            }"
        >
            <div class="mt-0.5 flex-shrink-0">
                <template x-if="toast.type === 'success'">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </template>
                <template x-if="toast.type === 'danger' || toast.type === 'error'">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </template>
                <template x-if="toast.type === 'warning'">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                    </svg>
                </template>
                <template x-if="toast.type === 'info'">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                    </svg>
                </template>
            </div>

            <div class="flex-1 min-w-0">
                <template x-if="toast.title">
                    <p class="text-xs font-bold leading-tight mb-0.5" x-text="toast.title"></p>
                </template>
                <p class="text-xs font-medium leading-relaxed text-text-main" x-text="toast.message"></p>
            </div>

            <button
                type="button"
                x-on:click="remove(toast.id)"
                class="p-1 rounded-md text-text-muted hover:text-text-main transition -mr-1 -mt-1 flex-shrink-0 min-h-[32px] min-w-[32px] flex items-center justify-center"
                aria-label="Tutup notifikasi"
            >
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </template>
</div>
