<?php
session_start();
include("../db.php");

header('Content-Type: application/json');

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "pharmacien") {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Validation des données
        $nom = trim($_POST['nom']);
        $description = trim($_POST['description']);
        $stock = intval($_POST['stock']);
        $prix = floatval($_POST['prix']);

        if (empty($nom)) {
            throw new Exception("Le nom est requis");
        }

        if ($stock < 0) {
            throw new Exception("Le stock ne peut pas être négatif");
        }

        if ($prix <= 0) {
            throw new Exception("Le prix doit être supérieur à 0");
        }

        $stmt = $conn->prepare("INSERT INTO medicaments (nom, description, stock, prix, pharmacien_id) 
                               VALUES (:nom, :description, :stock, :prix, :pharmacien_id)");
        
        $stmt->execute([
            ':nom' => $nom,
            ':description' => $description,
            ':stock' => $stock,
            ':prix' => $prix,
            ':pharmacien_id' => $_SESSION['user_id']
        ]);

        echo json_encode(['success' => true]);
    } catch(Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
}
?>
