<?php

namespace App\Console\Commands;

use App\Support\EventImages;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Generates the local placeholder image pool used by every event.
 *
 * The artwork is modern "mesh gradient" cover art — several large, blurred colour
 * blobs over a deep base, with film grain, a vignette for depth, and a refined
 * category label. Each category has its own vibrant palette and each variant a
 * different composition, so an event's gallery feels designed rather than generic.
 *
 * Output is committed to the repo (public/images/events/*.svg) so the app works out
 * of the box; this command keeps it reproducible. SVG keeps the assets tiny, crisp at
 * any size, and free of binary/licensing/network dependencies.
 */
class GenerateEventImages extends Command
{
    protected $signature = 'app:generate-event-images {--force : Overwrite existing files}';

    protected $description = 'Generate the local SVG placeholder pool served for event images';

    private const WIDTH = 1200;

    private const HEIGHT = 800;

    /**
     * Per-category palette: [base (deepest), c1, c2, c3 (brightest), label].
     *
     * @var array<string, array{0: string, 1: string, 2: string, 3: string, 4: string}>
     */
    private const PALETTES = [
        'concert' => ['#1a0b2e', '#7c3aed', '#ec4899', '#f9a8d4', 'Concert'],
        'conference' => ['#05223b', '#0ea5e9', '#22d3ee', '#7dd3fc', 'Conference'],
        'meetup' => ['#3a1c02', '#f59e0b', '#fb923c', '#fcd34d', 'Meetup'],
        'workshop' => ['#04231c', '#10b981', '#34d399', '#a7f3d0', 'Workshop'],
        'festival' => ['#3b0a36', '#db2777', '#f97316', '#fbbf24', 'Festival'],
        'sports' => ['#06281a', '#16a34a', '#84cc16', '#bef264', 'Sports'],
        'networking' => ['#161438', '#6366f1', '#818cf8', '#c7d2fe', 'Networking'],
        'exhibition' => ['#04302d', '#14b8a6', '#2dd4bf', '#5eead4', 'Exhibition'],
    ];

    /**
     * Blob compositions per variant: each is three [cx, cy, radius] placements. The
     * colours are rotated per variant (see svg()) so all four feel distinct.
     *
     * @var list<list<array{0: int, 1: int, 2: int}>>
     */
    private const LAYOUTS = [
        [[300, 250, 560], [980, 200, 470], [760, 760, 520]],
        [[1000, 260, 540], [220, 560, 500], [640, 120, 430]],
        [[200, 680, 540], [1040, 640, 520], [620, 180, 470]],
        [[640, 420, 660], [200, 160, 440], [1060, 520, 470]],
    ];

    public function handle(): int
    {
        $dir = public_path('images/events');
        File::ensureDirectoryExists($dir);

        $count = 0;

        foreach (self::PALETTES as $category => $palette) {
            for ($variant = 1; $variant <= EventImages::VARIANTS_PER_CATEGORY; $variant++) {
                $path = "{$dir}/{$category}-{$variant}.svg";

                if (File::exists($path) && ! $this->option('force')) {
                    continue;
                }

                File::put($path, $this->svg($palette, $variant));
                $count++;
            }
        }

        $this->info("Generated {$count} placeholder image(s) in public/images/events.");

        return self::SUCCESS;
    }

    /**
     * @param  array{0: string, 1: string, 2: string, 3: string, 4: string}  $palette
     */
    private function svg(array $palette, int $variant): string
    {
        [$base, $c1, $c2, $c3, $label] = $palette;
        $w = self::WIDTH;
        $h = self::HEIGHT;

        // Rotate the colour assignment per variant so each composition differs.
        $orders = [[$c1, $c2, $c3], [$c2, $c3, $c1], [$c3, $c1, $c2], [$c1, $c3, $c2]];
        $colors = $orders[$variant - 1];
        $layout = self::LAYOUTS[$variant - 1];

        $blobs = '';
        foreach ($layout as $i => [$cx, $cy, $r]) {
            $blobs .= "<circle cx=\"{$cx}\" cy=\"{$cy}\" r=\"{$r}\" fill=\"{$colors[$i]}\"/>";
        }

        // A soft top light source for a touch of depth.
        $glowX = (int) ($w * 0.5);

        $eyebrow = strtoupper($label);

        return <<<SVG
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {$w} {$h}" preserveAspectRatio="xMidYMid slice" role="img" aria-label="{$label} event cover">
          <defs>
            <filter id="soft" x="-30%" y="-30%" width="160%" height="160%">
              <feGaussianBlur stdDeviation="90" />
            </filter>
            <filter id="grain">
              <feTurbulence type="fractalNoise" baseFrequency="0.9" numOctaves="2" stitchTiles="stitch" />
              <feColorMatrix type="saturate" values="0" />
              <feComponentTransfer><feFuncA type="linear" slope="0.6" /></feComponentTransfer>
            </filter>
            <radialGradient id="glow" cx="50%" cy="8%" r="65%">
              <stop offset="0%" stop-color="#ffffff" stop-opacity="0.28" />
              <stop offset="100%" stop-color="#ffffff" stop-opacity="0" />
            </radialGradient>
            <radialGradient id="vignette" cx="50%" cy="42%" r="78%">
              <stop offset="52%" stop-color="#000000" stop-opacity="0" />
              <stop offset="100%" stop-color="#000000" stop-opacity="0.6" />
            </radialGradient>
          </defs>

          <rect width="{$w}" height="{$h}" fill="{$base}" />
          <g filter="url(#soft)">{$blobs}</g>
          <ellipse cx="{$glowX}" cy="0" rx="{$w}" ry="360" fill="url(#glow)" />
          <rect width="{$w}" height="{$h}" filter="url(#grain)" opacity="0.1" />
          <rect width="{$w}" height="{$h}" fill="url(#vignette)" />

          <g font-family="ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, sans-serif" fill="#ffffff">
            <rect x="64" y="636" width="34" height="3" rx="1.5" fill="#ffffff" opacity="0.85" />
            <text x="110" y="642" font-size="22" font-weight="600" letter-spacing="6" opacity="0.85">{$eyebrow}</text>
            <text x="64" y="704" font-size="56" font-weight="800" letter-spacing="-1">Live &amp; in person</text>
          </g>
        </svg>
        SVG;
    }
}
