<?php
session_start();
include("../db.php");

// Vérification de l'authentification et du rôle
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "pharmacien") {
    header("Location: ../pages-login.php");
    exit();
}

// Récupération des médicaments
$stmt = $conn->prepare("SELECT * FROM medicaments WHERE pharmacien_id = :pharmacien_id");
$stmt->bindParam(":pharmacien_id", $_SESSION["user_id"]);
$stmt->execute();
$medicaments = $stmt->fetchAll(PDO::FETCH_ASSOC);

include("../header.php");
?>

<main id="main" class="main">
    <div class="pagetitle pt-5">
        <h1>Gestion des Médicaments</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../dashboard-pharmacien.php">Accueil</a></li>
                <li class="breadcrumb-item active">Médicaments</li>
            </ol>
        </nav>
    </div>

    <section class="section">
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Stock des Médicaments</h5>
                        <button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addMedicamentModal">
                            <i class="bi bi-plus-circle"></i> Ajouter un Médicament
                        </button>

                        <table class="table datatable">
                            <thead>
                                <tr>
                                    <th>Nom</th>
                                    <th>Description</th>
                                    <th>Stock</th>
                                    <th>Prix</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($medicaments as $medicament): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($medicament['nom']); ?></td>
                                    <td><?php echo htmlspecialchars($medicament['description']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $medicament['stock'] > 10 ? 'success' : 'danger'; ?>">
                                            <?php echo $medicament['stock']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo number_format($medicament['prix'], 2); ?> DHs</td>
                                    <td>
                                        <button class="btn btn-primary btn-sm" onclick="updateStock(<?php echo $medicament['id']; ?>)">
                                            <i class="bi bi-plus-circle"></i>
                                        </button>
                                        <button class="btn btn-warning btn-sm" onclick="editMedicament(<?php echo $medicament['id']; ?>)">
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

<!-- Modal Ajout Médicament -->
<div class="modal fade" id="addMedicamentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ajouter un Médicament</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addMedicamentForm">
                    <div class="mb-3">
                        <label for="nom" class="form-label">Nom</label>
                        <input type="text" class="form-control" id="nom" name="nom" required>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="stock" class="form-label">Stock Initial</label>
                        <input type="number" class="form-control" id="stock" name="stock" required min="0">
                    </div>
                    <div class="mb-3">
                        <label for="prix" class="form-label">Prix</label>
                        <input type="number" class="form-control" id="prix" name="prix" required step="0.01" min="0">
                    </div>
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Mise à jour Stock -->
<div class="modal fade" id="updateStockModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Mettre à jour le Stock</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="updateStockForm">
                    <input type="hidden" id="medicament_id_stock" name="medicament_id">
                    <div class="mb-3">
                        <label for="quantite" class="form-label">Quantité à ajouter</label>
                        <input type="number" class="form-control" id="quantite" name="quantite" required min="1">
                    </div>
                    <button type="submit" class="btn btn-primary">Mettre à jour</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Modification Médicament -->
<div class="modal fade" id="editMedicamentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Modifier le Médicament</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editMedicamentForm">
                    <input type="hidden" id="medicament_id_edit" name="medicament_id">
                    <div class="mb-3">
                        <label for="edit_nom" class="form-label">Nom</label>
                        <input type="text" class="form-control" id="edit_nom" name="nom" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_description" class="form-label">Description</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="edit_prix" class="form-label">Prix</label>
                        <input type="number" class="form-control" id="edit_prix" name="prix" required step="0.01" min="0">
                    </div>
                    <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Ajouter SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// Fonction pour mettre à jour le stock
function updateStock(medicamentId) {
    document.getElementById('medicament_id_stock').value = medicamentId;
    new bootstrap.Modal(document.getElementById('updateStockModal')).show();
}

// Fonction pour éditer un médicament
function editMedicament(medicamentId) {
    // Récupérer les informations du médicament
    fetch(`get_medicament.php?id=${medicamentId}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('medicament_id_edit').value = medicamentId;
            document.getElementById('edit_nom').value = data.nom;
            document.getElementById('edit_description').value = data.description;
            document.getElementById('edit_prix').value = data.prix;
            new bootstrap.Modal(document.getElementById('editMedicamentModal')).show();
        });
}

// Gérer la soumission du formulaire d'ajout
document.getElementById('addMedicamentForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    fetch('add_medicament.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                title: 'Succès !',
                text: 'Le médicament a été ajouté avec succès',
                icon: 'success',
                confirmButtonText: 'OK'
            }).then(() => {
                location.reload();
            });
        } else {
            Swal.fire({
                title: 'Erreur',
                text: data.message || 'Une erreur est survenue',
                icon: 'error',
                confirmButtonText: 'OK'
            });
        }
    });
});

// Gérer la soumission du formulaire de mise à jour du stock
document.getElementById('updateStockForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    fetch('update_stock.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                title: 'Succès !',
                text: 'Le stock a été mis à jour avec succès',
                icon: 'success',
                confirmButtonText: 'OK'
            }).then(() => {
                location.reload();
            });
        } else {
            Swal.fire({
                title: 'Erreur',
                text: data.message || 'Une erreur est survenue',
                icon: 'error',
                confirmButtonText: 'OK'
            });
        }
    });
});

// Gérer la soumission du formulaire de modification
document.getElementById('editMedicamentForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    fetch('edit_medicament.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                title: 'Succès !',
                text: 'Le médicament a été modifié avec succès',
                icon: 'success',
                confirmButtonText: 'OK'
            }).then(() => {
                location.reload();
            });
        } else {
            Swal.fire({
                title: 'Erreur',
                text: data.message || 'Une erreur est survenue',
                icon: 'error',
                confirmButtonText: 'OK'
            });
        }
    });
});
</script>

<?php include($_SERVER['DOCUMENT_ROOT'] . "/fet/footer.php"); ?>