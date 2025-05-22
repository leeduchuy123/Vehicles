<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize_input($_POST['name']);
    $phone = sanitize_input($_POST['phone']);
    $address = sanitize_input($_POST['address']);

    $stmt = $conn->prepare("INSERT INTO owners (name, phone, address) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $name, $phone, $address);
    $stmt->execute();
    header('Location: owners.php');
    exit;
}
header('Location: owners.php');
exit;
?>