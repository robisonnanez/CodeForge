<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
        ])->validate();

        $usernameBase = Str::slug($input['name']);
        $usernameBase = $usernameBase !== '' ? Str::limit($usernameBase, 52, '') : 'user';
        $username = $usernameBase;
        $suffix = 1;

        while (User::query()->where('username', $username)->exists()) {
            $suffix++;
            $username = "{$usernameBase}-{$suffix}";
        }

        return User::create([
            'name' => $input['name'],
            'username' => $username,
            'email' => $input['email'],
            'password' => $input['password'],
        ]);
    }
}
