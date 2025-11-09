<?php
require_once '../../config/connect.php';
// --- Add Room ---
if (isset($_POST['add_room'])) {
    $room_name = $_POST['room_name'];
    $description = $_POST['description'];
    $price_per_night = $_POST['price_per_night'];
    $status = $_POST['status'];
    $sleeps = $_POST['sleeps'];

    $image_url = '';
    if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/Hotel-Restaurant/assets/uploads/rooms/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $filename = uniqid('room_') . '_' . basename($_FILES['image_file']['name']);
        $targetFile = $uploadDir . $filename;
        $imageUrl = '/Hotel-Restaurant/assets/uploads/rooms/' . $filename;
        if (move_uploaded_file($_FILES['image_file']['tmp_name'], $targetFile)) {
            $image_url = $imageUrl;
        }
    }

    $sql = "INSERT INTO rooms (room_id, room_name, description, price_per_night, status, image_url, sleeps)
            VALUES (rooms_seq.NEXTVAL, :room_name, :description, :price_per_night, :status, :image_url, :sleeps)";
    $stmt = oci_parse($connection, $sql);
    oci_bind_by_name($stmt, ':room_name', $room_name);
    oci_bind_by_name($stmt, ':description', $description);
    oci_bind_by_name($stmt, ':price_per_night', $price_per_night);
    oci_bind_by_name($stmt, ':status', $status);
    oci_bind_by_name($stmt, ':image_url', $image_url);
    oci_bind_by_name($stmt, ':sleeps', $sleeps);
    if (!oci_execute($stmt)) {
        $e = oci_error($stmt);
        die("Error inserting room: " . $e['message']);
    }
    header("Location: Add_Room.php?msg=added");
    exit;
}

// --- Update Room ---
if (isset($_POST['update_room'])) {
    $room_id = $_POST['room_id'];
    $room_name = $_POST['room_name'];
    $description = $_POST['description'];
    $price_per_night = $_POST['price_per_night'];
    $status = $_POST['status'];
    $sleeps = $_POST['sleeps'];

    $image_url = '';
    if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/Hotel-Restaurant/assets/uploads/rooms/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $filename = uniqid('room_') . '_' . basename($_FILES['image_file']['name']);
        $targetFile = $uploadDir . $filename;
        $imageUrl = '/Hotel-Restaurant/assets/uploads/rooms/' . $filename;
        if (move_uploaded_file($_FILES['image_file']['tmp_name'], $targetFile)) {
            $image_url = $imageUrl;
        }
    } else if (isset($_POST['old_image_url'])) {
        $image_url = $_POST['old_image_url'];
    }

    $sql = "UPDATE rooms SET room_name=:room_name, description=:description, price_per_night=:price_per_night, status=:status, image_url=:image_url, sleeps=:sleeps WHERE room_id=:room_id";
    $stmt = oci_parse($connection, $sql);
    oci_bind_by_name($stmt, ':room_name', $room_name);
    oci_bind_by_name($stmt, ':description', $description);
    oci_bind_by_name($stmt, ':price_per_night', $price_per_night);
    oci_bind_by_name($stmt, ':status', $status);
    oci_bind_by_name($stmt, ':image_url', $image_url);
    oci_bind_by_name($stmt, ':sleeps', $sleeps);
    oci_bind_by_name($stmt, ':room_id', $room_id);
    oci_execute($stmt);
    header("Location: Add_Room.php?msg=updated");
    exit;
}

// --- Delete Room ---
if (isset($_GET['delete'])) {
    $room_id = $_GET['delete'];
    $sql = "DELETE FROM rooms WHERE room_id=:room_id";
    $stmt = oci_parse($connection, $sql);
    oci_bind_by_name($stmt, ':room_id', $room_id);
    oci_execute($stmt);
    header("Location: Add_Room.php?msg=deleted");
    exit;
}

