<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit();
}

$fullname = $_SESSION["firstname"] . ' ' . $_SESSION["lastname"];
$role = $_SESSION["role"];

// Déterminer la page active
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">

  <title>CHU</title>

  <!-- Favicons -->
  <link href="/fet/assets/img/logo.svg" rel="icon" type="image/svg+xml">
  <link href="/fet/assets/img/logo.svg" rel="apple-touch-icon">

  <!-- Google Fonts -->
  <link href="https://fonts.gstatic.com" rel="preconnect">
  <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i|Nunito:300,300i,400,400i,600,600i,700,700i|Poppins:300,300i,400,400i,500,500i,600,600i,700,700i" rel="stylesheet">

  <!-- Vendor CSS Files -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
  <link href="/fet/assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="/fet/assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="/fet/assets/vendor/boxicons/css/boxicons.min.css" rel="stylesheet">
  <link href="/fet/assets/vendor/quill/quill.snow.css" rel="stylesheet">
  <link href="/fet/assets/vendor/quill/quill.bubble.css" rel="stylesheet">
  <link href="/fet/assets/vendor/remixicon/remixicon.css" rel="stylesheet">
  <link href="/fet/assets/vendor/simple-datatables/style.css" rel="stylesheet">

  <!-- Template Main CSS File -->
  <link href="/fet/assets/css/style.css" rel="stylesheet">
  
  <!-- SweetAlert2 -->
  <link href="https://cdn.jsdelivr.net/npm/@sweetalert2/theme-bootstrap-4/bootstrap-4.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <style>
    .sidebar-nav .nav-link:not(.collapsed) {
      background: rgba(0, 0, 0, 0.1);
    }
    .sidebar-nav .nav-content {
      padding-left: 1rem;
    }
    .sidebar-nav .nav-content a {
      display: flex;
      align-items: center;
      padding: 10px 0;
      color: #012970;
      transition: 0.3s;
    }
    .sidebar-nav .nav-content a:hover {
      color: #4154f1;
    }
    .sidebar-nav .nav-content a i {
      font-size: 6px;
      margin-right: 8px;
      line-height: 0;
      border-radius: 50%;
    }
    
    @media (min-width: 1200px) {
      .toggle-sidebar #main,
      .toggle-sidebar #footer {
        margin-left: 0;
      }
      .toggle-sidebar .sidebar {
        left: -300px;
      }
    }
    
    .toggle-sidebar-btn {
      cursor: pointer;
      font-size: 24px;
      transition: transform 0.3s;
    }
    .toggle-sidebar .toggle-sidebar-btn {
      transform: rotate(180deg);
    }

    .nav-profile {
      padding: 10px !important;
      cursor: pointer;
    }
    
    .dropdown-menu-arrow.profile {
      padding: 10px 0;
      min-width: 200px;
    }
    
    .dropdown-menu-arrow.profile .dropdown-header {
      text-align: center;
      padding: 5px 20px;
    }
    
    .dropdown-menu-arrow.profile .dropdown-header h6 {
      margin-bottom: 0;
      font-size: 16px;
      color: #444444;
    }
    
    .dropdown-menu-arrow.profile .dropdown-header span {
      font-size: 14px;
      color: #666666;
    }
    
    .dropdown-menu-arrow.profile .dropdown-item {
      padding: 10px 20px;
      transition: all 0.3s ease;
    }
    
    .dropdown-menu-arrow.profile .dropdown-item i {
      margin-right: 10px;
      font-size: 18px;
    }
    
    .dropdown-menu-arrow.profile .dropdown-item:hover {
      background-color: #f6f9ff;
    }
    
    .dropdown-menu-arrow.profile .dropdown-divider {
      margin: 5px 0;
    }
  </style>
</head>

