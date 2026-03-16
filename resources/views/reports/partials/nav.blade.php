@php
    $tabs = [
        [
            'route' => 'reports.index',
            'label' => 'Parskats',
            'icon' => 'M3.75 5.25h16.5v4.5H3.75Zm0 9h7.5v4.5h-7.5Zm10.5 0h6v4.5h-6Z',
        ],
        [
            'route' => 'reports.devices',
            'label' => 'Ierices',
            'icon' => 'M4.5 7.5h15m-15 4.5h15m-15 4.5h9M3.75 5.25h16.5A1.5 1.5 0 0 1 21.75 6.75v10.5a1.5 1.5 0 0 1-1.5 1.5H3.75a1.5 1.5 0 0 1-1.5-1.5V6.75a1.5 1.5 0 0 1 1.5-1.5Z',
        ],
        [
            'route' => 'reports.repairs',
            'label' => 'Remonti',
            'icon' => 'm15.75 6.75 1.5-1.5a2.121 2.121 0 1 1 3 3l-7.5 7.5-4.5 1.5 1.5-4.5 6-6Z M13.5 9l3 3',
        ],
        [
            'route' => 'reports.activity',
            'label' => 'Aktivitate',
            'icon' => 'M3.75 12h4.5l1.5-5.25L13.5 17.25l2.25-6h4.5',
        ],
    ];
@endphp

<div class="flex flex-wrap gap-2">
    @foreach ($tabs as $tab)
        @php $active = request()->routeIs($tab['route']); @endphp
        <a
            href="{{ route($tab['route']) }}"
            class="inline-flex items-center gap-2 rounded-full px-4 py-2 text-sm font-semibold ring-1 transition {{ $active ? 'bg-slate-900 text-white ring-slate-900' : 'bg-white text-slate-700 ring-slate-300 hover:bg-slate-50 hover:text-slate-900' }}"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $tab['icon'] }}"/>
            </svg>
            <span>{{ $tab['label'] }}</span>
        </a>
    @endforeach
</div>
