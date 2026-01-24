<?php
session_start();
require_once '../config/dbconfig.php';

// Check if user is logged in and is a customer
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: slogin.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get cart items
$cart_query = "SELECT c.id as cart_id, c.quantity, fi.food_items_id, fi.food_name, fi.price
               FROM cart c
               JOIN food_items fi ON c.food_item_id = fi.food_items_id
               WHERE c.customer_id = ?";

$cart_items = [];
$total_amount = 0;

if($stmt = $conn->prepare($cart_query)) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while($item = $result->fetch_assoc()) {
        $cart_items[] = $item;
        $total_amount += $item['price'] * $item['quantity'];
    }
}

// If cart is empty, redirect to menu
if(empty($cart_items)) {
    $_SESSION['error'] = "Your cart is empty!";
    header("Location: menu.php");
    exit();
}

// Handle order placement
if(isset($_POST['place_order'])) {
    $table_number = (int)$_POST['table_number'];
    $special_instructions = trim($_POST['special_instructions'] ?? '');
    
    // Validate table number
    if($table_number < 1 || $table_number > 20) {
        $_SESSION['error'] = "Please select a valid table number (1-20)";
    } else {
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Get actual column names from orders table
            $columns_result = $conn->query("SHOW COLUMNS FROM orders");
            if(!$columns_result) {
                throw new Exception("Orders table doesn't exist: " . $conn->error);
            }
            
            $columns = [];
            while($col = $columns_result->fetch_assoc()) {
                $columns[] = $col['Field'];
            }
            
            // Try to find the table number column
            $table_col = null;
            $possible_names = ['table_number', 'tabel_number', 'table_no', 'tableNumber'];
            foreach($possible_names as $name) {
                if(in_array($name, $columns)) {
                    $table_col = $name;
                    break;
                }
            }
            
            if(!$table_col) {
                throw new Exception("Could not find table number column. Available columns: " . implode(', ', $columns));
            }
            
            // Find user_id column
            $user_col = in_array('user_id', $columns) ? 'user_id' : (in_array('customer_id', $columns) ? 'customer_id' : null);
            
            // Calculate total with tax
            $tax_amount = $total_amount * 0.05;
            $final_total = $total_amount + $tax_amount;
            
            // Build insert query based on available columns
            if($user_col && in_array('total_amount', $columns) && in_array('special_instructions', $columns)) {
                $order_query = "INSERT INTO orders ($user_col, $table_col, total_amount, status, special_instructions) VALUES (?, ?, ?, 'pending', ?)";
                $order_stmt = $conn->prepare($order_query);
                if(!$order_stmt) throw new Exception("Failed to prepare order: " . $conn->error);
                $order_stmt->bind_param("iids", $user_id, $table_number, $final_total, $special_instructions);
            } else if($user_col) {
                $order_query = "INSERT INTO orders ($user_col, $table_col, status) VALUES (?, ?, 'pending')";
                $order_stmt = $conn->prepare($order_query);
                if(!$order_stmt) throw new Exception("Failed to prepare order: " . $conn->error);
                $order_stmt->bind_param("ii", $user_id, $table_number);
            } else {
                $order_query = "INSERT INTO orders ($table_col, status) VALUES (?, 'pending')";
                $order_stmt = $conn->prepare($order_query);
                if(!$order_stmt) throw new Exception("Failed to prepare order: " . $conn->error);
                $order_stmt->bind_param("i", $table_number);
            }
            
            if(!$order_stmt->execute()) {
                throw new Exception("Failed to insert order: " . $order_stmt->error);
            }
            
            $order_id = $conn->insert_id;
            
            // Get order_items column names
            $item_columns_result = $conn->query("SHOW COLUMNS FROM orders_items");
            if(!$item_columns_result) {
                throw new Exception("orders_items table doesn't exist: " . $conn->error);
            }
            
            $item_columns = [];
            while($col = $item_columns_result->fetch_assoc()) {
                $item_columns[] = $col['Field'];
            }
            
            // Find food_id column
            $food_col = null;
            $possible_food_names = ['food_id', 'food_item_id', 'foodId'];
            foreach($possible_food_names as $name) {
                if(in_array($name, $item_columns)) {
                    $food_col = $name;
                    break;
                }
            }
            
            if(!$food_col) {
                throw new Exception("Could not find food column. Available columns: " . implode(', ', $item_columns));
            }
            
            // Insert order items with available columns
            if(in_array('price', $item_columns) && in_array('subtotal', $item_columns)) {
                $item_query = "INSERT INTO orders_items (order_id, $food_col, quantity, price, subtotal) VALUES (?, ?, ?, ?, ?)";
                $item_stmt = $conn->prepare($item_query);
                if(!$item_stmt) throw new Exception("Failed to prepare items: " . $conn->error);
                
                foreach($cart_items as $item) {
                    $subtotal = $item['price'] * $item['quantity'];
                    $item_stmt->bind_param("iiidd", $order_id, $item['food_items_id'], $item['quantity'], $item['price'], $subtotal);
                    if(!$item_stmt->execute()) throw new Exception("Failed to insert item: " . $item_stmt->error);
                }
            } else {
                $item_query = "INSERT INTO orders_items (order_id, $food_col, quantity) VALUES (?, ?, ?)";
                $item_stmt = $conn->prepare($item_query);
                if(!$item_stmt) throw new Exception("Failed to prepare items: " . $conn->error);
                
                foreach($cart_items as $item) {
                    $item_stmt->bind_param("iii", $order_id, $item['food_items_id'], $item['quantity']);
                    if(!$item_stmt->execute()) throw new Exception("Failed to insert item: " . $item_stmt->error);
                }
            }
            
            // Clear cart
            $clear_cart = "DELETE FROM cart WHERE customer_id = ?";
            $clear_stmt = $conn->prepare($clear_cart);
            if(!$clear_stmt) throw new Exception("Failed to prepare clear cart: " . $conn->error);
            $clear_stmt->bind_param("i", $user_id);
            if(!$clear_stmt->execute()) throw new Exception("Failed to clear cart: " . $clear_stmt->error);
            
            // Commit transaction
            $conn->commit();
            
            // Redirect to success page
            $_SESSION['success'] = "Order placed successfully! Order #" . $order_id . " | Table: " . $table_number;
            header("Location: customer_dashboard.php");
            exit();
            
        } catch (Exception $e) {
            // Rollback on error
            $conn->rollback();
            $_SESSION['error'] = "Failed to place order: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Mups Cafe</title>
    <link rel="stylesheet" href="../assets/css/checkout.css">
    <style>
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background-color: #fff;
            margin: 10% auto;
            padding: 0;
            border-radius: 15px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            animation: modalSlideIn 0.3s ease-out;
        }

        @keyframes modalSlideIn {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 30px;
            border-radius: 15px 15px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h2 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }

        .modal-close {
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .modal-close:hover {
            transform: scale(1.1);
        }

        .modal-body {
            padding: 30px;
            text-align: center;
        }

        .order-confirmation .confirmation-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }

        .order-confirmation h3 {
            color: #333;
            margin-bottom: 20px;
            font-size: 20px;
        }

        .order-details {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
            text-align: left;
        }

        .order-details p {
            margin: 8px 0;
            color: #555;
        }

        .confirmation-note {
            color: #666;
            font-size: 14px;
            font-style: italic;
            margin-top: 15px;
        }

        .modal-footer {
            padding: 20px 30px 30px;
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            border-top: 1px solid #eee;
        }

        .btn-cancel, .btn-confirm {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-cancel {
            background: #6c757d;
            color: white;
        }

        .btn-cancel:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }

        .btn-confirm {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
        }

        .btn-confirm:hover {
            background: linear-gradient(135deg, #218838 0%, #1aa085 100%);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.4);
        }

        .btn-confirm:active {
            transform: translateY(0);
        }
    </style>
</head>
<body>
    <?php include '../Partials/nav.php'; ?>

    <div class="checkout-container">
        <div class="checkout-header">
            <h1>🍽️ Checkout</h1>
            <a href="cart.php" class="back-to-cart">← Back to Cart</a>
        </div>

        <!-- Error Message -->
        <?php if(isset($_SESSION['error'])): ?>
            <div class="error-message show">
                <?php 
                    echo $_SESSION['error']; 
                    unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

        <div class="checkout-content">
            <!-- Order Form -->
            <div class="checkout-form">
                <form method="POST" id="checkoutForm">
                    <div class="form-section">
                        <h2>📍 Table Selection</h2>
                        <p class="section-description">Please select your table number</p>
                        
                        <div class="table-grid">
                            <?php for($i = 1; $i <= 20; $i++): ?>
                                <label class="table-option">
                                    <input type="radio" name="table_number" value="<?php echo $i; ?>" required>
                                    <span class="table-number">Table <?php echo $i; ?></span>
                                </label>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <div class="form-section">
                        <h2>📝 Special Instructions</h2>
                        <p class="section-description">Any special requests? (Optional)</p>
                        
                        <textarea 
                            name="special_instructions" 
                            rows="4" 
                            placeholder="Example: No onions, extra spicy, allergies, etc."
                            maxlength="500"
                        ></textarea>
                        <span class="char-count">0 / 500 characters</span>
                    </div>

                    <button type="submit" name="place_order" class="place-order-btn" >
                        Place Order - $<?php echo number_format($total_amount * 1.05, 2); ?>
                    </button>
                </form>
            </div>

            <!-- Order Summary -->
            <div class="order-summary">
                <h2>Order Summary</h2>
                
                <div class="summary-items">
                    <?php foreach($cart_items as $item): ?>
                        <div class="summary-item">
                            <div class="item-info">
                                <span class="item-name"><?php echo htmlspecialchars($item['food_name']); ?></span>
                                <span class="item-qty">x<?php echo $item['quantity']; ?></span>
                            </div>
                            <span class="item-price">$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="summary-divider"></div>

                <div class="summary-row">
                    <span>Subtotal:</span>
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

                <div class="summary-info">
                    <p>✓ Order will be prepared fresh</p>
                    <p>✓ Estimated time: 15-20 mins</p>
                    <p>✓ Served at your table</p>
                </div>
            </div>
        </div>
    </div>

    <?php include '../Partials/footer.php'; ?>

    <script>
        // Character counter for special instructions
        const textarea = document.querySelector('textarea[name="special_instructions"]');
        const charCount = document.querySelector('.char-count');
        
        if(textarea && charCount) {
            textarea.addEventListener('input', function() {
                const length = this.value.length;
                charCount.textContent = length + ' / 500 characters';
            });
        }

        // Table selection visual feedback
        const tableOptions = document.querySelectorAll('.table-option input');
        tableOptions.forEach(option => {
            option.addEventListener('change', function() {
                document.querySelectorAll('.table-option').forEach(label => {
                    label.classList.remove('selected');
                });
                this.parentElement.classList.add('selected');
            });
        });

        // Form validation
        document.getElementById('checkoutForm').addEventListener('submit', function(e) {
            const tableSelected = document.querySelector('input[name="table_number"]:checked');
            
            if(!tableSelected) {
                e.preventDefault();
                alert('Please select a table number');
                return false;
            }
            
            return confirm('Confirm order placement?');
        });
    </script>

    <!-- Custom Confirmation Modal -->
    <div id="orderConfirmModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Confirm Your Order</h2>
                <span class="modal-close">&times;</span>
            </div>
            <div class="modal-body">
                <div class="order-confirmation">
                    <div class="confirmation-icon">🍽️</div>
                    <h3>Ready to place your order?</h3>
                    <div class="order-details">
                        <p><strong>Total Amount:</strong> $<?php echo number_format($total_amount * 1.05, 2); ?></p>
                        <p><strong>Items:</strong> <?php echo count($cart_items); ?> item(s)</p>
                        <p><strong>Table:</strong> <span id="selectedTable">Not selected</span></p>
                    </div>
                    <p class="confirmation-note">Once confirmed, your order will be sent to the kitchen and cannot be modified.</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" id="cancelOrder">Cancel</button>
                <button type="button" class="btn-confirm" id="confirmOrder">Place Order</button>
            </div>
        </div>
    </div>

</body>
</html>