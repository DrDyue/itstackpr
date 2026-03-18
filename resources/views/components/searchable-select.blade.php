@props([
    'name',
    'queryName',
    'options' => [],
    'selected' => '',
    'query' => '',
    'identifier' => '',
    'placeholder' => 'Izvelies vertibu',
    'emptyMessage' => 'Ieraksti nav atrasti.',
])

@php
    $optionsPayload = collect($options)
        ->map(function ($option) {
            if (is_array($option)) {
                return [
                    'value' => (string) ($option['value'] ?? ''),
                    'label' => (string) ($option['label'] ?? ''),
                    'description' => (string) ($option['description'] ?? ''),
                    'search' => (string) ($option['search'] ?? (($option['label'] ?? '') . ' ' . ($option['description'] ?? ''))),
                ];
            }

            return [
                'value' => (string) data_get($option, 'value', data_get($option, 'id', '')),
                'label' => (string) data_get($option, 'label', data_get($option, 'name', data_get($option, 'type_name', ''))),
                'description' => (string) data_get($option, 'description', ''),
                'search' => (string) data_get($option, 'search', ''),
            ];
        })
        ->values()
        ->all();
@endphp

<div
    x-data="searchableSelect({
        selected: @js((string) $selected),
        query: @js((string) $query),
        identifier: @js((string) $identifier),
        placeholder: @js($placeholder),
        emptyMessage: @js($emptyMessage),
        options: @js($optionsPayload),
    })"
    class="searchable-select"
    @keydown.escape.window="close()"
    @mousemove.window="handlePointerMove($event)"
    @mouseup.window="stopPointer()"
    @searchable-select-clear.window="if (! $event.detail?.target || $event.detail.target === @js($identifier)) { clearSelection() }"
>
    <input type="hidden" name="{{ $name }}" x-model="selected">

    <div class="relative">
        <button
            x-cloak
            x-show="!open"
            type="button"
            class="searchable-select-surface"
            title="Klikskini, lai atvertu. Turi un velc uz augsu vai leju, lai atri izveletos."
            @pointerdown.prevent.stop="beginScrub($event)"
            @pointermove.stop="handleSurfacePointerMove($event)"
            @pointerup.stop="finishSurfacePointer($event)"
            @pointercancel.stop="cancelSurfacePointer($event)"
        ></button>

        <input
            x-ref="input"
            type="text"
            name="{{ $queryName }}"
            x-model="query"
            class="crud-control pr-14"
            :class="open ? 'border-sky-300 ring-2 ring-sky-100 bg-white cursor-text' : 'cursor-default'"
            :readonly="!open"
            placeholder="{{ $placeholder }}"
            autocomplete="off"
            @focus="openPanel()"
            @click="handleTriggerClick()"
            @input="handleInput()"
            @keydown.arrow-down.prevent="move(1)"
            @keydown.arrow-up.prevent="move(-1)"
            @keydown.enter.prevent="commit()"
        >

        <button
            type="button"
            class="searchable-select-toggle"
            :class="pointerMode === 'scrub' ? 'searchable-select-toggle-active' : ''"
            title="Turi un velc uz augsu vai leju, lai atri izveletos vertibu"
            @click="togglePanel()"
            @keydown.enter.prevent="togglePanel()"
            @keydown.space.prevent="togglePanel()"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
            </svg>
        </button>
    </div>

    <div
        x-cloak
        x-show="open"
        x-transition.origin.top.left
        class="searchable-select-panel"
        @click.outside="close()"
    >
        <div
            x-ref="panel"
            class="searchable-select-list"
            :class="dragging ? 'cursor-grabbing select-none' : 'cursor-grab'"
            @mousedown="startPointer($event)"
        >
            <template x-if="filteredOptions.length === 0">
                <div class="searchable-select-empty" x-text="emptyMessage"></div>
            </template>

            <template x-for="(option, index) in filteredOptions" :key="option.value + '-' + index">
                <button
                    type="button"
                    class="searchable-select-option"
                    :data-index="index"
                    :class="optionClasses(index, option)"
                    @mouseenter="highlightedIndex = index"
                    @click="choose(option)"
                >
                    <span class="block text-sm font-semibold leading-5" x-text="option.label"></span>
                    <span
                        class="mt-1 block text-xs leading-5"
                        :class="highlightedIndex === index ? 'text-white/75' : 'text-slate-500'"
                        x-show="option.description"
                        x-text="option.description"
                    ></span>
                </button>
            </template>
        </div>
    </div>
</div>
