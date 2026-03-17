<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\LoginOtpMail;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->validateRecaptcha($request->input('g-recaptcha-response'));

        $validator = Validator::make($request->all(), [
            'name'                  => ['required', 'string', 'max:255'],
            'email'                 => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'password'              => ['required', 'confirmed', Rules\Password::defaults()],
            'g-recaptcha-response'  => ['required', 'string'],
        ], [
            'g-recaptcha-response.required' => 'Debes completar el reCAPTCHA.',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
        ]);

        event(new Registered($user));

        $otp = $this->generateOtp();

        $user->update([
            'login_otp_code'       => Hash::make($otp),
            'login_otp_expires_at' => now()->addMinutes(10),
        ]);

        Mail::to($user->email)->send(new LoginOtpMail($otp, $user->name));

        $request->session()->put('login_pending_user_id', $user->id);
        $request->session()->put('login_pending_remember', false);

        return redirect()
            ->route('login.otp')
            ->with('status', 'Cuenta creada. Te enviamos un código a tu correo para verificar.');
    }

    private function generateOtp(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    private function validateRecaptcha(?string $token): void
    {
        $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret'   => config('services.recaptcha.secret_key'),
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