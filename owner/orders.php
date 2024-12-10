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

// Get the logged-in owner's user_id
$user_id = $_SESSION['user_id'];

// Fetch Owner Details
$stmt = $conn->prepare("SELECT user_name, image, password, latitude, longitude FROM users WHERE user_id = ? AND user_type = 'O'");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $user = $result->fetch_assoc();
} else {
    echo "Error fetching owner details: " . $conn->error;
    exit();
}

// Fetch the station ID associated with the owner
$station_stmt = $conn->prepare("SELECT station_id FROM stations WHERE owner_id = ?");
$station_stmt->bind_param("i", $user_id);
$station_stmt->execute();
$station_result = $station_stmt->get_result();
$station_id = $station_result->fetch_assoc()['station_id'] ?? null;

?>

<?php include 'ownernavbar.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="orders.css" />
    <title>Orders</title>
</head>
<body>

<!-- Modal Structure -->
<div id="popupModal" class="modal">
  <div class="modal-content">
    <span class="close">&times;</span>
    <p id="popupMessage"></p>
  </div>
</div>

<div id="ordersBtn">
  <h2>Manage Orders</h2>
  <div class="table-responsive">
    <table class="table table-striped">
      <thead>
        <tr>
          <th>Order Date & Time</th>
          <th>Customer(s)</th>
          <th>Station</th>
          <th>Products</th>
          <th>Total Quantity</th>
          <th>Total Price</th>
          <th>Order Status</th>
          <th>Delivery Date & Time</th>
          <th>Address(es)</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php
          // Updated SQL query to ensure proper retrieval of delivery time
          $sql = "
            SELECT 
              GROUP_CONCAT(o.order_id) AS order_ids,
              o.order_date,
              o.order_time_delivered,
              GROUP_CONCAT(DISTINCT u.user_name SEPARATOR ', ') AS customer_names,
              s.station_name,
              GROUP_CONCAT(CONCAT(p.product_name, ' (', o.quantity, ')') SEPARATOR ', ') AS product_details,
              SUM(o.quantity) AS total_quantity,
              SUM(o.total_price) AS total_price,
              MAX(o.order_status) AS order_status,
              GROUP_CONCAT(DISTINCT u.address SEPARATOR ', ') AS addresses
            FROM orders o
            JOIN users u ON o.user_id = u.user_id
            JOIN stations s ON o.station_id = s.station_id
            JOIN products p ON o.product_id = p.product_id
            WHERE o.station_id = ?
            GROUP BY o.order_date, o.order_time_delivered
            ORDER BY o.order_date DESC, o.order_time_delivered DESC";

          $stmt = $conn->prepare($sql);
          $stmt->bind_param("i", $station_id);
          $stmt->execute();
          $result = $stmt->get_result();

          if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
              $order_ids = explode(',', $row["order_ids"]);
        ?>
          <tr>
            <td><?=$row["order_date"]?></td>
            <td><?=$row["customer_names"]?></td>
            <td><?=$row["station_name"]?></td>
            <td><?=$row["product_details"]?></td>
            <td><?=$row["total_quantity"]?></td>
            <td><?=$row["total_price"]?></td>
            <td>
              <?php 
                switch($row["order_status"]) {
                  case 'P': echo '<button class="btn btn-warning">Pending</button>'; break;
                  case 'A': echo '<button class="btn btn-info">Accepted</button>'; break;
                  case 'F': echo '<button class="btn btn-primary">For Pickup</button>'; break;
                  case 'Q': echo '<button class="btn btn-secondary">Processing</button>'; break;
                  case 'S': echo '<button class="btn btn-success">Shipping</button>'; break;
                  case 'D': echo '<button class="btn btn-success">Delivered</button>'; break;
                  default:  echo '<button class="btn btn-dark">Unknown</button>';
                }
              ?>
            </td>
            <td>
              <?= !empty($row["order_time_delivered"]) ? $row["order_time_delivered"] : 'Not Delivered Yet' ?>
            </td>
            <td><?=$row["addresses"] ?: 'No address available'?></td>
            <td>
              <?php 
                $next_status = null;
                $button_label = null;

                switch($row["order_status"]) {
                  case 'P': $next_status = 'A'; $button_label = 'Accept'; break;
                  case 'A': $next_status = 'F'; $button_label = 'For Pickup'; break;
                  case 'F': $next_status = 'Q'; $button_label = 'Processing'; break;
                  case 'Q': $next_status = 'S'; $button_label = 'Ship'; break;
                  case 'S': $next_status = 'D'; $button_label = 'Delivered'; break;
                }

                if ($next_status) { ?>
                  <button class="btn btn-primary" onclick="changeOrderStatus('<?=implode(',', $order_ids)?>', '<?=$next_status?>')">
                    <?=$button_label?>
                  </button>
              <?php } else { ?>
                  <button class="btn btn-dark" disabled>Completed</button>
              <?php } ?>
            </td>
          </tr>
        <?php
            }
          } else {
            echo "<tr><td colspan='10'>No orders found.</td></tr>";
          }
        ?>
      </tbody>
    </table>
  </div>
</div>

<!-- JavaScript -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
var modal = document.getElementById('popupModal');
var closeBtn = document.getElementsByClassName('close')[0];

function showPopup(message) {
  document.getElementById('popupMessage').innerText = message;
  modal.style.display = "block";
  setTimeout(function() {
    modal.style.display = "none";
  }, 3000);
}

closeBtn.onclick = function() {
  modal.style.display = "none";
}

window.onclick = function(event) {
  if (event.target == modal) {
    modal.style.display = "none";
  }
}

function changeOrderStatus(order_ids, status) {
  $.ajax({
    url: '',
    method: 'POST',
    data: { 
      order_ids: order_ids,
      status: status
    },
    success: function() {
      showPopup('Order status updated successfully!');
      setTimeout(function() {
        location.reload();
      }, 1000);
    },
    error: function(xhr, status, error) {
      showPopup('Failed to update order status: ' + error);
    }
  });
}
</script>

<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  if (isset($_POST['order_ids']) && isset($_POST['status'])) {
      $order_ids = explode(',', $_POST['order_ids']);
      $status = $_POST['status'];

      // Debug: Log the incoming data
      error_log("Received POST Data - Order IDs: " . implode(',', $order_ids) . ", Status: " . $status);

      $placeholders = implode(',', array_fill(0, count($order_ids), '?'));

      if ($status === 'D') {
          $update_sql = "
              UPDATE orders 
              SET order_status = ?, order_time_delivered = NOW() 
              WHERE order_id IN ($placeholders)";
      } else {
          $update_sql = "
              UPDATE orders 
              SET order_status = ? 
              WHERE order_id IN ($placeholders)";
      }

      $update_stmt = $conn->prepare($update_sql);

      if ($update_stmt) {
          $types = str_repeat('i', count($order_ids));
          $update_stmt->bind_param("s$types", $status, ...$order_ids);

          if ($update_stmt->execute()) {
              // Log successful update
              error_log("Order status updated successfully for Order IDs: " . implode(',', $order_ids));
              echo "<script>showPopup('Order status updated successfully.');</script>";
          } else {
              // Log specific error if execution fails
              error_log("Error executing prepared statement: " . $update_stmt->error);
              echo "<script>showPopup('Error updating order status.');</script>";
          }
      } else {
          // Log error if statement preparation fails
          error_log("Failed to prepare the SQL statement: " . $conn->error);
          echo "<script>showPopup('Failed to prepare the SQL statement.');</script>";
      }
  } else {
      // Log if POST parameters are missing
      error_log("Missing required POST parameters: order_ids or status.");
  }
}

?>

</body>
</html>
