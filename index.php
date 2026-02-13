<?php
session_start();
include("db.php");

// Initialize login attempts if not set
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['last_attempt_time'] = time();
}

// Check if user is locked out
$lockout_time = 15 * 60; // 15 minutes lockout
if ($_SESSION['login_attempts'] >= 5 && (time() - $_SESSION['last_attempt_time']) < $lockout_time) {
    $time_left = $lockout_time - (time() - $_SESSION['last_attempt_time']);
    die("Trop de tentatives de connexion. Veuillez réessayer dans " . ceil($time_left/60) . " minutes.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Reset attempts if lockout period has passed
    if ((time() - $_SESSION['last_attempt_time']) > $lockout_time) {
        $_SESSION['login_attempts'] = 0;
    }

    // Validate and sanitize input
    $username = filter_var(trim($_POST["username"]), FILTER_SANITIZE_STRING);
    $password = trim($_POST["password"]);

    if (empty($username) || empty($password)) {
        die("Veuillez remplir tous les champs.");
    }

    if (strlen($username) < 3 || strlen($username) > 50) {
        die("Le nom d'utilisateur doit contenir entre 3 et 50 caractères.");
    }

    $stmt = $conn->prepare("SELECT id, firstname, lastname, password, role FROM users WHERE username = :username");
    $stmt->bindParam(":username", $username, PDO::PARAM_STR);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        if ($password === $user["password"]) {
            // Reset login attempts
            $_SESSION['login_attempts'] = 0;
            
            // Set session variables
            $_SESSION["user_id"] = $user["id"];
            $_SESSION["firstname"] = $user["firstname"];
            $_SESSION["lastname"] = $user["lastname"];
            $_SESSION["role"] = $user["role"];
            
            // Redirect based on role
            if ($user["role"] === "admin") {
                header("Location: admin/gestion_utilisateurs.php");
            } else if ($user["role"] === "medecin") {
                header("Location: dashboard-medecin.php");
            } else if ($user["role"] === "pharmacien") {
                header("Location: dashboard-pharmacien.php");
            }
            exit();
        } else {
            // Increment failed attempts
            $_SESSION['login_attempts']++;
            $_SESSION['last_attempt_time'] = time();
            echo "Mot de passe incorrect.";
        }
    } else {
        // Increment failed attempts even for non-existent users
        $_SESSION['login_attempts']++;
        $_SESSION['last_attempt_time'] = time();
        echo "Utilisateur non trouvé.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">

  <title>CHU - Connexion</title>
  <meta content="" name="description">
  <meta content="" name="keywords">

  <!-- Favicons -->
  <link href="assets/img/logo.svg" rel="icon" type="image/svg+xml">
  <link href="assets/img/logo.svg" rel="apple-touch-icon">

  <!-- Google Fonts -->
  <link href="https://fonts.gstatic.com" rel="preconnect">
  <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i|Nunito:300,300i,400,400i,600,600i,700,700i|Poppins:300,300i,400,400i,500,500i,600,600i,700,700i" rel="stylesheet">

  <!-- Vendor CSS Files -->
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/vendor/boxicons/css/boxicons.min.css" rel="stylesheet">
  <link href="assets/vendor/quill/quill.snow.css" rel="stylesheet">
  <link href="assets/vendor/quill/quill.bubble.css" rel="stylesheet">
  <link href="assets/vendor/remixicon/remixicon.css" rel="stylesheet">
  <link href="assets/vendor/simple-datatables/style.css" rel="stylesheet">

  <!-- Template Main CSS File -->
  <link href="assets/css/style.css" rel="stylesheet">
</head>

<body>

  <main>
    <div class="container">

      <section class="section register min-vh-100 d-flex flex-column align-items-center justify-content-center py-4">
        <div class="container">
          <div class="row justify-content-center">
            <div class="col-lg-4 col-md-6 d-flex flex-column align-items-center justify-content-center">

              <div class="d-flex justify-content-center py-4">
                <a href="index.php" class="logo d-flex align-items-center w-100 h-100">
                  <img class="ind" src="assets/img/logo.svg" alt="CHU Logo" >
                </a>
              </div><!-- End Logo -->

              <div class="card mb-3">

                <div class="card-body">

                  <div class="pt-4 pb-2">
                    <h5 class="card-title text-center pb-0 fs-4">Connexion</h5>
                    <p class="text-center small">Entrez votre nom d'utilisateur et mot de passe</p>
                  </div>

                  <form class="row g-3 needs-validation" method="POST">

                    <div class="col-12">
                      <label for="username" class="form-label">Nom d'utilisateur</label>
                      <div class="input-group has-validation">
                        <span class="input-group-text" id="inputGroupPrepend">@</span>
                        <input type="text" name="username" class="form-control" id="username" required>
                        <div class="invalid-feedback">Veuillez entrer votre nom d'utilisateur.</div>
                      </div>
                    </div>

                    <div class="col-12">
                      <label for="password" class="form-label">Mot de passe</label>
                      <input type="password" name="password" class="form-control" id="password" required>
                      <div class="invalid-feedback">Veuillez entrer votre mot de passe!</div>
                    </div>
                    <div class="col-12">
                      <button class="btn btn-primary w-100" type="submit">Connexion</button>
                    </div>
                  </form>

                </div>
              </div>

            </div>
          </div>
        </div>

      </section>

    </div>
  </main><!-- End #main -->

  <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

  <!-- Vendor JS Files -->
  <script src="assets/vendor/apexcharts/apexcharts.min.js"></script>
  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="assets/vendor/chart.js/chart.umd.js"></script>
  <script src="assets/vendor/echarts/echarts.min.js"></script>
  <script src="assets/vendor/quill/quill.js"></script>
  <script src="assets/vendor/simple-datatables/simple-datatables.js"></script>
  <script src="assets/vendor/tinymce/tinymce.min.js"></script>
  <script src="assets/vendor/php-email-form/validate.js"></script>

  <!-- Template Main JS File -->
  <script src="assets/js/main.js"></script>

</body>

</html>
