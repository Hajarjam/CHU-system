<?php
session_start();
include("../db.php");

// Vérification de l'authentification et du rôle
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "pharmacien") {
    header("Location: ../pages-login.php");
    exit();
}

// Récupération de toutes les ordonnances
$stmt = $conn->prepare("
    SELECT o.*, 
           p.nom as patient_nom, p.prenom as patient_prenom,
           u.firstname as medecin_nom, u.lastname as medecin_prenom,
           DATE_FORMAT(o.date_creation, '%d/%m/%Y') as date_formatee
    FROM ordonnances o 
    JOIN patients p ON o.patient_id = p.id 
    JOIN users u ON o.medecin_id = u.id
    ORDER BY o.date_creation DESC
");
$stmt->execute();
$ordonnances = $stmt->fetchAll(PDO::FETCH_ASSOC);

include("../header.php");
?>

<main id="main" class="main">
    <div class="pagetitle pt-5">
        <h1>Toutes les Ordonnances</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../dashboard-pharmacien.php">Accueil</a></li>
                <li class="breadcrumb-item active">Toutes les Ordonnances</li>
            </ol>
        </nav>
    </div>

    <section class="section">
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Liste de Toutes les Ordonnances</h5>

                        <table class="table datatable">
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
                                <?php foreach ($ordonnances as $ordonnance): ?>
                                <tr>
                                    <td><?php echo $ordonnance['date_formatee']; ?></td>
                                    <td><?php echo htmlspecialchars($ordonnance['patient_nom'] . ' ' . $ordonnance['patient_prenom']); ?></td>
                                    <td><?php echo htmlspecialchars("Dr. " . $ordonnance['medecin_nom'] . ' ' . $ordonnance['medecin_prenom']); ?></td>
                                    <td>
                                        <span class="badge <?php 
                                            echo $ordonnance['status'] === 'traitee' ? 'bg-success' : 
                                                ($ordonnance['status'] === 'en_attente' ? 'bg-warning' : 'bg-danger'); 
                                        ?>">
                                            <?php 
                                            $status_text = [
                                                'en_attente' => 'En attente',
                                                'traitee' => 'Traitée',
                                                'annulee' => 'Annulée'
                                            ];
                                            echo $status_text[$ordonnance['status']] ?? ucfirst($ordonnance['status']); 
                                            ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-primary btn-sm" onclick="window.location.href='../view_ordonnance.php?id=<?php echo $ordonnance['id']; ?>'">
                                            <i class="bi bi-eye"></i> Voir
                                        </button>
                                        <?php if ($ordonnance['status'] === 'en_attente'): ?>
                                        <button class="btn btn-success btn-sm" onclick="confirmerTraitement(<?php echo $ordonnance['id']; ?>)">
                                            <i class="bi bi-check-circle"></i> Traiter
                                        </button>
                                        <?php endif; ?>
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
            fetch('traiter_ordonnance.php', {
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
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        title: 'Erreur',
                        text: data.message || 'Une erreur est survenue lors du traitement de l\'ordonnance',
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

<?php include("../footer.php"); ?>
