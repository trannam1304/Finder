<?php
declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;

require_once dirname(__DIR__, 2) . '/app/services/geo.php';

class GeoTest extends TestCase
{
    public function testBoundingBoxCalculations(): void
    {
        $lat = 10.7326;
        $lng = 106.6996;
        $radiusKm = 5.0;

        $box = bounding_box($lat, $lng, $radiusKm);

        $this->assertArrayHasKey('lat_min', $box);
        $this->assertArrayHasKey('lat_max', $box);
        $this->assertArrayHasKey('lng_min', $box);
        $this->assertArrayHasKey('lng_max', $box);

        $this->assertTrue($box['lat_min'] < $lat);
        $this->assertTrue($box['lat_max'] > $lat);
        $this->assertTrue($box['lng_min'] < $lng);
        $this->assertTrue($box['lng_max'] > $lng);

        // Kiểm tra tính đối xứng của bounding box quanh tâm
        $dLatMin = abs($lat - $box['lat_min']);
        $dLatMax = abs($box['lat_max'] - $lat);
        $this->assertTrue(abs($dLatMin - $dLatMax) < 0.0001);
    }

    public function testIsOpenNow24h(): void
    {
        $row24h = ['is_24h' => 1, 'open_time' => null, 'close_time' => null];
        $this->assertTrue(is_open_now($row24h));
    }

    public function testIsOpenNowMissingHours(): void
    {
        $rowMissing = ['is_24h' => 0, 'open_time' => null, 'close_time' => null];
        $this->assertFalse(is_open_now($rowMissing));
    }

    public function testIsOpenNowNormalHours(): void
    {
        // Tạo giờ luôn bao trùm thời điểm hiện tại
        $openTime = date('H:i:s', time() - 3600);
        $closeTime = date('H:i:s', time() + 3600);

        if ($closeTime > $openTime) {
            $row = ['is_24h' => 0, 'open_time' => $openTime, 'close_time' => $closeTime];
            $this->assertTrue(is_open_now($row));
        }

        // Quán đã đóng cửa trong quá khứ
        $rowClosed = ['is_24h' => 0, 'open_time' => '01:00:00', 'close_time' => '02:00:00'];
        $now = date('H:i:s');
        if ($now < '01:00:00' || $now >= '02:00:00') {
            $this->assertFalse(is_open_now($rowClosed));
        }
    }
}
