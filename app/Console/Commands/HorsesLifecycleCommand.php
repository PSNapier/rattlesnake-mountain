<?php

namespace App\Console\Commands;

use App\Services\LifecycleAgingService;
use Illuminate\Console\Command;

class HorsesLifecycleCommand extends Command
{
    protected $signature = 'horses:lifecycle
                            {--dry-run : Preview aging and death proposals without writing}
                            {--force : Run even if next update date is in the future}';

    protected $description = 'Age public horses and create NPC death proposals when due';

    public function handle(LifecycleAgingService $service): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');
        $mode = $dryRun ? 'preview' : 'scheduled';

        try {
            $result = $service->run(
                mode: $mode,
                dryRun: $dryRun,
                force: $force,
            );
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        if (! $result['ran']) {
            $this->info($result['reason'] ?? 'Lifecycle did not run.');

            return self::SUCCESS;
        }

        $this->info(sprintf(
            '%s: aged=%d proposed=%d survived=%d skipped=%d next_update=%s',
            $dryRun ? 'Dry run' : 'Lifecycle run',
            $result['aged_count'],
            $result['proposed_count'],
            $result['survived_count'],
            $result['skipped_count'],
            $result['next_update'] ?? 'n/a',
        ));

        return self::SUCCESS;
    }
}
