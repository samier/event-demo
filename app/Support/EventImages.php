<?php

namespace App\Support;

use App\Console\Commands\GenerateEventImages;

/**
 * Resolves the local placeholder images for an event.
 *
 * The brief allows reusing a shared pool of placeholder files, and the dataset has
 * 1.25M events — materialising 2.5M+ image rows would add storage and seed time
 * without changing behaviour, since every event draws from the same pool. Instead
 * images are resolved deterministically from the event's id + category against a
 * locally-served pool (see {@see GenerateEventImages}). The
 * mapping is stable (same event → same images) and entirely local — no external or
 * hotlinked URLs.
 */
final class EventImages
{
    /** Number of distinct variants generated per category. */
    public const VARIANTS_PER_CATEGORY = 4;

    /** How many images each event is given (the brief asks for two or more). */
    public const IMAGES_PER_EVENT = 3;

    private const FALLBACK_CATEGORY = 'concert';

    private const CATEGORIES = [
        'concert', 'conference', 'meetup', 'workshop',
        'festival', 'sports', 'networking', 'exhibition',
    ];

    /**
     * Public URLs of the images for an event, served locally from /images/events.
     *
     * @return list<string>
     */
    public static function forEvent(string $id, string $category): array
    {
        $category = in_array($category, self::CATEGORIES, true) ? $category : self::FALLBACK_CATEGORY;

        // Derive a stable numeric seed from the UUID so the same event always maps
        // to the same images, with no storage required.
        $seed = (int) hexdec(substr(md5($id), 0, 8));

        $images = [];
        for ($i = 0; $i < self::IMAGES_PER_EVENT; $i++) {
            $variant = (($seed >> ($i * 3)) % self::VARIANTS_PER_CATEGORY) + 1;
            $images[] = "/images/events/{$category}-{$variant}.svg";
        }

        // Guarantee distinct images even when the modulo collides.
        $images = array_values(array_unique($images));
        $variant = 1;
        while (count($images) < self::IMAGES_PER_EVENT) {
            $candidate = "/images/events/{$category}-{$variant}.svg";
            if (! in_array($candidate, $images, true)) {
                $images[] = $candidate;
            }
            $variant++;
        }

        return $images;
    }

    /** @return list<string> */
    public static function categories(): array
    {
        return self::CATEGORIES;
    }
}
