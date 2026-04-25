@props([
    'id' => null,
    'shellClass' => 'device-table-shell',
    'scrollClass' => 'device-table-scroll rounded-[1.75rem] border border-slate-200 bg-white shadow-sm',
    'tableClass' => 'device-table-content min-w-full text-sm',
])

<div @if($id) id="{{ $id }}" @endif class="{{ $shellClass }}">
    <div class="{{ $scrollClass }}">
        <div class="table-scroll-viewport">
            <table class="{{ $tableClass }}">
                {{ $slot }}
            </table>
        </div>
    </div>
</div>
