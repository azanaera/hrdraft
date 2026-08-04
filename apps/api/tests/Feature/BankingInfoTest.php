<?php

use App\Domain\Employee\Models\Employment;
use App\Models\User;

it('tokenizes banking info and never stores the raw account number', function () {
    $this->actingAs(User::factory()->admin()->create());

    $employment = Employment::factory()->create();

    $response = $this->postJson("/api/v1/employees/{$employment->id}/banking-info", [
        'routing_number' => '021000021',
        'account_number' => '1234567890',
        'account_type' => 'checking',
    ]);

    $response->assertCreated();
    $response->assertJsonPath('data.account_last_four', '7890');
    $response->assertJsonPath('data.verified', true);

    $raw = \Illuminate\Support\Facades\DB::table('employment_banking_info')->first();
    expect(collect((array) $raw)->keys()->all())->not->toContain('account_number', 'routing_number');
    expect($raw->external_token)->toStartWith('fake_tok_');
});

it('lets an employee view (but not require back-office to submit) their own banking info', function () {
    $employment = Employment::factory()->create();
    $user = User::factory()->create(['role' => 'employee', 'employment_id' => $employment->id]);
    $this->actingAs($user);

    $response = $this->postJson("/api/v1/employees/{$employment->id}/banking-info", [
        'routing_number' => '021000021',
        'account_number' => '1234567890',
        'account_type' => 'savings',
    ]);

    $response->assertCreated();
});
