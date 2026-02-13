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

// Récupération des patients du médecin
$stmt = $conn->prepare("SELECT * FROM patients WHERE medecin_id = :medecin_id");
$stmt->bindParam(":medecin_id", $_SESSION["user_id"]);
$stmt->execute();
$patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

include("../header.php");
?>

<main id="main" class="main">
    <div class="pagetitle pt-5">
        <h1>Gestion des Patients</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../dashboard-medecin.php">Accueil</a></li>
                <li class="breadcrumb-item active">Patients</li>
            </ol>
        </nav>
    </div>

    <section class="section">
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Liste des Patients</h5>
                        <button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addPatientModal">
                            Ajouter un Patient
                        </button>

                        <table class="table datatable">
                            <thead>
                                <tr>
                                    <th>Nom</th>
                                    <th>Prénom</th>
                                    <th>Date de Naissance</th>
                                    <th>Téléphone</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($patients as $patient): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($patient['nom']); ?></td>
                                    <td><?php echo htmlspecialchars($patient['prenom']); ?></td>
                                    <td><?php echo htmlspecialchars($patient['date_naissance']); ?></td>
                                    <td><?php echo htmlspecialchars($patient['telephone']); ?></td>
                                    <td>
                                        <button class="btn btn-primary btn-sm" onclick="viewPatient(<?php echo $patient['id']; ?>)">Voir</button>
                                        <button class="btn btn-success btn-sm" onclick="createOrdonnance(<?php echo $patient['id']; ?>)">Ordonnance</button>
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

<!-- Modal Ajout Patient -->
<div class="modal fade" id="addPatientModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ajouter un Patient</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addPatientForm" method="POST" action="add_patient.php">
                    <div class="mb-3">
                        <label for="nom" class="form-label">Nom</label>
                        <input type="text" class="form-control" id="nom" name="nom" required>
                    </div>
                    <div class="mb-3">
                        <label for="prenom" class="form-label">Prénom</label>
                        <input type="text" class="form-control" id="prenom" name="prenom" required>
                    </div>
                    <div class="mb-3">
                        <label for="date_naissance" class="form-label">Date de Naissance</label>
                        <input type="date" class="form-control" id="date_naissance" name="date_naissance" required>
                    </div>
                    <div class="mb-3">
                        <label for="telephone" class="form-label">Téléphone</label>
                        <input type="tel" class="form-control" id="telephone" name="telephone">
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email">
                    </div>
                    <div class="mb-3">
                        <label for="adresse" class="form-label">Adresse</label>
                        <textarea class="form-control" id="adresse" name="adresse" rows="3"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function viewPatient(patientId) {
    // Rediriger vers une page de détails du patient
    window.location.href = `patient_details.php?id=${patientId}`;
}

function createOrdonnance(patientId) {
    // Rediriger vers la page de création d'ordonnance avec l'ID du patient
    window.location.href = `create_ordonnance.php?patient_id=${patientId}`;
}
</script>

<?php include($_SERVER['DOCUMENT_ROOT'] . "/fet/footer.php"); ?>
