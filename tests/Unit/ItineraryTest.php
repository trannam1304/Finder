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

    public function testDynamicRealTimeTimelineCalculation(): void
    {
        require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
        $pdo = db();
        $result = generate_itinerary($pdo, 10.7326, 106.6996, 'half_day', 'chill', 8.0, '09:30');
        $this->assertArrayHasKey('stops', $result);
        $this->assertTrue(count($result['stops']) > 0);

        $firstStop = $result['stops'][0];
        $this->assertArrayHasKey('time_slot', $firstStop);
        $this->assertTrue(str_starts_with($firstStop['time_slot'], '09:30'), 'Chặng 1 phải bắt đầu chính xác từ thời gian thực 09:30');

        if (count($result['stops']) > 1) {
            $secondStop = $result['stops'][1];
            $this->assertTrue((bool) preg_match('/^[0-2][0-9]:[0-5][0-9] - [0-2][0-9]:[0-5][0-9]$/', $secondStop['time_slot']));
        }
    }
}
