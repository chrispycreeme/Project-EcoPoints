<?php
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

// Fetch RFID and total points
$rfid_id = "xxxxxxx"; // Default value if not found
$total_points = "NaN"; // Default value if not found

if (isset($_GET['rfid_id'])) {
    $rfid_id = $_GET['rfid_id'];
    $stmt = $conn->prepare("SELECT total_points FROM student_points WHERE rfid_id = ?");
    $stmt->bind_param("s", $rfid_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $total_points = $row['total_points'];
    }
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="/css/viewCard.css">
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&icon_names=history" />
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&icon_names=eco" />
    <style>
        body {
            margin: 0 auto;
            margin-left: 2em;
            margin-right: 2em;
            font-family: "Inter", sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            min-height: 100vh;
        }

        .card-main-viewer-infos {
            height: 200px;
            width: 320px;
            background-color: #007FFF;
            border-radius: 25px;
            color: white;
            padding: 1em 2em 1em 2em;
        }

        h1.rfid-id {
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .container-whole-page {
            width: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
        }

        h3.registered-holder-rfid {
            margin-top: 4em;
            margin-bottom: 0;
        }

        p.registered-date {
            margin-top: 0;
        }

        .card-point-total-viewer {
            height: 120px;
            width: 320px;
            background-color: #f1f1f1;
            margin-top: 1em;
            padding: 1em 2em 1em 2em;
            border-radius: 25px;
        }

        h4.point-heading {
            margin-bottom: 0;
            margin-top: -1px;
            color: black;
        }

        h2.point-count {
            margin-top: 0;
            color: #2994ff;
            font-size: 40px;
        }
    </style>
</head>

<body>
    <div class="container-whole-page">
        <div class="card-main-viewer-infos">
            <h1 class="rfid-id"><?php echo htmlspecialchars($rfid_id); ?></h1>
            <h3 class="registered-holder-rfid">Andrei Kenzi B. Comia</h3>
            <p class="registered-date">October 1, 2024</p>
        </div>
        <div class="card-point-total-viewer">
            <span class="material-symbols-outlined" style="color: #2994ff; font-size: 40px; margin-bottom: 15px;">
                eco
            </span>
            <h4 class="point-heading">Total EcoPoints</h4>
            <h2 class="point-count"><span><?php echo htmlspecialchars($total_points); ?></span> Points</h2>
        </div>
    </div>

</body>

</html>