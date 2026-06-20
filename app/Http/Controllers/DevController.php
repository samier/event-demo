<?php

namespace App\Http\Controllers;

use App\Services\DevEmailActivityService;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Local-only developer/testing tools. Registered only in the local environment
 * (see routes/web.php). The email-activity page lets a reviewer see, at a glance,
 * how many confirmation and reminder emails have gone out — and trigger or preview
 * them — without waiting days or reading the log file.
 */
class DevController extends Controller
{
    public function __construct(private readonly DevEmailActivityService $emailActivity) {}

    public function emailActivity(): Response
    {
        return Inertia::render('Dev/EmailActivity', $this->emailActivity->pageData());
    }
}
