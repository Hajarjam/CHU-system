<?php
session_start();
include("../db.php");

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "pharmacien") {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit();
}

if (isset($_GET['id'])) {
    $stmt = $conn->prepare("SELECT * FROM medicaments WHERE id = :id AND pharmacien_id = :pharmacien_id");
    $stmt->bindParam(":id", $_GET['id']);
    $stmt->bindParam(":pharmacien_id", $_SESSION["user_id"]);
    $stmt->execute();
    
    $medicament = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($medicament) {
        echo json_encode($medicament);
    } else {
        echo json_encode(['success' => false, 'message' => 'Médicament non trouvé']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'ID non fourni']);
}
?>