// --- Fetch All Rooms ---
$sql = "SELECT * FROM rooms ORDER BY room_id DESC";
$stmt = oci_parse($connection, $sql);
if (!oci_execute($stmt)) {
    $e = oci_error($stmt);
    die("Error fetching rooms: " . $e['message']);
}
$rooms = [];
while ($row = oci_fetch_assoc($stmt)) {

    if (isset($row['DESCRIPTION']) && is_object($row['DESCRIPTION']) && $row['DESCRIPTION'] instanceof OCILob) {
        $row['DESCRIPTION'] = $row['DESCRIPTION']->load();
    }

    $default_img = '/Hotel-Restaurant/img/default-room.jpg';
    $img = trim($row['IMAGE_URL'] ?? '');
    $filename = $img ? basename($img) : '';
    $local_url = '/Hotel-Restaurant/assets/uploads/rooms/' . $filename;
    $file_path = $_SERVER['DOCUMENT_ROOT'] . $local_url;

    if ($img && preg_match('/^https?:\/\//', $img)) {
        $row['IMAGE_URL'] = $img;
    } elseif ($filename && file_exists($file_path)) {
        $row['IMAGE_URL'] = $local_url;
    } else {
        $row['IMAGE_URL'] = $default_img;
    }
    $rooms[] = $row;
}

