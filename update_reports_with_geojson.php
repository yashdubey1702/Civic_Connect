<?php
require_once 'config/database.php';
require_once __DIR__ . '/../config/BhubaneswarDetector.php';


$database = new Database();
$db = $database->getConnection(); // mysqli
$wardDetector = new BhubaneswarWardDetector();

echo "<h2>Updating Reports with Bhubaneswar Ward Detection</h2>";

// Fetch all reports
$query = "SELECT id, latitude, longitude, municipality FROM reports ORDER BY created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$result = $stmt->get_result();
$reports = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

echo "<p>Found <strong>" . count($reports) . "</strong> reports.</p>";

$updated = 0;
$skipped = 0;
$errors  = 0;
$wardCounts = [];

foreach ($reports as $report) {
    try {
        $lat = (float)$report['latitude'];
        $lng = (float)$report['longitude'];

        // Detect ward
        $ward = $wardDetector->detectWard($lat, $lng);

        if ($ward === null) {
            $skipped++;
            echo "<p>⚠️ Skipped report ID {$report['id']} (outside Bhubaneswar)</p>";
            continue;
        }

        // Count wards
        if (!isset($wardCounts[$ward])) {
            $wardCounts[$ward] = 0;
        }
        $wardCounts[$ward]++;

        // Update only if different
        if ($report['municipality'] !== $ward) {
            $update = $db->prepare(
                "UPDATE reports SET municipality = ? WHERE id = ?"
            );
            $update->bind_param("si", $ward, $report['id']);

            if ($update->execute()) {
                $updated++;
                echo "<p>Updated report ID {$report['id']} → {$ward}</p>";
            } else {
                $errors++;
                echo "<p> DB error on report ID {$report['id']}</p>";
            }
            $update->close();
        } else {
            $skipped++;
            echo "<p>✓ Report ID {$report['id']} already correct ({$ward})</p>";
        }

    } catch (Throwable $e) {
        $errors++;
        echo "<p> Error on report ID {$report['id']}: {$e->getMessage()}</p>";
    }
}

//  SUMMARY

echo "<hr>";
echo "<h3>Update Summary</h3>";
echo "<p><strong>Updated:</strong> {$updated}</p>";
echo "<p><strong>Skipped:</strong> {$skipped}</p>";
echo "<p><strong>Errors:</strong> {$errors}</p>";

echo "<h3>Ward Distribution</h3>";
ksort($wardCounts);
foreach ($wardCounts as $ward => $count) {
    echo "<p><strong>" . strtoupper($ward) . ":</strong> {$count} reports</p>";
}

echo "<hr>";
echo "<p><strong>Done.</strong> You can now safely delete this script.</p>";
