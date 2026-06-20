<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboard) {}

    /**
     * A lightweight overview centred on attendee activity, so registrations are
     * visible in one place rather than only per-event.
     */
    public function index(): Response
    {
        return Inertia::render('Dashboard', $this->dashboard->pageData());
    }
}
