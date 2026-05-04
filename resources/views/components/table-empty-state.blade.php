{{-- Vienots tukšais stāvoklis tabulu sarakstiem. --}}
@props([
    'icon' => 'search',
    'title' => 'Nav atrastu ierakstu',
    'description' => 'Maini filtrus vai notīri atlasītos kritērijus, lai redzētu vairāk rezultātu.',
    'clearHref' => null,
    'clearLabel' => 'Notīrīt filtrus',
])

@php
    // Ja pašreizējā URL ir query parametri, tukšais stāvoklis var piedāvāt "Notīrīt filtrus".
    // Ja filtru nav, darbības pogu nerādām, jo nav ko notīrīt.
    $hasQuery = request()->query() !== [];
    $resolvedClearHref = $clearHref ?? ($hasQuery ? url()->current() : null);
@endphp

{{-- Tabulu tukšais stāvoklis balstās uz vispārīgo empty-state komponenti,
     bet automātiski pievieno filtru notīrīšanas darbību, kad tā ir jēgpilna. --}}
<x-empty-state
    compact
    class="table-empty-state"
    :icon="$icon"
    :title="$title"
    :description="$description"
    :action-href="$resolvedClearHref"
    :action-label="$resolvedClearHref ? $clearLabel : null"
/>
