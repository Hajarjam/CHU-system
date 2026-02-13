<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include("../db.php");

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "medecin") {
    header("Location: ../pages-login.php");
    exit();
}

// Traitement du formulaire
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $conn->beginTransaction();

        // Création de l'ordonnance
        $stmt = $conn->prepare("INSERT INTO ordonnances (patient_id, medecin_id, notes, status) 
                               VALUES (:patient_id, :medecin_id, :notes, 'en_attente')");
        
        $stmt->execute([
            ':patient_id' => $_POST['patient_id'],
            ':medecin_id' => $_SESSION['user_id'],
            ':notes' => $_POST['notes']
        ]);

        $ordonnance_id = $conn->lastInsertId();

        // Ajout des prescriptions
        $stmt = $conn->prepare("INSERT INTO prescriptions (ordonnance_id, medicament_id, posologie, duree) 
                               VALUES (:ordonnance_id, :medicament_id, :posologie, :duree)");

        foreach ($_POST['medicaments'] as $key => $medicament_id) {
            if (!empty($medicament_id)) {
                $stmt->execute([
                    ':ordonnance_id' => $ordonnance_id,
                    ':medicament_id' => $medicament_id,
                    ':posologie' => $_POST['posologies'][$key],
                    ':duree' => $_POST['durees'][$key]
                ]);
            }
        }

        $conn->commit();
        header("Location: patient_details.php?id=" . $_POST['patient_id'] . "&success=1");
        exit();
    } catch(PDOException $e) {
        $conn->rollBack();
        header("Location: create_ordonnance.php?patient_id=" . $_POST['patient_id'] . "&error=" . urlencode($e->getMessage()));
        exit();
    }
}

// Récupération du patient si l'ID est fourni
$patient = null;
if (isset($_GET['patient_id'])) {
    $stmt = $conn->prepare("SELECT * FROM patients WHERE id = :id AND medecin_id = :medecin_id");
    $stmt->bindParam(":id", $_GET['patient_id']);
    $stmt->bindParam(":medecin_id", $_SESSION["user_id"]);
    $stmt->execute();
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Récupération de tous les médicaments disponibles
$stmt = $conn->prepare("SELECT * FROM medicaments ORDER BY nom");
$stmt->execute();
$medicaments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupération de tous les patients du médecin si aucun patient n'est sélectionné
$patients = [];
if (!$patient) {
    $stmt = $conn->prepare("SELECT * FROM patients WHERE medecin_id = :medecin_id ORDER BY nom");
    $stmt->bindParam(":medecin_id", $_SESSION["user_id"]);
    $stmt->execute();
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

include("../header.php");
?>

<main id="main" class="main">
    <div class="pagetitle pt-5">
        <h1>Créer une Ordonnance</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../dashboard-medecin.php">Accueil</a></li>
                <li class="breadcrumb-item"><a href="patients.php">Patients</a></li>
                <li class="breadcrumb-item active">Créer une Ordonnance</li>
            </ol>
        </nav>
    </div>

    <?php if (isset($_GET['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($_GET['error']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <section class="section">
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Nouvelle Ordonnance</h5>

                        <form id="ordonnanceForm" method="POST">
                            <?php if ($patient): ?>
                                <input type="hidden" name="patient_id" value="<?php echo $patient['id']; ?>">
                                <div class="row mb-3">
                                    <label class="col-sm-2 col-form-label">Patient</label>
                                    <div class="col-sm-10">
                                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($patient['prenom'] . ' ' . $patient['nom']); ?>" readonly>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="row mb-3">
                                    <label for="patient_id" class="col-sm-2 col-form-label">Patient</label>
                                    <div class="col-sm-10">
                                        <select class="form-select" id="patient_id" name="patient_id" required>
                                            <option value="">Sélectionnez un patient</option>
                                            <?php foreach ($patients as $p): ?>
                                                <option value="<?php echo $p['id']; ?>">
                                                    <?php echo htmlspecialchars($p['prenom'] . ' ' . $p['nom']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div class="row mb-3">
                                <label class="col-sm-2 col-form-label">Notes</label>
                                <div class="col-sm-10">
                                    <textarea class="form-control" name="notes" rows="3"></textarea>
                                </div>
                            </div>

                            <div id="prescriptions">
                                <h5 class="mt-4">Prescriptions</h5>
                                <div class="prescription-item">
                                    <div class="row mb-3">
                                        <div class="col-md-4">
                                            <select class="form-select" name="medicaments[]" required>
                                                <option value="">Sélectionnez un médicament</option>
                                                <?php foreach ($medicaments as $medicament): ?>
                                                    <option value="<?php echo $medicament['id']; ?>">
                                                        <?php echo htmlspecialchars($medicament['nom']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <input type="text" class="form-control" name="posologies[]" placeholder="Posologie" required>
                                        </div>
                                        <div class="col-md-3">
                                            <input type="text" class="form-control" name="durees[]" placeholder="Durée" required>
                                        </div>
                                        <div class="col-md-1">
                                            <button type="button" class="btn btn-danger btn-sm remove-prescription">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-sm-12">
                                    <button type="button" class="btn btn-success" id="addPrescription">
                                        <i class="bi bi-plus-circle"></i> Ajouter un médicament
                                    </button>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-sm-12">
                                    <button type="submit" class="btn btn-primary">Créer l'ordonnance</button>
                                    <button type="button" class="btn btn-secondary" onclick="history.back()">Annuler</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<script>
document.getElementById('addPrescription').addEventListener('click', function() {
    const template = document.querySelector('.prescription-item').cloneNode(true);
    template.querySelector('select').value = '';
    template.querySelectorAll('input').forEach(input => input.value = '');
    document.getElementById('prescriptions').appendChild(template);
});

document.getElementById('prescriptions').addEventListener('click', function(e) {
    if (e.target.classList.contains('remove-prescription') || e.target.parentElement.classList.contains('remove-prescription')) {
        const items = document.querySelectorAll('.prescription-item');
        if (items.length > 1) {
            const button = e.target.classList.contains('remove-prescription') ? e.target : e.target.parentElement;
            button.closest('.prescription-item').remove();
        }
    }
});
</script>

<?php include("../footer.php"); ?>
