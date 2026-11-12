<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(ReportService $reports): View
    {
        return view('dashboard.index', ['overview' => $reports->overview()]);
    }
}
