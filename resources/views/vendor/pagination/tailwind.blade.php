{{--
    Vendor skats: Pielāgota lapošana.
    Atbildība: aizvieto Laravel noklusēto pagination attēlojumu ar projektam atbilstošu dizainu un latviskiem tekstiem.
    Kāpēc tas ir svarīgi:
    1. Lietotājs neredz noklusētos angļu tekstus Previous/Next/Showing.
    2. Visās tabulās lapošana izskatās vienādi un ir vieglāk saprotama.
    3. Šis fails centralizē pagination izskatu, tāpēc nav jālabo katra tabula atsevišķi.
--}}
@if ($paginator->hasPages())
    @php
        $currentPage = $paginator->currentPage();
        $lastPage = $paginator->lastPage();
    @endphp
    <nav role="navigation" aria-label="Lapu navigacija" class="app-pagination">
        <div class="app-pagination-meta">
            <span>Kopa {{ $paginator->total() }} ieraksti</span>
            <span>Lapa {{ $currentPage }} no {{ $lastPage }}</span>
        </div>

        <div class="app-pagination-links">
            @if ($paginator->onFirstPage())
                <span class="app-pagination-btn app-pagination-btn-disabled">Iepriekšēja</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="app-pagination-btn">Iepriekšēja</a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="app-pagination-btn app-pagination-btn-disabled">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $currentPage)
                            <span aria-current="page" class="app-pagination-btn app-pagination-btn-active">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="app-pagination-btn">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="app-pagination-btn">Nakama</a>
            @else
                <span class="app-pagination-btn app-pagination-btn-disabled">Nakama</span>
            @endif
        </div>
    </nav>
@endif
