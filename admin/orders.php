<?php
session_start();
require_once '../config/dbconfig.php';

// Check if user is logged in and is admin
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../customers/login.php");
    exit();
}

$admin_name = $_SESSION['username'];
$success = '';
$error = '';

// Handle status updates
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $order_id = (int)$_POST['order_id'];
    
    $new_status = '';
    if($action === 'process') {
        $new_status = 'preparing';
    } elseif($action === 'complete') {
        $new_status = 'served';
    } elseif($action === 'cancel') {
        $new_status = 'cancelled';
    }
    
    if($new_status) {
        $update_query = "UPDATE orders SET status = ? WHERE orders_id = ?";
        if($stmt = $conn->prepare($update_query)) {
            $stmt->bind_param("si", $new_status, $order_id);
            if($stmt->execute()) {
                $success = "Order #$order_id status updated to " . ucfirst($new_status) . "! (Affected: " . $conn->affected_rows . ")";
            } else {
                $error = "Failed to update order status: " . $conn->error;
            }
            $stmt->close();
        } else {
            $error = "Prepare failed: " . $conn->error;
        }
    }
}

// Fetch orders by status
$statuses = ['pending', 'preparing', 'served'];
$orders = [];

foreach($statuses as $status) {
    $query = "SELECT o.orders_id, o.table_number, o.status, o.order_time, o.total_amount, u.username,
              GROUP_CONCAT(CONCAT(fi.food_name, ' (', oi.quantity, ')') SEPARATOR ', ') as items,
              SUM(oi.quantity) as total_items
              FROM orders o
              LEFT JOIN users u ON o.user_id = u.users_id
              LEFT JOIN orders_items oi ON o.orders_id = oi.order_id
              LEFT JOIN food_items fi ON oi.food_id = fi.food_items_id
              WHERE o.status = ?
              GROUP BY o.orders_id
              ORDER BY o.order_time ASC";
    
    $orders[$status] = [];
    if($stmt = $conn->prepare($query)) {
        $stmt->bind_param("s", $status);
        $stmt->execute();
        $result = $stmt->get_result();
        while($row = $result->fetch_assoc()) {
            $orders[$status][] = $row;
        }
        $stmt->close();
    } else {
        echo "<!-- Prepare failed for $status -->";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders Management - Mups Cafe Admin</title>
    <link rel="stylesheet" href="../assets/css/admin_dashboard.css">
    <style>
        .content-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .status-section { margin-bottom: 40px; }
        .status-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding: 15px; background: var(--sidebar-bg); color: white; border-radius: 8px; }
        .status-title { font-size: 24px; font-weight: bold; margin: 0; }
        .order-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 20px; }
        .order-card { background: white; border-radius: 12px; padding: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); transition: transform 0.2s, box-shadow 0.3s; border-left: 5px solid; }
        .order-card:hover { transform: translateY(-2px); box-shadow: 0 8px 15px rgba(0,0,0,0.2); }
        .pending { border-left-color: #ffc107; background: linear-gradient(135deg, #fff3cd 0%, #ffffff 100%); }
        .preparing { border-left-color: #007bff; background: linear-gradient(135deg, #cce5ff 0%, #ffffff 100%); }
        .served { border-left-color: #28a745; background: linear-gradient(135deg, #d4edda 0%, #ffffff 100%); }
        .order-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px; }
        .order-info h4 { margin: 0 0 5px 0; font-size: 18px; color: #333; }
        .order-meta { font-size: 14px; color: #666; }
        .order-time { display: block; margin-top: 5px; }
        .order-details { margin-bottom: 15px; }
        .order-items { font-size: 14px; color: #555; line-height: 1.4; margin-bottom: 10px; }
        .order-total { font-weight: bold; font-size: 16px; color: #28a745; }
        .order-actions { display: flex; gap: 10px; justify-content: flex-end; }
        .btn { padding: 8px 16px; border: none; border-radius: 6px; cursor: pointer; text-decoration: none; display: inline-block; font-size: 14px; font-weight: 500; transition: background 0.2s; }
        .btn-process { background: #007bff; color: white; }
        .btn-process:hover { background: #0056b3; }
        .btn-complete { background: #28a745; color: white; }
        .btn-complete:hover { background: #1e7e34; }
        .btn-cancel { background: #dc3545; color: white; }
        .btn-cancel:hover { background: #bd2130; }
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 8px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .no-orders { text-align: center; padding: 40px; color: #666; font-size: 18px; }
    </style>
</head>
<body>
    <!-- Admin Sidebar -->
    <div class="admin-sidebar">
        <div class="sidebar-header">
            <div class="logo"> Mups Cafe</div>
            <div class="admin-badge">Admin Panel</div>
        </div>
        <nav class="sidebar-nav">
            <a href="admin_dashboard.php" class="nav-item">
                <span>Dashboard</span>
            </a>
            <a href="orders.php" class="nav-item active">
                <span>Orders</span>
            </a>
            <a href="menu_items.php" class="nav-item">
                <span>Menu Items</span>
            </a>
            <a href="order_history.php" class="nav-item">
                <span>Order History</span>
            </a>
            <a href="../auth/logout.php" class="nav-item logout">
                <span>Logout</span>
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="top-bar">
            <h1>Orders Management</h1>
            <div class="admin-profile">
                <span>Welcome, <strong><?php echo htmlspecialchars($admin_name); ?></strong></span>
            </div>
        </div>

        <div class="content-wrapper">
            <?php if($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            <?php if($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php foreach($statuses as $status): ?>
                <div class="status-section">
                    <div class="status-header">
                        <h2 class="status-title"><?php echo ucfirst($status); ?> Orders (<?php echo count($orders[$status]); ?>)</h2>
                    </div>
                    <!-- <?php echo ucfirst($status); ?> count: <?php echo count($orders[$status]); ?> -->
                    
                    <?php if(count($orders[$status]) > 0): ?>
                        <div class="order-grid">
                        <?php foreach($orders[$status] as $order): ?>
                            <div class="order-card <?php echo $status; ?>">
                                <div class="order-header">
                                    <div class="order-info">
                                        <h4>Order #<?php echo $order['orders_id']; ?></h4>
                                        <div class="order-meta">
                                            <span>Table <?php echo htmlspecialchars($order['table_number']); ?></span>
                                            <span class="order-time"><?php echo date('M d, Y - h:i A', strtotime($order['order_time'])); ?></span>
                                        </div>
                                        <div>Customer: <?php echo htmlspecialchars($order['username'] ?? 'N/A'); ?></div>
                                    </div>
                                </div>
                                <div class="order-details">
                                    <div class="order-items"><strong>Items:</strong> <?php echo htmlspecialchars($order['items'] ?: 'No items'); ?></div>
                                    <div class="order-total"><strong>Total:</strong> $<?php echo number_format($order['total_amount'], 2); ?> (<?php echo $order['total_items'] ?: 0; ?> items)</div>
                                </div>
                                <div class="order-actions">
                                    <?php if($status === 'pending'): ?>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="action" value="process">
                                            <input type="hidden" name="order_id" value="<?php echo $order['orders_id']; ?>">
                                            <button type="submit" class="btn btn-process">Process Order</button>
                                        </form>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="action" value="cancel">
                                            <input type="hidden" name="order_id" value="<?php echo $order['orders_id']; ?>">
                                            <button type="submit" class="btn btn-cancel" onclick="return confirm('Are you sure you want to cancel this order?')">Cancel Order</button>
                                        </form>
                                    <?php elseif($status === 'preparing'): ?>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="action" value="complete">
                                            <input type="hidden" name="order_id" value="<?php echo $order['orders_id']; ?>">
                                            <button type="submit" class="btn btn-complete">Complete Order</button>
                                        </form>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="action" value="cancel">
                                            <input type="hidden" name="order_id" value="<?php echo $order['orders_id']; ?>">
                                            <button type="submit" class="btn btn-cancel" onclick="return confirm('Are you sure you want to cancel this order?')">Cancel Order</button>
                                        </form>
                                    <?php elseif($status === 'served'): ?>
                                        <span class="btn" style="background: #6c757d; color: white; cursor: default;">Order Served</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="no-orders">
                            <span style="font-size: 48px;">📋</span>
                            <h3>No <?php echo $status; ?> orders</h3>
                            <p>Orders will appear here when customers place them.</p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>
