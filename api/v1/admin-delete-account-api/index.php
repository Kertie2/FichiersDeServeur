<?php
define('APP_RUNNING', true);
header('Content-Type: application/json');
session_start();

$config = require_once __DIR__ . '/../config/database.php';

function sendJsonResponse($success, $message, $data = []) {
    echo json_encode(['success' => $success, 'message' => $message] + $data);
    exit();
}

// Vérifier l'authentification et l'autorisation (Admin UNIQUEMENT)
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

    if ($loggedInUserRole !== 'Admin') { // Seuls les admins peuvent supprimer
        sendJsonResponse(false, 'Accès non autorisé pour supprimer un compte.');
    }

    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (json_last_error() !== JSON_ERROR_NONE || !isset($data['target_user_id'])) {
        sendJsonResponse(false, 'ID utilisateur manquant ou invalide.');
    }

    $targetUserId = $data['target_user_id'];

    // Empêcher un admin de supprimer son propre compte via cette API (pour éviter de se bloquer)
    if ($targetUserId === $_SESSION['user_id']) {
        sendJsonResponse(false, 'Vous ne pouvez pas supprimer votre propre compte via cette interface.');
    }

    $pdo->beginTransaction();

    // Supprimer les signalements liés à cet utilisateur (si vous avez mis ON DELETE CASCADE, ce n'est pas nécessaire explicitement)
    // $stmt = $pdo->prepare("DELETE FROM signalements WHERE signaleur_id = :id OR signale_id = :id");
    // $stmt->execute(['id' => $targetUserId]);

    // Supprimer l'utilisateur de la table 'utilisateurs'
    $deleteStmt = $pdo->prepare("DELETE FROM utilisateurs WHERE id = :id");
    $deleteStmt->execute(['id' => $targetUserId]);

    $pdo->commit();

    sendJsonResponse(true, 'Compte du membre supprimé avec succès !');

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Erreur BDD (admin-delete-account-api): " . $e->getMessage());
    sendJsonResponse(false, 'Erreur serveur lors de la suppression du compte.');
}