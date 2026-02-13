<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include("db.php");

if (!isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit();
}

// Récupérer les informations de l'utilisateur
$stmt = $conn->prepare("SELECT * FROM users WHERE id = :user_id");
$stmt->bindParam(":user_id", $_SESSION["user_id"]);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Récupérer l'historique en fonction du rôle
if ($_SESSION['role'] === 'medecin') {
    $stmt = $conn->prepare("
        SELECT o.*, p.nom as patient_nom, p.prenom as patient_prenom, 
               COUNT(pr.id) as nb_medicaments
        FROM ordonnances o
        JOIN patients p ON o.patient_id = p.id
        LEFT JOIN prescriptions pr ON o.id = pr.ordonnance_id
        WHERE o.medecin_id = :user_id
        GROUP BY o.id
        ORDER BY o.date_creation DESC
        LIMIT 10
    ");
    $stmt->bindParam(":user_id", $_SESSION["user_id"]);
    $stmt->execute();
} else {
    $stmt = $conn->prepare("
        SELECT o.*, p.nom as patient_nom, p.prenom as patient_prenom,
               u.firstname as medecin_prenom, u.lastname as medecin_nom,
               COUNT(pr.id) as nb_medicaments
        FROM ordonnances o
        JOIN patients p ON o.patient_id = p.id
        JOIN users u ON o.medecin_id = u.id
        LEFT JOIN prescriptions pr ON o.id = pr.ordonnance_id
        WHERE o.status = 'traitee'
        GROUP BY o.id
        ORDER BY o.date_creation DESC
        LIMIT 10
    ");
    $stmt->execute();
}
$historique = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Traitement du formulaire de mise à jour du profil
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'update_profile') {
            $stmt = $conn->prepare("UPDATE users SET firstname = :firstname, lastname = :lastname, 
                                  username = :username WHERE id = :user_id");
            $stmt->bindParam(":firstname", $_POST['firstname']);
            $stmt->bindParam(":lastname", $_POST['lastname']);
            $stmt->bindParam(":username", $_POST['username']);
            $stmt->bindParam(":user_id", $_SESSION["user_id"]);
            $stmt->execute();
            $_SESSION["fullname"] = $_POST['firstname'] . " " . $_POST['lastname'];
            header("Location: users-profile.php?success=profile_updated");
            exit();
        } elseif ($_POST['action'] === 'change_password') {
            if ($_POST['new_password'] === $_POST['confirm_password']) {
                $stmt = $conn->prepare("UPDATE users SET password = :password WHERE id = :user_id");
                $stmt->bindParam(":password", $_POST['new_password']);
                $stmt->bindParam(":user_id", $_SESSION["user_id"]);
                $stmt->execute();
                header("Location: users-profile.php?success=password_changed");
                exit();
            } else {
                header("Location: users-profile.php?error=passwords_dont_match");
                exit();
            }
        }
    }
}

include("header.php");
?>

