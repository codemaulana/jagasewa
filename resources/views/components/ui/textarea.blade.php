@props([
    'label' => null,
    'name' => null,
    'rows' => 3,
    'required' => false,
    'error' => null,
    'hint' => null,
    'id' => null,
    'disabled' => false,
    'placeholder' => null,
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

    <textarea 
        id="{{ $id }}"
        name="{{ $name }}"
        rows="{{ $rows }}"
        @disabled($disabled)
        @required($required)
        placeholder="{{ $placeholder }}"
        {{ $attributes->merge([
            'class' => 'w-full text-sm bg-white text-slate-900 rounded-lg border p-3 leading-relaxed transition duration-150 shadow-2xs ' .
                ($hasError 
                    ? 'border-rose-300 text-rose-900 focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20 ' 
                    : 'border-slate-200 hover:border-slate-300 focus:border-blue-600 focus:ring-2 focus:ring-blue-500/20 ') .
                'placeholder:text-slate-400 focus:outline-none disabled:bg-slate-50 disabled:text-slate-400 disabled:cursor-not-allowed resize-y'
        ]) }}
        @if($hasError) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif
    >{{ $slot }}</textarea>

    @if($hasError)
        <p id="{{ $id }}-error" class="text-xs font-medium text-rose-600 flex items-center gap-1 mt-1" role="alert">
            <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <span>{{ $errorMessage }}</span>
        </p>
    @elseif($hint)
        <p class="text-xs text-slate-500 mt-1">{{ $hint }}</p>
    @endif
</div>
