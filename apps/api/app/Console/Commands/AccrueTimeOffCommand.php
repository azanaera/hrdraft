<?php

namespace App\Console\Commands;

use App\Domain\TimeOff\Services\TimeOffAccrualService;
use Illuminate\Console\Command;

class AccrueTimeOffCommand extends Command
{
    protected $signature = 'time-off:accrue {--dry-run : Report what would be posted without writing anything}';

    protected $description = 'Post scheduled time-off accrual ledger entries for every active policy/employment pair that is due.';

    public function handle(TimeOffAccrualService $accrual): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $posted = $accrual->run($dryRun);

        if (empty($posted)) {
            $this->info('Nothing due.');

            return self::SUCCESS;
        }

        foreach ($posted as $entry) {
            $verb = $dryRun ? 'Would post' : 'Posted';
            $this->line("{$verb}: {$entry['employee_number']} — {$entry['policy']} +{$entry['hours']}h");
        }

        $this->info(count($posted).' accrual entr'.(count($posted) === 1 ? 'y' : 'ies').($dryRun ? ' would be posted.' : ' posted.'));

        return self::SUCCESS;
    }
}
