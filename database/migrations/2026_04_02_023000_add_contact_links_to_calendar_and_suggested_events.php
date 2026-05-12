<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('suggested_events', function (Blueprint $table) {
            if (! Schema::hasColumn('suggested_events', 'contact_id')) {
                $table->foreignId('contact_id')->nullable()->after('case_id')->constrained('contacts')->nullOnDelete();
                $table->index(['workspace_id', 'contact_id'], 'suggested_events_workspace_contact_idx');
            }
        });

        Schema::table('calendar_events', function (Blueprint $table) {
            if (! Schema::hasColumn('calendar_events', 'contact_id')) {
                $table->foreignId('contact_id')->nullable()->after('case_id')->constrained('contacts')->nullOnDelete();
                $table->index(['workspace_id', 'contact_id'], 'calendar_events_workspace_contact_idx');
            }
        });

        $caseContacts = \App\Models\SupportCase::query()
            ->whereNotNull('contact_id')
            ->pluck('contact_id', 'id');

        if ($caseContacts->isNotEmpty()) {
            \App\Models\SuggestedEvent::query()
                ->whereNull('contact_id')
                ->get()
                ->each(function (\App\Models\SuggestedEvent $event) use ($caseContacts) {
                    $contactId = $caseContacts->get($event->case_id);

                    if ($contactId) {
                        $event->forceFill(['contact_id' => $contactId])->save();
                    }
                });

            \App\Models\CalendarEvent::query()
                ->whereNull('contact_id')
                ->get()
                ->each(function (\App\Models\CalendarEvent $event) use ($caseContacts) {
                    $contactId = $caseContacts->get($event->case_id);

                    if ($contactId) {
                        $event->forceFill(['contact_id' => $contactId])->save();
                    }
                });
        }
    }

    public function down(): void
    {
        Schema::table('calendar_events', function (Blueprint $table) {
            if (Schema::hasColumn('calendar_events', 'contact_id')) {
                $table->dropIndex('calendar_events_workspace_contact_idx');
                $table->dropConstrainedForeignId('contact_id');
            }
        });

        Schema::table('suggested_events', function (Blueprint $table) {
            if (Schema::hasColumn('suggested_events', 'contact_id')) {
                $table->dropIndex('suggested_events_workspace_contact_idx');
                $table->dropConstrainedForeignId('contact_id');
            }
        });
    }
};
