@props([
    'label' => null,
    'name' => null,
    'type' => 'text',
    'required' => false,
    'error' => null,
    'hint' => null,
    'id' => null,
    'disabled' => false,
    'prefix' => null,
    'suffix' => null,
])

@php
    $id = $id ?? $name ?? \Illuminate\Support\Str::random(8);
    $hasError = $error || ($name && $errors->has($name));
    $errorMessage = $error ?? ($name ? $errors->first($name) : null);
@endphp

<div class="space-y-1.5 w-full">
    @if($label)
        <label for="{{ $id }}" class="block text-xs font-semibold text-slate-800">
            {{ $label }}
            @if($required)
                <span class="text-rose-500 ml-0.5" aria-hidden="true">*</span>
            @endif
        </label>
    @endif

    <div class="relative rounded-lg">
        @if($prefix)
            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 text-xs">
                {{ $prefix }}
            </div>
        @endif

        <input 
            type="{{ $type }}" 
            id="{{ $id }}"
            name="{{ $name }}"
            @disabled($disabled)
            @required($required)
            {{ $attributes->merge([
                'class' => 'w-full h-11 text-sm bg-white text-slate-900 rounded-lg border transition duration-150 shadow-2xs ' .
                    ($prefix ? 'pl-10 ' : 'pl-3.5 ') .
                    ($suffix ? 'pr-10 ' : 'pr-3.5 ') .
                    ($hasError 
                        ? 'border-rose-300 text-rose-900 focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20 ' 
                        : 'border-slate-200 hover:border-slate-300 focus:border-blue-600 focus:ring-2 focus:ring-blue-500/20 ') .
                    'placeholder:text-slate-400 focus:outline-none disabled:bg-slate-50 disabled:text-slate-400 disabled:cursor-not-allowed'
            ]) }}
            @if($hasError) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif
        >

        @if($suffix)
            <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 text-xs">
                {{ $suffix }}
            </div>
        @endif
    </div>

    @if($hasError)
        <p id="{{ $id }}-error" class="text-xs font-medium text-rose-600 flex items-center gap-1 mt-1">
            <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <span>{{ $errorMessage }}</span>
        </p>
    @elseif($hint)
        <p class="text-xs text-slate-500 mt-1">{{ $hint }}</p>
    @endif
</div>
