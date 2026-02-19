<x-guest-layout>
    <form method="POST" action="{{ route('register') }}">
        @csrf

        <!-- Darbinieks -->
        <div class="form-group">
            <x-input-label for="employee_id">Darbinieks</x-input-label>
            <select 
                id="employee_id" 
                name="employee_id" 
                class="form-select" 
                required
            >
                <option value="">-- Izvlieties darbnieku --</option>
                @foreach($employees as $emp)
                    <option value="{{ $emp->id }}" @selected(old('employee_id') == $emp->id)>
                        {{ $emp->full_name }} ({{ $emp->email }})
                    </option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('employee_id')" />
        </div>

        <!-- Parole -->
        <div class="form-group">
            <x-input-label for="password">Parole</x-input-label>
            <x-text-input 
                id="password" 
                type="password"
                name="password"
                placeholder="********"
                required 
                autocomplete="new-password" 
            />
            <x-input-error :messages="$errors->get('password')" />
            <p class="helper-text">Vismaz 8 rakstzmes</p>
        </div>

        <!-- Apstiprini paroli -->
        <div class="form-group">
            <x-input-label for="password_confirmation">Apstiprini paroli</x-input-label>
            <x-text-input 
                id="password_confirmation" 
                type="password"
                name="password_confirmation"
                placeholder="********"
                required 
                autocomplete="new-password" 
            />
            <x-input-error :messages="$errors->get('password_confirmation')" />
        </div>

        <!-- Loma -->
        <div class="form-group">
            <x-input-label for="role">Loma</x-input-label>
            <select 
                id="role" 
                name="role" 
                class="form-select" 
                required
            >
                <option value="">-- Izvlieties lomu --</option>
                @foreach($roles as $role)
                    <option value="{{ $role }}" @selected(old('role') == $role)>{{ $role }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('role')" />
        </div>

        <!-- Poga -->
        <button type="submit" class="btn-primary">
            Izveidot kontu
        </button>

        <!-- Pieteiksanas saite -->
        <div style="text-align: center; margin-top: 16px; padding-top: 16px; border-top: 1px solid #d5d5d7;">
            <span style="color: #555;">Jau ir konts? </span>
            <a href="{{ route('login') }}" class="auth-link">
                Pierakstities
            </a>
        </div>
    </form>
</x-guest-layout>
