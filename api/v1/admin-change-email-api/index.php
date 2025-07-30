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

    $stmt = $pdo->prepare("SELECT role FROM utilisateurs WHERE id = :id");
    $stmt->execute(['id' => $_SESSION['user_id']]);
    $loggedInUserRole = $stmt->fetchColumn();

    if ($loggedInUserRole !== 'Superviseur' && $loggedInUserRole !== 'Admin') {
        sendJsonResponse(false, 'Accès non autorisé pour changer l\'email d\'un autre utilisateur.');
    }

    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (json_last_error() !== JSON_ERROR_NONE || !isset($data['target_user_id']) || !isset($data['new_email'])) {
        sendJsonResponse(false, 'Données manquantes ou invalides.');
    }

    $targetUserId = $data['target_user_id'];
    $newEmail = trim($data['new_email']);

    if (empty($newEmail) || !filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
        sendJsonResponse(false, 'Format d\'email invalide.');
    }

    // Empêcher de changer son propre email via cette API (utiliser la page de profil pour ça)
    if ($targetUserId === $_SESSION['user_id']) {
        sendJsonResponse(false, 'Utilisez la page "Mon profil" pour changer votre propre email.');
    }

    // Vérifier que l'utilisateur cible existe
    $stmt = $pdo->prepare("SELECT id FROM utilisateurs WHERE id = :id");
    $stmt->execute(['id' => $targetUserId]);
    if (!$stmt->fetch()) {
        sendJsonResponse(false, 'L\'utilisateur cible n\'existe pas.');
    }

    // Vérifier si le nouvel email n'est pas déjà utilisé par un autre compte
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM utilisateurs WHERE email = :email AND id != :targetUserId");
    $stmt->execute(['email' => $newEmail, 'targetUserId' => $targetUserId]);
    if ($stmt->fetchColumn() > 0) {
        sendJsonResponse(false, 'Cette adresse email est déjà utilisée par un autre compte.');
    }

    $updateStmt = $pdo->prepare("UPDATE utilisateurs SET email = :email WHERE id = :id");
    $updateStmt->execute(['email' => $newEmail, 'id' => $targetUserId]);

    sendJsonResponse(true, 'Adresse email du membre mise à jour avec succès !');

} catch (PDOException $e) {
    error_log("Erreur BDD (admin-change-email-api): " . $e->getMessage());
    sendJsonResponse(false, 'Erreur serveur lors du changement d\'email.');
}