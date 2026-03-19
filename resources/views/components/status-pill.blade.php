@props([
    'value',
    'context' => 'generic',
    'label' => null,
])

@php
    $normalizedValue = is_bool($value) ? ($value ? '1' : '0') : strtolower((string) ($value ?? ''));

    $maps = [
        'device' => [
            'active' => ['label' => 'Aktiva', 'tone' => 'success', 'icon' => 'check-circle'],
            'repair' => ['label' => 'Remonta', 'tone' => 'warning', 'icon' => 'repair'],
            'writeoff' => ['label' => 'Norakstita', 'tone' => 'danger', 'icon' => 'writeoff'],
        ],
        'request' => [
            'submitted' => ['label' => 'Iesniegts', 'tone' => 'info', 'icon' => 'clock'],
            'approved' => ['label' => 'Apstiprinats', 'tone' => 'success', 'icon' => 'check-circle'],
            'rejected' => ['label' => 'Noraidits', 'tone' => 'danger', 'icon' => 'x-circle'],
        ],
        'repair' => [
            'waiting' => ['label' => 'Gaida', 'tone' => 'info', 'icon' => 'clock'],
            'in-progress' => ['label' => 'Procesa', 'tone' => 'warning', 'icon' => 'repair'],
            'completed' => ['label' => 'Pabeigts', 'tone' => 'success', 'icon' => 'check-circle'],
            'cancelled' => ['label' => 'Atcelts', 'tone' => 'danger', 'icon' => 'x-circle'],
        ],
        'priority' => [
            'low' => ['label' => 'Zema', 'tone' => 'neutral', 'icon' => 'tag'],
            'medium' => ['label' => 'Videja', 'tone' => 'info', 'icon' => 'tag'],
            'high' => ['label' => 'Augsta', 'tone' => 'warning', 'icon' => 'exclamation-triangle'],
            'critical' => ['label' => 'Kritiska', 'tone' => 'danger', 'icon' => 'exclamation-triangle'],
        ],
        'user-active' => [
            '1' => ['label' => 'Aktivs', 'tone' => 'success', 'icon' => 'check-circle'],
            '0' => ['label' => 'Neaktivs', 'tone' => 'danger', 'icon' => 'x-circle'],
            'active' => ['label' => 'Aktivs', 'tone' => 'success', 'icon' => 'check-circle'],
            'inactive' => ['label' => 'Neaktivs', 'tone' => 'danger', 'icon' => 'x-circle'],
        ],
        'user-role' => [
            'admin' => ['label' => 'Admins', 'tone' => 'violet', 'icon' => 'users'],
            'user' => ['label' => 'Darbinieks', 'tone' => 'info', 'icon' => 'profile'],
        ],
        'severity' => [
            'info' => ['label' => 'Info', 'tone' => 'neutral', 'icon' => 'audit'],
            'warning' => ['label' => 'Bridinajums', 'tone' => 'warning', 'icon' => 'exclamation-triangle'],
            'error' => ['label' => 'Kluda', 'tone' => 'danger', 'icon' => 'x-circle'],
            'critical' => ['label' => 'Kritisks', 'tone' => 'danger', 'icon' => 'exclamation-triangle'],
        ],
        'repair-type' => [
            'internal' => ['label' => 'Ieksejais', 'tone' => 'neutral', 'icon' => 'repair'],
            'external' => ['label' => 'Arejais', 'tone' => 'violet', 'icon' => 'repair-request'],
        ],
    ];

    $meta = $maps[$context][$normalizedValue] ?? [
        'label' => ucfirst(str_replace('-', ' ', $normalizedValue)),
        'tone' => 'neutral',
        'icon' => 'tag',
    ];

    $resolvedLabel = $label ?: $meta['label'];
@endphp

<span {{ $attributes->class(['status-pill', 'status-pill-' . $meta['tone']]) }}>
    <x-icon :name="$meta['icon']" size="h-3.5 w-3.5" />
    <span>{{ $resolvedLabel }}</span>
</span>
