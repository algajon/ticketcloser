<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('support_cases', function (Blueprint $table) {
            if (! Schema::hasColumn('support_cases', 'ops_stage')) {
                $table->string('ops_stage', 40)->nullable()->after('status');
            }

            if (! Schema::hasColumn('support_cases', 'access_notes')) {
                $table->text('access_notes')->nullable()->after('structured_payload');
            }

            if (! Schema::hasColumn('support_cases', 'preferred_visit_window')) {
                $table->string('preferred_visit_window', 160)->nullable()->after('access_notes');
            }

            if (! Schema::hasColumn('support_cases', 'vendor_name')) {
                $table->string('vendor_name', 120)->nullable()->after('preferred_visit_window');
            }

            if (! Schema::hasColumn('support_cases', 'vendor_phone')) {
                $table->string('vendor_phone', 30)->nullable()->after('vendor_name');
            }

            if (! $this->hasIndex('support_cases', 'support_cases_workspace_ops_stage_index')) {
                $table->index(['workspace_id', 'ops_stage'], 'support_cases_workspace_ops_stage_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('support_cases', function (Blueprint $table) {
            if ($this->hasIndex('support_cases', 'support_cases_workspace_ops_stage_index')) {
                $table->dropIndex('support_cases_workspace_ops_stage_index');
            }

            $columns = array_values(array_filter([
                Schema::hasColumn('support_cases', 'ops_stage') ? 'ops_stage' : null,
                Schema::hasColumn('support_cases', 'access_notes') ? 'access_notes' : null,
                Schema::hasColumn('support_cases', 'preferred_visit_window') ? 'preferred_visit_window' : null,
                Schema::hasColumn('support_cases', 'vendor_name') ? 'vendor_name' : null,
                Schema::hasColumn('support_cases', 'vendor_phone') ? 'vendor_phone' : null,
            ]));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }

    private function hasIndex(string $table, string $index): bool
    {
        $driver = DB::connection()->getDriverName();

        $results = match ($driver) {
            'sqlite' => DB::select("PRAGMA index_list('{$table}')"),
            'pgsql' => DB::select(
                'SELECT indexname AS name FROM pg_indexes WHERE schemaname = current_schema() AND tablename = ?',
                [$table]
            ),
            default => DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$index]),
        };

        if ($driver === 'sqlite' || $driver === 'pgsql') {
            return collect($results)->contains(function ($result) use ($index) {
                $name = is_object($result) ? ($result->name ?? null) : ($result['name'] ?? null);

                return $name === $index;
            });
        }

        return $results !== [];
    }
};
