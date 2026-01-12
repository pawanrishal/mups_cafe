<?php
session_start();
require_once '../config/dbconfig.php';

// Check if user is logged in and is a customer
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle update quantity
if(isset($_POST['update_quantity'])) {
    $cart_id = (int)$_POST['cart_id'];
    $quantity = (int)$_POST['quantity'];
    
    if($quantity > 0 && $quantity <= 10) {
        $update_query = "UPDATE cart SET quantity = ? WHERE id = ? AND customer_id = ?";
        if($stmt = $conn->prepare($update_query)) {
            $stmt->bind_param("iii", $quantity, $cart_id, $user_id);
            $stmt->execute();
            $_SESSION['success'] = "Quantity updated!";
        }
    }
    header("Location: cart.php");
    exit();
}

// Handle remove item
if(isset($_POST['remove_item'])) {
    $cart_id = (int)$_POST['cart_id'];
    
    $delete_query = "DELETE FROM cart WHERE id = ? AND customer_id = ?";
    if($stmt = $conn->prepare($delete_query)) {
        $stmt->bind_param("ii", $cart_id, $user_id);
        $stmt->execute();
        $_SESSION['success'] = "Item removed from cart!";
    }
    header("Location: cart.php");
    exit();
}

// Handle clear cart
if(isset($_POST['clear_cart'])) {
    $clear_query = "DELETE FROM cart WHERE customer_id = ?";
    if($stmt = $conn->prepare($clear_query)) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $_SESSION['success'] = "Cart cleared!";
    }
    header("Location: cart.php");
    exit();
}

// Get cart items
$cart_query = "SELECT c.id as cart_id, c.quantity, fi.food_items_id, fi.food_name, fi.description, fi.price, fi.image, cat.name as category_name
               FROM cart c
               JOIN food_items fi ON c.food_item_id = fi.food_items_id
               LEFT JOIN categories cat ON fi.category_id = cat.categories_id
               WHERE c.customer_id = ?
               ORDER BY fi.food_name";

$cart_items = null;
$total_amount = 0;
$total_items = 0;

if($stmt = $conn->prepare($cart_query)) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $cart_items = $stmt->get_result();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - Mups Cafe</title>
    <link rel="stylesheet" href="../assets/css/cart.css">
</head>
<body>
    <?php include '../Partials/nav.php'; ?>

    <div class="cart-container">
        <div class="cart-header">
            <h1>🛒 Shopping Cart</h1>
            <a href="menu.php" class="continue-shopping" onclick="sessionStorage.setItem('activeLink','menu')">← Continue Shopping</a>
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

        <?php if($cart_items && $cart_items->num_rows > 0): ?>
            <div class="cart-content">
                <!-- Cart Items -->
                <div class="cart-items">
                    <?php while($item = $cart_items->fetch_assoc()): 
                        $subtotal = $item['price'] * $item['quantity'];
                        $total_amount += $subtotal;
                        $total_items += $item['quantity'];
                    ?>
                        <div class="cart-item">
                            <div class="item-image">
                                <?php
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

                            <div class="item-details">
                                <h3><?php echo htmlspecialchars($item['food_name']); ?></h3>
                                <p class="item-category"><?php echo ucfirst($item['category_name']); ?></p>
                                <p class="item-price">$<?php echo number_format($item['price'], 2); ?> each</p>
                            </div>

                            <div class="item-actions">
                                <form method="POST" class="quantity-form">
                                    <input type="hidden" name="cart_id" value="<?php echo $item['cart_id']; ?>">
                                    <div class="quantity-controls">
                                        <button type="button" class="qty-btn" onclick="decreaseQty(this)">-</button>
                                        <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" min="1" max="10" class="qty-input" readonly>
                                        <button type="button" class="qty-btn" onclick="increaseQty(this)">+</button>
                                    </div>
                                    <button type="submit" name="update_quantity" class="update-btn">Update</button>
                                </form>

                                <div class="item-subtotal">
                                    <span class="subtotal-label">Subtotal:</span>
                                    <span class="subtotal-amount">$<?php echo number_format($subtotal, 2); ?></span>
                                </div>

                                <form method="POST" class="remove-form">
                                    <input type="hidden" name="cart_id" value="<?php echo $item['cart_id']; ?>">
                                    <button type="submit" name="remove_item" class="remove-btn" onclick="return confirm('Remove this item?')">
                                        🗑️ Remove
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endwhile; ?>

                    <div class="cart-footer">
                        <form method="POST" style="display: inline;">
                            <button type="submit" name="clear_cart" class="clear-cart-btn" onclick="return confirm('Clear entire cart?')">
                                Clear Cart
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Order Summary -->
                <div class="order-summary">
                    <h2>Order Summary</h2>
                    
                    <div class="summary-row">
                        <span>Items (<?php echo $total_items; ?>):</span>
                        <span>$<?php echo number_format($total_amount, 2); ?></span>
                    </div>

                    <div class="summary-row">
                        <span>Tax (5%):</span>
                        <span>$<?php echo number_format($total_amount * 0.05, 2); ?></span>
                    </div>

                    <div class="summary-divider"></div>

                    <div class="summary-row total">
                        <span>Total:</span>
                        <span>$<?php echo number_format($total_amount * 1.05, 2); ?></span>
                    </div>

                    <form action="checkout.php" method="POST">
                        <button type="submit" class="checkout-btn">Proceed to Checkout</button>
                    </form>

                    <div class="summary-info">
                        <p>✓ Free table service</p>
                        <p>✓ Fresh ingredients daily</p>
                        <p>✓ Quick preparation</p>
                    </div>
                </div>
            </div>
        <?php else: ?>
                <div class="empty-cart">
                <span class="empty-icon">🛒</span>
                <h2>Your cart is empty</h2>
                <p>Start adding delicious items from our menu!</p>
                <a href="menu.php" class="browse-menu-btn" onclick="sessionStorage.setItem('activeLink','menu')">Browse Menu</a>
            </div>
        <?php endif; ?>
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
    </script>
</body>
</html>