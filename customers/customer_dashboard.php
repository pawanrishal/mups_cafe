<?php
session_start();
require_once '../config/dbconfig.php';

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$fullname = $_SESSION['username'] ?? 'Customer';

// Get user's order statistics (use correct column names from schema)
$stats_query = "SELECT 
    COUNT(*) as total_orders,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
    SUM(CASE WHEN status = 'preparing' THEN 1 ELSE 0 END) as preparing_orders,
    SUM(CASE WHEN status = 'served' THEN 1 ELSE 0 END) as served_orders
    FROM orders WHERE user_id = ?";
$stats_stmt = $conn->prepare($stats_query);
if($stats_stmt) {
    $stats_stmt->bind_param("i", $user_id);
    $stats_stmt->execute();
    $stats = $stats_stmt->get_result()->fetch_assoc();
    $stats_stmt->close();
} else {
    $stats = ['total_orders' => 0, 'pending_orders' => 0, 'preparing_orders' => 0, 'served_orders' => 0];
}

// Get orders by status
$order_statuses = ['pending', 'preparing', 'served'];
$user_orders = [];

foreach($order_statuses as $status) {
    $status_query = "SELECT o.orders_id, o.status, o.order_time, o.table_number, o.total_amount,
        GROUP_CONCAT(CONCAT(fi.food_name, ' (', oi.quantity, ')') SEPARATOR ', ') as items,
        SUM(oi.quantity) as total_items
        FROM orders o
        LEFT JOIN orders_items oi ON o.orders_id = oi.order_id
        LEFT JOIN food_items fi ON oi.food_id = fi.food_items_id
        WHERE o.user_id = ? AND o.status = ?
        GROUP BY o.orders_id
        ORDER BY o.order_time DESC";
    
    $status_stmt = $conn->prepare($status_query);
    if($status_stmt) {
        $status_stmt->bind_param("is", $user_id, $status);
        $status_stmt->execute();
        $result = $status_stmt->get_result();
        $user_orders[$status] = [];
        while($row = $result->fetch_assoc()) {
            $user_orders[$status][] = $row;
        }
        $status_stmt->close();
    }
}

// Get recent orders for this user
$recent_orders_query = "SELECT o.orders_id, o.table_number, o.status, o.order_time, o.total_amount,
    GROUP_CONCAT(CONCAT(fi.food_name, ' (', oi.quantity, ')') SEPARATOR ', ') as items
    FROM orders o
    LEFT JOIN orders_items oi ON o.orders_id = oi.order_id
    LEFT JOIN food_items fi ON oi.food_id = fi.food_items_id
    WHERE o.user_id = ?
    GROUP BY o.orders_id
    ORDER BY o.order_time DESC
    LIMIT 5";

$recent_orders_stmt = $conn->prepare($recent_orders_query);
if($recent_orders_stmt) {
    $recent_orders_stmt->bind_param("i", $user_id);
    $recent_orders_stmt->execute();
    $recent_orders = $recent_orders_stmt->get_result();
    $recent_orders_stmt->close();
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

            <div class="stat-card preparing">
                <div class="stat-icon">👨‍🍳</div>
                <div class="stat-info">
                    <h3><?php echo $stats['preparing_orders'] ?? 0; ?></h3>
                    <p>Preparing</p>
                </div>
            </div>

            <div class="stat-card served">
                <div class="stat-icon">✅</div>
                <div class="stat-info">
                    <h3><?php echo $stats['served_orders'] ?? 0; ?></h3>
                    <p>Served</p>
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

                <a href="customer_profile.php" class="action-card">
                    <span class="action-icon">👤</span>
                    <h3>My Profile</h3>
                    <p>Update your information</p>
                </a>
            </div>
        </div>

        <!-- Order Status Sections -->
        <?php foreach($order_statuses as $status): ?>
            <?php if(count($user_orders[$status]) > 0): ?>
                <div class="order-section">
                    <div class="section-header">
                        <h2><?php echo ucfirst($status); ?> Orders</h2>
                    </div>
                    <div class="orders-grid">
                        <?php foreach($user_orders[$status] as $order): ?>
                            <div class="order-card status-<?php echo $status; ?>">
                                <div class="order-header">
                                    <h4>Order #<?php echo $order['orders_id']; ?></h4>
                                    <span class="order-time"><?php echo date('M d, Y - h:i A', strtotime($order['order_time'])); ?></span>
                                </div>
                                <div class="order-details">
                                    <p><strong>Table:</strong> <?php echo $order['table_number']; ?></p>
                                    <p><strong>Items:</strong> <?php echo htmlspecialchars($order['items']); ?></p>
                                    <p><strong>Total:</strong> $<?php echo number_format($order['total_amount'], 2); ?> (<?php echo $order['total_items']; ?> items)</p>
                                </div>
                                <div class="order-status">
                                    <span class="status-badge status-<?php echo $status; ?>">
                                        <?php echo ucfirst($status); ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>

        <!-- Recent Orders -->
        <div class="recent-orders">
            <div class="section-header">
                <h2>Recent Orders</h2>
                <a href="order_history.php" class="view-all">View All →</a>
            </div>

            <?php if($recent_orders && $recent_orders->num_rows > 0): ?>
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
                            <?php if($recent_orders): ?>
                                <?php while($order = $recent_orders->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?php echo $order['orders_id']; ?></td>
                                    <td class="items-cell"><?php echo htmlspecialchars($order['items']); ?></td>
                                    <td>Table <?php echo $order['table_number']; ?></td>
                                    <td>$<?php echo number_format($order['total_amount'] ?? 0, 2); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $order['status']; ?>">
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($order['order_time'])); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            <?php endif; ?>
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