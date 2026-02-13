<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include("../db.php");

// Vérification de l'authentification et du rôle
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "pharmacien") {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit();
}

// Vérification des paramètres
if (!isset($_POST['id']) || !isset($_POST['status'])) {
    echo json_encode(['success' => false, 'message' => 'Paramètres manquants']);
    exit();
}

$id = $_POST['id'];
$status = $_POST['status'];

// Mise à jour du statut
try {
    $stmt = $conn->prepare("UPDATE ordonnances SET status = :status WHERE id = :id");
    $stmt->bindParam(":status", $status);
    $stmt->bindParam(":id", $id);
    $stmt->execute();

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour']);
}
?>
