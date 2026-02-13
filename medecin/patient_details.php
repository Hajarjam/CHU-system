<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include("../db.php");

// Vérification de l'authentification et du rôle
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "medecin") {
    header("Location: ../pages-login.php");
    exit();
}

// Récupération des informations du patient
if (!isset($_GET['id'])) {
    header("Location: patients.php");
    exit();
}

$patientId = $_GET['id'];
$stmt = $conn->prepare("SELECT * FROM patients WHERE id = :id AND medecin_id = :medecin_id");
$stmt->bindParam(":id", $patientId);
$stmt->bindParam(":medecin_id", $_SESSION["user_id"]);
$stmt->execute();
$patient = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$patient) {
    header("Location: patients.php");
    exit();
}

// Récupération des ordonnances du patient
$stmt = $conn->prepare("SELECT * FROM ordonnances WHERE patient_id = :patient_id ORDER BY date_creation DESC");
$stmt->bindParam(":patient_id", $patientId);
$stmt->execute();
$ordonnances = $stmt->fetchAll(PDO::FETCH_ASSOC);

include("../header.php");
?>

<main id="main" class="main">
    <div class="pagetitle pt-5">
        <h1>Détails du Patient</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../dashboard-medecin.php">Accueil</a></li>
                <li class="breadcrumb-item"><a href="patients.php">Patients</a></li>
                <li class="breadcrumb-item active">Détails du Patient</li>
            </ol>
        </nav>
    </div>

    <section class="section profile">
        <div class="row">
            <div class="col-xl-4">
                <div class="card">
                    <div class="card-body profile-card pt-4 d-flex flex-column align-items-center">
                        <h2><?php echo htmlspecialchars($patient['prenom'] . ' ' . $patient['nom']); ?></h2>
                        <h3>Patient</h3>
                    </div>
                </div>
            </div>

            <div class="col-xl-8">
                <div class="card">
                    <div class="card-body pt-3">
                        <h5 class="card-title">Informations du Patient</h5>
                        <div class="row mb-3">
                            <div class="col-lg-3 col-md-4 label">Nom Complet</div>
                            <div class="col-lg-9 col-md-8"><?php echo htmlspecialchars($patient['prenom'] . ' ' . $patient['nom']); ?></div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-lg-3 col-md-4 label">Date de Naissance</div>
                            <div class="col-lg-9 col-md-8"><?php echo htmlspecialchars($patient['date_naissance']); ?></div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-lg-3 col-md-4 label">Téléphone</div>
                            <div class="col-lg-9 col-md-8"><?php echo htmlspecialchars($patient['telephone']); ?></div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-lg-3 col-md-4 label">Email</div>
                            <div class="col-lg-9 col-md-8"><?php echo htmlspecialchars($patient['email']); ?></div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-lg-3 col-md-4 label">Adresse</div>
                            <div class="col-lg-9 col-md-8"><?php echo htmlspecialchars($patient['adresse']); ?></div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body pt-3">
                        <h5 class="card-title">Historique des Ordonnances</h5>
                        <button type="button" class="btn btn-primary mb-3" onclick="window.location.href='create_ordonnance.php?patient_id=<?php echo $patientId; ?>'">
                            Nouvelle Ordonnance
                        </button>
                        
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ordonnances as $ordonnance): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y H:i', strtotime($ordonnance['date_creation'])); ?></td>
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
                                        <button class="btn btn-primary btn-sm" onclick="window.location.href='../view_ordonnance.php?id=<?php echo $ordonnance['id']; ?>'">Voir</button>
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

<?php include("../footer.php"); ?>
