<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "smart_bin_db";

$conn = new mysqli($servername, $username, $password, $dbname);
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

function handleData($rfid_id, $points, $timestamp) {
    global $conn;

    if (empty($rfid_id) || !is_numeric($points)) {
        http_response_code(400);
        echo json_encode(["error" => "Invalid data received"]);
        return;
    }

    $stmt = $conn->prepare("SELECT total_points FROM student_points WHERE rfid_id = ?");
    $stmt->bind_param("s", $rfid_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $total_points = 0;

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $total_points = $row['total_points'];
    }

    $total_points += $points;

    if ($result->num_rows > 0) {
        $stmt = $conn->prepare("UPDATE student_points SET points = ?, total_points = ?, timestamp = ? WHERE rfid_id = ?");
        $stmt->bind_param("iiss", $points, $total_points, $timestamp, $rfid_id);
    } else {
        $stmt = $conn->prepare("INSERT INTO student_points (rfid_id, points, total_points, timestamp) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("siis", $rfid_id, $points, $total_points, $timestamp);
    }

    if ($stmt->execute()) {
        http_response_code(200);
        echo json_encode([
            "message" => "Data recorded successfully",
            "rfid_id" => $rfid_id,
            "points" => $points,
            "total_points" => $total_points
        ]);
    } else {
        http_response_code(500);
        echo json_encode(["error" => "Error recording data: " . $stmt->error]);
    }

    $stmt->close();
}

function validateRFID($rfid_id) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT rfid_id FROM student_points WHERE rfid_id = ?");
    $stmt->bind_param("s", $rfid_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo json_encode(["message" => "Valid RFID"]);
    } else {
        http_response_code(400);
        echo json_encode(["error" => "Invalid RFID"]);
    }

    $stmt->close();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST["validate"])) {
        $rfid_id = $_POST["rfid_id"] ?? "";
        validateRFID($rfid_id);
    } else {
        $rfid_id = $_POST["rfid_id"] ?? "";
        $points = $_POST["points"] ?? 0;
        $timestamp = $_POST["timestamp"] ?? date("Y-m-d H:i:s");
        handleData($rfid_id, $points, $timestamp);
    }
} elseif ($_SERVER["REQUEST_METHOD"] == "GET") {
    echo json_encode(["message" => "GET request received. Use POST for data submission."]);
} else {
    http_response_code(405);
    echo json_encode(["error" => "Method not allowed"]);
}

$conn->close();
?>