<?php
declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;

require_once dirname(__DIR__, 2) . '/app/helpers/validate.php';

class ValidationTest extends TestCase
{
    public function testRequiredFloatValid(): void
    {
        $input = ['lat' => '10.7326', 'lng' => '106.6996'];
        $lat = required_float($input, 'lat', -90.0, 90.0);
        $this->assertEquals(10.7326, $lat);
    }

    public function testRequiredFloatThrowsWhenOutOfRange(): void
    {
        $input = ['lat' => '95.5'];
        $this->assertThrows(function () use ($input) {
            required_float($input, 'lat', -90.0, 90.0);
        }, \InvalidArgumentException::class);
    }

    public function testRequiredFloatThrowsWhenMissingOrInvalid(): void
    {
        $input = ['lat' => 'invalid_string'];
        $this->assertThrows(function () use ($input) {
            required_float($input, 'lat', -90.0, 90.0);
        }, \InvalidArgumentException::class);

        $this->assertThrows(function () {
            required_float([], 'lat', -90.0, 90.0);
        }, \InvalidArgumentException::class);
    }

    public function testRequiredIntValidAndBoundary(): void
    {
        $input = ['id' => '123'];
        $id = required_int($input, 'id', 1, 999);
        $this->assertEquals(123, $id);

        $this->assertThrows(function () {
            required_int(['id' => '0'], 'id', 1, 100);
        }, \InvalidArgumentException::class);

        $this->assertThrows(function () {
            required_int(['id' => '12.34'], 'id', 1, 100);
        }, \InvalidArgumentException::class);
    }

    public function testOptionalFloatAndIntDefaults(): void
    {
        $empty = [];
        $this->assertEquals(3.0, optional_float($empty, 'radius', 3.0, 0.1, 30.0));
        $this->assertEquals(null, optional_int($empty, 'category_id', null, 1, 100));

        $provided = ['radius' => '5.5', 'category_id' => '7'];
        $this->assertEquals(5.5, optional_float($provided, 'radius', 3.0, 0.1, 30.0));
        $this->assertEquals(7, optional_int($provided, 'category_id', null, 1, 100));
    }

    public function testOptionalEnumValidation(): void
    {
        $allowed = ['food', 'tourism', 'entertainment'];
        $this->assertEquals('food', optional_enum([], 'type', 'food', $allowed));
        $this->assertEquals('tourism', optional_enum(['type' => 'tourism'], 'type', 'food', $allowed));

        $this->assertThrows(function () use ($allowed) {
            optional_enum(['type' => 'hacking'], 'type', 'food', $allowed);
        }, \InvalidArgumentException::class);
    }

    public function testOptionalTextTrimmingAndLength(): void
    {
        $this->assertEquals('Pho Bo', optional_text(['q' => '  Pho Bo  '], 'q', 50));
        $this->assertEquals('', optional_text([], 'q', 50));

        $longStr = str_repeat('a', 100);
        $this->assertThrows(function () use ($longStr) {
            optional_text(['q' => $longStr], 'q', 50);
        }, \InvalidArgumentException::class);
    }

    public function testRestaurantFiltersSanitization(): void
    {
        $raw = [
            'lat' => '10.7326',
            'lng' => '106.6996',
            'radius' => '8.5',
            'type' => 'food',
            'limit' => '20',
            'offset' => '10',
            'open_now' => '1'
        ];
        $filters = restaurant_filters($raw);

        $this->assertEquals(10.7326, $filters['lat']);
        $this->assertEquals(106.6996, $filters['lng']);
        $this->assertEquals(8.5, $filters['radius']);
        $this->assertEquals('food', $filters['type']);
        $this->assertEquals(20, $filters['limit']);
        $this->assertEquals(10, $filters['offset']);
        $this->assertTrue($filters['open_now']);
        $this->assertEquals('distance', $filters['sort']);
    }
}
