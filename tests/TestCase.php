<?php
declare(strict_types=1);

namespace Tests;

class TestCase
{
    public static int $assertionsCount = 0;

    public function assertEquals($expected, $actual, string $message = ''): void
    {
        self::$assertionsCount++;
        if ($expected !== $actual) {
            $expectedStr = var_export($expected, true);
            $actualStr = var_export($actual, true);
            throw new \AssertionError($message ?: "Failed asserting that {$actualStr} matches expected {$expectedStr}.");
        }
    }

    public function assertTrue(bool $condition, string $message = ''): void
    {
        self::$assertionsCount++;
        if ($condition !== true) {
            throw new \AssertionError($message ?: "Failed asserting that false is true.");
        }
    }

    public function assertFalse(bool $condition, string $message = ''): void
    {
        self::$assertionsCount++;
        if ($condition !== false) {
            throw new \AssertionError($message ?: "Failed asserting that true is false.");
        }
    }

    public function assertNull($value, string $message = ''): void
    {
        self::$assertionsCount++;
        if ($value !== null) {
            $valStr = var_export($value, true);
            throw new \AssertionError($message ?: "Failed asserting that {$valStr} is null.");
        }
    }

    public function assertNotNull($value, string $message = ''): void
    {
        self::$assertionsCount++;
        if ($value === null) {
            throw new \AssertionError($message ?: "Failed asserting that null is not null.");
        }
    }

    public function assertCount(int $expectedCount, $countable, string $message = ''): void
    {
        self::$assertionsCount++;
        $actualCount = count($countable);
        if ($actualCount !== $expectedCount) {
            throw new \AssertionError($message ?: "Failed asserting that count {$actualCount} matches expected {$expectedCount}.");
        }
    }

    public function assertArrayHasKey(string $key, array $array, string $message = ''): void
    {
        self::$assertionsCount++;
        if (!array_key_exists($key, $array)) {
            throw new \AssertionError($message ?: "Failed asserting that array contains key '{$key}'.");
        }
    }

    public function assertThrows(callable $callback, string $expectedException = \Throwable::class, string $message = ''): void
    {
        self::$assertionsCount++;
        try {
            $callback();
        } catch (\Throwable $e) {
            if ($e instanceof $expectedException) {
                return;
            }
            throw new \AssertionError($message ?: "Expected exception {$expectedException}, but caught " . get_class($e));
        }
        throw new \AssertionError($message ?: "Expected exception {$expectedException} was not thrown.");
    }

    public function assertStringContainsString(string $needle, string $haystack, string $message = ''): void
    {
        self::$assertionsCount++;
        if (!str_contains($haystack, $needle)) {
            throw new \AssertionError($message ?: "Failed asserting that '{$haystack}' contains '{$needle}'.");
        }
    }
}
