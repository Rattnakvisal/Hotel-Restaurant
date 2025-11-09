<?php
$connection = require_once '../config/connect.php';

$name = 'Admin';
$email = 'admin@gmail.com';
$password = password_hash('E12345e', PASSWORD_DEFAULT);
$role = 'admin';

// Check if admin already exists
$check_sql = "SELECT COUNT(*) AS CNT FROM users WHERE email = :email";
$check_stmt = oci_parse($connection, $check_sql);
oci_bind_by_name($check_stmt, ":email", $email);
oci_execute($check_stmt);
$row = oci_fetch_assoc($check_stmt);
oci_free_statement($check_stmt);

if ($row && $row['CNT'] > 0) {
    echo "Admin user already exists.";
} else {
    // Use sequence for user_id
    $insert_sql = "INSERT INTO users (user_id, name, email, password, role) VALUES (USERS_SEQ.NEXTVAL, :name, :email, :password, :role)";
    $insert_stmt = oci_parse($connection, $insert_sql);
    oci_bind_by_name($insert_stmt, ':name', $name);
    oci_bind_by_name($insert_stmt, ':email', $email);
    oci_bind_by_name($insert_stmt, ':password', $password);
    oci_bind_by_name($insert_stmt, ':role', $role);

    if (oci_execute($insert_stmt)) {
        echo "Admin user created successfully.";
    } else {
        $error = oci_error($insert_stmt);
        echo "Error creating admin user: " . $error['message'];
    }

    oci_free_statement($insert_stmt);
}

oci_close($connection);
