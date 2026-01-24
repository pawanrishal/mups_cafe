<?php
session_start();
require_once '../config/dbconfig.php';

// Check if user is logged in and is admin
// if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
//     header("Location: ../customers/login.php");
//     exit();
// }

$admin_name = $_SESSION['username'];
$success = '';
$error = '';

// Handle form submissions
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    if(isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if($action === 'add' || $action === 'edit') {
            $food_name = trim($_POST['food_name']);
            $description = trim($_POST['description']);
            $price = (float)$_POST['price'];
            $category_id = (int)$_POST['category_id'];
            $image_path = '';
            
            // Handle image upload
            if(isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['image'];
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if(in_array($file['type'], $allowed_types) && $file['size'] <= 5 * 1024 * 1024) {
                    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $filename = 'food_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
                    $upload_path = '../uploads/image/' . $filename;
                    
                    if(!is_dir('../uploads/image')) {
                        mkdir('../uploads/image', 0755, true);
                    }
                    
                    if(move_uploaded_file($file['tmp_name'], $upload_path)) {
                        $image_path = $filename;
                    } else {
                        $error = "Failed to upload image.";
                    }
                } else {
                    $error = "Invalid image file.";
                }
            }
            
            if(empty($error)) {
                if($action === 'add') {
                    $query = "INSERT INTO food_items (food_name, description, price, category_id, image) VALUES (?, ?, ?, ?, ?)";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("ssdss", $food_name, $description, $price, $category_id, $image_path);
                } else {
                    $id = (int)$_POST['id'];
                    if($image_path) {
                        $query = "UPDATE food_items SET food_name=?, description=?, price=?, category_id=?, image=? WHERE food_items_id=?";
                        $stmt = $conn->prepare($query);
                        $stmt->bind_param("ssdssi", $food_name, $description, $price, $category_id, $image_path, $id);
                    } else {
                        $query = "UPDATE food_items SET food_name=?, description=?, price=?, category_id=? WHERE food_items_id=?";
                        $stmt = $conn->prepare($query);
                        $stmt->bind_param("ssdsi", $food_name, $description, $price, $category_id, $id);
                    }
                }
                
                if($stmt->execute()) {
                    $success = $action === 'add' ? "Item added successfully!" : "Item updated successfully!";
                } else {
                    $error = "Database error: " . $conn->error;
                }
                $stmt->close();
            }
        } elseif($action === 'delete') {
            $id = (int)$_POST['id'];
            $query = "DELETE FROM food_items WHERE food_items_id=?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $id);
            if($stmt->execute()) {
                $success = "Item deleted successfully!";
            } else {
                $error = "Failed to delete item.";
            }
            $stmt->close();
        }
    }
}

// Get all food items
$query = "SELECT fi.food_items_id, fi.food_name, fi.description, fi.price, fi.image, c.name as category_name 
          FROM food_items fi 
          LEFT JOIN categories c ON fi.category_id = c.categories_id 
          ORDER BY c.name, fi.food_name";
$food_items = $conn->query($query);

