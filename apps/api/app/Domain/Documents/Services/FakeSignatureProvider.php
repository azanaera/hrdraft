<?php

namespace App\Domain\Documents\Services;

use Illuminate\Support\Str;

/**
 * Local stand-in for a real e-signature provider (DocuSign, HelloSign/
 * Dropbox Sign). Simulates an instant "sent -> signed" round trip since
 * there's no webhook infrastructure locally. Bound in AppServiceProvider —
 * swapping in a real provider is a single binding change.
 */
class FakeSignatureProvider implements SignatureProviderInterface
{
    public function requestSignature(string $documentTitle, string $signerName, string $signerEmail): SignatureEnvelopeResult
    {
        return new SignatureEnvelopeResult(
            provider: 'fake',
            envelopeId: 'fake_env_'.Str::random(24),
            status: 'signed',
        );
    }
}
