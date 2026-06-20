<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property int $user_id
 * @property string $type
 * @property string $status
 * @property int|null $created_time
 * @property float|null $latitude
 * @property float|null $longitude
 * @property array<string, mixed> $payload
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User|null $user
 * @property-read Collection<int, Attendee> $attendees
 * @property-read int|null $attendees_count
 */
class Event extends Model
{
    /** @use HasFactory<EventFactory> */
    use HasFactory, HasUuids;

    protected $guarded = [];

    protected $casts = [
        'payload' => 'array',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<Attendee, $this>
     */
    public function attendees(): HasMany
    {
        return $this->hasMany(Attendee::class);
    }

    /**
     * Event start, as a UTC instant. `created_time` is the canonical start time
     * (a unix timestamp) and mirrors payload.schedule.starts_at.
     */
    public function startsAt(): CarbonImmutable
    {
        return CarbonImmutable::createFromTimestampUTC((int) $this->created_time);
    }

    public function endsAt(): ?CarbonImmutable
    {
        $ends = $this->payload['schedule']['ends_at'] ?? null;

        return $ends !== null ? CarbonImmutable::createFromTimestampUTC((int) $ends) : null;
    }

    public function name(): string
    {
        return (string) ($this->payload['name'] ?? 'Untitled Event');
    }
}
