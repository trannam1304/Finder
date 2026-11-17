<?php
declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;

require_once dirname(__DIR__, 2) . '/app/services/itinerary.php';

class ItineraryTest extends TestCase
{
    public function testItineraryTemplateGeneratesCorrectSlots(): void
    {
        $durations = ['evening', 'half_day', 'full_day'];
        $vibes = ['foodie', 'date', 'chill', 'adventure'];

        foreach ($durations as $duration) {
            foreach ($vibes as $vibe) {
                $template = itinerary_template($duration, $vibe);
                $this->assertTrue(is_array($template), "Template for $duration:$vibe must be an array");
                $this->assertTrue(count($template) >= 2, "Template for $duration:$vibe must have at least 2 steps");

                foreach ($template as $step) {
                    $this->assertArrayHasKey('step', $step);
                    $this->assertArrayHasKey('title', $step);
                    $this->assertArrayHasKey('time_slot', $step);
                    $this->assertArrayHasKey('place_type', $step);
                    $this->assertArrayHasKey('activity_note', $step);
                }
            }
        }
    }

    public function testItineraryMetaProvidesTitleAndPacing(): void
    {
        $meta = itinerary_meta('half_day', 'chill');
        $this->assertArrayHasKey('title', $meta);
        $this->assertArrayHasKey('duration_label', $meta);
        $this->assertArrayHasKey('vibe_label', $meta);
        $this->assertTrue(strlen($meta['title']) > 5);
    }

    public function testBuildGoogleMapsDirectionsUrl(): void
    {
        $startLat = 10.7326;
        $startLng = 106.6996;
        $stops = [
            ['place' => ['name' => 'Stop 1', 'lat' => 10.7400, 'lng' => 106.7050]],
            ['place' => ['name' => 'Stop 2', 'lat' => 10.7500, 'lng' => 106.7100]],
        ];

        $url = build_google_maps_directions_url($startLat, $startLng, $stops);

        $this->assertStringContainsString('https://www.google.com/maps/dir/', $url);
        $this->assertStringContainsString('origin=10.732600,106.699600', $url);
        $this->assertStringContainsString('destination=10.750000,106.710000', $url);
        $this->assertStringContainsString('waypoints=10.740000%2C106.705000', $url);
    }
}
