<?php

use App\Http\Controllers\AttendeeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DevController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\MailPreviewController;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/events-visual-1')->name('home');

// JSON feed backing both visual pages (date + location + category filtering).
Route::get('events/feed', [EventController::class, 'feed'])->name('events.feed');

// Two distinct browsing experiences.
Route::get('events-visual-1', [EventController::class, 'visualOne'])->name('events.visual1');
Route::get('events-visual-2', [EventController::class, 'visualTwo'])->name('events.visual2');

Route::get('events/{event}', [EventController::class, 'show'])->name('events.show');

// Register interest / attendance for an event.
Route::post('events/{event}/attendees', [AttendeeController::class, 'store'])->name('events.attendees.store');

Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

// Local-only developer tools: an email-activity dashboard plus in-browser previews
// of the confirmation and reminder emails.
if (App::environment('local')) {
    Route::get('dev/emails', [DevController::class, 'emailActivity'])->name('dev.emails');
    Route::get('dev/emails/{event}/confirmation', [MailPreviewController::class, 'confirmation'])->name('dev.emails.confirmation');
    Route::get('dev/emails/{event}/reminder/{window}', [MailPreviewController::class, 'reminder'])
        ->whereIn('window', ['3-day', '24-hour'])
        ->name('dev.emails.reminder');
}

require __DIR__.'/settings.php';
