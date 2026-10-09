<?php

namespace App\Console\Commands;

use App\Models\PlatformUser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class CreatePlatformAdminCommand extends Command
{
    protected $signature = 'platform-admin:create';

    protected $description = 'Interactively create an active landlord Super Admin account';

    public function handle(): int
    {
        if (! $this->input->isInteractive()) {
            $this->error('This command requires an interactive terminal so passwords are not passed as arguments.');

            return self::FAILURE;
        }

        $name = trim((string) $this->ask('Name'));
        $email = strtolower(trim((string) $this->ask('Email')));
        $password = (string) $this->secret('Password (minimum 12 characters)');
        $passwordConfirmation = (string) $this->secret('Confirm password');

        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $passwordConfirmation,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:12', 'confirmed'],
        ]);

        if (PlatformUser::query()->where('email', $email)->exists()) {
            $validator->errors()->add('email', 'A platform user already uses this email address.');
        }

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        PlatformUser::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'is_active' => true,
        ]);

        $this->info("Created active platform administrator [{$email}].");

        return self::SUCCESS;
    }
}
