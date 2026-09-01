<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

/**
 * Bootstraps the first real admin account for a real deployment.
 *
 * DemoDataSeeder creates a "System Admin" with a known password ("password")
 * for local dev — that account must never exist in an environment real
 * employees use. This command is the alternative: it creates one real admin
 * user, interactively or via flags, without touching demo/seed data at all.
 */
class CreateAdminCommand extends Command
{
    protected $signature = 'hris:create-admin {--name=} {--email=} {--password= : If omitted, you will be prompted (hidden input)}';

    protected $description = 'Create a real admin user — the non-demo alternative to DemoDataSeeder for a real deployment.';

    public function handle(): int
    {
        $name = $this->option('name') ?: $this->ask('Name');
        $email = $this->option('email') ?: $this->ask('Email');
        $password = $this->option('password') ?: $this->secret('Password (hidden, min 12 characters)');

        $validator = Validator::make(
            ['name' => $name, 'email' => $email, 'password' => $password],
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'unique:users,email'],
                'password' => ['required', 'string', 'min:12'],
            ]
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'role' => 'admin',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->info("Admin user created: {$email}");

        return self::SUCCESS;
    }
}
