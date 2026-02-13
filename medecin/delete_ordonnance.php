<?php
session_start();
include("../db.php");

// Vérification de l'authentification et du rôle
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "medecin") {
    echo json_encode(["success" => false, "message" => "Non autorisé"]);
    exit();
}

// Vérification des données reçues
if (!isset($_POST['ordonnance_id'])) {
    echo json_encode(["success" => false, "message" => "ID de l'ordonnance manquant"]);
    exit();
}

$ordonnance_id = $_POST['ordonnance_id'];

try {
    // Vérifier que l'ordonnance appartient bien au médecin connecté
    $stmt = $conn->prepare("SELECT medecin_id FROM ordonnances WHERE id = :id");
    $stmt->bindParam(":id", $ordonnance_id);
    $stmt->execute();
    $ordonnance = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ordonnance || $ordonnance['medecin_id'] != $_SESSION["user_id"]) {
        echo json_encode(["success" => false, "message" => "Ordonnance non trouvée ou non autorisée"]);
        exit();
    }

    // Supprimer d'abord les prescriptions liées à l'ordonnance
    $stmt = $conn->prepare("DELETE FROM prescriptions WHERE ordonnance_id = :id");
    $stmt->bindParam(":id", $ordonnance_id);
    $stmt->execute();

    // Supprimer l'ordonnance
    $stmt = $conn->prepare("DELETE FROM ordonnances WHERE id = :id AND medecin_id = :medecin_id");
    $stmt->bindParam(":id", $ordonnance_id);
    $stmt->bindParam(":medecin_id", $_SESSION["user_id"]);
    $stmt->execute();

    echo json_encode(["success" => true]);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Erreur lors de la suppression"]);
}
