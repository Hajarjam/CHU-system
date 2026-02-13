<?php
session_start();
include("../db.php");

// Vérifier si l'utilisateur est connecté et est un pharmacien
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'pharmacien') {
    header('Location: ../pages-login.php');
    exit();
}

if (isset($_GET['id'])) {
    $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    
    if ($id === false) {
        $_SESSION['error_message'] = "ID invalide";
        header('Location: gestion_medecins.php');
        exit();
    }

    try {
        // Vérifier si le médecin existe et est bien un médecin
        $stmt = $conn->prepare("SELECT role FROM users WHERE id = ? AND role = 'medecin'");
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            $_SESSION['error_message'] = "Médecin non trouvé";
            header('Location: gestion_medecins.php');
            exit();
        }

        // Supprimer le médecin
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'medecin'");
        $stmt->execute([$id]);

        $_SESSION['success_message'] = "Le médecin a été supprimé avec succès";
    } catch(PDOException $e) {
        $_SESSION['error_message'] = "Erreur lors de la suppression: " . $e->getMessage();
    }
} else {
    $_SESSION['error_message'] = "ID non spécifié";
}

header('Location: gestion_medecins.php');
exit();
?>
