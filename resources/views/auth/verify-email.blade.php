<x-guest-layout>
    <div style="color: #555; font-size: 14px; margin-bottom: 20px;">
        Paldies par reģistrāciju! Pirms sākšanas, lūdzu apstipriniet savu e-pasta adresi, noklikšķinot uz saites, kuru mēs tikko nosūtījām. Ja neatradāt e-pastu, ar prieku nosūtīsim vēl vienu.
    </div>

    @if (session('status') == 'verification-link-sent')
        <div style="background-color: #f0fdf4; color: #15803d; padding: 12px 14px; border-radius: 8px; border: 1px solid #bbf7d0; margin-bottom: 16px; font-size: 14px; font-weight: 500;">
            Jauna apstiprinājuma saite ir nosūtīta uz e-pasta adresi, kuru norādījāt reģistrācijas laikā.
        </div>
    @endif

    <div style="display: flex; gap: 16px; margin-top: 24px; justify-content: space-between;">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="btn-primary" style="width: auto;">
                Pārsūtīt apstiprinājuma e-pastu
            </button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" style="background: none; border: none; color: #0071e3; text-decoration: underline; cursor: pointer; font-size: 14px; padding: 12px 0;">
                Izloģēties
            </button>
        </form>
    </div>
</x-guest-layout>
