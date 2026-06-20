<?php

use App\Http\Controllers\MailPreviewController;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function previewEvent(): Event
{
    return Event::factory()->for(User::factory())->create([
        'status' => 'published',
        'created_time' => now()->addDays(5)->timestamp,
        'payload' => ['name' => 'Synthwave Night', 'schedule' => ['ends_at' => now()->addDays(5)->timestamp]],
    ]);
}

it('renders the confirmation email preview', function () {
    $html = (new MailPreviewController)->confirmation(previewEvent())->getContent();

    expect($html)->toContain('Synthwave Night')->toContain('on the list');
});

it('renders both reminder windows', function () {
    $event = previewEvent();

    expect((new MailPreviewController)->reminder($event, '3-day')->getContent())->toContain('3 days');
    expect((new MailPreviewController)->reminder($event, '24-hour')->getContent())->toContain('tomorrow');
});
