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

// Fetch top 3 subscribers based on remaining days
$topSubscribersSql = "SELECT u.user_name, station_name, 
                      DATEDIFF(s.end_date, CURDATE()) AS remaining_days
                      FROM subscriptions s 
                      JOIN users u ON s.user_id = u.user_id 
                      JOIN memberships m ON s.membership_id = m.membership_id 
                      JOIN stations st ON st.owner_id = u.user_id
                      ORDER BY remaining_days DESC 
                      LIMIT 3"; 
$topSubscribersResult = $conn->query($topSubscribersSql);
if (!$topSubscribersResult) {
    die("Error fetching top subscribers: " . $conn->error);
}

// Fetch top 3 selling stations based on total quantity sold
$topSellingStationsSql = "
    SELECT st.station_name, SUM(o.quantity) AS total_sold
    FROM orders o
    JOIN products p ON o.product_id = p.product_id
    JOIN stations st ON p.station_id = st.station_id
    GROUP BY st.station_name
    ORDER BY total_sold DESC
    LIMIT 3";
$topSellingStationsResult = $conn->query($topSellingStationsSql);
if (!$topSellingStationsResult) {
    die("Error fetching top selling stations: " . $conn->error);
}

// Fetch all subscribers for modal with dynamic remaining days calculation
$allSubscribersSql = "SELECT u.user_name, station_name, 
                      DATEDIFF(s.end_date, CURDATE()) AS remaining_days
                      FROM subscriptions s 
                      JOIN users u ON s.user_id = u.user_id 
                      JOIN memberships m ON s.membership_id = m.membership_id 
                      JOIN stations st ON st.owner_id = u.user_id
                      ORDER BY remaining_days DESC"; 
$allSubscribersResult = $conn->query($allSubscribersSql);
if (!$allSubscribersResult) {
    die("Error fetching all subscribers: " . $conn->error);
}

// Close the connection
$conn->close();
?>
<?php include 'adminnavbar.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="adminpage.css" />
    <title>Admin Page</title>
</head>
<body>
    <div class="home-container">
        <!-- Top Selling Stations Section -->
<section class="top-selling">
    <h2>TOP SELLING STATIONS</h2>
    <ul class="list">
        <?php
        // Display the top selling stations with numbering
        if ($topSellingStationsResult->num_rows > 0) {
            $rank = 1; // Initialize rank
            while ($row = $topSellingStationsResult->fetch_assoc()) {
                echo "<li class='home'><span class='home-id'>{$rank}.</span>{$row['station_name']} - {$row['total_sold']} sold</li>";
                $rank++; // Increment rank
            }
        } else {
            echo "<li class='home'>No sales found.</li>";
        }
        ?>
    </ul>
    <div class="show-all">
        <button class="btn" data-modal="top-selling-modal">Show All</button>
    </div>

<!-- Modal for Top Selling Stations -->
<div id="top-selling-modal" class="modal">
    <div class="modal-content">
     <span class="close-button" data-close="top-selling-modal">&times;</span>
        <h2>ALL TOP SELLING STATIONS</h2>
        <ul class="full-list">
            <?php
            // Fetch all selling stations for modal
            $allSellingStationsSql = "
                SELECT st.station_name, SUM(o.quantity) AS total_sold
                FROM orders o
                JOIN products p ON o.product_id = p.product_id
                JOIN stations st ON p.station_id = st.station_id
                GROUP BY st.station_name
                ORDER BY total_sold DESC";
            $allSellingStationsResult = $conn->query($allSellingStationsSql);
            if ($allSellingStationsResult && $allSellingStationsResult->num_rows > 0) {
                $rank = 1;
                while ($row = $allSellingStationsResult->fetch_assoc()) {
                    echo "<li><span class='home-id'>{$rank}.</span>" . htmlspecialchars($row['station_name']) . " - " . htmlspecialchars($row['total_sold']) . " sold</li>";
                    $rank++;
                }
            } else {
                echo "<li>No selling stations found.</li>";
            }
            ?>
        </ul>
    </div>
</div>
</section>

        <!-- Top Subscribers Section -->
        <section class="top-subscriber">
            <h2>TOP SUBSCRIBERS</h2>
            <ul class="list">
                <?php
                // Display the top 3 subscribers
                if ($topSubscribersResult->num_rows > 0) {
                    $rank = 1;
                    while ($row = $topSubscribersResult->fetch_assoc()) {
                        echo "<li class='home'><span class='home-id'>{$rank}.</span>{$row['station_name']} - {$row['remaining_days']} days left</li>";
                        $rank++;
                    }
                } else {
                    echo "<li class='home'>No subscribers found.</li>";
                }
                ?>
            </ul>
            <div class="show-all">
                <button class="btn" data-modal="top-subscriber-modal">Show All</button>
            </div>
        </section>
    </div>

   <!-- Modal for Top Subscribers -->
<div id="top-subscriber-modal" class="modal">
    <div class="modal-content">
        <span class="close-button" data-close="top-subscriber-modal">&times;</span>
        <h2>ALL TOP SUBSCRIBERS</h2>
        <ul class="full-list">
            <?php
            // Display all subscribers in the modal
            if ($allSubscribersResult->num_rows > 0) {
                $rank = 1;
                while ($row = $allSubscribersResult->fetch_assoc()) {
                    echo "<li><span class='home-id'>{$rank}.</span>{$row['station_name']} - {$row['remaining_days']} days left</li>";
                    $rank++;
                }
            } else {
                echo "<li>No subscribers found.</li>";
            }
            ?>
        </ul>
    </div>
</div>

    <script>
    // Function to open modal
    function openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = 'flex'; // Show modal
        }
    }

    // Function to close modal
    function closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = 'none'; // Hide modal
        }
    }

    // Attach event listeners to "Show All" buttons
    document.querySelectorAll('.show-all .btn').forEach(button => {
        button.addEventListener('click', () => {
            const modalId = button.getAttribute('data-modal');
            openModal(modalId);
        });
    });

    // Attach event listeners to close buttons
    document.querySelectorAll('.close-button').forEach(button => {
        button.addEventListener('click', () => {
            const modalId = button.getAttribute('data-close');
            closeModal(modalId);
        });
    });

    // Close modal when clicking outside the modal content
    window.addEventListener('click', (event) => {
        if (event.target.classList.contains('modal')) {
            closeModal(event.target.id);
        }
    });
    </script>
</body>
</html>
