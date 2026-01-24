<?php
session_start();
require_once '../config/dbconfig.php';

// Check if user is logged in as admin
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$success = '';
$error = '';

// Fetch all food items for dropdown
$food_items = [];
$query = "SELECT fi.food_items_id, fi.food_name, c.name as category_name, fi.image FROM food_items fi LEFT JOIN categories c ON fi.category_id = c.categories_id ORDER BY c.name, fi.food_name";
$result = $conn->query($query);
if($result) {
    while($row = $result->fetch_assoc()) {
        $food_items[] = $row;
    }
}

// Handle image upload
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['food_image'])) {
    $food_item_id = (int)$_POST['food_item_id'];
    $file = $_FILES['food_image'];
    
    if($file['error'] === UPLOAD_ERR_OK) {
        // Validate file type
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if(!in_array($file['type'], $allowed_types)) {
            $error = "Invalid file type. Only JPG, PNG, GIF, and WebP allowed.";
        } elseif($file['size'] > 5 * 1024 * 1024) { // 5MB limit
            $error = "File size exceeds 5MB limit.";
        } else {
            // Create unique filename
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'food_' . $food_item_id . '_' . time() . '.' . $ext;
            $upload_path = '../uploads/image/' . $filename;
            
            // Create uploads/image directory if it doesn't exist
            if(!is_dir('../uploads/image')) {
                mkdir('../uploads/image', 0755, true);
            }
            
            if(move_uploaded_file($file['tmp_name'], $upload_path)) {
                // Update database
                $update_query = "UPDATE food_items SET image = ? WHERE food_items_id = ?";
                if($stmt = $conn->prepare($update_query)) {
                    $stmt->bind_param("si", $filename, $food_item_id);
                    if($stmt->execute()) {
                        $success = "Image uploaded and linked successfully!";
                    } else {
                        $error = "Failed to update database.";
                        unlink($upload_path); // Delete uploaded file if DB update fails
                    }
                    $stmt->close();
                } else {
                    $error = "Database error: " . $conn->error;
                    unlink($upload_path);
                }
            } else {
                $error = "Failed to upload file. Check folder permissions.";
            }
        }
    } else {
        $error = "File upload error: " . $file['error'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Food Image - Admin</title>
    <link rel="stylesheet" href="../assets/css/upload_food_image.css">
    <style>
        .upload-container {
            max-width: 600px;
            margin: 40px auto;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .upload-container h1 {
            color: #333;
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }

        .form-group select,
        .form-group input[type="file"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            box-sizing: border-box;
        }

        .form-group select:focus,
        .form-group input[type="file"]:focus {
            outline: none;
            border-color: #8B4513;
            box-shadow: 0 0 5px rgba(139, 69, 19, 0.3);
        }

        .current-image {
            margin-top: 10px;
            padding: 10px;
            background: #f9f9f9;
            border-radius: 4px;
            font-size: 13px;
            color: #666;
        }

        .current-image img {
            max-width: 100px;
            max-height: 100px;
            margin-right: 10px;
            vertical-align: middle;
            border-radius: 4px;
        }

        .btn-submit {
            background-color: #8B4513;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            width: 100%;
            transition: background-color 0.3s;
        }

        .btn-submit:hover {
            background-color: #6b3410;
        }

        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
        }

        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }

        .food-list {
            margin-top: 40px;
            padding-top: 30px;
            border-top: 2px solid #eee;
        }

        .food-list h2 {
            color: #333;
            margin-bottom: 20px;
        }

        .food-item-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px;
            border-bottom: 1px solid #f0f0f0;
        }

        .food-item-info {
            flex: 1;
        }

        .food-item-name {
            font-weight: 600;
            color: #333;
        }

        .food-item-category {
            font-size: 13px;
            color: #999;
        }

        .food-item-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 4px;
            margin-left: 15px;
        }

        .no-image {
            width: 80px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f5f5f5;
            border-radius: 4px;
            margin-left: 15px;
            color: #999;
            font-size: 30px;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #8B4513;
            text-decoration: none;
            font-weight: 500;
        }

        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <?php include '../Partials/nav.php'; ?>

    <div class="upload-container">
        <a href="admin_dashboard.php" class="back-link">← Back to Dashboard</a>

        <h1>📸 Upload Food Image</h1>

        <?php if($success): ?>
            <div class="success-message"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if($error): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="food_item_id">Select Food Item *</label>
                <select id="food_item_id" name="food_item_id" required onchange="updateCurrentImage()">
                    <option value="">-- Choose a food item --</option>
                    <?php foreach($food_items as $item): ?>
                        <option value="<?php echo $item['food_items_id']; ?>" 
                                data-image="<?php echo htmlspecialchars($item['image'] ?? ''); ?>"
                                data-category="<?php echo htmlspecialchars($item['category_name']); ?>">
                            <?php echo htmlspecialchars($item['food_name']); ?> (<?php echo htmlspecialchars($item['category_name']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="current-image" id="current-image-info">
                    No item selected
                </div>
            </div>

            <div class="form-group">
                <label for="food_image">Choose Image File *</label>
                <input type="file" id="food_image" name="food_image" accept="image/*" required>
                <small style="color: #666; display: block; margin-top: 5px;">
                    Supported formats: JPG, PNG, GIF, WebP (Max 5MB)
                </small>
            </div>

            <button type="submit" class="btn-submit">Upload Image</button>
        </form>

        <!-- Display all food items with their current images -->
        <div class="food-list">
            <h2>All Food Items</h2>
            <?php if(!empty($food_items)): ?>
                <?php foreach($food_items as $item): ?>
                    <div class="food-item-row">
                        <div class="food-item-info">
                            <div class="food-item-name"><?php echo htmlspecialchars($item['food_name']); ?></div>
                            <div class="food-item-category"><?php echo htmlspecialchars($item['category_name']); ?></div>
                        </div>
                        <?php if(!empty($item['image']) && file_exists('../uploads/image/' . $item['image'])): ?>
                            <img src="../uploads/image/<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['food_name']); ?>" class="food-item-image">
                        <?php else: ?>
                            <div class="no-image">📷</div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No food items found in database.</p>
            <?php endif; ?>
        </div>
    </div>

    <?php include '../Partials/footer.php'; ?>

    <script>
        function updateCurrentImage() {
            const select = document.getElementById('food_item_id');
            const selected = select.options[select.selectedIndex];
            const infoDiv = document.getElementById('current-image-info');
            
            if(selected.value === '') {
                infoDiv.innerHTML = 'No item selected';
                return;
            }

            const image = selected.getAttribute('data-image');
            if(image) {
                infoDiv.innerHTML = '<strong>Current image:</strong> ' + image;
            } else {
                infoDiv.innerHTML = '<strong>No image</strong> - Upload one to add';
            }
        }
    </script>
</body>
</html>