// --- Edit Mode: Fetch Room ---
$edit_room = null;
if (isset($_GET['edit'])) {
    $room_id = $_GET['edit'];
    $sql = "SELECT * FROM rooms WHERE room_id=:room_id";
    $stmt = oci_parse($connection, $sql);
    oci_bind_by_name($stmt, ':room_id', $room_id);
    oci_execute($stmt);
    $edit_room = oci_fetch_assoc($stmt);

    if ($edit_room && isset($edit_room['DESCRIPTION']) && is_object($edit_room['DESCRIPTION']) && $edit_room['DESCRIPTION'] instanceof OCILob) {
        $edit_room['DESCRIPTION'] = $edit_room['DESCRIPTION']->load();
    }

    $default_img = '/Hotel-Restaurant/img/default-room.jpg';
    $img = trim($edit_room['IMAGE_URL'] ?? '');
    $filename = $img ? basename($img) : '';
    $local_url = '/Hotel-Restaurant/assets/uploads/rooms/' . $filename;
    $file_path = $_SERVER['DOCUMENT_ROOT'] . $local_url;

    if ($img && preg_match('/^https?:\/\//', $img)) {
        $edit_room['IMAGE_URL'] = $img;
    } elseif ($filename && file_exists($file_path)) {
        $edit_room['IMAGE_URL'] = $local_url;
    } else {
        $edit_room['IMAGE_URL'] = $default_img;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RoyalNest - Premium Room Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #3f37c9;
            --royal-gold: #d4af37;
            --royal-purple: #7851a9;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
            --light-purple: #e6e1f7;
            --transition: all 0.3s ease;
            --success: #2ecc71;
            --warning: #f39c12;
            --danger: #e74c3c;
            --info: #3498db;
            --shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
            color: var(--dark);
            min-height: 100vh;
            padding: 20px;
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


        .admin-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid var(--light-purple);
        }

        .logo-container {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .logo {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--royal-purple), var(--primary));
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: var(--shadow);
        }

        .logo i {
            font-size: 28px;
            color: white;
        }

        .page-title {
            font-size: 2.2rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--royal-purple), var(--primary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            position: relative;
            display: inline-block;
        }

        .page-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 0;
            width: 80px;
            height: 4px;
            background: var(--royal-gold);
            border-radius: 2px;
        }

        .admin-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .admin-profile {
            display: flex;
            align-items: center;
            gap: 15px;
            background: white;
            padding: 12px 20px;
            border-radius: 12px;
            box-shadow: var(--shadow);
        }

        .admin-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--royal-purple), var(--primary));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            color: white;
            font-weight: 600;
        }

        .admin-details h3 {
            color: var(--royal-purple);
            font-size: 1.1rem;
        }

        .admin-details p {
            color: var(--gray);
            font-size: 0.9rem;
        }

        /* Dashboard Layout */
        .dashboard-layout {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 25px;
            margin-bottom: 40px;
        }

        /* Form Card */
        .form-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: var(--shadow);
            margin-bottom: 25px;
        }

        .card-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--light-purple);
        }

        .card-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--royal-purple), var(--primary));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            color: white;
        }

        .card-title {
            font-size: 1.6rem;
            font-weight: 700;
            color: var(--royal-purple);
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
            margin-bottom: 25px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--royal-purple);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-label i {
            font-size: 0.9rem;
            color: var(--royal-gold);
        }

        .form-control {
            width: 100%;
            padding: 14px 18px;
            border: 1px solid #ddd;
            border-radius: 10px;
            font-size: 1rem;
            transition: var(--transition);
            background: #f9f9ff;
        }

        .form-control:focus {
            border-color: var(--royal-purple);
            outline: none;
            box-shadow: 0 0 0 3px rgba(120, 81, 169, 0.1);
            background: white;
        }

        textarea.form-control {
            min-height: 150px;
            resize: vertical;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 14px 28px;
            background: linear-gradient(135deg, var(--royal-purple), var(--primary));
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            gap: 8px;
            box-shadow: 0 4px 15px rgba(120, 81, 169, 0.2);
        }

        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(120, 81, 169, 0.3);
        }

        .btn i {
            font-size: 1.1rem;
        }

        .btn-secondary {
            background: linear-gradient(135deg, #6c757d, #5a6268);
        }

        .btn-gold {
            background: linear-gradient(135deg, var(--royal-gold), #c19d2e);
        }

        .btn-container {
            display: flex;
            gap: 15px;
            margin-top: 15px;
        }

        /* Image Upload */
        .image-upload {
            margin-top: 10px;
        }

        .choose-image-btn {
            display: inline-block;
            background: var(--royal-purple);
            color: #fff;
            border: none;
            padding: 10px 18px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.95rem;
            transition: background 0.2s;
            margin-bottom: 10px;
            width: 100%;
            text-align: center;
        }

        .choose-image-btn:hover {
            background: var(--royal-gold);
            color: var(--dark);
        }

        .image-preview {
            width: 100%;
            height: 200px;
            border-radius: 10px;
            border: 1px dashed #ddd;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            margin-top: 10px;
            background: #f9f9ff;
        }

        .image-preview img {
            max-width: 100%;
            max-height: 100%;
            object-fit: cover;
        }

        .image-preview i {
            font-size: 3rem;
            color: #ddd;
        }

        /* Stats Card */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: var(--shadow);
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 15px;
        }

        .bg-primary {
            background: rgba(67, 97, 238, 0.15);
            color: var(--primary);
        }

        .bg-success {
            background: rgba(46, 204, 113, 0.15);
            color: var(--success);
        }

        .bg-warning {
            background: rgba(241, 196, 15, 0.15);
            color: var(--warning);
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 5px;
            color: var(--royal-purple);
        }

        .stat-title {
            font-size: 1rem;
            color: var(--gray);
        }

        /* Room Table */
        .table-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .table-title {
            font-size: 1.6rem;
            font-weight: 700;
            color: var(--royal-purple);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .room-table {
            width: 100%;
            border-collapse: collapse;
            border-radius: 10px;
            overflow: hidden;
        }

        .room-table th {
            background: linear-gradient(135deg, var(--royal-purple), var(--primary));
            color: white;
            text-align: left;
            padding: 16px 20px;
            font-weight: 600;
            font-size: 1.05rem;
        }

        .room-table td {
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
            font-size: 0.95rem;
        }

        .room-table tr:nth-child(even) {
            background-color: #f9f9ff;
        }

        .room-table tr:hover {
            background-color: #f0f0ff;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
        }

        .action-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
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

        .status-badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .status-available {
            background: rgba(46, 204, 113, 0.15);
            color: #27ae60;
        }

        .status-booked {
            background: rgba(241, 196, 15, 0.15);
            color: #f39c12;
        }

        .status-maintenance {
            background: rgba(52, 152, 219, 0.15);
            color: #3498db;
        }

        .room-thumb {
            width: 80px;
            height: 60px;
            border-radius: 8px;
            object-fit: cover;
            border: 1px solid #eee;
            cursor: pointer;
            transition: var(--transition);
        }

        .room-thumb:hover {
            transform: scale(1.05);
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            max-width: 90%;
            max-height: 90%;
            border-radius: 15px;
            overflow: hidden;
            position: relative;
        }

        .modal-content img {
            max-width: 100%;
            max-height: 80vh;
            display: block;
        }

        .close-modal {
            position: absolute;
            top: 20px;
            right: 20px;
            color: white;
            font-size: 2rem;
            cursor: pointer;
            background: rgba(0, 0, 0, 0.5);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Responsive */
        @media (max-width: 1200px) {
            .dashboard-layout {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 992px) {
            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 20px;
            }

            .page-title {
                font-size: 1.8rem;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .admin-container {
                padding: 10px;
            }

            .form-card,
            .table-card {
                padding: 20px;
            }

            .room-table {
                display: block;
                overflow-x: auto;
            }

            .action-buttons {
                flex-direction: column;
                gap: 8px;
            }

            .action-link {
                justify-content: flex-start;
            }
        }

        @media (max-width: 576px) {
            .page-title {
                font-size: 1.6rem;
            }

            .admin-profile {
                flex-direction: column;
                text-align: center;
            }

            .btn-container {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }
        }
    </style>
</head>

<body>
    <div class="admin-container">
        <a href="../Room/manage_Room.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Room Management
        </a>
        <div class="header">
            <div class="logo-container">
                <div class="logo">
                    <i class="fas fa-crown"></i>
                </div>
                <h1 class="page-title">RoyalNest Room Management</h1>
            </div>
            <div class="admin-info">
                <div class="admin-profile">
                    <div class="admin-avatar">RJ</div>
                    <div class="admin-details">
                        <h3>Robert Johnson</h3>
                        <p>Hotel Administrator</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Section -->
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-icon bg-primary">
                    <i class="fas fa-bed"></i>
                </div>
                <div class="stat-value"><?php echo count($rooms); ?></div>
                <div class="stat-title">Total Rooms</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon bg-success">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-value">
                    <?php
                    $available = 0;
                    foreach ($rooms as $room) {
                        if ($room['STATUS'] === 'Available') $available++;
                    }
                    echo $available;
                    ?>
                </div>
                <div class="stat-title">Available</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon bg-warning">
                    <i class="fas fa-calendar-times"></i>
                </div>
                <div class="stat-value">
                    <?php
                    $booked = 0;
                    foreach ($rooms as $room) {
                        if ($room['STATUS'] === 'Booked') $booked++;
                    }
                    echo $booked;
                    ?>
                </div>
                <div class="stat-title">Booked</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon bg-warning">
                    <i class="fas fa-tools"></i>
                </div>
                <div class="stat-value">
                    <?php
                    $maintenance = 0;
                    foreach ($rooms as $room) {
                        if ($room['STATUS'] === 'Maintenance') $maintenance++;
                    }
                    echo $maintenance;
                    ?>
                </div>
                <div class="stat-title">Maintenance</div>
            </div>
        </div>

        <div class="dashboard-layout">
            <!-- Form Section -->
            <div>
                <div class="form-card">
                    <div class="card-header">
                        <div class="card-icon">
                            <i class="fas fa-door-open"></i>
                        </div>
                        <h2 class="card-title">
                            <?php echo $edit_room ? 'Edit Room' : 'Add New Room'; ?>
                        </h2>
                    </div>

                    <form method="post" enctype="multipart/form-data">
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label"><i class="fas fa-signature"></i> Room Name</label>
                                <input type="text" name="room_name" class="form-control" placeholder="Deluxe Suite" required value="<?php echo $edit_room['ROOM_NAME'] ?? ''; ?>">
                            </div>

                            <div class="form-group">
                                <label class="form-label"><i class="fas fa-tag"></i> Price Per Night ($)</label>
                                <input type="number" step="0.01" name="price_per_night" class="form-control" placeholder="5000" required value="<?php echo $edit_room['PRICE_PER_NIGHT'] ?? ''; ?>">
                            </div>

                            <div class="form-group">
                                <label class="form-label"><i class="fas fa-bed"></i> Sleeps</label>
                                <input type="number" name="sleeps" class="form-control" placeholder="2" value="<?php echo $edit_room['SLEEPS'] ?? ''; ?>">
                            </div>

                            <div class="form-group">
                                <label class="form-label"><i class="fas fa-info-circle"></i> Status</label>
                                <select name="status" class="form-control" required>
                                    <option value="Available" <?php echo ($edit_room['STATUS'] ?? '') === 'Available' ? 'selected' : ''; ?>>Available</option>
                                    <option value="Booked" <?php echo ($edit_room['STATUS'] ?? '') === 'Booked' ? 'selected' : ''; ?>>Booked</option>
                                    <option value="Maintenance" <?php echo ($edit_room['STATUS'] ?? '') === 'Maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label"><i class="fas fa-align-left"></i> Description</label>
                            <textarea name="description" class="form-control" placeholder="Describe the room features and amenities..." rows="5"><?php echo $edit_room['DESCRIPTION'] ?? ''; ?></textarea>
                        </div>

                        <div class="form-group">
                            <label class="form-label"><i class="fas fa-image"></i> Room Image</label>
                            <input type="file" id="image_file" name="image_file" accept="image/*" style="display:none;">
                            <button type="button" class="choose-image-btn" onclick="document.getElementById('image_file').click();">
                                <i class="fas fa-upload"></i> Choose Room Image
                            </button>
                            <div class="image-preview" id="image_preview">
                                <?php if (!empty($edit_room['IMAGE_URL'] ?? '')): ?>
                                    <img src="<?php echo htmlspecialchars($edit_room['IMAGE_URL']); ?>" alt="Room Preview">
                                <?php else: ?>
                                    <i class="fas fa-image"></i>
                                <?php endif; ?>
                            </div>
                            <?php if ($edit_room): ?>
                                <input type="hidden" name="old_image_url" value="<?php echo htmlspecialchars($edit_room['IMAGE_URL']); ?>">
                            <?php endif; ?>
                        </div>

                        <?php if ($edit_room): ?>
                            <input type="hidden" name="room_id" value="<?php echo $edit_room['ROOM_ID']; ?>">
                            <div class="btn-container">
                                <button type="submit" name="update_room" class="btn">
                                    <i class="fas fa-sync-alt"></i> Update Room
                                </button>
                                <a href="?" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Cancel
                                </a>
                            </div>
                        <?php else: ?>
                            <button type="submit" name="add_room" class="btn">
                                <i class="fas fa-plus"></i> Add Room
                            </button>
                        <?php endif; ?>
                    </form>
                </div>

                <div class="form-card">
                    <div class="card-header">
                        <div class="card-icon" style="background: linear-gradient(135deg, var(--royal-gold), #c19d2e);">
                            <i class="fas fa-lightbulb"></i>
                        </div>
                        <h2 class="card-title">Quick Tips</h2>
                    </div>
                    <ul style="padding-left: 20px; color: var(--gray); line-height: 1.8;">
                        <li>Use high-quality images to showcase your rooms</li>
                        <li>Keep descriptions clear and highlight key features</li>
                        <li>Update room status regularly to avoid overbooking</li>
                        <li>Use accurate pricing and capacity information</li>
                        <li>Delete outdated or unavailable rooms from inventory</li>
                    </ul>
                </div>
            </div>

            <!-- Room Table -->
            <div>
                <div class="table-card">
                    <div class="table-header">
                        <h2 class="table-title">
                            <i class="fas fa-list"></i> Current Room Inventory
                        </h2>
                        <div class="btn btn-gold">
                            <i class="fas fa-print"></i> Export Report
                        </div>
                    </div>

                    <table class="room-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Image</th>
                                <th>Price</th>
                                <th>Status</th>
                                <th>Sleeps</th>
                                <th>Description</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($rooms)): ?>
                                <tr>
                                    <td colspan="8" style="text-align:center; padding: 30px; color: var(--gray);">
                                        <i class="fas fa-bed" style="font-size: 3rem; margin-bottom: 15px; display: block;"></i>
                                        No rooms found. Add your first room to get started.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($rooms as $room): ?>
                                    <?php
                                    // Image handling
                                    $default_img = '/Hotel-Restaurant/assets/img/default-room.jpg';
                                    $img = trim($room['IMAGE_URL'] ?? '');
                                    $filename = $img ? basename($img) : '';
                                    $local_url = '/Hotel-Restaurant/assets/uploads/rooms/' . $filename;
                                    $file_path = $_SERVER['DOCUMENT_ROOT'] . $local_url;

                                    if ($img && preg_match('/^https?:\/\//', $img)) {
                                        $img_url_display = $img;
                                    } elseif ($filename && file_exists($file_path)) {
                                        $img_url_display = $local_url;
                                    } else {
                                        $img_url_display = $default_img;
                                    }

                                    // Status class
                                    $statusClass = '';
                                    if ($room['STATUS'] === 'Available') $statusClass = 'status-available';
                                    if ($room['STATUS'] === 'Booked') $statusClass = 'status-booked';
                                    if ($room['STATUS'] === 'Maintenance') $statusClass = 'status-maintenance';
                                    ?>
                                    <tr>
                                        <td>RN-<?php echo $room['ROOM_ID']; ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($room['ROOM_NAME']); ?></strong>
                                        </td>
                                        <td>
                                            <?php if (!empty($img_url_display)): ?>
                                                <img src="<?php echo htmlspecialchars($img_url_display); ?>"
                                                    alt="Room Image"
                                                    class="room-thumb"
                                                    onclick="showImageModal('<?php echo htmlspecialchars($img_url_display); ?>')">
                                            <?php else: ?>
                                                <i class="fas fa-image" style="color: #ccc;"></i>
                                            <?php endif; ?>
                                        </td>
                                        <td>$<?php echo number_format($room['PRICE_PER_NIGHT'], 2); ?></td>
                                        <td>
                                            <span class="status-badge <?php echo $statusClass; ?>">
                                                <?php echo htmlspecialchars($room['STATUS']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($room['SLEEPS']); ?>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($room['DESCRIPTION']); ?>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="?edit=<?php echo $room['ROOM_ID']; ?>" class="action-link edit-btn">
                                                    <i class="fas fa-edit"></i> Edit
                                                </a>
                                                <a href="?delete=<?php echo $room['ROOM_ID']; ?>" class="action-link delete-btn" onclick="return confirm('Are you sure you want to delete this room?');">
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

    <!-- Image Modal -->
    <div id="imageModal" class="modal">
        <div class="close-modal" onclick="closeModal()">&times;</div>
        <div class="modal-content">
            <img id="modalImage" src="" alt="Room Image">
        </div>
    </div>

    <script>
        // Image preview for form
        const imageFileInput = document.getElementById('image_file');
        const imagePreview = document.getElementById('image_preview');

        if (imageFileInput) {
            imageFileInput.addEventListener('change', function(event) {
                if (this.files && this.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        imagePreview.innerHTML = `<img src="${e.target.result}" alt="Room Preview">`;
                    };
                    reader.readAsDataURL(this.files[0]);
                }
            });
        }

        // Modal functions
        function showImageModal(src) {
            const modal = document.getElementById('imageModal');
            const modalImg = document.getElementById('modalImage');
            modalImg.src = src;
            modal.style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('imageModal').style.display = 'none';
        }

        // Close modal when clicking outside the image
        window.addEventListener('click', function(event) {
            const modal = document.getElementById('imageModal');
            if (event.target === modal) {
                closeModal();
            }
        });

        // Animation for form elements
        document.querySelectorAll('.form-control').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'translateY(-3px)';
            });

            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'translateY(0)';
            });
        });

        // Animation for buttons
        document.querySelectorAll('.btn').forEach(button => {
            button.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-3px)';
            });

            button.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        });
    </script>
</body>

</html>