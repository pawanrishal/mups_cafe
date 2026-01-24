<?php
session_start();
require_once '../config/dbconfig.php';

// Check if user is logged in and is admin
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../customers/login.php");
    exit();
}

$admin_name = $_SESSION['username'];

// Get statistics
$stats = [
    'total_orders' => 0,
    'pending_orders' => 0,
    'preparing_orders' => 0,
    'served_orders' => 0,
    'total_customers' => 0,
    'total_menu_items' => 0,
    'today_orders' => 0,
    'total_revenue' => 0
];

// Total orders
$result = $conn->query("SELECT COUNT(*) as total FROM orders");
if($result) $stats['total_orders'] = $result->fetch_assoc()['total'];

// Pending orders
$result = $conn->query("SELECT COUNT(*) as total FROM orders WHERE status = 'pending'");
if($result) $stats['pending_orders'] = $result->fetch_assoc()['total'];

// Preparing orders
$result = $conn->query("SELECT COUNT(*) as total FROM orders WHERE status = 'preparing'");
if($result) $stats['preparing_orders'] = $result->fetch_assoc()['total'];

// Served orders
$result = $conn->query("SELECT COUNT(*) as total FROM orders WHERE status = 'served'");
if($result) $stats['served_orders'] = $result->fetch_assoc()['total'];

// Total customers
$result = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'customer'");
if($result) $stats['total_customers'] = $result->fetch_assoc()['total'];

// Total menu items
$result = $conn->query("SELECT COUNT(*) as total FROM food_items");
if($result) $stats['total_menu_items'] = $result->fetch_assoc()['total'];

// Today's orders
$result = $conn->query("SELECT COUNT(*) as total FROM orders WHERE DATE(order_time) = CURDATE()");
if($result) $stats['today_orders'] = $result->fetch_assoc()['total'];

// Total revenue
$result = $conn->query("SELECT SUM(total_amount) as total FROM orders WHERE status = 'served'");
if($result) {
    $row = $result->fetch_assoc();
    $stats['total_revenue'] = $row['total'] ?? 0;
}

// Get recent orders
$recent_orders_query = "SELECT o.orders_id, o.table_number, o.status, o.order_time, o.total_amount, u.username
                        FROM orders o
                        LEFT JOIN users u ON o.user_id = u.users_id
                        ORDER BY o.order_time DESC
                        LIMIT 10";
$recent_orders = $conn->query($recent_orders_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Mups Cafe</title>
    <link rel="stylesheet" href="../assets/css/admin_dashboard.css">
</head>
<body>
    <!-- Admin Sidebar -->
    <div class="admin-sidebar">
        <div class="sidebar-header">
            <div class="logo">Mups Cafe</div>
            <div class="admin-badge">Admin Panel</div>
        </div>

        <nav class="sidebar-nav">
            <a href="admin_dashboard.php" class="nav-item active">
                <span>Dashboard</span>
            </a>
            <a href="orders.php" class="nav-item">
                <span>Orders</span>
                <?php if($stats['pending_orders'] > 0): ?>
                    <span class="badge"><?php echo $stats['pending_orders']; ?></span>
                <?php endif; ?>
            </a>
            <a href="menu_items.php" class="nav-item">
                <!-- <span class="nav-icon">🍽️</span> -->
                <span>Menu Items</span>
            </a>
            <a href="order_history.php" class="nav-item">
                <!-- <span class="nav-icon">👥</span> -->
                <span>Order History</span>
            </a>
            <a href="../auth/logout.php" class="nav-item logout">
                <!-- <span class="nav-icon">🚪</span> -->
                <span>Logout</span>
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Bar -->
        <div class="top-bar">
            <div class="top-bar-left">
                <button class="sidebar-toggle" id="sidebarToggle">
                    <span class="hamburger-line"></span>
                    <span class="hamburger-line"></span>
                    <span class="hamburger-line"></span>
                </button>
                <h1>Dashboard Overview</h1>
            </div>
            <div class="admin-profile">
                <span>Welcome, <strong><?php echo htmlspecialchars($admin_name); ?></strong></span>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon blue">📦</div>
                <div class="stat-info">
                    <h3><?php echo $stats['total_orders']; ?></h3>
                    <p>Total Orders</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon yellow">⏳</div>
                <div class="stat-info">
                    <h3><?php echo $stats['pending_orders']; ?></h3>
                    <p>Pending Orders</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon purple">👨‍🍳</div>
                <div class="stat-info">
                    <h3><?php echo $stats['preparing_orders']; ?></h3>
                    <p>Preparing</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon green">✅</div>
                <div class="stat-info">
                    <h3><?php echo $stats['served_orders']; ?></h3>
                    <p>Served</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon orange">👥</div>
                <div class="stat-info">
                    <h3><?php echo $stats['total_customers']; ?></h3>
                    <p>Total Customers</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon red">🍽️</div>
                <div class="stat-info">
                    <h3><?php echo $stats['total_menu_items']; ?></h3>
                    <p>Menu Items</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon teal">📅</div>
                <div class="stat-info">
                    <h3><?php echo $stats['today_orders']; ?></h3>
                    <p>Today's Orders</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon gold">💰</div>
                <div class="stat-info">
                    <h3>$<?php echo number_format($stats['total_revenue'], 2); ?></h3>
                    <p>Total Revenue</p>
                </div>
            </div>
        </div>

        <!-- Recent Orders -->
        <div class="recent-orders-section">
            <div class="section-header">
                <h2>Recent Orders</h2>
                <a href="orders.php" class="view-all-btn">View All →</a>
            </div>

            <div class="orders-table-container">
                <?php if($recent_orders && $recent_orders->num_rows > 0): ?>
                    <table class="orders-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Table</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Time</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($order = $recent_orders->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?php echo $order['orders_id']; ?></td>
                                    <td><?php echo htmlspecialchars($order['username'] ?? 'Guest'); ?></td>
                                    <td>Table <?php echo $order['table_number']; ?></td>
                                    <td>$<?php echo number_format($order['total_amount'] ?? 0, 2); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $order['status']; ?>">
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, h:i A', strtotime($order['order_time'])); ?></td>
                                    <td>
                                        <a href="orders.php?view=<?php echo $order['orders_id']; ?>" class="action-btn">View</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-data">
                        <p>No orders yet</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <h2>Quick Actions</h2>
            <div class="actions-grid">
                <a href="orders.php?status=pending" class="action-card">
                    <span class="action-icon">⏳</span>
                    <h3>View Pending Orders</h3>
                    <p><?php echo $stats['pending_orders']; ?> pending</p>
                </a>

                <a href="food_items.php?action=add" class="action-card">
                    <span class="action-icon">➕</span>
                    <h3>Add Menu Item</h3>
                    <p>Add new dish</p>
                </a>

                <a href="customers.php" class="action-card">
                    <span class="action-icon">👥</span>
                    <h3>View Customers</h3>
                    <p><?php echo $stats['total_customers']; ?> customers</p>
                </a>
            </div>
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