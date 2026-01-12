<?php
session_start();
require_once '../config/dbconfig.php';

// Check if user is logged in
// if(!isset($_SESSION['user_id'])) {
//     header("Location: login.php");
//     exit();
// }

// $user_id = $_SESSION['user_id'];
$fullname = $_SESSION['username'] ?? 'Customer';

// Get user's order statistics (use correct column names from schema)
$stats_query = "SELECT 
    COUNT(*) as total_orders,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
    SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing_orders,
    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_orders
    FROM orders WHERE user_id = ?";
$stats_stmt = $conn->prepare($stats_query);
if($stats_stmt) {
    $stats_stmt->bind_param("i", $user_id);
    $stats_stmt->execute();
    $stats = $stats_stmt->get_result()->fetch_assoc();
    $stats_stmt->close();
} else {
    $stats = ['total_orders' => 0, 'pending_orders' => 0, 'processing_orders' => 0, 'completed_orders' => 0];
}

// Get recent orders (use correct table/column names)
$orders_query = "SELECT o.orders_id, o.status, o.order_time,
    GROUP_CONCAT(CONCAT(fi.food_name, ' (', oi.quantity, ')') SEPARATOR ', ') as items
    FROM orders o
    LEFT JOIN orders_items oi ON o.orders_id = oi.order_id
    LEFT JOIN food_items fi ON oi.food_id = fi.food_items_id
    WHERE o.user_id = ?
    GROUP BY o.orders_id
    ORDER BY o.order_time DESC
    LIMIT 5";
$orders_stmt = $conn->prepare($orders_query);
if($orders_stmt) {
    $orders_stmt->bind_param("i", $user_id);
    $orders_stmt->execute();
    $recent_orders = $orders_stmt->get_result();
    $orders_stmt->close();
} else {
    $recent_orders = null;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard - Mups Cafe</title>
    <link rel="stylesheet" href="../assets/css/customer_dashboard.css">
</head>
<body>
    <?php include '../Partials/nav.php'; ?>

    <div class="dashboard-container">
        <!-- Welcome Section -->
        <div class="welcome-section">
            <div class="welcome-content">
                <h1>Welcome back, <?php echo htmlspecialchars($fullname); ?>! ☕</h1>
                <p>Ready to order some delicious food?</p>
            </div>
            <a href="menu.php" class="btn-order-now" onclick="sessionStorage.setItem('activeLink','menu')">Order Now</a>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">📦</div>
                <div class="stat-info">
                    <h3><?php echo $stats['total_orders'] ?? 0; ?></h3>
                    <p>Total Orders</p>
                </div>
            </div>

            <div class="stat-card pending">
                <div class="stat-icon">⏳</div>
                <div class="stat-info">
                    <h3><?php echo $stats['pending_orders'] ?? 0; ?></h3>
                    <p>Pending</p>
                </div>
            </div>

            <div class="stat-card processing">
                <div class="stat-icon">👨‍🍳</div>
                <div class="stat-info">
                    <h3><?php echo $stats['processing_orders'] ?? 0; ?></h3>
                    <p>Processing</p>
                </div>
            </div>

            <div class="stat-card completed">
                <div class="stat-icon">✅</div>
                <div class="stat-info">
                    <h3><?php echo $stats['completed_orders'] ?? 0; ?></h3>
                    <p>Completed</p>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <h2>Quick Actions</h2>
            <div class="actions-grid">
                <a href="menu.php" class="action-card" onclick="sessionStorage.setItem('activeLink','menu')">
                    <span class="action-icon">🍽️</span>
                    <h3>Browse Menu</h3>
                    <p>Explore our delicious offerings</p>
                </a>

                <a href="cart.php" class="action-card" onclick="sessionStorage.setItem('activeLink','cart')">
                    <span class="action-icon">🛒</span>
                    <h3>View Cart</h3>
                    <p>Check your selected items</p>
                </a>

                <a href="order_history.php" class="action-card">
                    <span class="action-icon">📋</span>
                    <h3>Order History</h3>
                    <p>View all your orders</p>
                </a>

                <a href="#profile" class="action-card">
                    <span class="action-icon">👤</span>
                    <h3>My Profile</h3>
                    <p>Update your information</p>
                </a>
            </div>
        </div>

        <!-- Recent Orders -->
        <div class="recent-orders">
            <div class="section-header">
                <h2>Recent Orders</h2>
                <a href="order_history.php" class="view-all">View All →</a>
            </div>

            <?php if($recent_orders->num_rows > 0): ?>
                <div class="orders-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Items</th>
                                <th>Table</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($order = $recent_orders->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo $order['id']; ?></td>
                                <td class="items-cell"><?php echo htmlspecialchars($order['items']); ?></td>
                                <td>Table <?php echo $order['table_number']; ?></td>
                                <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $order['status']; ?>">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="no-orders">
                    <span class="no-orders-icon">📭</span>
                    <h3>No orders yet</h3>
                    <p>Start by browsing our menu and placing your first order!</p>
                    <a href="menu.php" class="btn-browse-menu" onclick="sessionStorage.setItem('activeLink','menu')">Browse Menu</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include '../Partials/footer.php'; ?>
</body>
</html>