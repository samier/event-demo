<?php

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeEvent(array $overrides = []): Event
{
    return Event::factory()->for(User::factory())->create(array_merge([
        'type' => 'concert',
        'status' => 'published',
        'created_time' => now()->addDays(5)->timestamp,
        'latitude' => 51.5074,
        'longitude' => -0.1278,
    ], $overrides));
}

it('renders both visual pages with filter metadata', function () {
    foreach (['events.visual1', 'events.visual2'] as $route) {
        $this->get(route($route))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('cities')
                ->has('categories', 8)
            );
    }
});

it('returns an enriched, presentation-ready event from the feed', function () {
    $event = makeEvent([
        'payload' => [
            'name' => 'Synthwave Night',
            'description' => 'A neon evening.',
            'schedule' => ['starts_at' => now()->addDays(5)->timestamp, 'ends_at' => now()->addDays(5)->addHours(2)->timestamp],
            'pricing' => ['currency' => 'USD', 'min_price' => '49.50'],
            'venue' => ['name' => 'The Roundhouse', 'capacity' => '3000'],
        ],
    ]);

    $this->getJson(route('events.feed'))
        ->assertOk()
        ->assertJsonPath('data.0.id', $event->id)
        ->assertJsonPath('data.0.name', 'Synthwave Night')
        ->assertJsonPath('data.0.location.city', 'London')
        ->assertJsonPath('data.0.time.timezone', 'Europe/London')
        ->assertJsonPath('data.0.pricing.min_price', 49.5)
        ->assertJsonCount(3, 'data.0.images');
});

it('only serves locally-hosted images', function () {
    makeEvent();

    $images = $this->getJson(route('events.feed'))->json('data.0.images');

    foreach ($images as $url) {
        expect($url)->toStartWith('/images/events/');
    }
});

it('defaults to upcoming events only', function () {
    makeEvent(['created_time' => now()->subDays(10)->timestamp]); // past
    $upcoming = makeEvent(['created_time' => now()->addDays(3)->timestamp]);

    $this->getJson(route('events.feed'))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $upcoming->id);
});

it('filters by location using the city bounding box', function () {
    $london = makeEvent(['latitude' => 51.5074, 'longitude' => -0.1278]);
    makeEvent(['latitude' => 40.7128, 'longitude' => -74.0060]); // New York

    $this->getJson(route('events.feed', ['city' => 'London']))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $london->id);
});

it('filters by an explicit date range', function () {
    $inRange = makeEvent(['created_time' => now()->addDays(2)->timestamp]);
    makeEvent(['created_time' => now()->addDays(40)->timestamp]); // outside

    $this->getJson(route('events.feed', [
        'from' => now()->addDay()->toDateString(),
        'to' => now()->addDays(7)->toDateString(),
    ]))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $inRange->id);
});

it('hides draft events from the public feed', function () {
    makeEvent(['status' => 'draft']);

    $this->getJson(route('events.feed', ['when' => 'all']))
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

it('renders the event detail page with enriched data', function () {
    $event = makeEvent(['payload' => ['name' => 'Global Tech Summit', 'schedule' => ['starts_at' => now()->addDays(5)->timestamp, 'ends_at' => now()->addDays(5)->timestamp]]]);

    $this->get(route('events.show', $event))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Events/Show')
            ->where('event.id', $event->id)
            ->where('event.name', 'Global Tech Summit')
            ->where('event.location.city', 'London')
            ->where('backUrl', route('events.visual1'))
        );
});

it('returns to the agenda when opened from visual two', function () {
    $event = makeEvent();

    $this->get(route('events.show', ['event' => $event, 'from' => 'visual2']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('backUrl', route('events.visual2'))
        );
});