<body>
  <!-- ======= Header ======= -->
  <header id="header" class="header fixed-top d-flex align-items-center">
    <div class="d-flex align-items-center justify-content-between">
      <a href="/fet/<?php echo $_SESSION['role'] === 'medecin' ? 'dashboard-medecin.php' : 'dashboard-pharmacien.php'; ?>" class="logo d-flex align-items-center">
        <img class="hed" src="/fet/assets/img/logo.svg" alt="CHU Logo">
      </a>
      <i class="bi bi-list toggle-sidebar-btn"></i>
    </div>

    <nav class="header-nav ms-auto">
      <ul class="d-flex align-items-center">
        <li class="nav-item dropdown">
          <a class="nav-link nav-profile d-flex align-items-center pe-0" href="#" data-bs-toggle="dropdown">
            <span class="d-none d-md-block dropdown-toggle ps-2"><?php echo htmlspecialchars($_SESSION['firstname'] . ' ' . $_SESSION['lastname']); ?></span>
          </a>

          <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow profile">
            <li class="dropdown-header">
              <h6><?php echo htmlspecialchars($_SESSION['firstname'] . ' ' . $_SESSION['lastname']); ?></h6>
              <span><?php echo ucfirst($_SESSION['role']); ?></span>
            </li>

            <li><hr class="dropdown-divider"></li>

            <li>
              <a class="dropdown-item d-flex align-items-center" href="/fet/users-profile.php">
                <i class="bi bi-person"></i>
                <span>Mon Profil</span>
              </a>
            </li>

            <li><hr class="dropdown-divider"></li>

            <li>
              <a class="dropdown-item d-flex align-items-center" href="/fet/logout.php">
                <i class="bi bi-box-arrow-right"></i>
                <span>Déconnexion</span>
              </a>
            </li>
          </ul>
        </li>
      </ul>
    </nav>
  </header>

  <!-- ======= Sidebar ======= -->
  <aside id="sidebar" class="sidebar">
    <ul class="sidebar-nav" id="sidebar-nav">
      <?php if ($_SESSION['role'] === 'admin'): ?>
        <!-- Menu Admin -->
        <li class="nav-item">
          <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'gestion_utilisateurs.php' ? '' : 'collapsed'; ?>" href="/fet/admin/gestion_utilisateurs.php">
            <i class="bi bi-people"></i>
            <span>Gestion des Utilisateurs</span>
          </a>
        </li>
      <?php endif; ?>
      <?php if ($_SESSION['role'] === 'medecin'): ?>
        <!-- Menu Médecin -->
        <li class="nav-item">
          <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'dashboard-medecin.php' ? '' : 'collapsed'; ?>" href="/fet/dashboard-medecin.php">
            <i class="bi bi-grid"></i>
            <span>Tableau de bord</span>
          </a>
        </li>

        <li class="nav-item">
          <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], '/medecin/patients.php') !== false ? '' : 'collapsed'; ?>" data-bs-target="#patients-nav" data-bs-toggle="collapse" href="#">
            <i class="bi bi-people"></i>
            <span>Patients</span>
            <i class="bi bi-chevron-down ms-auto"></i>
          </a>
          <ul id="patients-nav" class="nav-content collapse <?php echo strpos($_SERVER['PHP_SELF'], '/medecin/patients.php') !== false ? 'show' : ''; ?>" data-bs-parent="#sidebar-nav">
            <li>
              <a href="/fet/medecin/patients.php" <?php echo strpos($_SERVER['PHP_SELF'], '/medecin/patients.php') !== false ? 'class="active"' : ''; ?>>
                <i class="bi bi-circle"></i>
                <span>Liste des Patients</span>
              </a>
            </li>
          </ul>
        </li>

        <li class="nav-item">
          <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], '/medecin/ordonnances.php') !== false ? '' : 'collapsed'; ?>" data-bs-target="#ordonnances-nav" data-bs-toggle="collapse" href="#">
            <i class="bi bi-file-text"></i>
            <span>Ordonnances</span>
            <i class="bi bi-chevron-down ms-auto"></i>
          </a>
          <ul id="ordonnances-nav" class="nav-content collapse <?php echo strpos($_SERVER['PHP_SELF'], '/medecin/ordonnances.php') !== false ? 'show' : ''; ?>" data-bs-parent="#sidebar-nav">
            <li>
              <a href="/fet/medecin/ordonnances.php" <?php echo strpos($_SERVER['PHP_SELF'], '/medecin/ordonnances.php') !== false ? 'class="active"' : ''; ?>>
                <i class="bi bi-circle"></i>
                <span>Gérer les Ordonnances</span>
              </a>
            </li>
          </ul>
        </li>

      <?php else: ?>
        <!-- Menu Pharmacien -->
        <li class="nav-item">
          <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'dashboard-pharmacien.php' ? '' : 'collapsed'; ?>" href="/fet/dashboard-pharmacien.php">
            <i class="bi bi-grid"></i>
            <span>Tableau de bord</span>
          </a>
        </li>

        <li class="nav-item">
          <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], '/pharmacien/medicaments.php') !== false ? '' : 'collapsed'; ?>" data-bs-target="#medicaments-nav" data-bs-toggle="collapse" href="#">
            <i class="bi bi-capsule"></i>
            <span>Médicaments</span>
            <i class="bi bi-chevron-down ms-auto"></i>
          </a>
          <ul id="medicaments-nav" class="nav-content collapse <?php echo strpos($_SERVER['PHP_SELF'], '/pharmacien/medicaments.php') !== false ? 'show' : ''; ?>" data-bs-parent="#sidebar-nav">
            <li>
              <a href="/fet/pharmacien/medicaments.php" <?php echo strpos($_SERVER['PHP_SELF'], '/pharmacien/medicaments.php') !== false ? 'class="active"' : ''; ?>>
                <i class="bi bi-circle"></i>
                <span>Stock Médicaments</span>
              </a>
            </li>
          </ul>
        </li>

        <li class="nav-item">
          <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], '/pharmacien/ordonnances') !== false ? '' : 'collapsed'; ?>" data-bs-target="#ordonnances-nav" data-bs-toggle="collapse" href="#">
            <i class="bi bi-file-text"></i>
            <span>Ordonnances</span>
            <i class="bi bi-chevron-down ms-auto"></i>
          </a>
          <ul id="ordonnances-nav" class="nav-content collapse <?php echo strpos($_SERVER['PHP_SELF'], '/pharmacien/ordonnances') !== false ? 'show' : ''; ?>" data-bs-parent="#sidebar-nav">
            <li>
              <a href="/fet/pharmacien/ordonnances_a_traiter.php" <?php echo basename($_SERVER['PHP_SELF']) === 'ordonnances_a_traiter.php' ? 'class="active"' : ''; ?>>
                <i class="bi bi-circle"></i>
                <span>À Traiter</span>
              </a>
            </li>
            <li>
              <a href="/fet/pharmacien/toutes_ordonnances.php" <?php echo basename($_SERVER['PHP_SELF']) === 'toutes_ordonnances.php' ? 'class="active"' : ''; ?>>
                <i class="bi bi-circle"></i>
                <span>Toutes les Ordonnances</span>
              </a>
            </li>
          </ul>
        </li>
      <?php endif; ?>

      <!-- Menu commun -->
      <li class="nav-item">
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'users-profile.php' ? '' : 'collapsed'; ?>" href="/fet/users-profile.php">
          <i class="bi bi-person"></i>
          <span>Profil</span>
        </a>
      </li>

    </ul>
  </aside>

  <!-- Vendor JS Files -->
  <script src="/fet/assets/vendor/apexcharts/apexcharts.min.js"></script>
  <script src="/fet/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="/fet/assets/vendor/chart.js/chart.umd.js"></script>
  <script src="/fet/assets/vendor/echarts/echarts.min.js"></script>
  <script src="/fet/assets/vendor/quill/quill.min.js"></script>
  <script src="/fet/assets/vendor/simple-datatables/simple-datatables.js"></script>
  <script src="/fet/assets/vendor/tinymce/tinymce.min.js"></script>
  <script src="/fet/assets/vendor/php-email-form/validate.js"></script>

  <!-- Template Main JS File -->
  <script src="/fet/assets/js/main.js"></script>
  
  <!-- Custom Sidebar JS -->
  <script src="/fet/assets/js/sidebar.js"></script>
  
  <!-- Profile Dropdown JS -->
  <script src="/fet/assets/js/profile-dropdown.js"></script>
</body>

</html>
