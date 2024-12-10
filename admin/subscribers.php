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

// Function to fetch subscribers
function fetchSubscribers($conn, $subscriptionType) {
    $subscribers = [];
    $stmt = $conn->prepare("SELECT u.user_name, s.end_date FROM subscriptions s JOIN users u ON s.user_id = u.user_id WHERE s.subscription_type = ?");
    if ($stmt === false) {
        error_log("MySQL prepare error: " . $conn->error);
        return []; // Return empty array on error
    }
    $stmt->bind_param("s", $subscriptionType);
    $stmt->execute();
    $result = $stmt->get_result();

    if (!$result) {
        error_log("Query error: " . $conn->error);
        return []; // Return empty array on error
    }

    while ($row = $result->fetch_assoc()) {
        $subscribers[] = $row;
    }

    $stmt->close();
    return $subscribers;
}

// Fetch owners and customers
$owners = fetchSubscribers($conn, 'O');
$customers = fetchSubscribers($conn, 'C');

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscribers</title>
    <link rel="stylesheet" href="subscribers.css">    
</head>
<body>

<?php include 'adminnavbar.php'; ?>

<div class="container">
    <h1>Subscribers</h1>
    
    <button class="sales-button" onclick="window.location.href='sales.php';">Sales</button>

    <div class="subscribers-section">
        <div class="owners-container">
            <h2>Station Owners</h2>
            <ul id="owners-list">
                <?php foreach ($owners as $index => $owner): ?>
                    <li class="card <?php echo ($index >= 3) ? 'hidden' : ''; ?>" data-end-date="<?php echo $owner['end_date']; ?>">
                        <div class="card-id"><?php echo ($index + 1); ?></div>
                        <h3><?php echo htmlspecialchars($owner['user_name']); ?></h3>
                        <?php
                            // Calculate days left
                            $endDate = new DateTime($owner['end_date']);
                            $daysLeft = $endDate->diff(new DateTime())->format("%r%a");

                            if ($daysLeft < 0) {
                                $daysLeftDisplay = "Subscription expired"; // Display when expired
                            } else {
                                $daysLeftDisplay = $daysLeft . " Days left"; // Show remaining days
                            }
                        ?>
                        <p>- <span class="days-left"><?php echo $daysLeftDisplay; ?></span></p>
                    </li>
                <?php endforeach; ?>
            </ul>
            <button id="show-owners" data-shown="false">Show all</button>
        </div>

        <div class="customers-container">
            <h2>Customers</h2>
            <?php if (count($customers) > 0): ?>
                <ul id="customers-list">
                    <?php foreach ($customers as $index => $customer): ?>
                        <li class="card <?php echo ($index >= 3) ? 'hidden' : ''; ?>" data-end-date="<?php echo $customer['end_date']; ?>">
                            <div class="card-id"><?php echo ($index + 1); ?></div>
                            <h3><?php echo htmlspecialchars($customer['user_name']); ?></h3>
                            <?php
                                // Calculate days left
                                $endDate = new DateTime($customer['end_date']);
                                $daysLeft = $endDate->diff(new DateTime())->format("%r%a");

                                if ($daysLeft < 0) {
                                    $daysLeftDisplay = "Subscription expired"; // Display when expired
                                } else {
                                    $daysLeftDisplay = $daysLeft . " Days left"; // Show remaining days
                                }
                            ?>
                            <p>- <span class="days-left"><?php echo $daysLeftDisplay; ?></span></p>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p>No customer subscribers yet.</p>
            <?php endif; ?>
            <button id="show-customers" data-shown="false">Show all</button>
        </div>
    </div>

    <!-- Modal for displaying all subscribers -->
<div id="owners-modal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>All Station Owners</h2>
        <ul id="owners-modal-list">
            <?php foreach ($owners as $index => $owner): ?>
                <li class="card" data-end-date="<?php echo $owner['end_date']; ?>">
                    <div class="card-id"><?php echo ($index + 1); ?></div>
                    <h3><?php echo htmlspecialchars($owner['user_name']); ?></h3>
                    <p>- <span class="days-left"><?php
                        // Calculate days left
                        $endDate = new DateTime($owner['end_date']);
                        $daysLeft = $endDate->diff(new DateTime())->format("%r%a");

                        if ($daysLeft < 0) {
                            echo "Subscription expired";
                        } else {
                            echo $daysLeft . " Days left";
                        }
                    ?></span></p>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>

<div id="customers-modal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>All Customers</h2>
        <ul id="customers-modal-list">
            <?php foreach ($customers as $index => $customer): ?>
                <li class="card" data-end-date="<?php echo $customer['end_date']; ?>">
                    <div class="card-id"><?php echo ($index + 1); ?></div>
                    <h3><?php echo htmlspecialchars($customer['user_name']); ?></h3>
                    <p>- <span class="days-left"><?php
                        // Calculate days left
                        $endDate = new DateTime($customer['end_date']);
                        $daysLeft = $endDate->diff(new DateTime())->format("%r%a");

                        if ($daysLeft < 0) {
                            echo "Subscription expired";
                        } else {
                            echo $daysLeft . " Days left";
                        }
                    ?></span></p>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>


    <script>
    document.addEventListener("DOMContentLoaded", function() {
        // Function to calculate the remaining days from today's date to the end date
        function calculateDaysLeft(endDate) {
            const today = new Date();
            const end = new Date(endDate);
            const timeDiff = end.getTime() - today.getTime();
            const daysLeft = Math.ceil(timeDiff / (1000 * 3600 * 24)); // Convert milliseconds to days
            return daysLeft > 0 ? daysLeft + ' Days left' : 'Subscription expired'; // Return days left or expired message
        }

        // Update the countdown dynamically for each subscriber
        document.querySelectorAll('.card').forEach(function(card) {
            const endDate = card.getAttribute('data-end-date');
            const daysLeftElement = card.querySelector('.days-left');

            // Calculate and update the days left
            const daysLeft = calculateDaysLeft(endDate);
            daysLeftElement.textContent = daysLeft;
        });

        // Modal functions
        function showModal(modalId) {
            const modal = document.getElementById(modalId);
            modal.style.display = "block";
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            modal.style.display = "none";
        }

        // Event listeners for modal open and close
        document.getElementById("show-owners").addEventListener("click", function() {
            showModal("owners-modal");
        });

        document.getElementById("show-customers").addEventListener("click", function() {
            showModal("customers-modal");
        });

        document.querySelectorAll(".close").forEach(function(closeButton) {
            closeButton.addEventListener("click", function() {
                closeModal("owners-modal");
                closeModal("customers-modal");
            });
        });

        window.addEventListener("click", function(event) {
            if (event.target.classList.contains("modal")) {
                closeModal("owners-modal");
                closeModal("customers-modal");
            }
        });
    });
    </script>

</body>
</html>
