<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    public function index(): View
    {
        $logs = ActivityLog::with('user')
            ->latest()
            ->limit(50)
            ->get();

        return view('log.index', compact('logs'));
    }
}
