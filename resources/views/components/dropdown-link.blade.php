{{-- Saite, ko izmanto dropdown izvēlnēs. --}}
<a {{ $attributes->merge(['class' => 'flex w-full items-center gap-2.5 rounded-xl px-4 py-2.5 text-start text-sm font-medium text-slate-700 transition duration-150 ease-in-out hover:bg-slate-100 hover:text-slate-900 focus:bg-slate-100 focus:text-slate-900 focus:outline-none']) }}>{{ $slot }}</a>


