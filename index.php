<?php
// Définir une constante pour indiquer que l'application est en cours d'exécution
define('APP_RUNNING', true);

// Démarrer la session PHP
session_start();

// Inclure le fichier de configuration de la base de données
// Ajustez le chemin si database.php est ailleurs
$config = require_once __DIR__ . '/api/v1/config/database.php';

$user = null; // Variable pour stocker les données de l'utilisateur

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    // Si l'ID utilisateur n'est pas en session, rediriger vers la page de connexion
    // Le .htaccess gère déjà une redirection si le cookie est absent, mais c'est une sécurité supplémentaire
    header('Location: /se-connecter');
    exit();
}

// Récupérer les informations de l'utilisateur depuis la BDD
try {
    $dsn = "mysql:host={$config['host']};dbname={$config['db_name']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['username'], $config['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT matricule, nom, prenom, service, email, statut, role FROM utilisateurs WHERE id = :id");
    $stmt->execute(['id' => $_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!$user) {
        // Utilisateur non trouvé en BDD malgré un ID de session. Invalider la session.
        session_destroy();
        header('Location: /se-connecter');
        exit();
    }

} catch (PDOException $e) {
    error_log("Erreur BDD (homepage): " . $e->getMessage());
    // En cas d'erreur de BDD, rediriger vers la connexion ou afficher un message d'erreur
    session_destroy(); // Détruire la session pour forcer une nouvelle connexion
    header('Location: /se-connecter?error=db'); // Peut ajouter un paramètre d'erreur
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accueil - Intranet FDO</title>
    <link rel="stylesheet" href="/static/css/index.css">
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
                    <li class="active"><a href="/"><i class="fas fa-home"></i> Accueil</a></li>
                    <li><a href="/dashboard"><i class="fas fa-chart-line"></i> Tableau de bord</a></li>
                    <li><a href="/procedures"><i class="fas fa-file-alt"></i> Procédures</a></li>
                    <li><a href="/profil"><i class="fas fa-user-circle"></i> Mon profil</a></li>
                    <?php if ($user['role'] === 'Admin'): ?>
                    <li><a href="/api/v1/admin/validate-accounts.php"><i class="fas fa-user-check"></i> Valider Comptes</a></li>
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
                <section class="user-overview">
                    <h3>Vos informations personnelles</h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <i class="fas fa-id-badge icon"></i>
                            <span class="label">Matricule:</span>
                            <span class="value"><?php echo htmlspecialchars($user['matricule']); ?></span>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-envelope icon"></i>
                            <span class="label">Email:</span>
                            <span class="value"><?php echo htmlspecialchars($user['email']); ?></span>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-briefcase icon"></i>
                            <span class="label">Service:</span>
                            <span class="value"><?php echo htmlspecialchars($user['service']); ?></span>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-shield-alt icon"></i>
                            <span class="label">Rôle:</span>
                            <span class="value"><?php echo htmlspecialchars($user['role']); ?></span>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-user-check icon"></i>
                            <span class="label">Statut:</span>
                            <span class="value"><?php echo htmlspecialchars($user['statut']); ?></span>
                        </div>
                    </div>
                </section>

                <section class="quick-access">
                    <h3>Accès rapide</h3>
                    <div class="access-grid">
                        <a href="/procedures/nouvelle" class="access-card">
                            <i class="fas fa-plus-circle"></i>
                            <span>Nouvelle Procédure</span>
                        </a>
                        <a href="/recherche" class="access-card">
                            <i class="fas fa-search"></i>
                            <span>Rechercher Dossier</span>
                        </a>
                        <a href="/messages" class="access-card">
                            <i class="fas fa-comments"></i>
                            <span>Messages</span>
                        </a>
                        <a href="/parametres" class="access-card">
                            <i class="fas fa-cog"></i>
                            <span>Paramètres</span>
                        </a>
                    </div>
                </section>

                </main>
        </div>
    </div>

    <script src="/static/js/index.js"></script>
</body>
</html>