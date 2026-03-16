@php
    $fieldName = $name;
    $selectedValue = isset($selected) ? (string) $selected : '';
    $placeholderText = $placeholder ?? 'Izvelies vertibu';
    $optionsPayload = collect($options ?? [])
        ->map(function ($value) use ($labels, $icons, $classes, $descriptions) {
            $value = (string) $value;

            return [
                'value' => $value,
                'label' => $labels[$value] ?? $value,
                'icon' => view('repairs.partials.icon', ['name' => $icons[$value] ?? 'bars', 'class' => 'h-4 w-4'])->render(),
                'toneClass' => $classes[$value] ?? 'bg-slate-100 text-slate-700 ring-slate-200',
                'description' => $descriptions[$value] ?? '',
            ];
        })
        ->values()
        ->all();
    $defaultOption = $optionsPayload[0] ?? [
        'value' => '',
        'label' => $placeholderText,
        'icon' => view('repairs.partials.icon', ['name' => 'bars', 'class' => 'h-4 w-4'])->render(),
        'toneClass' => 'bg-slate-100 text-slate-700 ring-slate-200',
        'description' => '',
    ];
    $syncExpression = trim((string) ($syncModel ?? ''));
@endphp

<div
    x-data="{
        open: false,
        selected: @js($selectedValue),
        options: @js($optionsPayload),
        fallback: @js($defaultOption),
        optionFor(value) {
            return this.options.find((option) => option.value === String(value)) ?? this.fallback;
        },
        choose(option) {
            this.selected = option.value;
            @if ($syncExpression !== '')
                {{ $syncExpression }} = option.value;
            @endif
            this.open = false;
        },
    }"
    class="relative"
    @keydown.escape.window="open = false"
>
    <input type="hidden" name="{{ $fieldName }}" x-model="selected">

    <button
        type="button"
        class="flex min-h-[4.5rem] w-full items-center justify-between gap-3 rounded-[1.65rem] border border-slate-300 bg-white px-4 py-3 text-left shadow-sm transition hover:border-slate-400 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-sky-200 focus:ring-offset-2"
        @click="open = !open"
        :aria-expanded="open ? 'true' : 'false'"
        :class="open ? 'border-sky-300 ring-2 ring-sky-100 bg-sky-50/50' : ''"
    >
        <span class="flex min-w-0 items-center gap-3">
            <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl ring-1" :class="optionFor(selected).toneClass">
                <span x-html="optionFor(selected).icon"></span>
            </span>
            <span class="min-w-0">
                <span class="block truncate text-sm font-semibold text-slate-900" x-text="optionFor(selected).label"></span>
                <span class="mt-1 block truncate text-xs text-slate-500" x-text="optionFor(selected).description || @js($placeholderText)"></span>
            </span>
        </span>
        <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-2xl border border-slate-200 bg-slate-50 text-slate-500 transition" :class="open ? 'border-sky-200 bg-white text-sky-700' : ''">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/>
            </svg>
        </span>
    </button>

    <div
        x-cloak
        x-show="open"
        x-transition.origin.top.left
        class="absolute z-30 mt-2 w-full overflow-hidden rounded-[1.65rem] border border-slate-200 bg-white shadow-xl ring-1 ring-slate-100"
        @click.outside="open = false"
    >
        <div class="max-h-80 overflow-y-auto p-2">
            <template x-for="option in options" :key="option.value">
                <button
                    type="button"
                    class="flex w-full items-start gap-3 rounded-2xl px-3 py-3 text-left transition"
                    :class="selected === option.value ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-50'"
                    @click="choose(option)"
                >
                    <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full ring-1" :class="selected === option.value ? 'bg-white/15 text-white ring-white/20' : option.toneClass">
                        <span x-html="option.icon"></span>
                    </span>
                    <span class="min-w-0">
                        <span class="block text-sm font-semibold" x-text="option.label"></span>
                        <span class="mt-1 block text-xs" :class="selected === option.value ? 'text-white/75' : 'text-slate-500'" x-text="option.description"></span>
                    </span>
                </button>
            </template>
        </div>
    </div>
</div>
