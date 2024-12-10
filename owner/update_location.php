<?php
// Start the session if not started already
session_start();

// Include database connection
include('dwos.php');

// Check if the request is POST and contains the necessary data
$data = json_decode(file_get_contents("php://input"), true);

if (isset($data['userId'], $data['latitude'], $data['longitude'], $data['stationId'])) {
    $userId = $data['userId'];
    $latitude = $data['latitude'];
    $longitude = $data['longitude'];
    $stationId = $data['stationId'];

    // Update the user's location in the 'users' table
    $update_user_query = "UPDATE users SET latitude = ?, longitude = ? WHERE user_id = ?";
    $stmt = $conn->prepare($update_user_query);
    $stmt->bind_param("ddi", $latitude, $longitude, $userId);
    if ($stmt->execute()) {
        // Also update the station's location in the 'stations' table
        $update_station_query = "UPDATE stations SET latitude = ?, longitude = ? WHERE station_id = ?";
        $station_stmt = $conn->prepare($update_station_query);
        $station_stmt->bind_param("ddi", $latitude, $longitude, $stationId);
        if ($station_stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "Location updated for both user and station."]);
        } else {
            echo json_encode(["status" => "error", "message" => "Failed to update station location."]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to update user location."]);
    }

    $stmt->close();
    $station_stmt->close();
} else {
    echo json_encode(["status" => "error", "message" => "Invalid data received."]);
}

$conn->close();
?>
