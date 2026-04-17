{{--
    Komponents: Validācijas kopsavilkums.
    Atbildība: vienuviet parāda galvenās formas kļūdas un palīdz lietotājam saprast, kas jāizlabo.
    Kāpēc tas ir svarīgi:
    1. Lietotājam nav jāmeklē kļūdas tikai pa atsevišķiem laukiem.
    2. Komponents var tikt izmantots dažādās formās ar vienādu stilu un loģiku.
    3. Šeit var redzēt, kā no kļūdu atslēgām tiek veidoti cilvēkam saprotami padomi.
--}}
@props([
    'title' => 'Neizdevās saglabāt formu',
    'bag' => null,
    'fieldLabels' => [],
    'focusFirstError' => true,
])

@php
    $errorBag = $bag ?? $errors;
    $errorKeys = collect($errorBag->keys())->values();
    $firstErrorField = $focusFirstError ? $errorKeys->first() : null;
    $displayErrors = collect($errorBag->messages())
        ->flatMap(function (array $messages, string $field) use ($fieldLabels) {
            $label = $fieldLabels[$field] ?? null;

            return collect($messages)->map(fn (string $message) => [
                'field' => $field,
                'label' => $label,
                'message' => $message,
            ]);
        })
        ->values();
    $tips = $errorKeys
        ->flatMap(function (string $field) {
            return match (true) {
                str_contains($field, 'device') => ['Izvēlies ierīci no saraksta vēlreiz. Ja tā vairs nav pieejama, atsvaidzini lapu vai nomaini darbību.'],
                str_contains($field, 'room') || str_contains($field, 'building') => ['Pārbaudi atrašanās vietu. Aktīvai ierīcei jābūt piesaistītai telpai, un telpai jābūt derīgai izvēlētajai ēkai.'],
                str_contains($field, 'assigned') || $field === 'user_id' || $field === 'transfered_to_id' => ['Pārbaudi atbildīgo personu vai saņēmēju un izvēlies ierakstu no meklējamā saraksta vēlreiz.'],
                str_contains($field, 'status') => ['Izvēlies vienu no piedāvātajiem statusiem. Ja ieraksts jau ir apstrādāts, atver sarakstu no jauna un pārbaudi aktīvo stāvokli.'],
                str_contains($field, 'image') || str_contains($field, 'file') => ['Pārbaudi failu. Drīkst izmantot tikai atbalstītu faila tipu, un failam jābūt pietiekami mazam augšupielādei.'],
                str_contains($field, 'date') => ['Pārbaudi datumus. Ja aizpildi vairākus datumus, tiem savstarpēji jāsaskan.'],
                default => ['Izlabo iezīmēto lauku un mēģini vēlreiz. Ja problēma atkārtojas, atver ierakstu no jauna un pārbaudi, vai saistītie dati vēl ir pieejami.'],
            };
        })
        ->unique()
        ->values();
@endphp

@if ($errorBag->any())
    <div
        {{ $attributes->class('validation-summary') }}
        @if ($firstErrorField)
            data-first-error-field="{{ $firstErrorField }}"
        @endif
    >
        <div class="validation-summary-head">
            <span class="validation-summary-icon">
                <x-icon name="exclamation-triangle" size="h-5 w-5" />
            </span>
            <div>
                <div class="validation-summary-title">{{ $title }}</div>
                <div class="validation-summary-subtitle">
                    Atrastas {{ $errorBag->count() }} {{ \Illuminate\Support\Str::plural('problēma', $errorBag->count()) }}. Izlabo atzīmētos laukus un saglabā vēlreiz.
                </div>
            </div>
        </div>

        <div class="validation-summary-grid">
            <div>
                <div class="validation-summary-section">Kas jāizlabo</div>
                <ul class="validation-summary-list">
                    @foreach ($displayErrors as $error)
                        <li>
                            @if ($error['label'])
                                <strong>{{ $error['label'] }}:</strong>
                            @endif
                            {{ $error['message'] }}
                        </li>
                    @endforeach
                </ul>
            </div>

            @if ($tips->isNotEmpty())
                <div>
                    <div class="validation-summary-section">Ko darīt tālāk</div>
                    <ul class="validation-summary-tips">
                        @foreach ($tips as $tip)
                            <li>{{ $tip }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>
@endif
