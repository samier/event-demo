<?php

namespace App\Services;

use App\Http\Resources\AttendeeResource;
use App\Http\Resources\EventResource;
use App\Models\Event;
use App\Models\CityAnchor;
use App\Support\EventImages;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class EventService
{
    /** Statuses that should appear in the public-facing visual listings. */
    public const PUBLIC_STATUSES = ['published', 'sold_out', 'cancelled'];

    /**
     * Filter options shared by both visual pages.
     *
     * @return array<string, mixed>
     */
    public function filterMeta(): array
    {
        return [
            'cities' => CityAnchor::filterOptions(),
            'categories' => EventImages::categories(),
        ];
    }

    /**
     * Paginated, filtered event feed backing both visual pages.
     */
    public function paginatedFeed(Request $request): LengthAwarePaginator|Paginator
    {
        $perPage = (int) min(48, max(6, (int) $request->input('per_page', 24)));

        $query = $this->buildQuery($request)
            ->withCount('attendees')
            ->orderBy('created_time');

        if ($this->usesSimplePagination($request)) {
            return $query->simplePaginate($perPage)->withQueryString();
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Skip the expensive COUNT(*) on filters that would scan large portions of
     * the 1.25M-row table. The client uses links.next for infinite scroll.
     */
    private function usesSimplePagination(Request $request): bool
    {
        return $request->filled('city')
            || $request->filled('country')
            || $request->filled('q');
    }

    /**
     * @return array<string, mixed>
     */
    public function showPageData(Event $event, ?string $from): array
    {
        $event->loadCount('attendees')
            ->load(['user', 'attendees' => fn ($q) => $q->latest()->limit(100)]);

        return [
            'event' => (new EventResource($event))->resolve(),
            'attendees' => AttendeeResource::collection($event->attendees)->resolve(),
            'backUrl' => $this->backUrlFor($from),
        ];
    }

    public function backUrlFor(?string $from): string
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
    public function buildQuery(Request $request): Builder
    {
        $now = time();

        $query = Event::query()->whereIn('status', self::PUBLIC_STATUSES);

        // Apply location predicates first so MySQL can use the location listing index.
        if ($request->filled('city')) {
            $box = CityAnchor::boundingBoxForCity((string) $request->input('city'));
            if ($box !== null) {
                [$minLat, $maxLat, $minLng, $maxLng] = $box;
                $query->whereBetween('latitude', [$minLat, $maxLat])
                    ->whereBetween('longitude', [$minLng, $maxLng]);
            }
        } elseif ($request->filled('country')) {
            $query = $this->filterByCountry($query, (string) $request->input('country'));
        }

        return $query
            ->when($request->filled('from'), function (Builder $q) use ($request) {
                $q->where('created_time', '>=', $request->date('from')->startOfDay()->getTimestamp());
            })
            ->when($request->filled('to'), function (Builder $q) use ($request) {
                $q->where('created_time', '<=', $request->date('to')->endOfDay()->getTimestamp());
            })
            ->when(
                ! $request->filled('from') && ! $request->filled('to') && $request->input('when', 'upcoming') === 'upcoming',
                fn (Builder $q) => $q->where('created_time', '>=', $now),
            )
            ->when($request->input('when') === 'past', fn (Builder $q) => $q->where('created_time', '<', $now))
            ->when($request->filled('type'), fn (Builder $q) => $q->where('type', $request->input('type')))
            ->when($request->filled('q'), fn (Builder $q) => $q->where('payload->name', 'like', '%'.$request->input('q').'%'));
    }

    /**
     * @param  Builder<Event>  $query
     * @return Builder<Event>
     */
    private function filterByCountry(Builder $query, string $countryCode): Builder
    {
        $boxes = collect(CityAnchor::filterOptions())
            ->where('country_code', $countryCode)
            ->map(fn (array $c) => CityAnchor::boundingBoxForCity($c['city']))
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
}
