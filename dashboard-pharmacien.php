<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include("db.php");

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "pharmacien") {
    header("Location: pages-login.php");
    exit();
}

// Récupérer le nombre d'ordonnances en attente
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM ordonnances WHERE status = 'en_attente'");
$stmt->execute();
$ordonnances_attente = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Récupérer le nombre total de médicaments
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM medicaments");
$stmt->execute();
$total_medicaments = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Récupérer le nombre de médicaments en stock faible (moins de 10 unités)
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM medicaments WHERE stock < 10");
$stmt->execute();
$stock_faible = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Récupérer les 5 dernières ordonnances traitées
$stmt = $conn->prepare("SELECT o.*, p.nom as patient_nom, p.prenom as patient_prenom, 
                       u.firstname as medecin_prenom, u.lastname as medecin_nom 
                       FROM ordonnances o 
                       JOIN patients p ON o.patient_id = p.id 
                       JOIN users u ON o.medecin_id = u.id 
                       WHERE o.status = 'traitee' 
                       ORDER BY o.date_creation DESC LIMIT 5");
$stmt->execute();
$dernieres_ordonnances = $stmt->fetchAll(PDO::FETCH_ASSOC);

include("header.php");
?>

<main id="main" class="main">
    <div class="pagetitle pt-5">
        <h1>Tableau de bord Pharmacien</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard-pharmacien.php">Accueil</a></li>
                <li class="breadcrumb-item active">Tableau de bord</li>
            </ol>
        </nav>
    </div>

    <section class="section dashboard">
        <div class="row">
            <!-- Cartes de statistiques -->
            <div class="col-lg-12">
                <div class="row">
                    <!-- Carte Ordonnances en attente -->
                    <div class="col-xxl-4 col-md-6">
                        <div class="card info-card sales-card">
                            <div class="card-body">
                                <h5 class="card-title">Ordonnances en attente</h5>
                                <div class="d-flex align-items-center">
                                    <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                                        <i class="bi bi-hourglass-split"></i>
                                    </div>
                                    <div class="ps-3">
                                        <h6><?php echo $ordonnances_attente; ?> ordonnances</h6>
                                        <a href="pharmacien/ordonnances_a_traiter.php" class="text-primary small pt-1">Voir les ordonnances</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Carte Total Médicaments -->
                    <div class="col-xxl-4 col-md-6">
                        <div class="card info-card customers-card">
                            <div class="card-body">
                                <h5 class="card-title">Total Médicaments</h5>
                                <div class="d-flex align-items-center">
                                    <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                                        <i class="bi bi-capsule"></i>
                                    </div>
                                    <div class="ps-3">
                                        <h6><?php echo $total_medicaments; ?> médicaments</h6>
                                        <a href="pharmacien/medicaments.php" class="text-primary small pt-1">Gérer le stock</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Carte Stock Faible -->
                    <div class="col-xxl-4 col-md-6">
                        <div class="card info-card revenue-card">
                            <div class="card-body">
                                <h5 class="card-title">Stock Faible <span class="text-danger">| Attention</span></h5>
                                <div class="d-flex align-items-center">
                                    <div class="card-icon rounded-circle d-flex align-items-center justify-content-center bg-danger">
                                        <i class="bi bi-exclamation-triangle text-white"></i>
                                    </div>
                                    <div class="ps-3">
                                        <h6><?php echo $stock_faible; ?> médicaments</h6>
                                        <span class="text-danger small pt-1">Stock inférieur à 10 unités</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dernières Ordonnances Traitées -->
            <div class="col-12">
                <div class="card recent-sales overflow-auto">
                    <div class="card-body">
                        <h5 class="card-title">Dernières Ordonnances Traitées</h5>
                        <table class="table table-borderless datatable">
                            <thead>
                                <tr>
                                    <th scope="col">Date</th>
                                    <th scope="col">Patient</th>
                                    <th scope="col">Médecin</th>
                                    <th scope="col">Statut</th>
                                    <th scope="col">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($dernieres_ordonnances as $ordonnance): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y H:i', strtotime($ordonnance['date_creation'])); ?></td>
                                    <td><?php echo htmlspecialchars($ordonnance['patient_prenom'] . ' ' . $ordonnance['patient_nom']); ?></td>
                                    <td>Dr. <?php echo htmlspecialchars($ordonnance['medecin_prenom'] . ' ' . $ordonnance['medecin_nom']); ?></td>
                                    <td><span class="badge bg-success">Traitée</span></td>
                                    <td>
                                        <a href="view_ordonnance.php?id=<?php echo $ordonnance['id']; ?>" class="btn btn-primary btn-sm">
                                            <i class="bi bi-eye"></i> Voir
                                        </a>
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
