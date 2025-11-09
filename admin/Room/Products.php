<?php
require_once '../../config/connect.php';

// --- Fetch Services (Rooms) ---
$services = [];
$sql = "SELECT * FROM viewsitem ORDER BY image_id DESC";
$stmt = oci_parse($connection, $sql);
oci_execute($stmt);
while ($row = oci_fetch_assoc($stmt)) {
    $services[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin Dashboard</title>
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css"
        rel="stylesheet" />
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" />
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" />
    <link rel="stylesheet" href="../../assets/Css/admin.css" />
    <link rel="stylesheet" href="../../assets/Css/detail.css" />
</head>

<body>
    <div class="dashboard-container">
        <?php require_once '../include/Header.php'; ?>
        <script src="../../assets/Js/app.js"></script>
        <!-- Main Content -->
        <div class="main-content">
            <h3>Existing Services</h3>
            <a href="../User/manage_detail.php" class="btn btn-primary" style="margin-bottom:16px;"><i class="fas fa-plus"></i> Add Service</a>
            <div style="overflow-x:auto;">
                <table class="manage-services-table">
                    <tr>
                        <th>Image</th>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Description</th>
                        <th>Action</th>
                    </tr>
                    <?php foreach ($services as $service): ?>
                        <tr>
                            <td data-label="Image">
                                <?php if ($service['IMAGE_URL']): ?>
                                    <img src="<?= htmlspecialchars($service['IMAGE_URL']) ?>" alt="Service Image">
                                <?php endif; ?>
                            </td>
                            <td data-label="Title"><?= htmlspecialchars($service['TITLE']) ?></td>
                            <td data-label="Category"><?= htmlspecialchars($service['CATEGORY']) ?></td>
                            <td data-label="Description">
                                <?php
                                $desc = $service['DESCRIPTION'];
                                if ($desc instanceof OCILob) {
                                    $desc = $desc->load();
                                }
                                echo htmlspecialchars($desc);
                                ?>
                            </td>
                            <td data-label="Action">
                                <a href="../User/manage_detail.php?edit=<?= $service['IMAGE_ID'] ?>" style="margin-left:8px;">Edit</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>
    </div>
    <script src="../../assets/Js/app.js"></script>
</body>

</html>