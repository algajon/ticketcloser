<?php
use App\Models\SupportCase;
use App\Models\AssistantConfig;

$ac = AssistantConfig::first();
if ($ac) {
    SupportCase::whereNull('assistant_config_id')->update(['assistant_config_id' => $ac->id]);
    echo "Updated null cases to assistant " . $ac->id . "\n";
} else {
    echo "No assistant config found\n";
}
