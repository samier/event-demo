<?php

namespace App\Http\Resources;

use App\Models\Attendee;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * @mixin Attendee
 */
class AttendeeResource extends JsonResource
{
    /**
     * Public-facing shape for an attendee. The email is masked so the attendee
     * list can be shown without exposing full addresses.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            // Public name is first name + last initial; the full name is never
            // exposed to other visitors.
            'name' => $this->displayName(),
            'initials' => $this->initials(),
            'email_masked' => $this->maskEmail($this->email),
            'registered_at' => $this->created_at?->toIso8601String(),
        ];
    }

    private function maskEmail(string $email): string
    {
        [$user, $domain] = array_pad(explode('@', $email, 2), 2, '');
        // Reveal the first two characters and use a fixed-length mask so the
        // exact local-part length isn't leaked.
        $masked = Str::substr($user, 0, 2).'•••';

        return $domain !== '' ? "{$masked}@{$domain}" : $masked;
    }
}
