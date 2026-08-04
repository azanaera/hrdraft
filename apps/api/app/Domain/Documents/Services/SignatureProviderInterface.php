<?php

namespace App\Domain\Documents\Services;

interface SignatureProviderInterface
{
    /**
     * Sends a document for legally-binding e-signature (ESIGN Act-compliant
     * in a real provider). A real implementation would call DocuSign/
     * HelloSign here and return a "sent" envelope, with the actual "signed"
     * status arriving later via webhook.
     */
    public function requestSignature(string $documentTitle, string $signerName, string $signerEmail): SignatureEnvelopeResult;
}
