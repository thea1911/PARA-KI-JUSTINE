<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include('dwos.php');

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get the logged-in admin's user_id
$user_id = $_SESSION['user_id'] ?? null;

// Fetch Admin Details
$stmt = $conn->prepare("SELECT user_name, image, password FROM users WHERE user_id = ? AND user_type = 'A'");
if (!$stmt) {
    die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
}
$stmt->bind_param("s", $user_id); // Assuming user_id is a string
$stmt->execute();
$admin_result = $stmt->get_result();

if ($admin_result && $admin_result->num_rows > 0) {
    $user = $admin_result->fetch_assoc();
} else {
    die("Error fetching admin details or invalid session.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="adminpage.css">
    <link rel="stylesheet" href="users.css">
    <title>Admin - Users</title>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modals = document.querySelectorAll('.modal');

            document.querySelectorAll('[data-modal]').forEach(button => {
                button.addEventListener('click', function () {
                    const modalId = this.getAttribute('data-modal');
                    document.getElementById(modalId).style.display = 'block';
                });
            });

            document.querySelectorAll('.close-button').forEach(button => {
                button.addEventListener('click', function () {
                    const modalId = this.getAttribute('data-close');
                    document.getElementById(modalId).style.display = 'none';
                });
            });

            modals.forEach(modal => {
                window.addEventListener('click', function (e) {
                    if (e.target === modal) {
                        modal.style.display = 'none';
                    }
                });
            });
        });
    </script>
</head>
<body>
    <?php include 'adminnavbar.php'; ?>

    <div class="header">
        <h1>Users</h1>
    </div>

    <div class="users-section">
        <!-- Owners Section -->
        <div class="container" id="owners-container">
            <h2>Owners</h2>
            <?php
            // Fetch Owners
            $sql = "SELECT user_id, user_name, email FROM users WHERE user_type = 'O' AND status = 'A' ORDER BY user_id DESC";
            $result = $conn->query($sql);
            $ownerCount = 0;

            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $user_name = !empty($row['user_name']) ? $row['user_name'] : $row['email'];
                    $ownerCount++;
                    $hiddenClass = $ownerCount > 5 ? 'hidden' : '';
                    echo "<div class='user {$hiddenClass}'><span class='user-id'>{$ownerCount}</span> {$user_name}</div>";
                }
            } else {
                echo "<p>No owners found.</p>";
            }
            ?>
            <div class="show-all">
                <button class="btn" data-modal="owners-modal">Show All</button>
            </div>
        </div>

        <!-- Owners Modal -->
        <div id="owners-modal" class="modal">
            <div class="modal-content">
                <span class="close-button" data-close="owners-modal">&times;</span>
                <h2>ALL OWNERS</h2>
                <ul class="full-list">
                    <?php
                    // Fetch all Owners for the modal
                    $result = $conn->query($sql);
                    if ($result->num_rows > 0) {
                        $modalOwnerCount = 1;
                        while ($row = $result->fetch_assoc()) {
                            $user_name = !empty($row['user_name']) ? $row['user_name'] : $row['email'];
                            echo "<li>{$modalOwnerCount}. {$user_name}</li>";
                            $modalOwnerCount++;
                        }
                    } else {
                        echo "<li>No owners found.</li>";
                    }
                    ?>
                </ul>
            </div>
        </div>

        <!-- Customers Section -->
        <div class="container" id="customers-container">
            <h2>Customers</h2>
            <?php
            // Fetch Customers
            $sql = "SELECT user_id, user_name, email FROM users WHERE user_type = 'C' AND status = 'A' ORDER BY user_id DESC";
            $result = $conn->query($sql);
            $customerCount = 0;

            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $user_name = !empty($row['user_name']) ? $row['user_name'] : $row['email'];
                    $customerCount++;
                    $hiddenClass = $customerCount > 5 ? 'hidden' : '';
                    echo "<div class='user {$hiddenClass}'><span class='user-id'>{$customerCount}</span> {$user_name}</div>";
                }
            } else {
                echo "<p>No customers found.</p>";
            }
            ?>
            <div class="show-all">
                <button class="btn" data-modal="customers-modal">Show All</button>
            </div>
        </div>

        <!-- Customers Modal -->
        <div id="customers-modal" class="modal">
            <div class="modal-content">
                <span class="close-button" data-close="customers-modal">&times;</span>
                <h2>ALL CUSTOMERS</h2>
                <ul class="full-list">
                    <?php
                    // Fetch all Customers for the modal
                    $result = $conn->query($sql);
                    if ($result->num_rows > 0) {
                        $modalCustomerCount = 1;
                        while ($row = $result->fetch_assoc()) {
                            $user_name = !empty($row['user_name']) ? $row['user_name'] : $row['email'];
                            echo "<li>{$modalCustomerCount}. {$user_name}</li>";
                            $modalCustomerCount++;
                        }
                    } else {
                        echo "<li>No customers found.</li>";
                    }
                    ?>
                </ul>
            </div>
        </div>
    </div>
</body>
</html>
