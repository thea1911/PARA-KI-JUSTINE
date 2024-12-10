<?php
// Start the session only if it hasn't been started yet
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include('dwos.php');

// Check connection
if ($conn->connect_error) {
    error_log("Connection failed: " . $conn->connect_error);
    exit();
}

// Get the logged-in owner's user_id
$user_id = $_SESSION['user_id'];

// Fetch Owner Details from the database using prepared statements
$stmt = $conn->prepare("SELECT user_name, image, password FROM users WHERE user_id = ? AND user_type = 'O'");
if (!$stmt) {
    error_log("Prepare failed: (" . $conn->errno . ") " . $conn->error);
    exit();
}
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $user = $result->fetch_assoc();
} else {
    error_log("Error fetching owner details: " . $conn->error); 
    exit();
}

// Queries to calculate sales for different time periods and get order details
$current_date = date('Y-m-d');

$sales = [
    'today' => [
        "sql" => "SELECT users.user_name, products.product_name, SUM(orders.quantity) AS total_quantity, orders.total_price, orders.payment_method, orders.order_date
                  FROM orders
                  JOIN users ON orders.user_id = users.user_id
                  JOIN products ON orders.product_id = products.product_id
                  WHERE DATE(orders.order_date) = CURDATE() AND orders.order_status = 'D'
                  GROUP BY users.user_name, products.product_name, orders.payment_method, orders.order_date",
        "total" => "SELECT SUM(orders.total_price) as total 
                    FROM orders 
                    WHERE DATE(orders.order_date) = CURDATE() AND orders.order_status = 'D'"
    ],

    'week' => [
        "sql" => "SELECT users.user_name, products.product_name, SUM(orders.quantity) AS total_quantity, orders.total_price, orders.payment_method, orders.order_date
                  FROM orders
                  JOIN users ON orders.user_id = users.user_id
                  JOIN products ON orders.product_id = products.product_id
                  WHERE YEARWEEK(orders.order_date, 1) = YEARWEEK(CURDATE(), 1) AND orders.order_status = 'D'
                  GROUP BY users.user_name, products.product_name, orders.payment_method, orders.order_date",
        "total" => "SELECT SUM(orders.total_price) as total 
                    FROM orders 
                    WHERE YEARWEEK(orders.order_date, 1) = YEARWEEK(CURDATE(), 1) AND orders.order_status = 'D'"
    ],

    'month' => [
        "sql" => "SELECT users.user_name, products.product_name, SUM(orders.quantity) AS total_quantity, orders.total_price, orders.payment_method, orders.order_date
                  FROM orders
                  JOIN users ON orders.user_id = users.user_id
                  JOIN products ON orders.product_id = products.product_id
                  WHERE MONTH(orders.order_date) = MONTH(CURDATE()) AND YEAR(orders.order_date) = YEAR(CURDATE()) AND orders.order_status = 'D'
                  GROUP BY users.user_name, products.product_name, orders.payment_method, orders.order_date",
        "total" => "SELECT SUM(orders.total_price) as total 
                    FROM orders 
                    WHERE MONTH(orders.order_date) = MONTH(CURDATE()) AND YEAR(orders.order_date) = YEAR(CURDATE()) AND orders.order_status = 'D'"
    ],

    'year' => [
        "sql" => "SELECT users.user_name, products.product_name, SUM(orders.quantity) AS total_quantity, orders.total_price, orders.payment_method, orders.order_date
                  FROM orders
                  JOIN users ON orders.user_id = users.user_id
                  JOIN products ON orders.product_id = products.product_id
                  WHERE YEAR(orders.order_date) = YEAR(CURDATE()) AND orders.order_status = 'D'
                  GROUP BY users.user_name, products.product_name, orders.payment_method, orders.order_date",
        "total" => "SELECT SUM(orders.total_price) as total 
                    FROM orders 
                    WHERE YEAR(orders.order_date) = YEAR(CURDATE()) AND orders.order_status = 'D'"
    ]
];

