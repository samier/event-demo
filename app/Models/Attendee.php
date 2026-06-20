<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $event_id
 * @property string $name
 * @property string $email
 * @property Carbon|null $confirmation_sent_at
 * @property Carbon|null $reminded_3d_at
 * @property Carbon|null $reminded_24h_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Event|null $event
 */
class Attendee extends Model
{
    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'confirmation_sent_at' => 'datetime',
            'reminded_3d_at' => 'datetime',
            'reminded_24h_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Privacy-friendly public name: first name + last initial, e.g. "Ada L.".
     * Used anywhere an attendee is shown publicly (the event attendee list).
     */
    public function displayName(): string
    {
        $parts = $this->nameParts();
        $first = $parts[0] ?? $this->name;
        $lastInitial = count($parts) > 1 ? Str::substr((string) end($parts), 0, 1).'.' : '';

        return trim("{$first} {$lastInitial}");
    }

    /** Avatar initials from the full name, e.g. "Ada Lovelace" → "AL". */
    public function initials(): string
    {
        $parts = $this->nameParts();
        $first = Str::substr($parts[0] ?? '', 0, 1);
        $last = count($parts) > 1 ? Str::substr((string) end($parts), 0, 1) : '';

        return Str::upper($first.$last) ?: '?';
    }

    /** @return list<string> */
    private function nameParts(): array
    {
        return array_values(array_filter(preg_split('/\s+/', trim($this->name)) ?: []));
    }
}
