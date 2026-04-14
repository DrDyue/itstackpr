{{-- Ziņojums par autentifikācijas statusu, piemēram, paroles atjaunošanas saites nosūtīšanu. --}}
@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'font-medium text-sm text-green-600']) }}>
        {{ $status }}
    </div>
@endif

