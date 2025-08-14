<?php
define('APP_RUNNING', true);
header('Content-Type: application/json');
session_start();

$config = require_once __DIR__ . '/../config/database.php';

function sendJsonResponse($success, $message, $data = []) {
    echo json_encode(['success' => $success, 'message' => $message] + $data);
    exit();
}

// Vérifier l'authentification et l'autorisation (Superviseur / Admin)
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    sendJsonResponse(false, 'Non authentifié. Veuillez vous reconnecter.');
}

$loggedInUserId = $_SESSION['user_id'];
$loggedInUserRole = null;
try {
    $dsn = "mysql:host={$config['host']};dbname={$config['db_name']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['username'], $config['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    $stmt = $pdo->prepare("SELECT role FROM utilisateurs WHERE id = :id");
    $stmt->execute(['id' => $loggedInUserId]);
    $loggedInUserRole = $stmt->fetchColumn();
} catch (PDOException $e) {
    error_log("Erreur BDD (delete-infraction-api role check): " . $e->getMessage());
    sendJsonResponse(false, 'Erreur lors de la vérification de votre rôle.');
}

if ($loggedInUserRole !== 'Superviseur' && $loggedInUserRole !== 'Admin') {
    sendJsonResponse(false, 'Accès non autorisé pour supprimer des infractions.');
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (json_last_error() !== JSON_ERROR_NONE || !isset($data['infraction_id'])) {
    sendJsonResponse(false, 'ID d\'infraction manquant ou invalide.');
}

$infractionId = (int)$data['infraction_id'];

try {
    $pdo->beginTransaction();

    // Vérifier si l'infraction existe
    $stmt = $pdo->prepare("SELECT id FROM procedures_verbales WHERE id = :id");
    $stmt->execute(['id' => $infractionId]);
    if (!$stmt->fetch()) {
        $pdo->rollBack();
        sendJsonResponse(false, 'Infraction non trouvée.');
    }

    // La suppression des liens dans procedure_personne devrait être gérée par ON DELETE CASCADE.
    // Supprimer l'infraction
    $deleteStmt = $pdo->prepare("DELETE FROM procedures_verbales WHERE id = :id");
    $deleteStmt->execute(['id' => $infractionId]);

    $pdo->commit();
    sendJsonResponse(true, 'Infraction supprimée avec succès !');

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Erreur BDD (delete-infraction-api): " . $e->getMessage());
    sendJsonResponse(false, 'Erreur serveur lors de la suppression de l\'infraction.');
}