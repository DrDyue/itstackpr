@props([
    'name',
    'value' => '',
    'label' => null,
    'placeholder' => 'dd.mm.gggg',
])

@php
    $inputValue = (string) $value;
@endphp

<label class="block">
    @if ($label)
        <span class="crud-label">{{ $label }}</span>
    @endif

    <div
        x-data="localizedDatePicker({ value: @js($inputValue) })"
        class="localized-date-picker"
        @keydown.escape.window="open = false"
    >
        <input type="hidden" name="{{ $name }}" x-model="value">

        <button
            type="button"
            class="localized-date-trigger"
            @click="toggle()"
        >
            <span class="localized-date-trigger-value" :class="displayValue ? 'text-slate-700' : 'text-slate-400'">
                <span x-text="displayValue || @js($placeholder)"></span>
            </span>
            <span class="localized-date-trigger-icon">
                <x-icon name="calendar" size="h-4 w-4" />
            </span>
        </button>

        <div
            x-cloak
            x-show="open"
            x-transition.origin.top.left
            class="localized-date-panel"
            @click.outside="open = false"
        >
            <div class="localized-date-header">
                <button type="button" class="localized-date-nav" @click="previousMonth()">&#8249;</button>
                <div class="localized-date-title" x-text="monthLabel"></div>
                <button type="button" class="localized-date-nav" @click="nextMonth()">&#8250;</button>
            </div>

            <div class="localized-date-weekdays">
                <template x-for="day in weekdays" :key="day">
                    <div class="localized-date-weekday" x-text="day"></div>
                </template>
            </div>

            <div class="localized-date-grid">
                <template x-for="day in days" :key="day.key">
                    <button
                        type="button"
                        class="localized-date-cell"
                        :class="[
                            day.isCurrentMonth ? 'localized-date-cell-current' : 'localized-date-cell-muted',
                            day.isSelected ? 'localized-date-cell-selected' : '',
                        ]"
                        @click="select(day.value)"
                    >
                        <span x-text="day.label"></span>
                    </button>
                </template>
            </div>

            <div class="localized-date-actions">
                <button type="button" class="btn-clear" @click="clear()">Notirit</button>
                <button type="button" class="btn-view" @click="select(toIso(new Date()))">Sodien</button>
                <button type="button" class="btn-back" @click="open = false">Aizvert</button>
            </div>
        </div>
    </div>
</label>
