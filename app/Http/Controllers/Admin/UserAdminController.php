<?php

namespace App\Http\Controllers\Admin;

use App\Concerns\PasswordValidationRules;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserAdminController extends Controller
{
    use PasswordValidationRules;

    public function index(): Response
    {
        return Inertia::render('admin/users-management', [
            'users' => User::query()
                ->with('roles:id,name')
                ->orderBy('name')
                ->get(['id', 'name', 'email', 'email_verified_at', 'created_at'])
                ->map(fn (User $user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'email_verified_at' => $user->email_verified_at,
                    'created_at' => $user->created_at,
                    'roles' => $user->roles->map(fn ($role) => ['id' => $role->id, 'name' => $role->name])->values(),
                ]),
            'roles' => Role::query()->orderBy('name')->get(['id', 'name']),
            'authUserId' => auth()->id(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'role' => ['nullable', 'string', 'exists:roles,name'],
            ...['password' => $this->passwordRules()],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        $role = $validated['role'] ?? null;
        $user->syncRoles($role ? [$role] : []);

        return back()->with('success', 'Usuario creado correctamente.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'role' => ['nullable', 'string', 'exists:roles,name'],
            'password' => ['nullable', 'string'],
            'password_confirmation' => ['nullable', 'same:password'],
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];

        if (! empty($validated['password'])) {
            $request->validate([
                'password' => $this->passwordRules(),
            ]);

            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        $role = $validated['role'] ?? null;
        $user->syncRoles($role ? [$role] : []);

        return back()->with('success', 'Usuario actualizado.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if (auth()->id() === $user->id) {
            return back()->with('error', 'No puedes eliminar tu propio usuario.');
        }

        $user->delete();

        return back()->with('success', 'Usuario eliminado.');
    }
}
