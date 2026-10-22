@props([
    'label' => null,
    'name' => null,
    'options' => [],
    'selected' => null,
    'required' => false,
    'error' => null,
    'hint' => null,
    'id' => null,
    'disabled' => false,
    'placeholder' => null,
])

@php
    $id = $id ?? $name ?? Str::random(8);
    $hasError = $error || ($name && $errors->has($name));
    $errorMessage = $error ?? ($name ? $errors->first($name) : null);
@endphp

<div class="space-y-1.5 w-full">
    @if($label)
        <label for="{{ $id }}" class="block text-xs font-semibold text-text-main">
            {{ $label }}
            @if($required)
                <span class="text-danger ml-0.5" aria-hidden="true">*</span>
            @endif
        </label>
    @endif

    <div class="relative rounded-lg">
        <select 
            id="{{ $id }}"
            name="{{ $name }}"
            @disabled($disabled)
            @required($required)
            {{ $attributes->merge([
                'class' => 'w-full h-11 text-sm bg-surface text-text-main rounded-lg border pl-3.5 pr-10 appearance-none transition shadow-subtle ' .
                    ($hasError 
                        ? 'border-danger focus:border-danger focus:ring-1 focus:ring-danger ' 
                        : 'border-border-subtle hover:border-border-hover focus:border-primary focus:ring-1 focus:ring-primary ') .
                    'focus:outline-none disabled:bg-surface-subtle disabled:text-text-muted disabled:cursor-not-allowed cursor-pointer'
            ]) }}
            @if($hasError) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif
        >
            @if($placeholder)
                <option value="" disabled {{ $selected === null ? 'selected' : '' }}>{{ $placeholder }}</option>
            @endif

            @if(!empty($options))
                @foreach($options as $val => $text)
                    <option value="{{ $val }}" {{ (string)$selected === (string)$val ? 'selected' : '' }}>
                        {{ $text }}
                    </option>
                @endforeach
            @else
                {{ $slot }}
            @endif
        </select>

        <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none text-text-secondary">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
            </svg>
        </div>
    </div>

    @if($hasError)
        <p id="{{ $id }}-error" class="text-xs text-danger font-medium flex items-center gap-1.5 mt-1" role="alert">
            <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
            </svg>
            <span>{{ $errorMessage }}</span>
        </p>
    @elseif($hint)
        <p class="text-[11px] text-text-secondary leading-normal mt-1">{{ $hint }}</p>
    @endif
</div>
