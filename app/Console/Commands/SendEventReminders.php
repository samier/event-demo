<?php

namespace App\Console\Commands;

use App\Mail\EventReminder;
use App\Models\Event;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Mail;

/**
 * Sends "the event is approaching" reminders to attendees.
 *
 * Two non-overlapping windows are handled: 3 days before and 24 hours before.
 * Per-attendee timestamp columns (reminded_3d_at / reminded_24h_at) make the
 * command idempotent — it can run as often as you like and will never double-send.
 * Scheduled hourly in routes/console.php.
 *
 * Options make it easy to verify/trigger reminders by hand:
 *   --event=UUID   only this event
 *   --window=...   3-day | 24-hour | both (default: both)
 *   --force        ignore the time window and the "already sent" guard (resend)
 *   --pretend      show who would be emailed without sending or marking anything
 */
class SendEventReminders extends Command
{
    protected $signature = 'events:send-reminders
        {--event= : Limit to a single event id}
        {--window=both : Which window to send (3-day, 24-hour, or both)}
        {--force : Ignore the time window and resend even if already sent}
        {--pretend : List who would be emailed without sending}';

    protected $description = 'Email attendees a reminder 3 days and 24 hours before their event';

    private const DAY = 86400;

    public function handle(): int
    {
        $now = time();
        $pretend = (bool) $this->option('pretend');
        $force = (bool) $this->option('force');
        $eventId = $this->option('event');
        $window = (string) $this->option('window');

        if (! in_array($window, ['both', '3-day', '24-hour'], true)) {
            $this->error("Invalid --window '{$window}'. Use 3-day, 24-hour or both.");

            return self::FAILURE;
        }

        /** @var list<array{window: '3-day'|'24-hour', column: string, from: int, to: int}> $windows */
        $windows = [
            ['window' => '3-day', 'column' => 'reminded_3d_at', 'from' => $now + self::DAY, 'to' => $now + (3 * self::DAY)],
            ['window' => '24-hour', 'column' => 'reminded_24h_at', 'from' => $now, 'to' => $now + self::DAY],
        ];

        $total = 0;
        /** @var list<array{0: string, 1: string, 2: string}> $sample */
        $sample = [];

        foreach ($windows as $w) {
            if ($window !== 'both' && $window !== $w['window']) {
                continue;
            }

            $this->processWindow($w, $eventId, $force, $pretend, $total, $sample);
        }

        if ($total === 0) {
            $this->info($pretend ? 'No reminders are currently due.' : 'No reminders to send.');

            return self::SUCCESS;
        }

        $this->table(['Event', 'Attendee', 'Window'], $sample);
        if ($total > count($sample)) {
            $this->line('  … and '.($total - count($sample)).' more.');
        }

        $verb = $pretend ? 'Would send' : 'Sent';
        $this->info("{$verb} {$total} reminder email(s).");

        return self::SUCCESS;
    }

    /**
     * Stream the due attendees for one window in chunks, sending as we go so we never
     * hold the whole due-set in memory. Only a capped sample is kept for the summary.
     *
     * @param  array{window: '3-day'|'24-hour', column: string, from: int, to: int}  $w
     * @param  list<array{0: string, 1: string, 2: string}>  $sample
     */
    private function processWindow(array $w, ?string $eventId, bool $force, bool $pretend, int &$total, array &$sample): void
    {
        $column = $w['column'];
        $windowLabel = $w['window'];

        Event::query()
            ->when($eventId, fn (Builder $q) => $q->whereKey($eventId))
            // --force ignores both the time window and the "already sent" guard.
            ->unless($force, fn (Builder $q) => $q->whereBetween('created_time', [$w['from'], $w['to']]))
            ->whereHas('attendees', fn (Builder $q) => $force ? $q : $q->whereNull($column))
            ->with(['attendees' => fn ($q) => $force ? $q : $q->whereNull($column)])
            ->chunkById(200, function ($events) use ($column, $windowLabel, $pretend, &$total, &$sample) {
                foreach ($events as $event) {
                    foreach ($event->attendees as $attendee) {
                        if (! $pretend) {
                            Mail::to($attendee->email)->send(new EventReminder($event, $attendee, $windowLabel));
                            $attendee->forceFill([$column => now()])->save();
                        }

                        $total++;
                        if (count($sample) < 50) {
                            $sample[] = [$event->name(), $attendee->email, $windowLabel];
                        }
                    }
                }
            });
    }
}
