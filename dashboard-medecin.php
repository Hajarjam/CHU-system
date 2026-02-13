<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include("db.php");

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "medecin") {
    header("Location: pages-login.php");
    exit();
}

// Récupérer les statistiques
$medecin_id = $_SESSION["user_id"];

// Nombre total de patients
$stmt = $conn->prepare("SELECT COUNT(*) as total_patients FROM patients WHERE medecin_id = ?");
$stmt->execute([$medecin_id]);
$total_patients = $stmt->fetch()['total_patients'];

// Nombre total d'ordonnances
$stmt = $conn->prepare("SELECT COUNT(*) as total_ordonnances FROM ordonnances WHERE medecin_id = ?");
$stmt->execute([$medecin_id]);
$total_ordonnances = $stmt->fetch()['total_ordonnances'];

// Ordonnances du mois en cours
$stmt = $conn->prepare("SELECT COUNT(*) as ordonnances_mois FROM ordonnances WHERE medecin_id = ? AND MONTH(date_creation) = MONTH(CURRENT_DATE()) AND YEAR(date_creation) = YEAR(CURRENT_DATE())");
$stmt->execute([$medecin_id]);
$ordonnances_mois = $stmt->fetch()['ordonnances_mois'];

// Nouveaux patients ce mois
$stmt = $conn->prepare("SELECT COUNT(*) as nouveaux_patients FROM patients WHERE medecin_id = ? AND MONTH(date_naissance) = MONTH(CURRENT_DATE()) AND YEAR(date_naissance) = YEAR(CURRENT_DATE())");
$stmt->execute([$medecin_id]);
$nouveaux_patients = $stmt->fetch()['nouveaux_patients'];

// Dernières ordonnances
$stmt = $conn->prepare("
    SELECT o.id, o.date_creation, p.nom, p.prenom, o.status
    FROM ordonnances o
    JOIN patients p ON o.patient_id = p.id
    WHERE o.medecin_id = ?
    ORDER BY o.date_creation DESC
    LIMIT 5
");
$stmt->execute([$medecin_id]);
$dernieres_ordonnances = $stmt->fetchAll();

include("header.php");
?>

<main id="main" class="main">
    <div class="pagetitle pt-5">
        <h1>Tableau de bord Médecin</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard-medecin.php">Accueil</a></li>
                <li class="breadcrumb-item active">Tableau de bord</li>
            </ol>
        </nav>
    </div>

    <section class="section dashboard">
        <div class="row">
            <!-- Statistiques -->
            <div class="col-lg-12">
                <div class="row">
                    <!-- Total Patients -->
                    <div class="col-xxl-3 col-md-6">
                        <div class="card info-card sales-card">
                            <div class="card-body">
                                <h5 class="card-title">Total Patients</h5>
                                <div class="d-flex align-items-center">
                                    <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                                        <i class="bi bi-people"></i>
                                    </div>
                                    <div class="ps-3">
                                        <h6><?php echo $total_patients; ?></h6>
                                        <a href="medecin/patients.php" class="text-primary small pt-1">Voir tous les patients</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Total Ordonnances -->
                    <div class="col-xxl-3 col-md-6">
                        <div class="card info-card revenue-card">
                            <div class="card-body">
                                <h5 class="card-title">Total Ordonnances</h5>
                                <div class="d-flex align-items-center">
                                    <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                                        <i class="bi bi-file-text"></i>
                                    </div>
                                    <div class="ps-3">
                                        <h6><?php echo $total_ordonnances; ?></h6>
                                        <span class="text-muted small pt-1"><?php echo $ordonnances_mois; ?> ce mois-ci</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Nouveaux Patients -->
                    <div class="col-xxl-3 col-md-6">
                        <div class="card info-card customers-card">
                            <div class="card-body">
                                <h5 class="card-title">Nouveaux Patients</h5>
                                <div class="d-flex align-items-center">
                                    <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                                        <i class="bi bi-person-plus"></i>
                                    </div>
                                    <div class="ps-3">
                                        <h6><?php echo $nouveaux_patients; ?></h6>
                                        <span class="text-muted small pt-1">ce mois-ci</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Actions Rapides -->
                    <div class="col-xxl-3 col-md-6">
                        <div class="card info-card sales-card">
                            <div class="card-body">
                                <h5 class="card-title">Actions Rapides</h5>
                                <div class="d-flex align-items-center">
                                    <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                                        <i class="bi bi-plus-circle"></i>
                                    </div>
                                    <div class="ps-3">
                                        <a href="medecin/create_ordonnance.php" class="btn btn-primary btn-sm">Nouvelle Ordonnance</a>
                                        <a href="medecin/patients.php" class="btn btn-outline-primary btn-sm mt-2">Nouveau Patient</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dernières Ordonnances -->
            <div class="col-12">
                <div class="card recent-sales overflow-auto">
                    <div class="card-body">
                        <h5 class="card-title">Dernières Ordonnances</h5>
                        <table class="table table-borderless">
                            <thead>
                                <tr>
                                    <th scope="col">#</th>
                                    <th scope="col">Patient</th>
                                    <th scope="col">Date</th>
                                    <th scope="col">Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($dernieres_ordonnances as $ordonnance): ?>
                                    <tr>
                                        <th scope="row"><a href="#">#<?php echo $ordonnance['id']; ?></a></th>
                                        <td><?php echo htmlspecialchars($ordonnance['prenom'] . ' ' . $ordonnance['nom']); ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($ordonnance['date_creation'])); ?></td>
                                        <td>
                                            <span class="badge <?php 
                                                echo $ordonnance['status'] === 'traitee' ? 'bg-success' : 
                                                    ($ordonnance['status'] === 'en_attente' ? 'bg-warning' : 'bg-danger'); 
                                            ?>">
                                                <?php echo $ordonnance['status']; ?>
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
    </section>
</main>

<?php include("footer.php"); ?>
