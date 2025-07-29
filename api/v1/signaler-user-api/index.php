<?php
define('APP_RUNNING', true);
header('Content-Type: application/json');
session_start();

$config = require_once __DIR__ . '/../config/database.php';

function sendJsonResponse($success, $message, $data = []) {
    echo json_encode(['success' => $success, 'message' => $message] + $data);
    exit();
}

// Vérifier l'authentification (tout utilisateur connecté peut signaler)
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    sendJsonResponse(false, 'Non authentifié. Veuillez vous reconnecter.');
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (json_last_error() !== JSON_ERROR_NONE || !isset($data['target_user_id']) || !isset($data['type_signalement']) || !isset($data['description'])) {
    sendJsonResponse(false, 'Données manquantes ou invalides.');
}

$signaleurId = $_SESSION['user_id'];
$signaleId = $data['target_user_id'];
$typeSignalement = trim($data['type_signalement']);
$description = trim($data['description']);

// Empêcher un utilisateur de se signaler lui-même
if ($signaleurId === $signaleId) {
    sendJsonResponse(false, 'Vous ne pouvez pas vous signaler vous-même.');
}

try {
    $dsn = "mysql:host={$config['host']};dbname={$config['db_name']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['username'], $config['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Vérifier si l'utilisateur signalé existe
    $stmt = $pdo->prepare("SELECT id FROM utilisateurs WHERE id = :id");
    $stmt->execute(['id' => $signaleId]);
    if (!$stmt->fetch()) {
        sendJsonResponse(false, 'L\'utilisateur signalé n\'existe pas.');
    }

    // Insérer le signalement dans la base de données
    $stmt = $pdo->prepare("INSERT INTO signalements (signaleur_id, signale_id, type_signalement, description, statut) VALUES (:signaleur_id, :signale_id, :type_signalement, :description, 'en_attente')");
    $stmt->execute([
        'signaleur_id' => $signaleurId,
        'signale_id' => $signaleId,
        'type_signalement' => $typeSignalement,
        'description' => $description
    ]);

    sendJsonResponse(true, 'Membre signalé avec succès. Votre signalement sera examiné.');

} catch (PDOException $e) {
    error_log("Erreur BDD (signaler-user-api): " . $e->getMessage());
    sendJsonResponse(false, 'Erreur serveur lors du signalement.');
}