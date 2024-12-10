<?php
include('dwos.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $membershipId = intval($_POST['membership_id']);
    $membershipName = $_POST['membership_name'];
    $membershipType = $_POST['membership_type'];
    $price = floatval($_POST['price']);
    $durationInDays = intval($_POST['duration_in_days']);
    $membershipFor = $_POST['membership_for'];

    // Update membership in the database
    $stmt = $conn->prepare("UPDATE memberships SET membership_name = ?, membership_type = ?, price = ?, duration_in_days = ?, membership_for = ? WHERE membership_id = ?");
    $stmt->bind_param("ssdisi", $membershipName, $membershipType, $price, $durationInDays, $membershipFor, $membershipId);
    
    if ($stmt->execute()) {
        echo "Membership updated successfully!";
    } else {
        echo "Error updating membership: " . $conn->error;
    }
    
    $stmt->close();
}
?>

<!-- USED FOR PREMIUMS.PHP -->