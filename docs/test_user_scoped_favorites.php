<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

echo "========================================================\n";
echo "   KIỂM THỬ TÁCH BIỆT DỮ LIỆU ĐÃ LƯU THEO TỪNG USER     \n";
echo "========================================================\n\n";

$pdo = db();

$testsPassed = 0;
$total = 0;

function report(string $name, bool $ok, string $extra = '') {
    global $testsPassed, $total;
    $total++;
    if ($ok) {
        $testsPassed++;
        echo " [PASS ✅] $name\n";
    } else {
        echo " [FAIL ❌] $name" . ($extra ? ": $extra" : "") . "\n";
    }
}

// 1. Tạo 2 tài khoản test khác nhau: User A và User B
$time = time();
$userAData = auth_register_user($pdo, "user_a_$time", "user_a_$time@test.com", "Password123!", "Người Dùng A");
$userBData = auth_register_user($pdo, "user_b_$time", "user_b_$time@test.com", "Password123!", "Người Dùng B");

$uidA = (int) $userAData['user']['id'];
$uidB = (int) $userBData['user']['id'];

report("Khởi tạo 2 tài khoản riêng biệt (User A #$uidA và User B #$uidB)", $uidA > 0 && $uidB > 0 && $uidA !== $uidB);

// 2. Ban đầu danh sách của cả hai đều phải trống rỗng (không có dữ liệu mẫu rác)
$stmt = $pdo->prepare('SELECT COUNT(*) FROM user_favorites WHERE user_id = :uid');
$stmt->execute([':uid' => $uidA]);
$countA0 = (int) $stmt->fetchColumn();

$stmt->execute([':uid' => $uidB]);
$countB0 = (int) $stmt->fetchColumn();

report("User A ban đầu không có địa điểm yêu thích nào (count = 0)", $countA0 === 0);
report("User B ban đầu không có địa điểm yêu thích nào (count = 0)", $countB0 === 0);

// 3. User A lưu quán ID = 1 và ID = 2
$pdo->prepare('INSERT INTO user_favorites (user_id, restaurant_id) VALUES (?, ?), (?, ?)')->execute([$uidA, 1, $uidA, 2]);

// User A lưu 1 lộ trình
$itinA = [
    'id' => "itin_a_$time",
    'title' => 'Lộ trình Chill của User A',
    'duration' => 'half_day',
    'vibe' => 'chill',
    'stops' => [['place' => ['id' => 1]], ['place' => ['id' => 2]]],
];
$pdo->prepare('INSERT INTO user_saved_itineraries (user_id, itinerary_uid, title, duration_type, vibe, data_json) VALUES (?, ?, ?, ?, ?, ?)')
    ->execute([$uidA, $itinA['id'], $itinA['title'], $itinA['duration'], $itinA['vibe'], json_encode($itinA)]);

// 4. User B lưu quán ID = 3 duy nhất
$pdo->prepare('INSERT INTO user_favorites (user_id, restaurant_id) VALUES (?, ?)')->execute([$uidB, 3]);

// 5. Kiểm tra tính tách biệt: User B KHÔNG THẤY dữ liệu của User A
$stmt->execute([':uid' => $uidB]);
$countB = (int) $stmt->fetchColumn();

$stmtIdsB = $pdo->prepare('SELECT restaurant_id FROM user_favorites WHERE user_id = :uid');
$stmtIdsB->execute([':uid' => $uidB]);
$idsB = array_map('intval', $stmtIdsB->fetchAll(PDO::FETCH_COLUMN));

report("User B chỉ có 1 quán yêu thích của riêng mình", $countB === 1 && $idsB === [3]);
report("User B KHÔNG chứa các quán ID=1, ID=2 của User A", !in_array(1, $idsB, true) && !in_array(2, $idsB, true));

// Kiểm tra lộ trình của User B: phải rỗng 0
$stmtItinB = $pdo->prepare('SELECT COUNT(*) FROM user_saved_itineraries WHERE user_id = :uid');
$stmtItinB->execute([':uid' => $uidB]);
$countItinB = (int) $stmtItinB->fetchColumn();
report("User B không có lộ trình nào của User A (count = 0)", $countItinB === 0);

// 6. Kiểm tra lại User A: dữ liệu của User A vẫn nguyên vẹn
$stmtIdsA = $pdo->prepare('SELECT restaurant_id FROM user_favorites WHERE user_id = :uid ORDER BY restaurant_id ASC');
$stmtIdsA->execute([':uid' => $uidA]);
$idsA = array_map('intval', $stmtIdsA->fetchAll(PDO::FETCH_COLUMN));
report("User A vẫn giữ nguyên chính xác danh sách yêu thích [1, 2]", $idsA === [1, 2]);

$stmtItinA = $pdo->prepare('SELECT COUNT(*) FROM user_saved_itineraries WHERE user_id = :uid');
$stmtItinA->execute([':uid' => $uidA]);
$countItinA = (int) $stmtItinA->fetchColumn();
report("User A có đúng 1 lộ trình đã lưu", $countItinA === 1);

// 7. Xóa dữ liệu test
$pdo->prepare('DELETE FROM users WHERE id IN (?, ?)')->execute([$uidA, $uidB]);

echo "\n--------------------------------------------------------\n";
echo "KẾT QUẢ: $testsPassed / $total tests passed!\n";
echo "========================================================\n";

