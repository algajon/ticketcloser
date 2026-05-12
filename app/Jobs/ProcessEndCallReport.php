<?php

namespace App\Jobs;

use App\Models\Workspace;
use App\Services\Vapi\VapiCallSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessEndCallReport implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $tries = 3;

    public $backoff = [10, 30, 60];

    public function __construct(
        public Workspace $workspace,
        public array $payload
    ) {
    }

    public function handle(?VapiCallSyncService $callSync = null): void
    {
        $callSync ??= app(VapiCallSyncService::class);

        $callSync->syncFromWebhookPayload($this->workspace, $this->payload);
    }
}
