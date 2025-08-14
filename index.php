<?php
// Définir une constante pour indiquer que l'application est en cours d'exécution
define('APP_RUNNING', true);

// Démarrer la session PHP
session_start();

// Inclure le fichier de configuration de la base de données
$config = require_once __DIR__ . '/api/v1/config/database.php';

$user = null; // Variable pour stocker les données de l'utilisateur

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header('Location: /se-connecter');
    exit();
}

// Récupérer les informations de l'utilisateur depuis la BDD
try {
    $dsn = "mysql:host={$config['host']};dbname={$config['db_name']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['username'], $config['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT id, matricule, nom, prenom, service, email, statut, role FROM utilisateurs WHERE id = :id");
    $stmt->execute(['id' => $_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!$user) {
        session_destroy();
        header('Location: /se-connecter');
        exit();
    }

} catch (PDOException $e) {
    error_log("Erreur BDD (index.php template): " . $e->getMessage());
    session_destroy();
    header('Location: /se-connecter?error=db');
    exit();
}

// --- LOGIQUE DE ROUTAGE ---
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$viewFile = '';
$pageTitle = 'Accueil'; // Titre par défaut
$pageCss = '/static/css/index.css'; // CSS par défaut
$pageJs = '/static/js/index.js'; // JS par défaut
$activeNav = ''; // Classe active pour la navigation

switch ($requestUri) {
    case '/':
        $viewFile = __DIR__ . '/views/index.php';
        $pageTitle = 'Accueil';
        $activeNav = 'Accueil';
        break;
    case '/dashboard':
        $viewFile = __DIR__ . '/views/dashboard.php';
        $pageTitle = 'Tableau de bord';
        $activeNav = 'Tableau de bord';
        break;
    case '/profil':
        $viewFile = __DIR__ . '/views/profil.php';
        $pageTitle = 'Mon Profil';
        $pageCss = '/static/css/profil.css';
        $pageJs = '/static/js/profil.js';
        $activeNav = 'Mon profil';
        break;
    case '/procedures':
            $viewFile = __DIR__ . '/views/procedures.php';
            $pageTitle = 'Gestion des Procédures';
            $pageCss = '/static/css/procedures.css';
            $pageJs = '/static/js/procedures.js';
            $activeNav = 'Procédures';
            break;
    case '/annuaire':
        $viewFile = __DIR__ . '/views/annuaire.php';
        $pageTitle = 'Annuaire';
        $pageCss = '/static/css/annuaire.css';
        $pageJs = '/static/js/annuaire.js';
        $activeNav = 'Annuaire';
        break;
    case '/attentes':
        $viewFile = __DIR__ . '/views/attentes.php';
        $pageTitle = 'Comptes en Attente';
        $pageCss = '/static/css/attentes.css';
        $pageJs = '/static/js/attentes.js';
        $activeNav = 'Attentes';
        // Vérification de rôle pour l'accès à la page (sécurité supplémentaire)
        if ($user['role'] !== 'Superviseur' && $user['role'] !== 'Admin') {
            header('Location: /'); // Rediriger si accès non autorisé
            exit();
        }
        break;
    case '/signalements':
        $viewFile = __DIR__ . '/views/signalements.php';
        $pageTitle = 'Signalements';
        $pageCss = '/static/css/signalements.css';
        $pageJs = '/static/js/signalements.js';
        $activeNav = 'Signalements';
        if ($user['role'] !== 'Admin') {
            header('Location: /');
            exit();
        }
        break;
    case '/administration':
        $viewFile = __DIR__ . '/views/administration.php';
        $pageTitle = 'Administration Système';
        $pageCss = '/static/css/administration.css';
        $pageJs = '/static/js/administration.js';
        $activeNav = 'Administration';
        if ($user['role'] !== 'Admin') { // Seuls les admins pour cette page
            header('Location: /');
            exit();
        }
        break;
    case '/logout':
        session_destroy(); // Détruire la session
        setcookie('auth_session_id', '', time() - 3600, '/', $_SERVER['HTTP_HOST'], true, true); // Supprimer le cookie
        header('Location: /se-connecter'); // Rediriger vers la page de connexion
        exit();
    default:
        // Gérer les pages 404 (non trouvées)
        http_response_code(404);
        $pageTitle = 'Page non trouvée';
        $viewFile = __DIR__ . '/views/404.php'; // Créer une page 404
        break;
}

