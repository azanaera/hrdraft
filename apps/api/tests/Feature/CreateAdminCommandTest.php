<?php

use App\Models\User;

it('creates a real admin user via flags, with a hashed password', function () {
    $this->artisan('hris:create-admin', [
        '--name' => 'Real Admin',
        '--email' => 'real.admin@example.com',
        '--password' => 'a-genuinely-long-password',
    ])->assertExitCode(0);

    $admin = User::where('email', 'real.admin@example.com')->firstOrFail();
    expect($admin->role)->toBe('admin');
    expect($admin->is_active)->toBeTrue();
    expect($admin->employment_id)->toBeNull();
    expect(\Illuminate\Support\Facades\Hash::check('a-genuinely-long-password', $admin->password))->toBeTrue();
});

it('refuses a duplicate email', function () {
    User::factory()->admin()->create(['email' => 'taken@example.com']);

    $this->artisan('hris:create-admin', [
        '--name' => 'Another Admin',
        '--email' => 'taken@example.com',
        '--password' => 'a-genuinely-long-password',
    ])->assertExitCode(1);
});

it('refuses a short password', function () {
    $this->artisan('hris:create-admin', [
        '--name' => 'Short Pass',
        '--email' => 'short@example.com',
        '--password' => 'tooshort',
    ])->assertExitCode(1);

    expect(User::where('email', 'short@example.com')->exists())->toBeFalse();
});
