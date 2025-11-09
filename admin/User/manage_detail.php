<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/Hotel-Restaurant/config/connect.php';

// --- Delete Service ---
if (isset($_GET['delete'])) {
    $image_id = intval($_GET['delete']);
    $sql = "DELETE FROM viewsitem WHERE image_id = :image_id";
    $stmt = oci_parse($connection, $sql);
    oci_bind_by_name($stmt, ':image_id', $image_id);
    oci_execute($stmt);
    header("Location: manage_detail.php");
    exit;
}

// --- Update Service ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_service'])) {
    $image_id = intval($_POST['image_id']);
    $title = $_POST['title'];
    $description = $_POST['description'];
    $category = $_POST['category'];
    $image_url = $_POST['current_image_url'];

    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/Hotel-Restaurant/assets/uploads/services/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        $filename = uniqid() . '_' . basename($_FILES['image']['name']);
        $targetFile = $uploadDir . $filename;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
            $image_url = '/Hotel-Restaurant/assets/uploads/services/' . $filename;
        }
    }

    $sql = "UPDATE viewsitem SET image_url = :image_url, title = :title, description = :description, category = :category WHERE image_id = :image_id";
    $stmt = oci_parse($connection, $sql);
    oci_bind_by_name($stmt, ':image_url', $image_url);
    oci_bind_by_name($stmt, ':title', $title);
    oci_bind_by_name($stmt, ':description', $description);
    oci_bind_by_name($stmt, ':category', $category);
    oci_bind_by_name($stmt, ':image_id', $image_id);
    oci_execute($stmt);
    header("Location: manage_detail.php");
    exit;
}

// --- Add Service ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_service'])) {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $category = $_POST['category'];
    $image_url = null;

    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/Hotel-Restaurant/assets/uploads/services/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        $filename = uniqid() . '_' . basename($_FILES['image']['name']);
        $targetFile = $uploadDir . $filename;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
            $image_url = '/Hotel-Restaurant/assets/uploads/services/' . $filename;
        }
    }

    $sql = "INSERT INTO viewsitem (image_id, image_url, title, description, category) VALUES (viewsitem_seq.NEXTVAL, :image_url, :title, :description, :category)";
    $stmt = oci_parse($connection, $sql);
    oci_bind_by_name($stmt, ':image_url', $image_url);
    oci_bind_by_name($stmt, ':title', $title);
    oci_bind_by_name($stmt, ':description', $description);
    oci_bind_by_name($stmt, ':category', $category);
    oci_execute($stmt);
    header("Location: manage_detail.php");
    exit;
}

// --- Fetch All Services ---
$services = [];
$sql = "SELECT * FROM viewsitem ORDER BY image_id DESC";
$stmt = oci_parse($connection, $sql);
oci_execute($stmt);
while ($row = oci_fetch_assoc($stmt)) {
    $services[] = $row;
}

