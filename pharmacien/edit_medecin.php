<?php
session_start();
include("../db.php");

// Vérifier si l'utilisateur est connecté et est un pharmacien
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'pharmacien') {
    header('Location: ../pages-login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = filter_var($_POST['id'], FILTER_VALIDATE_INT);
    $firstname = filter_var(trim($_POST['firstname']), FILTER_SANITIZE_STRING);
    $lastname = filter_var(trim($_POST['lastname']), FILTER_SANITIZE_STRING);
    $username = filter_var(trim($_POST['username']), FILTER_SANITIZE_STRING);
    $password = trim($_POST['password']);

    if ($id === false) {
        $_SESSION['error_message'] = "ID invalide";
        header('Location: gestion_medecins.php');
        exit();
    }

    // Validation des champs
    $errors = [];
    if (empty($firstname)) $errors[] = "Le prénom est requis";
    if (empty($lastname)) $errors[] = "Le nom est requis";
    if (empty($username)) $errors[] = "Le nom d'utilisateur est requis";

    // Vérifier si le nom d'utilisateur existe déjà pour un autre utilisateur
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
    $stmt->execute([$username, $id]);
    if ($stmt->fetch()) {
        $errors[] = "Ce nom d'utilisateur existe déjà";
    }

    if (empty($errors)) {
        try {
            if (!empty($password)) {
                // Mise à jour avec nouveau mot de passe
                $stmt = $conn->prepare("UPDATE users SET firstname = ?, lastname = ?, username = ?, password = ? WHERE id = ? AND role = 'medecin'");
                $stmt->execute([$firstname, $lastname, $username, $password, $id]);
            } else {
                // Mise à jour sans changer le mot de passe
                $stmt = $conn->prepare("UPDATE users SET firstname = ?, lastname = ?, username = ? WHERE id = ? AND role = 'medecin'");
                $stmt->execute([$firstname, $lastname, $username, $id]);
            }

            $_SESSION['success_message'] = "Le médecin a été modifié avec succès";
        } catch(PDOException $e) {
            $_SESSION['error_message'] = "Erreur lors de la modification: " . $e->getMessage();
        }
    } else {
        $_SESSION['error_message'] = implode("<br>", $errors);
    }
} else {
    $_SESSION['error_message'] = "Méthode non autorisée";
}

header('Location: gestion_medecins.php');
exit();
?>
