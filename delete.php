<?php
include 'connectDB.php';

$id = $_GET['id'] ?? '';

if ($id) {
    // Delete the patient from the database
    $stmt = $pdo->prepare("DELETE FROM patient WHERE pat_id = ?");
    $stmt->execute([$id]);

    header('Location: index.php');
    exit;
}
?>
