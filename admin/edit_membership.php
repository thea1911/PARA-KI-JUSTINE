<?php
include('dwos.php');

if (isset($_GET['id'])) {
    $membershipId = intval($_GET['id']);
    
    // Fetch membership details
    $stmt = $conn->prepare("SELECT * FROM memberships WHERE membership_id = ?");
    $stmt->bind_param("i", $membershipId);
    $stmt->execute();
    $result = $stmt->get_result();
    $membership = $result->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Membership</title>
</head>
<body>

<h1>Edit Membership</h1>
<form action="update_membership.php" method="POST">
    <input type="hidden" name="membership_id" value="<?php echo $membership['membership_id']; ?>">
    <label for="membership_name">Membership Name:</label>
    <input type="text" name="membership_name" value="<?php echo htmlspecialchars($membership['membership_name']); ?>" required>
    
    <label for="membership_type">Membership Type:</label>
    <input type="text" name="membership_type" value="<?php echo htmlspecialchars($membership['membership_type']); ?>" required>
    
    <label for="price">Price:</label>
    <input type="number" name="price" value="<?php echo htmlspecialchars($membership['price']); ?>" step="0.01" required>
    
    <label for="duration_in_days">Duration (in days):</label>
    <input type="number" name="duration_in_days" value="<?php echo htmlspecialchars($membership['duration_in_days']); ?>" required>
    
    <label for="membership_for">Membership For:</label>
    <select name="membership_for">
        <option value="O" <?php echo $membership['membership_for'] === 'O' ? 'selected' : ''; ?>>Owner</option>
        <option value="C" <?php echo $membership['membership_for'] === 'C' ? 'selected' : ''; ?>>Customer</option>
    </select>

    <button type="submit">Update Membership</button>
</form>

</body>
</html>

<!-- USED FOR PREMIUMS.PHP -->