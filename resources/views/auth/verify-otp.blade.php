<x-guest-layout>
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <div class="mb-4 text-sm text-gray-600">
        Te enviamos un código de 6 dígitos a tu correo. Ingresa el código para completar el inicio de sesión.
    </div>

    <form method="POST" action="{{ route('login.otp.verify') }}">
        @csrf

        <div>
            <x-input-label for="otp" :value="__('Código OTP')" />
            <x-text-input id="otp" class="block mt-1 w-full" type="text" name="otp" :value="old('otp')" required autofocus maxlength="6" autocomplete="one-time-code" />
            <x-input-error :messages="$errors->get('otp')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button>
                Verificar código
            </x-primary-button>
        </div>
    </form>

    <form method="POST" action="{{ route('login.otp.resend') }}" class="mt-4">
        @csrf
        <button type="submit" class="underline text-sm text-gray-600 hover:text-gray-900">
            Reenviar código
        </button>
    </form>
</x-guest-layout>