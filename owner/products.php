<?php
// Start the session only if it hasn't been started yet
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user_id is set in the session
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); // Redirect to login page
    exit();
}

include('dwos.php');

// Check connection
if ($conn->connect_error) {
    error_log("Connection failed: " . $conn->connect_error);
    die("Database connection error. Please try again later.");
}

$user_id = $_SESSION['user_id'];

// Fetch Owner Details
$stmt = $conn->prepare("SELECT user_name, image, password FROM users WHERE user_id = ? AND user_type = 'O'");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $user = $result->fetch_assoc();
} else {
    error_log("Error fetching owner details: " . $conn->error);
    exit("Error fetching owner details. Please try again later.");
}
$stmt->close();

// Handle product updates
// Handle product availability status update
if (isset($_POST['product_id'], $_POST['new_status'])) {
    $product_id = $_POST['product_id'];
    $new_status = $_POST['new_status'];

    if ($new_status == 'O') {
        // Set stock to 0 if the product type is 'I'
        $update_query = "UPDATE products SET availability_status = ?, item_stock = 0 WHERE product_id = ?";
    } else {
        $update_query = "UPDATE products SET availability_status = ? WHERE product_id = ?";
    }

    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("si", $new_status, $product_id);

    if ($stmt->execute()) {
        echo 'success';
    } else {
        echo 'error';
    }

    $stmt->close();
    exit();
}


if (isset($_POST['update_stock'], $_POST['new_stock'])) {
    $product_id = $_POST['update_stock'];
    $new_stock = (int)$_POST['new_stock'];

    $update_stock_query = "UPDATE products SET item_stock = ?, availability_status = 'A' WHERE product_id = ?";
    $stmt = $conn->prepare($update_stock_query);
    $stmt->bind_param("ii", $new_stock, $product_id);

    if ($stmt->execute()) {
        echo 'success';
    } else {
        echo 'error';
    }

    $stmt->close();
    exit();
}

// Handle shipping fee update
if (isset($_POST['station_id'], $_POST['shipping_fee'])) {
    $station_id = $_POST['station_id'];
    $shipping_fee = (float)$_POST['shipping_fee'];

    // Check if a shipping fee already exists for the station
    $checkQuery = "SELECT fee_id FROM shipping_fees WHERE station_id = ?";
    $stmt = $conn->prepare($checkQuery);
    $stmt->bind_param("i", $station_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Update existing shipping fee
        $updateQuery = "UPDATE shipping_fees SET shipping_fee = ? WHERE station_id = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("di", $shipping_fee, $station_id);
    } else {
        // Insert new shipping fee
        $insertQuery = "INSERT INTO shipping_fees (station_id, shipping_fee) VALUES (?, ?)";
        $stmt = $conn->prepare($insertQuery);
        $stmt->bind_param("id", $station_id, $shipping_fee);
    }

    // Execute the prepared statement
    if ($stmt->execute()) {
        // Sending response to be handled by JavaScript
        echo json_encode(['status' => 'success', 'message' => 'Shipping fee updated successfully.']);
    } else {
        // Sending error response to be handled by JavaScript
        echo json_encode(['status' => 'error', 'message' => 'Error updating shipping fee: ' . $conn->error]);
    }

    $stmt->close();
    exit(); // Ensure you exit after handling the AJAX request
}
?>

<?php include 'ownernavbar.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="products.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <title>Manage Products</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <div class="product-container">
        <h1>MANAGE PRODUCTS</h1>
        
        <a href="add_products.php" class="add-product-link">
            <i class="fas fa-plus-circle"></i> Add Product
        </a>
        
        <button id="open-modal" class="shipping-btn">Set Shipping Fee</button>

        <div class="product-list">

