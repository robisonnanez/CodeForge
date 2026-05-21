<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Spatie\LaravelPasskeys\Actions\FindPasskeyToAuthenticateAction;
use Spatie\LaravelPasskeys\Actions\GeneratePasskeyAuthenticationOptionsAction;
use Spatie\LaravelPasskeys\Actions\GeneratePasskeyRegisterOptionsAction;
use Spatie\LaravelPasskeys\Actions\StorePasskeyAction;
use Spatie\LaravelPasskeys\Models\Passkey;

class PasskeyController extends Controller
{
    public function authenticationOptions(): JsonResponse
    {
        $options = app(GeneratePasskeyAuthenticationOptionsAction::class)->execute();
        Session::put('passkey-authentication-options', $options);

        return response()->json(json_decode($options, true));
    }

    public function authenticate(Request $request): RedirectResponse
    {
        $request->validate([
            'start_authentication_response' => ['required', 'json'],
            'remember' => ['nullable', 'boolean'],
        ]);

        $passkeyOptions = Session::pull('passkey-authentication-options');

        if (! $passkeyOptions) {
            return back()->withErrors(['passkey' => 'No se pudo validar la passkey. Intenta nuevamente.']);
        }

        $passkey = app(FindPasskeyToAuthenticateAction::class)->execute(
            $request->string('start_authentication_response')->toString(),
            $passkeyOptions,
        );

        if (! $passkey || ! $passkey->authenticatable) {
            return back()->withErrors(['passkey' => 'Passkey invalida o no registrada.']);
        }

        auth()->login($passkey->authenticatable, $request->boolean('remember'));
        Session::regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function registerOptions(Request $request): JsonResponse
    {
        $user = $request->user();
        $options = app(GeneratePasskeyRegisterOptionsAction::class)->execute($user);
        Session::put('passkey-registration-options', $options);

        return response()->json(json_decode($options, true));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'passkey' => ['required', 'json'],
        ]);

        $options = Session::pull('passkey-registration-options');

        if (! $options) {
            return back()->withErrors(['passkey' => 'Primero genera una nueva solicitud de registro de passkey.']);
        }

        app(StorePasskeyAction::class)->execute(
            $request->user(),
            $request->string('passkey')->toString(),
            $options,
            $request->getHost(),
            ['name' => $request->string('name')->toString()],
        );

        return back()->with('status', 'Passkey registrada correctamente.');
    }

    public function destroy(Request $request, Passkey $passkey): RedirectResponse
    {
        abort_unless($passkey->authenticatable_id === $request->user()->id, 403);

        $passkey->delete();

        return back()->with('status', 'Passkey eliminada.');
    }
}