// Fetch orders for each period and calculate total sales
$orders = [];
foreach ($sales as $period => $query) {
    $result = $conn->query($query['sql']);
    $orders[$period] = $result->fetch_all(MYSQLI_ASSOC);

    $result_total = $conn->query($query['total']);
    $row_total = $result_total->fetch_assoc();
    ${"total_sales_$period"} = $row_total['total'] ? $row_total['total'] : 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Summary</title>
    <link rel="stylesheet" href="sales.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"> <!-- Font Awesome for Icons -->
</head>
<body>

<?php include 'ownernavbar.php'; ?>

<h1>Sales Summary</h1>
<div class="summary-container">

    <!-- Button container for selecting period -->
    <div class="button-container">
        <button onclick="showSales('today')" class="sales-button active">Today's Sales</button>
        <button onclick="showSales('week')" class="sales-button">This Week's Sales</button>
        <button onclick="showSales('month')" class="sales-button">This Month's Sales</button>
        <button onclick="showSales('year')" class="sales-button">This Year's Sales</button>
    </div>

    <div class="sales-container">
        <h2 id="sales-heading">Sales</h2>
        <div id="sales-details" class="sales-details">
            <h3>Sales Information</h3>
            <table id="sales-table">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Product Name</th>
                        <th>Total Quantity</th>
                        <th>Total Price</th>
                        <th>Payment Method</th>
                        <th>Order Date</th>
                    </tr>
                </thead>
                <tbody id="sales-data"></tbody>
            </table>
            <p id="total-sales"></p> <!-- Total sales information goes here -->
        </div>
    </div>

</div>

<script>
const orders = <?php echo json_encode($orders); ?>;

function showSales(period) {
    let salesInfo = '';
    let salesHeading = '';
    let totalSales = 0;

    // Get current date details
    const currentDate = new Date();
    const currentMonth = currentDate.toLocaleString('default', { month: 'long' });
    const currentYear = currentDate.getFullYear();

    // Calculate the start and end dates for the week
    const firstDayOfWeek = new Date();
    firstDayOfWeek.setDate(currentDate.getDate() - currentDate.getDay()); // Sunday
    const lastDayOfWeek = new Date();
    lastDayOfWeek.setDate(firstDayOfWeek.getDate() + 6); // Saturday

    const formatDate = (date) => date.toISOString().split('T')[0]; // Format date to YYYY-MM-DD
    const firstDayFormatted = formatDate(firstDayOfWeek);
    const lastDayFormatted = formatDate(lastDayOfWeek);

    // Build sales information based on the selected period
    if (period === 'today') {
        salesHeading = 'Sales for Today: ' + currentDate.toLocaleDateString();
        totalSales = <?php echo $total_sales_today; ?>;
        salesInfo = 'Total Sales: ₱' + totalSales.toFixed(2);
    } else if (period === 'week') {
        salesHeading = `Sales for the Week: ${firstDayFormatted} to ${lastDayFormatted}`;
        totalSales = <?php echo $total_sales_week; ?>;
        salesInfo = 'Total Sales: ₱' + totalSales.toFixed(2);
    } else if (period === 'month') {
        salesHeading = 'Sales for ' + currentMonth + ' ' + currentYear;
        totalSales = <?php echo $total_sales_month; ?>;
        salesInfo = 'Total Sales: ₱' + totalSales.toFixed(2);
    } else if (period === 'year') {
        salesHeading = 'Sales for Year ' + currentYear;
        totalSales = <?php echo $total_sales_year; ?>;
        salesInfo = 'Total Sales: ₱' + totalSales.toFixed(2);
    }

    // Set the active button
    const buttons = document.querySelectorAll('.sales-button');
    buttons.forEach(button => {
        if (button.getAttribute('onclick').includes(period)) {
            button.classList.add('active');
        } else {
            button.classList.remove('active');
        }
    });

    // Update total sales and sales data
    document.getElementById('total-sales').innerText = salesInfo;
    document.getElementById('sales-heading').innerText = salesHeading;

    const salesData = document.getElementById('sales-data');
    salesData.innerHTML = ''; // Clear previous sales data

    // Check if there are sales for the selected period
    if (orders[period].length > 0) {
        orders[period].forEach(order => {
            const row = `<tr>
                            <td>${order.user_name}</td>
                            <td>${order.product_name}</td>
                            <td>${order.total_quantity}</td>
                            <td>₱${parseFloat(order.total_price).toFixed(2)}</td>
                            <td>${order.payment_method}</td>
                            <td>${order.order_date}</td>
                        </tr>`;
            salesData.innerHTML += row;
        });
    } else {
        // If no sales data is available, display a "No sales" message
        salesData.innerHTML = '<tr><td colspan="5">No sales data available for this period.</td></tr>';
    }

    // Always show the sales details container
    document.getElementById('sales-details').style.display = 'block';
}

// Show today's sales by default on page load, and ensure it's handled even if no sales exist
if (<?php echo $total_sales_today; ?> > 0) {
    showSales('today');
} else {
    showSales('week'); // Or any other default period you prefer
}
</script>

</body>
</html>
