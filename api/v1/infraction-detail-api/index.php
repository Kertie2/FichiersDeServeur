<?php
define('APP_RUNNING', true);
header('Content-Type: application/json');
session_start();

$config = require_once __DIR__ . '/../config/database.php';

function sendJsonResponse($success, $message, $data = []) {
    echo json_encode(['success' => $success, 'message' => $message] + $data);
    exit();
}

// Vérifier l'authentification
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    sendJsonResponse(false, 'Non authentifié. Veuillez vous reconnecter.');
}

try {
    $dsn = "mysql:host={$config['host']};dbname={$config['db_name']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['username'], $config['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    $infractionId = isset($_GET['id']) ? (int)$_GET['id'] : null;

    if (!$infractionId) {
        sendJsonResponse(false, 'ID d\'infraction manquant.');
    }

    // Récupérer tous les détails de l'infraction, y compris les données JSON et l'agent
    $stmt = $pdo->prepare("
        SELECT
            pv.id,
            pv.numero_pv,
            pv.date_heure_infraction,
            pv.lieu_infraction,
            pv.type_infraction,
            pv.description,
            pv.data_json, -- Le champ JSON pour les données dynamiques
            pv.statut,
            pv.notes_internes,
            u.matricule AS agent_matricule,
            u.nom AS agent_nom,
            u.prenom AS agent_prenom
        FROM
            procedures_verbales pv
        LEFT JOIN
            utilisateurs u ON pv.agent_id = u.id
        WHERE
            pv.id = :id
    ");
    $stmt->execute(['id' => $infractionId]);
    $infraction = $stmt->fetch();

    if ($infraction) {
        sendJsonResponse(true, 'Détails de l\'infraction récupérés.', ['infraction' => $infraction]);
    } else {
        sendJsonResponse(false, 'Infraction non trouvée.');
    }

} catch (PDOException $e) {
    error_log("Erreur BDD (infraction-detail-api): " . $e->getMessage());
    sendJsonResponse(false, 'Erreur serveur lors de la récupération des détails de l\'infraction.');
}