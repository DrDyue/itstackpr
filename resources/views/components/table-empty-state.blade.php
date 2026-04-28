{{-- Vienots tukšais stāvoklis tabulu sarakstiem. --}}
@props([
    'icon' => 'search',
    'title' => 'Nav atrastu ierakstu',
    'description' => 'Maini filtrus vai notīri atlasītos kritērijus, lai redzētu vairāk rezultātu.',
    'clearHref' => null,
    'clearLabel' => 'Notīrīt filtrus',
])

@php
    $hasQuery = request()->query() !== [];
    $resolvedClearHref = $clearHref ?? ($hasQuery ? url()->current() : null);
@endphp

<x-empty-state
    compact
    class="table-empty-state"
    :icon="$icon"
    :title="$title"
    :description="$description"
    :action-href="$resolvedClearHref"
    :action-label="$resolvedClearHref ? $clearLabel : null"
/>