// --- Edit Mode: Fetch Service ---
$edit_service = null;
$edit_desc = '';
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    foreach ($services as $srv) {
        if ($srv['IMAGE_ID'] == $edit_id) {
            $edit_service = $srv;
            $edit_desc = $edit_service['DESCRIPTION'];
            if ($edit_desc instanceof OCILob) {
                $edit_desc = $edit_desc->load();
            }
            break;
        }
    }
    // If not found, fallback to add form
    if (!$edit_service) {
        echo "<script>window.location='manage_detail.php';</script>";
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Premium Services Management | RoyalNest</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" />
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #3f37c9;
            --accent: #4895ef;
            --royal-gold: #d4af37;
            --royal-purple: #7851a9;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
            --sidebar-width: 250px;
            --header-height: 70px;
            --transition: all 0.3s ease;
            --success: #2ecc71;
            --warning: #f39c12;
            --danger: #e74c3c;
            --info: #3498db;
        }

        body {
            background-color: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--dark);
        }

        .admin-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        .dashboard-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .dashboard-title {
            font-weight: 700;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .dashboard-subtitle {
            font-weight: 300;
            opacity: 0.9;
            font-size: 1.1rem;
            padding-left: 40px;
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

        .card {
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            border: none;
            transition: transform 0.3s, box-shadow 0.3s;
            overflow: hidden;
            background: white;
            margin-bottom: 25px;
        }

        .card:hover {
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            background: white;
            padding: 18px 25px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            font-weight: 700;
            color: var(--primary);
            font-size: 1.25rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-body {
            padding: 25px;
        }

        .form-section {
            padding: 20px;
            background: rgba(245, 247, 250, 0.5);
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .form-label {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 8px;
        }

        .form-control,
        .form-select {
            border-radius: 8px;
            padding: 10px 15px;
            border: 1px solid #e0e0e0;
            transition: var(--transition);
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.15);
        }

        .btn-primary {
            background: var(--primary);
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            transition: var(--transition);
        }

        .btn-primary:hover {
            background: var(--secondary);
            transform: translateY(-2px);
        }

        .btn-outline {
            background: transparent;
            border: 1px solid var(--primary);
            color: var(--primary);
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            transition: var(--transition);
        }

        .btn-outline:hover {
            background: rgba(67, 97, 238, 0.1);
        }

        .btn-icon {
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .table-container {
            overflow-x: auto;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            background: white;
        }

        .service-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .service-table th {
            background: var(--primary);
            color: white;
            padding: 15px 20px;
            text-align: left;
            font-weight: 600;
        }

        .service-table td {
            padding: 15px 20px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            vertical-align: middle;
        }

        .service-table tr:last-child td {
            border-bottom: none;
        }

        .service-table tr:hover {
            background: rgba(67, 97, 238, 0.03);
        }

        .service-image {
            width: 80px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
        }

        .action-buttons {
            display: flex;
            gap: 10px;
        }

        .btn-action {
            padding: 8px 12px;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition);
            border: none;
        }

        .btn-edit {
            background: rgba(52, 152, 219, 0.15);
            color: var(--info);
        }

        .btn-delete {
            background: rgba(231, 76, 60, 0.15);
            color: var(--danger);
        }

        .btn-action:hover {
            transform: translateY(-2px);
        }

        .badge-category {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-block;
        }

        .badge-room {
            background: rgba(67, 97, 238, 0.15);
            color: var(--primary);
        }

        .badge-food {
            background: rgba(46, 204, 113, 0.15);
            color: var(--success);
        }

        .badge-spa {
            background: rgba(155, 89, 182, 0.15);
            color: #9b59b6;
        }

        .badge-event {
            background: rgba(241, 196, 15, 0.15);
            color: var(--warning);
        }

        .badge-other {
            background: rgba(149, 165, 166, 0.15);
            color: #7f8c8d;
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: var(--gray);
        }

        .empty-state i {
            font-size: 3rem;
            opacity: 0.5;
            margin-bottom: 20px;
        }

        @media (max-width: 992px) {
            .admin-container {
                padding: 15px;
            }

            .card-body {
                padding: 20px;
            }

            .service-table th,
            .service-table td {
                padding: 12px 15px;
            }
        }

        @media (max-width: 768px) {
            .dashboard-header {
                padding: 15px;
            }

            .dashboard-title {
                font-size: 1.5rem;
            }

            .action-buttons {
                flex-direction: column;
                gap: 8px;
            }

            .service-table {
                min-width: 700px;
            }

            .form-section {
                padding: 15px;
            }
        }

        @media (max-width: 576px) {
            .admin-container {
                padding: 10px;
            }

            .card-header {
                padding: 15px 20px;
                font-size: 1.1rem;
            }

            .card-body {
                padding: 15px;
            }

            .btn-primary,
            .btn-outline {
                width: 100%;
                margin-top: 10px;
            }

            .form-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>

<body>
    <div class="admin-container">
        <a href="../Room/Products.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Room Management
        </a>

        <div class="dashboard-header">
            <h1 class="dashboard-title">
                <i class="fas fa-crown"></i> Premium Services Management
            </h1>
            <p class="dashboard-subtitle">Manage your premium services to enhance guest experience</p>
        </div>

        <div class="row">
            <div class="col-lg-5">
                <!-- Service Form Card -->
                <div class="card">
                    <div class="card-header">
                        <i class="fas <?= $edit_service ? 'fa-edit' : 'fa-plus-circle' ?>"></i>
                        <?= $edit_service ? 'Edit Service' : 'Add New Service' ?>
                    </div>
                    <div class="card-body">
                        <!-- Add Service Form -->
                        <form method="post" enctype="multipart/form-data" id="add-form" <?php if ($edit_service) echo 'style="display:none;"'; ?>>
                            <div class="mb-3">
                                <label class="form-label">Service Title</label>
                                <input type="text" name="title" class="form-control" placeholder="Enter service title" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Category</label>
                                <select name="category" class="form-select" required>
                                    <option value="">Select Category</option>
                                    <option value="Room">Room</option>
                                    <option value="Food">Food</option>
                                    <option value="Spa">Spa</option>
                                    <option value="Event">Event</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea name="description" rows="4" class="form-control" placeholder="Enter service description" required></textarea>
                            </div>

                            <div class="mb-4">
                                <label class="form-label">Service Image</label>
                                <input type="file" name="image" class="form-control" accept="image/*" required>
                                <small class="text-muted">Recommended size: 800x600px</small>
                            </div>

                            <div class="d-grid">
                                <button type="submit" name="add_service" class="btn btn-primary btn-icon">
                                    <i class="fas fa-plus"></i> Add Service
                                </button>
                            </div>
                        </form>

                        <!-- Edit Service Form -->
                        <form method="post" enctype="multipart/form-data" id="edit-form" <?php if (!$edit_service) echo 'style="display:none;"'; ?>>
                            <input type="hidden" name="image_id" id="edit-image-id" value="<?= $edit_service ? htmlspecialchars($edit_service['IMAGE_ID']) : '' ?>">
                            <input type="hidden" name="current_image_url" id="edit-current-image-url" value="<?= $edit_service ? htmlspecialchars($edit_service['IMAGE_URL']) : '' ?>">

                            <div class="mb-3">
                                <label class="form-label">Service Title</label>
                                <input type="text" name="title" id="edit-title" class="form-control" value="<?= $edit_service ? htmlspecialchars($edit_service['TITLE']) : '' ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Category</label>
                                <select name="category" id="edit-category" class="form-select" required>
                                    <option value="">Select Category</option>
                                    <option value="Room" <?= ($edit_service && $edit_service['CATEGORY'] == 'Room') ? 'selected' : '' ?>>Room</option>
                                    <option value="Food" <?= ($edit_service && $edit_service['CATEGORY'] == 'Food') ? 'selected' : '' ?>>Food</option>
                                    <option value="Drink" <?= ($edit_service && $edit_service['CATEGORY'] == 'Drink') ? 'selected' : '' ?>>Drink</option>
                                    <option value="Event" <?= ($edit_service && $edit_service['CATEGORY'] == 'Event') ? 'selected' : '' ?>>Event</option>
                                    <option value="Other" <?= ($edit_service && $edit_service['CATEGORY'] == 'Other') ? 'selected' : '' ?>>Other</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea name="description" id="edit-description" rows="4" class="form-control" required><?= $edit_service ? htmlspecialchars($edit_desc) : '' ?></textarea>
                            </div>

                            <div class="mb-4">
                                <label class="form-label">Service Image</label>
                                <input type="file" name="image" class="form-control" accept="image/*">
                                <small class="text-muted">Leave blank to keep current image</small>
                                <?php if ($edit_service && $edit_service['IMAGE_URL']): ?>
                                    <div class="mt-2">
                                        <img src="<?= htmlspecialchars($edit_service['IMAGE_URL']) ?>" alt="Current Image" class="service-image">
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="d-flex gap-2 form-buttons">
                                <button type="submit" name="update_service" class="btn btn-primary btn-icon flex-grow-1">
                                    <i class="fas fa-save"></i> Update Service
                                </button>
                                <a href="manage_detail.php" class="btn btn-outline">
                                    Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <!-- Services List Card -->
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-list"></i> Premium Services List
                    </div>
                    <div class="card-body p-0">
                        <div class="table-container">
                            <?php if (count($services) > 0): ?>
                                <table class="service-table">
                                    <thead>
                                        <tr>
                                            <th>Image</th>
                                            <th>Title</th>
                                            <th>Category</th>
                                            <th>Description</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($services as $service):
                                            $desc = $service['DESCRIPTION'];
                                            if ($desc instanceof OCILob) {
                                                $desc = $desc->load();
                                            }

                                            // Get badge class based on category
                                            $badge_class = 'badge-other';
                                            if ($service['CATEGORY'] == 'Room') $badge_class = 'badge-room';
                                            if ($service['CATEGORY'] == 'Food') $badge_class = 'badge-food';
                                            if ($service['CATEGORY'] == 'Spa') $badge_class = 'badge-spa';
                                            if ($service['CATEGORY'] == 'Event') $badge_class = 'badge-event';
                                        ?>
                                            <tr>
                                                <td>
                                                    <?php if ($service['IMAGE_URL']): ?>
                                                        <img src="<?= htmlspecialchars($service['IMAGE_URL']) ?>" alt="Service Image" class="service-image">
                                                    <?php else: ?>
                                                        <div class="bg-light rounded d-flex align-items-center justify-content-center" style="width:80px;height:60px;">
                                                            <i class="fas fa-image text-muted"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= htmlspecialchars($service['TITLE']) ?></td>
                                                <td>
                                                    <span class="badge-category <?= $badge_class ?>">
                                                        <?= htmlspecialchars($service['CATEGORY']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="text-truncate" style="max-width: 200px;" title="<?= htmlspecialchars($desc) ?>">
                                                        <?= htmlspecialchars($desc) ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="action-buttons">
                                                        <a href="?edit=<?= $service['IMAGE_ID'] ?>" class="btn-action btn-edit">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <a href="?delete=<?= $service['IMAGE_ID'] ?>" class="btn-action btn-delete" onclick="return confirm('Are you sure you want to delete this service?')">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="fas fa-box-open"></i>
                                    <h3>No Premium Services Found</h3>
                                    <p>Add your first premium service to get started</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Show confirmation before deleting
        document.querySelectorAll('.btn-delete').forEach(btn => {
            btn.addEventListener('click', function(e) {
                if (!confirm('Are you sure you want to delete this service?')) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>

</html>