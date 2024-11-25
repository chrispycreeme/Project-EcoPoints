<?php
// Database connection details
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "smart_bin_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Function to handle data insertion and update total points
function handleData($rfid_id, $points, $timestamp) {
    global $conn;
    
    // Validate the data
    if (empty($rfid_id) || !is_numeric($points)) {
        http_response_code(400);
        echo json_encode(["error" => "Invalid data received"]);
        return;
    }

    // Get current total points for the student
    $stmt = $conn->prepare("SELECT total_points FROM student_points WHERE rfid_id = ?");
    $stmt->bind_param("s", $rfid_id);
    $stmt->execute();
    $result = $stmt->get_result();

    // Initialize total points
    $total_points = 0;

    if ($result->num_rows > 0) {
        // If the student exists, fetch current total points
        $row = $result->fetch_assoc();
        $total_points = $row['total_points'];
    }

    // Update total points
    $total_points += $points;

    // Insert or update student points
    if ($result->num_rows > 0) {
        // If student exists, update the points and total_points
        $stmt = $conn->prepare("UPDATE student_points SET points = ?, total_points = ?, timestamp = ? WHERE rfid_id = ?");
        $stmt->bind_param("iiss", $points, $total_points, $timestamp, $rfid_id);
    } else {
        // If student doesn't exist, insert new record
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

// Function to validate RFID
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