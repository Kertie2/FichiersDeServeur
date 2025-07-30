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

    if ($loggedInUserRole !== 'Superviseur' && $loggedInUserRole !== 'Admin') {
        sendJsonResponse(false, 'Accès non autorisé à cette ressource.');
    }

    // Récupérer l'ID de l'utilisateur en attente depuis l'URL (paramètre GET)
    $userId = isset($_GET['id']) ? trim($_GET['id']) : null;

    if (!$userId) {
        sendJsonResponse(false, 'ID utilisateur manquant.');
    }

    // Récupérer tous les détails de l'utilisateur en attente
    $stmt = $pdo->prepare("SELECT id, email, nom, prenom, matricule, service, date_demande FROM en_attente WHERE id = :id");
    $stmt->execute(['id' => $userId]);
    $user_details = $stmt->fetch();

    if ($user_details) {
        sendJsonResponse(true, 'Détails du compte récupérés.', ['user' => $user_details]);
    } else {
        sendJsonResponse(false, 'Compte en attente non trouvé.');
    }

} catch (PDOException $e) {
    error_log("Erreur BDD (attentes-detail-api): " . $e->getMessage());
    sendJsonResponse(false, 'Erreur serveur lors de la récupération des détails.');
}