{{-- Datuma izvēles komponents ar tekstlauku un kalendāra logiku. --}}
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
        class="relative"
        x-data="localizedDatePicker({ value: @js($value) })"
        @if ($xModel) x-modelable="value" x-model="{{ $xModel }}" @endif
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

@once
    <script>
        (() => {
            const registerLocalizedDatePicker = () => {
                if (!window.Alpine || window.__localizedDatePickerRegistered) {
                    return;
                }

                window.__localizedDatePickerRegistered = true;

                window.Alpine.data('localizedDatePicker', ({ value = '' } = {}) => ({
                    open: false,
                    value: value || '',
                    viewDate: null,
                    weekdays: ['Pr', 'Ot', 'Tr', 'Ce', 'Pk', 'Se', 'Sv'],
                    months: ['Janvaris', 'Februaris', 'Marts', 'Aprilis', 'Maijs', 'Junijs', 'Julijs', 'Augusts', 'Septembris', 'Oktobris', 'Novembris', 'Decembris'],
                    init() {
                        this.viewDate = this.value ? this.parseDate(this.value) : new Date();
                    },
                    toggle() {
                        this.open = !this.open;
                    },
                    previousMonth() {
                        this.viewDate = new Date(this.viewDate.getFullYear(), this.viewDate.getMonth() - 1, 1);
                    },
                    nextMonth() {
                        this.viewDate = new Date(this.viewDate.getFullYear(), this.viewDate.getMonth() + 1, 1);
                    },
                    select(selectedValue) {
                        this.value = selectedValue;
                        this.viewDate = this.parseDate(selectedValue);
                        this.open = false;
                    },
                    clear() {
                        this.value = '';
                        this.open = false;
                    },
                    parseDate(dateValue) {
                        const [year, month, day] = dateValue.split('-').map(Number);
                        return new Date(year, month - 1, day);
                    },
                    formatDate(dateValue) {
                        if (!dateValue) {
                            return '';
                        }

                        const [year, month, day] = dateValue.split('-');
                        return `${day}.${month}.${year}`;
                    },
                    toIso(date) {
                        const year = date.getFullYear();
                        const month = String(date.getMonth() + 1).padStart(2, '0');
                        const day = String(date.getDate()).padStart(2, '0');

                        return `${year}-${month}-${day}`;
                    },
                    get displayValue() {
                        return this.formatDate(this.value);
                    },
                    get monthLabel() {
                        return `${this.months[this.viewDate.getMonth()]} ${this.viewDate.getFullYear()}`;
                    },
                    get days() {
                        const startOfMonth = new Date(this.viewDate.getFullYear(), this.viewDate.getMonth(), 1);
                        const endOfMonth = new Date(this.viewDate.getFullYear(), this.viewDate.getMonth() + 1, 0);
                        const startWeekday = (startOfMonth.getDay() + 6) % 7;
                        const days = [];

                        for (let i = startWeekday; i > 0; i -= 1) {
                            const date = new Date(this.viewDate.getFullYear(), this.viewDate.getMonth(), 1 - i);
                            days.push({
                                key: `prev-${this.toIso(date)}`,
                                label: date.getDate(),
                                value: this.toIso(date),
                                isCurrentMonth: false,
                                isSelected: false,
                            });
                        }

                        for (let day = 1; day <= endOfMonth.getDate(); day += 1) {
                            const date = new Date(this.viewDate.getFullYear(), this.viewDate.getMonth(), day);
                            const iso = this.toIso(date);

                            days.push({
                                key: iso,
                                label: day,
                                value: iso,
                                isCurrentMonth: true,
                                isSelected: this.value === iso,
                            });
                        }

                        while (days.length < 42) {
                            const offset = days.length - (startWeekday + endOfMonth.getDate()) + 1;
                            const date = new Date(this.viewDate.getFullYear(), this.viewDate.getMonth() + 1, offset);
                            days.push({
                                key: `next-${this.toIso(date)}`,
                                label: date.getDate(),
                                value: this.toIso(date),
                                isCurrentMonth: false,
                                isSelected: false,
                            });
                        }

                        return days;
                    },
                }));
            };

            document.addEventListener('alpine:init', registerLocalizedDatePicker);
            registerLocalizedDatePicker();
        })();
    </script>
@endonce
