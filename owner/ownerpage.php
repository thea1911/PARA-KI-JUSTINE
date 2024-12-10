<?php
// Start the session only if it hasn't been started yet
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include('dwos.php');

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get the logged-in owner's user_id
$user_id = $_SESSION['user_id'];

// Fetch Owner Details from the database using prepared statements
$stmt = $conn->prepare("SELECT user_name, image, password, latitude, longitude FROM users WHERE user_id = ? AND user_type = 'O'");
if (!$stmt) {
    echo "Prepare failed: (" . $conn->errno . ") " . $conn->error;
    exit();
}
$stmt->bind_param("s", $user_id); // Assuming user_id is a string
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $user = $result->fetch_assoc();
} else {
    echo "Error fetching owner details: " . $conn->error;
    exit();
}

// Fetch the station ID associated with the owner
$station_stmt = $conn->prepare("SELECT station_id FROM stations WHERE owner_id = ?");
$station_stmt->bind_param("i", $user_id);
$station_stmt->execute();
$station_result = $station_stmt->get_result();
$station_id = $station_result->fetch_assoc()['station_id'] ?? null;

?>

<?php include 'ownernavbar.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="ownerpage.css" />
    <title>Owner Homepage</title>
</head>
<body>

<h1 style="font-size: 2em; color: black; text-align: left; margin-top: 20px;">
    Welcome, <?php echo htmlspecialchars($user['user_name']); ?>!
</h1>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(position) {
                const latitude = position.coords.latitude;
                const longitude = position.coords.longitude;

                // Send the location to the server
                fetch('update_location.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        userId: <?php echo json_encode($user_id); ?>, // PHP variable for user ID
                        latitude: latitude,
                        longitude: longitude,
                        stationId: <?php echo json_encode($station_id); ?> // PHP variable for station ID
                    }),
                })
                .then(response => response.json())
                .then(data => {
                    console.log('Location updated:', data);
                })
                .catch((error) => {
                    console.error('Error updating location:', error);
                });
            }, function(error) {
                console.error("Error getting location: ", error);
            });
        } else {
            console.log("Geolocation is not supported by this browser.");
        }
    });
</script>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(position) {
                const latitude = position.coords.latitude;
                const longitude = position.coords.longitude;

                // Send the location to the server
                fetch('update_location.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        userId: <?php echo json_encode($user_id); ?>, // PHP variable for user ID
                        latitude: latitude,
                        longitude: longitude,
                        stationId: <?php echo json_encode($station_id); ?> // PHP variable for station ID
                    }),
                })
                .then(response => response.json())
                .then(data => {
                    console.log('Location updated:', data);
                })
                .catch((error) => {
                    console.error('Error updating location:', error);
                });
            }, function(error) {
                console.error("Error getting location: ", error);
            });
        } else {
            console.log("Geolocation is not supported by this browser.");
        }
    });
</script>
</body>
</html>
