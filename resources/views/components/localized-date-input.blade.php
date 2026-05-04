{{-- Datuma ievades komponents ar lokalizētu formātu un Alpine palīgloģiku. --}}
@props([
    'name',
    'value' => '',
    'label' => null,
    'placeholder' => 'dd.mm.gggg',
])

@php
    // Backend strādā ar ISO datumu `Y-m-d`, bet lietotājam rādām `dd.mm.gggg`.
    // Tāpēc īstā formas vērtība tiek glabāta hidden inputā, nevis pogas tekstā.
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
        {{-- Šis hidden input ir vienīgais lauks, ko forma iesniedz backendam. --}}
        <input type="hidden" name="{{ $name }}" x-model="value">

        {{-- Redzamā poga tikai attēlo lokalizēto vērtību un atver kalendāru. --}}
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
                {{-- Navigācija maina mēnesi vai 12 gadu lapu atkarībā no pašreizējā režīma. --}}
                <button type="button" class="localized-date-nav" @click="mode === 'years' ? previousYearPage() : previousMonth()" aria-label="Iepriekšējais periods">&#8249;</button>
                <div class="localized-date-heading">
                    <button type="button" class="localized-date-current-button" @click="showMonths()" x-text="months[viewDate.getMonth()]"></button>
                    <button type="button" class="localized-date-current-button" @click="showYears()" x-text="viewDate.getFullYear()"></button>
                    <span class="localized-date-year-range" x-show="mode === 'years'" x-text="yearRangeLabel"></span>
                </div>
                <button type="button" class="localized-date-nav" @click="mode === 'years' ? nextYearPage() : nextMonth()" aria-label="Nākamais periods">&#8250;</button>
            </div>

            <div x-show="mode === 'days'">
                {{-- Dienu režģi aprēķina Alpine `localizedDatePicker.days`,
                     lai kalendārs vienmēr saglabātu stabilu 6x7 izkārtojumu. --}}
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
            </div>

            <div x-show="mode === 'months'" class="localized-date-view-grid localized-date-month-grid">
                <template x-for="(month, index) in months" :key="month">
                    <button
                        type="button"
                        class="localized-date-option"
                        :class="index === viewDate.getMonth() ? 'localized-date-option-selected' : ''"
                        @click="selectMonth(index)"
                        x-text="month"
                    ></button>
                </template>
            </div>

            <div x-show="mode === 'years'" class="localized-date-view-grid localized-date-year-grid">
                <template x-for="year in yearOptions" :key="year">
                    <button
                        type="button"
                        class="localized-date-option"
                        :class="year === viewDate.getFullYear() ? 'localized-date-option-selected' : ''"
                        @click="selectYear(year)"
                        x-text="year"
                    ></button>
                </template>
            </div>

            <div class="localized-date-actions">
                {{-- Ātrās darbības maina hidden ISO vērtību, nevis tikai redzamo tekstu. --}}
                <button type="button" class="btn-clear" @click="clear()">Notīrīt</button>
                <button type="button" class="btn-view" @click="today()">Šodien</button>
                <button type="button" class="btn-back" @click="open = false">Aizvērt</button>
            </div>
        </div>
    </div>
</label>
