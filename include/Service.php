<?php
// Oracle database configuration
$host = 'localhost';
$port = '1521';
$service_name = 'XE';
$username = 'RoyalNestdb';
$password = 'E12345e';

$connectionString = sprintf(
    "(DESCRIPTION = (ADDRESS = (PROTOCOL = TCP)(HOST = %s)(PORT = %s))(CONNECT_DATA = (SERVICE_NAME = %s)))",
    $host,
    $port,
    $service_name
);

try {
    if (!extension_loaded('oci8')) {
        throw new Exception('OCI8 extension is not loaded. Please enable it in your php.ini file.');
    }
    $conn = oci_connect($username, $password, $connectionString);
    if (!$conn) {
        $e = oci_error();
        throw new Exception('Connection failed: ' . $e['message']);
    }

    // Fetch service items from the database
    $query = "SELECT * FROM viewsitem";
    $stmt = oci_parse($conn, $query);
    oci_execute($stmt);

    $services = [];
    while ($row = oci_fetch_assoc($stmt)) {
        $services[] = $row;
    }

    oci_free_statement($stmt);
    oci_close($conn);
} catch (Exception $e) {
    $services = [];
    $error = "Database error: " . $e->getMessage();
}
