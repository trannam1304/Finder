<?php
declare(strict_types=1);

function required_float(array $source, string $key, float $min, float $max): float
{
    if (!isset($source[$key]) || filter_var($source[$key], FILTER_VALIDATE_FLOAT) === false) {
        throw new InvalidArgumentException("Tham số {$key} không hợp lệ");
    }
    $value = (float) $source[$key];
    if ($value < $min || $value > $max) throw new InvalidArgumentException("Tham số {$key} không hợp lệ");
    return $value;
}

function optional_float(array $source, string $key, float $default, float $min, float $max): float
{
    if (!isset($source[$key]) || $source[$key] === '') return $default;
    return required_float($source, $key, $min, $max);
}

function required_int(array $source, string $key, int $min, int $max): int
{
    if (!isset($source[$key]) || filter_var($source[$key], FILTER_VALIDATE_INT) === false) {
        throw new InvalidArgumentException("Tham số {$key} không hợp lệ");
    }
    $value = (int) $source[$key];
    if ($value < $min || $value > $max) throw new InvalidArgumentException("Tham số {$key} không hợp lệ");
    return $value;
}

function optional_int(array $source, string $key, ?int $default, int $min, int $max): ?int
{
    if (!isset($source[$key]) || $source[$key] === '') return $default;
    $value = filter_var($source[$key], FILTER_VALIDATE_INT);
    if ($value === false || $value < $min || $value > $max) throw new InvalidArgumentException("Tham số {$key} không hợp lệ");
    return $value;
}

function optional_enum(array $source, string $key, string $default, array $allowed): string
{
    $value = (string) ($source[$key] ?? $default);
    if (!in_array($value, $allowed, true)) throw new InvalidArgumentException("Tham số {$key} không hợp lệ");
    return $value;
}

function optional_text(array $source, string $key, int $maxLength = 80): string
{
    $value = trim((string) ($source[$key] ?? ''));
    if (mb_strlen($value) > $maxLength) throw new InvalidArgumentException("Tham số {$key} quá dài");
    return $value;
}

function restaurant_filters(array $source): array
{
    $lat = required_float($source, 'lat', -90, 90);
    $lng = required_float($source, 'lng', -180, 180);
    $radius = optional_float($source, 'radius', 1.0, 0.1, 10.0);
    if (!in_array($radius, [0.5, 1.0, 2.0, 3.0, 5.0, 10.0], true)) {
        throw new InvalidArgumentException('Bán kính không hợp lệ');
    }
    return [
        'lat' => $lat, 'lng' => $lng, 'radius' => $radius,
        'category_id' => optional_int($source, 'category_id', null, 1, PHP_INT_MAX),
        'type' => optional_enum($source, 'type', 'all', ['all', 'food', 'tourism', 'entertainment']),
        'price_level' => optional_int($source, 'price_level', null, 1, 3),
        'min_rating' => optional_float($source, 'min_rating', 0, 0, 5),
        'open_now' => optional_int($source, 'open_now', 0, 0, 1) === 1,
        'q' => optional_text($source, 'q'),
        'sort' => optional_enum($source, 'sort', 'distance', ['distance', 'rating', 'price']),
        'limit' => optional_int($source, 'limit', 50, 1, 100) ?? 50,
        'offset' => optional_int($source, 'offset', 0, 0, 100000) ?? 0,
    ];
}