<div id="shipping-fee-modal" class="shipping-modal" style="display:none;">
    <div class="shipping-modal-content">
        <span class="close-button">&times;</span>
        <h2>Set Shipping Fee</h2>
        <form id="shipping-fee-form">
            <?php
            // Fetch stations based on the owner
            $stationQuery = "SELECT station_id, station_name FROM stations WHERE owner_id = ?";
            $stmt = $conn->prepare($stationQuery);
            $stmt->bind_param("s", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($row = $result->fetch_assoc()) {
                echo '<p>' . htmlspecialchars($row['station_name']) . '</p>';
                echo '<input type="hidden" name="station_id" value="' . htmlspecialchars($row['station_id']) . '">';
            } else {
                echo '<p>No stations found.</p>';
                $stmt->close();
                return; // Exit the modal if no station exists
            }
            $stmt->close();
            ?>

            <label for="shipping_fee">Shipping Fee:</label>
            <input type="number" name="shipping_fee" id="shipping_fee" min="0" step="0.01" required>

            <button type="submit">Update Shipping Fee</button>
        </form>
    </div>
</div>

        <?php
        $stationQuery = "SELECT station_id FROM stations WHERE owner_id = ?";
        $stmt = $conn->prepare($stationQuery);
        $stmt->bind_param("s", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $station_ids = [];
        while ($row = $result->fetch_assoc()) {
            $station_ids[] = $row['station_id'];
        }
        $stmt->close();

        if (!empty($station_ids)) {
            $placeholders = implode(',', array_fill(0, count($station_ids), '?'));
            $productQuery = "SELECT product_id, product_name, description, price, availability_status, image, product_type, item_stock 
                             FROM products 
                             WHERE station_id IN ($placeholders)";
            $stmt = $conn->prepare($productQuery);
            $stmt->bind_param(str_repeat("i", count($station_ids)), ...$station_ids);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                echo '<div class="product-list-item">';
                // Inside the while loop where products are displayed
while ($product = $result->fetch_assoc()) {
    if ($product['product_type'] == 'I' && $product['item_stock'] == 0 && $product['availability_status'] != 'O') {
        $updateStatusQuery = "UPDATE products SET availability_status = 'O' WHERE product_id = ?";
        $updateStmt = $conn->prepare($updateStatusQuery);
        $updateStmt->bind_param("i", $product['product_id']);
        $updateStmt->execute();
        $updateStmt->close();
        $product['availability_status'] = 'O';
    }

    echo '<div class="product-item">';
    echo '<img src="' . htmlspecialchars($product['image']) . '" alt="' . htmlspecialchars($product['product_name']) . '" class="product-image">';
    echo '<div class="product-details">';
    echo '<h2>' . htmlspecialchars($product['product_name']) . ' (' . htmlspecialchars($product['description']) . ')</h2>';
    echo '<p class="price">Price: ₱' . number_format($product['price'], 2) . '</p>';
    
    // Only show stock if the product type is "I-item"
    if ($product['product_type'] == 'I') {
        echo '<p id="stock-display-' . $product['product_id'] . '">Stock: ' . $product['item_stock'] . '</p>';
        echo '<div id="stock-input-' . $product['product_id'] . '" style="display:none;">
                <input type="number" id="stock_' . $product['product_id'] . '" min="1" placeholder="Enter new stock">
                <button class="update-stock-button" data-product-id="' . $product['product_id'] . '">Update Stock</button>
              </div>';
    }

    echo '<p>Status: <span id="availability_' . $product['product_id'] . '">' . ($product['availability_status'] == 'A' ? 'Available' : 'Out of Stock') . '</span></p>';
    echo '<button class="availability-button" 
            data-product-id="' . $product['product_id'] . '" 
            data-current-status="' . $product['availability_status'] . '" 
            data-product-type="' . $product['product_type'] . '">'
            . ($product['availability_status'] == 'A' ? 'Available' : 'Out of Stock') . 
          '</button>';
    echo '</div>';
    echo '</div>';
}
                echo '</div>';
            } else {
                echo '<p>No products found for your stations.</p>';
            }
            $stmt->close();
        } else {
            echo '<p>You do not own any stations with products.</p>';
        }
        ?>
    </div>
    </div>

    <script>
        $(document).ready(function() {
    $(".availability-button").click(function() {
        var button = $(this);
        var productId = button.data("product-id");
        var currentStatus = button.data("current-status");
        var productType = button.data("product-type");
        var newStatus = (currentStatus === 'A') ? 'O' : 'A';

        $.ajax({
            url: '', 
            method: 'POST',
            data: { product_id: productId, new_status: newStatus },
            success: function(response) {
                if (response === 'success') {
                    // Update availability status
                    $('#availability_' + productId).text(newStatus === 'A' ? 'Available' : 'Out of Stock');
                    button.text(newStatus === 'A' ? 'Available' : 'Out of Stock');
                    button.data('current-status', newStatus);

                    // Update stock to 0 in real-time when the product is Out of Stock
                    if (newStatus === 'O' && productType === 'I') {
                        $('#stock-display-' + productId).text('Stock: 0');
                        $('#stock-input-' + productId).hide();
                    }

                    // If the product type is "I" and status is "Available", show stock input
                    if (productType === 'I' && newStatus === 'A') {
                        $('#stock-input-' + productId).show();
                    }
                } else {
                    alert('Failed to update availability status. Please try again.');
                }
            },
            error: function() {
                alert('Error occurred while updating availability status.');
            }
        });
    });

    $(".update-stock-button").click(function() {
        var button = $(this);
        var productId = button.data("product-id");
        var newStock = $('#stock_' + productId).val();

        $.ajax({
            url: '',
            method: 'POST',
            data: { update_stock: productId, new_stock: newStock },
            success: function(response) {
                if (response === 'success') {
                    $('#stock-display-' + productId).text('Stock: ' + newStock);
                    $('#stock-input-' + productId).hide();
                } else {
                    alert('Failed to update stock. Please try again.');
                }
            },
            error: function() {
                alert('Error occurred while updating stock.');
            }
        });
    });
});
    </script>
    <script>
    $(document).ready(function() {
    // Open the modal when the button is clicked
    $("#open-modal").click(function() {
        $("#shipping-fee-modal").show();
    });

    // Close the modal when the close button is clicked
    $(".close-button").click(function() {
        $("#shipping-fee-modal").hide();
    });

    // Close the modal when clicking outside of it
    $(window).click(function(event) {
        if ($(event.target).is("#shipping-fee-modal")) {
            $("#shipping-fee-modal").hide();
        }
    });

    // Handle form submission
    $("#shipping-fee-form").submit(function(event) {
        event.preventDefault(); // Prevent the form from submitting normally

        $.ajax({
            url: '', // Your PHP file where the form is processed
            method: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                let data = JSON.parse(response);
                if (data.status === 'success') {
                    // Create a custom popup message
                    showPopup(data.message);
                } else {
                    // Show error message in popup
                    showPopup(data.message);
                }
                $("#shipping-fee-modal").hide(); // Close modal
            },
            error: function() {
                alert('Error occurred while updating shipping fee.');
            }
        });
    });

    // Function to show the popup
function showPopup(message) {
    const popup = document.createElement('div');
    popup.classList.add('popup-message');
    
    const popupContent = document.createElement('div');
    popupContent.classList.add('popup-message-content');
    popupContent.textContent = message;
    
    popup.appendChild(popupContent);
    document.body.appendChild(popup);

    // Show the popup
    popup.style.display = 'block';

    // Automatically remove the popup after 3 seconds without delay
    setTimeout(function() {
        popup.remove(); // Remove the popup after 3 seconds
    }, 1000); // Popup will be removed after 3 seconds
}
});
</script>
</body>
</html>
