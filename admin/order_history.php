<?php
session_start();
require_once '../config/dbconfig.php';

// Check if user is logged in and is admin
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$admin_name = $_SESSION['username'];

// Get filter status
$filter_status = isset($_GET['status']) ? $_GET['status'] : 'all';

// Build query
$query = "SELECT o.orders_id, o.table_number, o.status, o.order_time, o.total_amount, u.username,
          GROUP_CONCAT(CONCAT(fi.food_name, ' (', oi.quantity, ')') SEPARATOR ', ') as items,
          SUM(oi.quantity) as total_items
          FROM orders o
          LEFT JOIN users u ON o.user_id = u.users_id
          LEFT JOIN orders_items oi ON o.orders_id = oi.order_id
          LEFT JOIN food_items fi ON oi.food_id = fi.food_items_id";

if($filter_status !== 'all') {
    $query .= " WHERE o.status = ?";
}

$query .= " GROUP BY o.orders_id ORDER BY o.order_time DESC";

$orders = null;
if($filter_status !== 'all') {
    if($stmt = $conn->prepare($query)) {
        $stmt->bind_param("s", $filter_status);
        $stmt->execute();
        $orders = $stmt->get_result();
    }
} else {
    $orders = $conn->query($query);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order History - Mups Cafe Admin</title>
    <link rel="stylesheet" href="../assets/css/order_history.css">
    <style>
        .content-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .filter-tabs { display: flex; gap: 10px; margin-bottom: 20px; }
        .filter-tab { padding: 8px 16px; text-decoration: none; border-radius: 4px; background: #f0f0f0; color: #333; }
        .filter-tab.active { background: var(--medium); color: white; }
        .table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .table th, .table td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        .table th { background: var(--sidebar-bg); color: white; }
        .status-badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-preparing { background: #cce5ff; color: #004085; }
        .status-served { background: #d4edda; color: #155724; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        .order-items { max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
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
            <a href="orders.php" class="nav-item">
                <span>Orders</span>
            </a>
            <a href="menu_items.php" class="nav-item">
                <span>Menu Items</span>
            </a>
            <a href="order_history.php" class="nav-item active">
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
            <div class="top-bar-left">
                <button class="sidebar-toggle" id="sidebarToggle">
                    <span class="hamburger-line"></span>
                    <span class="hamburger-line"></span>
                    <span class="hamburger-line"></span>
                </button>
                <h1>Order History</h1>
            </div>
            <div class="admin-profile">
                <span>Welcome, <strong><?php echo htmlspecialchars($admin_name); ?></strong></span>
            </div>
        </div>

        <div class="content-wrapper">
            <div class="content-header">
                <h2>All Orders</h2>
            </div>

            <!-- Filter Tabs -->
            <div class="filter-tabs">
                <a href="order_history.php?status=all" class="filter-tab <?php echo $filter_status === 'all' ? 'active' : ''; ?>">
                    All Orders
                </a>
                <a href="order_history.php?status=pending" class="filter-tab <?php echo $filter_status === 'pending' ? 'active' : ''; ?>">
                    ⏳ Pending
                </a>
                <a href="order_history.php?status=preparing" class="filter-tab <?php echo $filter_status === 'preparing' ? 'active' : ''; ?>">
                    👨‍🍳 Preparing
                </a>
                <a href="order_history.php?status=served" class="filter-tab <?php echo $filter_status === 'served' ? 'active' : ''; ?>">
                    ✅ Served
                </a>
                <a href="order_history.php?status=cancelled" class="filter-tab <?php echo $filter_status === 'cancelled' ? 'active' : ''; ?>">
                    ❌ Cancelled
                </a>
            </div>

            <table class="table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Table</th>
                            <th>Items</th>
                            <th>Total Items</th>
                            <th>Total Amount</th>
                            <th>Status</th>
                            <th>Order Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($orders && $orders->num_rows > 0): ?>
                            <?php while($order = $orders->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $order['orders_id']; ?></td>
                                    <td><?php echo htmlspecialchars($order['username'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($order['table_number']); ?></td>
                                    <td class="order-items" title="<?php echo htmlspecialchars($order['items']); ?>"><?php echo htmlspecialchars($order['items']); ?></td>
                                    <td><?php echo $order['total_items']; ?></td>
                                    <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $order['status']; ?>">
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y - h:i A', strtotime($order['order_time'])); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8">No orders found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
        </div>
    </div>

    <script>
        // Sidebar toggle functionality
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.querySelector('.admin-sidebar');
        const backdrop = document.createElement('div');
        backdrop.className = 'sidebar-backdrop';
        document.body.appendChild(backdrop);

        function toggleSidebar() {
            sidebar.classList.toggle('sidebar-open');
            backdrop.classList.toggle('show');
            sidebarToggle.classList.toggle('active');
        }

        sidebarToggle.addEventListener('click', toggleSidebar);

        // Close sidebar when clicking backdrop
        backdrop.addEventListener('click', toggleSidebar);

        // Close sidebar when clicking outside
        document.addEventListener('click', function(event) {
            if (window.innerWidth <= 1024 && !sidebar.contains(event.target) && !sidebarToggle.contains(event.target)) {
                sidebar.classList.remove('sidebar-open');
                backdrop.classList.remove('show');
                sidebarToggle.classList.remove('active');
            }
        });
    </script>
</body>
</html>
