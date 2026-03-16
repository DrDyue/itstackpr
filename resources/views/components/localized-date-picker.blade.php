@props([
    'name' => null,
    'value' => '',
    'label' => '',
    'labelClass' => 'user-filter-label',
    'required' => false,
    'wrapperClass' => 'block',
    'buttonClass' => 'crud-control flex w-full items-center justify-between rounded-2xl border border-slate-200 bg-white px-4 py-3 text-left shadow-sm',
    'placeholder' => 'dd.mm.gggg',
    'xModel' => null,
])

<label {{ $attributes->merge(['class' => $wrapperClass]) }}>
    @if ($label !== '')
        <span class="{{ $labelClass }}">{{ $label }}</span>
    @endif

    <div
        class="relative"
        x-data="localizedDatePicker({ value: @js($value) })"
        @if ($xModel) x-modelable="value" x-model="{{ $xModel }}" @endif
    >
        @if ($name)
            <input type="hidden" name="{{ $name }}" x-model="value" @if($required) required @endif>
        @endif

        <button type="button" class="{{ $buttonClass }}" @click="toggle()">
            <span :class="displayValue ? 'text-slate-900' : 'text-slate-400'" x-text="displayValue || @js($placeholder)"></span>
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 3.75v1.5m7.5-1.5v1.5M3.75 8.25h16.5M4.5 6h15a.75.75 0 0 1 .75.75v12.75a.75.75 0 0 1-.75.75h-15a.75.75 0 0 1-.75-.75V6.75A.75.75 0 0 1 4.5 6Z"/>
            </svg>
        </button>

        <div x-cloak x-show="open" @click.outside="open = false" class="absolute left-0 top-full z-20 mt-2 w-80 rounded-[1.5rem] border border-slate-200 bg-white p-4 shadow-xl">
            <div class="mb-3 flex items-center justify-between">
                <button type="button" class="rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-600 hover:bg-slate-50" @click="previousMonth()">&larr;</button>
                <div class="text-sm font-semibold text-slate-900" x-text="monthLabel"></div>
                <button type="button" class="rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-600 hover:bg-slate-50" @click="nextMonth()">&rarr;</button>
            </div>

            <div class="mb-2 grid grid-cols-7 gap-1 text-center text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">
                <template x-for="weekday in weekdays" :key="weekday">
                    <span x-text="weekday"></span>
                </template>
            </div>

            <div class="grid grid-cols-7 gap-1">
                <template x-for="day in days" :key="day.key">
                    <button
                        type="button"
                        class="flex h-10 items-center justify-center rounded-xl text-sm transition"
                        :class="day.isCurrentMonth
                            ? (day.isSelected ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100')
                            : 'text-slate-300'"
                        :disabled="!day.isCurrentMonth"
                        @click="select(day.value)"
                        x-text="day.label"
                    ></button>
                </template>
            </div>

            <div class="mt-3 flex items-center justify-between gap-3">
                <button type="button" class="text-sm text-slate-500 hover:text-slate-700" @click="clear()">Notirit datumu</button>
                <button type="button" class="rounded-xl bg-slate-900 px-3 py-2 text-sm font-semibold text-white hover:bg-slate-800" @click="open = false">Aizvert</button>
            </div>
        </div>
    </div>
</label>