<style>
.profile-header {
    background: linear-gradient(135deg, #0d6efd 0%, #0099ff 100%);
    padding: 2rem;
    color: white;
    border-radius: 0.5rem;
    margin-bottom: 2rem;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.profile-header h1 {
    margin: 0;
    font-size: 2rem;
    font-weight: 600;
}

.profile-nav {
    background: white;
    border-radius: 0.5rem;
    padding: 1rem;
    margin-bottom: 2rem;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.profile-nav .nav-link {
    color: #495057;
    font-weight: 500;
    padding: 0.8rem 1.5rem;
    border-radius: 0.5rem;
    transition: all 0.3s ease;
}

.profile-nav .nav-link:hover {
    background-color: #f8f9fa;
}

.profile-nav .nav-link.active {
    background-color: #0d6efd;
    color: white;
}

.profile-card {
    background: white;
    border-radius: 0.5rem;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    margin-bottom: 2rem;
}

.profile-card .card-body {
    padding: 2rem;
}

.profile-info .label {
    font-weight: 600;
    color: #495057;
}

.profile-info .value {
    color: #6c757d;
}

.btn-primary {
    padding: 0.8rem 2rem;
    font-weight: 500;
}

.history-table {
    background: white;
    border-radius: 0.5rem;
    overflow: hidden;
}

.history-table th {
    background-color: #f8f9fa;
    font-weight: 600;
    border: none;
}

.history-table td {
    vertical-align: middle;
    border-color: #f8f9fa;
}

.alert {
    border-radius: 0.5rem;
    border: none;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}
</style>

<main id="main" class="main">
    <div class="profile-header mt-5">
        <h1><?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?></h1>
        <p class="mb-0"><?php echo $_SESSION['role'] === 'medecin' ? 'Médecin' : 'Pharmacien'; ?></p>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php 
            if ($_GET['success'] === 'profile_updated') echo "Profil mis à jour avec succès!";
            if ($_GET['success'] === 'password_changed') echo "Mot de passe modifié avec succès!";
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php 
            if ($_GET['error'] === 'passwords_dont_match') echo "Les mots de passe ne correspondent pas!";
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="profile-nav">
        <ul class="nav nav-pills" role="tablist">
            <li class="nav-item">
                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#profile-overview">
                    <i class="bi bi-person me-2"></i>Aperçu
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#profile-edit">
                    <i class="bi bi-pencil me-2"></i>Modifier
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#profile-change-password">
                    <i class="bi bi-key me-2"></i>Mot de passe
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#profile-history">
                    <i class="bi bi-clock-history me-2"></i>Historique
                </button>
            </li>
        </ul>
    </div>

    <div class="tab-content">
        <!-- Aperçu du Profil -->
        <div class="tab-pane fade show active" id="profile-overview">
            <div class="profile-card">
                <div class="card-body">
                    <h5 class="card-title mb-4">Informations personnelles</h5>
                    <div class="profile-info">
                        <div class="row mb-3">
                            <div class="col-lg-3 label">Nom complet</div>
                            <div class="col-lg-9 value">
                                <?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-lg-3 label">Rôle</div>
                            <div class="col-lg-9 value">
                                <?php echo $_SESSION['role'] === 'medecin' ? 'Médecin' : 'Pharmacien'; ?>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-3 label">Nom d'utilisateur</div>
                            <div class="col-lg-9 value">
                                <?php echo htmlspecialchars($user['username']); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modifier Profil -->
        <div class="tab-pane fade" id="profile-edit">
            <div class="profile-card">
                <div class="card-body">
                    <h5 class="card-title mb-4">Modifier vos informations</h5>
                    <form method="POST">
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="row mb-4">
                            <label for="firstname" class="col-md-3 label">Prénom</label>
                            <div class="col-md-9">
                                <input name="firstname" type="text" class="form-control" id="firstname" 
                                       value="<?php echo htmlspecialchars($user['firstname']); ?>" required>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <label for="lastname" class="col-md-3 label">Nom</label>
                            <div class="col-md-9">
                                <input name="lastname" type="text" class="form-control" id="lastname" 
                                       value="<?php echo htmlspecialchars($user['lastname']); ?>" required>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <label for="username" class="col-md-3 label">Nom d'utilisateur</label>
                            <div class="col-md-9">
                                <input name="username" type="text" class="form-control" id="username" 
                                       value="<?php echo htmlspecialchars($user['username']); ?>" required>
                            </div>
                        </div>

                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save me-2"></i>Enregistrer les modifications
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Changer Mot de Passe -->
        <div class="tab-pane fade" id="profile-change-password">
            <div class="profile-card">
                <div class="card-body">
                    <h5 class="card-title mb-4">Changer votre mot de passe</h5>
                    <form method="POST">
                        <input type="hidden" name="action" value="change_password">

                        <div class="row mb-4">
                            <label for="newPassword" class="col-md-3 label">Nouveau mot de passe</label>
                            <div class="col-md-9">
                                <input name="new_password" type="password" class="form-control" id="newPassword" required>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <label for="confirmPassword" class="col-md-3 label">Confirmer le mot de passe</label>
                            <div class="col-md-9">
                                <input name="confirm_password" type="password" class="form-control" id="confirmPassword" required>
                            </div>
                        </div>

                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-key me-2"></i>Changer le mot de passe
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Historique -->
        <div class="tab-pane fade" id="profile-history">
            <div class="profile-card">
                <div class="card-body">
                    <h5 class="card-title mb-4">
                        <?php echo $_SESSION['role'] === 'medecin' ? 'Historique des ordonnances créées' : 'Historique des ordonnances traitées'; ?>
                    </h5>
                    <div class="table-responsive">
                        <table class="table history-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Patient</th>
                                    <?php if ($_SESSION['role'] === 'pharmacien'): ?>
                                        <th>Médecin</th>
                                    <?php endif; ?>
                                    <th>Médicaments</th>
                                    <th>Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($historique as $item): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y', strtotime($item['date_creation'])); ?></td>
                                        <td><?php echo htmlspecialchars($item['patient_prenom'] . ' ' . $item['patient_nom']); ?></td>
                                        <?php if ($_SESSION['role'] === 'pharmacien'): ?>
                                            <td><?php echo htmlspecialchars($item['medecin_prenom'] . ' ' . $item['medecin_nom']); ?></td>
                                        <?php endif; ?>
                                        <td><?php echo $item['nb_medicaments']; ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $item['status'] === 'en_attente' ? 'warning' : 
                                                    ($item['status'] === 'traitee' ? 'success' : 'danger'); 
                                            ?>">
                                                <?php 
                                                echo $item['status'] === 'en_attente' ? 'En attente' : 
                                                    ($item['status'] === 'traitee' ? 'Traitée' : 'Annulée'); 
                                                ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include("footer.php"); ?>