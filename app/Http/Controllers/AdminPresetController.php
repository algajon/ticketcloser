<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AssistantPreset;

class AdminPresetController extends Controller
{
    public function index()
    {
        $presets = AssistantPreset::all();
        return view('admin.presets.index', compact('presets'));
    }

    public function edit(AssistantPreset $preset)
    {
        return view('admin.presets.edit', compact('preset'));
    }

    public function update(Request $request, AssistantPreset $preset)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'notes' => 'nullable|string',
            'vapi_payload_json' => 'required|json',
        ]);

        $json = json_decode($data['vapi_payload_json'], true);

        $preset->update([
            'name' => $data['name'],
            'notes' => $data['notes'] ?? '',
            'vapi_payload_json' => $json,
        ]);

        return redirect()->route('admin.presets.index')->with('success', 'Preset updated.');
    }
}
