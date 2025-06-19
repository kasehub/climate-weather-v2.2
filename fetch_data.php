<?php
$host = "localhost";
$user = "root";
$password = "";
$db = "climateaware";

$conn = new mysqli($host, $user, $password, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$result = $conn->query("SELECT region, latitude, longitude, date, temperature, rainfall FROM climate_data ORDER BY date ASC");

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

header('Content-Type: application/json');
echo json_encode($data);

$conn->close();
?>
