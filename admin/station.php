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

// Get the logged-in admin's user_id
$user_id = $_SESSION['user_id'];

// Fetch Admin Details from the database using prepared statements
$stmt = $conn->prepare("SELECT user_name, image, password FROM users WHERE user_id = ? AND user_type = 'A'");
if (!$stmt) {
    echo "Prepare failed: (" . $conn->errno . ") " . $conn->error;
    exit();
}
$stmt->bind_param("s", $user_id); // Changed to "s" assuming user_id is a string
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $user = $result->fetch_assoc();
} else {
    echo "Error fetching admin details: " . $conn->error; 
    exit();
}

// Fetch top 3 selling stations based on total quantity sold (from adminpage.php)
$topSellingStationsSql = "
    SELECT st.station_name, SUM(o.quantity) AS total_sold
    FROM orders o
    JOIN products p ON o.product_id = p.product_id
    JOIN stations st ON st.station_id = p.station_id
    GROUP BY st.station_name
    ORDER BY total_sold DESC
    LIMIT 3";

$topSellingStationsResult = $conn->query($topSellingStationsSql);

if (!$topSellingStationsResult) {
    die("Error fetching top selling stations: " . $conn->error);
}

$topSellingStations = [];
if ($topSellingStationsResult->num_rows > 0) {
    while ($row = $topSellingStationsResult->fetch_assoc()) {
        $topSellingStations[] = $row;
    }
}

// Query to get all top selling stations for "Show All"
$sqlAllTopSelling = "
    SELECT st.station_name, SUM(o.quantity) AS total_sold
    FROM orders o
    JOIN products p ON o.product_id = p.product_id
    JOIN stations st ON st.station_id = p.station_id
    GROUP BY st.station_name
    ORDER BY total_sold DESC";

$resultAllTopSelling = $conn->query($sqlAllTopSelling);

$allTopSellingStations = [];
if ($resultAllTopSelling->num_rows > 0) {
    while ($row = $resultAllTopSelling->fetch_assoc()) {
        $allTopSellingStations[] = $row;
    }
}

// Query to get newly added stations based on station_id (this part is intact)
$sqlNewStations = "
    SELECT s.station_name
    FROM stations s
    JOIN users u ON s.owner_id = u.user_id
    WHERE u.user_type = 'O'
    ORDER BY s.station_id DESC
    LIMIT 3"; // Limit to 3 for initial display

$resultNewStations = $conn->query($sqlNewStations);

$newStations = [];
if ($resultNewStations->num_rows > 0) {
    while ($row = $resultNewStations->fetch_assoc()) {
        $newStations[] = $row;
    }
}

// Query to get all newly added stations for "Show All"
$sqlAllNewStations = "
    SELECT s.station_name
    FROM stations s
    JOIN users u ON s.owner_id = u.user_id
    WHERE u.user_type = 'O'
    ORDER BY s.station_id DESC";

$resultAllNewStations = $conn->query($sqlAllNewStations);

$allNewStations = [];
if ($resultAllNewStations->num_rows > 0) {
    while ($row = $resultAllNewStations->fetch_assoc()) {
        $allNewStations[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="station.css">
    <title>Water Stations</title>
</head>
<body>
    
    <?php include 'adminnavbar.php'; ?>

    <div class="header">
        <h1>Water Stations</h1>
    </div>

    <div class="home-container">
    <section id="top-selling" class="top-selling-container">
            <h2>Top Selling Stations</h2>
            <?php if (!empty($topSellingStations)): ?>
                <ul>
                    <?php 
                    $count = 1; // Initialize a counter for row numbers
                    foreach ($topSellingStations as $station): ?>
                        <li home-id="top-selling-<?php echo $count; ?>" class="station-item">
                            <span class="station-number"><?php echo $count; ?>.</span>
                            <span class="station-name"><?php echo htmlspecialchars($station['station_name']);?></span>
                            <span class="station-sales">-<?php echo htmlspecialchars($station['total_sold']); ?> sold</span>
                        </li>
                    <?php 
                    $count++; // Increment the counter for the next station
                    endforeach; ?>
                </ul>
                <button id="show-all-top-selling" class="show-all-button" onclick="showAllTopSelling()">Show All</button>
            <?php else: ?>
                <p class="centered">No top selling stations found.</p>
            <?php endif; ?>
        </section>

        <section id="new-stations" class="new-stations-container">
            <h2>Newly Added Stations</h2>
            <?php if (!empty($newStations)): ?>
                <ul>
                    <?php 
                    $count = 1; // Reset counter for new stations
                    foreach ($newStations as $station): ?>
                        <li home-id="new-station-<?php echo $count; ?>" class="station-item">
                            <span class="station-number"><?php echo $count; ?>.</span>
                            <span><?php echo $station['station_name']; ?></span>
                        </li>
                    <?php 
                    $count++; // Increment the counter for each new station
                    endforeach; ?>
                    
                    <button id="show-all-new-stations" class="show-all-button" onclick="showAllNewStations()">Show All</button>
                </ul>
            <?php else: ?>
                <p class="centered">No newly added stations found.</p>
            <?php endif; ?>
        </section>
    </div>

     <!-- Modal for Top Selling Stations -->
     <div id="top-selling-modal" class="modal hidden">
        <div class="modal-content">
            <span class="close" onclick="closeTopSellingModal()">&times;</span>
            <h2>All Top Selling Stations</h2>
            <ul id="modal-top-selling-list">
                <?php foreach ($allTopSellingStations as $index => $station): ?>
                    <li class="station-item">
                        <span class="station-number"><?php echo $index + 1; ?>.</span>
                        <span class="station-name"><?php echo htmlspecialchars($station['station_name']); ?></span>
                        <span class="station-sales">-<?php echo htmlspecialchars($station['total_sold']); ?> sold</span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <!-- Modal for Newly Added Stations -->
    <div id="new-stations-modal" class="modal hidden">
        <div class="modal-content">
            <span class="close" onclick="closeNewStationsModal()">&times;</span>
            <h2>All Newly Added Stations</h2>
            <ul id="modal-new-stations-list">
                <?php foreach ($allNewStations as $index => $station): ?>
                    <li class="station-item">
                        <span class="station-number"><?php echo $index + 1; ?></span>
                        <span><?php echo $station['station_name']; ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <script>
        function showAllTopSelling() {
            document.getElementById('top-selling-modal').classList.remove('hidden');
        }

        function closeTopSellingModal() {
            document.getElementById('top-selling-modal').classList.add('hidden');
        }

        function showAllNewStations() {
            document.getElementById('new-stations-modal').classList.remove('hidden');
        }

        function closeNewStationsModal() {
            document.getElementById('new-stations-modal').classList.add('hidden');
        }
    </script>
</body>
</html>
