@props([
    'headers' => [],
    'empty' => false,
    'emptyTitle' => 'Tidak ada data ditemukan',
    'emptyDescription' => 'Belum ada data yang tercatat untuk tampilan ini.',
])

<div {{ $attributes->merge(['class' => 'w-full']) }}>
    @if($empty)
        <div class="bg-surface rounded-lg border border-border-subtle p-8 text-center shadow-subtle">
            <div class="w-12 h-12 mx-auto rounded-full bg-surface-subtle border border-border-subtle flex items-center justify-center text-text-muted mb-3">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m8.25 3v6.75m0 0l-3-3m3 3l3-3M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                </svg>
            </div>
            <h4 class="text-sm font-semibold text-text-main">{{ $emptyTitle }}</h4>
            <p class="text-xs text-text-secondary max-w-sm mx-auto mt-1">{{ $emptyDescription }}</p>
        </div>
    @else
        <!-- Desktop / Tablet Table View (hidden on small screens if mobileCards slot provided) -->
        <div class="{{ isset($mobileCards) ? 'hidden md:block' : 'block' }}">
            <div class="overflow-x-auto rounded-lg border border-border-subtle bg-surface shadow-subtle">
                <table class="w-full text-left border-collapse text-sm">
                    @if(!empty($headers))
                        <thead class="bg-surface-subtle border-b border-border-subtle">
                            <tr>
                                @foreach($headers as $header)
                                    <th scope="col" class="py-3 px-4 text-xs font-semibold uppercase tracking-wider text-text-secondary">
                                        {{ $header }}
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                    @endif
                    <tbody class="divide-y divide-border-subtle">
                        {{ $slot }}
                    </tbody>
                </table>
            </div>
        </div>

        @if(isset($mobileCards))
            <!-- Mobile Responsive Card View (visible only on mobile) -->
            <div class="md:hidden space-y-3">
                {{ $mobileCards }}
            </div>
        @endif
    @endif
</div>
