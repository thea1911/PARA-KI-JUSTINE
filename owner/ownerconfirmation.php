<?php
session_start();

if (!isset($_SESSION['user_data']['payment_method'])) {
    header('Location: premiumpayment.php');
    exit();
}

include('dwos.php');

// Ensure the plan is set in session
if (!isset($_SESSION['user_data']['plan'])) {
    header('Location: Premiums.php'); // Redirect if no plan is set
    exit();
}

// Access the plan and payment method from session
$plan = $_SESSION['user_data']['plan'];
$payment_method = $_SESSION['user_data']['payment_method'];

// Handle payment confirmation
if (isset($_POST['confirm_payment'])) {
    // Retrieve user data from session
    $name = $_SESSION['user_data']['user_name'] ?? '';
    $email = $_SESSION['user_data']['email'] ?? '';
    $user_type = $_SESSION['user_data']['user_type'] ?? ''; // 'O' for owner
    $user_id = $_SESSION['user_data']['user_id'] ?? '';

    // Ensure the user is an Owner
    if (!isset($_SESSION['user_data']['user_type']) || strtoupper($_SESSION['user_data']['user_type']) !== 'O') {
        echo 'Invalid user type. Only owners can confirm payment.';
        exit();
    }

    // Get the current subscription details
    $stmt_check_subscription = $conn->prepare("SELECT * FROM subscriptions WHERE user_id = ? AND subscription_type = 'O' ORDER BY end_date DESC LIMIT 1");
    $stmt_check_subscription->bind_param("s", $user_id);
    $stmt_check_subscription->execute();
    $result = $stmt_check_subscription->get_result();

    $remaining_days = 0;
    if ($result->num_rows > 0) {
        // Fetch the current subscription details
        $current_subscription = $result->fetch_assoc();
        $current_end_date = strtotime($current_subscription['end_date']);
        $current_date = time();

        // If the current subscription has not expired, calculate the remaining days
        if ($current_end_date > $current_date) {
            $remaining_days = ceil(($current_end_date - $current_date) / (60 * 60 * 24)); // Days remaining
        }
    }

    // Add the remaining days to the new plan's duration
    $total_duration = $remaining_days + $plan['duration_in_days'];

    // Insert the new subscription into the database
    $subscription_type = 'O'; // For Owner

    // Use prepared statements for security
    $stmt_subscription = $conn->prepare("INSERT INTO subscriptions (user_id, membership_id, subscription_type, start_date, end_date, payment_method) 
                                          VALUES (?, ?, ?, NOW(), DATE_ADD(NOW(), INTERVAL ? DAY), ?)");
    $stmt_subscription->bind_param("sisss", $user_id, $plan['membership_id'], $subscription_type, $total_duration, $payment_method);

    // Execute the insert and check success
    $subscription_success = $stmt_subscription->execute();

    if ($subscription_success) {
        // Reassign session variables before redirecting
        $_SESSION['user_id'] = $user_id; // Reassign user_id to session
        $_SESSION['user_data'] = [
            'user_id' => $user_id,
            'user_name' => $name,
            'email' => $email,
            'user_type' => $user_type,
        ];

        // Redirect to the owner's page
        header('Location: /waterordering/SIGNIN/signup/Page/owner/ownerpage.php');
        exit();
    } else {
        echo "Error in payment confirmation. Please try again.";
    }

    // Close the prepared statement
    $stmt_subscription->close();
}
?>

<?php include 'ownernavbar.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirm Payment</title>
    <link rel="stylesheet" href="ownerconfirmation.css"> <!-- Add your CSS file here -->
</head>
<body>
    <div class="confirmation-container">
        <h3>Confirm Your Payment</h3>
        <div class="plan-details-container">
            <p>You have selected the <?php echo htmlspecialchars($plan['duration_in_days']); ?> days plan for ₱<?php echo htmlspecialchars($plan['price']); ?>.</p>
        </div>
        <p>Payment Method: <?php echo htmlspecialchars($payment_method); ?></p>
        <form action="" method="post">
            <div class="button-container">
                <input type="submit" name="confirm_payment" value="Confirm Payment" class="form-btn">
            </div>
        </form>
    </div>
</body>
</html>
