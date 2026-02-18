<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;

class AuditLogController extends Controller
{
    public function index()
    {
        $logs = AuditLog::orderByDesc('created_at')
            ->limit(300)
            ->get();

        return view('audit_log.index', compact('logs'));
    }
}
