<?php
require_once 'config/database.php';
require_once 'config/BhubaneswarDetector.php';

$database = new Database();
$db = $database->getConnection(); // mysqli
$wardDetector = new BhubaneswarDetector();

echo "<h2>Bhubaneswar Ward Detection Debug</h2>";

//   TEST COORDINATES (KNOWN BMC LOCATIONS)

echo "<h3>Testing All Wards (Auto from GeoJSON)</h3>";

$wards = $wardDetector->getAllWards();

if (empty($wards)) {
    echo "<p style='color:red;'>No wards loaded from Ward.geojson</p>";
} else {

    echo "<table border='1' cellpadding='6' cellspacing='0' style='border-collapse:collapse;width:100%;'>";
    echo "<tr style='background:#f1f5f9;'>
            <th>Ward</th>
            <th>Zone</th>
            <th>Test Lat</th>
            <th>Test Lng</th>
            <th>Detected Ward</th>
            <th>Status</th>
          </tr>";

    foreach ($wards as $key => $ward) {

//          Get a safe test point (first polygon coord)
 
        $geometry = $ward['geometry'];

        if ($geometry['type'] === 'Polygon') {
            $point = $geometry['coordinates'][0][0];
        } elseif ($geometry['type'] === 'MultiPolygon') {
            $point = $geometry['coordinates'][0][0][0];
        } else {
            continue;
        }

        $lng = $point[0];
        $lat = $point[1];

        $detectedWard = $wardDetector->detectWard($lat, $lng);
        $isCorrect = ($detectedWard === $key);

        echo "<tr>";
        echo "<td><strong>{$ward['ward_no']}</strong></td>";
        echo "<td>{$ward['zone']}</td>";
        echo "<td>{$lat}</td>";
        echo "<td>{$lng}</td>";
        echo "<td style='font-weight:bold;color:" . ($detectedWard ? '#1e40af' : '#dc2626') . ";'>
                " . ($detectedWard ?? 'NULL') . "
              </td>";
        echo "<td style='font-weight:bold;color:" . ($isCorrect ? 'green' : 'red') . ";'>
                " . ($isCorrect ? 'PASS ✅' : 'FAIL ❌') . "
              </td>";
        echo "</tr>";
    }

    echo "</table>";
}



//   RECENT REPORTS FROM DATABASE

echo "<h3>Recent Reports (with Stored Ward)</h3>";

$query = "
    SELECT 
        id,
        latitude,
        longitude,
        municipality,
        category,
        description,
        created_at
    FROM reports
    ORDER BY created_at DESC
    LIMIT 20
";

$stmt = $db->prepare($query);
$stmt->execute();
$result = $stmt->get_result();
$reports = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if (empty($reports)) {
    echo "<p style='color:red;'>No reports found in database.</p>";
} else {
    echo "<table border='1' cellpadding='6' cellspacing='0' style='border-collapse:collapse;width:100%;'>";
    echo "<tr style='background:#f1f5f9;'>
            <th>ID</th>
            <th>Lat</th>
            <th>Lng</th>
            <th>Ward (municipality)</th>
            <th>Category</th>
            <th>Description</th>
            <th>Created</th>
          </tr>";

    foreach ($reports as $report) {
        $ward = $report['municipality'] ?: 'NULL';
        $color = $ward ? '#1e40af' : '#dc2626';

        echo "<tr>";
        echo "<td>{$report['id']}</td>";
        echo "<td>{$report['latitude']}</td>";
        echo "<td>{$report['longitude']}</td>";
        echo "<td style='color:{$color}; font-weight:bold;'>{$ward}</td>";
        echo "<td>" . htmlspecialchars($report['category']) . "</td>";
        echo "<td>" . htmlspecialchars(substr($report['description'] ?? '', 0, 60)) . "</td>";
        echo "<td>{$report['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
}


//   LOADED WARDS FROM GEOJSON

echo "<h3>Loaded Wards from GeoJSON</h3>";

$wards = $wardDetector->getAllWards();

if (empty($wards)) {
    echo "<p style='color:red;'>No wards loaded. Check data/Wards.geojson path.</p>";
} else {
    echo "<ul>";
    foreach ($wards as $key => $ward) {
        echo "<li>
            <strong>{$ward['ward_no']}</strong>
            — Zone: {$ward['zone']}
            (key: {$key})
        </li>";
    }
    echo "</ul>";
}

echo "<hr>";
echo "<p><strong>Legend:</strong></p>";
echo "<ul>
        <li><strong>municipality column</strong> = ward key (w1, w9, w15…)</li>
        <li>Green = correctly detected</li>
        <li>Red = outside Bhubaneswar or missing</li>
      </ul>";
?>
