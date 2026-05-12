<?php

namespace App\Console\Commands;

use App\Models\Workspace;
use App\Services\Contacts\ContactLinkingService;
use Illuminate\Console\Command;

class ConsolidateContactsCommand extends Command
{
    protected $signature = 'contacts:consolidate {workspaceSlug? : Optional workspace slug to limit the repair}';

    protected $description = 'Consolidate duplicate contacts by phone number and remove weak transcript-fragment names.';

    public function handle(ContactLinkingService $contacts): int
    {
        $workspaceSlug = $this->argument('workspaceSlug');

        $workspaces = Workspace::query()
            ->when($workspaceSlug, fn ($query) => $query->where('slug', $workspaceSlug))
            ->get();

        if ($workspaces->isEmpty()) {
            $this->error('No matching workspace found.');

            return self::FAILURE;
        }

        $merged = 0;

        foreach ($workspaces as $workspace) {
            $count = $contacts->repairWorkspaceContacts($workspace);
            $merged += $count;
            $this->info("{$workspace->slug}: merged {$count} duplicate contacts.");
        }

        $this->info("Done. Total merged duplicates: {$merged}.");

        return self::SUCCESS;
    }
}
