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
    error_log("Erreur BDD (delete-person-api role check): " . $e->getMessage());
    sendJsonResponse(false, 'Erreur lors de la vérification de votre rôle.');
}

if ($loggedInUserRole !== 'Superviseur' && $loggedInUserRole !== 'Admin') {
    sendJsonResponse(false, 'Accès non autorisé pour supprimer des personnes.');
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (json_last_error() !== JSON_ERROR_NONE || !isset($data['person_id'])) {
    sendJsonResponse(false, 'ID de personne manquant ou invalide.');
}

$personId = (int)$data['person_id'];

try {
    $pdo->beginTransaction();

    // Vérifier que la personne existe
    $stmt = $pdo->prepare("SELECT id FROM personnes_interessees WHERE id = :id");
    $stmt->execute(['id' => $personId]);
    if (!$stmt->fetch()) {
        $pdo->rollBack();
        sendJsonResponse(false, 'Personne non trouvée.');
    }

    // La suppression des enregistrements liés (permis, physiques, FPR, procédures, signalements)
    // devrait être gérée par les FOREIGN KEY avec ON DELETE CASCADE sur les tables
    // (ex: permis_conduire, informations_physiques, fpr_records, procedure_personne, signalements)
    // C'est pourquoi nous utilisons les DROP TABLE avec ON DELETE CASCADE dans le script SQL complet.

    // Supprimer la personne
    $deleteStmt = $pdo->prepare("DELETE FROM personnes_interessees WHERE id = :id");
    $deleteStmt->execute(['id' => $personId]);

    $pdo->commit();
    sendJsonResponse(true, 'Personne et ses fichiers supprimés avec succès !');

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Erreur BDD (delete-person-api): " . $e->getMessage());
    sendJsonResponse(false, 'Erreur serveur lors de la suppression de la personne.');
}