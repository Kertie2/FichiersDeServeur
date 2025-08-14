<?php
define('APP_RUNNING', true);
header('Content-Type: application/json');
session_start();

$config = require_once __DIR__ . '/../config/database.php';

function sendJsonResponse($success, $message, $data = []) {
    echo json_encode(['success' => $success, 'message' => $message] + $data);
    exit();
}

// Vérifier l'authentification (tout agent/opj/superviseur/admin peut rechercher)
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    sendJsonResponse(false, 'Non authentifié. Veuillez vous reconnecter.');
}

try {
    $dsn = "mysql:host={$config['host']};dbname={$config['db_name']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['username'], $config['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Terme de recherche par nom, prénom, ou email (pas de matricule pour les citoyens)
    $searchTerm = isset($_GET['search']) ? '%' . trim($_GET['search']) . '%' : null;

    $sql = "SELECT id, nom, prenom, email, date_naissance FROM personnes_interessees WHERE 1=1";
    $params = [];

    if ($searchTerm !== null) {
        $sql .= " AND (nom LIKE :searchTerm OR prenom LIKE :searchTerm OR email LIKE :searchTerm)";
        $params[':searchTerm'] = $searchTerm;
    }

    $sql .= " ORDER BY nom ASC, prenom ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $persons = $stmt->fetchAll();

    sendJsonResponse(true, 'Liste des personnes récupérée.', ['persons' => $persons]);

} catch (PDOException $e) {
    error_log("Erreur BDD (persons-list-api): " . $e->getMessage());
    sendJsonResponse(false, 'Erreur serveur lors de la récupération des personnes.');
}