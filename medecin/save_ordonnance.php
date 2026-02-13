<?php
session_start();
include("../db.php");

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "medecin") {
    header("Location: ../pages-login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $conn->beginTransaction();

        // Création de l'ordonnance
        $stmt = $conn->prepare("INSERT INTO ordonnances (patient_id, medecin_id, notes, status) 
                               VALUES (:patient_id, :medecin_id, :notes, 'en_attente')");
        
        $stmt->execute([
            ':patient_id' => $_POST['patient_id'],
            ':medecin_id' => $_SESSION['user_id'],
            ':notes' => $_POST['notes']
        ]);

        $ordonnance_id = $conn->lastInsertId();

        // Ajout des prescriptions
        $stmt = $conn->prepare("INSERT INTO prescriptions (ordonnance_id, medicament_id, posologie, duree) 
                               VALUES (:ordonnance_id, :medicament_id, :posologie, :duree)");

        foreach ($_POST['medicaments'] as $key => $medicament_id) {
            if (!empty($medicament_id)) {
                $stmt->execute([
                    ':ordonnance_id' => $ordonnance_id,
                    ':medicament_id' => $medicament_id,
                    ':posologie' => $_POST['posologies'][$key],
                    ':duree' => $_POST['durees'][$key]
                ]);
            }
        }

        $conn->commit();
        header("Location: patient_details.php?id=" . $_POST['patient_id'] . "&success=1");
        exit();
    } catch(PDOException $e) {
        $conn->rollBack();
        header("Location: create_ordonnance.php?patient_id=" . $_POST['patient_id'] . "&error=" . urlencode($e->getMessage()));
        exit();
    }
}

header("Location: patients.php");
exit();
?>
