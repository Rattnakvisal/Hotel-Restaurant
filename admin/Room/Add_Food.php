<?php
require_once '../../config/connect.php';

// --- Initialization ---
$message = '';
$name = $description = $category = '';
$price = 0;
$image_url = '';
$edit_food = null;

// --- Delete Food Item ---
if (isset($_GET['delete'])) {
    $menu_id = intval($_GET['delete']);
    $sql = "DELETE FROM restaurant_menu WHERE menu_id = :menu_id";
    $stmt = oci_parse($connection, $sql);
    oci_bind_by_name($stmt, ':menu_id', $menu_id);
    oci_execute($stmt);
    header("Location: Add_Food.php?msg=deleted");
    exit;
}

// --- Edit Mode: Fetch Food Item ---
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $sql = "SELECT * FROM restaurant_menu WHERE menu_id = :menu_id";
    $stmt = oci_parse($connection, $sql);
    oci_bind_by_name($stmt, ':menu_id', $edit_id);
    oci_execute($stmt);
    $row = oci_fetch_assoc($stmt);
    if ($row) {
        $edit_food = $row;
        $edit_food['DESCRIPTION'] = is_object($row['DESCRIPTION']) && $row['DESCRIPTION'] instanceof OCILob ? $row['DESCRIPTION']->load() : $row['DESCRIPTION'];
    }
}

// --- Update Food Item ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_food'])) {
    $edit_id = intval($_POST['menu_id']);
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $category = trim($_POST['category'] ?? '');
    $image_url = $_POST['old_image_url'] ?? '';

    // Handle image upload if provided
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (in_array($_FILES['image']['type'], $allowed_types)) {
            $upload_dir = '/Hotel-Restaurant/assets/uploads/food/';
            $target_dir = $_SERVER['DOCUMENT_ROOT'] . $upload_dir;
            if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
            $filename = uniqid() . '_' . basename($_FILES['image']['name']);
            $target_file = $target_dir . $filename;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                $image_url = $upload_dir . $filename;
            } else {
                $message = "<span class='error'>Image upload failed.</span>";
            }
        } else {
            $message = "<span class='error'>Invalid image type. Only JPG, PNG, GIF, WEBP allowed.</span>";
        }
    }

    if (!$message) {
        $sql = "UPDATE restaurant_menu SET name = :name, description = :description, price = :price, category = :category, image_url = :image_url WHERE menu_id = :menu_id";
        $stmt = oci_parse($connection, $sql);
        oci_bind_by_name($stmt, ':name', $name);
        oci_bind_by_name($stmt, ':description', $description);
        oci_bind_by_name($stmt, ':price', $price);
        oci_bind_by_name($stmt, ':category', $category);
        oci_bind_by_name($stmt, ':image_url', $image_url);
        oci_bind_by_name($stmt, ':menu_id', $edit_id);
        if (oci_execute($stmt)) {
            $message = "<span class='success'>Food item updated successfully!</span>";
            header("Location: Add_Food.php?msg=updated");
            exit;
        } else {
            $e = oci_error($stmt);
            $message = "<span class='error'>Error updating food item: " . htmlspecialchars($e['message']) . "</span>";
        }
    }
}

// --- Add New Food Item ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_food'])) {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $category = trim($_POST['category'] ?? '');
    $image_url = '';

    // Handle image upload if provided
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (in_array($_FILES['image']['type'], $allowed_types)) {
            $upload_dir = '/Hotel-Restaurant/assets/uploads/food/';
            $target_dir = $_SERVER['DOCUMENT_ROOT'] . $upload_dir;
            if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
            $filename = uniqid() . '_' . basename($_FILES['image']['name']);
            $target_file = $target_dir . $filename;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                $image_url = $upload_dir . $filename;
            } else {
                $message = "<span class='error'>Image upload failed.</span>";
            }
        } else {
            $message = "<span class='error'>Invalid image type. Only JPG, PNG, GIF, WEBP allowed.</span>";
        }
    }

    // Insert into restaurant_menu
    if (!$message) {
        $sql = "INSERT INTO restaurant_menu (menu_id, name, description, price, category, image_url)
                VALUES (restaurant_menu_seq.NEXTVAL, :name, :description, :price, :category, :image_url)";
        $stmt = oci_parse($connection, $sql);
        oci_bind_by_name($stmt, ':name', $name);
        oci_bind_by_name($stmt, ':description', $description);
        oci_bind_by_name($stmt, ':price', $price);
        oci_bind_by_name($stmt, ':category', $category);
        oci_bind_by_name($stmt, ':image_url', $image_url);

        if (oci_execute($stmt)) {
            header("Location: ../Menu/manage_menu.php");
            exit;
        } else {
            $e = oci_error($stmt);
            $message = "<span class='error'>Error adding food item: " . htmlspecialchars($e['message']) . "</span>";
        }
    }
}

