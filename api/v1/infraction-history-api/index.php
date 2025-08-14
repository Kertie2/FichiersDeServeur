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

    $personId = isset($_GET['person_id']) ? (int)$_GET['person_id'] : null;

    if (!$personId) {
        sendJsonResponse(false, 'ID de personne manquant pour l\'historique.');
    }

    // Récupérer les infractions liées à cette personne via la table de jonction
    $stmt = $pdo->prepare("
        SELECT
            pv.id,
            pv.numero_pv,
            pv.date_heure_infraction,
            pv.lieu_infraction,
            pv.type_infraction,
            pv.description,
            pv.statut,
            u.matricule AS agent_matricule,
            u.email AS agent_email
        FROM
            procedures_verbales pv
        JOIN
            procedure_personne pp ON pv.id = pp.procedure_id
        LEFT JOIN
            utilisateurs u ON pv.agent_id = u.id
        WHERE
            pp.personne_id = :person_id
        ORDER BY
            pv.date_heure_infraction DESC
    ");
    $stmt->execute(['person_id' => $personId]);
    $infractions = $stmt->fetchAll();

    sendJsonResponse(true, 'Historique des infractions récupéré.', ['infractions' => $infractions]);

} catch (PDOException $e) {
    error_log("Erreur BDD (infraction-history-api): " . $e->getMessage());
    sendJsonResponse(false, 'Erreur serveur lors de la récupération de l\'historique des infractions.');
}