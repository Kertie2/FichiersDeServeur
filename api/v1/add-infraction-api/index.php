<?php
define('APP_RUNNING', true);
header('Content-Type: application/json');
session_start();

$config = require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

function sendJsonResponse($success, $message, $data = []) {
    echo json_encode(['success' => $success, 'message' => $message] + $data);
    exit();
}

// Vérifier l'authentification et l'autorisation (Agent ou rôle au-dessus)
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
    error_log("Erreur BDD (add-infraction-api role check): " . $e->getMessage());
    sendJsonResponse(false, 'Erreur lors de la vérification de votre rôle.');
}

// Les rôles autorisés à ajouter des infractions (FNPC, TAJ, SIA, SIV - pas FPR)
$allowedRolesForInfraction = ['Agent', 'OPJ', 'Superviseur', 'Admin'];
if (!in_array($loggedInUserRole, $allowedRolesForInfraction)) {
    sendJsonResponse(false, 'Accès non autorisé. Seuls les agents et supérieurs peuvent ajouter des infractions.');
}


$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (json_last_error() !== JSON_ERROR_NONE || !isset($data['person_id']) || !isset($data['procedure_type_id']) || !isset($data['dynamic_data']) || !isset($data['agent_id'])) {
    sendJsonResponse(false, 'Données manquantes ou invalides.');
}

$personId = (int)$data['person_id'];
$procedureTypeId = $data['procedure_type_id'];
$dynamicData = $data['dynamic_data'];
$agentId = $data['agent_id'];

// Vérifier que l'agent ID correspond à l'utilisateur connecté (sécurité)
if ($agentId !== $loggedInUserId) {
    sendJsonResponse(false, 'ID de l\'agent non correspondant.');
}

try {
    // Vérifier si la personne existe
    $stmt = $pdo->prepare("SELECT id FROM personnes_interessees WHERE id = :id");
    $stmt->execute(['id' => $personId]);
    if (!$stmt->fetch()) {
        sendJsonResponse(false, 'La personne n\'existe pas.');
    }

    // Vérifier si le type de procédure existe et est actif
    $stmt = $pdo->prepare("SELECT name FROM procedure_types WHERE id = :id AND is_active = 1");
    $stmt->execute(['id' => $procedureTypeId]);
    $procedureType = $stmt->fetch();
    if (!$procedureType) {
        sendJsonResponse(false, 'Type de procédure invalide ou inactif.');
    }
    $procedureTypeName = $procedureType['name'];

    // Vérifier que le type de procédure n'est PAS FPR pour cette API
    if ($procedureTypeName === 'FPR') {
        sendJsonResponse(false, 'Les fiches FPR doivent être ajoutées via leur formulaire dédié.');
    }

    // Valider les champs dynamiques contre la configuration
    $stmt = $pdo->prepare("SELECT field_name, field_label, is_required, field_type FROM procedure_fields WHERE procedure_type_id = :type_id");
    $stmt->execute(['type_id' => $procedureTypeId]);
    $fieldsConfig = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $parsedDynamicData = [];
    foreach ($fieldsConfig as $field) {
        $fieldName = $field['field_name'];
        $fieldLabel = $field['field_label'];
        $isRequired = (bool)$field['is_required'];
        $value = $dynamicData[$fieldName] ?? null;

        if ($isRequired && (empty($value) && $value !== 0 && $value !== false)) { // Vérification si requis et vide/null/false (pour boolean 0)
            sendJsonResponse(false, "Le champ '{$fieldLabel}' est obligatoire.");
        }
        $parsedDynamicData[$fieldName] = $value;
    }

    // Générer un numéro de PV
    $numeroPv = uniqid('PV_');

    // Extraire les champs fixes (date, heure, lieu, description) qui DOIVENT être présents dans dynamic_data
    // Ces champs doivent être définis comme obligatoires dans les templates de procédure
    $dateHeureInfraction = ($parsedDynamicData['date_infraction'] ?? '') . ' ' . ($parsedDynamicData['heure_infraction'] ?? '');
    $lieuInfraction = $parsedDynamicData['lieu_infraction'] ?? 'Non spécifié';
    $descriptionFaits = $parsedDynamicData['description_faits'] ?? 'Aucune description';

    if (empty(trim($dateHeureInfraction))) {
        sendJsonResponse(false, "La date et l'heure d'infraction sont obligatoires (vérifiez les champs dynamiques).");
    }
    if (empty(trim($lieuInfraction))) {
        sendJsonResponse(false, "Le lieu d'infraction est obligatoire (vérifiez les champs dynamiques).");
    }


    $pdo->beginTransaction();

    // Insertion dans procedures_verbales
    $stmt = $pdo->prepare("
        INSERT INTO procedures_verbales 
        (numero_pv, agent_id, date_heure_infraction, lieu_infraction, type_infraction, description, data_json, statut) 
        VALUES (:numero_pv, :agent_id, :date_heure_infraction, :lieu_infraction, :type_infraction, :description, :data_json, 'en_cours')
    ");
    $stmt->execute([
        'numero_pv' => $numeroPv,
        'agent_id' => $agentId,
        'date_heure_infraction' => $dateHeureInfraction,
        'lieu_infraction' => $lieuInfraction,
        'type_infraction' => $procedureTypeName,
        'description' => $descriptionFaits,
        'data_json' => json_encode($parsedDynamicData),
        // 'statut' => 'en_cours' (défini par défaut dans la BDD)
    ]);
    $newProcedureId = $pdo->lastInsertId();

    // Lier la personne à la procédure
    $stmt = $pdo->prepare("INSERT INTO procedure_personne (procedure_id, personne_id, role_personne) VALUES (:procedure_id, :personne_id, 'suspect')");
    $stmt->execute(['procedure_id' => $newProcedureId, 'personne_id' => $personId]);

    $pdo->commit();
    sendJsonResponse(true, 'Infraction ajoutée avec succès !', ['procedure_id' => $newProcedureId]);

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Erreur BDD (add-infraction-api): " . $e->getMessage());
    sendJsonResponse(false, 'Erreur serveur lors de l\'ajout de l\'infraction: ' . $e->getMessage());
}