// Assurez-vous que le fichier de vue existe avant de l'inclure
if (!file_exists($viewFile)) {
    http_response_code(404);
    $pageTitle = 'Page non trouvée';
    $viewFile = __DIR__ . '/views/404.php'; // Fallback si le fichier de vue est absent
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - Intranet FDO</title>
    <link rel="stylesheet" href="/static/css/index.css"> <?php if ($pageCss && $pageCss !== '/static/css/index.css'): ?>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($pageCss); ?>"> <?php endif; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="main-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <img src="/static/img/logo.png" alt="Logo FDO" class="sidebar-logo">
                <h3>Intranet FDO</h3>
            </div>
            <nav class="sidebar-nav">
                <ul>
                    <li <?php if($activeNav === 'Accueil') echo 'class="active"'; ?>><a href="/"><i class="fas fa-home"></i> Accueil</a></li>
                    <li <?php if($activeNav === 'Tableau de bord') echo 'class="active"'; ?>><a href="/dashboard"><i class="fas fa-chart-line"></i> Tableau de bord</a></li>
                    <li <?php if($activeNav === 'Procédures') echo 'class="active"'; ?>><a href="/procedures"><i class="fas fa-file-alt"></i> Procédures</a></li>
                    <li <?php if($activeNav === 'Annuaire') echo 'class="active"'; ?>><a href="/annuaire"><i class="fas fa-address-book"></i> Annuaire</a></li>
                    <li <?php if($activeNav === 'Mon profil') echo 'class="active"'; ?>><a href="/profil"><i class="fas fa-user-circle"></i> Mon profil</a></li>
                    
                    <?php if ($user['role'] === 'Superviseur' || $user['role'] === 'Admin'): ?>
                    <li class="separator"></li>
                    <li <?php if($activeNav === 'Attentes') echo 'class="active"'; ?>><a href="/attentes"><i class="fas fa-hourglass-half"></i> Attentes</a></li>
                    <?php endif; ?>

                    <?php if ($user['role'] === 'Admin'): ?>
                    <li <?php if($activeNav === 'Signalements') echo 'class="active"'; ?>><a href="/signalements"><i class="fas fa-exclamation-triangle"></i> Signalements</a></li>
                    <li <?php if($activeNav === 'Administration') echo 'class="active"'; ?>><a href="/administration"><i class="fas fa-cogs"></i> Administration</a></li>
                    <?php endif; ?>
                    
                    <li><a href="/logout"><i class="fas fa-sign-out-alt"></i> Déconnexion</a></li>
                </ul>
            </nav>
        </aside>

        <div class="content-area">
            <header class="content-header">
                <h2>Bienvenue, <?php echo htmlspecialchars($user['prenom'] . ' ' . $user['nom']); ?> (<?php echo htmlspecialchars($user['matricule']); ?>)</h2>
                <div class="header-actions">
                    <span class="user-info"><?php echo htmlspecialchars($user['grade'] ?? $user['service']); ?></span>
                    <i class="fas fa-bell notification-icon"></i>
                </div>
            </header>

            <main class="main-content">
                <?php
                // Inclure la vue partielle en fonction de la route
                if (file_exists($viewFile)) {
                    include $viewFile;
                } else {
                    // Fallback pour le 404 si le fichier de vue n'existe pas malgré le switch
                    echo '<div style="text-align: center; color: var(--error-color); padding: 50px;">
                            <h3>Erreur 404 : Page non trouvée</h3>
                            <p>La page demandée n\'existe pas.</p>
                          </div>';
                }
                ?>
            </main>
        </div>
    </div>

    <div id="genericConfirmModal" class="modal-overlay">
        <div class="modal-content">
            <button class="close-button" id="closeGenericModalButton">&times;</button>
            <h3 id="genericModalTitle">Titre de la Demande</h3>
            <p id="genericModalMessage">Message de confirmation ou d'information.</p>
                
            <div id="genericModalInputGroup" class="input-group" style="display: none;">
                <i class="fas fa-pencil-alt icon"></i> <input type="text" id="genericModalInput" placeholder="Saisissez ici...">
            </div>
                
            <div id="genericModalActions" class="modal-actions">
                <button class="action-button primary-button" id="genericModalConfirmButton">Confirmer</button>
                <button class="action-button secondary-button" id="genericModalCancelButton">Annuler</button>
            </div>
            <div id="genericModalFeedback" class="modal-message"></div>
        </div>
    </div>

    <script src="/static/js/index.js"></script> <?php if ($pageJs && $pageJs !== '/static/js/index.js'): ?>
    <script src="<?php echo htmlspecialchars($pageJs); ?>"></script> <?php endif; ?>
</body>
</html>