<?php

use App\Domain\Employee\Models\Employment;
use App\Models\User;

it('lets an admin create a location with a minimum wage', function () {
    $this->actingAs(User::factory()->admin()->create());

    $response = $this->postJson('/api/v1/admin/locations', [
        'name' => 'Phoenix Hub', 'code' => 'PHX-01', 'city' => 'Phoenix', 'state' => 'AZ', 'minimum_wage' => 14.35,
    ]);

    $response->assertCreated();
    $response->assertJsonPath('data.code', 'PHX-01');
});

it('blocks an employee-role user from creating admin config', function () {
    $employment = Employment::factory()->create();
    $user = User::factory()->create(['role' => 'employee', 'employment_id' => $employment->id]);
    $this->actingAs($user);

    $response = $this->postJson('/api/v1/admin/locations', [
        'name' => 'Phoenix Hub', 'code' => 'PHX-02',
    ]);

    $response->assertStatus(403);
});

it('lets an admin create a time off policy visible on the public policies list', function () {
    $this->actingAs(User::factory()->admin()->create());

    $this->postJson('/api/v1/admin/time-off-policies', [
        'name' => 'Bereavement', 'applies_to' => 'all', 'accrual_method' => 'none', 'accrual_rate' => 0,
    ])->assertCreated();

    $response = $this->getJson('/api/v1/time-off/policies');
    $response->assertOk();
    expect(collect($response->json('data'))->pluck('name'))->toContain('Bereavement');
});
