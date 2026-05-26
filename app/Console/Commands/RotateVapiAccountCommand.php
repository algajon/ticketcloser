<?php

namespace App\Console\Commands;

use App\Models\AssistantConfig;
use App\Models\WorkspacePhoneNumber;
use App\Services\Vapi\VapiProvisioningService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class RotateVapiAccountCommand extends Command
{
    protected $signature = 'vapi:rotate-account
        {workspaceSlug? : Optional workspace slug to limit the rotation}
        {--force : Execute the cutover instead of running a dry run}';

    protected $description = 'Rotate stored Vapi assistant, tool, and phone bindings onto the currently configured Vapi account.';

    public function handle(VapiProvisioningService $provisioner): int
    {
        if (! filled(config('services.vapi.key'))) {
            $this->error('VAPI_API_KEY is not configured for the current environment.');

            return self::FAILURE;
        }

        $workspaceSlug = $this->argument('workspaceSlug');

        $assistants = AssistantConfig::query()
            ->with('workspace')
            ->when($workspaceSlug, function ($query, string $slug) {
                $query->whereHas('workspace', fn ($workspaceQuery) => $workspaceQuery->where('slug', $slug));
            })
            ->orderBy('workspace_id')
            ->orderBy('id')
            ->get();

        if ($assistants->isEmpty()) {
            $this->error('No matching assistants found for the requested scope.');

            return self::FAILURE;
        }

        $phoneNumbers = WorkspacePhoneNumber::query()
            ->with(['workspace', 'assistant'])
            ->when($workspaceSlug, function ($query, string $slug) {
                $query->whereHas('workspace', fn ($workspaceQuery) => $workspaceQuery->where('slug', $slug));
            })
            ->orderBy('workspace_id')
            ->orderBy('id')
            ->get();

        $warnings = [];

        if ($assistants->contains(fn (AssistantConfig $assistant) => $assistant->voice_provider === 'azure')) {
            $warnings[] = 'One or more assistants use Azure voices. The new Vapi account must already have the matching Azure provider keys configured.';
        }

        if ($phoneNumbers->contains(fn (WorkspacePhoneNumber $phoneNumber) => filled($phoneNumber->vapi_credential_id))) {
            $warnings[] = 'Some phone records reference Vapi BYO credential IDs. Those credential IDs are account-specific and must exist in the new Vapi account.';
        }

        if ($phoneNumbers->contains(fn (WorkspacePhoneNumber $phoneNumber) => $phoneNumber->provisioning_mode === null)) {
            $warnings[] = 'Legacy phone records with a blank provisioning mode will be treated as vapi_instant during rotation.';
        }

        $this->line('Vapi account rotation summary');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Workspace scope', $workspaceSlug ?: 'all workspaces'],
                ['Assistants to recreate', (string) $assistants->count()],
                ['Phone records to rebind', (string) $phoneNumbers->count()],
                ['Phone records with current Vapi IDs', (string) $phoneNumbers->whereNotNull('vapi_phone_number_id')->where('vapi_phone_number_id', '!=', '')->count()],
            ]
        );

        foreach ($warnings as $warning) {
            $this->warn($warning);
        }

        if (! $this->option('force')) {
            $this->info('Dry run only. Re-run with --force to execute the rotation.');

            return self::SUCCESS;
        }

        $backupPath = $this->writeBackup($workspaceSlug, $assistants, $phoneNumbers);
        $this->info("Backup written to storage/app/{$backupPath}");

        $assistantOutcomes = [];
        $phoneOutcomes = [];

        foreach ($assistants as $assistant) {
            $workspace = $assistant->workspace;

            if (! $workspace) {
                $assistantOutcomes[] = [
                    'workspace' => 'unknown',
                    'assistant' => (string) $assistant->id,
                    'status' => 'failed',
                    'message' => 'Missing workspace relationship.',
                ];
                continue;
            }

            try {
                $assistant->forceFill([
                    'vapi_tool_id' => null,
                    'vapi_booking_tool_id' => null,
                    'vapi_lookup_tool_id' => null,
                    'vapi_case_lookup_tool_id' => null,
                    'vapi_assistant_id' => null,
                ])->save();

                $provisioner->provisionAssistantAndToolForConfig($assistant, $workspace, [
                    'name' => $assistant->name,
                    'first_message' => $assistant->first_message,
                    'system_prompt' => $assistant->system_prompt,
                    'voice_provider' => $assistant->voice_provider,
                    'voice_id' => $assistant->voice_id,
                    'language_code' => $assistant->language_code,
                    'model_name' => $assistant->model_name,
                    'preset_key' => $assistant->preset_key,
                    'override_params' => $assistant->override_params,
                    'intake_params' => $assistant->intake_params,
                    'is_active' => $assistant->is_active,
                ]);

                $assistantOutcomes[] = [
                    'workspace' => $workspace->slug,
                    'assistant' => $assistant->name,
                    'status' => 'rotated',
                    'message' => 'Assistant and tools recreated.',
                ];
            } catch (\Throwable $e) {
                $assistantOutcomes[] = [
                    'workspace' => $workspace->slug,
                    'assistant' => $assistant->name,
                    'status' => 'failed',
                    'message' => $e->getMessage(),
                ];
            }
        }

        foreach ($phoneNumbers as $phoneNumber) {
            $workspace = $phoneNumber->workspace;

            if (! $workspace) {
                $phoneOutcomes[] = [
                    'workspace' => 'unknown',
                    'phone_record' => (string) $phoneNumber->id,
                    'status' => 'failed',
                    'message' => 'Missing workspace relationship.',
                ];
                continue;
            }

            if (! $phoneNumber->assistant_id) {
                $phoneOutcomes[] = [
                    'workspace' => $workspace->slug,
                    'phone_record' => (string) $phoneNumber->id,
                    'status' => 'skipped',
                    'message' => 'No assistant is attached to this phone record.',
                ];
                continue;
            }

            try {
                $mode = $phoneNumber->provisioning_mode ?: 'vapi_instant';
                $input = [
                    'assistant_id' => $phoneNumber->assistant_id,
                    'provisioning_mode' => $mode,
                    'external_provider' => $phoneNumber->external_provider,
                    'forwarding_number' => $phoneNumber->forwarding_number,
                ];

                if (filled($phoneNumber->vapi_credential_id)) {
                    $input['vapi_credential_id'] = $phoneNumber->vapi_credential_id;
                }

                if ($mode === 'existing_business_number' && filled($phoneNumber->vapi_phone_number_id)) {
                    $input['auto_forwarding_target'] = 1;
                }

                $provisioner->provisionPhoneNumber($workspace, $input);

                $phoneOutcomes[] = [
                    'workspace' => $workspace->slug,
                    'phone_record' => (string) $phoneNumber->id,
                    'status' => 'rotated',
                    'message' => 'Phone binding recreated or refreshed.',
                ];
            } catch (\Throwable $e) {
                $phoneOutcomes[] = [
                    'workspace' => $workspace->slug,
                    'phone_record' => (string) $phoneNumber->id,
                    'status' => 'failed',
                    'message' => $e->getMessage(),
                ];
            }
        }

        $this->newLine();
        $this->info('Assistant outcomes');
        $this->table(['Workspace', 'Assistant', 'Status', 'Message'], $assistantOutcomes);

        $this->newLine();
        $this->info('Phone outcomes');
        $this->table(['Workspace', 'Phone record', 'Status', 'Message'], $phoneOutcomes);

        $assistantFailures = collect($assistantOutcomes)->where('status', 'failed')->count();
        $phoneFailures = collect($phoneOutcomes)->where('status', 'failed')->count();

        if ($assistantFailures > 0 || $phoneFailures > 0) {
            $this->warn("Rotation finished with {$assistantFailures} assistant failures and {$phoneFailures} phone failures.");

            return self::FAILURE;
        }

        $this->info('Rotation finished successfully.');

        return self::SUCCESS;
    }

    private function writeBackup(?string $workspaceSlug, $assistants, $phoneNumbers): string
    {
        $timestamp = now()->format('Ymd-His');
        $scope = $workspaceSlug ?: 'all';
        $path = "vapi-rotations/{$timestamp}-{$scope}.json";

        $payload = [
            'generated_at' => now()->toIso8601String(),
            'workspace_scope' => $workspaceSlug,
            'assistants' => $assistants->map(function (AssistantConfig $assistant) {
                return [
                    'id' => $assistant->id,
                    'workspace_id' => $assistant->workspace_id,
                    'name' => $assistant->name,
                    'vapi_tool_id' => $assistant->vapi_tool_id,
                    'vapi_booking_tool_id' => $assistant->vapi_booking_tool_id,
                    'vapi_lookup_tool_id' => $assistant->vapi_lookup_tool_id,
                    'vapi_case_lookup_tool_id' => $assistant->vapi_case_lookup_tool_id,
                    'vapi_assistant_id' => $assistant->vapi_assistant_id,
                ];
            })->values()->all(),
            'phone_numbers' => $phoneNumbers->map(function (WorkspacePhoneNumber $phoneNumber) {
                return [
                    'id' => $phoneNumber->id,
                    'workspace_id' => $phoneNumber->workspace_id,
                    'assistant_id' => $phoneNumber->assistant_id,
                    'provisioning_mode' => $phoneNumber->provisioning_mode,
                    'external_provider' => $phoneNumber->external_provider,
                    'forwarding_number' => $phoneNumber->forwarding_number,
                    'e164' => $phoneNumber->e164,
                    'vapi_credential_id' => $phoneNumber->vapi_credential_id,
                    'vapi_phone_number_id' => $phoneNumber->vapi_phone_number_id,
                    'is_active' => $phoneNumber->is_active,
                ];
            })->values()->all(),
        ];

        Storage::disk('local')->put($path, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return $path;
    }
}
