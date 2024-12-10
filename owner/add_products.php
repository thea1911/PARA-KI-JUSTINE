<?php
// Start the session only if it hasn't been started yet
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user_id is set in the session
if (!isset($_SESSION['user_id'])) {
    die("Session variable for user_id is not set. Please log in again.");
}

include('dwos.php');

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize variables
$message = "";
$user_id = $_SESSION['user_id'];
$item_stock = 0; // Initialize item_stock to 0

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $station_id = $_POST['station_id'];
    $product_name = $_POST['product_name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $availability_status = $_POST['availability_status'];
    $product_type = $_POST['product_type'];  // Capture the product type

    // Capture item_stock only if product_type is Item
    if ($product_type == "I") {
        $item_stock = $_POST['item_stock'];  // Get item stock for "Item" products
    }

    // Handle image upload
    $image = $_FILES['image'];
    $image_name = $image['name'];
    $image_tmp = $image['tmp_name'];
    $image_size = $image['size'];
    $image_error = $image['error'];

    // Validate image
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $image_type = mime_content_type($image_tmp);

    if ($image_error === 0 && in_array($image_type, $allowed_types) && $image_size < 2000000) {
        // Define the upload directory and move the uploaded file
        $upload_dir = 'image/';
        $image_path = $upload_dir . basename($image_name);

        if (move_uploaded_file($image_tmp, $image_path)) {
            // Prepare and bind the SQL statement
            if ($product_type == "I") {
                // Insert item stock into the database for "Item" products
                $stmt = $conn->prepare("INSERT INTO products (station_id, product_name, description, price, availability_status, product_type, item_stock, image) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("isssssis", $station_id, $product_name, $description, $price, $availability_status, $product_type, $item_stock, $image_path);
            } else {
                // Insert without item stock for "Refill" products
                $stmt = $conn->prepare("INSERT INTO products (station_id, product_name, description, price, availability_status, product_type, image) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssss", $station_id, $product_name, $description, $price, $availability_status, $product_type, $image_path);
            }

            // Execute the statement
            if ($stmt->execute()) {
                $message = "Product added successfully!";
                $success = true; // Flag for success
            } else {
                $message = "Error adding product: " . $stmt->error;
                $success = false; // Flag for failure
            }
            $stmt->close();
        } else {
            $message = "Error uploading image.";
            $success = false; // Flag for failure
        }
    } else {
        $message = "Invalid image. Please upload a JPEG, PNG, or GIF file under 2MB.";
        $success = false; // Flag for failure
    }
}
?>

<?php include 'ownernavbar.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="add_products.css" />
    <title>Add Product</title>
    <script>
        // Function to show the item stock field only when "Item" is selected
        function toggleItemStockField() {
            const productType = document.getElementById('product_type').value;
            const itemStockField = document.getElementById('item-stock-container');
            if (productType === "I") {
                itemStockField.style.display = 'block'; // Show item stock input for "Item"
            } else {
                itemStockField.style.display = 'none'; // Hide item stock input for "Refill"
            }
        }

        function redirectToProducts() {
            setTimeout(function() {
                window.location.href = 'products.php';
            }, 1500); // Redirect after 1.5 seconds
        }

        function previewImage() {
            const imageInput = document.getElementById('image');
            const preview = document.getElementById('product-preview');
            const plusSign = document.querySelector('.plus-sign');

            imageInput.addEventListener('change', function () {
                const file = this.files[0];
                if (file) {
                    const reader = new FileReader();

                    reader.onload = function (e) {
                        preview.src = e.target.result;
                        preview.style.display = 'block';
                        plusSign.style.display = 'none';
                    };

                    reader.readAsDataURL(file);
                } else {
                    preview.src = '#';
                    preview.style.display = 'none';
                    plusSign.style.display = 'block';
                }
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            previewImage();
            toggleItemStockField(); // Call to initially set up the stock field visibility
            // Listen for changes in the product type dropdown
            document.getElementById('product_type').addEventListener('change', toggleItemStockField);
        });

    </script>
</head>
<body>
    <div class="product-container">
        <h1>Add New Product</h1>

        <?php if (isset($message)): ?>
            <p id="message" style="color: <?= $success ? 'green' : 'red' ?>;">
                <?php echo htmlspecialchars($message); ?>
            </p>
            <script>
                // Call the redirect function only if the product was added successfully
                <?php if ($success): ?>
                    redirectToProducts();
                <?php endif; ?>
            </script>
        <?php endif; ?>

        <form action="" method="POST" enctype="multipart/form-data">
            <div class="input-group">
                <label for="station_id">Select Station:</label>
                <select name="station_id" required>
                    <?php
                    // Fetch stations owned by the logged-in user
                    $stationQuery = "SELECT station_id, station_name FROM stations WHERE owner_id = ?";
                    $stmt = $conn->prepare($stationQuery);
                    $stmt->bind_param("s", $user_id);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    while ($row = $result->fetch_assoc()) {
                        echo '<option value="' . htmlspecialchars($row['station_id']) . '">' . htmlspecialchars($row['station_name']) . '</option>';
                    }
                    $stmt->close();
                    ?>
                </select>
            </div>

            <div class="input-group">
                <label for="product_name">Product Name:</label>
                <input type="text" name="product_name" required>
            </div>

            <div class="input-group">
                <label for="description">Description:</label>
                <textarea name="description" required></textarea>
            </div>

            <div class="input-group">
                <label for="price">Price:</label>
                <input type="number" name="price" step="0.01" required>
            </div>

            <div class="input-group">
                <label for="product_type">Product Type:</label>
                <select name="product_type" id="product_type" required>
                    <option value="R">Refill</option>
                    <option value="I">Item</option>
                </select>
            </div>

            <div class="input-group" id="item-stock-container" style="display:none;">
                <label for="item_stock">Item Stock:</label>
                <input type="number" name="item_stock" id="item_stock" min="0">
            </div>

            <div class="input-group">
                <label for="availability_status">Availability Status:</label>
                <select name="availability_status" required>
                    <option value="A">Available</option>
                    <option value="O">Out of Stock</option>
                </select>
            </div>

            <div class="input-group">
                <label for="image">Product Image:</label>
                <div class="image-upload-container">
                    <img id="product-preview" src="#" alt="Product Preview">
                    <span class="plus-sign">+</span>
                    <input type="file" name="image" id="image" required>
                </div>
            </div>

            <button type="submit">Add Product</button>
        </form>
    </div>
</body>
</html>
