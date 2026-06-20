<?php

namespace App\Services;

use App\Http\Resources\AttendeeResource;
use App\Http\Resources\EventResource;
use App\Models\Event;
use App\Support\EventImages;
use App\Support\Geocoder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
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
            'cities' => Geocoder::filterOptions(),
            'categories' => EventImages::categories(),
        ];
    }

    /**
     * Paginated, filtered event feed backing both visual pages.
     */
    public function paginatedFeed(Request $request): LengthAwarePaginator
    {
        $perPage = (int) min(48, max(6, (int) $request->input('per_page', 24)));

        return $this->buildQuery($request)
            ->withCount('attendees')
            ->orderBy('created_time')
            ->paginate($perPage)
            ->withQueryString();
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

        return Event::query()
            ->whereIn('status', self::PUBLIC_STATUSES)
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
            ->when($request->filled('city'), function (Builder $q) use ($request) {
                $box = Geocoder::boundingBoxForCity((string) $request->input('city'));
                if ($box !== null) {
                    [$minLat, $maxLat, $minLng, $maxLng] = $box;
                    $q->whereBetween('latitude', [$minLat, $maxLat])
                        ->whereBetween('longitude', [$minLng, $maxLng]);
                }
            })
            ->when($request->filled('country'), fn (Builder $q) => $this->filterByCountry($q, (string) $request->input('country')))
            ->when($request->filled('type'), fn (Builder $q) => $q->where('type', $request->input('type')))
            ->when($request->filled('q'), fn (Builder $q) => $q->where('payload->name', 'like', '%'.$request->input('q').'%'));
    }

    /**
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
}
