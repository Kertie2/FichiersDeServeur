<?php
define('APP_RUNNING', true);
header('Content-Type: application/json');
session_start();

$config = require_once __DIR__ . '../config/database.php';

function sendJsonResponse($success, $message, $data = []) {
    echo json_encode(['success' => $success, 'message' => $message] + $data);
    exit();
}

if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    sendJsonResponse(false, 'Non authentifié. Veuillez vous reconnecter.');
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (json_last_error() !== JSON_ERROR_NONE || 
    !isset($data['user_id']) ||
    !isset($data['current_password']) ||
    !isset($data['new_password']) ||
    !isset($data['confirm_new_password'])
) {
    sendJsonResponse(false, 'Données manquantes ou invalides.');
}

// Vérifier que l'ID utilisateur dans la session correspond à l'ID envoyé par le client (sécurité)
if ($data['user_id'] !== $_SESSION['user_id']) {
    sendJsonResponse(false, 'Accès non autorisé à la modification de ce mot de passe.');
}

$userId = $data['user_id'];
$currentPassword = $data['current_password'];
$newPassword = $data['new_password'];
$confirmNewPassword = $data['confirm_new_password'];

// Validation côté serveur
if (empty($currentPassword) || empty($newPassword) || empty($confirmNewPassword)) {
    sendJsonResponse(false, 'Tous les champs de mot de passe sont requis.');
}
if ($newPassword !== $confirmNewPassword) {
    sendJsonResponse(false, 'Le nouveau mot de passe et sa confirmation ne correspondent pas.');
}
if (strlen($newPassword) < 8) {
    sendJsonResponse(false, 'Le nouveau mot de passe doit contenir au moins 8 caractères.');
}
if ($newPassword === $currentPassword) {
    sendJsonResponse(false, 'Le nouveau mot de passe doit être différent de l\'ancien.');
}

try {
    $dsn = "mysql:host={$config['host']};dbname={$config['db_name']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['username'], $config['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Vérifier le mot de passe actuel
    $stmt = $pdo->prepare("SELECT password FROM utilisateurs WHERE id = :userId");
    $stmt->execute(['userId' => $userId]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($currentPassword, $user['password'])) {
        sendJsonResponse(false, 'Le mot de passe actuel est incorrect.');
    }

    // Hacher le nouveau mot de passe
    $hashedNewPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    if ($hashedNewPassword === false) {
        error_log("Erreur de hachage de mot de passe pour l'utilisateur ID: " . $userId);
        sendJsonResponse(false, 'Une erreur interne est survenue lors du traitement du nouveau mot de passe.');
    }

    // Mettre à jour le mot de passe
    $updateStmt = $pdo->prepare("UPDATE utilisateurs SET password = :password WHERE id = :userId");
    $updateStmt->execute(['password' => $hashedNewPassword, 'userId' => $userId]);

    sendJsonResponse(true, 'Mot de passe changé avec succès !');

} catch (PDOException $e) {
    error_log("Erreur BDD (password-change): " . $e->getMessage());
    sendJsonResponse(false, 'Une erreur est survenue lors du changement de mot de passe. Veuillez réessayer.');
}