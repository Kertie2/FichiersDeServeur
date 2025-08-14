<?php
define('APP_RUNNING', true);
header('Content-Type: application/json');
session_start();

$config = require_once __DIR__ . '/../config/database.php';

function sendJsonResponse($success, $message, $data = []) {
    echo json_encode(['success' => $success, 'message' => $message] + $data);
    exit();
}

// Vérifier l'authentification (tout agent/opj/superviseur/admin peut ajouter une personne)
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    sendJsonResponse(false, 'Non authentifié. Veuillez vous reconnecter.');
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Validation basique des données (ajoutez d'autres validations selon les champs requis)
if (json_last_error() !== JSON_ERROR_NONE || !isset($data['nom']) || !isset($data['prenom'])) {
    sendJsonResponse(false, 'Données manquantes ou invalides (nom et prénom requis).');
}

$nom = trim($data['nom']);
$prenom = trim($data['prenom']);
$email = trim($data['email'] ?? '');
$dateNaissance = trim($data['date_naissance'] ?? '');
$lieuNaissance = trim($data['lieu_naissance'] ?? '');
$adresse = trim($data['adresse'] ?? '');
$ville = trim($data['ville'] ?? '');
$codePostal = trim($data['code_postal'] ?? '');
$nationalite = trim($data['nationalite'] ?? 'Française');
$genre = trim($data['genre'] ?? '');
$telephone = trim($data['telephone'] ?? '');
$informationsComplementaires = trim($data['informations_complementaires'] ?? '');

try {
    $dsn = "mysql:host={$config['host']};dbname={$config['db_name']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['username'], $config['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    $pdo->beginTransaction();

    // Vérifier si la personne existe déjà par nom/prénom/date_naissance (si date_naissance est fournie)
    // Ne vérifie plus par matricule car c'est pour les citoyens sans matricule.
    if (!empty($dateNaissance)) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM personnes_interessees WHERE nom = :nom AND prenom = :prenom AND date_naissance = :date_naissance");
        $stmt->execute(['nom' => $nom, 'prenom' => $prenom, 'date_naissance' => $dateNaissance]);
        if ($stmt->fetchColumn() > 0) {
            $pdo->rollBack();
            sendJsonResponse(false, 'Une personne avec ce nom, prénom et date de naissance existe déjà.');
        }
    }


    // Insertion dans personnes_interessees
    $stmt = $pdo->prepare("
        INSERT INTO personnes_interessees 
        (nom, prenom, email, date_naissance, lieu_naissance, adresse, ville, code_postal, nationalite, genre, telephone, informations_complementaires) 
        VALUES (:nom, :prenom, :email, :date_naissance, :lieu_naissance, :adresse, :ville, :code_postal, :nationalite, :genre, :telephone, :informations_complementaires)
    ");
    $stmt->execute([
        'nom' => $nom,
        'prenom' => $prenom,
        'email' => !empty($email) ? $email : null,
        'date_naissance' => !empty($dateNaissance) ? $dateNaissance : null,
        'lieu_naissance' => !empty($lieuNaissance) ? $lieuNaissance : null,
        'adresse' => !empty($adresse) ? $adresse : null,
        'ville' => !empty($ville) ? $ville : null,
        'code_postal' => !empty($codePostal) ? $codePostal : null,
        'nationalite' => !empty($nationalite) ? $nationalite : 'Française',
        'genre' => !empty($genre) ? $genre : null,
        'telephone' => !empty($telephone) ? $telephone : null,
        'informations_complementaires' => !empty($informationsComplementaires) ? $informationsComplementaires : null
    ]);
    $newPersonId = $pdo->lastInsertId(); // Récupérer l'ID auto-incrémenté de la nouvelle personne

    $pdo->commit();
    sendJsonResponse(true, 'Personne ajoutée avec succès aux fichiers !', ['person_id' => $newPersonId]);

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Erreur BDD (add-person-api): " . $e->getMessage());
    sendJsonResponse(false, 'Erreur serveur lors de l\'ajout de la personne.');
}