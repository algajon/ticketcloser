<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class OnboardingController extends Controller
{
    private function workspaceOrFail(Request $request)
    {
        $workspace = $request->user()->currentWorkspace();
        abort_if(!$workspace, 403, 'No workspace found for user.');
        return $workspace;
    }

    public function company(Request $request)
    {
        $workspace = $this->workspaceOrFail($request);
        return view('onboarding.company', compact('workspace'));
    }

    public function saveCompany(Request $request)
    {
        $workspace = $this->workspaceOrFail($request);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:80|alpha_dash',
            'default_timezone' => 'required|string|max:80',
            'case_label' => 'required|string|max:40',
        ]);

        // ensure slug unique
        if ($data['slug'] !== $workspace->slug && \App\Models\Workspace::where('slug', $data['slug'])->exists()) {
            return back()->withErrors(['slug' => 'This slug is already taken.'])->withInput();
        }

        $workspace->update($data + ['onboarding_step' => 'done']);

        return redirect()->route('app.billing.plans');
    }
}
