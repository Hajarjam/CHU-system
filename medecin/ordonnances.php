<?php
session_start();
include("../db.php");

// Vérification de l'authentification et du rôle
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "medecin") {
    header("Location: ../index.php");
    exit();
}

// Paramètres de pagination
$per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $per_page;

// Récupération du nombre total d'ordonnances
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM ordonnances WHERE medecin_id = :medecin_id");
$stmt->bindParam(":medecin_id", $_SESSION["user_id"]);
$stmt->execute();
$total_rows = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_rows / $per_page);

// Récupération des ordonnances du médecin avec pagination
$stmt = $conn->prepare("
    SELECT o.*, p.nom as patient_nom, p.prenom as patient_prenom 
    FROM ordonnances o 
    JOIN patients p ON o.patient_id = p.id 
    WHERE o.medecin_id = :medecin_id 
    ORDER BY o.date_creation DESC
    LIMIT :offset, :per_page
");
$stmt->bindParam(":medecin_id", $_SESSION["user_id"]);
$stmt->bindParam(":offset", $offset, PDO::PARAM_INT);
$stmt->bindParam(":per_page", $per_page, PDO::PARAM_INT);
$stmt->execute();
$ordonnances = $stmt->fetchAll(PDO::FETCH_ASSOC);

include("../header.php");
?>

<main id="main" class="main">
    <div class="pagetitle pt-5">
        <h1>Gestion des Ordonnances</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../dashboard-medecin.php">Accueil</a></li>
                <li class="breadcrumb-item active">Ordonnances</li>
            </ol>
        </nav>
    </div>

    <section class="section">
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Liste des Ordonnances</h5>
                        <button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#createOrdonnanceModal">
                            <i class="bi bi-plus-circle me-1"></i> Nouvelle Ordonnance
                        </button>

                        <!-- Table with stripped rows -->
                        <div class="table-responsive">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="d-flex align-items-center">
                                    <select class="form-select me-2" style="width: auto;" onchange="changeEntriesPerPage(this.value)">
                                        <option value="10" <?php echo isset($_GET['per_page']) && $_GET['per_page'] == '10' ? 'selected' : ''; ?>>10</option>
                                        <option value="25" <?php echo isset($_GET['per_page']) && $_GET['per_page'] == '25' ? 'selected' : ''; ?>>25</option>
                                        <option value="50" <?php echo isset($_GET['per_page']) && $_GET['per_page'] == '50' ? 'selected' : ''; ?>>50</option>
                                    </select>
                                    <span>entrées par page</span>
                                </div>
                                <div class="search-bar">
                                    <input type="text" class="form-control" placeholder="Rechercher..." id="searchInput" onkeyup="searchTable()">
                                </div>
                            </div>

                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th scope="col">Date</th>
                                        <th scope="col">Patient</th>
                                        <th scope="col">Status</th>
                                        <th scope="col" class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($ordonnances as $ordonnance): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y', strtotime($ordonnance['date_creation'])); ?></td>
                                        <td><?php echo htmlspecialchars($ordonnance['patient_nom'] . ' ' . $ordonnance['patient_prenom']); ?></td>
                                        <td>
                                            <?php 
                                            $statusClass = '';
                                            $statusText = '';
                                            switch($ordonnance['status']) {
                                                case 'en_attente':
                                                    $statusClass = 'warning';
                                                    $statusText = 'En attente';
                                                    break;
                                                case 'traitee':
                                                    $statusClass = 'success';
                                                    $statusText = 'Traitée';
                                                    break;
                                                case 'annulee':
                                                    $statusClass = 'danger';
                                                    $statusText = 'Annulée';
                                                    break;
                                            }
                                            ?>
                                            <span class="badge bg-<?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                                        </td>
                                        <td class="text-center">
                                            <button class="btn btn-primary btn-sm" onclick="viewOrdonnance(<?php echo $ordonnance['id']; ?>)" title="Voir">
                                                <i class="bi bi-eye-fill"></i>
                                            </button>
                                            <?php if ($ordonnance['status'] !== 'traitee'): ?>
                                            <button class="btn btn-danger btn-sm ms-1" onclick="deleteOrdonnance(<?php echo $ordonnance['id']; ?>)" title="Supprimer">
                                                <i class="bi bi-trash-fill"></i>
                                            </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <!-- End Table -->

                        <div class="row mt-3">
                            <div class="col-sm-12 col-md-5">
                                <div class="dataTables_info" role="status" aria-live="polite">
                                    Affichage de <?php echo count($ordonnances); ?> ordonnances
                                </div>
                            </div>
                            <div class="col-sm-12 col-md-7">
                                <div class="dataTables_paginate paging_simple_numbers">
                                    <ul class="pagination">
                                        <?php if ($page > 1): ?>
                                        <li class="paginate_button page-item previous" id="dataTable_previous">
                                            <a href="?page=<?php echo $page - 1; ?>&per_page=<?php echo $per_page; ?>" aria-controls="dataTable" data-dt-idx="0" tabindex="0" class="page-link">
                                                Précédent
                                            </a>
                                        </li>
                                        <?php endif; ?>
                                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                        <li class="paginate_button page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                            <a href="?page=<?php echo $i; ?>&per_page=<?php echo $per_page; ?>" aria-controls="dataTable" data-dt-idx="<?php echo $i; ?>" tabindex="<?php echo $i; ?>" class="page-link">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                        <?php endfor; ?>
                                        <?php if ($page < $total_pages): ?>
                                        <li class="paginate_button page-item next" id="dataTable_next">
                                            <a href="?page=<?php echo $page + 1; ?>&per_page=<?php echo $per_page; ?>" aria-controls="dataTable" data-dt-idx="<?php echo $total_pages; ?>" tabindex="<?php echo $total_pages; ?>" class="page-link">
                                                Suivant
                                            </a>
                                        </li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<!-- Modal Création Ordonnance -->
<div class="modal fade" id="createOrdonnanceModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nouvelle Ordonnance</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="createOrdonnanceForm" method="POST" action="create_ordonnance.php">
                    <div class="mb-3">
                        <label for="patient_id" class="form-label">Patient</label>
                        <select class="form-select" id="patient_id" name="patient_id" required>
                            <option value="">Sélectionner un patient</option>
                            <?php
                            $stmt = $conn->prepare("SELECT id, nom, prenom FROM patients WHERE medecin_id = :medecin_id");
                            $stmt->bindParam(":medecin_id", $_SESSION["user_id"]);
                            $stmt->execute();
                            $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            foreach ($patients as $patient) {
                                echo "<option value=\"" . $patient['id'] . "\">" . 
                                     htmlspecialchars($patient['nom'] . " " . $patient['prenom']) . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div id="prescriptions">
                        <div class="prescription-item mb-3">
                            <h6>Prescription #1</h6>
                            <div class="row">
                                <div class="col-md-4">
                                    <label class="form-label">Médicament</label>
                                    <select class="form-select" name="medicaments[]" required>
                                        <?php
                                        $stmt = $conn->prepare("SELECT id, nom FROM medicaments");
                                        $stmt->execute();
                                        $medicaments = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                        foreach ($medicaments as $medicament) {
                                            echo "<option value=\"" . $medicament['id'] . "\">" . 
                                                 htmlspecialchars($medicament['nom']) . "</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Posologie</label>
                                    <input type="text" class="form-control" name="posologies[]" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Durée</label>
                                    <input type="text" class="form-control" name="durees[]" required>
                                </div>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-secondary mb-3" onclick="addPrescription()">
                        Ajouter un médicament
                    </button>
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Créer l'ordonnance</button>
                </form>
            </div>
        </div>
    </div>
</div>
</div>

<!-- Modal de Confirmation de Suppression -->
<div class="modal fade" id="deleteOrdonnanceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Confirmation de suppression</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir supprimer cette ordonnance ?</p>
                <p class="text-danger"><i class="bi bi-exclamation-triangle-fill"></i> Cette action est irréversible.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-danger" id="confirmDelete">Supprimer</button>
            </div>
        </div>
    </div>
</div>

<script>
let prescriptionCount = 1;
let ordonnanceToDelete = null;
let deleteModal = null;

document.addEventListener('DOMContentLoaded', function() {
    deleteModal = new bootstrap.Modal(document.getElementById('deleteOrdonnanceModal'));
    
    // Gestionnaire pour le bouton de confirmation
    document.getElementById('confirmDelete').addEventListener('click', function() {
        if (ordonnanceToDelete !== null) {
            performDelete(ordonnanceToDelete);
        }
    });
});

function addPrescription() {
    prescriptionCount++;
    const newPrescription = `
        <div class="prescription-item mb-3">
            <h6>Prescription #${prescriptionCount}</h6>
            <div class="row">
                <div class="col-md-4">
                    <label class="form-label">Médicament</label>
                    <select class="form-select" name="medicaments[]" required>
                        <?php foreach ($medicaments as $medicament): ?>
                            <option value="<?php echo $medicament['id']; ?>">
                                <?php echo htmlspecialchars($medicament['nom']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Posologie</label>
                    <input type="text" class="form-control" name="posologies[]" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Durée</label>
                    <input type="text" class="form-control" name="durees[]" required>
                </div>
            </div>
        </div>
    `;
    document.getElementById('prescriptions').insertAdjacentHTML('beforeend', newPrescription);
}

function viewOrdonnance(ordonnanceId) {
    window.location.href = '../view_ordonnance.php?id=' + ordonnanceId;
}

function deleteOrdonnance(ordonnanceId) {
    ordonnanceToDelete = ordonnanceId;
    deleteModal.show();
}

function performDelete(ordonnanceId) {
    fetch('delete_ordonnance.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'ordonnance_id=' + ordonnanceId
    })
    .then(response => response.json())
    .then(data => {
        deleteModal.hide();
        if (data.success) {
            // Afficher une notification de succès
            Swal.fire({
                title: 'Succès !',
                text: 'L\'ordonnance a été supprimée avec succès.',
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            }).then(() => {
                location.reload();
            });
        } else {
            Swal.fire({
                title: 'Erreur',
                text: data.message || 'Une erreur est survenue lors de la suppression',
                icon: 'error'
            });
        }
    })
    .catch(error => {
        deleteModal.hide();
        console.error('Erreur:', error);
        Swal.fire({
            title: 'Erreur',
            text: 'Une erreur est survenue lors de la suppression',
            icon: 'error'
        });
    });
}

function searchTable() {
    var input, filter, table, tr, td, i, txtValue;
    input = document.getElementById("searchInput");
    filter = input.value.toUpperCase();
    table = document.querySelector(".table");
    tr = table.getElementsByTagName("tr");

    for (i = 1; i < tr.length; i++) { // Start from 1 to skip header
        let visible = false;
        // Search in date and patient columns
        for (let j = 0; j < 2; j++) { // 0 = date, 1 = patient
            td = tr[i].getElementsByTagName("td")[j];
            if (td) {
                txtValue = td.textContent || td.innerText;
                if (txtValue.toUpperCase().indexOf(filter) > -1) {
                    visible = true;
                    break;
                }
            }
        }
        tr[i].style.display = visible ? "" : "none";
    }
}

function changeEntriesPerPage(value) {
    const url = new URL(window.location.href);
    url.searchParams.set('per_page', value);
    window.location.href = url.toString();
}
</script>

<?php include($_SERVER['DOCUMENT_ROOT'] . "/fet/footer.php"); ?>
