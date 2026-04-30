<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../app/Core/Database.php';
require_once __DIR__ . '/../app/Support/mail.php';
require_once __DIR__ . '/../app/Services/BhubaneswarDetector.php';

/* =========================
   INIT
   ========================= */
$database = new Database();
$db = $database->getConnection(); // mysqli
$wardDetector = new BhubaneswarWardDetector();

/* =========================
   INPUT
   ========================= */
$lat         = isset($_POST['lat']) ? floatval($_POST['lat']) : null;
$lng         = isset($_POST['lng']) ? floatval($_POST['lng']) : null;
$category    = trim($_POST['category'] ?? '');
$description = trim($_POST['description'] ?? '');

/* 🔒 EMAIL MUST COME FROM SESSION */
$email = $_SESSION['email'] ?? null;
$userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;

$imageFilename = null;

// Generates a unique public tracking token for a report.
function generateTrackingToken(mysqli $db): string
{
    do {
        $token = 'CC-' . strtoupper(bin2hex(random_bytes(4)));
        $check = $db->prepare("SELECT id FROM reports WHERE tracking_token = ? LIMIT 1");
        $check->bind_param("s", $token);
        $check->execute();
        $exists = $check->get_result()->num_rows > 0;
        $check->close();
    } while ($exists);

    return $token;
}

// Builds the absolute home page URL for email links.
function getHomePageUrl(): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $basePath = rtrim(dirname(dirname($scriptName)), '/');

    return $scheme . '://' . $host . ($basePath === '' ? '' : $basePath) . '/public/index.html';
}

/* =========================
   AUTH VALIDATION
   ========================= */
if ($email === null || $userId === null) {
    echo json_encode([
        "success" => false,
        "message" => "User not authenticated."
    ]);
    exit;
}

/* =========================
   BASIC VALIDATION
   ========================= */
if (
    $lat === null ||
    $lng === null ||
    !is_numeric($lat) ||
    !is_numeric($lng) ||
    $category === ''
) {
    echo json_encode([
        "success" => false,
        "message" => "Missing or invalid required fields."
    ]);
    exit;
}

/* =========================
   BHUBANESWAR BOUNDARY CHECK
   ========================= */
if (!$wardDetector->isWithinBhubaneswar($lat, $lng)) {
    echo json_encode([
        "success" => false,
        "message" => "Reports can only be submitted within Bhubaneswar city limits."
    ]);
    exit;
}

/* =========================
   DETECT WARD
   ========================= */
$ward = $wardDetector->detectWard($lat, $lng);

if ($ward === null) {
    echo json_encode([
        "success" => false,
        "message" => "Unable to detect ward. Please select a valid location."
    ]);
    exit;
}

/* =========================
   IMAGE UPLOAD
   ========================= */
if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {

    $uploadDir = __DIR__ . '/uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $check = getimagesize($_FILES['image']['tmp_name']);
    if ($check === false) {
        echo json_encode([
            "success" => false,
            "message" => "Invalid image file."
        ]);
        exit;
    }

    $allowedTypes = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp'
    ];

    $mime = $check['mime'];
    if (!isset($allowedTypes[$mime])) {
        echo json_encode([
            "success" => false,
            "message" => "Unsupported image type."
        ]);
        exit;
    }

    $imageFilename = uniqid('report_', true) . '.' . $allowedTypes[$mime];
    $uploadPath = $uploadDir . $imageFilename;

    if (!move_uploaded_file($_FILES['image']['tmp_name'], $uploadPath)) {
        echo json_encode([
            "success" => false,
            "message" => "Image upload failed."
        ]);
        exit;
    }
}


/* =========================
   INSERT REPORT
   ========================= */
$trackingToken = generateTrackingToken($db);

$query = "
    INSERT INTO reports
        (user_id, latitude, longitude, category, description, email, image_filename, municipality, tracking_token)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
";

$stmt = $db->prepare($query);
$stmt->bind_param(
    "iddssssss",
    $userId,
    $lat,
    $lng,
    $category,
    $description,
    $email,
    $imageFilename,
    $ward,
    $trackingToken
);

if ($stmt->execute()) {
    $reportId = (int)$db->insert_id;
    $emailSent = sendReportTrackingEmail($email, [
        'report_id' => $reportId,
        'category' => $category,
        'description' => $description,
        'ward' => $ward,
        'status' => 'Reported',
        'tracking_token' => $trackingToken,
        'submitted_at' => date('Y-m-d H:i:s'),
        'tracking_url' => getHomePageUrl()
    ]);
    echo json_encode([
        "success" => true,
        "message" => $emailSent
            ? "Report submitted successfully. The tracking token has been sent to your email."
            : "Report submitted successfully. Your tracking token is {$trackingToken}.",
        "ward"    => $ward,
        "email_sent" => $emailSent,
        "tracking_token" => $trackingToken
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Failed to save report."
    ]);
}

$stmt->close();
