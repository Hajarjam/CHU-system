<?php
session_start();
include("../db.php");

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "medecin") {
    header("Location: ../pages-login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $stmt = $conn->prepare("INSERT INTO patients (nom, prenom, date_naissance, telephone, email, adresse, medecin_id) 
                               VALUES (:nom, :prenom, :date_naissance, :telephone, :email, :adresse, :medecin_id)");
        
        $stmt->execute([
            ':nom' => $_POST['nom'],
            ':prenom' => $_POST['prenom'],
            ':date_naissance' => $_POST['date_naissance'],
            ':telephone' => $_POST['telephone'],
            ':email' => $_POST['email'],
            ':adresse' => $_POST['adresse'],
            ':medecin_id' => $_SESSION['user_id']
        ]);

        header("Location: patients.php?success=1");
    } catch(PDOException $e) {
        header("Location: patients.php?error=" . urlencode($e->getMessage()));
    }
} else {
    header("Location: patients.php");
}
exit();
?>
