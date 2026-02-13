<?php
session_start();
require_once('../db.php');

// Vérifier si l'utilisateur est connecté et a le rôle admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /fet/login.php');
    exit();
}

$message = '';
$error = '';

// Traitement des actions (ajout, modification, suppression)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                // Traitement de l'ajout d'utilisateur
                if (isset($_POST['username'], $_POST['firstname'], $_POST['lastname'], $_POST['password'], $_POST['role'])) {
                    $username = trim($_POST['username']);
                    $firstname = trim($_POST['firstname']);
                    $lastname = trim($_POST['lastname']);
                    $password = trim($_POST['password']); // Using plain password as in index.php
                    $role = trim($_POST['role']);
                    
                    $stmt = $conn->prepare("INSERT INTO users (username, firstname, lastname, password, role) VALUES (?, ?, ?, ?, ?)");
                    if ($stmt->execute([$username, $firstname, $lastname, $password, $role])) {
                        $message = "Utilisateur ajouté avec succès";
                    } else {
                        $error = "Erreur lors de l'ajout de l'utilisateur";
                    }
                }
                break;
                
            case 'edit':
                // Traitement de la modification d'utilisateur
                if (isset($_POST['user_id'], $_POST['username'], $_POST['firstname'], $_POST['lastname'], $_POST['role'])) {
                    $user_id = $_POST['user_id'];
                    $username = trim($_POST['username']);
                    $firstname = trim($_POST['firstname']);
                    $lastname = trim($_POST['lastname']);
                    $role = trim($_POST['role']);
                    
                    $sql = "UPDATE users SET username = ?, firstname = ?, lastname = ?, role = ?";
                    $params = [$username, $firstname, $lastname, $role];
                    
                    if (!empty($_POST['password'])) {
                        $password = trim($_POST['password']);
                        $sql .= ", password = ?";
                        $params[] = $password;
                    }
                    
                    $sql .= " WHERE id = ?";
                    $params[] = $user_id;
                    
                    $stmt = $conn->prepare($sql);
                    if ($stmt->execute($params)) {
                        $message = "Utilisateur modifié avec succès";
                    } else {
                        $error = "Erreur lors de la modification de l'utilisateur";
                    }
                }
                break;
                
            case 'delete':
                // Traitement de la suppression d'utilisateur
                if (isset($_POST['user_id'])) {
                    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
                    if ($stmt->execute([$_POST['user_id']])) {
                        $message = "Utilisateur supprimé avec succès";
                    } else {
                        $error = "Erreur lors de la suppression de l'utilisateur";
                    }
                }
                break;
        }
    }
}

// Récupération de la liste des utilisateurs
$stmt = $conn->query("SELECT id, username, firstname, lastname, role FROM users ORDER BY username");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

include('../header1.php');
?>

<main id="main" class="main">
    <div class="pagetitle mt-5">
        <h1>Gestion des Utilisateurs</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="/fet/index.php">Accueil</a></li>
                <li class="breadcrumb-item active">Gestion des Utilisateurs</li>
            </ol>
        </nav>
    </div>

    <section class="section">
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Liste des Utilisateurs</h5>
                        
                        <?php if ($message): ?>
                            <div class="alert alert-success"><?php echo $message; ?></div>
                        <?php endif; ?>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>

                        <!-- Bouton Ajouter -->
                        <button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addUserModal">
                            <i class="bi bi-plus-circle"></i> Ajouter un utilisateur
                        </button>

                        <!-- Tableau des utilisateurs -->
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nom d'utilisateur</th>
                                    <th>Prénom</th>
                                    <th>Nom</th>
                                    <th>Rôle</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['id']); ?></td>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['firstname']); ?></td>
                                    <td><?php echo htmlspecialchars($user['lastname']); ?></td>
                                    <td><?php echo htmlspecialchars($user['role']); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-primary" onclick="editUser(<?php echo htmlspecialchars(json_encode($user)); ?>)">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<!-- Modal Ajouter Utilisateur -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ajouter un utilisateur</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label for="username" class="form-label">Nom d'utilisateur</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="firstname" class="form-label">Prénom</label>
                        <input type="text" class="form-control" id="firstname" name="firstname" required>
                    </div>
                    <div class="mb-3">
                        <label for="lastname" class="form-label">Nom</label>
                        <input type="text" class="form-control" id="lastname" name="lastname" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Mot de passe</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="mb-3">
                        <label for="role" class="form-label">Rôle</label>
                        <select class="form-select" id="role" name="role" required>
                            <option value="admin">Admin</option>
                            <option value="pharmacien">Pharmacien</option>
                            <option value="medecin">Médecin</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                    <button type="submit" class="btn btn-primary">Ajouter</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Modifier Utilisateur -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Modifier l'utilisateur</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="user_id" id="edit_user_id">
                    <div class="mb-3">
                        <label for="edit_username" class="form-label">Nom d'utilisateur</label>
                        <input type="text" class="form-control" id="edit_username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_firstname" class="form-label">Prénom</label>
                        <input type="text" class="form-control" id="edit_firstname" name="firstname" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_lastname" class="form-label">Nom</label>
                        <input type="text" class="form-control" id="edit_lastname" name="lastname" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_password" class="form-label">Nouveau mot de passe (laisser vide pour ne pas changer)</label>
                        <input type="password" class="form-control" id="edit_password" name="password">
                    </div>
                    <div class="mb-3">
                        <label for="edit_role" class="form-label">Rôle</label>
                        <select class="form-select" id="edit_role" name="role" required>
                            <option value="admin">Admin</option>
                            <option value="pharmacien">Pharmacien</option>
                            <option value="medecin">Médecin</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Supprimer Utilisateur -->
<div class="modal fade" id="deleteUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmer la suppression</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="user_id" id="delete_user_id">
                    <p>Êtes-vous sûr de vouloir supprimer cet utilisateur ?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-danger">Supprimer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editUser(user) {
    document.getElementById('edit_user_id').value = user.id;
    document.getElementById('edit_username').value = user.username;
    document.getElementById('edit_firstname').value = user.firstname;
    document.getElementById('edit_lastname').value = user.lastname;
    document.getElementById('edit_role').value = user.role;
    document.getElementById('edit_password').value = '';
    new bootstrap.Modal(document.getElementById('editUserModal')).show();
}

function deleteUser(userId) {
    document.getElementById('delete_user_id').value = userId;
    new bootstrap.Modal(document.getElementById('deleteUserModal')).show();
}
</script>

<?php include('../footer.php'); ?>
