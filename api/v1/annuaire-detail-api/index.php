<?php
define('APP_RUNNING', true);
header('Content-Type: application/json');
session_start();

$config = require_once __DIR__ . '/../config/database.php';

function sendJsonResponse($success, $message, $data = []) {
    echo json_encode(['success' => $success, 'message' => $message] + $data);
    exit();
}

// Vérifier l'authentification (tout le monde peut voir les détails, sauf l'email)
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    sendJsonResponse(false, 'Non authentifié. Veuillez vous reconnecter.');
}

try {
    $dsn = "mysql:host={$config['host']};dbname={$config['db_name']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['username'], $config['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    $memberId = isset($_GET['id']) ? trim($_GET['id']) : null;

    if (!$memberId) {
        sendJsonResponse(false, 'ID de membre manquant.');
    }

    // Récupérer tous les détails SAUF L'EMAIL pour l'affichage public de l'annuaire
    $stmt = $pdo->prepare("SELECT id, matricule, nom, prenom, service, role, statut, date_creation FROM utilisateurs WHERE id = :id");
    $stmt->execute(['id' => $memberId]);
    $member_details = $stmt->fetch();

    if ($member_details) {
        // Enlève l'email pour s'assurer qu'il n'est pas envoyé par erreur (même si pas sélectionné)
        // unset($member_details['email']); // Si vous le sélectionnez par erreur

        sendJsonResponse(true, 'Détails du membre récupérés.', ['member' => $member_details]);
    } else {
        sendJsonResponse(false, 'Membre non trouvé.');
    }

} catch (PDOException $e) {
    error_log("Erreur BDD (annuaire-detail-api): " . $e->getMessage());
    sendJsonResponse(false, 'Erreur serveur lors de la récupération des détails du membre. ' . $e->getMessage());
}