<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

class BootstrapAdmin extends Command
{
    private const LOCK_NAME = 'portfolio:bootstrap-admin';

    protected $signature = 'portfolio:bootstrap-admin';

    protected $description = 'Create the initial portfolio administrator interactively';

    public function handle(): int
    {
        if (! $this->acquireBootstrapLock()) {
            $this->error('Administrator bootstrap is already in progress. No user was created.');

            return self::FAILURE;
        }

        try {
            if (User::query()->where('is_admin', true)->exists()) {
                $this->error('An administrator already exists. No user was created.');

                return self::FAILURE;
            }

            $name = text(
                label: 'Name',
                required: true,
                validate: fn (string $value): ?string => $this->validationMessage('name', trim($value), ['required', 'string', 'max:255']),
                transform: trim(...),
            );
            $email = text(
                label: 'Email',
                required: true,
                validate: fn (string $value): ?string => $this->validationMessage('email', trim($value), ['required', 'email:rfc', 'max:255']),
                transform: trim(...),
            );
            $newPassword = password(
                label: 'Password',
                required: true,
                validate: fn (string $value): ?string => $this->validationMessage('password', $value, ['required', 'string', 'min:12']),
            );
            $confirmation = password(label: 'Confirm password', required: true);

            if (! hash_equals($newPassword, $confirmation)) {
                $this->error('Password confirmation does not match. No user was created.');

                return self::FAILURE;
            }

            $created = DB::transaction(function () use ($name, $email, $newPassword): bool {
                if (User::query()->where('is_admin', true)->exists()) {
                    return false;
                }

                if (User::query()->where('email', $email)->exists()) {
                    return false;
                }

                $user = new User;
                $user->name = $name;
                $user->email = $email;
                $user->password = Hash::make($newPassword);
                $user->is_admin = true;
                $user->save();

                return true;
            });

            if (! $created) {
                $this->error('An administrator or user with that email already exists. No user was created.');

                return self::FAILURE;
            }

            $this->info('Administrator created.');

            return self::SUCCESS;
        } finally {
            $this->releaseBootstrapLock();
        }
    }

    private function acquireBootstrapLock(): bool
    {
        $result = DB::selectOne('SELECT GET_LOCK(?, 0) AS acquired', [self::LOCK_NAME]);

        return (int) $result->acquired === 1;
    }

    private function releaseBootstrapLock(): void
    {
        DB::selectOne('SELECT RELEASE_LOCK(?) AS released', [self::LOCK_NAME]);
    }

    /**
     * @param  list<string>  $rules
     */
    private function validationMessage(string $field, string $value, array $rules): ?string
    {
        $validator = Validator::make([$field => $value], [$field => $rules]);

        return $validator->errors()->first($field) ?: null;
    }
}