// --- Fetch All Food Items for Table ---
$foods = [];
$sql = "SELECT * FROM restaurant_menu ORDER BY menu_id DESC";
$stmt = oci_parse($connection, $sql);
oci_execute($stmt);
while ($row = oci_fetch_assoc($stmt)) {
    if (isset($row['DESCRIPTION']) && is_object($row['DESCRIPTION']) && $row['DESCRIPTION'] instanceof OCILob) {
        $row['DESCRIPTION'] = $row['DESCRIPTION']->load();
    }
    $foods[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RoyalNest - Food Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --royal-purple: #7851a9;
            --royal-gold: #d4af37;
            --light-purple: #e6e1f7;
            --dark-purple: #5d3a87;
            --light-gray: #f8f9fa;
            --dark: #222;
            --success: #28a745;
            --danger: #dc3545;
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #f9f7fe 0%, #f0edf8 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            width: 100%;
            margin: 0 auto;
        }

        .header {
            background: linear-gradient(135deg, var(--royal-purple), var(--dark-purple));
            color: white;
            padding: 25px 30px;
            border-radius: 15px 15px 0 0;
            text-align: center;
            position: relative;
            margin-bottom: 30px;
        }

        .header::after {
            content: "";
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 100%;
            height: 4px;
            background: var(--royal-gold);
        }

        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }

        .header h1 i {
            color: var(--royal-gold);
        }

        .header p {
            color: rgba(255, 255, 255, 0.85);
            font-size: 1.1rem;
            max-width: 800px;
            margin: 0 auto;
        }

        .content-wrapper {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 30px;
        }

        /* Form Styles */
        .form-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            height: fit-content;
            position: sticky;
            top: 20px;
        }

        .form-header {
            background: var(--light-purple);
            padding: 20px;
            border-bottom: 1px solid #e0daf0;
        }

        .form-header h2 {
            font-size: 1.8rem;
            color: var(--royal-purple);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .form-body {
            padding: 25px;
        }

        .message {
            text-align: center;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 10px;
            font-weight: 500;
        }

        .success {
            background: rgba(40, 167, 69, 0.15);
            color: var(--success);
            border: 1px solid rgba(40, 167, 69, 0.3);
        }

        .error {
            background: rgba(220, 53, 69, 0.15);
            color: var(--danger);
            border: 1px solid rgba(220, 53, 69, 0.3);
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark-purple);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        label i {
            color: var(--royal-gold);
            width: 20px;
            text-align: center;
        }

        input[type="text"],
        input[type="number"],
        textarea,
        select {
            width: 100%;
            padding: 14px 18px;
            border: 2px solid #eaeaea;
            border-radius: 10px;
            font-size: 1rem;
            transition: var(--transition);
            background: var(--light-gray);
        }

        input[type="text"]:focus,
        input[type="number"]:focus,
        textarea:focus,
        select:focus {
            outline: none;
            border-color: var(--royal-purple);
            box-shadow: 0 0 0 4px rgba(120, 81, 169, 0.15);
            background: white;
        }

        textarea {
            min-height: 120px;
            resize: vertical;
        }

        .file-upload {
            position: relative;
            display: block;
            cursor: pointer;
            height: 100%;
        }

        .file-upload input[type="file"] {
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }

        .file-custom {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 15px;
            padding: 20px;
            border: 2px dashed #ddd;
            border-radius: 10px;
            background: var(--light-gray);
            transition: var(--transition);
            height: 100%;
            text-align: center;
        }

        .file-custom i {
            font-size: 3rem;
            color: var(--royal-purple);
        }

        .file-text h4 {
            color: var(--dark);
            margin-bottom: 5px;
            font-size: 1.1rem;
        }

        .file-text p {
            color: var(--royal-purple);
            font-size: 0.9rem;
        }

        .file-upload:hover .file-custom {
            border-color: var(--royal-purple);
            background: rgba(120, 81, 169, 0.05);
        }

        button[type="submit"] {
            background: linear-gradient(135deg, var(--royal-purple), var(--dark-purple));
            color: white;
            border: none;
            padding: 16px;
            width: 100%;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-top: 10px;
            box-shadow: 0 5px 20px rgba(120, 81, 169, 0.3);
        }

        button[type="submit"]:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(120, 81, 169, 0.4);
        }

        .cancel-btn {
            background: #f8f9fa;
            color: var(--dark-purple);
            border: none;
            padding: 16px;
            width: 100%;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-top: 10px;
            box-shadow: 0 5px 20px rgba(120, 81, 169, 0.08);
            text-decoration: none;
        }

        .cancel-btn:hover {
            background: #e6e1f7;
            color: var(--royal-purple);
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(120, 81, 169, 0.12);
            text-decoration: none;
        }

        .preview-container {
            margin-top: 15px;
            text-align: center;
            display: none;
        }

        .preview-container img {
            max-width: 100%;
            max-height: 200px;
            border-radius: 8px;
            border: 2px solid var(--light-purple);
            margin-top: 10px;
            object-fit: cover;
        }

        /* Table Styles */
        .table-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }

        .table-header {
            padding: 20px;
            background: var(--light-purple);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .table-header h2 {
            font-size: 1.8rem;
            color: var(--royal-purple);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .search-box {
            display: flex;
            align-items: center;
            background: white;
            border-radius: 50px;
            padding: 8px 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .search-box input {
            border: none;
            padding: 8px;
            min-width: 200px;
            outline: none;
        }

        .search-box i {
            color: var(--royal-purple);
        }

        .food-table {
            width: 100%;
            border-collapse: collapse;
        }

        .food-table th {
            background: linear-gradient(135deg, var(--royal-purple), var(--dark-purple));
            color: white;
            text-align: left;
            padding: 15px 20px;
            font-weight: 600;
            font-size: 1.05rem;
        }

        .food-table td {
            padding: 12px 20px;
            border-bottom: 1px solid #eee;
            font-size: 0.95rem;
        }

        .food-table tr:nth-child(even) {
            background-color: #faf9ff;
        }

        .food-table tr:hover {
            background-color: #f5f3ff;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--primary);
            text-decoration: none;
            margin-bottom: 20px;
            font-weight: 500;
            transition: var(--transition);
        }

        .back-link:hover {
            color: var(--secondary);
            transform: translateX(-3px);
        }


        .food-table img {
            width: 70px;
            height: 50px;
            border-radius: 6px;
            object-fit: cover;
            border: 1px solid #eee;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
        }

        .edit-btn,
        .delete-btn {
            padding: 8px 12px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 0.9rem;
        }

        .edit-btn {
            background: rgba(52, 152, 219, 0.1);
            color: #3498db;
        }

        .edit-btn:hover {
            background: rgba(52, 152, 219, 0.2);
            transform: translateY(-2px);
        }

        .delete-btn {
            background: rgba(231, 76, 60, 0.1);
            color: #e74c3c;
        }

        .delete-btn:hover {
            background: rgba(231, 76, 60, 0.2);
            transform: translateY(-2px);
        }

        .empty-table {
            text-align: center;
            padding: 40px;
            color: var(--royal-purple);
        }

        .empty-table i {
            font-size: 3.5rem;
            margin-bottom: 20px;
            color: var(--light-purple);
        }

        /* Responsive Design */
        @media (max-width: 992px) {
            .content-wrapper {
                grid-template-columns: 1fr;
            }

            .form-container {
                position: relative;
                top: 0;
            }
        }

        @media (max-width: 768px) {
            .header h1 {
                font-size: 2rem;
            }

            .header p {
                font-size: 1rem;
            }

            .table-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .search-box {
                width: 100%;
            }

            .food-table {
                display: block;
                overflow-x: auto;
            }

            .action-buttons {
                flex-direction: column;
                gap: 8px;
            }

            .edit-btn,
            .delete-btn {
                justify-content: center;
            }
        }

        @media (max-width: 576px) {
            .header {
                padding: 20px;
            }

            .header h1 {
                font-size: 1.8rem;
            }

            .form-header h2,
            .table-header h2 {
                font-size: 1.5rem;
            }

            input[type="text"],
            input[type="number"],
            textarea,
            select {
                padding: 12px 15px;
            }

            .file-custom {
                padding: 15px;
            }

            .file-custom i {
                font-size: 2.5rem;
            }

            button[type="submit"],
            .cancel-btn {
                padding: 14px;
                font-size: 1rem;
            }
        }

        /* Animation */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .form-container,
        .table-container {
            animation: fadeIn 0.5s ease-out;
        }
    </style>
</head>

<body>
    <div class="container">
        <a href="../Menu/manage_menu.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Menu Management
        </a>
        <div class="header">
            <h1><i class="fas fa-utensils"></i> RoyalNest Food Management</h1>
            <p>Manage your restaurant menu with our intuitive interface</p>
        </div>

        <div class="content-wrapper">
            <!-- Form Container -->
            <div class="form-container">
                <div class="form-header">
                    <h2><i class="fas fa-edit"></i> <?php echo $edit_food ? 'Edit Food Item' : 'Add New Food Item'; ?></h2>
                </div>

                <form method="post" enctype="multipart/form-data" id="food-form" class="form-body">
                    <?php if ($message): ?>
                        <div class="message <?php echo strpos($message, 'successfully') !== false ? 'success' : 'error'; ?>">
                            <?php echo $message; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($edit_food): ?>
                        <input type="hidden" name="menu_id" value="<?php echo htmlspecialchars($edit_food['MENU_ID']); ?>">
                        <input type="hidden" name="old_image_url" value="<?php echo htmlspecialchars($edit_food['IMAGE_URL']); ?>">
                    <?php endif; ?>

                    <div class="form-group">
                        <label for="name"><i class="fas fa-tag"></i> Food Name</label>
                        <input type="text" name="name" id="name" required
                            placeholder="Enter food item name"
                            maxlength="100" value="<?php echo htmlspecialchars($edit_food['NAME'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="price"><i class="fas fa-dollar-sign"></i> Price ($)</label>
                        <input type="number" name="price" id="price" step="0.01" min="0" required
                            placeholder="Enter price" value="<?php echo htmlspecialchars($edit_food['PRICE'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="category"><i class="fas fa-list"></i> Category</label>
                        <select name="category" id="category">
                            <option value="">Select Category</option>
                            <option value="Appetizers" <?php echo ($edit_food['CATEGORY'] ?? '') === 'Appetizers' ? 'selected' : ''; ?>>Appetizers</option>
                            <option value="Main Course" <?php echo ($edit_food['CATEGORY'] ?? '') === 'Main Course' ? 'selected' : ''; ?>>Main Course</option>
                            <option value="Desserts" <?php echo ($edit_food['CATEGORY'] ?? '') === 'Desserts' ? 'selected' : ''; ?>>Desserts</option>
                            <option value="Beverages" <?php echo ($edit_food['CATEGORY'] ?? '') === 'Beverages' ? 'selected' : ''; ?>>Beverages</option>
                            <option value="Chef's Specials" <?php echo ($edit_food['CATEGORY'] ?? '') === "Chef's Specials" ? 'selected' : ''; ?>>Chef's Specials</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="image"><i class="fas fa-image"></i> Food Image</label>
                        <div class="file-upload">
                            <div class="file-custom">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <div class="file-text">
                                    <h4>Upload Food Image</h4>
                                    <p>Click to select or drag & drop</p>
                                </div>
                            </div>
                            <input type="file" name="image" id="image" accept="image/*">
                        </div>
                        <div class="preview-container" id="preview-container" style="margin-top:10px;<?php echo empty($edit_food['IMAGE_URL']) ? 'display:none;' : ''; ?>">
                            <?php if (!empty($edit_food['IMAGE_URL'])): ?>
                                <p>Image Preview:</p>
                                <img id="image-preview" src="<?php echo htmlspecialchars($edit_food['IMAGE_URL']); ?>" alt="Preview" style="max-width:120px;max-height:120px;" />
                            <?php else: ?>
                                <img id="image-preview" src="#" alt="Preview" style="max-width:120px;max-height:120px;display:none;" />
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="description"><i class="fas fa-align-left"></i> Description</label>
                        <textarea name="description" id="description" rows="4"
                            placeholder="Describe the food item in detail"
                            maxlength="1000"><?php echo htmlspecialchars($edit_food['DESCRIPTION'] ?? ''); ?></textarea>
                    </div>

                    <div>
                        <?php if ($edit_food): ?>
                            <button type="submit" name="update_food" class="btn">
                                <i class="fas fa-save"></i> Update Food Item
                            </button>
                            <a href="Add_Food.php" class="cancel-btn">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        <?php else: ?>
                            <button type="submit" name="add_food" class="btn">
                                <i class="fas fa-plus-circle"></i> Add Food Item
                            </button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Table Container -->
            <div class="table-container">
                <div class="table-header">
                    <h2><i class="fas fa-list"></i> Current Food Menu</h2>
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" placeholder="Search food items...">
                    </div>
                </div>

                <div style="overflow-x: auto;">
                    <table class="food-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Image</th>
                                <th>Price</th>
                                <th>Category</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($foods)): ?>
                                <tr>
                                    <td colspan="6" class="empty-table">
                                        <i class="fas fa-utensils"></i>
                                        <p>No food items found</p>
                                        <p>Add new items using the form</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($foods as $food): ?>
                                    <tr>
                                        <td><?php echo $food['MENU_ID']; ?></td>
                                        <td><?php echo htmlspecialchars($food['NAME']); ?></td>
                                        <td>
                                            <?php if (!empty($food['IMAGE_URL'])): ?>
                                                <img src="<?php echo htmlspecialchars($food['IMAGE_URL']); ?>" alt="Food Image">
                                            <?php else: ?>
                                                <span style="color:#aaa;">No image</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>$<?php echo number_format($food['PRICE'], 2); ?></td>
                                        <td><?php echo htmlspecialchars($food['CATEGORY']); ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="?edit=<?php echo $food['MENU_ID']; ?>" class="edit-btn">
                                                    <i class="fas fa-edit"></i> Edit
                                                </a>
                                                <a href="?delete=<?php echo $food['MENU_ID']; ?>" class="delete-btn" onclick="return confirm('Delete this food item?');">
                                                    <i class="fas fa-trash-alt"></i> Delete
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Image preview functionality
        document.getElementById('image').addEventListener('change', function(e) {
            const previewContainer = document.getElementById('preview-container');
            const previewImage = document.getElementById('image-preview');

            if (this.files && this.files[0]) {
                const reader = new FileReader();

                reader.onload = function(e) {
                    previewImage.src = e.target.result;
                    previewImage.style.display = 'block';
                    previewContainer.style.display = 'block';
                }

                reader.readAsDataURL(this.files[0]);
            } else {
                previewContainer.style.display = 'none';
            }
        });

        // Form validation
        document.getElementById('food-form').addEventListener('submit', function(e) {
            const price = document.getElementById('price').value;
            if (parseFloat(price) <= 0) {
                e.preventDefault();
                alert('Price must be greater than zero');
                return false;
            }

            const name = document.getElementById('name').value.trim();
            if (name === '') {
                e.preventDefault();
                alert('Food name is required');
                return false;
            }

            return true;
        });

        // Search functionality
        const searchInput = document.querySelector('.search-box input');
        searchInput.addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('.food-table tbody tr');

            rows.forEach(row => {
                const name = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
                const category = row.querySelector('td:nth-child(5)').textContent.toLowerCase();

                if (name.includes(searchTerm) || category.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    </script>
</body>

</html>