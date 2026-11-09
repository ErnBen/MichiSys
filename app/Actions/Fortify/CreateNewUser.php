<?php

namespace App\Actions\Fortify;

use App\Models\Business;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Laravel\Jetstream\Jetstream;
use Throwable;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'business_name' => ['required', 'string', 'max:255'],
            'responsible_name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'password' => $this->passwordRules(),
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature() ? ['accepted', 'required'] : '',
        ])->validate();

        $email = mb_strtolower(trim($input['email']));
        $logoPath = isset($input['logo']) ? $input['logo']->store('businesses', 'public') : null;

        try {
            return DB::transaction(function () use ($input, $email, $logoPath): User {
                $business = Business::create([
                    'name' => $input['business_name'],
                    'owner_name' => $input['responsible_name'],
                    'email' => $email,
                    'phone' => $input['phone'] ?? null,
                    'address' => $input['address'] ?? null,
                    'logo' => $logoPath,
                    'active' => true,
                ]);

                $user = $business->users()->create([
                    'name' => $input['name'],
                    'email' => $email,
                    'password' => Hash::make($input['password']),
                    'role' => 'admin',
                    'active' => true,
                ]);

                $this->createTeam($user);

                return $user;
            });
        } catch (Throwable $exception) {
            if ($logoPath) {
                Storage::disk('public')->delete($logoPath);
            }

            throw $exception;
        }
    }

    /**
     * Create a personal team for the user.
     */
    protected function createTeam(User $user): void
    {
        $user->ownedTeams()->save(Team::forceCreate([
            'user_id' => $user->id,
            'name' => explode(' ', $user->name, 2)[0]."'s Team",
            'personal_team' => true,
        ]));
    }
}
