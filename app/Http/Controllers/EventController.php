<?php

namespace App\Http\Controllers;

use App\Http\Resources\AttendeeResource;
use App\Http\Resources\EventResource;
use App\Models\Event;
use App\Support\EventImages;
use App\Support\Geocoder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    /** Statuses that should appear in the public-facing visual listings. */
    private const PUBLIC_STATUSES = ['published', 'sold_out', 'cancelled'];

    /** Event Visuals 1 — card-grid gallery. */
    public function visualOne(): Response
    {
        return Inertia::render('Events/VisualOne', $this->filterMeta());
    }

    /** Event Visuals 2 — agenda timeline. */
    public function visualTwo(): Response
    {
        return Inertia::render('Events/VisualTwo', $this->filterMeta());
    }

    /**
     * JSON feed backing both visual pages. Supports filtering by date range,
     * location (city / country), category, status and free-text search.
     */
    public function feed(Request $request): JsonResponse
    {
        $perPage = (int) min(48, max(6, (int) $request->input('per_page', 24)));

        $events = $this->buildQuery($request)
            ->withCount('attendees')
            ->orderBy('created_time')
            ->paginate($perPage)
            ->withQueryString();

        // Passing the paginator (not just its items) lets the resource emit the
        // standard `meta` / `links` pagination payload automatically.
        return EventResource::collection($events)->response();
    }

    public function show(Request $request, Event $event): Response
    {
        $event->loadCount('attendees')
            ->load(['user', 'attendees' => fn ($q) => $q->latest()->limit(100)]);

        return Inertia::render('Events/Show', [
            'event' => (new EventResource($event))->resolve(),
            'attendees' => AttendeeResource::collection($event->attendees)->resolve(),
            'backUrl' => $this->backUrlFor($request->query('from')),
        ]);
    }

    private function backUrlFor(?string $from): string
    {
        return match ($from) {
            'visual2', 'events-visual-2' => route('events.visual2'),
            default => route('events.visual1'),
        };
    }

    /**
     * Apply the date + location + category filters shared by the feed.
     *
     * @return Builder<Event>
     */
    private function buildQuery(Request $request): Builder
    {
        $now = time();

        return Event::query()
            ->whereIn('status', self::PUBLIC_STATUSES)
            // --- Date filtering -------------------------------------------------
            // `created_time` is a UTC unix timestamp, so compare against the UTC
            // start/end of the requested day.
            ->when($request->filled('from'), function (Builder $q) use ($request) {
                $q->where('created_time', '>=', $request->date('from')->startOfDay()->getTimestamp());
            })
            ->when($request->filled('to'), function (Builder $q) use ($request) {
                $q->where('created_time', '<=', $request->date('to')->endOfDay()->getTimestamp());
            })
            // Default to upcoming events when no explicit date range is given.
            ->when(! $request->filled('from') && ! $request->filled('to') && $request->input('when', 'upcoming') === 'upcoming',
                fn (Builder $q) => $q->where('created_time', '>=', $now))
            ->when($request->input('when') === 'past', fn (Builder $q) => $q->where('created_time', '<', $now))
            // --- Location filtering --------------------------------------------
            ->when($request->filled('city'), function (Builder $q) use ($request) {
                $box = Geocoder::boundingBoxForCity((string) $request->input('city'));
                if ($box !== null) {
                    [$minLat, $maxLat, $minLng, $maxLng] = $box;
                    $q->whereBetween('latitude', [$minLat, $maxLat])
                        ->whereBetween('longitude', [$minLng, $maxLng]);
                }
            })
            ->when($request->filled('country'), fn (Builder $q) => $this->filterByCountry($q, (string) $request->input('country')))
            // --- Category / search ---------------------------------------------
            ->when($request->filled('type'), fn (Builder $q) => $q->where('type', $request->input('type')))
            ->when($request->filled('q'), fn (Builder $q) => $q->where('payload->name', 'like', '%'.$request->input('q').'%'));
    }

    /**
     * Filter to a country by OR-ing the bounding boxes of every seeded city in
     * that country. Runs against the (latitude, longitude) index.
     *
     * @param  Builder<Event>  $query
     * @return Builder<Event>
     */
    private function filterByCountry(Builder $query, string $countryCode): Builder
    {
        $boxes = collect(Geocoder::filterOptions())
            ->where('country_code', $countryCode)
            ->map(fn (array $c) => Geocoder::boundingBoxForCity($c['city']))
            ->filter()
            ->values();

        if ($boxes->isEmpty()) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($boxes) {
            foreach ($boxes as $box) {
                [$minLat, $maxLat, $minLng, $maxLng] = $box;
                $q->orWhere(function (Builder $inner) use ($minLat, $maxLat, $minLng, $maxLng) {
                    $inner->whereBetween('latitude', [$minLat, $maxLat])
                        ->whereBetween('longitude', [$minLng, $maxLng]);
                });
            }
        });
    }

    /**
     * Filter options shared by both visual pages.
     *
     * @return array<string, mixed>
     */
    private function filterMeta(): array
    {
        return [
            'cities' => Geocoder::filterOptions(),
            'categories' => EventImages::categories(),
        ];
    }
}
