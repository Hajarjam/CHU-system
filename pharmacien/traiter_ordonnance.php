<?php
session_start();
include("../db.php");

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "pharmacien") {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        $ordonnance_id = $data['ordonnance_id'];

        $conn->beginTransaction();

        // Mettre à jour le statut de l'ordonnance
        $stmt = $conn->prepare("UPDATE ordonnances SET status = 'traitee' WHERE id = :id");
        $stmt->execute([':id' => $ordonnance_id]);

        // Mettre à jour le stock des médicaments
        $stmt = $conn->prepare("
            SELECT p.medicament_id, m.stock 
            FROM prescriptions p 
            JOIN medicaments m ON p.medicament_id = m.id 
            WHERE p.ordonnance_id = :ordonnance_id
        ");
        $stmt->execute([':ordonnance_id' => $ordonnance_id]);
        $prescriptions = $stmt->fetchAll();

        $updateStock = $conn->prepare("UPDATE medicaments SET stock = stock - 1 WHERE id = :id AND stock > 0");
        
        foreach ($prescriptions as $prescription) {
            if ($prescription['stock'] <= 0) {
                throw new Exception("Stock insuffisant pour certains médicaments");
            }
            $updateStock->execute([':id' => $prescription['medicament_id']]);
        }

        $conn->commit();
        echo json_encode(['success' => true]);
    } catch(Exception $e) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
}
?>
