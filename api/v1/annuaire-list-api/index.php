<?php
define('APP_RUNNING', true);
header('Content-Type: application/json');
session_start();

$config = require_once __DIR__ . '/../config/database.php';

function sendJsonResponse($success, $message, $data = []) {
    echo json_encode(['success' => $success, 'message' => $message] + $data);
    exit();
}

// Vérifier l'authentification (tout le monde peut voir l'annuaire si connecté)
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    sendJsonResponse(false, 'Non authentifié. Veuillez vous reconnecter.');
}

try {
    $dsn = "mysql:host={$config['host']};dbname={$config['db_name']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['username'], $config['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    $searchTerm = isset($_GET['search']) ? '%' . trim($_GET['search']) . '%' : '%';
    $serviceFilter = isset($_GET['service']) ? trim($_GET['service']) : '';

    $sql = "SELECT id, matricule, nom, prenom, service, role, statut FROM utilisateurs WHERE 1=1";
    $params = [];

    if ($searchTerm !== '%') {
        $sql .= " AND (nom LIKE :searchTerm OR prenom LIKE :searchTerm OR matricule LIKE :searchTerm)";
        $params[':searchTerm'] = $searchTerm;
    }
    if ($serviceFilter !== '') {
        $sql .= " AND service = :serviceFilter";
        $params[':serviceFilter'] = $serviceFilter;
    }

    $sql .= " ORDER BY nom ASC, prenom ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $members = $stmt->fetchAll();

    sendJsonResponse(true, 'Liste des membres récupérée.', ['members' => $members]);

} catch (PDOException $e) {
    error_log("Erreur BDD (annuaire-list-api): " . $e->getMessage());
    sendJsonResponse(false, 'Erreur serveur lors de la récupération des membres.');
}