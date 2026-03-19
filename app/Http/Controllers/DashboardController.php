<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $workspace = $request->user()->currentWorkspace();
        return view('dashboard', compact('workspace'));
    }
}
