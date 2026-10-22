@props([
    'name' => 'photo',
    'label' => 'Unggah Foto',
    'hint' => 'Format JPG, PNG, atau WEBP (maksimal 5 MB per berkas).',
    'multiple' => false,
    'maxFiles' => 5,
    'required' => false,
    'error' => null,
    'existingPhotos' => [],
])

@php
    $id = $name . '_' . Str::random(6);
    $hasError = $error || ($name && $errors->has($name));
    $errorMessage = $error ?? ($name ? $errors->first($name) : null);
@endphp

<div 
    x-data="{
        previews: {{ Js::from(is_array($existingPhotos) ? $existingPhotos : [$existingPhotos]) }}.filter(Boolean),
        isDragging: false,
        errorMsg: '',
        handleFiles(files) {
            this.errorMsg = '';
            const validTypes = ['image/jpeg', 'image/png', 'image/webp'];
            const maxSize = 5 * 1024 * 1024; // 5MB

            Array.from(files).forEach(file => {
                if (!validTypes.includes(file.type)) {
                    this.errorMsg = 'Format berkas ' + file.name + ' tidak didukung (harus JPG, PNG, atau WEBP).';
                    return;
                }
                if (file.size > maxSize) {
                    this.errorMsg = 'Ukuran berkas ' + file.name + ' melebihi batas 5 MB.';
                    return;
                }
                @if(!$multiple)
                    this.previews = [];
                @endif
                @if($multiple)
                    if (this.previews.length >= {{ $maxFiles }}) {
                        this.errorMsg = 'Batas maksimal {{ $maxFiles }} foto telah tercapai.';
                        return;
                    }
                @endif
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.previews.push(e.target.result);
                };
                reader.readAsDataURL(file);
            });
        },
        removePreview(index) {
            this.previews.splice(index, 1);
        }
    }"
    class="space-y-2 w-full"
>
    @if($label)
        <label class="block text-xs font-semibold text-text-main">
            {{ $label }}
            @if($required)
                <span class="text-danger ml-0.5" aria-hidden="true">*</span>
            @endif
        </label>
    @endif

    <!-- Dropzone Area -->
    <div
        x-on:dragover.prevent="isDragging = true"
        x-on:dragleave.prevent="isDragging = false"
        x-on:drop.prevent="isDragging = false; handleFiles($event.dataTransfer.files)"
        x-on:click="$refs.fileInput.click()"
        :class="{
            'border-primary bg-accent-lime-subtle/30': isDragging,
            'border-danger': '{{ $hasError }}',
            'border-border-subtle hover:border-border-hover bg-surface-subtle/40': !isDragging && !'{{ $hasError }}'
        }"
        class="border-2 border-dashed rounded-lg p-5 text-center cursor-pointer transition select-none flex flex-col items-center justify-center gap-2"
    >
        <input 
            type="file" 
            x-ref="fileInput"
            name="{{ $multiple ? $name . '[]' : $name }}"
            id="{{ $id }}"
            accept="image/jpeg,image/png,image/webp"
            @if($multiple) multiple @endif
            @disabled($attributes->get('disabled'))
            class="hidden"
            x-on:change="handleFiles($event.target.files)"
            {{ $attributes->except(['class', 'disabled']) }}
        >

        <div class="w-10 h-10 rounded-full bg-surface border border-border-subtle flex items-center justify-center text-text-secondary shadow-subtle">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
            </svg>
        </div>

        <div>
            <p class="text-xs font-semibold text-text-main">
                <span class="underline underline-offset-2">Klik untuk mengunggah</span> atau seret foto ke sini
            </p>
            <p class="text-[11px] text-text-secondary mt-0.5">{{ $hint }}</p>
        </div>
    </div>

    <!-- Error message display -->
    <template x-if="errorMsg">
        <p class="text-xs text-danger font-medium flex items-center gap-1.5 mt-1" role="alert">
            <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
            </svg>
            <span x-text="errorMsg"></span>
        </p>
    </template>

    @if($hasError)
        <p class="text-xs text-danger font-medium flex items-center gap-1.5 mt-1" role="alert">
            <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
            </svg>
            <span>{{ $errorMessage }}</span>
        </p>
    @endif

    <!-- Image Previews Grid -->
    <div x-show="previews.length > 0" class="pt-2">
        <p class="text-xs font-semibold text-text-secondary mb-2">Pratinjau Foto (<span x-text="previews.length"></span>):</p>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <template x-for="(preview, index) in previews" :key="index">
                <div class="relative group rounded-lg overflow-hidden border border-border-subtle bg-surface aspect-square shadow-subtle">
                    <img :src="preview" alt="Pratinjau foto" class="w-full h-full object-cover">
                    
                    <template x-if="index === 0">
                        <span class="absolute top-1.5 left-1.5 px-2 py-0.5 rounded-full text-[10px] font-bold bg-primary text-white shadow-subtle">
                            Utama
                        </span>
                    </template>

                    <button
                        type="button"
                        x-on:click.stop="removePreview(index)"
                        class="absolute top-1.5 right-1.5 p-1 rounded-full bg-text-main/70 text-white hover:bg-danger transition min-h-[32px] min-w-[32px] flex items-center justify-center opacity-90 hover:opacity-100"
                        title="Hapus foto"
                        aria-label="Hapus foto"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                        </svg>
                    </button>
                </div>
            </template>
        </div>
    </div>
</div>
