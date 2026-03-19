<?php

$presets = \App\Models\AssistantPreset::all();
$count = 0;

foreach ($presets as $p) {
    if (!$p->vapi_payload_json)
        continue;
    $data = $p->vapi_payload_json;
    if (isset($data['startSpeakingPlan']['smartEndpointingPlan']['type']) && $data['startSpeakingPlan']['smartEndpointingPlan']['type'] === 'livekit') {
        $data['startSpeakingPlan']['smartEndpointingPlan'] = ['provider' => 'livekit'];
        $p->vapi_payload_json = $data;
        $p->save();
        echo "Patched preset: {$p->key}" . PHP_EOL;
        $count++;
    } elseif (isset($data['startSpeakingPlan']['smartEndpointingPlan']) && is_string($data['startSpeakingPlan']['smartEndpointingPlan'])) {
        $data['startSpeakingPlan']['smartEndpointingPlan'] = ['provider' => $data['startSpeakingPlan']['smartEndpointingPlan']];
        $p->vapi_payload_json = $data;
        $p->save();
        echo "Patched preset string: {$p->key}" . PHP_EOL;
        $count++;
    }
}

echo "Patched {$count} presets." . PHP_EOL;
