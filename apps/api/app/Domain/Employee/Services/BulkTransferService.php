<?php

namespace App\Domain\Employee\Services;

use App\Domain\Employee\Models\Employment;

class BulkTransferService
{
    public function __construct(private readonly TransferService $transferService)
    {
    }

    /**
     * Transfers many employments at once (e.g. a shift consolidation or
     * location closure). Each employee's transfer is its own DB transaction
     * (via TransferService::transfer) so one bad employment_id doesn't roll
     * back everyone else — instead we collect per-employee success/failure
     * so nothing silently gets lost.
     */
    public function transferMany(array $employmentIds, array $transferData): array
    {
        $succeeded = [];
        $failed = [];

        foreach ($employmentIds as $employmentId) {
            try {
                $employment = Employment::findOrFail($employmentId);
                $this->transferService->transfer($employment, $transferData);
                $succeeded[] = $employmentId;
            } catch (\Throwable $e) {
                $failed[] = ['employment_id' => $employmentId, 'error' => $e->getMessage()];
            }
        }

        return ['succeeded' => $succeeded, 'failed' => $failed];
    }
}
