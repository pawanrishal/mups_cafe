<?php
session_start();
require_once '../config/dbconfig.php';

// Check if user is logged in and is a customer
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get filter status
$filter_status = isset($_GET['status']) ? $_GET['status'] : 'all';

// Build query based on filter
$query = "SELECT o.orders_id, o.table_number, o.status, o.order_time, o.total_amount,
          GROUP_CONCAT(CONCAT(fi.food_name, ' (', oi.quantity, ')') SEPARATOR ', ') as items,
          SUM(oi.quantity) as total_items
          FROM orders o
          LEFT JOIN order_items oi ON o.orders_id = oi.order_id
          LEFT JOIN food_items fi ON oi.food_id = fi.food_items_id
          WHERE o.user_id = ?";

if($filter_status !== 'all') {
    $query .= " AND o.status = ?";
}

$query .= " GROUP BY o.orders_id ORDER BY o.order_time DESC";

$orders = null;
if($filter_status !== 'all') {
    if($stmt = $conn->prepare($query)) {
        $stmt->bind_param("is", $user_id, $filter_status);
        $stmt->execute();
        $orders = $stmt->get_result();
    }
} else {
    if($stmt = $conn->prepare($query)) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $orders = $stmt->get_result();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order History - Mups Cafe</title>
    <link rel="stylesheet" href="../assets/css/order_history.css">
</head>
<body>
    <?php include '../Partials/nav.php'; ?>

    <div class="history-container">
        <div class="history-header">
            <h1>📋 Order History</h1>
            <a href="customer_dashboard.php" class="back-btn">← Back to Dashboard</a>
        </div>

        <!-- Filter Tabs -->
        <div class="filter-tabs">
            <a href="order_history.php?status=all" class="filter-tab <?php echo $filter_status === 'all' ? 'active' : ''; ?>">
                All Orders
            </a>
            <a href="order_history.php?status=pending" class="filter-tab <?php echo $filter_status === 'pending' ? 'active' : ''; ?>">
                ⏳ Pending
            </a>
            <a href="order_history.php?status=processing" class="filter-tab <?php echo $filter_status === 'processing' ? 'active' : ''; ?>">
                👨‍🍳 Processing
            </a>
            <a href="order_history.php?status=completed" class="filter-tab <?php echo $filter_status === 'completed' ? 'active' : ''; ?>">
                ✅ Completed
            </a>
            <a href="order_history.php?status=cancelled" class="filter-tab <?php echo $filter_status === 'cancelled' ? 'active' : ''; ?>">
                ❌ Cancelled
            </a>
        </div>

        <!-- Orders List -->
        <div class="orders-list">
            <?php if($orders && $orders->num_rows > 0): ?>
                <?php while($order = $orders->fetch_assoc()): ?>
                    <div class="order-card status-<?php echo $order['status']; ?>">
                        <div class="order-header">
                            <div class="order-info">
                                <h3>Order #<?php echo $order['orders_id']; ?></h3>
                                <span class="order-date">
                                    <?php echo date('M d, Y - h:i A', strtotime($order['order_time'])); ?>
                                </span>
                            </div>
                            <div class="order-status">
                                <span class="status-badge status-<?php echo $order['status']; ?>">
                                    <?php 
                                    $status_icons = [
                                        'pending' => '⏳',
                                        'processing' => '👨‍🍳',
                                        'completed' => '✅',
                                        'cancelled' => '❌'
                                    ];
                                    echo $status_icons[$order['status']] ?? '';
                                    echo ' ' . ucfirst($order['status']); 
                                    ?>
                                </span>
                            </div>
                        </div>

                        <div class="order-body">
                            <div class="order-detail">
                                <span class="detail-label">Table Number:</span>
                                <span class="detail-value">Table <?php echo $order['table_number']; ?></span>
                            </div>

                            <div class="order-detail">
                                <span class="detail-label">Items:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($order['items'] ?? 'No items'); ?></span>
                            </div>

                            <div class="order-detail">
                                <span class="detail-label">Total Items:</span>
                                <span class="detail-value"><?php echo $order['total_items'] ?? 0; ?> items</span>
                            </div>

                            <?php if($order['total_amount']): ?>
                                <div class="order-detail">
                                    <span class="detail-label">Total Amount:</span>
                                    <span class="detail-value price">$<?php echo number_format($order['total_amount'], 2); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="order-footer">
                            <?php if($order['status'] === 'pending'): ?>
                                <span class="status-message">⏰ Your order is being prepared...</span>
                            <?php elseif($order['status'] === 'processing'): ?>
                                <span class="status-message">🔥 Your order is being cooked!</span>
                            <?php elseif($order['status'] === 'completed'): ?>
                                <span class="status-message">🎉 Order completed! Enjoy your meal!</span>
                            <?php elseif($order['status'] === 'cancelled'): ?>
                                <span class="status-message">This order was cancelled</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-orders">
                    <span class="no-orders-icon">📭</span>
                    <h2>No orders found</h2>
                    <p>
                        <?php 
                        if($filter_status !== 'all') {
                            echo "You don't have any " . $filter_status . " orders.";
                        } else {
                            echo "You haven't placed any orders yet.";
                        }
                        ?>
                    </p>
                    <a href="menu.php" class="browse-menu-btn">Browse Menu</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include '../Partials/footer.php'; ?>
</body>
</html>