{{-- Datuma izvēles komponents ar tekstlauku un kopīgu kalendāra loģiku. --}}
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

@php
    $fallbackText = filled($value)
        ? \Illuminate\Support\Carbon::parse($value)->format('d.m.Y')
        : $placeholder;
@endphp

<label {{ $attributes->merge(['class' => $wrapperClass]) }}>
    @if ($label !== '')
        <span class="{{ $labelClass }}">{{ $label }}</span>
    @endif

    <div
        class="localized-date-picker"
        x-data="localizedDatePicker({ value: @js($value) })"
        @if ($xModel) x-modelable="value" x-model="{{ $xModel }}" @endif
        @keydown.escape.window="open = false"
    >
        @if ($name)
            <input type="hidden" name="{{ $name }}" x-model="value" @if($required) required @endif>
        @endif

        <button type="button" class="{{ $buttonClass }}" @click="toggle()">
            <span :class="displayValue ? 'text-slate-900' : 'text-slate-400'" x-text="displayValue || @js($placeholder)">{{ $fallbackText }}</span>
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 3.75v1.5m7.5-1.5v1.5M3.75 8.25h16.5M4.5 6h15a.75.75 0 0 1 .75.75v12.75a.75.75 0 0 1-.75.75h-15a.75.75 0 0 1-.75-.75V6.75A.75.75 0 0 1 4.5 6Z"/>
            </svg>
        </button>

        <div x-cloak x-show="open" x-transition.origin.top.left @click.outside="open = false" class="localized-date-panel">
            <div class="localized-date-header">
                <button type="button" class="localized-date-nav" @click="mode === 'years' ? previousYearPage() : previousMonth()" aria-label="Iepriekšējais periods">&#8249;</button>
                <div class="localized-date-heading">
                    <button type="button" class="localized-date-current-button" @click="showMonths()" x-text="months[viewDate.getMonth()]"></button>
                    <button type="button" class="localized-date-current-button" @click="showYears()" x-text="viewDate.getFullYear()"></button>
                    <span class="localized-date-year-range" x-show="mode === 'years'" x-text="yearRangeLabel"></span>
                </div>
                <button type="button" class="localized-date-nav" @click="mode === 'years' ? nextYearPage() : nextMonth()" aria-label="Nākamais periods">&#8250;</button>
            </div>

            <div x-show="mode === 'days'">
                <div class="localized-date-weekdays">
                    <template x-for="weekday in weekdays" :key="weekday">
                        <span class="localized-date-weekday" x-text="weekday"></span>
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
                            x-text="day.label"
                        ></button>
                    </template>
                </div>
            </div>

            <div x-show="mode === 'months'" class="localized-date-view-grid localized-date-month-grid">
                <template x-for="(month, index) in months" :key="month">
                    <button type="button" class="localized-date-option" :class="index === viewDate.getMonth() ? 'localized-date-option-selected' : ''" @click="selectMonth(index)" x-text="month"></button>
                </template>
            </div>

            <div x-show="mode === 'years'" class="localized-date-view-grid localized-date-year-grid">
                <template x-for="year in yearOptions" :key="year">
                    <button type="button" class="localized-date-option" :class="year === viewDate.getFullYear() ? 'localized-date-option-selected' : ''" @click="selectYear(year)" x-text="year"></button>
                </template>
            </div>

            <div class="localized-date-actions">
                <button type="button" class="btn-clear" @click="clear()">Notīrīt</button>
                <button type="button" class="btn-view" @click="today()">Šodien</button>
                <button type="button" class="btn-back" @click="open = false">Aizvērt</button>
            </div>
        </div>
    </div>
</label>
