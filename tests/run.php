<?php
declare(strict_types=1);

/**
 * FOOD FINDER — AUTOMATED UNIT & INTEGRATION TEST RUNNER
 * Zero external dependencies — Compatible with PHP 8.1+ & 8.2+
 * Cách chạy: php tests/run.php
 */

require_once __DIR__ . '/TestCase.php';

$testFiles = [
    'Unit: Helpers & Validation' => __DIR__ . '/Unit/ValidationTest.php',
    'Unit: Geo & Bounding Box'   => __DIR__ . '/Unit/GeoTest.php',
    'Unit: JWT & Tokens'         => __DIR__ . '/Unit/JwtTest.php',
    'Unit: Security & Cryptography' => __DIR__ . '/Unit/SecurityTest.php',
    'Unit: Itinerary Algorithms' => __DIR__ . '/Unit/ItineraryTest.php',
    'Integration: TiDB Cloud Database & Auth' => __DIR__ . '/Integration/DatabaseAuthTest.php',
];

echo "\n";
echo "====================================================================\n";
echo "   FOOD FINDER — BỘ KIỂM THỬ TỰ ĐỘNG (UNIT & INTEGRATION TESTS)    \n";
echo "====================================================================\n\n";

$startTime = microtime(true);
$totalMethods = 0;
$passedMethods = 0;
$failedMethods = 0;
$failures = [];

foreach ($testFiles as $suiteName => $filePath) {
    if (!is_file($filePath)) {
        continue;
    }

    echo "▶ \033[1;36mSUITE: {$suiteName}\033[0m\n";
    require_once $filePath;

    // Xác định tên class tương ứng
    $className = 'Tests\\' . str_replace('/', '\\', substr($filePath, strlen(__DIR__) + 1, -4));
    if (!class_exists($className)) {
        echo "  [WARN] Class {$className} không tìm thấy.\n\n";
        continue;
    }

    $ref = new ReflectionClass($className);
    $instance = $ref->newInstance();
    $methods = array_filter(
        $ref->getMethods(ReflectionMethod::IS_PUBLIC),
        fn($m) => str_starts_with($m->getName(), 'test')
    );

    foreach ($methods as $method) {
        $totalMethods++;
        $methodName = $method->getName();
        $testStart = microtime(true);

        try {
            $method->invoke($instance);
            $testDuration = round((microtime(true) - $testStart) * 1000, 1);
            $passedMethods++;
            echo "   \033[32m[PASS ✅]\033[0m {$methodName} ({$testDuration}ms)\n";
        } catch (\Throwable $e) {
            $failedMethods++;
            $testDuration = round((microtime(true) - $testStart) * 1000, 1);
            echo "   \033[31m[FAIL ❌]\033[0m {$methodName} ({$testDuration}ms)\n";
            $failures[] = [
                'suite' => $suiteName,
                'method' => $methodName,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ];
        }
    }
    echo "\n";
}

$totalTime = round((microtime(true) - $startTime) * 1000, 2);
$assertions = Tests\TestCase::$assertionsCount;

echo "====================================================================\n";
if ($failedMethods === 0) {
    echo "\033[1;32m   KẾT QUẢ: TOÀN BỘ {$passedMethods}/{$totalMethods} TEST CASES THÀNH CÔNG (100% PASS)!\033[0m\n";
} else {
    echo "\033[1;31m   KẾT QUẢ: {$passedMethods}/{$totalMethods} PASS, {$failedMethods} FAILED\033[0m\n";
    echo "--------------------------------------------------------------------\n";
    echo "DANH SÁCH LỖI:\n";
    foreach ($failures as $f) {
        echo " - [{$f['suite']}] {$f['method']}:\n";
        echo "   Lỗi: {$f['message']}\n";
        echo "   Tại: {$f['file']}:{$f['line']}\n\n";
    }
}

echo "   - Tổng số Assertions: {$assertions}\n";
echo "   - Thời gian thực thi: {$totalTime} ms\n";
echo "====================================================================\n\n";

exit($failedMethods === 0 ? 0 : 1);
