@props([
    'id' => null,
    'shellClass' => 'device-table-shell',
    'scrollClass' => 'device-table-scroll rounded-[1.75rem] border border-slate-200 bg-white shadow-sm',
    'tableClass' => 'device-table-content min-w-full text-sm',
])

{{-- Kopīgs tabulas karkass nodrošina vienādu scroll struktūru visām lielajām tabulām.
     Async table skripts droši var meklēt `table-scroll-viewport` neatkarīgi no konkrētās lapas. --}}
<div @if($id) id="{{ $id }}" @endif class="{{ $shellClass }}">
    <div class="{{ $scrollClass }}">
        <div class="table-scroll-viewport">
            {{-- Slotā nāk konkrētās lapas `<thead>` un `<tbody>`, bet ārējais karkass paliek nemainīgs. --}}
            <table class="{{ $tableClass }}">
                {{ $slot }}
            </table>
        </div>
    </div>
</div>
