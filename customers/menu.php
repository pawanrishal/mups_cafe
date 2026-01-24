<?php
session_start();
require_once '../config/dbconfig.php';

// Check if user is logged in and is a customer
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get cart count
$cart_count = 0;
$cart_count_query = "SELECT SUM(quantity) as total FROM cart WHERE customer_id = ?";
if($stmt = $conn->prepare($cart_count_query)) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if($row = $result->fetch_assoc()) {
        $cart_count = $row['total'] ?? 0;
    }
    $stmt->close();
}

// Get selected category (default: all)
$selected_category = isset($_GET['category']) ? $_GET['category'] : 'all';

// Get all food items with category names and images
$query = "SELECT fi.food_items_id, fi.category_id, fi.food_name, fi.description, fi.price, fi.image, c.name as category_name 
          FROM food_items fi 
          LEFT JOIN categories c ON fi.category_id = c.categories_id";

if($selected_category !== 'all') {
    $query .= " WHERE LOWER(c.name) = LOWER(?)";
}
$query .= " ORDER BY c.name, fi.food_name";

$food_items = null;
if($selected_category !== 'all') {
    if($stmt = $conn->prepare($query)) {
        $stmt->bind_param("s", $selected_category);
        $stmt->execute();
        $food_items = $stmt->get_result();
    }
} else {
    $food_items = $conn->query($query);
}

