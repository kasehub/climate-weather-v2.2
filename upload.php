<?php
$host = "localhost";
$user = "root";
$password = "";
$db = "climateaware";  

$conn = new mysqli($host, $user, $password, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
    $file = $_FILES['file']['tmp_name'];
    $handle = fopen($file, "r");
    fgetcsv($handle); 

    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        $region = $conn->real_escape_string($data[0]);
        $latitude = floatval($data[1]);
        $longitude = floatval($data[2]);
        $date = $conn->real_escape_string($data[3]);
        $temperature = floatval($data[4]);
        $rainfall = floatval($data[5]);

        $sql = "INSERT INTO climate_data (region, latitude, longitude, date, temperature, rainfall) 
                VALUES ('$region', '$latitude', '$longitude', '$date', '$temperature', '$rainfall')";
        $conn->query($sql);
    }

    fclose($handle);
    echo "<script>alert('File uploaded successfully!'); window.location.href='index.html';</script>";
} else {
    echo "<script>alert('File upload error!'); window.location.href='index.html';</script>";
}

$conn->close();
?>
