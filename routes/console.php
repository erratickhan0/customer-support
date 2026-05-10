<?php

use App\Jobs\ProcessKnowledgeDocumentJob;
use App\Models\KnowledgeDocument;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('knowledge:reprocess {--agency=} {--status=*}', function (): int {
    $agencyId = $this->option('agency');
    /** @var array<int, string> $statuses */
    $statuses = array_values(array_filter((array) $this->option('status')));

    $query = KnowledgeDocument::query()->select('id');

    if ($agencyId !== null && $agencyId !== '') {
        $query->where('agency_id', (int) $agencyId);
    }

    if ($statuses !== []) {
        $query->whereIn('status', $statuses);
    }

    $count = 0;
    $query->orderBy('id')->chunkById(100, function ($documents) use (&$count): void {
        foreach ($documents as $document) {
            ProcessKnowledgeDocumentJob::dispatch($document->id)->onQueue('ai');
            $count++;
        }
    });

    $this->info("Queued {$count} knowledge document(s) for reprocessing.");

    return self::SUCCESS;
})->purpose('Queue knowledge document reprocessing jobs');

Schedule::command('knowledge:reprocess --status=failed')
    ->hourly()
    ->withoutOverlapping();
