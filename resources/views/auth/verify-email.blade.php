<x-guest-layout>
    <div class="auth-intro-text">
        Paldies par reistrciju! Pirms skanas, ldzu apstipriniet savu e-pasta adresi, noklikinot uz saites, kuru ms tikko nostjm. Ja neatradt e-pastu, ar prieku nostsim vl vienu.
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="verification-success-banner">
            Jauna apstiprinjuma saite ir nostta uz e-pasta adresi, kuru nordjt reistrcijas laik.
        </div>
    @endif

    <div class="verification-actions">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="btn-primary btn-auto">
                Prstt apstiprinjuma e-pastu
            </button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn-link-plain">
                Izloties
            </button>
        </form>
    </div>
</x-guest-layout>


