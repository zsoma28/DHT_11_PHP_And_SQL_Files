<?php
// Adatbázis konfiguráció
$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "sensor_data";
$valid_api_key = "123456789"; // Ezt cseréld ki a sajátodra!

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    header('Content-Type: application/json');
    die(json_encode(["error" => "Kapcsolódási hiba"]));
}

// --- ADATBEVÉTEL (Szenzor küldi) ---
if (isset($_GET['temperature']) && isset($_GET['humidity'])) {
    header('Content-Type: text/plain; charset=utf-8');

    // API kulcs ellenőrzése
    $provided_key = $_GET['api_key'] ?? '';
    if ($provided_key !== $valid_api_key) {
        http_response_code(403);
        die("Hiba: Érvénytelen API kulcs!");
    }

    $temp = filter_var($_GET['temperature'], FILTER_VALIDATE_FLOAT);
    $humi = filter_var($_GET['humidity'], FILTER_VALIDATE_FLOAT);

    if ($temp !== false && $humi !== false) {
        $stmt = $conn->prepare("INSERT INTO measurements (temperature, humidity, created_at) VALUES (?, ?, NOW())");
        $stmt->bind_param("dd", $temp, $humi);
        if ($stmt->execute()) {
            echo "Siker: Adat rögzítve!";
        } else {
            http_response_code(500);
            echo "Mentési hiba: " . $conn->error;
        }
        $stmt->close();
    } else {
        http_response_code(400);
        echo "Hiba: Érvénytelen számformátum.";
    }
    $conn->close();
    exit;
}

// --- ADATSZOLGÁLTATÁS (Dashboard olvassa) ---
header('Content-Type: application/json');
$sql = "SELECT id, temperature, humidity, created_at FROM measurements ORDER BY id DESC LIMIT 20";
$result = $conn->query($sql);

$data = [];
if ($result) {
    while($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
}
echo json_encode(array_reverse($data)); // Időrendbe rakjuk a grafikonhoz
$conn->close();
?>
