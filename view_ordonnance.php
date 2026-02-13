<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include("db.php");

if (!isset($_SESSION["user_id"]) || !isset($_GET['id'])) {
    header("Location: pages-login.php");
    exit();
}

// Récupérer les détails de l'ordonnance
$stmt = $conn->prepare("
    SELECT o.*, 
           p.nom as patient_nom, p.prenom as patient_prenom,
           p.date_naissance, p.telephone, p.adresse,
           u.firstname as medecin_prenom, u.lastname as medecin_nom
    FROM ordonnances o
    JOIN patients p ON o.patient_id = p.id
    JOIN users u ON o.medecin_id = u.id
    WHERE o.id = :ordonnance_id
");
$stmt->bindParam(":ordonnance_id", $_GET['id']);
$stmt->execute();
$ordonnance = $stmt->fetch(PDO::FETCH_ASSOC);

// Vérifier que l'utilisateur a le droit de voir cette ordonnance
if ($_SESSION['role'] === 'medecin' && $ordonnance['medecin_id'] != $_SESSION['user_id']) {
    header("Location: dashboard-medecin.php");
    exit();
}

// Récupérer les prescriptions
$stmt = $conn->prepare("
    SELECT p.*, m.nom as medicament_nom, m.description as medicament_description
    FROM prescriptions p
    JOIN medicaments m ON p.medicament_id = m.id
    WHERE p.ordonnance_id = :ordonnance_id
");
$stmt->bindParam(":ordonnance_id", $_GET['id']);
$stmt->execute();
$prescriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);

include("header.php");
?>

<main id="main" class="main">
    <div class="pagetitle pt-5">
        <h1>Détails de l'Ordonnance</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="<?php echo $_SESSION['role'] === 'medecin' ? 'dashboard-medecin.php' : 'dashboard-pharmacien.php'; ?>">Accueil</a>
                </li>
                <li class="breadcrumb-item">
                    <a href="users-profile.php">Profil</a>
                </li>
                <li class="breadcrumb-item active">Ordonnance</li>
            </ol>
        </nav>
    </div>

    <section class="section">
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Informations de l'Ordonnance</h5>
                        
                        <!-- Informations du Patient -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h6 class="text-primary">Patient</h6>
                                <p><strong>Nom:</strong> <?php echo htmlspecialchars($ordonnance['patient_prenom'] . ' ' . $ordonnance['patient_nom']); ?></p>
                                <p><strong>Date de naissance:</strong> <?php echo date('d/m/Y', strtotime($ordonnance['date_naissance'])); ?></p>
                                <p><strong>Téléphone:</strong> <?php echo htmlspecialchars($ordonnance['telephone']); ?></p>
                                <p><strong>Adresse:</strong> <?php echo htmlspecialchars($ordonnance['adresse']); ?></p>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-primary">Médecin</h6>
                                <p><strong>Nom:</strong> <?php echo htmlspecialchars($ordonnance['medecin_prenom'] . ' ' . $ordonnance['medecin_nom']); ?></p>
                                <p><strong>Date de création:</strong> <?php echo date('d/m/Y H:i', strtotime($ordonnance['date_creation'])); ?></p>
                                <p><strong>Statut:</strong> 
                                    <span class="badge <?php 
                                        echo $ordonnance['status'] === 'traitee' ? 'bg-success' : 
                                            ($ordonnance['status'] === 'en_attente' ? 'bg-warning' : 'bg-danger'); 
                                    ?>">
                                        <?php echo ucfirst($ordonnance['status']); ?>
                                    </span>
                                </p>
                            </div>
                        </div>

                        <!-- Liste des Médicaments -->
                        <h5 class="card-title">Médicaments Prescrits</h5>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Médicament</th>
                                        <th>Description</th>
                                        <th>Posologie</th>
                                        <th>Durée</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($prescriptions as $prescription): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($prescription['medicament_nom']); ?></td>
                                            <td><?php echo htmlspecialchars($prescription['medicament_description']); ?></td>
                                            <td><?php echo htmlspecialchars($prescription['posologie']); ?></td>
                                            <td><?php echo htmlspecialchars($prescription['duree']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <?php if ($ordonnance['notes']): ?>
                            <div class="mt-4">
                                <h6 class="text-primary">Notes</h6>
                                <p><?php echo nl2br(htmlspecialchars($ordonnance['notes'])); ?></p>
                            </div>
                        <?php endif; ?>

                        <!-- Boutons d'action -->
                        <div class="mt-4">
                            <button class="btn btn-secondary" onclick="window.history.back();">
                                <i class="bi bi-arrow-left"></i> Retour
                            </button>
                            <?php if ($_SESSION['role'] === 'pharmacien' && $ordonnance['status'] === 'en_attente'): ?>
                                <button onclick="confirmerTraitement(<?php echo $ordonnance['id']; ?>)" 
                                   class="btn btn-primary">
                                    <i class="bi bi-check-circle"></i> Traiter l'ordonnance
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<!-- Ajouter SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
function confirmerTraitement(ordonnanceId) {
    Swal.fire({
        title: 'Confirmation',
        text: 'Voulez-vous vraiment traiter cette ordonnance ?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Oui, traiter',
        cancelButtonText: 'Annuler'
    }).then((result) => {
        if (result.isConfirmed) {
            // Afficher un indicateur de chargement
            Swal.fire({
                title: 'Traitement en cours...',
                text: 'Veuillez patienter',
                allowOutsideClick: false,
                showConfirmButton: false,
                willOpen: () => {
                    Swal.showLoading();
                }
            });

            // Envoyer la requête AJAX
            fetch('pharmacien/traiter_ordonnance.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ ordonnance_id: ordonnanceId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: 'Succès !',
                        text: 'L\'ordonnance a été traitée avec succès',
                        icon: 'success',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        window.location.href = 'pharmacien/ordonnances_a_traiter.php';
                    });
                } else {
                    Swal.fire({
                        title: 'Erreur',
                        text: 'Une erreur est survenue lors du traitement de l\'ordonnance',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                }
            })
            .catch(error => {
                Swal.fire({
                    title: 'Erreur',
                    text: 'Une erreur est survenue lors du traitement de l\'ordonnance',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            });
        }
    });
}
</script>

<?php include("footer.php"); ?>
