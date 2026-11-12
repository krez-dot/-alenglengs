<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(Request $request, ReportService $reports): View
    {
        $data = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ], [
            'date_from.date' => 'The "from" date is not a valid date.',
            'date_to.date' => 'The "to" date is not a valid date.',
            'date_to.after_or_equal' => 'The "to" date cannot be earlier than the "from" date.',
        ]);

        $to = isset($data['date_to']) ? CarbonImmutable::parse($data['date_to']) : CarbonImmutable::today();
        $from = isset($data['date_from']) ? CarbonImmutable::parse($data['date_from']) : $to->subDays(29);

        return view('reports.index', ['report' => $reports->report($from, $to)]);
    }
}
