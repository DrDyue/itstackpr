<x-app-layout>
    <section class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mb-6">
            <h1 class="text-2xl font-semibold text-gray-900">Audita zurnals</h1>
            <p class="text-sm text-gray-500">Pedejie 300 sistemas notikumi</p>
        </div>
        
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-600">
                        <tr>
                            <th class="px-4 py-3 text-left">Laiks</th>
                            <th class="px-4 py-3 text-left">Lietotjs</th>
                            <th class="px-4 py-3 text-left">Darbiba</th>
                            <th class="px-4 py-3 text-left">Entitija</th>
                            <th class="px-4 py-3 text-left">ID</th>
                            <th class="px-4 py-3 text-left">Apraksts</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($logs as $log)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3">{{ $log->created_at }}</td>
                                <td class="px-4 py-3">{{ $log->user_id }}</td>
                                <td class="px-4 py-3">{{ $log->action }}</td>
                                <td class="px-4 py-3">{{ $log->entity_type }}</td>
                                <td class="px-4 py-3">{{ $log->entity_id }}</td>
                                <td class="px-4 py-3">{{ $log->description }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">Zurnals ir tukss.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</x-app-layout>


