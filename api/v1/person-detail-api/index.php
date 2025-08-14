<?php
define('APP_RUNNING', true);
header('Content-Type: application/json');
session_start();

$config = require_once __DIR__ . '/../config/database.php';

function sendJsonResponse($success, $message, $data = []) {
    echo json_encode(['success' => $success, 'message' => $message] + $data);
    exit();
}

// Vérifier l'authentification (tout agent/opj/superviseur/admin peut voir les détails)
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    sendJsonResponse(false, 'Non authentifié. Veuillez vous reconnecter.');
}

try {
    $dsn = "mysql:host={$config['host']};dbname={$config['db_name']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['username'], $config['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    $personId = isset($_GET['id']) ? (int)$_GET['id'] : null;

    if (!$personId) {
        sendJsonResponse(false, 'ID de personne manquant.');
    }

    // --- Vérification de l'activation des types de procédure (pour affichage conditionnel) ---
    $fprIsActive = false;
    $fnpcIsActive = false;
    $stmt = $pdo->query("SELECT name, is_active FROM procedure_types WHERE name IN ('FPR', 'FNPC')");
    $procedureTypesStatus = $stmt->fetchAll(PDO::FETCH_KEY_PAIR); // ['FPR' => 1, 'FNPC' => 1]
    if (isset($procedureTypesStatus['FPR']) && $procedureTypesStatus['FPR'] == 1) {
        $fprIsActive = true;
    }
    if (isset($procedureTypesStatus['FNPC']) && $procedureTypesStatus['FNPC'] == 1) {
        $fnpcIsActive = true;
    }


    // Récupérer les informations personnelles de base
    $stmt = $pdo->prepare("SELECT id, nom, prenom, email, date_naissance, lieu_naissance, adresse, ville, code_postal, nationalite, genre, telephone, informations_complementaires FROM personnes_interessees WHERE id = :id");
    $stmt->execute(['id' => $personId]);
    $person = $stmt->fetch();

    if (!$person) {
        sendJsonResponse(false, 'Personne non trouvée dans les fichiers.');
    }

    // Récupérer les informations physiques (LEFT JOIN car optionnel)
    $stmt = $pdo->prepare("SELECT taille_cm, poids_kg, couleur_cheveux, couleur_yeux, corpulence, signes_distinctifs FROM informations_physiques WHERE personne_id = :person_id");
    $stmt->execute(['person_id' => $personId]);
    $physicalInfo = $stmt->fetch();
    $person = array_merge($person, $physicalInfo ? $physicalInfo : [
        'taille_cm' => null, 'poids_kg' => null, 'couleur_cheveux' => null, 'couleur_yeux' => null, 'corpulence' => null, 'signes_distinctifs' => null
    ]);


    // Récupérer les informations de permis de conduire (LEFT JOIN car optionnel, et si FNPC est actif)
    if ($fnpcIsActive) {
        $stmt = $pdo->prepare("SELECT numero_permis, categorie_permis, points_restants, statut_permis FROM permis_conduire WHERE personne_id = :person_id");
        $stmt->execute(['person_id' => $personId]);
        $licenseInfo = $stmt->fetch();
        $person = array_merge($person, $licenseInfo ? $licenseInfo : [
            'permis_numero' => null, 'permis_categorie' => null, 'permis_points_restants' => null, 'permis_statut' => null
        ]);
    } else {
        $person = array_merge($person, [
            'permis_numero' => null, 'permis_categorie' => null, 'permis_points_restants' => null, 'permis_statut' => null
        ]);
    }


    // Récupérer le statut FPR (LEFT JOIN car optionnel, et si FPR est actif)
    if ($fprIsActive) {
        $stmt = $pdo->prepare("SELECT r.is_wanted, r.reason, u.matricule AS fpr_wanted_by_agent_matricule, r.date_wanted FROM fpr_records r LEFT JOIN utilisateurs u ON r.wanted_by_agent_id = u.id WHERE r.personne_id = :person_id");
        $stmt->execute(['person_id' => $personId]);
        $fprInfo = $stmt->fetch();
        $person = array_merge($person, $fprInfo ? $fprInfo : [
            'fpr_is_wanted' => false, 'fpr_reason' => null, 'fpr_wanted_by_agent_matricule' => null, 'fpr_date_wanted' => null
        ]);
    } else {
        $person = array_merge($person, [
            'fpr_is_wanted' => false, 'fpr_reason' => null, 'fpr_wanted_by_agent_matricule' => null, 'fpr_date_wanted' => null
        ]);
    }

    sendJsonResponse(true, 'Détails de la personne récupérés.', [
        'person' => $person,
        'fpr_is_active' => $fprIsActive, // Passer l'état d'activation pour le JS
        'fnpc_is_active' => $fnpcIsActive // Passer l'état d'activation pour le JS
    ]);

} catch (PDOException $e) {
    error_log("Erreur BDD (person-detail-api): " . $e->getMessage());
    sendJsonResponse(false, 'Erreur serveur lors de la récupération des détails de la personne.');
}