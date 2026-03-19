<?php

namespace App\Http\Controllers;

use App\Models\SupportCase;
use App\Models\CaseEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CaseController extends Controller
{
    public function store(Request $request)
    {
        $workspace = $request->attributes->get('workspace');

        $data = $request->validate([
            'title' => 'required|string|max:200',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:100',
            'priority' => 'nullable|in:low,normal,high,critical',
            'requesterPhone' => 'nullable|string|max:30',
            'requesterEmail' => 'nullable|email|max:200',
            'source' => 'nullable|string|max:30',
            'externalCallId' => 'nullable|string|max:200',
        ]);

        $case = SupportCase::create([
            'workspace_id' => $workspace->id,
            'case_number' => 'TC-' . now()->format('Ymd') . '-' . Str::upper(Str::random(6)),
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'category' => $data['category'] ?? null,
            'priority' => $data['priority'] ?? 'normal',
            'status' => 'new',
            'requester_phone' => $data['requesterPhone'] ?? null,
            'requester_email' => $data['requesterEmail'] ?? null,
            'source' => $data['source'] ?? 'voice',
            'external_call_id' => $data['externalCallId'] ?? null,
        ]);

        CaseEvent::create([
            'workspace_id' => $workspace->id,
            'support_case_id' => $case->id,
            'actor_user_id' => null,
            'type' => 'created',
            'data' => ['source' => $case->source],
        ]);

        return response()->json([
            'id' => $case->id,
            'caseNumber' => $case->case_number,
            'status' => $case->status,
        ], 201);
    }

    public function index(Request $request)
    {
        $workspace = $request->attributes->get('workspace');

        $items = SupportCase::where('workspace_id', $workspace->id)
            ->orderByDesc('id')
            ->limit(50)
            ->get(['id', 'case_number', 'title', 'status', 'priority', 'category', 'created_at']);

        return response()->json(['items' => $items]);
    }

    public function show(Request $request, $id)
    {
        $workspace = $request->attributes->get('workspace');

        $case = SupportCase::where('workspace_id', $workspace->id)
            ->where('id', $id)
            ->firstOrFail();

        return response()->json(['case' => $case]);
    }
}
