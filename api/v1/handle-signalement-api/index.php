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
        sendJsonResponse(false, 'Accès non autorisé pour traiter les signalements.');
    }

    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (json_last_error() !== JSON_ERROR_NONE || !isset($data['signalement_id']) || !isset($data['action']) || !isset($data['treated_by_id'])) {
        sendJsonResponse(false, 'Données manquantes ou invalides.');
    }

    $signalementId = $data['signalement_id'];
    $action = $data['action']; // 'process' ou 'reject'
    $treatedById = $data['treated_by_id'];

    if (!in_array($action, ['process', 'reject'])) {
        sendJsonResponse(false, 'Action invalide.');
    }

    $newStatus = ($action === 'process') ? 'traite' : 'rejete';

    $pdo->beginTransaction();

    // Mettre à jour le statut du signalement
    $stmt = $pdo->prepare("UPDATE signalements SET statut = :newStatus, date_traitement = NOW(), traite_par_id = :treatedById WHERE id = :signalementId AND statut = 'en_attente'");
    $stmt->execute([
        'newStatus' => $newStatus,
        'treatedById' => $treatedById,
        'signalementId' => $signalementId
    ]);

    if ($stmt->rowCount() === 0) {
        $pdo->rollBack();
        sendJsonResponse(false, 'Signalement non trouvé ou déjà traité/rejeté.');
    }

    $pdo->commit();

    sendJsonResponse(true, 'Signalement mis à jour avec succès.');

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Erreur BDD (handle-signalement-api): " . $e->getMessage());
    sendJsonResponse(false, 'Erreur serveur lors du traitement du signalement.');
}