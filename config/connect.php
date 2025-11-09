<?php
if (!extension_loaded('oci8')) {
    die('OCI8 extension is not loaded. please enable it in your php.ini file.');
}

$host = 'localhost';
$port = '1521';
$service_name = 'XE';
$username = 'RoyalNestdb';
$password = 'E12345e';

$connectionString = sprintf(
    "(DESCRIPTION = (ADDRESS = (PROTOCOL = TCP)(HOST = %s)(PORT = %s))(CONNECT_DATA = (SERVICE_NAME = %s)))",
    $host,
    $port, // Use $port here
    $service_name
);

try {
    if (function_exists('oci_connect')) {
        $connection = oci_connect($username, $password, $connectionString);
        if (!$connection) {
            $error = oci_error();
            die('Connection failed: ' . $error['message']);
        } else {
            //echo 'Connected successfully to the Oracle database.';
        }
    }
} catch (Exception $e) {
    die('Error occurred: ' . $e->getMessage());
}
return $connection;
