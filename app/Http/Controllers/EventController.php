<?php

namespace App\Http\Controllers;

use App\Http\Resources\EventResource;
use App\Models\Event;
use App\Services\EventService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    public function __construct(private readonly EventService $events) {}

    /** Event Visuals 1 — card-grid gallery. */
    public function visualOne(): Response
    {
        return Inertia::render('Events/VisualOne', $this->events->filterMeta());
    }

    /** Event Visuals 2 — agenda timeline. */
    public function visualTwo(): Response
    {
        return Inertia::render('Events/VisualTwo', $this->events->filterMeta());
    }

    /**
     * JSON feed backing both visual pages. Supports filtering by date range,
     * location (city / country), category, status and free-text search.
     */
    public function feed(Request $request): JsonResponse
    {
        $events = $this->events->paginatedFeed($request);

        return EventResource::collection($events)->response();
    }

    public function show(Request $request, Event $event): Response
    {
        return Inertia::render('Events/Show', $this->events->showPageData(
            $event,
            $request->query('from'),
        ));
    }
}
