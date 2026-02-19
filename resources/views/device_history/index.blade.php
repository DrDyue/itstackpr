<x-app-layout>
    <section class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mb-6">
            <h1 class="text-2xl font-semibold text-gray-900">Iericu vesture</h1>
            <p class="text-sm text-gray-500">Pedejas 300 izmainas ierices</p>
        </div>

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-600">
                        <tr>
                            <th class="px-4 py-3 text-left">Laiks</th>
                            <th class="px-4 py-3 text-left">Ierice</th>
                            <th class="px-4 py-3 text-left">Darbiba</th>
                            <th class="px-4 py-3 text-left">Lauks</th>
                            <th class="px-4 py-3 text-left">Veca vertiba</th>
                            <th class="px-4 py-3 text-left">Jauna vertiba</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($history as $item)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3">{{ $item->timestamp }}</td>
                                <td class="px-4 py-3">
                                    {{ $item->device?->code }} - {{ $item->device?->name }}
                                    @if($item->device)
                                        <a href="{{ route('devices.history', $item->device) }}" class="ml-2 text-blue-600 hover:text-blue-700">Vesture</a>
                                    @endif
                                </td>
                                <td class="px-4 py-3">{{ $item->action }}</td>
                                <td class="px-4 py-3">{{ $item->field_changed ?: '-' }}</td>
                                <td class="px-4 py-3">{{ $item->old_value ?: '-' }}</td>
                                <td class="px-4 py-3">{{ $item->new_value ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">Vestures ierakstu nav.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</x-app-layout>
