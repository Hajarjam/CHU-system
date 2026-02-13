<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include("../db.php");

// Vérification de l'authentification et du rôle
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "pharmacien") {
    header("Location: ../index.php");
    exit();
}

// Initialisation des variables de recherche
$search = isset($_GET['search']) ? $_GET['search'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : 'en_attente';

// Construction de la requête SQL de base
$sql = "SELECT o.*, p.nom as patient_nom, p.prenom as patient_prenom, 
        u.firstname as medecin_prenom, u.lastname as medecin_nom 
        FROM ordonnances o 
        JOIN patients p ON o.patient_id = p.id 
        JOIN users u ON o.medecin_id = u.id 
        WHERE 1=1";

// Ajout des conditions de recherche
if (!empty($search)) {
    $sql .= " AND (p.nom LIKE :search OR p.prenom LIKE :search OR 
              u.firstname LIKE :search OR u.lastname LIKE :search OR 
              o.date_creation LIKE :search)";
}

if ($status !== 'tous') {
    $sql .= " AND o.status = :status";
}

$sql .= " ORDER BY o.date_creation DESC";

// Préparation et exécution de la requête
$stmt = $conn->prepare($sql);

if (!empty($search)) {
    $searchParam = "%$search%";
    $stmt->bindParam(":search", $searchParam);
}

if ($status !== 'tous') {
    $stmt->bindParam(":status", $status);
}

$stmt->execute();
$ordonnances = $stmt->fetchAll(PDO::FETCH_ASSOC);

include("../header.php");
?>

<main id="main" class="main">
    <div class="pagetitle pt-5">
        <h1>Ordonnances à Traiter</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../dashboard-pharmacien.php">Accueil</a></li>
                <li class="breadcrumb-item active">Ordonnances à Traiter</li>
            </ol>
        </nav>
    </div>

    <section class="section">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row mb-3 mt-3">
                            <div class="col-md-8">
                                <form action="" method="GET" class="d-flex gap-2">
                                    <input type="text" name="search" class="form-control" placeholder="Rechercher..." value="<?php echo htmlspecialchars($search); ?>">
                                    <select name="status" class="form-select" style="width: auto;">
                                        <option value="en_attente" <?php echo $status === 'en_attente' ? 'selected' : ''; ?>>En attente</option>
                                        <option value="traitee" <?php echo $status === 'traitee' ? 'selected' : ''; ?>>Traitée</option>
                                        <option value="annulee" <?php echo $status === 'annulee' ? 'selected' : ''; ?>>Annulée</option>
                                        <option value="tous" <?php echo $status === 'tous' ? 'selected' : ''; ?>>Tous</option>
                                    </select>
                                    <button type="submit" class="btn btn-primary">Rechercher</button>
                                </form>
                            </div>
                        </div>

                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Patient</th>
                                    <th>Médecin</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($ordonnances)): ?>
                                <tr>
                                    <td colspan="5" class="text-center">Aucune ordonnance trouvée</td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($ordonnances as $ordonnance): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y H:i', strtotime($ordonnance['date_creation'])); ?></td>
                                        <td><?php echo htmlspecialchars($ordonnance['patient_prenom'] . ' ' . $ordonnance['patient_nom']); ?></td>
                                        <td><?php echo htmlspecialchars($ordonnance['medecin_prenom'] . ' ' . $ordonnance['medecin_nom']); ?></td>
                                        <td>
                                            <?php
                                            $statusClass = '';
                                            $statusText = '';
                                            switch($ordonnance['status']) {
                                                case 'en_attente':
                                                    $statusClass = 'badge bg-warning';
                                                    $statusText = 'En attente';
                                                    break;
                                                case 'traitee':
                                                    $statusClass = 'badge bg-success';
                                                    $statusText = 'Traitée';
                                                    break;
                                                case 'annulee':
                                                    $statusClass = 'badge bg-danger';
                                                    $statusText = 'Annulée';
                                                    break;
                                            }
                                            ?>
                                            <span class="<?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                                        </td>
                                        <td>
                                            <a href="../view_ordonnance.php?id=<?php echo $ordonnance['id']; ?>" class="btn btn-primary btn-sm">Voir</a>
                                            <?php if ($ordonnance['status'] === 'en_attente'): ?>
                                            <button onclick="traiterOrdonnance(<?php echo $ordonnance['id']; ?>)" class="btn btn-success btn-sm">Traiter</button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- Ajouter SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
function traiterOrdonnance(id) {
    Swal.fire({
        title: 'Confirmation',
        text: 'Voulez-vous marquer cette ordonnance comme traitée ?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#dc3545',
        confirmButtonText: 'Oui, traiter',
        cancelButtonText: 'Annuler',
        background: '#fff',
        customClass: {
            confirmButton: 'btn btn-success',
            cancelButton: 'btn btn-danger'
        },
        buttonsStyling: true
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading state
            Swal.fire({
                title: 'Traitement en cours...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Send AJAX request to update status
            fetch('../ajax/update_ordonnance_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'id=' + id + '&status=traitee'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: 'Succès !',
                        text: 'L\'ordonnance a été marquée comme traitée',
                        icon: 'success',
                        confirmButtonColor: '#28a745',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire({
                        title: 'Erreur',
                        text: 'Une erreur est survenue lors de la mise à jour du statut',
                        icon: 'error',
                        confirmButtonColor: '#dc3545',
                        confirmButtonText: 'OK'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    title: 'Erreur',
                    text: 'Une erreur est survenue lors de la mise à jour du statut',
                    icon: 'error',
                    confirmButtonColor: '#dc3545',
                    confirmButtonText: 'OK'
                });
            });
        }
    });
}
</script>

<?php include("../footer.php"); ?>
</main>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function traiterOrdonnance(id) {
    Swal.fire({
        title: 'Confirmation',
        text: 'Voulez-vous marquer cette ordonnance comme traitée ?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#dc3545',
        confirmButtonText: 'Oui, traiter',
        cancelButtonText: 'Annuler',
        background: '#fff',
        customClass: {
            confirmButton: 'btn btn-success',
            cancelButton: 'btn btn-danger'
        },
        buttonsStyling: true
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Traitement en cours...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            fetch('../ajax/update_ordonnance_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'id=' + id + '&status=traitee'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: 'Succès !',
                        text: 'L\'ordonnance a été marquée comme traitée',
                        icon: 'success',
                        confirmButtonColor: '#28a745',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire({
                        title: 'Erreur',
                        text: 'Une erreur est survenue',
                        icon: 'error',
                        confirmButtonColor: '#dc3545',
                        confirmButtonText: 'OK'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    title: 'Erreur',
                    text: 'Une erreur est survenue',
                    icon: 'error',
                    confirmButtonColor: '#dc3545',
                    confirmButtonText: 'OK'
                });
            });
        }
    });
}
</script>

<?php include("../footer.php"); ?>