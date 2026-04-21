{{--
    Komponents: Statusa birka.
    Atbildība: vienotā veidā attēlo ierīču, remonta un pieprasījumu statusus ar tekstu un krāsām.
    Kāpēc tas ir svarīgi:
    1. Vienā vietā tiek uzturēta statusu vizuālā loģika.
    2. Lietotājs ātri saprot, kas ir aktīvs, gaidošs, procesā vai noraidīts.
    3. Komisijai šis komponents labi parāda atkārtoti izmantojamu UI principu.
--}}
@props([
    'value',
    'context' => 'generic',
    'label' => null,
])

@php
    $normalizedValue = is_bool($value) ? ($value ? '1' : '0') : strtolower((string) ($value ?? ''));

    $maps = [
        'device' => [
            'active' => ['label' => 'Aktīva', 'tone' => 'success', 'icon' => 'check-circle'],
            'repair' => ['label' => 'Remontā', 'tone' => 'warning', 'icon' => 'repair'],
            'writeoff' => ['label' => 'Norakstīta', 'tone' => 'danger', 'icon' => 'writeoff'],
        ],
        'request' => [
            'submitted' => ['label' => 'Iesniegts', 'tone' => 'warning', 'icon' => 'clock'],
            'approved' => ['label' => 'Apstiprināts', 'tone' => 'success', 'icon' => 'check-circle'],
            'rejected' => ['label' => 'Noraidīts', 'tone' => 'danger', 'icon' => 'x-circle'],
        ],
        'repair' => [
            'waiting' => ['label' => 'Gaida', 'tone' => 'warning', 'icon' => 'clock'],
            'in-progress' => ['label' => 'Procesā', 'tone' => 'info', 'icon' => 'repair'],
            'completed' => ['label' => 'Pabeigts', 'tone' => 'success', 'icon' => 'check-circle'],
            'cancelled' => ['label' => 'Atcelts', 'tone' => 'danger', 'icon' => 'x-circle'],
        ],
        'priority' => [
            'low' => ['label' => 'Zema', 'tone' => 'neutral', 'icon' => 'information-circle'],
            'medium' => ['label' => 'Vidēja', 'tone' => 'info', 'icon' => 'tag'],
            'high' => ['label' => 'Augsta', 'tone' => 'warning', 'icon' => 'flag'],
            'critical' => ['label' => 'Kritiska', 'tone' => 'danger', 'icon' => 'bolt'],
        ],
        'user-active' => [
            '1' => ['label' => 'Aktīvs', 'tone' => 'success', 'icon' => 'check-circle'],
            '0' => ['label' => 'Neaktīvs', 'tone' => 'danger', 'icon' => 'x-circle'],
            'active' => ['label' => 'Aktīvs', 'tone' => 'success', 'icon' => 'check-circle'],
            'inactive' => ['label' => 'Neaktīvs', 'tone' => 'danger', 'icon' => 'x-circle'],
        ],
        'user-role' => [
            'admin' => ['label' => 'Admins', 'tone' => 'violet', 'icon' => 'users'],
            'user' => ['label' => 'Darbinieks', 'tone' => 'info', 'icon' => 'profile'],
        ],
        'severity' => [
            'info' => ['label' => 'Info', 'tone' => 'neutral', 'icon' => 'audit'],
            'warning' => ['label' => 'Brīdinājums', 'tone' => 'warning', 'icon' => 'exclamation-triangle'],
            'error' => ['label' => 'Kļūda', 'tone' => 'danger', 'icon' => 'x-circle'],
            'critical' => ['label' => 'Kritisks', 'tone' => 'danger', 'icon' => 'exclamation-triangle'],
        ],
        'repair-type' => [
            'internal' => ['label' => 'Iekšējais', 'tone' => 'neutral', 'icon' => 'repair'],
            'external' => ['label' => 'Ārējais', 'tone' => 'violet', 'icon' => 'repair-request'],
        ],
    ];

    $meta = $maps[$context][$normalizedValue] ?? [
        'label' => ucfirst(str_replace('-', ' ', $normalizedValue)),
        'tone' => 'neutral',
        'icon' => 'tag',
    ];

    $resolvedLabel = $label ?: $meta['label'];
    $isPendingAction = $context === 'request' && $normalizedValue === 'submitted';
@endphp

<span {{ $attributes->class(['status-pill', 'status-pill-' . $meta['tone'], 'status-pill-pending-action' => $isPendingAction]) }}>
    <x-icon :name="$meta['icon']" size="h-3.5 w-3.5" />
    <span>{{ $resolvedLabel }}</span>
</span>
