<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Mail\LoginOtpMail;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $this->validateRecaptcha($request->input('g-recaptcha-response'));

        $user = $request->authenticate();

        $otp = $this->generateOtp();

        $user->update([
            'login_otp_code' => Hash::make($otp),
            'login_otp_expires_at' => now()->addMinutes(10),
        ]);

        Mail::to($user->email)->send(new LoginOtpMail($otp, $user->name));

        $request->session()->put('login_pending_user_id', $user->id);
        $request->session()->put('login_pending_remember', $request->boolean('remember'));

        return redirect()
            ->route('login.otp')
            ->with('status', 'Te enviamos un código a tu correo.');
    }

    public function createOtp(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('login_pending_user_id')) {
            return redirect()->route('login');
        }

        return view('auth.verify-otp');
    }

    public function verifyOtp(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'otp' => ['required', 'digits:6'],
        ], [
            'otp.required' => 'Debes ingresar el código.',
            'otp.digits' => 'El código debe tener 6 dígitos.',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $userId = $request->session()->get('login_pending_user_id');

        if (! $userId) {
            return redirect()->route('login')->withErrors([
                'otp' => 'Tu sesión de verificación expiró. Inicia sesión de nuevo.',
            ]);
        }

        $user = User::find($userId);

        if (! $user || ! $user->login_otp_code || ! $user->login_otp_expires_at) {
            return redirect()->route('login')->withErrors([
                'otp' => 'No hay un código válido pendiente.',
            ]);
        }

        if (now()->greaterThan($user->login_otp_expires_at)) {
            $user->update([
                'login_otp_code' => null,
                'login_otp_expires_at' => null,
            ]);

            $request->session()->forget(['login_pending_user_id', 'login_pending_remember']);

            return redirect()->route('login')->withErrors([
                'otp' => 'El código expiró. Inicia sesión de nuevo.',
            ]);
        }

        if (! Hash::check($request->otp, $user->login_otp_code)) {
            return back()->withErrors([
                'otp' => 'El código es incorrecto.',
            ])->withInput();
        }

        Auth::login($user, $request->session()->get('login_pending_remember', false));

        $user->update([
            'login_otp_code' => null,
            'login_otp_expires_at' => null,
        ]);

        $request->session()->forget(['login_pending_user_id', 'login_pending_remember']);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    public function resendOtp(Request $request): RedirectResponse
    {
        $userId = $request->session()->get('login_pending_user_id');

        if (! $userId) {
            return redirect()->route('login');
        }

        $user = User::find($userId);

        if (! $user) {
            return redirect()->route('login');
        }

        $otp = $this->generateOtp();

        $user->update([
            'login_otp_code' => Hash::make($otp),
            'login_otp_expires_at' => now()->addMinutes(10),
        ]);

        Mail::to($user->email)->send(new LoginOtpMail($otp, $user->name));

        return back()->with('status', 'Se envió un nuevo código a tu correo.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->forget(['login_pending_user_id', 'login_pending_remember']);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    private function generateOtp(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    private function validateRecaptcha(?string $token): void
    {
        $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret' => config('services.recaptcha.secret_key'),
            'response' => $token,
            'remoteip' => request()->ip(),
        ]);

        $data = $response->json();

        if (! isset($data['success']) || $data['success'] !== true) {
            throw ValidationException::withMessages([
                'g-recaptcha-response' => 'No se pudo validar el reCAPTCHA.',
            ]);
        }
    }
}