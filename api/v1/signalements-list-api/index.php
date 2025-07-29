<?php
define('APP_RUNNING', true);
header('Content-Type: application/json');
session_start();

$config = require_once __DIR__ . '/../config/database.php';

function sendJsonResponse($success, $message, $data = []) {
    echo json_encode(['success' => $success, 'message' => $message] + $data);
    exit();
}

// Vérifier l'authentification et l'autorisation (Admin ou Superviseur)
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    error_log("DEBUG Signalements List API: Échec authentification - Session ID: " . ($_SESSION['user_id'] ?? 'NULL'));
    sendJsonResponse(false, 'Non authentifié. Veuillez vous reconnecter.');
}

try {
    $dsn = "mysql:host={$config['host']};dbname={$config['db_name']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['username'], $config['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Récupérer le rôle de l'utilisateur connecté pour l'autorisation
    $stmt = $pdo->prepare("SELECT role FROM utilisateurs WHERE id = :id");
    $stmt->execute(['id' => $_SESSION['user_id']]);
    $loggedInUserRole = $stmt->fetchColumn();

    error_log("DEBUG Signalements List API: Utilisateur ID: " . $_SESSION['user_id'] . " Rôle: " . $loggedInUserRole);


    if ($loggedInUserRole !== 'Superviseur' && $loggedInUserRole !== 'Admin') {
        error_log("DEBUG Signalements List API: Accès refusé - Rôle insuffisant: " . $loggedInUserRole);
        sendJsonResponse(false, 'Accès non autorisé à la liste des signalements.');
    }

    // Filtres - Récupérer les termes de recherche bruts
    $statusFilter = isset($_GET['status']) ? trim($_GET['status']) : '';
    $reporterSearchTerm = isset($_GET['reporter']) ? trim($_GET['reporter']) : '';
    $reportedSearchTerm = isset($_GET['reported']) ? trim($_GET['reported']) : '';

    error_log("DEBUG Signalements List API: Filtres reçus - Statut: '" . $statusFilter . "', Signaleur: '" . $reporterSearchTerm . "', Signalé: '" . $reportedSearchTerm . "'");


    $sql = "
        SELECT
            s.id AS id_signalement_short,
            s.signaleur_id,
            s.signale_id,
            s.type_signalement,
            s.description,
            s.statut,
            s.date_signalement,
            u1.email AS signaleur_email,
            u1.matricule AS signaleur_matricule,
            u2.email AS signale_email,
            u2.matricule AS signale_matricule
        FROM
            signalements s
        JOIN
            utilisateurs u1 ON s.signaleur_id = u1.id
        JOIN
            utilisateurs u2 ON s.signale_id = u2.id
        WHERE 1=1
    ";
    $params = [];

    if ($statusFilter !== '') {
        $sql .= " AND s.statut = :statusFilter";
        $params[':statusFilter'] = $statusFilter;
    }
    // AJOUT DE LA CONDITION : n'ajouter le filtre que si le terme de recherche n'est PAS vide
    if ($reporterSearchTerm !== '') {
        $sql .= " AND (u1.email LIKE :reporterFilter OR u1.matricule LIKE :reporterFilter)";
        $params[':reporterFilter'] = '%' . $reporterSearchTerm . '%'; // Ajouter les wildcards ici
    }
    // AJOUT DE LA CONDITION : n'ajouter le filtre que si le terme de recherche n'est PAS vide
    if ($reportedSearchTerm !== '') {
        $sql .= " AND (u2.email LIKE :reportedFilter OR u2.matricule LIKE :reportedFilter)";
        $params[':reportedFilter'] = '%' . $reportedSearchTerm . '%'; // Ajouter les wildcards ici
    }

    $sql .= " ORDER BY s.date_signalement DESC";

    error_log("DEBUG Signalements List API: Requête SQL exécutée: " . $sql);
    error_log("DEBUG Signalements List API: Paramètres SQL: " . json_encode($params));


    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $signalements = $stmt->fetchAll();

    error_log("DEBUG Signalements List API: Nombre de signalements trouvés: " . count($signalements));

    sendJsonResponse(true, 'Liste des signalements récupérée.', ['signalements' => $signalements]);

} catch (PDOException $e) {
    error_log("Erreur BDD (signalements-list-api): " . $e->getMessage());
    sendJsonResponse(false, 'Erreur serveur lors de la récupération des signalements.');
}