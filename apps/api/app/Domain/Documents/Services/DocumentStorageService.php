<?php

namespace App\Domain\Documents\Services;

use App\Domain\Documents\Models\Document;
use App\Domain\Documents\Models\DocumentAcknowledgment;
use App\Domain\Documents\Models\DocumentVersion;
use App\Domain\Timeline\Services\TimelineRecorder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DocumentStorageService
{
    public function __construct(
        private readonly TimelineRecorder $timeline,
        private readonly SignatureProviderInterface $signatureProvider,
    ) {
    }

    /**
     * Stores a new document (or a new version of an existing one) on the
     * configured disk. Disk is env-driven (local in dev, s3 pre-wired) so
     * switching storage backends later is a config change, not a code change.
     */
    public function store(Model $documentable, int $categoryId, string $title, UploadedFile $file, ?Document $existing = null): Document
    {
        return DB::transaction(function () use ($documentable, $categoryId, $title, $file, $existing) {
            $document = $existing ?? Document::create([
                'documentable_type' => $documentable::class,
                'documentable_id' => $documentable->id,
                'category_id' => $categoryId,
                'title' => $title,
                'uploaded_by_user_id' => Auth::id(),
            ]);

            $disk = config('filesystems.default');
            $nextVersion = $document->versions()->max('version_number') + 1;
            $path = $file->store("documents/{$document->id}", $disk);

            $version = DocumentVersion::create([
                'document_id' => $document->id,
                'version_number' => $nextVersion,
                'disk' => $disk,
                'file_path' => $path,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'checksum' => hash_file('sha256', $file->getRealPath()),
                'uploaded_by_user_id' => Auth::id(),
            ]);

            $document->update(['current_version_id' => $version->id]);

            if (method_exists($documentable, 'person') || property_exists($documentable, 'person_id')) {
                $person = $documentable->person ?? null;
                if ($person) {
                    $this->timeline->record(
                        person: $person,
                        employment: $documentable,
                        eventType: 'document_uploaded',
                        summary: "Document \"{$title}\" uploaded (version {$nextVersion}).",
                        payload: ['document_id' => $document->id],
                    );
                }
            }

            return $document->fresh(['currentVersion', 'category']);
        });
    }

    public function download(DocumentVersion $version): string
    {
        return Storage::disk($version->disk)->path($version->file_path);
    }

    /**
     * Routes through the bound SignatureProviderInterface — a category
     * flagged requires_signature (offer letters, I-9s, handbook
     * acknowledgments) gets a real e-signature envelope rather than just a
     * typed-name/checkbox record, which isn't legally sufficient on its own.
     */
    public function acknowledge(Document $document, $employment, string $signatureType, ?string $signatureData, string $ip): DocumentAcknowledgment
    {
        $envelope = $document->category->requires_signature
            ? $this->signatureProvider->requestSignature(
                $document->title,
                $employment->person->fullName(),
                $employment->person->personal_email ?? '',
            )
            : null;

        $acknowledgment = DocumentAcknowledgment::updateOrCreate(
            ['document_id' => $document->id, 'employment_id' => $employment->id],
            [
                'acknowledged_at' => now(),
                'ip_address' => $ip,
                'signature_type' => $signatureType,
                'signature_data' => $signatureData,
                'signature_provider' => $envelope?->provider,
                'signature_envelope_id' => $envelope?->envelopeId,
                'signature_status' => $envelope?->status ?? 'signed',
            ],
        );

        $this->timeline->record(
            person: $employment->person,
            employment: $employment,
            eventType: 'document_acknowledged',
            summary: "Acknowledged \"{$document->title}\"".($envelope ? " via e-signature ({$envelope->provider}).": '.'),
            payload: ['document_id' => $document->id, 'envelope_id' => $envelope?->envelopeId],
        );

        return $acknowledgment;
    }
}
