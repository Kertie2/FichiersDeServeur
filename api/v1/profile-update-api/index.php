<?php
define('APP_RUNNING', true);
header('Content-Type: application/json');
session_start();

$config = require_once __DIR__ . '../config/database.php';

function sendJsonResponse($success, $message, $data = []) {
    echo json_encode(['success' => $success, 'message' => $message] + $data);
    exit();
}

if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    sendJsonResponse(false, 'Non authentifié. Veuillez vous reconnecter.');
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (json_last_error() !== JSON_ERROR_NONE || 
    !isset($data['user_id']) ||
    !isset($data['email']) ||
    !isset($data['nom']) ||
    !isset($data['prenom']) ||
    !isset($data['matricule']) ||
    !isset($data['service'])
) {
    sendJsonResponse(false, 'Données manquantes ou invalides.');
}

// Vérifier que l'ID utilisateur dans la session correspond à l'ID envoyé par le client (sécurité)
if ($data['user_id'] !== $_SESSION['user_id']) {
    sendJsonResponse(false, 'Accès non autorisé à la modification de ce profil.');
}

$userId = $data['user_id'];
$email = trim($data['email']);
$nom = trim($data['nom']);
$prenom = trim($data['prenom']);
$matricule = trim($data['matricule']);
$service = trim($data['service']);

// Validation côté serveur
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    sendJsonResponse(false, 'Format d\'email invalide.');
}
if (empty($nom) || empty($prenom) || empty($matricule) || empty($service)) {
    sendJsonResponse(false, 'Tous les champs sont requis.');
}

try {
    $dsn = "mysql:host={$config['host']};dbname={$config['db_name']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['username'], $config['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Vérifier si le nouvel email ou matricule est déjà utilisé par un autre utilisateur
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM utilisateurs WHERE (email = :email OR matricule = :matricule) AND id != :userId");
    $stmt->execute(['email' => $email, 'matricule' => $matricule, 'userId' => $userId]);
    if ($stmt->fetchColumn() > 0) {
        sendJsonResponse(false, 'Cet email ou matricule est déjà utilisé par un autre compte.');
    }

    $stmt = $pdo->prepare("UPDATE utilisateurs SET email = :email, nom = :nom, prenom = :prenom, matricule = :matricule, service = :service WHERE id = :userId");
    $stmt->execute([
        'email' => $email,
        'nom' => $nom,
        'prenom' => $prenom,
        'matricule' => $matricule,
        'service' => $service,
        'userId' => $userId
    ]);

    sendJsonResponse(true, 'Profil mis à jour avec succès !');

} catch (PDOException $e) {
    error_log("Erreur BDD (profile-update): " . $e->getMessage());
    sendJsonResponse(false, 'Une erreur est survenue lors de la mise à jour du profil. Veuillez réessayer.');
}