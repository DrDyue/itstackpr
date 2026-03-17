<x-app-layout>
    <section class="mx-auto max-w-5xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h1 class="text-3xl font-semibold text-slate-900">Jauna ierice</h1>
                <p class="mt-2 text-sm text-slate-600">Pievieno jaunu ierīci un piesaisti to lietotājam.</p>
            </div>
            <a href="{{ route('devices.index') }}" class="crud-btn-secondary">Atpakal</a>
        </div>

        @if ($errors->any())
            <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('devices.store') }}" enctype="multipart/form-data" class="space-y-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            @csrf
            @include('devices.partials.form-fields', ['device' => null])
            <div class="flex flex-wrap gap-3">
                <button type="submit" class="crud-btn-primary">Saglabat</button>
                <a href="{{ route('devices.index') }}" class="crud-btn-secondary">Atcelt</a>
            </div>
        </form>
    </section>
</x-app-layout>
