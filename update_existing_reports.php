<?php
require_once 'config/database.php';
require_once __DIR__ . '/../config/BhubaneswarDetector.php';


$database = new Database();
$db = $database->getConnection(); // mysqli
$wardDetector = new BhubaneswarWardDetector();

echo "=== Bhubaneswar Ward Backfill Script ===\n\n";

// Get reports with missing ward
$query = "
    SELECT id, latitude, longitude
    FROM reports
    WHERE municipality IS NULL OR municipality = ''
";

$stmt = $db->prepare($query);
if (!$stmt) {
    die("DB Prepare Error: " . $db->error . "\n");
}

$stmt->execute();
$result = $stmt->get_result();
$reports = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$total = count($reports);
echo "Found {$total} reports without ward data.\n\n";

$updated = 0;
$skipped = 0;
$errors  = 0;

foreach ($reports as $report) {

    $lat = (float)$report['latitude'];
    $lng = (float)$report['longitude'];

    // Check inside Bhubaneswar
    if (!$wardDetector->isWithinBhubaneswar($lat, $lng)) {
        echo "Skipped ID {$report['id']} (outside Bhubaneswar)\n";
        $skipped++;
        continue;
    }

    // Detect ward
    $ward = $wardDetector->detectWard($lat, $lng);

    if ($ward === null) {
        echo "Skipped ID {$report['id']} (ward not detected)\n";
        $skipped++;
        continue;
    }

    // Update DB
    $updateQuery = "UPDATE reports SET municipality = ? WHERE id = ?";
    $updateStmt = $db->prepare($updateQuery);

    if (!$updateStmt) {
        echo "Error preparing update for ID {$report['id']}\n";
        $errors++;
        continue;
    }

    $updateStmt->bind_param("si", $ward, $report['id']);

    if ($updateStmt->execute()) {
        echo "Updated report ID {$report['id']} → Ward {$ward}\n";
        $updated++;
    } else {
        echo "Failed updating report ID {$report['id']}\n";
        $errors++;
    }

    $updateStmt->close();
}

echo "\n=== UPDATE COMPLETE ===\n";
echo "Total scanned : {$total}\n";
echo "Updated       : {$updated}\n";
echo "Skipped       : {$skipped}\n";
echo "Errors        : {$errors}\n";
