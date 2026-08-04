<?php

use App\Domain\Documents\Models\Document;
use App\Domain\Documents\Models\DocumentCategory;
use App\Domain\Employee\Models\Employment;
use App\Models\User;

it('routes acknowledgment of a signature-required document through the signature provider', function () {
    $this->actingAs(User::factory()->admin()->create());

    $category = DocumentCategory::factory()->create(['requires_signature' => true]);
    $employment = Employment::factory()->create();
    $document = Document::create([
        'documentable_type' => Employment::class,
        'documentable_id' => $employment->id,
        'category_id' => $category->id,
        'title' => 'Employee Handbook',
    ]);

    $response = $this->postJson("/api/v1/employees/{$employment->id}/documents/{$document->id}/acknowledge", [
        'signature_type' => 'typed',
        'signature_data' => 'Jane Doe',
    ]);

    $response->assertCreated();
    $ack = $employment->fresh();
    $record = \App\Domain\Documents\Models\DocumentAcknowledgment::where('document_id', $document->id)->first();
    expect($record->signature_provider)->toBe('fake');
    expect($record->signature_envelope_id)->not->toBeNull();
    expect($record->signature_status)->toBe('signed');
});

it('does not create a signature envelope for a document that does not require one', function () {
    $this->actingAs(User::factory()->admin()->create());

    $category = DocumentCategory::factory()->create(['requires_signature' => false]);
    $employment = Employment::factory()->create();
    $document = Document::create([
        'documentable_type' => Employment::class,
        'documentable_id' => $employment->id,
        'category_id' => $category->id,
        'title' => 'Generic Note',
    ]);

    $this->postJson("/api/v1/employees/{$employment->id}/documents/{$document->id}/acknowledge", [
        'signature_type' => 'checkbox',
    ])->assertCreated();

    $record = \App\Domain\Documents\Models\DocumentAcknowledgment::where('document_id', $document->id)->first();
    expect($record->signature_envelope_id)->toBeNull();
});
