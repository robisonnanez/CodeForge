<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ImpersonationController extends Controller
{
    public function store(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()?->hasRole('super-admin'), 403);

        if ($request->user()->is($user)) {
            return back()->with('error', 'Ya estas autenticado con este usuario.');
        }

        $impersonator = $request->user();
        activity('security')
            ->causedBy($impersonator)
            ->withProperties([
                'target_user_id' => $user->id,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'result' => 'allowed',
            ])
            ->log('authentication.impersonation.started');

        $request->session()->put('impersonator_id', $impersonator->id);
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('success', "Ahora estas navegando como {$user->name}.");
    }

    public function destroy(Request $request): RedirectResponse
    {
        $impersonatorId = $request->session()->pull('impersonator_id');

        if (! $impersonatorId) {
            return redirect()->route('dashboard');
        }

        $impersonator = User::query()->findOrFail($impersonatorId);
        $impersonatedUser = $request->user();

        Auth::login($impersonator);
        $request->session()->regenerate();

        activity('security')
            ->causedBy($impersonator)
            ->withProperties([
                'target_user_id' => $impersonatedUser?->id,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'result' => 'allowed',
            ])
            ->log('authentication.impersonation.ended');

        return redirect()->route('admin.users-permissions.index')->with('success', 'Volviste a la sesion del super-admin.');
    }
}