// Get categories for dropdown
$categories = $conn->query("SELECT categories_id, name FROM categories ORDER BY name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu Items - Mups Cafe Admin</title>
    <link rel="stylesheet" href="../assets/css/menu_items.css">
    <style>
        .content-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .btn { padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn-primary { background: var(--medium); color: white; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-secondary { background: #6c757d; color: white; }
        .table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .table th, .table td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        .table th { background: var(--sidebar-bg); color: white; }
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); }
        .modal-content { background: white; margin: 5% auto; padding: 20px; border-radius: 8px; width: 90%; max-width: 500px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; }
        .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        .alert { padding: 10px; margin-bottom: 20px; border-radius: 4px; }
        .alert-success { background: #d4edda; color: #155724; }
        .alert-error { background: #f8d7da; color: #721c24; }
        .item-image { width: 50px; height: 50px; object-fit: cover; border-radius: 4px; }
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
            <a href="menu_items.php" class="nav-item active">
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
            <div class="top-bar-left">
                <button class="sidebar-toggle" id="sidebarToggle">
                    <span class="hamburger-line"></span>
                    <span class="hamburger-line"></span>
                    <span class="hamburger-line"></span>
                </button>
                <h1>Menu Items Management</h1>
            </div>
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

            <div class="content-header">
                <h2>Food Items</h2>
                <a href="?action=add" class="btn btn-primary">Add New Item</a>
            </div>

            <?php if(isset($_GET['action']) && $_GET['action'] === 'add'): ?>
                <div class="form-section">
                    <h3>Add New Item</h3>
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="add">
                        <!-- form fields -->
                        <div class="form-group">
                            <label for="food_name">Name:</label>
                            <input type="text" id="food_name" name="food_name" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Description:</label>
                            <textarea id="description" name="description" rows="3"></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="price">Price:</label>
                            <input type="number" id="price" name="price" step="0.01" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="category_id">Category:</label>
                            <select id="category_id" name="category_id" required>
                                <option value="">Select Category</option>
                                <?php 
                                $categories->data_seek(0); // Reset pointer
                                while($cat = $categories->fetch_assoc()): ?>
                                    <option value="<?php echo $cat['categories_id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="image">Image:</label>
                            <input type="file" id="image" name="image" accept="image/*">
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Add Item</button>
                        <a href="menu_items.php" class="btn btn-secondary">Cancel</a>
                    </form>
                </div>
            <?php elseif(isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])): 
                $edit_id = (int)$_GET['id'];
                $edit_query = "SELECT * FROM food_items WHERE food_items_id=?";
                $stmt = $conn->prepare($edit_query);
                $stmt->bind_param("i", $edit_id);
                $stmt->execute();
                $edit_item = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                if($edit_item):
            ?>
                <div class="form-section">
                    <h3>Edit Item</h3>
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" value="<?php echo $edit_item['food_items_id']; ?>">
                        <!-- form fields -->
                        <div class="form-group">
                            <label for="food_name">Name:</label>
                            <input type="text" id="food_name" name="food_name" value="<?php echo htmlspecialchars($edit_item['food_name']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Description:</label>
                            <textarea id="description" name="description" rows="3"><?php echo htmlspecialchars($edit_item['description']); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="price">Price:</label>
                            <input type="number" id="price" name="price" step="0.01" value="<?php echo $edit_item['price']; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="category_id">Category:</label>
                            <select id="category_id" name="category_id" required>
                                <option value="">Select Category</option>
                                <?php 
                                $categories->data_seek(0); // Reset pointer
                                while($cat = $categories->fetch_assoc()): ?>
                                    <option value="<?php echo $cat['categories_id']; ?>" <?php echo $cat['categories_id'] == $edit_item['category_id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat['name']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="image">Image (leave empty to keep current):</label>
                            <input type="file" id="image" name="image" accept="image/*">
                            <?php if($edit_item['image']): ?>
                                <br><small>Current: <img src="../uploads/image/<?php echo htmlspecialchars($edit_item['image']); ?>" style="width:50px; height:50px; object-fit:cover;"></small>
                            <?php endif; ?>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Update Item</button>
                        <a href="menu_items.php" class="btn btn-secondary">Cancel</a>
                    </form>
                </div>
            <?php endif; ?>
            <?php endif; ?>

            <table class="table">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($item = $food_items->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <?php if($item['image']): ?>
                                    <img src="../uploads/image/<?php echo htmlspecialchars($item['image']); ?>" alt="Image" class="item-image">
                                <?php else: ?>
                                    No Image
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($item['food_name']); ?></td>
                            <td><?php echo htmlspecialchars(substr($item['description'], 0, 50)) . (strlen($item['description']) > 50 ? '...' : ''); ?></td>
                            <td><?php echo htmlspecialchars($item['category_name']); ?></td>
                            <td>$<?php echo number_format($item['price'], 2); ?></td>
                            <td>
                                <a href="?action=edit&id=<?php echo $item['food_items_id']; ?>" class="btn btn-secondary">Edit</a>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this item?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $item['food_items_id']; ?>">
                                    <button type="submit" class="btn btn-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
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
