<?php
session_start();
include("../db.php");

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "pharmacien") {
    echo json_encode(['error' => 'Non autorisé']);
    exit();
}

if (isset($_GET['id'])) {
    try {
        // Récupérer les informations de l'ordonnance
        $stmt = $conn->prepare("
            SELECT o.*, 
                   p.nom as patient_nom, p.prenom as patient_prenom,
                   u.firstname as medecin_nom, u.lastname as medecin_prenom
            FROM ordonnances o
            JOIN patients p ON o.patient_id = p.id
            JOIN users u ON o.medecin_id = u.id
            WHERE o.id = :id
        ");
        $stmt->execute([':id' => $_GET['id']]);
        $ordonnance = $stmt->fetch();

        // Récupérer les prescriptions
        $stmt = $conn->prepare("
            SELECT p.*, m.nom as medicament_nom
            FROM prescriptions p
            JOIN medicaments m ON p.medicament_id = m.id
            WHERE p.ordonnance_id = :ordonnance_id
        ");
        $stmt->execute([':ordonnance_id' => $_GET['id']]);
        $prescriptions = $stmt->fetchAll();

        $response = [
            'patient' => $ordonnance['patient_nom'] . ' ' . $ordonnance['patient_prenom'],
            'medecin' => $ordonnance['medecin_nom'] . ' ' . $ordonnance['medecin_prenom'],
            'date' => date('d/m/Y', strtotime($ordonnance['date_creation'])),
            'notes' => $ordonnance['notes'],
            'prescriptions' => array_map(function($p) {
                return [
                    'medicament' => $p['medicament_nom'],
                    'posologie' => $p['posologie'],
                    'duree' => $p['duree']
                ];
            }, $prescriptions)
        ];

        echo json_encode($response);
    } catch(PDOException $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['error' => 'ID non fourni']);
}
?>
