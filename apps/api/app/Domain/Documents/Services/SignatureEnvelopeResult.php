<?php

namespace App\Domain\Documents\Services;

class SignatureEnvelopeResult
{
    public function __construct(
        public readonly string $provider,
        public readonly string $envelopeId,
        public readonly string $status,
    ) {
    }
}