// Handle add to cart
if(isset($_POST['add_to_cart'])) {
    $food_item_id = (int)$_POST['food_item_id'];
    $quantity = (int)$_POST['quantity'];
    
    if($quantity > 0 && $quantity <= 10) {
        // Check if item already in cart
        $check_query = "SELECT id, quantity FROM cart WHERE customer_id = ? AND food_item_id = ?";
        if($check_stmt = $conn->prepare($check_query)) {
            $check_stmt->bind_param("ii", $user_id, $food_item_id);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            
            if($result->num_rows > 0) {
                // Update quantity
                $row = $result->fetch_assoc();
                $new_quantity = $row['quantity'] + $quantity;
                if($new_quantity <= 10) {
                    $update_query = "UPDATE cart SET quantity = ? WHERE id = ?";
                    if($update_stmt = $conn->prepare($update_query)) {
                        $update_stmt->bind_param("ii", $new_quantity, $row['id']);
                        if($update_stmt->execute()) {
                            $_SESSION['success'] = "Cart updated successfully!";
                        } else {
                            $_SESSION['error'] = "Failed to update cart: " . $conn->error;
                        }
                    }
                } else {
                    $_SESSION['error'] = "Maximum quantity is 10 items per product!";
                }
            } else {
                // Insert new item
                $insert_query = "INSERT INTO cart (customer_id, food_item_id, quantity) VALUES (?, ?, ?)";
                if($insert_stmt = $conn->prepare($insert_query)) {
                    $insert_stmt->bind_param("iii", $user_id, $food_item_id, $quantity);
                    if($insert_stmt->execute()) {
                        $_SESSION['success'] = "Item added to cart!";
                    } else {
                        $_SESSION['error'] = "Failed to add to cart: " . $conn->error;
                    }
                } else {
                    $_SESSION['error'] = "Database error: " . $conn->error;
                }
            }
        } else {
            $_SESSION['error'] = "Database error: " . $conn->error;
        }
    } else {
        $_SESSION['error'] = "Invalid quantity!";
    }
    
    header("Location: menu.php?category=" . $selected_category);
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu - Mups Cafe</title>
    <link rel="stylesheet" href="../assets/css/menu.css">
</head>
<body>
    <?php include '../Partials/nav.php'; ?>

    <div class="menu-container">
        <!-- Menu Header -->
        <div class="menu-header">
            <div class="header-content">
                <h1>Our Menu</h1>
                <p>Delicious food crafted with love and passion</p>
            </div>
            <a href="cart.php" class="cart-button">
                🛒 Cart 
                <?php if($cart_count > 0): ?>
                    <span class="cart-badge"><?php echo $cart_count; ?></span>
                <?php endif; ?>
            </a>
        </div>

        <!-- Success Message -->
        <?php if(isset($_SESSION['success'])): ?>
            <div class="success-message show">
                <?php 
                    echo $_SESSION['success']; 
                    unset($_SESSION['success']);
                ?>
            </div>
        <?php endif; ?>

        <!-- Error Message -->
        <?php if(isset($_SESSION['error'])): ?>
            <div class="error-message show">
                <?php 
                    echo $_SESSION['error']; 
                    unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

        <!-- Category Filter -->
        <div class="category-filter">
            <a href="menu.php?category=all" class="category-btn <?php echo $selected_category === 'all' ? 'active' : ''; ?>">
                All Items
            </a>
            <a href="menu.php?category=breakfast" class="category-btn <?php echo $selected_category === 'breakfast' ? 'active' : ''; ?>">
                Breakfast
            </a>
            <a href="menu.php?category=lunch" class="category-btn <?php echo $selected_category === 'lunch' ? 'active' : ''; ?>">
                Lunch
            </a>
            <a href="menu.php?category=dinner" class="category-btn <?php echo $selected_category === 'dinner' ? 'active' : ''; ?>">
                Dinner
            </a>
            <a href="menu.php?category=snacks" class="category-btn <?php echo $selected_category === 'snacks' ? 'active' : ''; ?>">
                Snacks
            </a>
            <a href="menu.php?category=beverage" class="category-btn <?php echo $selected_category === 'beverage' ? 'active' : ''; ?>">
                Beverages
            </a>
        </div>

        <!-- Menu Grid -->
        <div class="menu-grid">
            <?php if($food_items && $food_items->num_rows > 0): ?>
                <?php while($item = $food_items->fetch_assoc()): ?>
                    <div class="menu-item">
                        <div class="item-image">
                            <?php if(!empty($item['image']) && file_exists('../uploads/image/' . $item['image'])): ?>
                                <img style="height: 240px; width: 370px;" src="../uploads/image/<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['food_name']); ?>">
                            <?php else: ?>
                                <div class="placeholder-image">
                                    <?php
                                    // Display emoji based on category
                                    $emojis = [
                                        'breakfast' => '🍳',
                                        'lunch' => '🍔',
                                        'dinner' => '🍝',
                                        'snacks' => '🍟',
                                        'beverage' => '☕'
                                    ];
                                    echo $emojis[$item['category_name']] ?? '🍽️';
                                    ?>
                                </div>
                            <?php endif; ?>
                            <span class="category-badge"><?php echo ucfirst($item['category_name']); ?></span>
                        </div>
                        
                        <div class="item-content">
                            <h3><?php echo htmlspecialchars($item['food_name']); ?></h3>
                            <p><?php echo htmlspecialchars($item['description'] ?? 'Delicious dish'); ?></p>
                            
                            <div class="item-footer">
                                <div class="item-price">$<?php echo number_format($item['price'], 2); ?></div>
                                
                                <form method="POST" class="add-to-cart-form">
                                    <input type="hidden" name="food_item_id" value="<?php echo $item['food_items_id']; ?>">
                                    <div class="quantity-controls">
                                        <button type="button" class="qty-btn" onclick="decreaseQty(this)">-</button>
                                        <input type="number" name="quantity" value="1" min="1" max="10" class="qty-input" readonly>
                                        <button type="button" class="qty-btn" onclick="increaseQty(this)">+</button>
                                    </div>
                                    <button type="submit" name="add_to_cart" class="add-btn">Add</button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-items">
                    <span class="no-items-icon">😕</span>
                    <h3>No items found</h3>
                    <p>Try selecting a different category</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include '../Partials/footer.php'; ?>

    <script>
        function increaseQty(btn) {
            const input = btn.parentElement.querySelector('.qty-input');
            const currentValue = parseInt(input.value);
            if(currentValue < 10) {
                input.value = currentValue + 1;
            }
        }

        function decreaseQty(btn) {
            const input = btn.parentElement.querySelector('.qty-input');
            const currentValue = parseInt(input.value);
            if(currentValue > 1) {
                input.value = currentValue - 1;
            }
        }

        // Auto-hide success message
        const successMsg = document.querySelector('.success-message');
        if(successMsg) {
            setTimeout(() => {
                successMsg.style.opacity = '0';
                setTimeout(() => {
                    successMsg.remove();
                }, 300);
            }, 3000);
        }

        // Auto-hide error message
        const errorMsg = document.querySelector('.error-message');
        if(errorMsg) {
            setTimeout(() => {
                errorMsg.style.opacity = '0';
                setTimeout(() => {
                    errorMsg.remove();
                }, 300);
            }, 3000);
        }
    </script>
</body>
</html>