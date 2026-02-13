<?php
session_start();
include("../db.php");

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "pharmacien") {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $medicament_id = $_POST['medicament_id'];
        $quantite = intval($_POST['quantite']);

        if ($quantite <= 0) {
            throw new Exception("La quantité doit être supérieure à 0");
        }

        // Vérifier que le médicament appartient au pharmacien
        $stmt = $conn->prepare("SELECT * FROM medicaments WHERE id = :id AND pharmacien_id = :pharmacien_id");
        $stmt->bindParam(":id", $medicament_id);
        $stmt->bindParam(":pharmacien_id", $_SESSION["user_id"]);
        $stmt->execute();
        
        if (!$stmt->fetch()) {
            throw new Exception("Médicament non trouvé");
        }

        // Mettre à jour le stock
        $stmt = $conn->prepare("UPDATE medicaments SET stock = stock + :quantite WHERE id = :id");
        $stmt->bindParam(":quantite", $quantite);
        $stmt->bindParam(":id", $medicament_id);
        $stmt->execute();

        echo json_encode(['success' => true]);
    } catch(Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
}
?>
