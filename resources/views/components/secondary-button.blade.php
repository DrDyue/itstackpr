{{-- Neitrālās sekundārās darbības poga. --}}
<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2 disabled:opacity-25']) }}>
    {{ $slot }}
</button>


