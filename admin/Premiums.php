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

// Fetch Admin Details from the database
$query = "SELECT * FROM users WHERE user_id = '$user_id' AND user_type = 'A'";
$result = mysqli_query($conn, $query);

if ($result && mysqli_num_rows($result) > 0) {
    $user = mysqli_fetch_assoc($result);
} else {
    echo "Error fetching admin details";
    exit();
}

// Fetch memberships from the database
$query = "SELECT * FROM memberships";
$result = mysqli_query($conn, $query);

$memberships = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $memberships[] = $row;
    }
} else {
    echo "Error fetching memberships: " . mysqli_error($conn);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="Premiums.css">
    <title>Memberships</title>
</head>

<?php include 'adminnavbar.php'; ?>

<body>

<div class="main-container">
    <?php
    // Separate memberships for Owners and Customers
    $ownerMemberships = [];
    $customerMemberships = [];

    foreach ($memberships as $membership) {
        if ($membership['membership_for'] === 'O') {
            $ownerMemberships[] = $membership;
        } elseif ($membership['membership_for'] === 'C') {
            $customerMemberships[] = $membership;
        }
    }
    ?>

    <!-- Owners Section -->
    <div class="container owner-container">
        <h2>Owners</h2>
        <div class="card-container">
            <?php if (empty($ownerMemberships)): ?>
                <p>No memberships available for Owners.</p>
            <?php else: ?>
                <?php foreach ($ownerMemberships as $membership): ?>
                    <div class="card">
                        <h3><?php echo htmlspecialchars($membership['membership_name']); ?></h3>
                        <p><strong>Price:</strong> <?php echo htmlspecialchars($membership['price']); ?></p>
                        <p><strong>Duration:</strong> <?php echo htmlspecialchars($membership['duration_in_days']); ?> days</p>
                        <button class="edit-button" onclick="openModal(<?php echo htmlspecialchars(json_encode($membership)); ?>)">Edit</button>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Customers Section -->
    <div class="container customer-container">
        <h2>Customers</h2>
        <div class="card-container">
            <?php if (empty($customerMemberships)): ?>
                <p>No memberships available for Customers.</p>
            <?php else: ?>
                <?php foreach ($customerMemberships as $membership): ?>
                    <div class="card">
                        <h3><?php echo htmlspecialchars($membership['membership_name']); ?></h3>
                        <p><strong>Price:</strong> <?php echo htmlspecialchars($membership['price']); ?></p>
                        <p><strong>Duration:</strong> <?php echo htmlspecialchars($membership['duration_in_days']); ?> days</p>
                        <button class="edit-button" onclick="openModal(<?php echo htmlspecialchars(json_encode($membership)); ?>)">Edit</button>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h2>Edit Membership</h2>
        <form id="editForm">
            <input type="hidden" id="membership_id" name="membership_id">
            <label for="membership_name">Name:</label>
            <input type="text" id="membership_name" name="membership_name" required>
            <label for="price">Price:</label>
            <input type="number" id="price" name="price" required>
            <label for="duration">Duration (days):</label>
            <input type="number" id="duration" name="duration" required>
            <button type="submit">Update</button>
        </form>
    </div>
</div>

<script>
function openModal(membership) {
    document.getElementById('membership_id').value = membership.membership_id;
    document.getElementById('membership_name').value = membership.membership_name;
    document.getElementById('price').value = membership.price;
    document.getElementById('duration').value = membership.duration_in_days;
    document.getElementById('editModal').style.display = 'flex'; // Use 'flex' to enable centering

    document.body.classList.add('no-scroll'); // Prevent scrolling when modal is open
}

function closeModal() {
    document.getElementById('editModal').style.display = 'none';
    document.body.classList.remove('no-scroll'); // Re-enable scrolling when modal is closed
}

document.getElementById('editForm').onsubmit = function(event) {
    event.preventDefault();
    alert('Membership updated successfully!');
    closeModal();
};

</script>

</body>
</html>
